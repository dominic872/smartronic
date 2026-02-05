
    const weeksContainer = document.getElementById("calendarWeeks");
    const monthLabel = document.getElementById("monthLabel");
    //const overlay = document.getElementById("overlay");
    
    const popup = document.getElementById('order-popup');
    const form = document.getElementById("installForm");
  
    let allInstalls = [];
    let currentMonday = getMonday(new Date());
    let editingId = null;
    const NOTES_API = 'notes_api.php'; 
    const monthColors = ["#03944f", "#e53935", "#f0b414", "#454dce", "#498aaa", "#c315dc"];
    let showExtendedWeeks = false; // hide prev/next weeks by default
    let weeksToggleBtn = null;
    let statsBtn = null;

    // cache for stats popup (filled by updateStats)
    let lastWeekStats = null;
    let lastMonthStats = null;

    // Technician stats caches for the popup
    let lastTechWeek = [];        // [{ tech, count }]
    let lastTechMonth = [];       // [{ tech, count }]
    let lastTechTwoPerDay = [];   // [{ tech, daysWithTwo }]
  
    function getMonday(date) {
      const d = new Date(date);
      const day = d.getDay();
      const diff = d.getDate() - day + (day === 0 ? -6 : 1);
      return new Date(d.setDate(diff));
    }
  
    function formatDate(d) {
      // Convert to "local ISO" so we don't lose 5:30 hrs to UTC
      const local = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
      return local.toISOString().split("T")[0]; // YYYY-MM-DD (local)
    }
    // Display date like "25 Aug" (local)
    function formatDateDisplay(iso) {
      // iso is "YYYY-MM-DD"
      const [y, m, d] = iso.split('-').map(Number);
      const dt = new Date(y, (m || 1) - 1, d || 1);
      const day = dt.getDate();
      const mon = dt.toLocaleString('default', { month: 'short' });
      return `${day} ${mon}`;
    }

    function debounce(fn, wait=250) {
      let t; 
      return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
    }

    function moveNoteToDate(id, destDate) {
      fetch(NOTES_API, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: Number(id), date: destDate }) // backend will push to end
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) render();
        else alert(d.message || "Failed to move note");
      })
      .catch(() => alert("Failed to move note"));
    }

    // Turn URLs and phone numbers into clickable links
    function linkify(text = "") {
      if (!text) return "";
      // URLs
      const urlRegex = /(\bhttps?:\/\/[^\s<]+)/gi;
      // Basic phone (e.g., 9884310000, +91-98843-10000, 09884310000)
      const phoneRegex = /(?:\+?\d[\d\s\-]{8,}\d)/g;

      let html = text.replace(urlRegex, (m) => `<a href="${m}" target="_blank" rel="noopener">${m}</a>`);
      html = html.replace(phoneRegex, (m) => {
        const digits = m.replace(/[^\d+]/g, "");
        return `<a href="tel:${digits}">${m}</a>`;
      });
      return html;
    }

    function ensureWeeksToggle() {
      if (!weeksToggleBtn) {
        const btn = document.createElement('button');
        btn.className = 'weeks-toggle-btn';
        btn.type = 'button';
        monthLabel.insertAdjacentElement('afterend', btn);
        weeksToggleBtn = btn;
      }
      weeksToggleBtn.textContent = showExtendedWeeks ? 'Hide Weeks' : 'Show Weeks';
      weeksToggleBtn.setAttribute('aria-pressed', String(showExtendedWeeks));
      weeksToggleBtn.onclick = () => {
        showExtendedWeeks = !showExtendedWeeks;
        ensureWeeksToggle();
        buildCalendar();     // re-render visibility
        updateStats();
        if (showExtendedWeeks) {
          // smooth scroll to top so the newly revealed weeks are visible
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      };
    }

    function ensureOwnerStatsButton() {
      if (statsBtn) return;
      const btn = document.createElement('button');
      btn.className = 'owner-stats-btn';
      btn.type = 'button';
      btn.textContent = 'Owner stats';
      btn.onclick = openOwnerStatsModal;
      // put it after the weeks toggle (or after month label if you prefer)
      (weeksToggleBtn || monthLabel).insertAdjacentElement('afterend', btn);
      statsBtn = btn;
    }

    function techDatesTable(title, rows) {
    const fmt = (iso) => {
      // reuse your existing formatDateDisplay if available:
      if (typeof formatDateDisplay === 'function') return formatDateDisplay(iso);
      // fallback:
      const [y, m, d] = iso.split('-').map(Number);
      const dt = new Date(y, (m || 1) - 1, d || 1);
      return dt.toLocaleString('default', { day: '2-digit', month: 'short' });
    };

    return `
      <div class="stats-card">
        <div class="stats-card-title">${title}</div>
        <div class="tech-table-wrap">
          <table class="tech-table tech-dates-table">
            <thead>
              <tr>
                <th>Technician</th>
                <th class="num">Days with exactly 2 installs</th>
                <th>Dates</th>
              </tr>
            </thead>
            <tbody>
              ${
                rows.length
                  ? rows.map(r => `
                      <tr>
                        <td>${escapeHtml(r.tech)}</td>
                        <td class="num">${r.dates.length}</td>
                        <td>
                          <div class="date-chip-row">
                            ${r.dates.map(d => `<span class="date-chip">${fmt(d)}</span>`).join('')}
                          </div>
                        </td>
                      </tr>
                    `).join('')
                  : `<tr><td colspan="3" class="muted">No technician has exactly two installs on any day this month.</td></tr>`
              }
            </tbody>
          </table>
        </div>
      </div>
    `;
  }


    let toolbar = null;
    let toolbarRight = null;

    // stats caches for the popup

    let lastWeekTotalCount = 0;
    let lastMonthTotalCount = 0;
    let lastMonthTotalCams = 0;

    function ensureToolbar() {
      if (toolbar && toolbarRight) return;

      // Create toolbar skeleton
      toolbar = document.createElement('div');
      toolbar.id = 'calendarToolbar';
      toolbar.className = 'calendar-toolbar';

      const left = document.createElement('div');
      left.className = 'toolbar-left';

      toolbarRight = document.createElement('div');
      toolbarRight.className = 'toolbar-right';

      toolbar.appendChild(left);
      toolbar.appendChild(toolbarRight);

      // Insert the toolbar as the first child of .calendar (before .weekdays)
      const calendarEl = document.querySelector('.calendar');
      const weekdaysEl = calendarEl.querySelector('.weekdays');

      if (weekdaysEl) {
        calendarEl.insertBefore(toolbar, weekdaysEl);
      } else {
        // fallback: if no .weekdays, just prepend
        calendarEl.insertBefore(toolbar, calendarEl.firstChild);
      }

      // Move the existing monthLabel into the left side
      left.appendChild(monthLabel);
    }

    function ensureWeeksToggle() {
      ensureToolbar();
      if (!weeksToggleBtn) {
        weeksToggleBtn = document.createElement('button');
        weeksToggleBtn.type = 'button';
        weeksToggleBtn.className = 'weeks-toggle-btn';
        toolbarRight.appendChild(weeksToggleBtn);
      }
      weeksToggleBtn.textContent = showExtendedWeeks ? 'Hide Weeks' : 'Show Weeks';
      weeksToggleBtn.setAttribute('aria-pressed', String(showExtendedWeeks));
      weeksToggleBtn.onclick = () => {
        showExtendedWeeks = !showExtendedWeeks;
        ensureWeeksToggle();
        buildCalendar();
        updateStats();
        if (showExtendedWeeks) {
          window.scrollTo({ top: 0, behavior: 'smooth' }); // reveal from top when showing
        }
      };
    }

    function ensureOwnerStatsButton() {
      ensureToolbar();
      if (statsBtn) return;
      statsBtn = document.createElement('button');
      statsBtn.type = 'button';
      statsBtn.className = 'owner-stats-btn';
      statsBtn.textContent = 'Show Stats';
      statsBtn.onclick = openOwnerStatsModal;
      toolbarRight.appendChild(statsBtn);
    }

    function persistNoteOrder(dayEl) {
      const date = dayEl.dataset.date;
      // collect notes in DOM order (across groups)
      const ids = Array.from(dayEl.querySelectorAll('.note')).map(n => Number(n.dataset.id));
      fetch(NOTES_API, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ reorder: true, date, order: ids })
      })
      .then(r => r.json())
      .then(d => {
        if (!d.success) console.warn("Failed to persist note order:", d);
      })
      .catch(err => console.warn("Order persist error", err));
    }
    function moveNoteToDate(id, destDate) {
      fetch(NOTES_API, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: Number(id), date: destDate }) // backend will push to end
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) render();
        else alert(d.message || "Failed to move note");
      })
      .catch(() => alert("Failed to move note"));
    }

  function persistNoteOrder(dayEl) {
    const date = dayEl.dataset.date;
    // collect notes in DOM order (across groups)
    const ids = Array.from(dayEl.querySelectorAll('.note')).map(n => Number(n.dataset.id));
    fetch(NOTES_API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ reorder: true, date, order: ids })
    })
    .then(r => r.json())
    .then(d => {
      if (!d.success) console.warn("Failed to persist note order:", d);
    })
    .catch(err => console.warn("Order persist error", err));
  }
      
    function changeWeek(offset) {
      currentMonday.setDate(currentMonday.getDate() + offset * 7);
      render();
      updateStats(); // Call updateStats after changing week
    }

    function render() {
      fetch('installs_api.php')
        .then(res => res.json())
        .then(data => {
          allInstalls = data;
          buildCalendar();
          updateStats(); // Add this line to update stats
          // Scroll to current week after rendering
          setTimeout(() => {
            const currentWeek = document.getElementById('currentWeek');
            if (currentWeek) {
              currentWeek.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
          }, 0);
        });
    }

  function buildCalendar() {
    weeksContainer.innerHTML = "";

    const prev2 = new Date(currentMonday); prev2.setDate(prev2.getDate() - 14);
    const prev1 = new Date(currentMonday); prev1.setDate(prev1.getDate() - 7);
    const next1 = new Date(currentMonday); next1.setDate(next1.getDate() + 7);
    const next2 = new Date(currentMonday); next2.setDate(next2.getDate() + 14);

    // build all weeks
    const wPrev2 = buildWeek(prev2, "prev");
    const wPrev1 = buildWeek(prev1, "prev");
    const wCurr  = buildWeek(currentMonday, "current");
    const wNext1 = buildWeek(next1, "next");
    const wNext2 = buildWeek(next2, "next");

    // mark extended weeks so we can toggle them
    [wPrev2, wPrev1, wNext1, wNext2].forEach(w => {
      w.classList.add('extended-week');
      // hide/show based on toggle
      w.style.display = showExtendedWeeks ? "" : "none";
    });

    wCurr.id = "currentWeek";

    // append in order
    weeksContainer.appendChild(wPrev2);
    weeksContainer.appendChild(wPrev1);
    weeksContainer.appendChild(wCurr);
    weeksContainer.appendChild(wNext1);
    weeksContainer.appendChild(wNext2);

    // month label
    const labelDate = new Date(currentMonday);
    monthLabel.textContent = labelDate.toLocaleString("default", { month: "long", year: "numeric" }).toUpperCase();

    // make sure toggle button exists/updates label
    ensureWeeksToggle();

    // only scroll when extended are visible (when hidden, current week is already at top)
    if (showExtendedWeeks) {
      setTimeout(() => {
        const currentWeek = document.getElementById('currentWeek');
        if (currentWeek) currentWeek.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 0);
    }
  }

    async function fetchLeaves(date) {
        try {
            const res = await fetch(`../vacation/api_get_leaves.php?date=${date}`);
            const data = await res.json();
            if (data.success) return data.leaves;
        } catch (e) {
            console.error(`Error fetching leaves for ${date}`, e);
        }
        return [];
        }
  
    function buildWeek(startDate, label) {
      const week = document.createElement("div");
      week.className = `week ${label}`;

      for (let i = 0; i < 7; i++) {
        const dayDate = new Date(startDate);
        dayDate.setDate(startDate.getDate() + i);
        const dateStr = formatDate(dayDate);

        const day = document.createElement("div");
        day.className = "day";
        day.dataset.date = dateStr;

        const monthIndex = dayDate.getMonth() % monthColors.length;

        if (dateStr === formatDate(new Date())) {
          day.classList.add("today");
        }

        const dateBgColor = monthColors[monthIndex];
        day.innerHTML = `<span class="date" style="background-color: ${dateBgColor}">${dayDate.getDate()} ${dayDate.toLocaleString('default', { month: 'short' }).toUpperCase()}</span>`;
        fetchLeaves(dateStr).then(leaves => {
          if (leaves.length > 0) {
            const leaveDiv = document.createElement("div");
            leaveDiv.className = "leaves";
            leaveDiv.innerHTML = `
            <strong><i class="fas fa-user-times"></i></strong> ${leaves.map(l => l.fullname).join(", ")}
            `;
            day.querySelector(".date").after(leaveDiv);
          }
        });
        day.ondblclick = () => openForm({ date: dateStr });
        day.ondragover = e => { e.preventDefault(); day.classList.add("dragover"); };
        day.ondragleave = () => day.classList.remove("dragover");
        day.ondrop = e => {
          e.preventDefault();
            day.classList.remove("dragover");

            const noteId = e.dataTransfer.getData('note-id');
            if (noteId) {
              // Move note to this day (end of list)
              moveNoteToDate(noteId, dateStr);
              return;
            }
          const id = e.dataTransfer.getData("id");
          const item = allInstalls.find(i => i.id === id);
          if (item) {
            item.date = dateStr;
            saveInstalls(item);  // pass the updated record
          }
        };

        const todaysInstalls = allInstalls.filter(ev => ev.date === dateStr);
        // Sort by order field
        todaysInstalls.sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
        todaysInstalls.forEach(ev => {
          const div = document.createElement("div");
          div.className = `event ${ev.type || ""}`;
          const requiredFields = ['id', 'name', 'cams', 'bullets', 'dome', 'hdd', 'time', 'location', 'date'];
          const isComplete = requiredFields.every(f => ev[f] && ev[f].toString().trim() !== "");
          if (!isComplete) div.classList.add("missing");
          div.draggable = true;
          div.dataset.id = ev.id;
          div.ondragstart = e => {
            e.dataTransfer.setData("id", ev.id);
            e.dataTransfer.effectAllowed = "move";
          };
          div.ondragover = e => e.preventDefault();
          div.ondrop = e => {
            e.preventDefault();
            const draggedId = e.dataTransfer.getData("id");
            const targetId = ev.id;
            if (draggedId === targetId) return;
            const dragged = allInstalls.find(i => i.id === draggedId);
            const target = allInstalls.find(i => i.id === targetId);
            if (dragged && target && dragged.date === target.date) {
              const siblings = allInstalls.filter(i => i.date === dragged.date && i.id !== draggedId);
              const targetIndex = siblings.findIndex(i => i.id === targetId);
              siblings.splice(targetIndex, 0, dragged);
              siblings.forEach((item, idx) => item.order = idx);
              saveInstalls();
              render();
            }
          };
          ev.hdd = ev.hdd.replace(/\s+/g, '');
          const monitorIcon = ev.monitor ? '<i class="fas fa-tv" title="Monitor"></i>' : "";
          const rackIcon = ev.rack ? '<i class="fas fa-server" title="Rack"></i>' : "";
          // Show full details only for current week, simplified view for prev/next weeks
          if (label === "current") {
            const origin = "HSR Layout"; // You can change this
            const destination = `${ev.location}, Bengaluru`;
            div.innerHTML = `
              <h3><span onclick="editInstall('${ev.id}')"> ${ev.id.replace('-', '<br>')}</span><a href="tel:${ev.phone}">${ev.name || "No Name"}</a></h3> 
              ${ev.notes ? `<div class="event-notes" style="background:#fffbcc;border-left:1px solid #f0b414;padding:4px 8px;margin:4px 0 9px;color:#333;"><strong>${escapeHtml(ev.notes)}</strong></div>` : ''}
              <div class="details">
                <span class="hdd">${ev.hdd || ""}</span>
                <span class="cams"> ${ev.resolution || ""} x <strong>${ev.cams || 0}</strong></span> 
                <span class="cams type">${ev.bullets || 0} B + ${ev.dome || 0} D</span>
                 
                 <span class="area"><span id="dist-${ev.id}"></span> <a href="${ev.map || ""}" target="_blank">${ev.location || ""}</a>  
                
                 @ ${ev.time || ""}</span> 
                 <span class="monitor">${monitorIcon}</span> <span class="rack">${rackIcon}</span></div>
                 <div class="names"><strong>${ev.owner || ""}</strong> | 
                 <a href="#" onclick="sendToWhatsapp(event, ${JSON.stringify(ev).replace(/"/g, '&quot;')})">
                    ${ev.technician || ""}
                </a> | ${ev.helper || ""}</div>
              <div class="icons">
                <i class="fas fa-edit" onclick="editInstall('${ev.id}')" title="Edit"></i>
                <i class="fas fa-trash" onclick="deleteInstall('${ev.id}')" title="Delete"></i>
              </div>
            `;
            // Fetch distance from your PHP API
            if (destination) {
              fetch(`../distance_api.php?origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}`)
                .then(res => res.json())
                .then(data => {
                  const distEl = div.querySelector(`#dist-${ev.id}`);
                  if (data.distance_km !== undefined) {
                    distEl.innerHTML = `<span class="distance">${Math.round(data.distance_km)}</span>`;
                  } else {
                    distEl.innerHTML = '';
                  }
                })
                .catch(err => {
                  console.error("Distance fetch error:", err);
                });
            }
          } else {
            div.innerHTML = `
            <span class="past-event"><strong>${ev.id || 0}</strong> | ${ev.name || "No Name"}</span>
              <div class="icons past">
                <i class="fas fa-edit" onclick="editInstall('${ev.id}')" title="Edit"></i>
                <i class="fas fa-trash" onclick="deleteInstall('${ev.id}')" title="Delete"></i>
              </div>
            `;
          }
          day.appendChild(div);
        });

        
        // --- Add notes below installs ---
// --- Add notes below installs (grouped by Type) ---

fetchNotes(dateStr).then(notes => {
  if (!notes || !notes.length) return;

  // Group notes by type
  const groups = { Issue: [], Inspection: [], General: [] };
  notes.forEach(n => {
    const t = (n.type === 'Issue' || n.type === 'Inspection' || n.type === 'General') ? n.type : 'General';
    groups[t].push(n);
  });

  // Visual order of groups
  const typeOrder = ['Issue', 'Inspection', 'General'];
  const presentTypes = typeOrder.filter(t => groups[t] && groups[t].length);

  presentTypes.forEach((type, idx) => {
    const arr = groups[type];
    if (!arr || !arr.length) return;

    // Group wrapper
    const group = document.createElement('div');
    group.className = `note-group note-${type.toLowerCase()}`;

    arr.forEach(note => {
      const item = document.createElement('div');
      item.className = 'note';
      item.dataset.id = note.id;

      // Make each note draggable
      item.draggable = true;
      item.addEventListener('dragstart', (e) => {
        e.dataTransfer.setData('note-id', String(note.id));
        e.dataTransfer.effectAllowed = 'move';
        item.classList.add('note-dragging');
      });
      item.addEventListener('dragend', () => item.classList.remove('note-dragging'));

      // Allow reordering within the day
      item.addEventListener('dragover', (e) => {
        if (!e.dataTransfer.getData('note-id')) return;
        e.preventDefault();
        item.classList.add('note-dragover');
      });
      item.addEventListener('dragleave', () => item.classList.remove('note-dragover'));
      item.addEventListener('drop', (e) => {
        e.preventDefault();
        item.classList.remove('note-dragover');
        const draggedId = e.dataTransfer.getData('note-id');
        if (!draggedId || +draggedId === note.id) return;

        const dayEl = item.closest('.day');
        const draggedEl = dayEl.querySelector(`.note[data-id="${draggedId}"]`);
        if (draggedEl) {
          dayEl.insertBefore(draggedEl, item);
          persistNoteOrder(dayEl); // save new order
        }
      });

      // TYPE | TITLE (bold), then next line description
      item.innerHTML = `
        <div class="note-line">
          <div class="note-main">
            <span class="note-type">${type.toUpperCase()}</span>
            <span class="note-sep">|</span>
            <strong class="note-title">${note.title || ''}</strong>
          </div>
        </div>
        
        <div class="note-desc">${linkify(note.description || '')}</div>
          ${note.phone ? `
        <div class="note-phone">
          <a href="tel:${note.phone}">${note.phone}</a>
        </div>` : ''}

        <!-- controls: top-right (edit), bottom-right (delete) -->
        <button class="note-edit" title="Edit" aria-label="Edit"
          onclick='editNote(${JSON.stringify(note).replace(/"/g, '&quot;')})'>
          <i class="fas fa-edit" aria-hidden="true"></i>
        </button>
        <button class="note-delete" title="Delete" aria-label="Delete"
          onclick="deleteNote(${note.id})">
          <i class="fas fa-trash" aria-hidden="true"></i>
        </button>
      `;

      group.appendChild(item);
    });

    day.appendChild(group);

    // Add a separator between type groups when there are ≥2 types
    if (presentTypes.length > 1 && idx < presentTypes.length - 1) {
      const sep = document.createElement('hr');
      sep.className = 'note-type-sep';
      day.appendChild(sep);
    }
  });
});

// --- End notes ---

// --- Add "+" button for quick note ---

const addNoteBtn = document.createElement("button");
addNoteBtn.className = "add-note-btn";
addNoteBtn.type = "button";
addNoteBtn.title = "Add quick note";
addNoteBtn.setAttribute("aria-label", "Add quick note");
addNoteBtn.innerHTML = '<i class="fas fa-plus-circle" aria-hidden="true"></i>';
addNoteBtn.addEventListener("click", (e) => {
  e.stopPropagation();
  openNoteForm(dateStr);
});
day.appendChild(addNoteBtn);

// --- End "+" button ---

        week.appendChild(day);
      }

      return week;
    }

// --- Fetch Notes Function ---
async function fetchNotes(date) {
  try {
    const url = `${NOTES_API}?date=${encodeURIComponent(date)}&t=${Date.now()}`;
    const res = await fetch(url, { cache: 'no-store' });

    const contentType = res.headers.get('content-type') || '';
    const raw = await res.text(); // read raw text first

    if (!res.ok) {
      console.error('Notes API HTTP error:', res.status, raw);
      return [];
    }
    if (!contentType.includes('application/json')) {
      console.error('Notes API non-JSON response:', raw);
      return [];
    }

    const data = JSON.parse(raw);
    if (!data || data.success !== true) {
      console.warn('Notes API success:false or malformed:', data);
      return [];
    }

    return Array.isArray(data.notes) ? data.notes : [];
  } catch (e) {
    console.error(`Error fetching notes for ${date}`, e);
    return [];
  }
}
// --- End Fetch Notes Function ---
function saveNote(note) {
  fetch(NOTES_API, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(note)
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      render();
    } else {
      alert(data.message || "Failed to save note");
    }
  })
  .catch(() => alert("Failed to save note"));
}


