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

  w.render = w.render || function render() {
    fetch('installs_api.php')
      .then(res => res.json())
      .then(data => {
        w.allInstalls = data;
        w.buildCalendar();
        if (typeof w.updateStats === 'function') w.updateStats();
        if (w.showExtendedWeeks) {
          setTimeout(() => {
            const currentWeek = document.getElementById('currentWeek');
            if (currentWeek) currentWeek.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }, 0);
        }
      });
  };

  w.changeWeek = w.changeWeek || function changeWeek(offset) {
    w.currentMonday.setDate(w.currentMonday.getDate() + offset * 7);
    w.render();
    if (typeof w.updateStats === 'function') w.updateStats();
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
    left.appendChild(w.monthLabel);
  }

  w.ensureWeeksToggle = w.ensureWeeksToggle || function ensureWeeksToggle() {
    ensureToolbar();
    if (!w.weeksToggleBtn) {
      w.weeksToggleBtn = document.createElement('button');
      w.weeksToggleBtn.type = 'button';
      w.weeksToggleBtn.className = 'weeks-toggle-btn';
      w.toolbarRight.appendChild(w.weeksToggleBtn);
    }
    w.weeksToggleBtn.textContent = w.showExtendedWeeks ? 'Hide Weeks' : 'Show Weeks';
    w.weeksToggleBtn.setAttribute('aria-pressed', String(w.showExtendedWeeks));
    w.weeksToggleBtn.onclick = () => {
      w.showExtendedWeeks = !w.showExtendedWeeks;
      w.ensureWeeksToggle();
      w.buildCalendar();
      if (typeof w.updateStats === 'function') w.updateStats();
      if (w.showExtendedWeeks) window.scrollTo({ top: 0, behavior: 'smooth' });
    };
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
    w.monthLabel.textContent = labelDate.toLocaleString('default', { month: 'long', year: 'numeric' }).toUpperCase();
    w.ensureWeeksToggle();
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
          <span class="date" style="background-color:${dateBgColor}" title="Click to show/hide map locations" data-original-text="${dayDate.getDate()} ${dayDate.toLocaleString('default', { month: 'short' }).toUpperCase()}">
            ${dayDate.getDate()} ${dayDate.toLocaleString('default', { month: 'short' }).toUpperCase()}
          </span>
          <button class="day-map-toggle" onclick="console.log('Globe clicked'); window.toggleDayMaps(this)" title="Show Map">
            <i class="fa-solid fa-globe"></i>
          </button>
        </div>
      `;
      // console.log('Building day:', dateStr); // Debug log
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
        const id = e.dataTransfer.getData('id');
        const item = (w.allInstalls || []).find(i => i.id === id);
        if (item) { item.date = dateStr; w.saveInstalls(item); }
      };

      const todaysInstalls = (w.allInstalls || []).filter(ev => ev.date === dateStr).sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
      todaysInstalls.forEach(ev => {
        const div = document.createElement('div'); div.className = `event ${ev.type || ''}`;
        const required = ['id', 'name', 'cams', 'bullets', 'dome', 'hdd', 'time', 'location', 'date'];
        const isComplete = required.every(f => ev[f] && ev[f].toString().trim() !== '');
        if (!isComplete) div.classList.add('missing');
        div.draggable = true; div.dataset.id = ev.id;
        div.ondragstart = e => { e.dataTransfer.setData('id', ev.id); e.dataTransfer.effectAllowed = 'move'; };
        div.ondragover = e => e.preventDefault();
        div.ondrop = e => {
          e.preventDefault();
          const draggedId = e.dataTransfer.getData('id');
          const targetId = ev.id; if (draggedId === targetId) return;
          const dragged = (w.allInstalls || []).find(i => i.id === draggedId);
          const target = (w.allInstalls || []).find(i => i.id === targetId);
          if (dragged && target && dragged.date === target.date) {
            const siblings = (w.allInstalls || []).filter(i => i.date === dragged.date && i.id !== draggedId);
            const targetIndex = siblings.findIndex(i => i.id === targetId);
            siblings.splice(targetIndex, 0, dragged); siblings.forEach((item, idx) => item.order = idx);
            w.saveInstalls(); w.render();
          }
        };
        ev.hdd = (ev.hdd || '').replace(/\s+/g, '');
        const monitorIcon = ev.monitor ? '<i class="fas fa-tv" title="Monitor"></i>' : '';
        const rackIcon = ev.rack ? '<i class="fas fa-server" title="Rack"></i>' : '';
        if (label === 'current') {
          const origin = 'HSR Layout';
          const destination = `${ev.location}, Bengaluru`;
          div.innerHTML = `
            <h3><span onclick="editInstall('${ev.id}')"> ${ev.id.replace('-', '<br>')}</span><a href="tel:${ev.phone}">${ev.name || 'No Name'}</a></h3>
            ${ev.notes ? `<div class="event-notes" style="background:#fffbcc;border-left:1px solid #f0b414;padding:4px 8px;margin:4px 0 9px;color:#333;"><strong>${window.escapeHtml(ev.notes)}</strong></div>` : ''}
            <div class="details">
              <span class="hdd">${ev.hdd || ''}</span>
              <span class="cams"> ${ev.resolution || ''} x <strong>${ev.cams || 0}</strong></span>
              <span class="cams type">${ev.bullets || 0} B + ${ev.dome || 0} D</span>
              <span class="area"><span id="dist-${ev.id}"></span> <a href="${ev.map || ''}" target="_blank">${ev.location || ''}</a>  @ ${ev.time || ''}</span>
              <span class="monitor">${monitorIcon}</span> <span class="rack">${rackIcon}</span></div>
            <div class="names"><strong>${ev.owner || ''}</strong> | <a href="#" onclick="sendToWhatsapp(event, ${JSON.stringify(ev).replace(/"/g, '&quot;')})">${ev.technician || ''}</a> | ${ev.helper || ''}</div>
            <div class="icons"><i class="fas fa-edit" onclick="editInstall('${ev.id}')" title="Edit"></i><i class="fas fa-trash" onclick="deleteInstall('${ev.id}')" title="Delete"></i></div>
          `;
          if (destination) {
            fetch(`../distance_api.php?origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}`)
              .then(res => res.json())
              .then(data => {
                const distEl = div.querySelector(`#dist-${ev.id}`);
                if (data.distance_km !== undefined) distEl.innerHTML = `<span class="distance">${Math.round(data.distance_km)}</span>`; else distEl.innerHTML = '';
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

      const addNoteBtn = document.createElement('button');
      addNoteBtn.className = 'add-note-btn'; addNoteBtn.type = 'button'; addNoteBtn.title = 'Add quick note';
      addNoteBtn.setAttribute('aria-label', 'Add quick note'); addNoteBtn.innerHTML = '<i class="fas fa-plus-circle" aria-hidden="true"></i>';
      addNoteBtn.addEventListener('click', (e) => { e.stopPropagation(); if (typeof window.openNoteForm === 'function') window.openNoteForm(dateStr); });
      day.appendChild(addNoteBtn);

      week.appendChild(day);
    }
    return week;
  };

  // Export core functions to global scope
  window.render = w.render;
  window.buildCalendar = w.buildCalendar;
  window.buildWeek = w.buildWeek;
  window.changeWeek = w.changeWeek;
  window.ensureWeeksToggle = w.ensureWeeksToggle;

})(window);
