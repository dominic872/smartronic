// Notes module for installs
// Requires: installs.utils.js (debounce, linkify, formatDateDisplay, escapeHtml)

(function(){
  const NOTES_API = 'notes_api.php';
  // Expose for other modules
  window.NOTES_API = NOTES_API;

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

  async function fetchNotes(date) {
    try {
      const url = `${NOTES_API}?date=${encodeURIComponent(date)}&t=${Date.now()}`;
      const res = await fetch(url, { cache: 'no-store' });
      const contentType = res.headers.get('content-type') || '';
      const raw = await res.text();
      if (!res.ok) { console.error('Notes API HTTP error:', res.status, raw); return []; }
      if (!contentType.includes('application/json')) { console.error('Notes API non-JSON response:', raw); return []; }
      const data = JSON.parse(raw);
      if (!data || data.success !== true) return [];
      return Array.isArray(data.notes) ? data.notes : [];
    } catch (e) {
      console.error(`Error fetching notes for ${date}`, e);
      return [];
    }
  }
  window.fetchNotes = fetchNotes;

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

    if (isEdit) {
      document.getElementById('noteType').value = note.type || 'General';
      document.getElementById('noteTitle').value = note.title || '';
      document.getElementById('noteDesc').value = note.description || '';
      if (note.phone) document.getElementById('notePhone').value = note.phone;
    }

    const phoneInput = document.getElementById('notePhone');
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
        el.addEventListener('click', (evt) => {
          evt.stopPropagation();
          const id = el.getAttribute('data-id');
          const hit = latestResults.find(r => String(r.id) === String(id));
          if (hit) {
            phoneInput.value = hit.phone || '';
            if (!document.getElementById('noteTitle').value) document.getElementById('noteTitle').value = hit.name || '';
            document.getElementById('noteDesc').value = orderToPreview(hit);
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
    const notes = await fetchNotes(dateStr);
    
    notes.forEach(note => {
      const noteDiv = document.createElement('div');
      noteDiv.className = 'note';
      noteDiv.dataset.id = note.id;
      noteDiv.draggable = true;
      
      const typeClass = (note.type || 'General').toLowerCase();
      const typeColor = {
        'issue': '#dc3545',
        'inspection': '#fd7e14', 
        'general': '#28a745'
      }[typeClass] || '#28a745';
      noteDiv.style.borderColor = typeColor;
      
      noteDiv.innerHTML = `
        <div class="note-header" style="text-transform: uppercase; color: ${typeColor}">
          <span class="note-type">${note.type || 'General'}</span>
          <div class="note-actions">
            <i class="fas fa-edit" onclick="editNote(${JSON.stringify(note).replace(/"/g, '&quot;')})" title="Edit"></i>
            <i class="fas fa-trash" onclick="deleteNote(${note.id})" title="Delete"></i>
          </div>
        </div>
        <div class="note-content">
          <div class="note-title">${window.escapeHtml(note.title || '')}</div>
          ${note.description ? `<div class="note-desc">${window.linkify(window.escapeHtml(note.description))}</div>` : ''}
          ${note.phone ? `<div class="note-phone"><i class="fas fa-phone"></i> <a href="tel:${note.phone}">${note.phone}</a></div>` : ''}
          ${note.username ? `<div class="note-username" style="color:#1976d2;font-size:12px;margin-top:2px;">By: ${window.escapeHtml(note.username)}</div>` : ''}
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

})();