// --- Quick Note Popup Functions ---
function openNoteForm(date, note = null) {
  // remove any existing popup
  closeNoteForm();

  const isEdit = !!(note && note.id);

  // Backdrop
  const backdrop = document.createElement('div');
  backdrop.id = 'note-popup-backdrop';
  backdrop.setAttribute('role', 'presentation');
  backdrop.style.cssText = `
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    z-index: 2147483646;
  `;

  // Wrapper (centers dialog)
  const wrap = document.createElement('div');
  wrap.id = 'note-popup-wrap';
  wrap.setAttribute('role', 'dialog');
  wrap.setAttribute('aria-modal', 'true');
  wrap.setAttribute('aria-label', isEdit ? 'Edit quick note' : 'Add quick note');
  wrap.style.cssText = `
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    z-index: 2147483647;
  `;

  // Dialog
  const form = document.createElement('form');
  form.id = 'noteForm';
  form.className = 'note-popup';
  form.innerHTML = `
    <h3 style="margin:0 0 12px 0;font-size:16px;">
      ${isEdit ? "Edit Quick Note" : "Add Quick Note"} (${formatDateDisplay(date)})
    </h3>

    <label class="np-field" style="display:grid;grid-template-columns:110px 1fr;align-items:center;gap:10px;margin:10px 0;">
      <span style="font-weight:600;">Type</span>
      <select id="noteType" name="type" style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px;font:inherit;outline:none;">
        <option value="General">General</option>
        <option value="Issue">Issue</option>
        <option value="Inspection">Inspection</option>
      </select>
    </label>




    <label class="np-field" style="display:grid;grid-template-columns:110px 1fr;align-items:center;gap:10px;margin:10px 0;">
      <span style="font-weight:600;">Phone or Name</span>
      <div class="np-phone-wrap">
        <input type="text" id="notePhone" name="phone"
              placeholder="Enter phone or name (min 3 chars)"
              style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px;font:inherit;outline:none;" />
        <div id="phoneSuggestions" class="np-suggest" aria-label="Matching orders"></div>
      </div>
    </label>

    <label class="np-field" style="display:grid;grid-template-columns:110px 1fr;align-items:center;gap:10px;margin:10px 0;">
      <span style="font-weight:600;">Title</span>
      <input type="text" id="noteTitle" name="title" required
             style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px;font:inherit;outline:none;" />
    </label>

    <label class="np-field" style="display:grid;grid-template-columns:110px 1fr;align-items:center;gap:10px;margin:10px 0;">
      <span style="font-weight:600;">Description</span>
      <textarea id="noteDesc" name="description" rows="4"
                style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:8px;font:inherit;outline:none;resize:vertical;"></textarea>
    </label>

    <input type="hidden" id="noteDate" name="date" value="${date}">
    <input type="hidden" id="noteId" name="id" value="${isEdit ? note.id : ""}">

    <div class="np-actions" style="display:flex;justify-content:flex-end;gap:10px;margin-top:12px;">
      <button type="submit" class="np-save"
              style="padding:8px 12px;border-radius:8px;border:1px solid transparent;background:#111;color:#fff;cursor:pointer;font:inherit;">
        ${isEdit ? "Update" : "Save"}
      </button>
      <button type="button" class="np-cancel" id="np-cancel-btn"
              style="padding:8px 12px;border-radius:8px;border:1px solid #e6e6e6;background:#f2f2f2;color:#333;cursor:pointer;font:inherit;">
        Cancel
      </button>
    </div>
  `;
  // dialog container styles (inline so it always overlays)
  form.style.cssText = `
    width: min(520px, 92vw);
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 14px 40px rgba(0,0,0,0.25);
    padding: 18px 18px 14px;
    max-height: 85vh;
    overflow: auto;
    font-size: 14px;
  `;

  // Lock background scroll
  document.documentElement.style.overflow = 'hidden';
  document.body.style.overflow = 'hidden';

  wrap.appendChild(form);
  // Append directly to body (no extra wrappers)
  document.body.appendChild(backdrop);
  document.body.appendChild(wrap);

// Prefill for edit (add phone)
if (isEdit) {
  document.getElementById("noteType").value = note.type || "General";
  document.getElementById("noteTitle").value = note.title || "";
  document.getElementById("noteDesc").value = note.description || "";
  if (note.phone) document.getElementById("notePhone").value = note.phone;
}

// Phone search wiring
const phoneInput = document.getElementById('notePhone');
const suggBox   = document.getElementById('phoneSuggestions');

// Build a pretty preview text for the description
function orderToPreview(o){
  // tweak the format as you like
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
      <div class="np-s-line1">
        <strong>${o.name || 'Unknown'}</strong> &middot; ${o.phone || ''}
      </div>
      <div class="np-s-line2">
        ${o.date || ''} &middot; ${o.location || ''} &middot; ${o.cams ? (o.cams + ' cams') : ''}
      </div>
    </div>
  `).join('');
  suggBox.style.display = 'block';

  // hover preview
  suggBox.querySelectorAll('.np-suggest-item').forEach(el => {
    el.addEventListener('mouseenter', () => {
      const id = el.getAttribute('data-id');
      const hit = latestResults.find(r => String(r.id) === String(id));
      if (hit) {
        document.getElementById('noteDesc').value = orderToPreview(hit);
      }
    });
    // click to commit
    el.addEventListener('click', (evt) => {
      evt.stopPropagation();
      const id = el.getAttribute('data-id');
      const hit = latestResults.find(r => String(r.id) === String(id));
      if (hit) {
        phoneInput.value = hit.phone || '';
        if (!document.getElementById('noteTitle').value) {
          document.getElementById('noteTitle').value = hit.name || '';
        }
        document.getElementById('noteDesc').value = orderToPreview(hit);
      }
      suggBox.innerHTML = '';
      suggBox.style.display = 'none';
    });
    el.addEventListener('click', () => {
      const id = el.getAttribute('data-id');
      const hit = latestResults.find(r => String(r.id) === String(id));
      if (hit) {
        // fill phone + description; also a good title if empty
        phoneInput.value = hit.phone || '';
        if (!document.getElementById('noteTitle').value) {
          document.getElementById('noteTitle').value = hit.name || '';
        }
        document.getElementById('noteDesc').value = orderToPreview(hit);
        suggBox.style.display = 'none';
      }
    });
  });
}

let latestResults = [];

const doSearch = debounce(async (q) => {
  const digits = (q.match(/\d/g) || []).length;
  const letters = (q.match(/[A-Za-z]/g) || []).length;
  if (!q || (digits < 3 && letters < 3)) { // need at least 3 digits OR 3 letters
    renderSuggestions([]);
    return;
  }
  try {
    const res = await fetch(`orders_search.php?q=${encodeURIComponent(q)}`, { cache: 'no-store' });
    const data = await res.json();
    latestResults = Array.isArray(data.results) ? data.results : [];
    renderSuggestions(latestResults);
  } catch (e) {
    latestResults = [];
    renderSuggestions([]);
    console.warn('Search failed', e);
  }
}, 300);

phoneInput.addEventListener('input', (e) => doSearch(e.target.value));
phoneInput.addEventListener('focus',  () => { if (latestResults.length) suggBox.style.display = 'block'; });
document.addEventListener('click', (e) => {
  if (!suggBox.contains(e.target) && e.target !== phoneInput) {
    suggBox.style.display = 'none';
  }
}, { capture: true });

  // Submit handler
  form.onsubmit = function(e) {
    e.preventDefault();
    const data = {
      id: document.getElementById("noteId").value || undefined,
      type: document.getElementById("noteType").value,
      title: document.getElementById("noteTitle").value.trim(),
      description: document.getElementById("noteDesc").value.trim(),
      date: document.getElementById("noteDate").value,
      phone: document.getElementById("notePhone").value.trim() || null

    };
    saveNote(data);
    closeNoteForm();
  };

  // Close handlers
  document.getElementById('np-cancel-btn').onclick = closeNoteForm;
  backdrop.onclick = closeNoteForm;
  const escHandler = (ev) => { if (ev.key === 'Escape') closeNoteForm(); };
  document.addEventListener('keydown', escHandler, { once: true });
}

function openOwnerStatsModal() {
  // build if not present
  if (!document.getElementById('stats-backdrop')) {
    const backdrop = document.createElement('div');
    backdrop.id = 'stats-backdrop';
    backdrop.className = 'modal-backdrop';
    backdrop.addEventListener('click', closeOwnerStatsModal);

    const wrap = document.createElement('div');
    wrap.id = 'stats-modal';
    wrap.className = 'modal-wrap';
    wrap.setAttribute('role', 'dialog');
    wrap.setAttribute('aria-modal', 'true');
    wrap.setAttribute('aria-label', 'Show Stats');

    wrap.innerHTML = `
      <div class="modal-card">
        <div class="modal-head">
          <h3>Stats</h3>
          <button class="modal-close" aria-label="Close" title="Close">&times;</button>
        </div>
        <div class="modal-body" id="stats-body"></div>
      </div>
    `;

    document.body.appendChild(backdrop);
    document.body.appendChild(wrap);
    wrap.querySelector('.modal-close').addEventListener('click', closeOwnerStatsModal);
    document.addEventListener('keydown', escCloseStats, { once: true });
  }

  // render stats each time (fresh)
  const body = document.getElementById('stats-body');
  if (!lastWeekStats || !lastMonthStats) {
    body.innerHTML = `<p>No data yet.</p>`;
  } else {
    body.innerHTML = buildStatsHTML(lastWeekStats, lastMonthStats);
  }

  document.body.classList.add('modal-open');
}

function closeOwnerStatsModal() {
  const b = document.getElementById('stats-backdrop');
  const m = document.getElementById('stats-modal');
  if (b) b.remove();
  if (m) m.remove();
  document.body.classList.remove('modal-open');
}

function escCloseStats(e) {
  if (e.key === 'Escape') closeOwnerStatsModal();
}

function buildStatsHTML(week, month) {
  const owners = ['DAR','AMR','DOM'];
  const types = ['WIFI','DVR','NVR'];

  const summary = `
    <div class="stats-summary">
      <div class="summary-pill"><span class="label">Weekly installs</span><span class="value">${lastWeekTotalCount}</span></div>
      <div class="summary-pill"><span class="label">Monthly installs</span><span class="value">${lastMonthTotalCount}</span></div>
      <div class="summary-pill"><span class="label">Cameras this month</span><span class="value">${lastMonthTotalCams}</span></div>
    </div>
  `;

  const ownerCard = (title, data) => `
    <div class="stats-card">
      <div class="stats-card-title">${title}</div>
      <div class="stats-grid">
        ${owners.map(o => `
          <div class="owner-block">
            <div class="owner-name">${o}</div>
            <div class="owner-row">
              ${types.map(t => `
                <div class="metric">
                  <div class="metric-num">${(data[o] && data[o][t]) ? data[o][t] : 0}</div>
                  <div class="metric-label">${t}</div>
                </div>
              `).join('')}
            </div>
          </div>
        `).join('')}
      </div>
    </div>
  `;

  const techTable = (title, rows, showCams=false) => `
    <div class="stats-card">
      <div class="stats-card-title">${title}</div>
      <div class="tech-table-wrap">
        <table class="tech-table">
          <thead>
            <tr>
              <th>Technician</th>
              <th>Installs</th>
              ${showCams ? '<th>Cams</th>' : ''}
            </tr>
          </thead>
          <tbody>
            ${
              rows.length
              ? rows.map(r => `
                  <tr>
                    <td>${escapeHtml(r.tech)}</td>
                    <td class="num">${r.count ?? r.daysWithTwo}</td>
                    ${showCams ? `<td class="num">${r.cams ?? 0}</td>` : ''}
                  </tr>
                `).join('')
              : `<tr><td colspan="${showCams?3:2}" class="muted">No data</td></tr>`
            }
          </tbody>
        </table>
      </div>
    </div>
  `;

   return `
    ${summary}
    ${ownerCard('This week (Owners)', week)}
    ${ownerCard('This month (Owners)', month)}

    ${techTable('This week (Technicians)', lastTechWeek, true)}
    ${techTable('This month (Technicians)', lastTechMonth, true)}
    ${techTable('Two-in-a-day this month (Technicians)', lastTechTwoPerDay, false)}
    ${techDatesTable('Two-in-a-day dates this month (Technicians)', lastTechTwoPerDayDates)}
  `;
}

// Small utility to avoid breaking HTML if tech names contain special chars
function escapeHtml(s){
  return String(s).replace(/[&<>"']/g, m => (
    { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]
  ));
}

function closeNoteForm() {
  const wrap = document.getElementById('note-popup-wrap');
  const backdrop = document.getElementById('note-popup-backdrop');
  if (wrap) wrap.remove();
  if (backdrop) backdrop.remove();
  // restore scroll
  document.documentElement.style.overflow = '';
  document.body.style.overflow = '';
}


function editNote(note) {
  // note contains {id, type, title, description, date}
  openNoteForm(note.date, note);
}

// 3) DELETE
function deleteNote(id) {
  if (!id) return;
  if (!confirm("Delete this note?")) return;

  fetch(NOTES_API, {
    method: "DELETE",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) render();
    else alert(data.message || "Failed to delete note");
  })
  .catch(() => alert("Failed to delete note"));
}
// --- End Quick Note Popup ---
  
   function openForm(data = {}) {
     const form = document.querySelector('#installForm');
  form.reset();
  Object.entries(data).forEach(([key, value]) => {
    const field = form.querySelector(`#${key}`);
    if (field) {
      field.value = value;
    }
  });
  editingId = data.id || null;
  //overlay.style.display = "block";
  popup.style.display = "block";
  redirectToOrderItems('')
}
  
    function closeForm() {
      //orderoverlay.style.display = "none";
      popup.style.display = "none";
    }
  
    form.onsubmit = function (e) {
      e.preventDefault();
      const data = {
        id: form.id.value,
        name: form.name.value,
        cams: form.cams.value,
        bullets: form.bullets.value,
        dome: form.dome.value,
        hdd: (form.hdd.value).replace(/\s+/g, ''),
        monitor: form.monitor.value,
        type: form.type.value,
        location: form.location.value,
        time: form.time.value,
        date: form.date.value,
        owner: form.owner.value,
        technician: form.technician.value,
        helper: form.helper.value,
        resolution: form.resolution.value, // Add resolution to data
        map: form.map.value, // Add map to data
        rack: form.rack.value, // Add rack to data
        notes: form.notes ? form.notes.value.trim() : ""
      };
      
      if (!editingId) {
        data.order = allInstalls.filter(i => i.date === data.date).length;
      }
      if (editingId) {
        allInstalls = allInstalls.map(i => i.id === editingId ? data : i);
      } else {
        allInstalls.push(data);
      }
      saveInstalls(data);
      closeForm();
      render();
    };
  
    function editInstall(id) {
  const data = allInstalls.find(i => i.id === id);

  if (data) {
    openForm(data);
  }
  switchTab('requirement');
  // Initialize Google Places autocomplete for the location field
  setTimeout(() => {
      initLocationAutocomplete();
    }, 3000); // Adjust if needed


  const fields = [
    'id', 'name', 'cams', 'bullets', 'dome', 'hdd',
    'monitor', 'type', 'location', 'time', 'date',
    'owner', 'technician', 'helper', 'resolution', 'map', 'rack', 'notes'
  ];

  const params = new URLSearchParams();

  fields.forEach(field => {
    const input = document.getElementById(field);
    if (input && input.value) {
      params.append(field, input.value);
    }
    orderDetails[field] = input.value;
  });

  getQuoteDetails(orderDetails);
}

  
function deleteInstall(id) {
  if (confirm("Are you sure to delete this install?")) {
    fetch('installs.php', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        render();
      } else {
        alert(data.message || "You do not have permission to delete this.");
      }
    });
  }
}

  
function saveInstalls(data) {
  if (!data || !data.id) {
    console.error("No data to save");
    return;
  }

  fetch('installs_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  }).then(res => res.json())
    .then(() => render());
}

    render();

    // Add this new function to calculate and display statistics
    var installDetails = {};
    const orderDetails = {};
    console.log(installDetails);
    function updateStats() {
      const today = new Date();
      
      // Get the Monday of the current week (the week being displayed)
      const currentWeekStart = new Date(currentMonday);
      currentWeekStart.setHours(0, 0, 0, 0); // Set to 00:00:00
      
      const currentWeekEnd = new Date(currentWeekStart);
      currentWeekEnd.setDate(currentWeekStart.getDate() + 6); // Sunday
      currentWeekEnd.setHours(23, 59, 59, 999); // Set to 23:59:59
      
      // Get the month of the currently displayed week - from 1st day 00:00 to last day 23:59:59
      const displayedMonthStart = new Date(currentWeekStart.getFullYear(), currentWeekStart.getMonth(), 1);
      displayedMonthStart.setHours(0, 0, 0, 0); // Set to 00:00:00 of 1st day
      
      const displayedMonthEnd = new Date(currentWeekStart.getFullYear(), currentWeekStart.getMonth() + 1, 0);
      displayedMonthEnd.setHours(23, 59, 59, 999); // Set to 23:59:59 of last day
      
      // Calculate week stats - only for currently displayed week (Monday 00:00 to Sunday 23:59:59)
     const weekStats = {
        DAR: { WIFI: 0, DVR: 0, NVR: 0 },
        AMR: { WIFI: 0, DVR: 0, NVR: 0 },
        DOM: { WIFI: 0, DVR: 0, NVR: 0 }
      };

      const monthStats = {
        DAR: { WIFI: 0, DVR: 0, NVR: 0 },
        AMR: { WIFI: 0, DVR: 0, NVR: 0 },
        DOM: { WIFI: 0, DVR: 0, NVR: 0 }
      };
      
      allInstalls.forEach(install => {
        if (!install.date || !install.owner) return;
        
        const installDate = new Date(install.date);
        installDate.setHours(12, 0, 0, 0); // Set to noon to avoid timezone issues
        
        // Week stats - for currently displayed week (Monday 00:00 to Sunday 23:59:59)
        if (installDate >= currentWeekStart && installDate <= currentWeekEnd) {
          if (weekStats[install.owner] && weekStats[install.owner][install.type] !== undefined) {
            weekStats[install.owner][install.type]++;
          }
        }
          
        
        // Month stats - for the month of the currently displayed week (1st day 00:00 to last day 23:59:59)
        if (installDate >= displayedMonthStart && installDate <= displayedMonthEnd) {
           
          if (monthStats[install.owner] && monthStats[install.owner][install.type] !== undefined) {
            
            monthStats[install.owner][install.type]++;
            
           
          }
        }
      });
      
      // Update the display
      const weekDAR = document.getElementById('weekDAR');
      const weekAMR = document.getElementById('weekAMR');
      const weekDOM = document.getElementById('weekDOM');
      const monthDAR = document.getElementById('monthDAR');
      const monthAMR = document.getElementById('monthAMR');
      const monthDOM = document.getElementById('monthDOM');
      
      // Calculate totals for each owner
      const weekDARTotal = weekStats.DAR.WIFI + weekStats.DAR.DVR + weekStats.DAR.NVR;
      const weekAMRTotal = weekStats.AMR.WIFI + weekStats.AMR.DVR + weekStats.AMR.NVR;
      const weekDOMTotal = weekStats.DOM.WIFI + weekStats.DOM.DVR + weekStats.DOM.NVR;
      const monthDARTotal = monthStats.DAR.WIFI + monthStats.DAR.DVR + monthStats.DAR.NVR;
      const monthAMRTotal = monthStats.AMR.WIFI + monthStats.AMR.DVR + monthStats.AMR.NVR;
      const monthDOMTotal = monthStats.DOM.WIFI + monthStats.DOM.DVR + monthStats.DOM.NVR;
      
      // Update week stats with animation class if count >= 5
      weekDAR.innerHTML = `
        <div class="number">${weekStats.DAR.WIFI}</div>WiFi
        <div class="number">${weekStats.DAR.DVR}</div>DVR
        <div class="number">${weekStats.DAR.NVR}</div>NVR
        <br>DAR`;
      if (weekDARTotal >= 5) {
        console.log('Adding high-count class to weekDAR, total:', weekDARTotal);
        weekDAR.classList.add('high-count');
      } else {
        console.log('Removing high-count class from weekDAR, total:', weekDARTotal);
        weekDAR.classList.remove('high-count');
      }

      weekAMR.innerHTML = `
        <div class="number">${weekStats.AMR.WIFI}</div>WiFi
        <div class="number">${weekStats.AMR.DVR}</div>DVR
        <div class="number">${weekStats.AMR.NVR}</div>NVR
        <br>AMR`;
      if (weekAMRTotal >= 5) {
        console.log('Adding high-count class to weekAMR, total:', weekAMRTotal);
        weekAMR.classList.add('high-count');
      } else {
        console.log('Removing high-count class from weekAMR, total:', weekAMRTotal);
        weekAMR.classList.remove('high-count');
      }

      weekDOM.innerHTML = `
        <div class="number">${weekStats.DOM.WIFI}</div>WiFi
        <div class="number">${weekStats.DOM.DVR}</div>DVR
        <div class="number">${weekStats.DOM.NVR}</div>NVR
        <br>DOM`;
      if (weekDOMTotal >= 5) {
        console.log('Adding high-count class to weekDOM, total:', weekDOMTotal);
        weekDOM.classList.add('high-count');
      } else {
        console.log('Removing high-count class from weekDOM, total:', weekDOMTotal);
        weekDOM.classList.remove('high-count');
      }

      // Month stats with animation class if count >= 5
      monthDAR.innerHTML = `
        <div class="number">${monthStats.DAR.WIFI}</div>WiFi
        <div class="number">${monthStats.DAR.DVR}</div>DVR
        <div class="number">${monthStats.DAR.NVR}</div>NVR
        <br>DAR`;
      if (monthDARTotal >= 5) {
        console.log('Adding high-count class to monthDAR, total:', monthDARTotal);
        monthDAR.classList.add('high-count');
      } else {
        console.log('Removing high-count class from monthDAR, total:', monthDARTotal);
        monthDAR.classList.remove('high-count');
      }

      monthAMR.innerHTML = `
        <div class="number">${monthStats.AMR.WIFI}</div>WiFi
        <div class="number">${monthStats.AMR.DVR}</div>DVR
        <div class="number">${monthStats.AMR.NVR}</div>NVR
        <br>AMR`;
      if (monthAMRTotal >= 5) {
        console.log('Adding high-count class to monthAMR, total:', monthAMRTotal);
        monthAMR.classList.add('high-count');
      } else {
        console.log('Removing high-count class from monthAMR, total:', monthAMRTotal);
        monthAMR.classList.remove('high-count');
      }

      monthDOM.innerHTML = `
        <div class="number">${monthStats.DOM.WIFI}</div>WiFi
        <div class="number">${monthStats.DOM.DVR}</div>DVR
        <div class="number">${monthStats.DOM.NVR}</div>NVR
        <br>DOM`;
      if (monthDOMTotal >= 5) {
        console.log('Adding high-count class to monthDOM, total:', monthDOMTotal);
        monthDOM.classList.add('high-count');
      } else {
        console.log('Removing high-count class from monthDOM, total:', monthDOMTotal);
        monthDOM.classList.remove('high-count');
      }

      const weekTotal =
        weekStats.DAR.WIFI + weekStats.DAR.DVR+ weekStats.DAR.NVR +
        weekStats.AMR.WIFI + weekStats.AMR.DVR + weekStats.AMR.NVR +
        weekStats.DOM.WIFI + weekStats.DOM.DVR + weekStats.DOM.NVR;
        const today1 = new Date();
        let dayOfWeek = today.getDay(); // Sunday = 0, Monday = 1, ..., Saturday = 6

        // Convert so Monday = 1 → 1,2,3,4,5,6,7 (Mon to Sun)
        dayOfWeek = dayOfWeek === 0 ? 7 : dayOfWeek;

        // Number of days passed (including today)
        const daysPassed = dayOfWeek;
        const totalDaysInWeek = 7;
        const projectedWeek = Math.round((weekTotal / daysPassed) * totalDaysInWeek);
              document.getElementById("thisWeek").textContent =
          `${weekTotal} (~${projectedWeek} / w)`;
              //const todayNumber = new Date();
        const dayNumber = today.getDate();

        const totalSoFar =
          monthStats.DAR.WIFI + monthStats.DAR.DVR + monthStats.DAR.NVR +
          monthStats.AMR.WIFI + monthStats.AMR.DVR + monthStats.AMR.NVR +
          monthStats.DOM.WIFI + monthStats.DOM.DVR + monthStats.DOM.NVR;

        const currentDay = today1.getDate(); // day of the month
        const totalDays = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate(); // days in this month

        const projected = Math.round((totalSoFar / currentDay) * totalDays);
            document.getElementById("thisMonth").textContent =
          `${totalSoFar} (~ ${projected} / m)`;   

       // cache for popup
        lastWeekStats  = JSON.parse(JSON.stringify(weekStats));
        lastMonthStats = JSON.parse(JSON.stringify(monthStats));

        // total weekly installs (sum of all owners/types)
        lastWeekTotalCount =
          weekStats.DAR.WIFI + weekStats.DAR.DVR + weekStats.DAR.NVR +
          weekStats.AMR.WIFI + weekStats.AMR.DVR + weekStats.AMR.NVR +
          weekStats.DOM.WIFI + weekStats.DOM.DVR + weekStats.DOM.NVR;

        // total monthly installs (sum of all owners/types)
        lastMonthTotalCount =
          monthStats.DAR.WIFI + monthStats.DAR.DVR + monthStats.DAR.NVR +
          monthStats.AMR.WIFI + monthStats.AMR.DVR + monthStats.AMR.NVR +
          monthStats.DOM.WIFI + monthStats.DOM.DVR + monthStats.DOM.NVR;

        // total number of cameras installed this month
        lastMonthTotalCams = allInstalls.reduce((sum, install) => {
          if (!install.date) return sum;
          const d = new Date(install.date); d.setHours(12,0,0,0);
          if (d >= displayedMonthStart && d <= displayedMonthEnd) {
            const c = parseInt(install.cams, 10);
            return sum + (isNaN(c) ? 0 : c);
          }
          return sum;
        }, 0);

       // ----- Technician stats -----
      const normTech = (t) => (t && String(t).trim()) || 'Unassigned';

      // Maps: tech -> { installs, cams }
      const weekTechCounts = {};
      const monthTechCounts = {};

      // For 2-per-day metric this month: tech -> (date -> countOnThatDate)
      const monthTechDayCounts = {}; // { tech: { 'YYYY-MM-DD': n } }

      allInstalls.forEach(inst => {
        if (!inst || !inst.date) return;
        const tech = normTech(inst.technician);
        const d = new Date(inst.date); d.setHours(12,0,0,0);
        const cams = parseInt(inst.cams, 10) || 0;

        // Weekly window
        if (d >= currentWeekStart && d <= currentWeekEnd) {
          if (!weekTechCounts[tech]) weekTechCounts[tech] = { installs:0, cams:0 };
          weekTechCounts[tech].installs++;
          weekTechCounts[tech].cams += cams;
        }

        // Monthly window
        if (d >= displayedMonthStart && d <= displayedMonthEnd) {
          if (!monthTechCounts[tech]) monthTechCounts[tech] = { installs:0, cams:0 };
          monthTechCounts[tech].installs++;
          monthTechCounts[tech].cams += cams;

          const iso = inst.date; // already YYYY-MM-DD
          if (!monthTechDayCounts[tech]) monthTechDayCounts[tech] = {};
          monthTechDayCounts[tech][iso] = (monthTechDayCounts[tech][iso] || 0) + 1;
        }
      });

      // Compute "exactly two installs in a single day" counts per tech for the month
      const monthTwoPerDay = {};
      Object.entries(monthTechDayCounts).forEach(([tech, byDate]) => {
        let daysWithTwo = 0;
        Object.values(byDate).forEach(n => { if (n === 2) daysWithTwo++; });
        if (daysWithTwo > 0) monthTwoPerDay[tech] = daysWithTwo;
      });

      // Build per-tech list of dates with exactly 2 installs (for the month)
      let lastTechTwoPerDayDates = []; // [{ tech, dates: ['YYYY-MM-DD', ...] }]
      Object.entries(monthTechDayCounts).forEach(([tech, byDate]) => {
        const dates = Object.entries(byDate)
          .filter(([, n]) => n === 2)
          .map(([iso]) => iso)
          .sort(); // chronological
        if (dates.length) lastTechTwoPerDayDates.push({ tech, dates });
      });
      // sort techs by number of such days desc
      lastTechTwoPerDayDates.sort((a, b) => b.dates.length - a.dates.length);

      // expose globally if not already
      window.lastTechTwoPerDayDates = lastTechTwoPerDayDates;

      // Sort helpers
      const sortDesc = (a, b) => b.installs - a.installs;
      const sortDescTwo = (a, b) => b.daysWithTwo - a.daysWithTwo;

      // Save sorted arrays for the modal
      lastTechWeek = Object.entries(weekTechCounts).map(([tech, v]) => ({
        tech,
        count: v.installs,
        cams: v.cams
      })).sort(sortDesc);

      lastTechMonth = Object.entries(monthTechCounts).map(([tech, v]) => ({
        tech,
        count: v.installs,
        cams: v.cams
      })).sort(sortDesc);

      lastTechTwoPerDay = Object.entries(monthTwoPerDay).map(([tech, daysWithTwo]) => ({
        tech,
        daysWithTwo
      })).sort(sortDescTwo);

        // ensure stats button exists (right side)
        ensureOwnerStatsButton();
    }
    
    // Modify the render function to also update stats
    function render() {
      fetch('installs_api.php')
        .then(res => res.json())
        .then(data => {
          allInstalls = data;
          buildCalendar();
          updateStats(); // Add this line to update stats
        });
    }

let locationAutocompleteInitialized = false;

function initLocationAutocomplete() {
  if (locationAutocompleteInitialized) return; // Prevent re-initialization

  const input = document.getElementById('location');

  const options = {
    types: ['geocode'],
    componentRestrictions: { country: 'in' },
    fields: ['formatted_address'],
    bounds: new google.maps.LatLngBounds(
      new google.maps.LatLng(12.80, 77.40),
      new google.maps.LatLng(13.20, 77.80)
    ),
    strictBounds: false
  };

  const autocomplete = new google.maps.places.Autocomplete(input, options);

  autocomplete.addListener('place_changed', () => {
    const place = autocomplete.getPlace();
    if (place && place.formatted_address) {
      input.value = place.formatted_address;
    }
  });

  locationAutocompleteInitialized = true;
}


    window.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const id = params.get("id");

  if (!id) return;

  // Fetch from DB using API
  fetch("installs_api.php")
    .then(res => res.json())
    .then(data => {
      const exists = data.find(r => r.id === id);

      if (exists) {
        alert(`Record already exists for date: ${exists.date}`);
      } else {
        const formData = {
          id: params.get("id") || '',
          name: params.get("name") || '',
          cams: params.get("cams") || '',
          bullets: '', // optional fallback
          dome: '',
          hdd: (params.get("hdd") || '').replace(/\s+/g, ''),
          monitor: '',
          type: params.get("type") || '',
          location: params.get("location") || '',
          time: '',
          date: params.get("date") || '',
          owner: params.get("owner") || '',
          technician: '',
          helper: '',
          resolution: params.get("resolution") || '',
          map: params.get("map") || ''
        };
        console.log(formData.hdd, "@@@@");
        openForm(formData); // This is your existing function
      }
    });
});
function redirectToOrderItems() {
  const fields = [
    'id', 'name', 'cams', 'bullets', 'dome', 'hdd',
    'monitor', 'type', 'location', 'time', 'date',
    'owner', 'technician', 'helper', 'resolution', 'map', 'rack', 'notes'
  ];

  const params = new URLSearchParams();

  fields.forEach(field => {
    const input = document.getElementById(field);
    if (input && input.value) {
      params.append(field, input.value);
      
    }
     orderDetails[field] = input.value;
  });
  

  // Redirect to order_items.php with all form values as query string
  const url = `../../admin/order_items_2.php?${params.toString()}`;
  // invoice is now server-rendered by installs.php; no iframe URL needed
  redirectToOrderItems2(url, null);
}
  console.log("🌐 Global orderDetails object:", orderDetails);

