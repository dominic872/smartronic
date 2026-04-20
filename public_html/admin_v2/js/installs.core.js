'use strict';
(function (w) {
  // Wait for utilities to be available
  function initializeCore() {
    // Shared state (guarded)
    w.weeksContainer = w.weeksContainer || document.getElementById('calendarWeeks');
    w.monthLabel = w.monthLabel || document.getElementById('monthLabel');
    w.allInstalls = w.allInstalls || [];
    w.currentMonday = w.currentMonday || window.getMonday(new Date());
    w.editingId = w.editingId || null;
    w.monthColors = w.monthColors || ["#03944f", "#e53935", "#f0b414", "#454dce", "#498aaa", "#c315dc"];
    w.showExtendedWeeks = w.showExtendedWeeks || false;
    w.weeksToggleBtn = w.weeksToggleBtn || null;
    w.statsBtn = w.statsBtn || null;
  }

  // Initialize when utils are ready or immediately if available
  if (window.getMonday) {
    initializeCore();
  } else {
    // Wait for utils to load
    const checkUtils = () => {
      if (window.getMonday) {
        initializeCore();
      } else {
        setTimeout(checkUtils, 10);
      }
    };
    checkUtils();
  }

  w.render = w.render || function render(callback) {
    fetch('installs_api.php')
      .then(res => res.json())
      .then(data => {
        w.allInstalls = Array.isArray(data) ? data : [];
        if (!Array.isArray(data)) {
          console.error('installs_api.php returned non-array data:', data);
        }
        w.buildCalendar();
        if (typeof w.updateStats === 'function') w.updateStats();
        if (typeof w.updateUnscheduledTray === 'function') w.updateUnscheduledTray();
        if (w.showExtendedWeeks) {
          setTimeout(() => {
            const currentWeek = document.getElementById('currentWeek');
            if (currentWeek) currentWeek.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }, 0);
        }
        if (typeof callback === 'function') {
          callback();
        }
      });
  };

  w.changeWeek = w.changeWeek || function changeWeek(offset) {
    w.currentMonday.setDate(w.currentMonday.getDate() + offset * 7);
    w.render();
  };

  function ensureToolbar() {
    if (w.toolbar && w.toolbarRight) return;
    w.toolbar = document.createElement('div');
    w.toolbar.id = 'calendarToolbar';
    w.toolbar.className = 'calendar-toolbar';
    const left = document.createElement('div'); left.className = 'toolbar-left';
    w.toolbarRight = document.createElement('div'); w.toolbarRight.className = 'toolbar-right';
    w.toolbar.appendChild(left); w.toolbar.appendChild(w.toolbarRight);
    const calendarEl = document.querySelector('.calendar');
    const weekdaysEl = calendarEl.querySelector('.weekdays');
    if (weekdaysEl) calendarEl.insertBefore(w.toolbar, weekdaysEl); else calendarEl.insertBefore(w.toolbar, calendarEl.firstChild);
    if (w.monthLabel) {
      left.appendChild(w.monthLabel);
    }
  }

  w.ensureWeeksToggle = w.ensureWeeksToggle || function ensureWeeksToggle() {
    ensureToolbar();
    ensureProfitToggle();
  };

  w.buildCalendar = w.buildCalendar || function buildCalendar() {
    w.weeksContainer.innerHTML = '';
    const prev2 = new Date(w.currentMonday); prev2.setDate(prev2.getDate() - 14);
    const prev1 = new Date(w.currentMonday); prev1.setDate(prev1.getDate() - 7);
    const next1 = new Date(w.currentMonday); next1.setDate(next1.getDate() + 7);
    const next2 = new Date(w.currentMonday); next2.setDate(next2.getDate() + 14);

    const wPrev2 = w.buildWeek(prev2, 'prev');
    const wPrev1 = w.buildWeek(prev1, 'prev');
    const wCurr = w.buildWeek(w.currentMonday, 'current');
    const wNext1 = w.buildWeek(next1, 'next');
    const wNext2 = w.buildWeek(next2, 'next');

    ;[wPrev2, wPrev1, wNext1, wNext2].forEach(we => { we.classList.add('extended-week'); we.style.display = w.showExtendedWeeks ? '' : 'none'; });
    wCurr.id = 'currentWeek';

    w.weeksContainer.appendChild(wPrev2);
    w.weeksContainer.appendChild(wPrev1);
    w.weeksContainer.appendChild(wCurr);
    w.weeksContainer.appendChild(wNext1);
    w.weeksContainer.appendChild(wNext2);

    const labelDate = new Date(w.currentMonday);
    if (w.monthLabel) {
      w.monthLabel.textContent = labelDate.toLocaleString('default', { month: 'long', year: 'numeric' }).toUpperCase();
    }
    w.ensureWeeksToggle();
    updateProfitLineVisibility();
    if (w.showExtendedWeeks) {
      setTimeout(() => {
        const cw = document.getElementById('currentWeek');
        if (cw) cw.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 0);
    }
  };

  async function fetchLeaves(date) {
    try {
      const res = await fetch(`../vacation/api_get_leaves.php?date=${date}`);
      const data = await res.json();
      if (data.success) return data.leaves;
    } catch (e) { console.error(`Error fetching leaves for ${date}`, e); }
    return [];
  }

  w.buildWeek = w.buildWeek || function buildWeek(startDate, label) {
    const week = document.createElement('div'); week.className = `week ${label}`;
    for (let i = 0; i < 7; i++) {
      const dayDate = new Date(startDate); dayDate.setDate(startDate.getDate() + i);
      const dateStr = window.formatDate(dayDate);
      const day = document.createElement('div'); day.className = 'day'; day.dataset.date = dateStr;
      const monthIndex = dayDate.getMonth() % w.monthColors.length;
      if (dateStr === window.formatDate(new Date())) day.classList.add('today');
      const dateBgColor = w.monthColors[monthIndex];
    day.innerHTML = `
  <div class="day-header">
    <span class="date" style="background-color:${dateBgColor}" title="Click to show/hide map locations" data-original-text="${dayDate.getDate()} ${dayDate.toLocaleString('default', { month: 'short' }).toUpperCase()} | ${dayDate.toLocaleDateString('default', { weekday: 'short' }).toUpperCase()}">
      ${dayDate.getDate()} ${dayDate.toLocaleString('default', { month: 'short' }).toUpperCase()} <br>
      ${dayDate.toLocaleDateString('default', { weekday: 'short' }).toUpperCase()}
    </span>
    <div class="day-header-actions">
      <button class="day-map-toggle" onclick="console.log('Globe clicked'); window.toggleDayMaps(this)" title="Show Map">
        <i class="fa-solid fa-globe"></i>
      </button>
      <button class="day-note-select-toggle" onclick="window.toggleNoteSelectMode(this)" title="Select Notes">
        <i class="fa-solid fa-plus"></i>
      </button>
      <button class="day-add-note" onclick="if (typeof window.openNoteForm === 'function') window.openNoteForm('${dateStr}')" title="Add quick note">
        <i class="fas fa-plus-circle"></i>
      </button>
    </div>
  </div>
`;
      fetchLeaves(dateStr).then(leaves => {
        if (leaves.length > 0) {
          const leaveDiv = document.createElement('div');
          leaveDiv.className = 'leaves';
          leaveDiv.innerHTML = `<strong><i class="fas fa-user-times"></i></strong> ${leaves.map(l => l.fullname).join(', ')}`;
          const headerEl = day.querySelector('.day-header') || day.querySelector('.date');
          headerEl.after(leaveDiv);
        }
      });

      day.ondblclick = () => { if (typeof w.openForm === 'function') w.openForm({ date: dateStr }); };
      day.ondragover = e => { e.preventDefault(); day.classList.add('dragover'); };
      day.ondragleave = () => day.classList.remove('dragover');
      day.ondrop = e => {
        e.preventDefault(); day.classList.remove('dragover');
        const noteId = e.dataTransfer.getData('note-id');
        if (noteId && typeof w.moveNoteToDate === 'function') { w.moveNoteToDate(noteId, dateStr); return; }
        const id = e.dataTransfer.getData('id') || e.dataTransfer.getData('tray-id') || e.dataTransfer.getData('text/plain');
        const installs = Array.isArray(w.allInstalls) ? w.allInstalls : [];
        const item = installs.find(i => String(i.id) === String(id));
        if (item) { item.date = dateStr; w.saveInstalls(item); }
      };

      const installs = Array.isArray(w.allInstalls) ? w.allInstalls : [];
      const todaysInstalls = installs.filter(ev => ev.date === dateStr).sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
      todaysInstalls.forEach(ev => {
        const div = document.createElement('div'); div.className = `event ${ev.type || ''}`;
        const required = ['id', 'name', 'cams', 'bullets', 'dome', 'hdd', 'time', 'location', 'date'];
        const isComplete = required.every(f => ev[f] && ev[f].toString().trim() !== '');
        if (!isComplete) div.classList.add('missing');
        div.draggable = true; div.dataset.id = ev.id;
        div.ondragstart = e => {
          e.dataTransfer.setData('id', ev.id);
          e.dataTransfer.setData('source-date', ev.date || '');
          e.dataTransfer.effectAllowed = 'move';
        };
        div.ondragover = e => e.preventDefault();
        div.ondrop = e => {
          e.preventDefault();
          const draggedId = e.dataTransfer.getData('id');
          const targetId = ev.id; if (draggedId === targetId) return;
          const installs = Array.isArray(w.allInstalls) ? w.allInstalls : [];
          const dragged = installs.find(i => i.id === draggedId);
          const target = installs.find(i => i.id === targetId);
          if (dragged && target && dragged.date === target.date) {
            const siblings = installs.filter(i => i.date === dragged.date && i.id !== draggedId);
            const targetIndex = siblings.findIndex(i => i.id === targetId);
            siblings.splice(targetIndex, 0, dragged); siblings.forEach((item, idx) => item.order = idx);
            w.saveInstalls(); w.render();
          }
        };
        ev.hdd = (ev.hdd || '').replace(/\s+/g, '');
        const monitorIcon = ev.monitor ? '<i class="fas fa-tv" title="Monitor"></i>' : '';
        const rackIcon = ev.rack ? '<i class="fas fa-server" title="Rack"></i>' : '';
        const idClasses = [];
        if (String(ev.pdf_sent || '').trim().toLowerCase() === 'yes') idClasses.push('pdf-sent');
        if (ev.fully_paid) idClasses.push('paid');
        
        const idClassAttr = idClasses.length ? ` class="${idClasses.join(' ')}"` : '';
        if (label === 'current') {
          const origin = 'HSR Layout';
          const destination = `${ev.location}, Bengaluru`;
          // Add brand logo and cam_type if brand is specified
          let brandLogo = '';
          console.log('Event data:', ev); // Debug: log full event data
          console.log('Brand:', ev.brand); // Debug: log brand specifically
          console.log('Cam Type:', ev.cam_type); // Debug: log cam_type specifically
          if (ev.brand) {
            const brandLower = ev.brand.toLowerCase();
            console.log('Brand lower:', brandLower); // Debug: log lowercase brand
            const logoPath = brandLower === 'cp plus' 
              ? 'https://smartronic.online/content/uploads/2025/01/cp-plus_logo.svg'
              : brandLower === 'hikvision'
              ? 'https://smartronic.online/content/uploads/2025/01/Hikvision_logo.svg'
              : brandLower === 'prama'
              ? 'https://smartronic.online/content/uploads/2025/01/Prama_logo.png'
              : '';
            const camTypeText = ''; // Removed - now using special camera type boxes
            if (logoPath) {
              brandLogo = `<div style="text-align: center; margin: 4px 0; display: flex; align-items: center;">
                <img src="${logoPath}" alt="${ev.brand}" style="height: 12px; width: auto;" />
                ${camTypeText}
              </div>`;
            } else {
              // Fallback: show brand text if no logo matches
              brandLogo = `<div style="text-align: center; margin: 4px 0; font-size: 12px; color: #666; font-weight: 600;">
                ${ev.brand}${camTypeText ? ' | ' + camTypeText : ''}
              </div>`;
            }
          }

          // Add camera type styling based on cam_type
          let camTypeBox = '';
          if (ev.cam_type) {
            const camTypeLower = ev.cam_type.toLowerCase();
            if (camTypeLower === 'normal with mic') {
              camTypeBox = `<div style="display: inline-block; background: #000; color: #fff; padding: 2px 6px; font-size: 12px; font-weight: bold; margin-left: 8px; border-radius: 4px;">NORMAL</div>`;
            } else if (camTypeLower === 'hybrid') {
              camTypeBox = `<div style="display: inline-block; background: linear-gradient(145deg, #000 50%, #ff6b35 50%); color: #fff; padding: 2px 6px; font-size: 12px; font-weight: bold; margin-left: 8px; border-radius: 4px;">HYBRID</div>`;
            } else if (camTypeLower === 'full colour') {
              camTypeBox = `<div style="display: inline-block; background: linear-gradient(145deg, #ff0000, #00ff00, #0000ff); color: #fff; padding: 2px 6px; font-size: 12px; font-weight: bold; margin-left: 8px; border-radius: 4px;">COLOUR</div>`;
            }
          }

          div.innerHTML = `
            <h3><span onclick="editInstall('${ev.id}')"${idClassAttr}> ${ev.id.replace('-', '<br>')}</span><a class="name-link" href="tel:${ev.phone}">${ev.name || 'No Name'}</a></h3>
            ${ev.notes ? `<div class="event-notes" style="background:#fffbcc;border-left:1px solid #f0b414;padding:4px 8px;margin:4px 0 9px;color:#333;"><strong>${window.escapeHtml(ev.notes)}</strong></div>` : ''}
            ${brandLogo ? `<div style="margin: 4px 0; display: flex; align-items: center; justify-content: left;">
              ${brandLogo}
              ${camTypeBox}
            </div>` : ''}
            <div class="details">
              <span class="hdd">${ev.hdd || ''}</span>
              <span class="cams"> ${ev.resolution || ''} x <strong>${ev.cams || 0}</strong></span>
              <span class="cams type">${ev.bullets || 0} B + ${ev.dome || 0} D</span>
              <span class="area"><span id="dist-${ev.id}"></span> <a href="${ev.map || ''}" target="_blank">${ev.location || ''}</a>  @ ${ev.time || ''}</span>
              <span class="monitor">${monitorIcon}</span> <span class="rack">${rackIcon}</span></div>
            <div class="names">${(ev.lead_campaign && ev.lead_campaign.toString().trim().length < 8) ? `<span style="display:inline-flex;align-items:center;height:16px;background:#9ca3af;color:#fff;font-size:10px;line-height:1;padding:0 6px;border-radius:3px;margin-right:6px;font-weight:800;">${window.escapeHtml(ev.lead_campaign.toString().trim())}</span>` : ''}<strong>${ev.owner || ''}</strong> | <a href="#" onclick="sendToWhatsapp(event, ${JSON.stringify(ev).replace(/"/g, '&quot;')})">${ev.technician || ''}</a> | ${ev.helper || ''}</div>
            <div class="icons"><i class="fas fa-edit" onclick="editInstall('${ev.id}')" title="Edit"></i><i class="fas fa-trash" onclick="deleteInstall('${ev.id}')" title="Delete"></i></div>
          `;
          if (destination) {
            fetch(`../distance_api.php?origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}`)
              .then(res => res.json())
              .then(data => {
                const distEl = div.querySelector(`#dist-${ev.id}`);
                if (data.distance_km !== undefined) distEl.innerHTML = `${Math.round(data.distance_km)}`; else distEl.innerHTML = '';
              })
              .catch(err => console.error('Distance fetch error:', err));
          }

          
        } else {
          div.innerHTML = `
            <span class="past-event"><strong>${ev.id || 0}</strong> | ${ev.name || 'No Name'}</span>
            <div class="icons past"><i class="fas fa-edit" onclick="editInstall('${ev.id}')" title="Edit"></i><i class="fas fa-trash" onclick="deleteInstall('${ev.id}')" title="Delete"></i></div>
          `;
        }
        day.appendChild(div);
      });

      // Notes and add-note button are handled by installs.notes.js if present
      if (typeof window.injectNotesForDate === 'function') window.injectNotesForDate(day, dateStr);

      week.appendChild(day);
    }
    return week;
  };

  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
  }

  w.apiCounter = w.apiCounter || 0;
  w.showProfitLines = w.showProfitLines || false;

  async function computeAndInsertProfit(ev, containerDiv) {
    try {
      const role = getCookie('auth_role');
      if (role !== 'admin') return;
      if (!w.showProfitLines) return;
      const namesEl = containerDiv.querySelector('.names');
      if (!namesEl) return;
      const profitEl = document.createElement('div');
      profitEl.className = 'profit-line';
      profitEl.style.display = 'flex';
      profitEl.textContent = 'Calculating...';
      namesEl.insertAdjacentElement('afterend', profitEl);
      const invRes = await fetch(`../smart/installs.php?render_invoice=1&id=${encodeURIComponent(ev.id)}`, { cache: 'no-store' });
      const invHtml = invRes.ok ? await invRes.text() : '';
      let customerPayable = NaN;
      if (invHtml) {
        const tmp = document.createElement('div');
        tmp.innerHTML = invHtml;
        const payEl = tmp.querySelector('#payable');
        if (payEl) {
          const baseAttr = payEl.getAttribute('data-base-value');
          const numFromAttr = baseAttr ? parseFloat(String(baseAttr).replace(/[^0-9.]/g, '')) : NaN;
          const numFromText = parseFloat(String(payEl.textContent || '').replace(/[^0-9.]/g, ''));
          customerPayable = !isNaN(numFromAttr) ? numFromAttr : numFromText;
        }
      }
      w.apiCounter += 1;
      if (w.apiCounterSpan && w.showProfitLines) w.apiCounterSpan.textContent = `API: ${w.apiCounter}`;
      const quoteReq = {
        whatsapp_number: '0000000000',
        num_cameras: ev.cams,
        dvr_type: ev.type,
        hdd_size: ev.hdd,
        camera_resolution: ev.resolution
      };
      let sellerWithGst = NaN;
      try {
        const qRes = await fetch('/admin_v2/quote_api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(quoteReq)
        });
        const qData = qRes.ok ? await qRes.json() : null;
        if (qData && typeof qData.with_gst !== 'undefined') {
          sellerWithGst = parseFloat(String(qData.with_gst).toString().replace(/[^0-9.]/g, ''));
        }
      } catch (e) {
        console.warn('Quote API error', e);
      }
      w.apiCounter += 1;
      if (w.apiCounterSpan && w.showProfitLines) w.apiCounterSpan.textContent = `API: ${w.apiCounter}`;
      if (!isNaN(customerPayable) && !isNaN(sellerWithGst)) {
        const fmt = n => `₹${Math.round(n).toLocaleString('en-IN')}`;
        const profit = customerPayable - sellerWithGst;
        profitEl.textContent = `${fmt(customerPayable)} - ${fmt(sellerWithGst)} = ${fmt(profit)}`;
        profitEl.dataset.ready = 'true';
      } else {
        profitEl.textContent = 'n/a';
        profitEl.dataset.ready = 'true';
      }
    } catch (err) {
      console.warn('Failed to compute profit for event', ev && ev.id, err);
    }
  }

  function ensureProfitToggle() {
    const role = getCookie('auth_role');
    if (role !== 'admin') return;
    if (w.apiCounterSpan) {
      w.apiCounterSpan.style.display = w.showProfitLines ? '' : 'none';
    }
  }

  w.toggleWeeksVisibility = w.toggleWeeksVisibility || function toggleWeeksVisibility() {
    w.showExtendedWeeks = !w.showExtendedWeeks;
    w.buildCalendar();
    if (typeof w.updateStats === 'function') w.updateStats();
    if (w.showExtendedWeeks) window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  w.toggleProfitVisibility = w.toggleProfitVisibility || function toggleProfitVisibility() {
    const role = getCookie('auth_role');
    if (role !== 'admin') return;
    w.showProfitLines = !w.showProfitLines;
    if (w.apiCounterSpan) {
      w.apiCounterSpan.style.display = w.showProfitLines ? '' : 'none';
    }
    if (!w.showProfitLines) {
      const lines = document.querySelectorAll('.event .profit-line');
      lines.forEach(el => el.style.display = 'none');
      return;
    }
    updateProfitLineVisibility();
  };

  function updateProfitLineVisibility() {
    const role = getCookie('auth_role');
    if (role !== 'admin') return;
    const events = Array.from(document.querySelectorAll('.week.current .event'));
    if (!w.showProfitLines) {
      events.forEach(evDiv => {
        const el = evDiv.querySelector('.profit-line');
        if (el) el.style.display = 'none';
      });
      return;
    }
    events.forEach(evDiv => {
      const id = evDiv.dataset.id;
      let line = evDiv.querySelector('.profit-line');
      if (line) { line.style.display = 'flex'; return; }
      const installs = Array.isArray(w.allInstalls) ? w.allInstalls : [];
      const ev = installs.find(x => x.id === id);
      if (ev) computeAndInsertProfit(ev, evDiv);
    });
  }

  const UNSCHEDULED_UI_KEY = 'installs_unscheduled_ui_v1';
  const UNSCHEDULED_ORDER_KEY = 'installs_unscheduled_order_v1';
  let trayEl = null;
  let trayDragId = null;

  function loadUnscheduledUi() {
    try {
      const raw = localStorage.getItem(UNSCHEDULED_UI_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function saveUnscheduledUi(next) {
    try {
      localStorage.setItem(UNSCHEDULED_UI_KEY, JSON.stringify(next));
    } catch (e) {}
  }

  function loadUnscheduledOrder() {
    try {
      const raw = localStorage.getItem(UNSCHEDULED_ORDER_KEY);
      const data = raw ? JSON.parse(raw) : [];
      return Array.isArray(data) ? data.map(x => String(x)) : [];
    } catch (e) {
      return [];
    }
  }

  function saveUnscheduledOrder(ids) {
    try {
      localStorage.setItem(UNSCHEDULED_ORDER_KEY, JSON.stringify(ids));
    } catch (e) {}
  }

  function normalizeUnscheduledDate(v) {
    const s = (v == null) ? '' : String(v).trim();
    if (!s) return null;
    if (s === '0000-00-00') return null;
    return s;
  }

  function appendMovedFrom(existingNotes, fromDate) {
    const actual = normalizeUnscheduledDate(fromDate);
    if (!actual) return existingNotes || '';
    const movedLine = `- moved from (${actual})`;
    const curr = String(existingNotes || '').trim();
    if (!curr) return movedLine;
    if (curr.includes(movedLine)) return curr;
    return `${curr}\n${movedLine}`;
  }

  function buildTrayEventElement(ev) {
    const wrap = document.createElement('div');
    wrap.className = 'event-tray-item';
    wrap.setAttribute('draggable', 'true');
    wrap.dataset.id = ev.id;

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'event-tray-remove';
    removeBtn.setAttribute('aria-label', 'Delete event');
    removeBtn.innerHTML = '<i class="fas fa-minus" aria-hidden="true"></i>';
    removeBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (typeof window.deleteInstall === 'function') window.deleteInstall(ev.id);
      const ids = loadUnscheduledOrder().filter(x => x !== String(ev.id));
      saveUnscheduledOrder(ids);
      if (typeof window.render === 'function') window.render();
    });

    const div = document.createElement('div');
    div.className = `event ${ev.type || ''}`;
    div.dataset.id = ev.id;
    div.draggable = false;

    const esc = typeof window.escapeHtml === 'function' ? window.escapeHtml : (s) => String(s ?? '');
    const monitorIcon = ev.monitor ? '<i class="fas fa-tv" title="Monitor"></i>' : '';
    const rackIcon = ev.rack ? '<i class="fas fa-server" title="Rack"></i>' : '';
    div.innerHTML = `
      <h3><span onclick="editInstall('${ev.id}')" ${ev.fully_paid ? ' class="paid"' : ''}> ${String(ev.id || '').replace('-', '<br>')}</span><a class="name-link" href="tel:${ev.phone}">${esc(ev.name || 'No Name')}</a></h3>
      ${ev.notes ? `<div class="event-notes" style="background:#fffbcc;border-left:1px solid #f0b414;padding:4px 8px;margin:4px 0 9px;color:#333;"><strong>${esc(ev.notes)}</strong></div>` : ''}
      <div class="details">
        <span class="hdd">${esc((ev.hdd || '').toString().replace(/\s+/g, ''))}</span>
        <span class="cams"> ${esc(ev.resolution || '')} x <strong>${esc(ev.cams || 0)}</strong></span>
        <span class="cams type">${esc(ev.bullets || 0)} B + ${esc(ev.dome || 0)} D</span>
        <span class="area"><span id="dist-${esc(ev.id)}"></span> <a href="${esc(ev.map || '')}" target="_blank">${esc(ev.location || '')}</a>  @ ${esc(ev.time || '')}</span>
        <span class="monitor">${monitorIcon}</span> <span class="rack">${rackIcon}</span>
      </div>
      <div class="names">${(ev.lead_campaign && ev.lead_campaign.toString().trim().length < 8) ? `<span style="display:inline-flex;align-items:center;height:16px;background:#9ca3af;color:#fff;font-size:10px;line-height:1;padding:0 6px;border-radius:3px;margin-right:6px;font-weight:800;">${esc(ev.lead_campaign.toString().trim())}</span>` : ''}<strong>${esc(ev.owner || '')}</strong> | <a href="#" onclick="sendToWhatsapp(event, ${JSON.stringify(ev).replace(/"/g, '&quot;')})">${esc(ev.technician || '')}</a> | ${esc(ev.helper || '')}</div>
      <div class="icons"><i class="fas fa-edit" onclick="editInstall('${ev.id}')" title="Edit"></i><i class="fas fa-trash" onclick="deleteInstall('${ev.id}')" title="Delete"></i></div>
    `;

    const origin = 'HSR Layout';
    const destination = ev.location ? `${ev.location}, Bengaluru` : '';
    if (destination) {
      fetch(`../distance_api.php?origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}`)
        .then(res => res.json())
        .then(data => {
          const safeId = (window.CSS && typeof window.CSS.escape === 'function')
            ? window.CSS.escape(String(ev.id))
            : String(ev.id).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
          const distEl = div.querySelector(`#dist-${safeId}`);
          if (!distEl) return;
          if (data.distance_km !== undefined) distEl.innerHTML = `<span class="distance">${Math.round(data.distance_km)}</span>`; else distEl.innerHTML = '';
        })
        .catch(() => {});
    }

    wrap.appendChild(removeBtn);
    wrap.appendChild(div);

    wrap.addEventListener('dragstart', (e) => {
      trayDragId = String(ev.id);
      wrap.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
      try {
        e.dataTransfer.setData('tray-id', trayDragId);
        e.dataTransfer.setData('id', trayDragId);
        e.dataTransfer.setData('text/plain', trayDragId);
        e.dataTransfer.setData('source-date', '');
      } catch (err) {}
    });
    wrap.addEventListener('dragend', () => {
      wrap.classList.remove('dragging');
      trayDragId = null;
      syncOrderFromDom();
    });
    wrap.addEventListener('dragover', (e) => {
      if (!trayDragId) return;
      e.preventDefault();
      const list = trayEl?.querySelector('#event-tray-list');
      if (!list) return;
      const draggingEl = list.querySelector(`.event-tray-item[data-id="${CSS.escape(String(trayDragId))}"]`);
      if (!draggingEl || draggingEl === wrap) return;
      const rect = wrap.getBoundingClientRect();
      const before = (e.clientY - rect.top) < rect.height / 2;
      list.insertBefore(draggingEl, before ? wrap : wrap.nextSibling);
    });
    wrap.addEventListener('drop', (e) => {
      if (!trayDragId) return;
      e.preventDefault();
      syncOrderFromDom();
    });

    return wrap;
  }

  function syncOrderFromDom() {
    const list = trayEl?.querySelector('#event-tray-list');
    if (!list) return;
    const ids = Array.from(list.querySelectorAll('.event-tray-item')).map(el => String(el.dataset.id || ''));
    saveUnscheduledOrder(ids.filter(Boolean));
  }

  function ensureUnscheduledTray() {
    if (trayEl && document.body.contains(trayEl)) return trayEl;

    trayEl = document.createElement('div');
    trayEl.className = 'event-tray';
    trayEl.id = 'event-tray';
    trayEl.innerHTML = `
      <div class="event-tray-header" id="event-tray-header">
        <div class="event-tray-title">Unscheduled</div>
        <div class="event-tray-controls">
          <button type="button" class="event-tray-minimize" id="event-tray-minimize" aria-label="Minimize">
            <i class="fas fa-window-minimize" aria-hidden="true"></i>
          </button>
        </div>
      </div>
      <div class="event-tray-body" id="event-tray-body">
        <div class="event-tray-drop-hint">Drag any event here to unschedule it</div>
        <div class="event-tray-list" id="event-tray-list"></div>
      </div>
    `;
    document.body.appendChild(trayEl);

    const ui = loadUnscheduledUi();
    if (ui && typeof ui.left === 'number' && typeof ui.top === 'number') {
      trayEl.style.left = `${ui.left}px`;
      trayEl.style.top = `${ui.top}px`;
      trayEl.style.right = 'auto';
      trayEl.style.bottom = 'auto';
    }
    if (ui && ui.minimized) trayEl.classList.add('minimized');

    const minimizeBtn = trayEl.querySelector('#event-tray-minimize');
    if (minimizeBtn) {
      minimizeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        trayEl.classList.toggle('minimized');
        const rect = trayEl.getBoundingClientRect();
        saveUnscheduledUi({ left: rect.left, top: rect.top, minimized: trayEl.classList.contains('minimized') });
      });
    }

    const header = trayEl.querySelector('#event-tray-header');
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
        const left = Math.max(8, Math.min(window.innerWidth - trayEl.offsetWidth - 8, startLeft + dx));
        const top = Math.max(8, Math.min(window.innerHeight - 60, startTop + dy));
        trayEl.style.left = `${left}px`;
        trayEl.style.top = `${top}px`;
        trayEl.style.right = 'auto';
        trayEl.style.bottom = 'auto';
        saveUnscheduledUi({ left, top, minimized: trayEl.classList.contains('minimized') });
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
        const rect = trayEl.getBoundingClientRect();
        startX = ev.clientX;
        startY = ev.clientY;
        startLeft = rect.left;
        startTop = rect.top;
        document.addEventListener('mousemove', move, true);
        document.addEventListener('mouseup', up, true);
      });
    }

    const body = trayEl.querySelector('#event-tray-body');
    if (body) {
      let touchY = 0;
      const canScrollBody = () => (body.scrollHeight - body.clientHeight) > 1;
      const onWheel = (e) => {
        const dy = Number(e.deltaY || 0);
        const withinBody = !!(e.target && e.target.closest && e.target.closest('#event-tray-body'));
        if (!withinBody) {
          if (canScrollBody() && dy) body.scrollTop += dy;
          e.preventDefault();
        } else if (!canScrollBody()) {
          e.preventDefault();
        }
        e.stopPropagation();
      };
      trayEl.addEventListener('wheel', onWheel, { passive: false, capture: true });
      trayEl.addEventListener('touchstart', (e) => {
        if (!e.touches || e.touches.length !== 1) return;
        touchY = e.touches[0].clientY;
      }, { passive: true, capture: true });
      trayEl.addEventListener('touchmove', (e) => {
        if (!e.touches || e.touches.length !== 1) return;
        const nextY = e.touches[0].clientY;
        const dy = touchY - nextY;
        touchY = nextY;
        const withinBody = !!(e.target && e.target.closest && e.target.closest('#event-tray-body'));
        if (!withinBody) {
          if (canScrollBody() && dy) body.scrollTop += dy;
          e.preventDefault();
        } else if (!canScrollBody()) {
          e.preventDefault();
        }
        e.stopPropagation();
      }, { passive: false, capture: true });

      body.addEventListener('dragover', (e) => {
        e.preventDefault();
        trayEl.classList.add('dragover');
      });
      body.addEventListener('dragleave', () => trayEl.classList.remove('dragover'));
      body.addEventListener('drop', (e) => {
        trayEl.classList.remove('dragover');
        const id = e.dataTransfer.getData('id') || e.dataTransfer.getData('text/plain');
        if (!id) return;
        e.preventDefault();
        const srcDate = e.dataTransfer.getData('source-date');
        unscheduleEvent(String(id), srcDate);
      });
    }

    trayEl.addEventListener('dragover', (e) => {
      e.preventDefault();
      trayEl.classList.add('dragover');
    }, true);
    trayEl.addEventListener('dragleave', () => trayEl.classList.remove('dragover'), true);
    trayEl.addEventListener('drop', (e) => {
      trayEl.classList.remove('dragover');
      const id = e.dataTransfer.getData('id') || e.dataTransfer.getData('text/plain');
      if (!id) return;
      e.preventDefault();
      const srcDate = e.dataTransfer.getData('source-date');
      unscheduleEvent(String(id), srcDate);
    }, true);

    return trayEl;
  }

  document.addEventListener('dragstart', (e) => {
    const eventEl = e.target && e.target.closest ? e.target.closest('.event') : null;
    if (!eventEl) return;
    const id = eventEl.dataset ? String(eventEl.dataset.id || '') : '';
    if (!id) return;
    try {
      e.dataTransfer.setData('id', id);
      e.dataTransfer.setData('text/plain', id);
      const installs = Array.isArray(w.allInstalls) ? w.allInstalls : [];
      const ev = installs.find(x => String(x.id) === id);
      const src = normalizeUnscheduledDate(ev?.date) || normalizeUnscheduledDate(eventEl.closest('.day')?.dataset?.date) || '';
      e.dataTransfer.setData('source-date', src);
      e.dataTransfer.effectAllowed = 'move';
    } catch (err) {}
  }, true);

  function unscheduleEvent(id, srcDate) {
    const installs = Array.isArray(w.allInstalls) ? w.allInstalls : [];
    const ev = installs.find(x => String(x.id) === String(id));
    if (!ev) return;
    const fromDate = normalizeUnscheduledDate(srcDate) || normalizeUnscheduledDate(ev.date);
    ev.notes = appendMovedFrom(ev.notes, fromDate);
    ev.date = null;
    const order = loadUnscheduledOrder().filter(x => x !== String(ev.id));
    order.unshift(String(ev.id));
    saveUnscheduledOrder(order);
    if (typeof window.saveInstalls === 'function') window.saveInstalls(ev, { silent: true });
  }

  function updateUnscheduledTray() {
    ensureUnscheduledTray();
    const list = trayEl.querySelector('#event-tray-list');
    if (!list) return;

    const installs = Array.isArray(w.allInstalls) ? w.allInstalls : [];
    const all = installs.filter(ev => !normalizeUnscheduledDate(ev.date));
    const order = loadUnscheduledOrder();
    const map = new Map(all.map(ev => [String(ev.id), ev]));
    const sorted = [];
    order.forEach(id => { const hit = map.get(String(id)); if (hit) { sorted.push(hit); map.delete(String(id)); } });
    Array.from(map.values()).forEach(ev => sorted.push(ev));

    list.innerHTML = '';
    sorted.forEach(ev => list.appendChild(buildTrayEventElement(ev)));
    saveUnscheduledOrder(sorted.map(ev => String(ev.id)));
  }

  w.ensureUnscheduledTray = w.ensureUnscheduledTray || ensureUnscheduledTray;
  w.updateUnscheduledTray = w.updateUnscheduledTray || updateUnscheduledTray;

  // Export core functions to global scope
  window.render = w.render;
  window.buildCalendar = w.buildCalendar;
  window.buildWeek = w.buildWeek;
  window.changeWeek = w.changeWeek;
  window.ensureWeeksToggle = w.ensureWeeksToggle;
  window.toggleWeeksVisibility = w.toggleWeeksVisibility;
  window.toggleProfitVisibility = w.toggleProfitVisibility;
  window.ensureUnscheduledTray = w.ensureUnscheduledTray;
  window.updateUnscheduledTray = w.updateUnscheduledTray;

})(window);
