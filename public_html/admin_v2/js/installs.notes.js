// Notes module for installs
// Requires: installs.utils.js (debounce, linkify, formatDateDisplay, escapeHtml)

(function(){
  const NOTES_API = 'notes_api.php';
  // Expose for other modules
  window.NOTES_API = NOTES_API;
  let notesAutoRenderQueued = false;

  function queueNotesAutoRender() {
    if (notesAutoRenderQueued || typeof window.render !== 'function') return;
    notesAutoRenderQueued = true;
    setTimeout(() => {
      notesAutoRenderQueued = false;
      window.render();
    }, 0);
  }

  // Persist order of notes for a given day element
  function persistNoteOrder(dayEl) {
    const date = dayEl.dataset.date;
    const ids = Array.from(dayEl.querySelectorAll('.note')).map(n => Number(n.dataset.id));
    fetch(NOTES_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ reorder: true, date, order: ids })
    })
      .then(r => r.json())
      .then(d => { if (!d.success) console.warn('Failed to persist note order:', d); })
      .catch(err => console.warn('Order persist error', err));
  }
  window.persistNoteOrder = persistNoteOrder;

  function moveNoteToDate(id, destDate) {
    fetch(NOTES_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: Number(id), date: destDate })
    })
      .then(r => r.json())
      .then(d => {
        if (d.success) { if (typeof window.render === 'function') window.render(); }
        else alert(d.message || 'Failed to move note');
      })
      .catch(() => alert('Failed to move note'));
  }
  window.moveNoteToDate = moveNoteToDate;

  const notesBulkCache = new Map();
  const notesBulkPending = new Map();
  let notesBulkFlushTimer = null;

  function flushNotesBulkFetch() {
    const pending = Array.from(notesBulkPending.entries());
    notesBulkPending.clear();
    notesBulkFlushTimer = null;
    if (!pending.length) return;

    const dates = pending.map(([date]) => date);
    const resolversByDate = new Map(pending);
    const url = `installs.php?get_notes_bulk=1&dates=${encodeURIComponent(dates.join(','))}&t=${Date.now()}`;
    const load = () => fetch(url, { cache: 'no-store' });
    const fetchPromise = typeof window.scheduleInstallApiFetch === 'function'
      ? window.scheduleInstallApiFetch(load)
      : load();

    fetchPromise
      .then(async res => {
        const contentType = res.headers.get('content-type') || '';
        const raw = await res.text();
        if (!res.ok) throw new Error(`Notes bulk HTTP ${res.status}: ${raw}`);
        if (!contentType.includes('application/json')) throw new Error(`Notes bulk non-JSON: ${raw}`);
        return JSON.parse(raw);
      })
      .then(data => {
        const notesByDate = data && data.success && data.notes ? data.notes : {};
        dates.forEach(date => {
          const notes = Array.isArray(notesByDate[date]) ? notesByDate[date] : [];
          notesBulkCache.set(date, notes);
          (resolversByDate.get(date) || []).forEach(resolve => resolve(notes));
        });
      })
      .catch(err => {
        console.error('Error fetching bulk notes', err);
        dates.forEach(date => {
          notesBulkCache.set(date, []);
          (resolversByDate.get(date) || []).forEach(resolve => resolve([]));
        });
      });
  }

  async function fetchNotes(date) {
    if (notesBulkCache.has(date)) return notesBulkCache.get(date);
    return new Promise(resolve => {
      const list = notesBulkPending.get(date) || [];
      list.push(resolve);
      notesBulkPending.set(date, list);
      if (!notesBulkFlushTimer) {
        notesBulkFlushTimer = setTimeout(flushNotesBulkFetch, 0);
      }
    });
  }
  window.fetchNotes = fetchNotes;

  function localDateString(date = new Date()) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  function parseNoteDate(value) {
    if (!value) return null;
    const text = String(value).trim();
    const match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (match) {
      return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
    }
    const parsed = new Date(text);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  function getNoteAgeDays(note) {
    const created = parseNoteDate(note.created_at || note.date);
    if (!created) return 0;
    const today = parseNoteDate(localDateString());
    const createdDay = new Date(created.getFullYear(), created.getMonth(), created.getDate());
    return Math.max(0, Math.floor((today - createdDay) / 86400000));
  }

  function formatNoteAgeLabel(days) {
    return String(Math.max(0, Number(days) || 0));
  }

  function isBeforeToday(value) {
    const noteDate = parseNoteDate(value);
    const today = parseNoteDate(localDateString());
    if (!noteDate || !today) return false;
    const noteDay = new Date(noteDate.getFullYear(), noteDate.getMonth(), noteDate.getDate());
    return noteDay < today;
  }

  async function moveOpenNotesToToday(notes, dateStr) {
    const today = localDateString();
    if (dateStr === today) return notes;

    const openOldNotes = notes.filter(note => {
      const isDone = !!(note.is_done && String(note.is_done) !== '0');
      return !isDone && isBeforeToday(note.date || dateStr);
    });

    if (!openOldNotes.length) return notes;

    await Promise.all(openOldNotes.map(note =>
      fetch(NOTES_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: Number(note.id), date: today })
      }).catch(err => console.warn('Failed to auto-move note', note.id, err))
    ));

    queueNotesAutoRender();

    return notes.filter(note => !openOldNotes.some(moved => String(moved.id) === String(note.id)));
  }

  async function autoMarkOldNotesDone(notes) {
    const staleOpenNotes = notes.filter(note => {
      const isDone = !!(note.is_done && String(note.is_done) !== '0');
      return !isDone && getNoteAgeDays(note) > 14;
    });

    if (!staleOpenNotes.length) return notes;

    const results = await Promise.all(staleOpenNotes.map(note =>
      fetch(NOTES_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: Number(note.id), is_done: 1 })
      })
        .then(r => r.json())
        .then(d => ({ ok: !!(d && d.success), id: String(note.id) }))
        .catch(err => {
          console.warn('Failed to auto-mark old note as done', note.id, err);
          return { ok: false, id: String(note.id) };
        })
    ));

    const doneIds = new Set(results.filter(r => r.ok).map(r => r.id));
    if (!doneIds.size) return notes;

    queueNotesAutoRender();

    return notes.map(note => (
      doneIds.has(String(note.id))
        ? { ...note, is_done: 1 }
        : note
    ));
  }

  function saveNote(note) {
    fetch(NOTES_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(note)
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          if (typeof window.render === 'function') window.render();
        } else {
          alert(data.message || 'Failed to save note');
        }
      })
      .catch(() => alert('Failed to save note'));
  }
  window.saveNote = saveNote;

  function closeNoteForm() {
    const wrap = document.getElementById('note-popup-wrap');
    const backdrop = document.getElementById('note-popup-backdrop');
    if (wrap) wrap.remove();
    if (backdrop) backdrop.remove();
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
  }
  window.closeNoteForm = closeNoteForm;

  function editNote(note) { openNoteForm(note.date, note); }
  window.editNote = editNote;

  function deleteNote(id) {
    if (!id) return;
    if (!confirm('Delete this note?')) return;
    fetch(NOTES_API, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    })
      .then(r => r.json())
      .then(data => { if (data.success) { if (typeof window.render === 'function') window.render(); } else alert(data.message || 'Failed to delete note'); })
      .catch(() => alert('Failed to delete note'));
  }
  window.deleteNote = deleteNote;

  function toggleNoteDone(id, isDone) {
    if (!id) return;
    fetch(NOTES_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, is_done: isDone ? 1 : 0 })
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          if (typeof window.render === 'function') window.render();
        } else {
          alert(data.message || 'Failed to update note');
        }
      })
      .catch(() => alert('Failed to update note'));
  }
  window.toggleNoteDone = toggleNoteDone;

  function openNoteForm(date, note = null) {
    closeNoteForm();
    const isEdit = !!(note && note.id);
    const backdrop = document.createElement('div');
    backdrop.id = 'note-popup-backdrop';
    backdrop.setAttribute('role', 'presentation');
    backdrop.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2147483646;';

    const wrap = document.createElement('div');
    wrap.id = 'note-popup-wrap';
    wrap.setAttribute('role', 'dialog');
    wrap.setAttribute('aria-modal', 'true');
    wrap.setAttribute('aria-label', isEdit ? 'Edit quick note' : 'Add quick note');
    wrap.style.cssText = 'position:fixed;inset:0;display:flex;align-items:center;justify-content:center;padding:24px;z-index:2147483647;';

    const form = document.createElement('form');
    form.id = 'noteForm';
    form.className = 'note-popup';
    form.innerHTML = `
      <h3 style="margin:0 0 12px 0;font-size:16px;">${isEdit ? 'Edit Quick Note' : 'Add Quick Note'} (${formatDateDisplay(date)})</h3>
      <label class="np-field" style="display:grid;grid-template-columns:110px 1fr;align-items:center;gap:10px;margin:10px 0;">
        <span style="font-weight:600;">Phone or Name</span>
        <div class="np-phone-wrap">
          <input type="search" id="notePhone" name="phone" placeholder="Search phone or name" style="width:100%;padding:8px 10px;border:2px solid #1976d2;background:#e3f2fd;border-radius:8px;font:inherit;outline:none;" />
          <div id="phoneSuggestions" class="np-suggest" aria-label="Matching orders"></div>
        </div>
      </label>
      <label class="np-field" style="display:grid;grid-template-columns:110px 1fr;align-items:center;gap:10px;margin:10px 0;">
        <span style="font-weight:600;">Type</span>
        <select id="noteType" name="type" style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px;font:inherit;outline:none;">
          <option value="General">General</option>
          <option value="Issue">Issue</option>
          <option value="Inspection">Inspection</option>
        </select>
      </label>
      <label class="np-field" style="display:grid;grid-template-columns:110px 1fr;align-items:center;gap:10px;margin:10px 0;">
        <span style="font-weight:600;">Title</span>
        <input type="text" id="noteTitle" name="title" required style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px;font:inherit;outline:none;" />
      </label>
      <label class="np-field" style="display:grid;grid-template-columns:110px 1fr;align-items:center;gap:10px;margin:10px 0;">
        <span style="font-weight:600;">Description</span>
        <textarea id="noteDesc" name="description" rows="4" style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px;font:inherit;outline:none;resize:vertical;"></textarea>
      </label>
      <input type="hidden" id="noteDate" name="date" value="${date}">
      <input type="hidden" id="noteId" name="id" value="${isEdit ? note.id : ''}">
      <div class="np-actions" style="display:flex;justify-content:flex-end;gap:10px;margin-top:12px;">
        <button type="submit" class="np-save" style="padding:8px 12px;border-radius:8px;border:1px solid transparent;background:#111;color:#fff;cursor:pointer;font:inherit;">${isEdit ? 'Update' : 'Save'}</button>
        <button type="button" class="np-cancel" id="np-cancel-btn" style="padding:8px 12px;border-radius:8px;border:1px solid #e6e6e6;background:#f2f2f2;color:#333;cursor:pointer;font:inherit;">Cancel</button>
      </div>
    `;
    form.style.cssText = 'width:min(520px,92vw);background:#fff;border-radius:12px;box-shadow:0 14px 40px rgba(0,0,0,0.25);padding:18px 18px 14px;max-height:85vh;overflow:auto;font-size:14px;';

    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';

    wrap.appendChild(form);
    document.body.appendChild(backdrop);
    document.body.appendChild(wrap);

    const phoneInput = document.getElementById('notePhone');
    const descInput = document.getElementById('noteDesc');
    const titleInput = document.getElementById('noteTitle');
    const saveBtn = form.querySelector('.np-save');
    if (saveBtn && !saveBtn.dataset.label) saveBtn.dataset.label = saveBtn.textContent || '';
    const setSaveLoading = (on) => {
      if (!saveBtn) return;
      if (on) saveBtn.classList.add('is-loading');
      else saveBtn.classList.remove('is-loading');
      saveBtn.disabled = true;
    };
    const syncSaveDisabled = () => {
      if (!saveBtn) return;
      const hasTitle = !!(titleInput && titleInput.value.trim());
      const isLoading = saveBtn.classList.contains('is-loading');
      saveBtn.disabled = isLoading || !hasTitle;
    };
    if (titleInput) titleInput.addEventListener('input', syncSaveDisabled);
    syncSaveDisabled();

    if (isEdit) {
      document.getElementById('noteType').value = note.type || 'General';
      document.getElementById('noteTitle').value = note.title || '';
      document.getElementById('noteDesc').value = note.description || '';
      if (note.phone) document.getElementById('notePhone').value = note.phone;
    }

    // Focus on Phone or Name field for both Add and Edit
    setTimeout(() => {
      if (phoneInput) {
        phoneInput.focus();
        // Move cursor to end if there's a value
        const val = phoneInput.value;
        phoneInput.value = '';
        phoneInput.value = val;
      }
    }, 150);

    // Make | a line separator in Description (input and paste)
    const replacePipe = (e) => {
      if (e.target.value.includes('|')) {
        const start = e.target.selectionStart;
        e.target.value = e.target.value.replace(/\|/g, '\n');
        e.target.setSelectionRange(start, start);
      }
    };
    if (descInput) {
      descInput.addEventListener('input', replacePipe);
      descInput.addEventListener('paste', () => setTimeout(() => replacePipe({ target: descInput }), 0));
    }
    const suggBox = document.getElementById('phoneSuggestions');

    function orderToPreview(o){
      const parts = [];
      if (o.id) parts.push(`ID: ${o.id}`);
      if (o.name) parts.push(`Name: ${o.name}`);
      if (o.phone) parts.push(`Phone: ${o.phone}`);
      if (o.date) parts.push(`Date: ${o.date}`);
      if (o.location) parts.push(`Location: ${o.location}`);
      if (o.type) parts.push(`Type: ${o.type}`);
      if (o.cams) parts.push(`Cams: ${o.cams}`);
      if (o.resolution) parts.push(`Resolution: ${o.resolution}`);
      if (o.hdd) parts.push(`HDD: ${o.hdd}`);
      if (o.map) parts.push(`Map: ${o.map}`);
      return parts.join(' | ');
    }

    function renderSuggestions(list){
      if (!list || !list.length) { suggBox.innerHTML = ''; suggBox.style.display = 'none'; return; }
      suggBox.innerHTML = list.map(o => `
        <div class="np-suggest-item" data-id="${o.id}">
          <div class="np-s-line1"><strong>${o.name || 'Unknown'}</strong> · ${o.phone || ''}</div>
          <div class="np-s-line2">${o.date || ''} · ${o.location || ''} · ${o.cams ? (o.cams + ' cams') : ''}</div>
        </div>
      `).join('');
      suggBox.style.display = 'block';

      suggBox.querySelectorAll('.np-suggest-item').forEach(el => {
        el.addEventListener('mouseenter', () => {
          const id = el.getAttribute('data-id');
          const hit = latestResults.find(r => String(r.id) === String(id));
          if (hit) document.getElementById('noteDesc').value = orderToPreview(hit);
        });
        el.addEventListener('click', async (evt) => {
          evt.stopPropagation();
          const id = el.getAttribute('data-id');
          const hit = latestResults.find(r => String(r.id) === String(id));
          if (hit) {
            console.log('Suggestion clicked:', hit);
            setSaveLoading(true);
            try {
              phoneInput.value = hit.phone || '';
              const titleField = document.getElementById('noteTitle');
              if (titleField) titleField.value = '';
              syncSaveDisabled();
              
              let areaName = hit.location || '';
              const mapsRegex = /https?:\/\/[^\s]*(?:maps|goo\.gl)[^\s]*/i;
              let locationToGeocode = hit.location || '';
              const desc = orderToPreview(hit) || '';
              
              if (!locationToGeocode.match(mapsRegex) && desc.match(mapsRegex)) {
                locationToGeocode = desc.match(mapsRegex)[0];
                console.log('Found map URL in description:', locationToGeocode);
              }

              if (locationToGeocode && typeof google !== 'undefined' && google.maps && google.maps.Geocoder) {
                try {
                  if (locationToGeocode.match(mapsRegex)) {
                    const resolveRes = await fetch(`../smart/resolve_map_url.php?url=${encodeURIComponent(locationToGeocode)}`);
                    const resolveData = await resolveRes.json();
                    if (resolveData.success && resolveData.coords) {
                      locationToGeocode = `${resolveData.coords.lat},${resolveData.coords.lng}`;
                    }
                  }

                  const geocoder = new google.maps.Geocoder();
                  const geoRes = await new Promise((resolve, reject) => {
                    const request = locationToGeocode.includes(',') && !isNaN(parseFloat(locationToGeocode))
                      ? { location: { lat: parseFloat(locationToGeocode.split(',')[0]), lng: parseFloat(locationToGeocode.split(',')[1]) } }
                      : { address: locationToGeocode };
                    geocoder.geocode(request, (results, status) => {
                      if (status === 'OK' && results[0]) resolve(results[0]);
                      else reject(status);
                    });
                  });
                  
                  const addr = geoRes.address_components;
                  const subLoc = addr.find(c => c.types.includes('sublocality_level_1')) || 
                                 addr.find(c => c.types.includes('sublocality')) || 
                                 addr.find(c => c.types.includes('locality')) ||
                                 addr.find(c => c.types.includes('neighborhood'));
                  if (subLoc) areaName = subLoc.long_name;
                  console.log('Geocoded area name:', areaName);
                } catch (e) {
                  console.warn('Geocoding failed for', locationToGeocode, e);
                }
              }
              
              const newTitle = [areaName, hit.name, hit.phone].filter(Boolean).join(' | ');
              if (titleField) titleField.value = newTitle;
              console.log('New Title set:', newTitle);
              
              document.getElementById('noteDesc').value = orderToPreview(hit);
            } finally {
              if (saveBtn) saveBtn.classList.remove('is-loading');
              syncSaveDisabled();
            }
          }
          suggBox.innerHTML = '';
          suggBox.style.display = 'none';
        });
      });
    }

    let latestResults = [];

    function normalizePhoneQuery(raw) {
      let s = String(raw || '').trim().replace(/\s+/g, '');
      if (s.startsWith('+')) s = s.slice(1);
      let digits = s.replace(/\D/g, '');
      if (digits.startsWith('91') && digits.length > 10) digits = digits.slice(-10);
      return digits;
    }

    function appendToDescIfMissing(value) {
      const descEl = document.getElementById('noteDesc');
      if (!descEl) return;
      const v = String(value || '').trim();
      if (!v) return;
      const current = descEl.value || '';
      if (current.includes(v)) return;
      descEl.value = current ? `${current}${current.endsWith('\n') ? '' : '\n'}${v}` : v;
    }

    const doSearch = debounce(async (rawInput) => {
      const raw = String(rawInput || '').trim();
      const digits = (raw.match(/\d/g) || []).length;
      const letters = (raw.match(/[A-Za-z]/g) || []).length;
      if (!raw || (digits < 3 && letters < 3)) { renderSuggestions([]); return; }
      const isPhoneQuery = letters === 0 && digits > 0;
      const q = isPhoneQuery ? normalizePhoneQuery(raw) : raw;
      if (!q) { renderSuggestions([]); return; }
      try {
        const res = await fetch(`orders_search.php?q=${encodeURIComponent(q)}`, { cache: 'no-store' });
        const data = await res.json();
        latestResults = Array.isArray(data.results) ? data.results : [];
        renderSuggestions(latestResults);
        if (isPhoneQuery && latestResults.length === 0 && q.length >= 7) appendToDescIfMissing(q);
      } catch (e) {
        latestResults = [];
        renderSuggestions([]);
        if (isPhoneQuery && q.length >= 7) appendToDescIfMissing(q);
      }
    }, 300);

    phoneInput.addEventListener('input', (e) => doSearch(e.target.value));
    phoneInput.addEventListener('focus',  () => { if (latestResults.length) suggBox.style.display = 'block'; });
    document.addEventListener('click', (e) => {
      if (!suggBox.contains(e.target) && e.target !== phoneInput) suggBox.style.display = 'none';
    }, { capture: true });

    form.onsubmit = function(e){
      e.preventDefault();
      const phoneVal = document.getElementById('notePhone').value.trim();
      const titleVal = document.getElementById('noteTitle').value.trim();
      const descVal = document.getElementById('noteDesc').value.trim();
      // Validate: at least a phone number in phone, title, or description
      const phoneRegex = /\d{7,}/; // Accepts 7+ digit numbers
      if (!phoneRegex.test(phoneVal) && !phoneRegex.test(titleVal) && !phoneRegex.test(descVal)) {
        alert('Please enter a phone number in the phone, title, or description field.');
        document.getElementById('notePhone').focus();
        return;
      }
      // Get username from cookie
      function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return '';
      }
      const username = getCookie('username') || getCookie('auth_user') || '';
      const data = {
        id: document.getElementById('noteId').value || undefined,
        type: document.getElementById('noteType').value,
        title: titleVal,
        description: descVal,
        date: document.getElementById('noteDate').value,
        phone: phoneVal,
        username: username
      };
      saveNote(data);
      closeNoteForm();
    };

    document.getElementById('np-cancel-btn').onclick = closeNoteForm;
    backdrop.onclick = closeNoteForm;
    const escHandler = (ev) => { if (ev.key === 'Escape') closeNoteForm(); };
    document.addEventListener('keydown', escHandler, { once: true });
  }
  window.openNoteForm = openNoteForm;

  // Inject notes for a specific date into the day element
  async function injectNotesForDate(dayEl, dateStr) {
    const fetchedNotes = await fetchNotes(dateStr);
    const movedNotes = await moveOpenNotesToToday(fetchedNotes, dateStr);
    const notesWithAutoDone = await autoMarkOldNotesDone(movedNotes);
    const visibleNotes = notesWithAutoDone.filter(note => getNoteAgeDays(note) <= 14);
    const notes = visibleNotes.sort((a, b) => {
      const ageDiff = getNoteAgeDays(b) - getNoteAgeDays(a);
      if (ageDiff !== 0) return ageDiff;
      return (Number(a.sort_order) || 0) - (Number(b.sort_order) || 0);
    });
    
    notes.forEach(note => {
      const noteDiv = document.createElement('div');
      const isDone = !!(note.is_done && String(note.is_done) !== '0');
      const ageDays = getNoteAgeDays(note);
      noteDiv.className = `note${isDone ? ' is-done' : ''}`;
      noteDiv.dataset.id = note.id;
      noteDiv.draggable = !isDone;
      
      const typeClass = (note.type || 'General').toLowerCase();
      const typeColor = {
        'issue': '#dc3545',
        'inspection': '#fd7e14', 
        'general': '#28a745'
      }[typeClass] || '#28a745';
      noteDiv.style.borderColor = typeColor;
      
      noteDiv.innerHTML = `
        <input type="checkbox" class="note-select-checkbox" aria-label="Select note">
        <div class="note-header" style="text-transform: uppercase; color: ${typeColor}">
          <span class="note-type">${note.type || 'General'}</span>
          <span class="note-age">${formatNoteAgeLabel(ageDays)}</span>
        </div>
        <div class="note-content">
          <div class="note-title">${window.escapeHtml(note.title || '')}</div>
          ${note.description ? `<div class="note-desc">${window.linkify(window.escapeHtml(note.description))}</div>` : ''}
          ${note.phone ? `<div class="note-phone"><i class="fas fa-phone"></i> <a href="tel:${note.phone}">${note.phone}</a></div>` : ''}
          ${note.username ? `<div class="note-username" style="color:#1976d2;font-size:12px;margin-top:2px;">By: ${window.escapeHtml(note.username)}</div>` : ''}
        </div>
        <div class="note-actions" aria-label="Note actions">
          <button type="button" class="note-action note-action-done" onclick="toggleNoteDone(${note.id}, ${isDone ? 0 : 1})" aria-label="${isDone ? 'Reopen' : 'Done'}" title="${isDone ? 'Reopen' : 'Done'}">
            <i class="fas ${isDone ? 'fa-undo' : 'fa-check-circle'}" aria-hidden="true"></i>
          </button>
          <button type="button" class="note-action note-action-edit" onclick="editNote(${JSON.stringify(note).replace(/"/g, '&quot;')})" aria-label="Edit" title="Edit">
            <i class="fas fa-edit" aria-hidden="true"></i>
          </button>
          <button type="button" class="note-action note-action-delete" onclick="deleteNote(${note.id})" aria-label="Delete" title="Delete">
            <i class="fas fa-trash" aria-hidden="true"></i>
          </button>
        </div>
      `;
      
      // Add drag and drop functionality
      noteDiv.ondragstart = (e) => {
        e.dataTransfer.setData('note-id', note.id);
        e.dataTransfer.effectAllowed = 'move';
      };
      
      dayEl.appendChild(noteDiv);
    });
  }
  window.injectNotesForDate = injectNotesForDate;

  const NOTE_BASKET_KEY = 'installs_note_basket_v1';
  const NOTE_BASKET_UI_KEY = 'installs_note_basket_ui_v1';
  let noteBasketState = [];
  let activeDayEl = null;
  let basketEl = null;
  let dragId = null;

  function loadBasketState() {
    try {
      const raw = localStorage.getItem(NOTE_BASKET_KEY);
      const data = raw ? JSON.parse(raw) : [];
      noteBasketState = Array.isArray(data) ? data : [];
    } catch (e) {
      noteBasketState = [];
    }
  }

  function saveBasketState() {
    try {
      localStorage.setItem(NOTE_BASKET_KEY, JSON.stringify(noteBasketState));
    } catch (e) {}
  }

  function loadBasketUi() {
    try {
      const raw = localStorage.getItem(NOTE_BASKET_UI_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function saveBasketUi(data) {
    try {
      localStorage.setItem(NOTE_BASKET_UI_KEY, JSON.stringify(data));
    } catch (e) {}
  }

  function normalizePhone(raw) {
    let s = String(raw || '').trim();
    if (!s) return '';
    if (s.startsWith('+')) s = s.slice(1);
    const digits = s.replace(/\D/g, '');
    if (!digits) return '';
    if (digits.length === 10) return digits;
    if (digits.length > 10) return digits.slice(-10);
    return digits;
  }

  function getMapLink(noteEl) {
    const desc = noteEl.querySelector('.note-desc');
    if (!desc) return '';
    const links = Array.from(desc.querySelectorAll('a[href]'));
    const map = links.find(a => /maps|goo\.gl\/maps|maps\.app\.goo\.gl/i.test(a.getAttribute('href') || ''));
    return (map ? map.getAttribute('href') : (links[0] ? links[0].getAttribute('href') : '')) || '';
  }

  function getDescriptionText(noteEl) {
    const desc = noteEl.querySelector('.note-desc');
    if (!desc) return '';
    return (desc.textContent || '').trim();
  }

  function buildNotePayload(noteEl) {
    const id = String(noteEl?.dataset?.id || '');
    const type = (noteEl.querySelector('.note-type')?.textContent || '').trim();
    const title = (noteEl.querySelector('.note-title')?.textContent || '').trim();
    const phoneText = (noteEl.querySelector('.note-phone a')?.textContent || '').trim();
    const mapLink = getMapLink(noteEl);
    const desc = getDescriptionText(noteEl);
    const by = (noteEl.querySelector('.note-username')?.textContent || '').trim();
    const other = [desc, by].filter(Boolean).join('\n');
    return { id, type, title, phone: phoneText, mapLink, other };
  }

  function formatNoteForWhatsApp(payload) {
    const lines = [];
    const icon = '📌';
    if (payload.type) lines.push(`${icon} ${payload.type}`);
    if (payload.title) lines.push(payload.title);
    if (payload.phone) lines.push(payload.phone);
    if (payload.mapLink) lines.push(payload.mapLink);
    if (payload.other) lines.push(payload.other);
    return lines.join('\n').trim();
  }

  function ensureBasketEl() {
    if (basketEl && document.body.contains(basketEl)) return basketEl;

    basketEl = document.createElement('div');
    basketEl.className = 'note-basket';
    basketEl.id = 'note-basket';
    basketEl.innerHTML = `
      <div class="note-basket-header" id="note-basket-header">
        <div class="note-basket-title">Notes</div>
        <button type="button" class="note-basket-close" id="note-basket-close" aria-label="Close">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
      </div>
      <div class="note-basket-body">
        <div class="note-basket-list" id="note-basket-list"></div>
      </div>
      <div class="note-basket-footer">
        <select id="note-basket-user"></select>
        <button type="button" class="note-basket-send" id="note-basket-send">Send</button>
      </div>
    `;
    document.body.appendChild(basketEl);

    const bodyEl = basketEl.querySelector('.note-basket-body');
    if (bodyEl) {
      let touchY = 0;
      const canScrollBody = () => (bodyEl.scrollHeight - bodyEl.clientHeight) > 1;
      const onWheel = (e) => {
        const dy = Number(e.deltaY || 0);
        const withinBody = !!(e.target && e.target.closest && e.target.closest('.note-basket-body'));
        if (!withinBody) {
          if (canScrollBody() && dy) bodyEl.scrollTop += dy;
          e.preventDefault();
        } else if (!canScrollBody()) {
          e.preventDefault();
        }
        e.stopPropagation();
      };
      basketEl.addEventListener('wheel', onWheel, { passive: false, capture: true });
      basketEl.addEventListener('touchstart', (e) => {
        if (!e.touches || e.touches.length !== 1) return;
        touchY = e.touches[0].clientY;
      }, { passive: true, capture: true });
      basketEl.addEventListener('touchmove', (e) => {
        if (!e.touches || e.touches.length !== 1) return;
        const nextY = e.touches[0].clientY;
        const dy = touchY - nextY;
        touchY = nextY;
        const withinBody = !!(e.target && e.target.closest && e.target.closest('.note-basket-body'));
        if (!withinBody) {
          if (canScrollBody() && dy) bodyEl.scrollTop += dy;
          e.preventDefault();
        } else if (!canScrollBody()) {
          e.preventDefault();
        }
        e.stopPropagation();
      }, { passive: false, capture: true });
    }

    const closeBtn = basketEl.querySelector('#note-basket-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        closeBasket();
      });
    }

    const sendBtn = basketEl.querySelector('#note-basket-send');
    if (sendBtn) {
      sendBtn.addEventListener('click', (e) => {
        e.preventDefault();
        sendBasketToWhatsApp();
      });
    }

    const list = basketEl.querySelector('#note-basket-list');
    if (list) {
      list.addEventListener('click', (e) => {
        const rm = e.target.closest('.note-basket-item-remove');
        if (!rm) return;
        const id = rm.getAttribute('data-id') || '';
        removeFromBasket(id);
      });
    }

    const header = basketEl.querySelector('#note-basket-header');
    if (header) {
      let moving = false;
      let startX = 0;
      let startY = 0;
      let startLeft = 0;
      let startTop = 0;

      const move = (ev) => {
        if (!moving) return;
        const dx = ev.clientX - startX;
        const dy = ev.clientY - startY;
        const left = Math.max(8, Math.min(window.innerWidth - basketEl.offsetWidth - 8, startLeft + dx));
        const top = Math.max(8, Math.min(window.innerHeight - 60, startTop + dy));
        basketEl.style.left = `${left}px`;
        basketEl.style.top = `${top}px`;
        basketEl.style.right = 'auto';
        saveBasketUi({ open: basketEl.classList.contains('open'), left, top });
      };

      const up = () => {
        if (!moving) return;
        moving = false;
        document.removeEventListener('mousemove', move, true);
        document.removeEventListener('mouseup', up, true);
      };

      header.addEventListener('mousedown', (ev) => {
        if (ev.button !== 0) return;
        if (ev.target.closest('button')) return;
        moving = true;
        const rect = basketEl.getBoundingClientRect();
        startX = ev.clientX;
        startY = ev.clientY;
        startLeft = rect.left;
        startTop = rect.top;
        document.addEventListener('mousemove', move, true);
        document.addEventListener('mouseup', up, true);
      });
    }

    const ui = loadBasketUi();
    if (ui && typeof ui.left === 'number' && typeof ui.top === 'number') {
      basketEl.style.left = `${ui.left}px`;
      basketEl.style.top = `${ui.top}px`;
      basketEl.style.right = 'auto';
    }

    populateUserDropdown();
    renderBasket();

    return basketEl;
  }

  function populateUserDropdown() {
    const sel = basketEl?.querySelector('#note-basket-user');
    if (!sel) return;
    const users = Array.isArray(window.INSTALLS_WA_USERS) ? window.INSTALLS_WA_USERS : [];
    sel.innerHTML = '';
    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = 'Select user';
    sel.appendChild(opt0);
    users.forEach(u => {
      const name = String(u?.name || '').trim();
      const phone = String(u?.phone || '').trim();
      if (!name || !phone) return;
      const opt = document.createElement('option');
      opt.value = normalizePhone(phone);
      opt.textContent = `${name} (${phone})`;
      sel.appendChild(opt);
    });

    const ui = loadBasketUi();
    if (ui && ui.selectedPhone) sel.value = String(ui.selectedPhone || '');
    sel.addEventListener('change', () => {
      const next = loadBasketUi() || {};
      next.selectedPhone = sel.value;
      next.open = basketEl.classList.contains('open');
      saveBasketUi(next);
    });
  }

  function openBasket() {
    ensureBasketEl();
    basketEl.classList.add('open');
    const ui = loadBasketUi() || {};
    ui.open = true;
    saveBasketUi(ui);
  }

  function closeBasket() {
    ensureBasketEl();
    basketEl.classList.remove('open');
    const ui = loadBasketUi() || {};
    ui.open = false;
    saveBasketUi(ui);
  }

  function renderBasket() {
    ensureBasketEl();
    const list = basketEl.querySelector('#note-basket-list');
    if (!list) return;
    list.innerHTML = '';

    noteBasketState.forEach(item => {
      const wrap = document.createElement('div');
      wrap.className = 'note-basket-item';
      wrap.setAttribute('draggable', 'true');
      wrap.dataset.id = item.id;

      const payload = item.payload || {};
      const text = formatNoteForWhatsApp(payload);

      wrap.innerHTML = `
        <div class="note-basket-item-top">
          <div class="note-basket-item-lines">${window.escapeHtml(text)}</div>
          <button type="button" class="note-basket-item-remove" aria-label="Remove" data-id="${window.escapeHtml(item.id)}">
            <i class="fa-solid fa-trash" aria-hidden="true"></i>
          </button>
        </div>
      `;

      wrap.addEventListener('dragstart', (e) => {
        dragId = item.id;
        wrap.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', String(item.id)); } catch (err) {}
      });
      wrap.addEventListener('dragend', () => {
        wrap.classList.remove('dragging');
        dragId = null;
        syncBasketFromDom();
        saveBasketState();
      });
      wrap.addEventListener('dragover', (e) => {
        e.preventDefault();
        if (!dragId || dragId === item.id) return;
        const draggingEl = list.querySelector(`.note-basket-item[data-id="${CSS.escape(String(dragId))}"]`);
        if (!draggingEl) return;
        const rect = wrap.getBoundingClientRect();
        const before = (e.clientY - rect.top) < rect.height / 2;
        list.insertBefore(draggingEl, before ? wrap : wrap.nextSibling);
      });
      wrap.addEventListener('drop', (e) => {
        e.preventDefault();
        syncBasketFromDom();
        saveBasketState();
        syncCheckboxesForDay(activeDayEl);
      });

      list.appendChild(wrap);
    });

    const sendBtn = basketEl.querySelector('#note-basket-send');
    if (sendBtn) sendBtn.disabled = noteBasketState.length === 0;
  }

  function syncBasketFromDom() {
    const list = basketEl?.querySelector('#note-basket-list');
    if (!list) return;
    const ids = Array.from(list.querySelectorAll('.note-basket-item')).map(el => String(el.dataset.id || ''));
    const next = [];
    ids.forEach(id => {
      const hit = noteBasketState.find(x => String(x.id) === id);
      if (hit) next.push(hit);
    });
    noteBasketState = next;
  }

  function addToBasket(payload) {
    const id = String(payload?.id || '');
    if (!id) return;
    const exists = noteBasketState.some(x => String(x.id) === id);
    if (exists) return;
    noteBasketState.push({ id, payload });
    saveBasketState();
    renderBasket();
  }

  function removeFromBasket(id) {
    const nid = String(id || '');
    if (!nid) return;
    noteBasketState = noteBasketState.filter(x => String(x.id) !== nid);
    saveBasketState();
    renderBasket();
    if (activeDayEl) syncCheckboxesForDay(activeDayEl);
  }

  function isInBasket(noteId) {
    return noteBasketState.some(x => String(x.id) === String(noteId));
  }

  function syncCheckboxesForDay(dayEl) {
    if (!dayEl) return;
    dayEl.querySelectorAll('.note').forEach(noteEl => {
      const cb = noteEl.querySelector('.note-select-checkbox');
      if (!cb) return;
      cb.checked = isInBasket(noteEl.dataset.id);
    });
  }

  window.toggleNoteSelectMode = function (btn) {
    console.log('toggleNoteSelectMode clicked', btn);
    const day = btn.closest('.day');
    if (!day) {
      console.error('Day element not found for button', btn);
      return;
    }
    
    if (day.classList.contains('note-select-mode')) {
      console.log('Deactivating select mode for day', day);
      day.classList.remove('note-select-mode');
      const icon = btn.querySelector('i');
      if (icon) {
        icon.classList.remove('fa-check');
        icon.classList.add('fa-plus');
      }
      closeBasket();
    } else {
      console.log('Activating select mode for day', day);
      activateSelectModeForDay(day);
      const icon = btn.querySelector('i');
      if (icon) {
        icon.classList.remove('fa-plus');
        icon.classList.add('fa-check');
      }
      openBasket();
    }
  };

  function activateSelectModeForDay(dayEl) {
    if (!dayEl) return;
    if (activeDayEl && activeDayEl !== dayEl) {
      activeDayEl.classList.remove('note-select-mode');
      const oldBtn = activeDayEl.querySelector('.day-note-select-toggle i');
      if (oldBtn) {
        oldBtn.classList.remove('fa-check');
        oldBtn.classList.add('fa-plus');
      }
    }
    activeDayEl = dayEl;
    activeDayEl.classList.add('note-select-mode');
    syncCheckboxesForDay(activeDayEl);
  }

  function sendBasketToWhatsApp() {
    ensureBasketEl();
    const sel = basketEl.querySelector('#note-basket-user');
    const phone = sel ? normalizePhone(sel.value) : '';
    if (!phone) return;
    const msg = noteBasketState
      .map(x => formatNoteForWhatsApp(x.payload || {}))
      .filter(Boolean)
      .join('\n\n');
    if (!msg) return;
    const url = `https://wa.me/91${encodeURIComponent(phone)}?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank', 'noopener');
  }

  document.addEventListener('change', (e) => {
    const cb = e.target.closest('.note-select-checkbox');
    if (!cb) return;
    const noteEl = cb.closest('.note');
    if (!noteEl) return;
    e.stopPropagation();
    openBasket();
    const payload = buildNotePayload(noteEl);
    if (cb.checked) addToBasket(payload);
    else removeFromBasket(payload.id);
  }, true);

  (function initNoteBasket() {
    loadBasketState();
    const ui = loadBasketUi();
    if (ui && ui.open) openBasket();
  })();

})();