async function loadInvoiceIntoContainer() {
  const params = new URLSearchParams();
  // gather current form values to pass to PHP
  const fields = [
    'id', 'name', 'cams', 'bullets', 'dome', 'hdd',
    'monitor', 'type', 'location', 'time', 'date',
    'owner', 'technician', 'helper', 'resolution', 'map', 'rack', 'notes'
  ];
  fields.forEach(field => {
    const input = document.getElementById(field);
    if (input && input.value) params.append(field, input.value);
  });
  params.append('render_invoice', '1');

  const container1 = document.getElementById('invoice-content');
  if (container1) container1.innerHTML = 'Loading invoice...';

  try {
    const res = await fetch(`installs.php?${params.toString()}`, { cache: 'no-store' });
    const html = await res.text();
    if (container1) container1.innerHTML = html;
  } catch (e) {
    if (container1) container1.innerHTML = 'Failed to load invoice.';
    console.error('Invoice load error', e);
  } finally {
    // Initialize Extras UI and load from DB
    initExtrasModule();
    await loadExtrasFromDB();
    // Ensure PDF download works for embedded invoice
    await ensureInvoicePdfSupport();
  }
}

// Slide-in overlay builder for extras-card
function setupExtrasOverlay(){
  const card = document.querySelector('.extras-card');
  if (!card || card.dataset.overlayReady === '1') return;
  card.dataset.overlayReady = '1';
  const container = document.getElementById('install-popup') || document.getElementById('order-popup') || document.body;

  // Create overlay shell and backdrop
  const overlay = document.createElement('div');
  overlay.id = 'extras-overlay';
  overlay.style.cssText = [
    'position:absolute',
    'top:0',
    'left:0',
    'height:100%',
    'width:33.333%',
    'min-width:360px',
    'max-width:520px',
    'background:#fff',
    'box-shadow:0 10px 30px rgba(0,0,0,0.35)',
    'transform:translateX(-100%)',
    'transition:transform .28s ease',
    'z-index:2147483645',
    'display:none',
    'flex-direction:column'
  ].join(';');

  const backdrop = document.createElement('div');
  backdrop.id = 'extras-backdrop';
  backdrop.style.cssText = [
    'position:absolute',
    'inset:0',
    'background:rgba(0,0,0,0.35)',
    'z-index:2147483644',
    'display:none'
  ].join(';');

  // Style the card to fit overlay
  card.style.margin = '0';
  card.style.border = '0';
  card.style.borderRadius = '0';
  card.style.height = '100%';
  card.style.overflow = 'auto';
  card.style.boxShadow = 'none';
  card.style.padding = '20px';

  // Create and add payment details section to overlay
  const paymentSection = document.createElement('div');
  paymentSection.className = 'payment-details-overlay';
  paymentSection.innerHTML = `
    <h4>Payment Details</h4>
    <form id="overlay-payment-form">
      <div style="margin-bottom: 12px;">
        <label style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
          <input type="checkbox" id="overlay-fully-paid" style="margin: 0;"> Mark as fully paid
        </label>
      </div>
      <div style="margin-bottom: 12px;">
        <label style="display: block; margin-bottom: 4px; font-size: 14px; font-weight: 500;">Amount Paid (₹):</label>
        <input type="number" id="overlay-amount-paid" placeholder="Enter amount" step="0.01" min="0" style="width: 100%; padding: 6px 8px; border: 1px solid #ced4da; border-radius: 4px; font-size: 12px;">
      </div>
      <button type="submit" style="background: #007cba; color: white; padding: 8px; border: none; border-radius: 4px; cursor: pointer; width: 40px; height: 32px; display: flex; align-items: center; justify-content: center;">
        <i class="fas fa-save"></i>
      </button>
    </form>
  `;

  // Create and add profit details section to overlay
  const profitSection = document.createElement('div');
  profitSection.className = 'profit-details-overlay';
  profitSection.innerHTML = `
    <h4>Profit Details</h4>
    <div id="results"></div>
    <div id="profit-content" style="font-size: 14px; line-height: 1.5;">
      <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
        <span>Final Profit:</span>
        <span id="final-profit-value" style="font-weight: 600;">₹0</span>
      </div>
      <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
        <span>Limited Profit:</span>
        <span id="limited-profit-value" style="font-weight: 600;">₹0</span>
      </div>
    </div>
  `;

  // Insert sections at the beginning of the card
  card.insertBefore(profitSection, card.firstChild);
  card.insertBefore(paymentSection, card.firstChild);

  // Add organized styling to extras card content
  const extrasForm = card.querySelector('.extras-form');
  if (extrasForm) {
    extrasForm.style.cssText = [
      'margin-top:20px',
      'display:flex',
      'flex-direction:column',
      'gap:20px'
    ].join(';');
    
    // Style individual form sections
    const formSections = extrasForm.querySelectorAll('div[style*="border:1px solid"]');
    formSections.forEach(section => {
      section.style.cssText = [
        'border:1px solid #e0e0e0',
        'border-radius:12px',
        'padding:16px',
        'background:#f8f9fa',
        'box-shadow:0 2px 4px rgba(0,0,0,0.05)'
      ].join(';');
      
      // Style section headers
      const header = section.querySelector('div[style*="font-weight:600"]');
      if (header) {
        header.style.cssText = [
          'font-weight:600',
          'margin-bottom:12px',
          'color:#495057',
          'font-size:16px',
          'border-bottom:2px solid #dee2e6',
          'padding-bottom:8px'
        ].join(';');
      }
      
      // Style inputs in sections
      const inputs = section.querySelectorAll('input');
      inputs.forEach(input => {
        input.style.cssText = [
          'width:100%',
          'padding:8px 12px',
          'margin-top:4px',
          'border:1px solid #ced4da',
          'border-radius:6px',
          'font-size:14px',
          'transition:border-color 0.3s ease'
        ].join(';');
      });
    });
  }

  // Mount overlay
  overlay.appendChild(card);
  container.appendChild(backdrop);
  container.appendChild(overlay);

  // Global open/close helpers
  window.openExtrasOverlay = function(){
    overlay.style.display = 'flex';
    overlay.style.transform = 'translateX(0)';
    backdrop.style.display = '';
    const btn = document.getElementById('extras-open-btn');
    if (btn) {
      btn.innerHTML = '<i class="fas fa-times"></i>';
      btn.style.background = '#dc3545';
      btn.style.zIndex = '2147483646'; // Higher than overlay
    }
  };
  window.closeExtrasOverlay = function(){
    overlay.style.transform = 'translateX(-100%)';
    backdrop.style.display = 'none';
    // Hide overlay after animation
    setTimeout(() => {
      overlay.style.display = 'none';
    }, 280);
    const btn = document.getElementById('extras-open-btn');
    if (btn) {
      btn.innerHTML = '<i class="fas fa-plus"></i>';
      btn.style.background = '#28a745';
      btn.style.zIndex = '2147483643';
    }
  };
  window.toggleExtrasOverlay = function(){
    const isOpen = overlay.style.transform === 'translateX(0px)' && overlay.style.display === 'flex';
    if (isOpen) {
      window.closeExtrasOverlay();
    } else {
      window.openExtrasOverlay();
    }
  };

  // Backdrop and ESC to close
  backdrop.addEventListener('click', () => window.closeExtrasOverlay());
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') window.closeExtrasOverlay(); });

  // Add a persistent open button
  if (!document.getElementById('extras-open-btn')) {
    const openBtn = document.createElement('button');
    openBtn.id = 'extras-open-btn';
    openBtn.type = 'button';
    openBtn.innerHTML = '<i class="fas fa-plus"></i>';
    openBtn.style.cssText = [
      'position:absolute',
      'right:18px',
      'bottom:18px',
      'width:50px',
      'height:50px',
      'border-radius:50%',
      'border:none',
      'background:#28a745',
      'color:#fff',
      'cursor:pointer',
      'z-index:2147483643',
      'box-shadow:0 4px 12px rgba(0,0,0,0.2)',
      'display:flex',
      'align-items:center',
      'justify-content:center',
      'font-size:18px',
      'transition:all 0.3s ease'
    ].join(';');
    openBtn.addEventListener('click', () => window.toggleExtrasOverlay());
    container.appendChild(openBtn);
  }
}

