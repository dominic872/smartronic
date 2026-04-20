// Stats and Owner Modal module
// Requires: installs.utils.js (escapeHtml), and relies on global allInstalls, currentMonday
console.log("Stats module loaded");
(function () {
  let lastWeekStats = null;
  let lastMonthStats = null;
  let lastTechWeek = [];
  let lastTechMonth = [];
  let lastTechTwoPerDay = [];
  let lastTechTwoPerDayDates = [];
  let lastWeekTotalCount = 0;
  let lastMonthTotalCount = 0;
  let lastMonthTotalCams = 0;

  let floatingMenuMounted = false;
  let weeksToggleBtn = null; // referenced by toolbar if present
  let toolbar = null;
  let toolbarRight = null;
  const monthLabel = document.getElementById('monthLabel');

  function ensureToolbar() {
    if (toolbar && toolbarRight) return;
    toolbar = document.createElement('div');
    toolbar.id = 'calendarToolbar';
    toolbar.className = 'calendar-toolbar';
    const left = document.createElement('div'); left.className = 'toolbar-left';

    // Find or create toolbar-right
    toolbarRight = document.querySelector('.toolbar-right');
    if (!toolbarRight) {
      toolbarRight = document.createElement('div');
      toolbarRight.className = 'toolbar-right';
    }

    toolbar.appendChild(left);
    toolbar.appendChild(toolbarRight);
    const calendarEl = document.querySelector('.calendar');
    const weekdaysEl = calendarEl ? calendarEl.querySelector('.weekdays') : null;
    if (calendarEl) {
      if (weekdaysEl) calendarEl.insertBefore(toolbar, weekdaysEl);
      else calendarEl.insertBefore(toolbar, calendarEl.firstChild);
    }

    // Only append monthLabel if it exists and is a DOM node
    if (left && monthLabel && monthLabel.nodeType === Node.ELEMENT_NODE) {
      left.appendChild(monthLabel);
    }
  }

  function ensureOwnerStatsButton() {
    if (floatingMenuMounted) return;
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('floating_menu') === '0') return;
    if (!window.SmartFloatingMenu || typeof window.SmartFloatingMenu.mount !== 'function') return;
    const authRole = ((document.cookie.match(/(?:^|; )auth_role=([^;]*)/) || [])[1] || '').trim().toLowerCase();
    const links = [
      {
        label: 'Weeks',
        icon: 'fas fa-calendar-week',
        color: '#0f766e',
        top: 208,
        action: () => {
          if (typeof window.toggleWeeksVisibility === 'function') window.toggleWeeksVisibility();
        }
      },
      {
        label: 'Next Week',
        icon: 'fas fa-chevron-down',
        color: '#111827',
        action: () => {
          if (typeof window.changeWeek === 'function') window.changeWeek(1);
        }
      },
      {
        label: 'Previous Week',
        icon: 'fas fa-chevron-up',
        color: '#111827',
        action: () => {
          if (typeof window.changeWeek === 'function') window.changeWeek(-1);
        }
      },
      {
        label: 'Stats',
        icon: 'fas fa-chart-pie',
        color: '#007bff',
        action: () => {
          if (typeof window.openOwnerStatsModal === 'function') window.openOwnerStatsModal(false);
        }
      },
      {
        label: 'Lead List',
        icon: 'fas fa-users',
        color: '#e11d48',
        url: `${window.location.origin}/admin_v2/smart/lead_list_enhanced.php`
      },
      {
        label: 'Quote Tool',
        icon: 'fas fa-calculator',
        color: '#6f42c1',
        url: `${window.location.origin}/admin_v2/smart/quote.php`
      }
    ];

    if (authRole === 'admin') {
      links.splice(1, 0, {
        label: 'Profit',
        icon: 'fas fa-indian-rupee-sign',
        color: '#15803d',
        action: () => {
          if (typeof window.toggleProfitVisibility === 'function') window.toggleProfitVisibility();
        }
      });
    }

    window.SmartFloatingMenu.mount({
      links,
      baseBottom: 140,
      step: 60
    });
    floatingMenuMounted = true;
  }

  function openOwnerStatsModal(isAutomatic = false) {
    if (isAutomatic) {
      const today = new Date().toLocaleDateString();
      const lastOpen = localStorage.getItem('statsModalLastOpen');
      if (lastOpen === today) {
        return; // Already opened automatically today
      }
      localStorage.setItem('statsModalLastOpen', today);
    }
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
    const body = document.getElementById('stats-body');
    if (!lastWeekStats || !lastMonthStats) body.innerHTML = '<p>No data yet.</p>';
    else body.innerHTML = buildStatsHTML(lastWeekStats, lastMonthStats);
    document.body.classList.add('modal-open');
  }

  function closeOwnerStatsModal() {
    const b = document.getElementById('stats-backdrop');
    const m = document.getElementById('stats-modal');
    if (b) b.remove();
    if (m) m.remove();
    document.body.classList.remove('modal-open');
  }
  function escCloseStats(e) { if (e.key === 'Escape') closeOwnerStatsModal(); }

  function techDatesTable(title, rows) {
    const fmt = (iso) => typeof window.formatDateDisplay === 'function' ? window.formatDateDisplay(iso) : iso;
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
              ${rows.length
        ? rows.map(r => `
                      <tr>
                        <td>${escapeHtml(r.tech)}</td>
                        <td class="num">${r.dates.length}</td>
                        <td><div class="date-chip-row">${r.dates.map(d => `<span class="date-chip">${fmt(d)}</span>`).join('')}</div></td>
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

  function buildOwnerCardHTML(title, data, cardId, offset = 0) {
    // Exclude DAR as requested
    const owners = ['AMR', 'DOM', 'BHA', 'VAR', 'ZOY'];
    const types = ['WIFI', 'DVR', 'NVR'];
    let titleContent = title.toUpperCase();

    // For month card, compute projected totals (DVR+NVR) overall and per owner, only for current month
    if (cardId === 'owner-month-card' && offset === 0) {
      const totalDvrNvr = owners.reduce((acc, o) => acc + (data[o]?.DVR || 0) + (data[o]?.NVR || 0), 0);
      const now = new Date();
      const dayOfMonth = now.getDate();
      const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
      const projectedOverall = Math.round((totalDvrNvr / Math.max(1, dayOfMonth)) * daysInMonth);
      const projectedHtml = ` <span style="position:relative; top: -3px; background: #ff1414; color: #f1f1f1; border-radius: 4px; padding: 2px 5px; font-size: 10px; margin-left: 5px;">${projectedOverall}</span>`;
      titleContent += projectedHtml;
    }

    const gridHtml = owners.map(o => {
      const wifi = (data[o]?.WIFI || 0);
      const dvr = (data[o]?.DVR || 0);
      const nvr = (data[o]?.NVR || 0);
      const now = new Date();
      const dayOfMonth = now.getDate();
      const daysInMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
      const proj = (cardId === 'owner-month-card' && offset === 0) ? Math.round(((dvr + nvr) / Math.max(1, dayOfMonth)) * daysInMonth) : null;
      const ownerNameHtml = proj !== null
        ? `<div class="owner-name">${o} <span style="position:relative; top: -3px; background: #ff1414; color: #f1f1f1; border-radius: 4px; padding: 2px 5px; font-size: 10px; margin-left: 5px;">${proj}</span></div>`
        : `<div class="owner-name">${o}</div>`;
      // Weekly highlight if DVR+NVR >= 5
      const shouldHighlight = (cardId === 'owner-week-card') && ((dvr + nvr) >= 5);
      const highlightClass = shouldHighlight ? ` owner-highlight owner-highlight-${o}` : '';
      return `
          <div class="owner-block${highlightClass}">
            ${ownerNameHtml}
            <div class="owner-row">
              ${types.map(t => `
                <div class="metric">
                  <div class="metric-num">${(t === 'WIFI' ? wifi : (t === 'DVR' ? dvr : nvr))}</div>
                  <div class="metric-label">${t}</div>
                </div>
              `).join('')}
            </div>
          </div>
        `;
    }).join('');

    // Inline style for temporary highlight animation
    const flashStyle = (cardId === 'owner-week-card') ? `
      <style>
        @keyframes cracker {
          0% { background-color: transparent; box-shadow: 0 0 0 0 var(--glow-color); }
          50% { background-color: var(--glow-color-alpha); box-shadow: 0 0 10px 15px rgba(0, 0, 0, 0); }
          100% { background-color: transparent; box-shadow: 0 0 0 0 var(--glow-color); }
        }
        .owner-highlight {
          animation: cracker 1.5s ease-in-out 3;
          border: 1px solid var(--glow-color);
        }
        .owner-highlight-AMR { --glow-color: #ff6b6b; --glow-color-alpha: rgba(255, 107, 107, 0.2); }
        .owner-highlight-DOM { --glow-color: #63e6be; --glow-color-alpha: rgba(99, 230, 190, 0.2); }
        .owner-highlight-BHA { --glow-color: #fcc419; --glow-color-alpha: rgba(252, 196, 25, 0.2); }
        .owner-highlight-VAR { --glow-color: #845ef7; --glow-color-alpha: rgba(132, 94, 247, 0.2); }
        .owner-highlight-ZOY { --glow-color: #ff922b; --glow-color-alpha: rgba(255, 146, 43, 0.2); }
      </style>
    ` : '';

    let navLinks = '';
    const separator = `<span style="color: #999; margin: 0 5px;">|</span>`;
    if (cardId === 'owner-week-card') {
      const thisWeekLink = (offset === 0)
        ? `<span style="color: #999;">THIS WEEK</span>`
        : `<a href="#" style="text-decoration: none;" onclick="event.preventDefault(); loadStatsForWeek(0);">THIS WEEK</a>`;
      navLinks = `
            <div class="nav-links" style="font-size: 12px; text-transform: uppercase; font-weight: 400;">
                ${thisWeekLink} ${separator}
                <a href="#" style="text-decoration: none;" onclick="event.preventDefault(); loadStatsForWeek(-1);">-1W</a> ${separator}
                <a href="#" style="text-decoration: none;" onclick="event.preventDefault(); loadStatsForWeek(-2);">-2W</a> ${separator}
                <a href="#" style="text-decoration: none;" onclick="event.preventDefault(); loadStatsForWeek(-3);">-3W</a>
            </div>
        `;
    } else if (cardId === 'owner-month-card') {
      const thisMonthLink = (offset === 0)
        ? `<span style="color: #999;">THIS MONTH</span>`
        : `<a href="#" style="text-decoration: none;" onclick="event.preventDefault(); loadStatsForMonth(0);">THIS MONTH</a>`;
      const monthNames = ["JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC"];
      const today = new Date();
      const prevMonth1 = new Date(today.getFullYear(), today.getMonth() - 1, 1);
      const prevMonth2 = new Date(today.getFullYear(), today.getMonth() - 2, 1);
      const prevMonth3 = new Date(today.getFullYear(), today.getMonth() - 3, 1);
      navLinks = `
            <div class="nav-links" style="font-size: 12px; text-transform: uppercase; font-weight: 400;">
                ${thisMonthLink} ${separator}
                <a href="#" style="text-decoration: none;" onclick="event.preventDefault(); loadStatsForMonth(-1);">${monthNames[prevMonth1.getMonth()]}</a> ${separator}
                <a href="#" style="text-decoration: none;" onclick="event.preventDefault(); loadStatsForMonth(-2);">${monthNames[prevMonth2.getMonth()]}</a> ${separator}
                <a href="#" style="text-decoration: none;" onclick="event.preventDefault(); loadStatsForMonth(-3);">${monthNames[prevMonth3.getMonth()]}</a>
            </div>
        `;
    }

    const cardHtml = `
      ${flashStyle}
      <div class="stats-card" id="${cardId}">
        <div class="stats-card-title" style="display:flex; align-items:center; justify-content:space-between; text-transform:uppercase;">
          ${titleContent}
          ${navLinks}
        </div>
        <div class="stats-grid">${gridHtml}</div>
      </div>`;

    return cardHtml;
  }

  function loadStatsForWeek(offset) {
    if (!Array.isArray(window.allInstalls)) return;

    const currentWeekStart = new Date(window.currentMonday);
    currentWeekStart.setDate(currentWeekStart.getDate() + (offset * 7));
    currentWeekStart.setHours(0, 0, 0, 0);
    const weekEnd = new Date(currentWeekStart);
    weekEnd.setDate(currentWeekStart.getDate() + 6);
    weekEnd.setHours(23, 59, 59, 999);

    const weekInstalls = window.allInstalls.filter(install => {
      if (!install.date) return false;
      const d = new Date(install.date);
      d.setHours(12, 0, 0, 0);
      return d >= currentWeekStart && d <= weekEnd;
    });

    const weekStats = { AMR: { WIFI: 0, DVR: 0, NVR: 0 }, DOM: { WIFI: 0, DVR: 0, NVR: 0 }, BHA: { WIFI: 0, DVR: 0, NVR: 0 }, VAR: { WIFI: 0, DVR: 0, NVR: 0 }, ZOY: { WIFI: 0, DVR: 0, NVR: 0 } };
    weekInstalls.forEach(install => {
      if (install.owner && weekStats[install.owner] && weekStats[install.owner][install.type] !== undefined) {
        weekStats[install.owner][install.type]++;
      }
    });

    const title = offset === 0 ? 'This week (Owners)' : `Week of ${currentWeekStart.toLocaleDateString()}`;
    const newCardHTML = buildOwnerCardHTML(title, weekStats, 'owner-week-card', offset);

    const cardToReplace = document.getElementById('owner-week-card');
    if (cardToReplace) {
      cardToReplace.outerHTML = newCardHTML;
    }
  }

  function loadStatsForMonth(offset) {
    if (!Array.isArray(window.allInstalls)) return;

    const today = new Date();
    const targetDate = new Date(today.getFullYear(), today.getMonth() + offset, 1);
    const monthStart = new Date(targetDate.getFullYear(), targetDate.getMonth(), 1);
    const monthEnd = new Date(targetDate.getFullYear(), targetDate.getMonth() + 1, 0);
    monthEnd.setHours(23, 59, 59, 999);

    const monthInstalls = window.allInstalls.filter(install => {
      if (!install.date) return false;
      const d = new Date(install.date);
      d.setHours(12, 0, 0, 0);
      return d >= monthStart && d <= monthEnd;
    });

    const monthStats = { AMR: { WIFI: 0, DVR: 0, NVR: 0 }, DOM: { WIFI: 0, DVR: 0, NVR: 0 }, BHA: { WIFI: 0, DVR: 0, NVR: 0 }, VAR: { WIFI: 0, DVR: 0, NVR: 0 }, ZOY: { WIFI: 0, DVR: 0, NVR: 0 } };
    monthInstalls.forEach(install => {
      if (install.owner && monthStats[install.owner] && monthStats[install.owner][install.type] !== undefined) {
        monthStats[install.owner][install.type]++;
      }
    });

    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    const title = offset === 0 ? 'This month (Owners)' : `${monthNames[targetDate.getMonth()]}, ${targetDate.getFullYear()}`;
    const newCardHTML = buildOwnerCardHTML(title, monthStats, 'owner-month-card', offset);

    const cardToReplace = document.getElementById('owner-month-card');
    if (cardToReplace) {
      cardToReplace.outerHTML = newCardHTML;
    }
  }

  function buildStatsHTML(week, month) {
    const summary = `
      <div class="stats-summary">
        <div class="summary-pill"><span class="label">Weekly installs</span><span class="value">${lastWeekTotalCount}</span></div>
        <div class="summary-pill"><span class="label">Monthly installs</span><span class="value">${lastMonthTotalCount}</span></div>
        <div class="summary-pill"><span class="label">Cameras this month</span><span class="value">${lastMonthTotalCams}</span></div>
      </div>
    `;
    const techTable = (title, rows, showCams = false) => `
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
              ${rows.length
        ? rows.map(r => `
                      <tr>
                        <td>${escapeHtml(r.tech)}</td>
                        <td class="num">${r.count ?? r.daysWithTwo}</td>
                        ${showCams ? `<td class="num">${r.cams ?? 0}</td>` : ''}
                      </tr>
                    `).join('')
        : `<tr><td colspan="${showCams ? 3 : 2}" class="muted">No data</td></tr>`
      }
            </tbody>
          </table>
        </div>
      </div>
    `;
    return `
      ${summary}
      ${buildOwnerCardHTML('This week (Owners)', week, 'owner-week-card', 0)}
      ${buildOwnerCardHTML('This month (Owners)', month, 'owner-month-card', 0)}
      ${techTable('This week (Technicians)', lastTechWeek, true)}
      ${techTable('This month (Technicians)', lastTechMonth, true)}
      ${techTable('Two-in-a-day this month (Technicians)', lastTechTwoPerDay, false)}
      ${techDatesTable('Two-in-a-day dates this month (Technicians)', lastTechTwoPerDayDates)}
    `;
  }

  function updateStats() {
    if (!Array.isArray(window.allInstalls)) return;
    const allInstalls = window.allInstalls;
    const today = new Date();
    const currentWeekStart = new Date(window.currentMonday);
    currentWeekStart.setHours(0, 0, 0, 0);
    const currentWeekEnd = new Date(currentWeekStart);
    currentWeekEnd.setDate(currentWeekStart.getDate() + 6);
    currentWeekEnd.setHours(23, 59, 59, 999);

    const displayedMonthStart = new Date(currentWeekStart.getFullYear(), currentWeekStart.getMonth(), 1);
    displayedMonthStart.setHours(0, 0, 0, 0);
    const displayedMonthEnd = new Date(currentWeekStart.getFullYear(), currentWeekStart.getMonth() + 1, 0);
    displayedMonthEnd.setHours(23, 59, 59, 999);

    const weekStats = { AMR: { WIFI: 0, DVR: 0, NVR: 0 }, DOM: { WIFI: 0, DVR: 0, NVR: 0 }, BHA: { WIFI: 0, DVR: 0, NVR: 0 }, VAR: { WIFI: 0, DVR: 0, NVR: 0 }, ZOY: { WIFI: 0, DVR: 0, NVR: 0 } };
    const monthStats = { AMR: { WIFI: 0, DVR: 0, NVR: 0 }, DOM: { WIFI: 0, DVR: 0, NVR: 0 }, BHA: { WIFI: 0, DVR: 0, NVR: 0 }, VAR: { WIFI: 0, DVR: 0, NVR: 0 }, ZOY: { WIFI: 0, DVR: 0, NVR: 0 } };

    allInstalls.forEach(install => {
      if (!install.date || !install.owner) return;
      const d = new Date(install.date); d.setHours(12, 0, 0, 0);
      if (d >= currentWeekStart && d <= currentWeekEnd) {
        if (weekStats[install.owner] && weekStats[install.owner][install.type] !== undefined) weekStats[install.owner][install.type]++;
      }
      if (d >= displayedMonthStart && d <= displayedMonthEnd) {
        if (monthStats[install.owner] && monthStats[install.owner][install.type] !== undefined) monthStats[install.owner][install.type]++;
      }
    });

    const setText = (id, html) => { const el = document.getElementById(id); if (el) el.innerHTML = html; };
    // Remove DAR rows
    setText('weekAMR', `<div class="number">${weekStats.AMR.WIFI}</div>WiFi<div class="number">${weekStats.AMR.DVR}</div>DVR<div class="number">${weekStats.AMR.NVR}</div>NVR<br>AMR`);
    setText('weekDOM', `<div class="number">${weekStats.DOM.WIFI}</div>WiFi<div class="number">${weekStats.DOM.DVR}</div>DVR<div class="number">${weekStats.DOM.NVR}</div>NVR<br>DOM`);
    setText('weekBHA', `<div class="number">${weekStats.BHA.WIFI}</div>WiFi<div class="number">${weekStats.BHA.DVR}</div>DVR<div class="number">${weekStats.BHA.NVR}</div>NVR<br>BHA`);
    setText('weekVAR', `<div class="number">${weekStats.VAR.WIFI}</div>WiFi<div class="number">${weekStats.VAR.DVR}</div>DVR<div class="number">${weekStats.VAR.NVR}</div>NVR<br>VAR`);
    setText('weekZOY', `<div class="number">${weekStats.ZOY.WIFI}</div>WiFi<div class="number">${weekStats.ZOY.DVR}</div>DVR<div class="number">${weekStats.ZOY.NVR}</div>NVR<br>ZOY`);

    setText('monthAMR', `<div class="number">${monthStats.AMR.WIFI}</div>WiFi<div class="number">${monthStats.AMR.DVR}</div>DVR<div class="number">${monthStats.AMR.NVR}</div>NVR<br>AMR`);
    setText('monthDOM', `<div class="number">${monthStats.DOM.WIFI}</div>WiFi<div class="number">${monthStats.DOM.DVR}</div>DVR<div class="number">${monthStats.DOM.NVR}</div>NVR<br>DOM`);
    setText('monthBHA', `<div class="number">${monthStats.BHA.WIFI}</div>WiFi<div class="number">${monthStats.BHA.DVR}</div>DVR<div class="number">${monthStats.BHA.NVR}</div>NVR<br>BHA`);
    setText('monthVAR', `<div class="number">${monthStats.VAR.WIFI}</div>WiFi<div class="number">${monthStats.VAR.DVR}</div>DVR<div class="number">${monthStats.VAR.NVR}</div>NVR<br>VAR`);
    setText('monthZOY', `<div class="number">${monthStats.ZOY.WIFI}</div>WiFi<div class="number">${monthStats.ZOY.DVR}</div>DVR<div class="number">${monthStats.ZOY.NVR}</div>NVR<br>ZOY`);

    const weekTotal = /* exclude DAR */
      weekStats.AMR.WIFI + weekStats.AMR.DVR + weekStats.AMR.NVR
      + weekStats.DOM.WIFI + weekStats.DOM.DVR + weekStats.DOM.NVR
      + weekStats.BHA.WIFI + weekStats.BHA.DVR + weekStats.BHA.NVR
      + weekStats.VAR.WIFI + weekStats.VAR.DVR + weekStats.VAR.NVR
      + weekStats.ZOY.WIFI + weekStats.ZOY.DVR + weekStats.ZOY.NVR;
    const dayOfWeek = (today.getDay() === 0 ? 7 : today.getDay());
    const projectedWeek = Math.round((weekTotal / dayOfWeek) * 7);
    const thisWeekEl = document.getElementById('thisWeek'); if (thisWeekEl) thisWeekEl.textContent = `${weekTotal} (~${projectedWeek} / w)`;

    const totalSoFar = monthStats.AMR.WIFI + monthStats.AMR.DVR + monthStats.AMR.NVR
      + monthStats.DOM.WIFI + monthStats.DOM.DVR + monthStats.DOM.NVR
      + monthStats.BHA.WIFI + monthStats.BHA.DVR + monthStats.BHA.NVR
      + monthStats.VAR.WIFI + monthStats.VAR.DVR + monthStats.VAR.NVR
      + monthStats.ZOY.WIFI + monthStats.ZOY.DVR + monthStats.ZOY.NVR;
    const currentDay = today.getDate();
    const totalDays = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();
    const projected = Math.round((totalSoFar / currentDay) * totalDays);
    const thisMonthEl = document.getElementById('thisMonth'); if (thisMonthEl) thisMonthEl.textContent = `${totalSoFar} (~ ${projected} / m)`;

    lastWeekStats = JSON.parse(JSON.stringify(weekStats));
    lastMonthStats = JSON.parse(JSON.stringify(monthStats));
    lastWeekTotalCount = weekTotal;
    lastMonthTotalCount = totalSoFar;

    lastMonthTotalCams = allInstalls.reduce((sum, inst) => {
      if (!inst.date) return sum;
      const d = new Date(inst.date); d.setHours(12, 0, 0, 0);
      if (d >= displayedMonthStart && d <= displayedMonthEnd) {
        const c = parseInt(inst.cams, 10);
        return sum + (isNaN(c) ? 0 : c);
      }
      return sum;
    }, 0);

    const normTech = (t) => (t && String(t).trim()) || 'Unassigned';
    const weekTechCounts = {}; const monthTechCounts = {}; const monthTechDayCounts = {};

    allInstalls.forEach(inst => {
      if (!inst || !inst.date) return;
      const tech = normTech(inst.technician);
      const d = new Date(inst.date); d.setHours(12, 0, 0, 0);
      const cams = parseInt(inst.cams, 10) || 0;
      if (d >= currentWeekStart && d <= currentWeekEnd) {
        if (!weekTechCounts[tech]) weekTechCounts[tech] = { installs: 0, cams: 0 };
        weekTechCounts[tech].installs++; weekTechCounts[tech].cams += cams;
      }
      if (d >= displayedMonthStart && d <= displayedMonthEnd) {
        if (!monthTechCounts[tech]) monthTechCounts[tech] = { installs: 0, cams: 0 };
        monthTechCounts[tech].installs++; monthTechCounts[tech].cams += cams;
        const iso = inst.date;
        if (!monthTechDayCounts[tech]) monthTechDayCounts[tech] = {};
        monthTechDayCounts[tech][iso] = (monthTechDayCounts[tech][iso] || 0) + 1;
      }
    });

    const monthTwoPerDay = {};
    Object.entries(monthTechDayCounts).forEach(([tech, byDate]) => {
      let daysWithTwo = 0;
      Object.values(byDate).forEach(n => { if (n === 2) daysWithTwo++; });
      if (daysWithTwo > 0) monthTwoPerDay[tech] = daysWithTwo;
    });

    lastTechTwoPerDayDates = [];
    Object.entries(monthTechDayCounts).forEach(([tech, byDate]) => {
      const dates = Object.entries(byDate).filter(([, n]) => n === 2).map(([iso]) => iso).sort();
      if (dates.length) lastTechTwoPerDayDates.push({ tech, dates });
    });
    lastTechTwoPerDayDates.sort((a, b) => b.dates.length - a.dates.length);

    const sortDesc = (a, b) => b.installs - a.installs;
    const sortDescTwo = (a, b) => b.daysWithTwo - a.daysWithTwo;

    lastTechWeek = Object.entries(weekTechCounts).map(([tech, v]) => ({ tech, count: v.installs, cams: v.cams })).sort(sortDesc);
    lastTechMonth = Object.entries(monthTechCounts).map(([tech, v]) => ({ tech, count: v.installs, cams: v.cams })).sort(sortDesc);
    lastTechTwoPerDay = Object.entries(monthTwoPerDay).map(([tech, daysWithTwo]) => ({ tech, daysWithTwo })).sort(sortDescTwo);

    ensureOwnerStatsButton();
  }

  // Expose globals used by calendar
  window.updateStats = updateStats;
  window.openOwnerStatsModal = openOwnerStatsModal;
  window.closeOwnerStatsModal = closeOwnerStatsModal;
  window.loadStatsForWeek = loadStatsForWeek;
  window.loadStatsForMonth = loadStatsForMonth;
})();