// Load html2pdf library if needed and expose window.downloadPDF targeting #invoice within #invoice-content
async function ensureInvoicePdfSupport(){
  function installDownloadPDF(){
    window.downloadPDF = function(){
      const host = document.getElementById('invoice-content');
      const el = (host && host.querySelector('#invoice')) || document.getElementById('invoice');
      if (!el) { alert('Invoice content not loaded'); return; }
      const id = (document.getElementById('id')||{}).value || 'invoice';
      try {
        html2pdf().from(el).save(`Smartronic_Invoice_${id}.pdf`);
      } catch (err) {
        console.error('html2pdf failed', err);
        alert('Failed to generate PDF');
      }
    };
  }
  if (window.html2pdf) { installDownloadPDF(); return; }
  await new Promise((resolve) => {
    const s = document.createElement('script');
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
    s.onload = () => { installDownloadPDF(); resolve(); };
    s.onerror = () => { console.warn('Failed to load html2pdf'); resolve(); };
    document.head.appendChild(s);
  });
}

async function redirectToOrderItems2(url, _urlInvoice) {
  const popup = document.getElementById('order-popup');
  popup.style.display = 'flex';
  switchTab('material');
}

async function redirecktToOrderItems(url) {
  const popup = document.getElementById('order-popup');
  popup.style.display = 'flex';
  switchTab('requirement');
}

function closeOrderPopup() {
  const popup = document.getElementById('order-popup');
  popup.style.display = 'none';
}

function switchTab(tabName) {
  const tabs = ['requirement', 'material', 'invoice'];
  tabs.forEach(name => {
    document.getElementById('tab-' + name).style.display = (name === tabName) ? 'block' : 'none';
  });
  if (tabName === 'invoice') {
    loadInvoiceIntoContainer();
  }
  if (tabName === 'material') {
    loadMaterialIntoContainer();
  }
}

async function loadMaterialIntoContainer(){
  // If we've already loaded and initialized scripts once, just re-fill from form
  if (window.__materialLoaded) {
    try { fillMaterialFromForm(); } catch(_) {}
    return;
  }

  const params = new URLSearchParams();
  const fields = [
    'id', 'name', 'cams', 'bullets', 'dome', 'hdd',
    'monitor', 'type', 'location', 'time', 'date',
    'owner', 'technician', 'helper', 'resolution', 'map', 'rack', 'notes'
  ];
  fields.forEach(field => {
    const input = document.getElementById(field);
    if (input && input.value) params.append(field, input.value);
  });
  params.append('render_material', '1');

  const host = document.getElementById('material-content');
  if (host) host.textContent = 'Loading material...';
  try {
    const res = await fetch(`installs.php?${params.toString()}`, { cache: 'no-store' });
    const html = await res.text();
    if (!host) return;
    // Inject HTML
    host.innerHTML = html;

    // Execute scripts in the injected HTML (both external and inline) ONLY ONCE
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    const scripts = Array.from(tmp.querySelectorAll('script'));

    // Load external scripts sequentially
    for (const s of scripts.filter(sc => sc.src)) {
      await new Promise((resolve) => {
        const el = document.createElement('script');
        el.src = s.src;
        el.onload = resolve;
        el.onerror = resolve; // don't block on errors
        document.head.appendChild(el);
      });
    }
    // Run inline scripts in order
    for (const s of scripts.filter(sc => !sc.src)) {
      const el = document.createElement('script');
      el.type = s.type || 'text/javascript';
      el.text = s.textContent || '';
      host.appendChild(el);
    }

    // Mark as loaded to avoid re-executing scripts (prevents const redeclaration errors)
    window.__materialLoaded = true;

    // Now populate fields from current install form values and build the tables
    try { fillMaterialFromForm(); } catch(_) {}
  } catch (e) {
    if (host) host.textContent = 'Failed to load material.';
  }
}

// Populate material form using current installForm fields (no reliance on URL params)
function fillMaterialFromForm(){
  const getVal = (id) => (document.getElementById(id)||{}).value || '';
  const name = getVal('name');
  const idno = getVal('id');
  const location = getVal('location');
  const resolution = getVal('resolution'); // "2 MP" or "5 MP"
  const bullets = parseInt(getVal('bullets')||'0', 10) || 0;
  const dome = parseInt(getVal('dome')||'0', 10) || 0;
  const monitor = getVal('monitor');
  const rack = getVal('rack');
  const hdd = (getVal('hdd')||'').replace(/\s+/g,''); // e.g., 1TB, 500GB

  const selStartsWith = (sel, text) => {
    if (!sel || !text) return;
    const t = text.trim();
    for (let i=0;i<sel.options.length;i++){
      const optText = (sel.options[i].text || sel.options[i].value || '').trim();
      if (optText.startsWith(t)) { sel.selectedIndex = i; break; }
    }
  };
  const selectByHdd = (sel, valNoSpace) => {
    if (!sel || !valNoSpace) return;
    for (let i=0;i<sel.options.length;i++){
      const optText = (sel.options[i].text || '').replace(/\s+/g,'');
      if (optText.toUpperCase().startsWith(valNoSpace.toUpperCase())) { sel.selectedIndex = i; break; }
    }
  };

  // Name & ID
  const uname = document.getElementById('user-name');
  if (uname) uname.value = name;
  const uid = document.getElementById('user-id');
  if (uid) uid.value = idno ? `${idno} | ${location}` : `${location}`;

  // Resolution selects and quantities
  const bulletSel = document.getElementById('resolution-bullet');
  const domeSel = document.getElementById('resolution-dome');
  selStartsWith(bulletSel, resolution);
  selStartsWith(domeSel, resolution);
  const bulletQty = document.getElementById('resolution-bullet-qty');
  const domeQty = document.getElementById('resolution-dome-qty');
  if (bulletQty) bulletQty.value = bullets;
  if (domeQty) domeQty.value = dome;

  // Monitor
  const monitorSel = document.getElementById('monitor');
  const monitorQty = document.getElementById('monitor-qty');
  selStartsWith(monitorSel, monitor);
  if (monitorQty) monitorQty.value = monitor ? 1 : 0;

  // Rack
  const rackQty = document.getElementById('rack-qty');
  if (rackQty) rackQty.value = rack ? 1 : 0;

  // HDD
  const hddSel = document.getElementById('hdd');
  selectByHdd(hddSel, hdd);
  const hddQty = document.getElementById('hdd-qty');
  if (hddQty) hddQty.value = 1;

  // Trigger dependent recalculations and table build
  try { if (typeof updateDependentFields === 'function') updateDependentFields(); } catch(_) {}
  try { if (typeof generateTable === 'function') generateTable({ preventDefault: () => {} }); } catch(_) {}
}

// ----- Extras module -----
function getExtrasFormRefs(){
  return {
    cableRate: document.getElementById('ex_cable_rate'),
    cableLen: document.getElementById('ex_cable_len'),
    cableTotal: document.getElementById('ex_cable_total'),
    rackSize: document.getElementById('ex_rack_size'),
    rackRate: document.getElementById('ex_rack_rate'),
    monitorSize: document.getElementById('ex_monitor_size'),
    monitorRate: document.getElementById('ex_monitor_rate'),
    routerSize: document.getElementById('ex_router_size'),
    routerRate: document.getElementById('ex_router_rate'),
    customLabel: document.getElementById('ex_custom_label'),
    customItem: document.getElementById('ex_custom_item'),
    customTotal: document.getElementById('ex_custom_total'),
    saveBtn: document.getElementById('ex-save-btn'),
    tableBody: (document.getElementById('extras-table')||{}).tBodies ? document.getElementById('extras-table').tBodies[0] : null,
    totalCell: document.getElementById('extras-total-cell')
  };
}

function initExtrasModule(){
  const refs = getExtrasFormRefs();
  if (!refs || !refs.saveBtn) return; // not on invoice tab yet
  // Build slide-in overlay for the entire extras-card
  setupExtrasOverlay();

  // auto compute cable total
  const recompute = () => {
    const r = parseFloat(refs.cableRate?.value || '0');
    const l = parseFloat(refs.cableLen?.value || '0');
    if (refs.cableTotal && !isNaN(r) && !isNaN(l)) {
      refs.cableTotal.value = (r * l).toFixed(2);
    }
  };
  refs.cableRate?.addEventListener('input', recompute);
  refs.cableLen?.addEventListener('input', recompute);

  refs.saveBtn.onclick = async () => {
    const payload = collectExtrasPayload();
    const orderId = (document.getElementById('id')||{}).value || '';
    if (!orderId) { alert('Order ID is required to save extras'); return; }
    try {
      const res = await fetch('installs.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ extrasOnly: true, id: orderId, extras: JSON.stringify(payload) })
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message||'Failed');
      renderExtrasTable(payload);
      applyExtrasToInvoiceTotals(sumExtras(payload));
      // auto-hide overlay on save
      if (typeof window.closeExtrasOverlay === 'function') window.closeExtrasOverlay();
    } catch (e) {
      console.error('Save extras failed', e);
      alert('Failed to save extras');
    }
  };
}

function collectExtrasPayload(){
  const r = getExtrasFormRefs();
  const num = v => { const n = parseFloat(v); return isNaN(n) ? 0 : n; };
  const payload = {
    cable: {
      rate: num(r.cableRate?.value),
      length: num(r.cableLen?.value),
      total: num(r.cableTotal?.value)
    },
    rack: { size: (r.rackSize?.value||'').trim(), rate: num(r.rackRate?.value) },
    monitor: { size: (r.monitorSize?.value||'').trim(), rate: num(r.monitorRate?.value) },
    router: { size: (r.routerSize?.value||'').trim(), rate: num(r.routerRate?.value) },
    custom: { label: (r.customLabel?.value||'').trim(), item: (r.customItem?.value||'').trim(), total: num(r.customTotal?.value) }
  };
  // ensure cable total is consistent if empty
  if (!payload.cable.total) payload.cable.total = +(payload.cable.rate * payload.cable.length).toFixed(2);
  return payload;
}

async function loadExtrasFromDB(){
  const orderId = (document.getElementById('id')||{}).value || '';
  if (!orderId) return;
  try {
    const res = await fetch(`installs.php?get_extras=1&id=${encodeURIComponent(orderId)}`, { cache: 'no-store' });
    const data = await res.json();
    let payload = null;
    if (data && data.success && typeof data.extras === 'string' && data.extras.trim()) {
      try { payload = JSON.parse(data.extras); } catch(_) { payload = null; }
    }
    if (payload) {
      fillExtrasForm(payload);
      renderExtrasTable(payload);
      applyExtrasToInvoiceTotals(sumExtras(payload));
    } else {
      // clear
      renderExtrasTable({});
      applyExtrasToInvoiceTotals(0);
    }
  } catch(e){ console.warn('Load extras failed', e); }
}

function fillExtrasForm(p){
  const r = getExtrasFormRefs();
  if (!r) return;
  if (p.cable){
    if (r.cableRate) r.cableRate.value = p.cable.rate ?? '';
    if (r.cableLen) r.cableLen.value = p.cable.length ?? '';
    if (r.cableTotal) r.cableTotal.value = p.cable.total ?? '';
  }
  if (p.rack){ if (r.rackSize) r.rackSize.value = p.rack.size ?? ''; if (r.rackRate) r.rackRate.value = p.rack.rate ?? ''; }
  if (p.monitor){ if (r.monitorSize) r.monitorSize.value = p.monitor.size ?? ''; if (r.monitorRate) r.monitorRate.value = p.monitor.rate ?? ''; }
  if (p.router){ if (r.routerSize) r.routerSize.value = p.router.size ?? ''; if (r.routerRate) r.routerRate.value = p.router.rate ?? ''; }
  if (p.custom){ if (r.customLabel) r.customLabel.value = p.custom.label ?? ''; if (r.customItem) r.customItem.value = p.custom.item ?? ''; if (r.customTotal) r.customTotal.value = p.custom.total ?? ''; }
}

function renderExtrasTable(p){
  const r = getExtrasFormRefs();
  if (!r.tableBody) return;
  const rows = [];
  const fmt = n => Number(n||0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
  // Cable
  if (p.cable && (p.cable.total||0) > 0){
    rows.push({ item:'Cable 3+1', details:`Rate ${fmt(p.cable.rate)} x Len ${fmt(p.cable.length)}`, amount: p.cable.total });
  }
  if (p.rack && (p.rack.rate||0) > 0){ rows.push({ item:'Rack', details:`Size ${p.rack.size||''}`, amount: p.rack.rate }); }
  if (p.monitor && (p.monitor.rate||0) > 0){ rows.push({ item:'Monitor', details:`Size ${p.monitor.size||''}`, amount: p.monitor.rate }); }
  if (p.router && (p.router.rate||0) > 0){ rows.push({ item:'Router', details:`Size ${p.router.size||''}`, amount: p.router.rate }); }
  if (p.custom && (p.custom.total||0) > 0){
    const label = (p.custom.label||'Custom').trim();
    const item = (p.custom.item||'').trim();
    rows.push({ item: label, details: item, amount: p.custom.total });
  }

  r.tableBody.innerHTML = rows.map(row => `
    <tr>
      <td style="padding:6px;border:1px solid #eee;">${row.item}</td>
      <td style="padding:6px;border:1px solid #eee;">${row.details}</td>
      <td style="padding:6px;border:1px solid #eee;text-align:right;">${fmt(row.amount)}</td>
    </tr>
  `).join('');

  const total = rows.reduce((s, x) => s + (Number(x.amount)||0), 0);
  if (r.totalCell) r.totalCell.textContent = fmt(total);
  // also mirror inside invoice content below "Package Details"
  renderExtrasTableInInvoice(rows, total);
}

function sumExtras(p){
  let total = 0;
  if (p && p.cable) total += Number(p.cable.total)||0;
  if (p && p.rack) total += Number(p.rack.rate)||0;
  if (p && p.monitor) total += Number(p.monitor.rate)||0;
  if (p && p.router) total += Number(p.router.rate)||0;
  if (p && p.custom) total += Number(p.custom.total)||0;
  return total;
}

function renderExtrasTableInInvoice(rows, total){
  const host = document.getElementById('invoice-content');
  if (!host) return;
  // remove existing section if any
  const existing = host.querySelector('#extras-invoice-section');
  if (existing) existing.remove();

  const fmt = n => Number(n||0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
  const wrapper = document.createElement('div');
  wrapper.id = 'extras-invoice-section';
  // adopt invoice .items styling
  wrapper.className = 'items';
  wrapper.innerHTML = `
    <h3>Extra Items</h3>
    <table>
      <thead>
        <tr>
          <th style="text-align:left;">Item</th>
          <th style="text-align:left;">Details</th>
          <th style="text-align:right;">Amount</th>
        </tr>
      </thead>
      <tbody>
        ${rows.map(r => `
          <tr>
            <td>${r.item}</td>
            <td>${r.details}</td>
            <td style=\"text-align:right;\">${fmt(r.amount)}</td>
          </tr>
        `).join('')}
      </tbody>
      <tfoot>
        <tr>
          <td colspan=\"2\" style=\"text-align:right;font-weight:600;\">Extras total</td>
          <td style=\"text-align:right;font-weight:600;\">${fmt(total)}</td>
        </tr>
      </tfoot>
    </table>
  `;

  // Place after .items and before .total for consistent invoice flow
  const totalEl = host.querySelector('.total');
  if (totalEl && totalEl.parentNode) {
    totalEl.parentNode.insertBefore(wrapper, totalEl);
  } else {
    // fallback: after last .items or append
    const itemsEls = host.querySelectorAll('.items');
    const lastItems = itemsEls.length ? itemsEls[itemsEls.length - 1] : null;
    if (lastItems) {
      lastItems.insertAdjacentElement('afterend', wrapper);
    } else {
      host.appendChild(wrapper);
    }
  }
}

function applyExtrasToInvoiceTotals(extrasTotal){
  // Try to locate totals inside invoice-content and add extras
  const host = document.getElementById('invoice-content');
  if (!host) return;
  const parseNum = (s) => {
    if (!s) return NaN;
    const m = String(s).replace(/[^0-9.]/g, '');
    return parseFloat(m);
  };
  const fmt = (n) => isNaN(n) ? '' : n.toLocaleString('en-IN', { maximumFractionDigits: 2 });

  const candidates = ['#payable', '#total_amount', '#grand_total', '#total', '#totalAmountPayable', '.total-amount', '.grand-total'];
  candidates.forEach(sel => {
    const el = host.querySelector(sel);
    if (el){
      if (!el.dataset.baseValue) {
        const parsed = parseNum(el.textContent);
        if (!isNaN(parsed)) el.dataset.baseValue = String(parsed);
      }
      const base = parseFloat(el.dataset.baseValue);
      if (!isNaN(base)) el.textContent = fmt(base + (extrasTotal||0));
    }
  });

  const paidCandidates = ['#paid', '#total_paid', '#amount_paid', '.total-paid'];
  paidCandidates.forEach(sel => {
    const el = host.querySelector(sel);
    if (el){
      if (!el.dataset.baseValue) {
        const parsed = parseNum(el.textContent);
        if (!isNaN(parsed)) el.dataset.baseValue = String(parsed);
      }
      const base = parseFloat(el.dataset.baseValue);
      if (!isNaN(base)) el.textContent = fmt(base + (extrasTotal||0));
    }
  });
}
// ----- End Extras module -----

async function sendToWhatsapp(e, ev) {
    if (e && e.preventDefault) e.preventDefault(); 
  const technicianName = ev.technician || "";



  try {
      
    // Load users.json from the same directory
    const res = await fetch('../users.json');
    const users = await res.json();

    // Match technician by name (case insensitive)
    const user = users.find(u => u.name.toLowerCase() === technicianName.toLowerCase());
    const phone = user ? user.phone : "91888431000"; // fallback if not found

 

    const message = `
*Name: ${ev.name || ""}*
*Phone: ${ev.phone || ""}*
*ID*: ${ev.id || ""}\n
----------------------\n
*${ev.type || ""} | Resolution*: ${ev.resolution || ""}*
*Total Cams*: ${ev.total || ""} | B ${ev.bullets || ""} | D ${ev.dome || ""} | *HDD*: ${ev.hdd || ""}
*Monitor*: ${ev.monitor || "No"} | *Rack*: ${ev.rack || "No"}\n
----------------------\n
*Location*: ${ev.location || ""}
*Date*: ${ev.date || ""}
*Time*: ${ev.time || ""}
*Map*: ${ev.map || ""}

`;


    const encodedMsg = encodeURIComponent(message.trim());
    const waUrl = `https://wa.me/${phone}?text=${encodedMsg}`;
    window.open(waUrl, '_blank');
    } catch (error) {
    console.error("Error loading users.json or matching technician:", error);
    alert("Could not send message. Please check technician name or users.json file.");
  }
  }

   


    // ✅ requestData for API call
function getQuoteDetails(installDetails){
  console.log(installDetails)
  const requestData = {
    whatsapp_number: '888888818',
    num_cameras: installDetails.cams,
    dvr_type: installDetails.type,
    hdd_size: installDetails.hdd,
    camera_resolution: installDetails.resolution
  };
  console.log(requestData)
      // Call your PHP API
      fetch('/admin_v2/quote_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
      })
      .then(response => response.json())
      .then(data => {
        console.log("📦 Quote Response:", data);

        // Show the summary in a readable format
        /*📞 WhatsApp: ${data.whatsapp}
        🎥 Camera: ${data.camera_key} x ${data.num_cams} @ ₹${data.camera_unit_price} = ₹${data.camera_total}
        📦 Recorder: ${data.recorder_key} = ₹${data.recorder_price}
        💽 HDD: ${data.hdd_size} = ₹${data.hdd_price}
        🔌 SMPS: ₹${data.smps_price}
        🔌 POE: ₹${data.poe_price}
        🧰 Accessories: ₹${data.accessories}
        💰 Subtotal: ₹${data.subtotal}
        🧾 GST (18%): ₹${data.gst}
        🧩 Installation: ₹${data.install_charge}
        💼 Profit (60%): ₹${data.profit}
        💵 Total Before Discount: ₹${data.before_discount}
        🎁 Final (20% Off): ₹${data.final_total}

        -------------------------
        🧮 Per-Cam Cost: ₹${data.install_per_cam} x ${data.num_cams} = ₹${data.install_cam_cost}
        🔒 Min Install Charge Applied: ₹${data.install_min_cost}
        💼 Extra Profit: ₹${data.extra_profit}
        🧾 Total with GST: ₹${data.with_gst}


        */
        document.getElementById('result').textContent = `
        🎁 Final (20% Off): ₹${data.final_total} \n
        ✅ Final Limited Profit Total: ₹${data.final_limited_profit}
        `.trim();
      })
      .catch(err => {
        console.error("❌ Error calling quote API:", err);
        document.getElementById('result').textContent = "Error fetching quote.";
      });
}
 


