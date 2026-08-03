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

  function normalizeInstallDate(value) {
    if (value === null || value === undefined) return '';
    const text = String(value).trim();
    if (!text || text === '0000-00-00') return '';
    const match = text.match(/^(\d{4}-\d{2}-\d{2})/);
    return match ? match[1] : text;
  }
  window.normalizeInstallDate = normalizeInstallDate;

  function buildCityBadgeHtml(city, esc) {
    const label = String(city || 'Bangalore').trim().toLowerCase() === 'chennai' ? 'Chennai' : 'Bangalore';
    const bg = label === 'Chennai' ? '#f97316' : '#2563eb';
    return `<span class="event-city-tag" style="position:absolute;top:5px;right:6px;background:${bg};color:#fff;border-radius:4px;padding:2px 6px;font-size:10px;font-weight:900;line-height:1;text-transform:uppercase;letter-spacing:0;z-index:2;">${esc(label)}</span>`;
  }

  const INSTALL_MAPS_API_KEY = 'AIzaSyB7BKkBQEI0WpbFFjn8K4VWKRaYeIs3GhU';
  const INSTALL_BASE_COORDS = window.HSR_LAYOUT_COORDS || { lat: 12.9121, lng: 77.6446 };

  function getInstallEventCoords(ev) {
    const rawLat = ev && ev.map_lat !== undefined && ev.map_lat !== null ? String(ev.map_lat).trim() : '';
    const rawLng = ev && ev.map_lng !== undefined && ev.map_lng !== null ? String(ev.map_lng).trim() : '';
    const lat = Number(rawLat);
    const lng = Number(rawLng);
    if (rawLat !== '' && rawLng !== '' && Number.isFinite(lat) && Number.isFinite(lng)) return { lat, lng };
    if (typeof window.extractCoordsFromText === 'function') {
      return window.extractCoordsFromText(ev && ev.map) || window.extractCoordsFromText(ev && ev.location) || null;
    }
    return null;
  }

  function getInstallDirectionLabel(coords, origin = INSTALL_BASE_COORDS) {
    if (!coords || !Number.isFinite(coords.lat) || !Number.isFinite(coords.lng)) return '';
    const dx = coords.lng - origin.lng;
    const dy = coords.lat - origin.lat;
    if (Math.abs(dx) < 0.005 && Math.abs(dy) < 0.005) return 'CTR';
    const angle = (Math.atan2(dx, dy) * 180 / Math.PI + 360) % 360;
    const sectors = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
    return sectors[Math.round(angle / 45) % 8];
  }

  function getInstallDistanceLabel(coords, origin = INSTALL_BASE_COORDS) {
    if (typeof window.haversineDistanceKm !== 'function') return '';
    const km = window.haversineDistanceKm(origin, coords);
    if (!Number.isFinite(km)) return '';
    if (km < 1) return `${Math.round(km * 1000)}m`;
    return `${km.toFixed(km < 10 ? 1 : 0)}km`;
  }

  function installLatLngToWorldPoint(lat, lng) {
    const tileSize = 256;
    const siny = Math.min(Math.max(Math.sin(lat * Math.PI / 180), -0.9999), 0.9999);
    return {
      x: tileSize * (0.5 + lng / 360),
      y: tileSize * (0.5 - Math.log((1 + siny) / (1 - siny)) / (4 * Math.PI))
    };
  }

  function getInstallMapViewport(originCoords, targetCoords, width, height) {
    const p1 = installLatLngToWorldPoint(originCoords.lat, originCoords.lng);
    const p2 = installLatLngToWorldPoint(targetCoords.lat, targetCoords.lng);
    const dx = Math.abs(p1.x - p2.x);
    const dy = Math.abs(p1.y - p2.y);
    const padX = width * 0.18;
    const padY = height * 0.22;
    const usableW = Math.max(80, width - (padX * 2));
    const usableH = Math.max(80, height - (padY * 2));
    const zoomX = dx > 0 ? Math.log2(usableW / dx) : 16;
    const zoomY = dy > 0 ? Math.log2(usableH / dy) : 16;
    let zoom = Math.floor(Math.min(zoomX, zoomY, 16));
    if (!Number.isFinite(zoom)) zoom = 11;
    return { center: { lat: (originCoords.lat + targetCoords.lat) / 2, lng: (originCoords.lng + targetCoords.lng) / 2 }, zoom: Math.max(7, Math.min(16, zoom)) };
  }

  function buildInstallStaticMapUrl(coords) {
    if (!coords || !Number.isFinite(coords.lat) || !Number.isFinite(coords.lng)) return '';
    const viewport = getInstallMapViewport(INSTALL_BASE_COORDS, coords, 720, 480);
    const params = new URLSearchParams({
      center: `${viewport.center.lat.toFixed(6)},${viewport.center.lng.toFixed(6)}`,
      zoom: String(viewport.zoom),
      size: '720x480',
      scale: '2',
      maptype: 'roadmap',
      key: INSTALL_MAPS_API_KEY
    });
    params.append('style', 'feature:poi|visibility:off');
    params.append('style', 'feature:transit|visibility:off');
    params.append('style', 'feature:road|element:geometry|color:0xcbd5e1');
    params.append('style', 'feature:road|element:labels.text.fill|color:0x334155');
    params.append('style', 'feature:water|element:geometry|color:0x93c5fd');
    params.append('style', 'feature:landscape|element:geometry|color:0xf1f5f9');
    params.append('markers', `size:mid|color:blue|label:H|${INSTALL_BASE_COORDS.lat},${INSTALL_BASE_COORDS.lng}`);
    params.append('markers', `size:mid|color:red|label:I|${coords.lat},${coords.lng}`);
    return `https://maps.googleapis.com/maps/api/staticmap?${params.toString()}`;
  }

  function projectInstallMapPoint(coords, center, zoom, width, height) {
    const tileSize = 256;
    const scale = tileSize * Math.pow(2, zoom);
    const worldPoint = installLatLngToWorldPoint(coords.lat, coords.lng);
    const centerPoint = installLatLngToWorldPoint(center.lat, center.lng);
    return {
      x: ((worldPoint.x - centerPoint.x) * (scale / tileSize)) + (width / 2),
      y: ((worldPoint.y - centerPoint.y) * (scale / tileSize)) + (height / 2)
    };
  }

  function buildInstallInlineMapHtml(ev, esc) {
    const coords = getInstallEventCoords(ev);
    if (!coords) return '';
    const mapUrl = String((ev && ev.map) || '').trim();
    const staticMapUrl = buildInstallStaticMapUrl(coords);
    if (!staticMapUrl) return '';
    const viewport = getInstallMapViewport(INSTALL_BASE_COORDS, coords, 720, 480);
    const hsrPoint = projectInstallMapPoint(INSTALL_BASE_COORDS, viewport.center, viewport.zoom, 720, 480);
    const installPoint = projectInstallMapPoint(coords, viewport.center, viewport.zoom, 720, 480);
    const direction = getInstallDirectionLabel(coords);
    const distance = getInstallDistanceLabel(coords);
    const openHref = mapUrl || `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${coords.lat},${coords.lng}`)}`;
    return `
      <div class="install-geo-plot" title="HSR Layout to install location">
        <a href="${esc(openHref)}" target="_blank" rel="noopener" title="Open actual map location">
          <img class="install-geo-map" src="${esc(staticMapUrl)}" alt="Map from HSR Layout to install">
        </a>
        <svg class="install-geo-overlay" viewBox="0 0 720 480" preserveAspectRatio="none" aria-hidden="true">
          <line x1="${hsrPoint.x.toFixed(1)}" y1="${hsrPoint.y.toFixed(1)}" x2="${installPoint.x.toFixed(1)}" y2="${installPoint.y.toFixed(1)}" stroke="#ea580c" stroke-width="2" stroke-dasharray="6 6" stroke-linecap="round"></line>
          <circle cx="${hsrPoint.x.toFixed(1)}" cy="${hsrPoint.y.toFixed(1)}" r="11" fill="#2563eb" stroke="#ffffff" stroke-width="3"></circle>
          <circle cx="${installPoint.x.toFixed(1)}" cy="${installPoint.y.toFixed(1)}" r="11" fill="#dc2626" stroke="#ffffff" stroke-width="3"></circle>
        </svg>
        <div class="install-geo-meta">
          <span class="install-geo-pill">${esc([direction || 'CTR', distance].filter(Boolean).join(' | '))}</span>
        </div>
      </div>`;
  }

  function showInstallsLoadError(message) {
    let banner = document.getElementById('installs-load-error');
    if (!banner) {
      banner = document.createElement('div');
      banner.id = 'installs-load-error';
      banner.style.cssText = 'margin:12px 16px;padding:12px 14px;background:#fdecea;border:1px solid #f5c2c7;color:#842029;border-radius:8px;font-size:14px;';
      const calendar = document.querySelector('.calendar');
      if (calendar && calendar.parentNode) {
        calendar.parentNode.insertBefore(banner, calendar);
      } else {
        document.body.prepend(banner);
      }
    }
    banner.textContent = message;
  }

  w.render = w.render || function render(callback) {
    fetch('installs_api.php', { cache: 'no-store' })
      .then(res => res.text().then(text => ({ ok: res.ok, status: res.status, text })))
      .then(({ ok, status, text }) => {
        let data = [];
        try {
          data = text ? JSON.parse(text) : [];
        } catch (err) {
          console.error('installs_api.php returned invalid JSON:', text);
          showInstallsLoadError('Could not load installs (invalid server response). Check the browser console.');
          data = [];
        }
        if (!ok || (data && data.error)) {
          const msg = (data && (data.details || data.message || data.error))
            ? String(data.details || data.message || data.error)
            : `HTTP ${status}`;
          console.error('installs_api.php failed:', data || text);
          showInstallsLoadError(`Could not load installs: ${msg}`);
          w.allInstalls = [];
        } else if (!Array.isArray(data)) {
          console.error('installs_api.php returned non-array data:', data);
          showInstallsLoadError('Could not load installs (unexpected response format).');
          w.allInstalls = [];
        } else {
          const errBanner = document.getElementById('installs-load-error');
          if (errBanner) errBanner.remove();
          w.allInstalls = data.map(ev => ({ ...ev, date: normalizeInstallDate(ev.date) }));
        }
        if (!w.weeksContainer) {
          w.weeksContainer = document.getElementById('calendarWeeks');
        }
        if (!w.weeksContainer) {
          console.error('calendarWeeks element not found');
          return;
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
      })
      .catch(err => {
        console.error('Failed to fetch installs_api.php', err);
        showInstallsLoadError('Could not load installs (network error).');
        w.allInstalls = [];
        if (w.weeksContainer && typeof w.buildCalendar === 'function') {
          w.buildCalendar();
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

  w.scheduleInstallApiFetch = w.scheduleInstallApiFetch || (() => {
    const queue = [];
    let active = 0;
    const maxActive = 2;

    const runNext = () => {
      if (active >= maxActive || queue.length === 0) return;
      const job = queue.shift();
      active++;
      Promise.resolve()
        .then(job.task)
        .then(job.resolve, job.reject)
        .finally(() => {
          active--;
          runNext();
        });
    };

    return function scheduleInstallApiFetch(task) {
      return new Promise((resolve, reject) => {
        queue.push({ task, resolve, reject });
        runNext();
      });
    };
  })();

  w.installLeavesCache = w.installLeavesCache || new Map();
  w.installLeavesPending = w.installLeavesPending || new Map();
  w.installLeavesFlushTimer = w.installLeavesFlushTimer || null;

  function flushInstallLeavesFetch() {
    const pending = Array.from(w.installLeavesPending.entries());
    w.installLeavesPending.clear();
    w.installLeavesFlushTimer = null;
    if (!pending.length) return;

    const dates = pending.map(([date]) => date);
    const resolversByDate = new Map(pending);
    const url = `installs.php?get_leaves_bulk=1&dates=${encodeURIComponent(dates.join(','))}&t=${Date.now()}`;

    w.scheduleInstallApiFetch(() => fetch(url, { cache: 'no-store' }))
      .then(res => res.json())
      .then(data => {
        const leavesByDate = data && data.success && data.leaves ? data.leaves : {};
        dates.forEach(date => {
          const leaves = Array.isArray(leavesByDate[date]) ? leavesByDate[date] : [];
          w.installLeavesCache.set(date, leaves);
          (resolversByDate.get(date) || []).forEach(resolve => resolve(leaves));
        });
      })
      .catch(err => {
        console.error('Error fetching bulk leaves', err);
        dates.forEach(date => {
          w.installLeavesCache.set(date, []);
          (resolversByDate.get(date) || []).forEach(resolve => resolve([]));
        });
      });
  }

  async function fetchLeaves(date) {
    if (w.installLeavesCache.has(date)) return w.installLeavesCache.get(date);
    return new Promise(resolve => {
      const list = w.installLeavesPending.get(date) || [];
      list.push(resolve);
      w.installLeavesPending.set(date, list);
      if (!w.installLeavesFlushTimer) {
        w.installLeavesFlushTimer = setTimeout(flushInstallLeavesFetch, 0);
      }
    });
  }

  function askPaymentReceivedConfirmation() {
    return new Promise(resolve => {
      const existing = document.getElementById('payment-received-confirm');
      if (existing) existing.remove();

      const backdrop = document.createElement('div');
      backdrop.id = 'payment-received-confirm';
      backdrop.style.cssText = [
        'position:fixed',
        'inset:0',
        'z-index:2147483647',
        'display:flex',
        'align-items:center',
        'justify-content:center',
        'background:rgba(15,23,42,.42)'
      ].join(';');
      backdrop.innerHTML = `
        <div style="width:min(300px,calc(100vw - 32px));background:#fff;border-radius:8px;box-shadow:0 18px 45px rgba(0,0,0,.28);padding:16px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
          <div style="font-size:16px;font-weight:700;color:#111827;margin-bottom:10px;">Payment received?</div>
          <label style="display:block;font-size:12px;font-weight:700;color:#991b1b;margin:0 0 12px;">Pending amount
            <input type="number" min="0" step="0.01" id="payment-pending-amount" placeholder="Enter pending amount" style="width:100%;box-sizing:border-box;margin-top:4px;border:1px solid #fca5a5;border-radius:5px;padding:8px 9px;font-size:13px;color:#7f1d1d;background:#fff7f7;">
          </label>
          <div style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
            <button type="button" data-payment-confirm="cancel" style="border:1px solid #d1d5db;background:#fff;color:#374151;border-radius:6px;padding:7px 12px;font-size:13px;cursor:pointer;">Cancel</button>
            <button type="button" data-payment-confirm="pending" style="border:1px solid #dc2626;background:#dc2626;color:#fff;border-radius:6px;padding:7px 12px;font-size:13px;font-weight:700;cursor:pointer;">Pending Payment</button>
            <button type="button" data-payment-confirm="yes" style="border:1px solid #15803d;background:#15803d;color:#fff;border-radius:6px;padding:7px 14px;font-size:13px;font-weight:700;cursor:pointer;">Yes</button>
          </div>
        </div>
      `;

      const finish = value => {
        backdrop.remove();
        resolve(value);
      };
      backdrop.addEventListener('click', e => {
        const action = e.target && e.target.getAttribute ? e.target.getAttribute('data-payment-confirm') : '';
        if (action === 'yes') finish({ action: 'paid' });
        if (action === 'pending') {
          const amountInput = backdrop.querySelector('#payment-pending-amount');
          const amount = Number.parseFloat(amountInput ? amountInput.value : '');
          if (!Number.isFinite(amount) || amount <= 0) {
            if (amountInput) amountInput.focus();
            alert('Enter a valid pending amount.');
            return;
          }
          finish({ action: 'pending', amount });
        }
        if (action === 'cancel' || e.target === backdrop) finish({ action: 'cancel' });
      });
      document.body.appendChild(backdrop);
    });
  }

  function markEventPaymentReceived(orderId) {
    if (!orderId) return;
    if (!isAdminRole()) {
      alert('Only admin users can modify payment details');
      return;
    }

    const overlayForm = document.getElementById('overlay-payment-form');
    if (overlayForm && window.editingId && String(window.editingId) === String(orderId)) {
      const overlayFullyPaid = document.getElementById('overlay-fully-paid');
      if (overlayFullyPaid) overlayFullyPaid.checked = true;
      overlayForm.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
      return;
    }

    if (typeof window.submitPaymentUpdate === 'function') {
      window.submitPaymentUpdate(orderId, true, '');
      return;
    }

    alert('Payment form is not ready yet. Please try again.');
  }

  function updateLocalPendingAmount(orderId, amount) {
    const id = String(orderId || '');
    if (!id || !Array.isArray(w.allInstalls)) return;
    w.allInstalls.forEach(ev => {
      if (String(ev.id || ev.idno || '') === id) {
        ev.pending_amount = Number(amount) || 0;
        if (Number(amount) > 0) ev.fully_paid = false;
        if (Number(amount) <= 0) {
          ev.pending_amount = 0;
          ev.fully_paid = true;
        }
      }
    });
  }

  function submitPendingPaymentUpdate(orderId, pendingAmount) {
    if (!orderId) return;
    if (!isAdminRole()) {
      alert('Only admin users can modify payment details');
      return;
    }
    const amount = Number.parseFloat(pendingAmount);
    if (!Number.isFinite(amount) || amount < 0) {
      alert('Please enter a valid pending amount.');
      return;
    }

    fetch('../smart/installs.php', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        updatePendingPayment: true,
        id: orderId,
        pending_amount: amount
      })
    })
      .then(res => res.json())
      .then(data => {
        if (!data || !data.success) throw new Error(data && data.message ? data.message : 'Failed to update pending payment');
        updateLocalPendingAmount(orderId, amount);
        if (typeof w.render === 'function') w.render();
        if (typeof window.renderPendingPaymentsModal === 'function') window.renderPendingPaymentsModal();
        if (typeof window.refreshInstallHistoryPanel === 'function') window.refreshInstallHistoryPanel();
      })
      .catch(err => {
        console.error('Pending payment update failed', err);
        alert(err.message || 'Failed to update pending payment');
      });
  }
  window.submitPendingPaymentUpdate = submitPendingPaymentUpdate;

  function getPendingPaymentItems() {
    const installs = Array.isArray(w.allInstalls) ? w.allInstalls : [];
    return installs
      .map(ev => ({ ev, amount: Number.parseFloat(ev.pending_amount ?? ev.pendingAmount ?? 0) || 0 }))
      .filter(item => item.amount > 0 && String(item.ev.record_status || '').toUpperCase() !== 'DELETED')
      .sort((a, b) => {
        const ad = String(a.ev.date || '');
        const bd = String(b.ev.date || '');
        if (ad !== bd) return ad.localeCompare(bd);
        return String(a.ev.name || '').localeCompare(String(b.ev.name || ''));
      });
  }

  function renderPendingPaymentsModal() {
    const modal = document.getElementById('pending-payments-modal');
    if (!modal) return;
    const list = modal.querySelector('#pending-payments-list');
    const totalEl = modal.querySelector('#pending-payments-total');
    const countEl = modal.querySelector('#pending-payments-count');
    const esc = typeof window.escapeHtml === 'function' ? window.escapeHtml : (s) => String(s ?? '');
    const fmt = n => `₹${Math.round(Number(n) || 0).toLocaleString('en-IN')}`;
    const daysOldLabel = dateValue => {
      const raw = String(dateValue || '').trim();
      if (!raw) return '';
      const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
      if (!match) return '';
      const eventDate = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
      if (Number.isNaN(eventDate.getTime())) return '';
      const today = new Date();
      const startToday = new Date(today.getFullYear(), today.getMonth(), today.getDate());
      const diffDays = Math.max(0, Math.floor((startToday - eventDate) / 86400000));
      if (diffDays === 0) return 'Today';
      if (diffDays === 1) return '1 day old';
      return `${diffDays} days old`;
    };
    const canClearPending = isAdminRole();
    const items = getPendingPaymentItems();
    const total = items.reduce((sum, item) => sum + item.amount, 0);
    if (totalEl) totalEl.textContent = fmt(total);
    if (countEl) countEl.textContent = `${items.length} pending${items.length === 1 ? '' : 's'}`;
    if (!list) return;
    if (!items.length) {
      list.innerHTML = '<div style="padding:18px;text-align:center;color:#64748b;font-size:13px;">No pending payments.</div>';
      return;
    }
    list.innerHTML = items.map(({ ev, amount }, index) => {
      const id = esc(ev.id || ev.idno || '');
      const map = ev.map || ev.Map || '';
      const location = ev.location || ev.area || ev.Area || '';
      const city = ev.city || 'Bangalore';
      const panelId = `pending-payment-details-${index}`;
      const ageLabel = daysOldLabel(ev.date);
      const ownerLabel = String(ev.owner || ev.Owner || '').trim();
      const detailBits = [
        ev.date ? `Date: ${esc(ev.date)}` : '',
        ev.time ? `Time: ${esc(ev.time)}` : '',
        location ? `Location: ${map ? `<a href="${esc(map)}" target="_blank" style="color:#2563eb;text-decoration:none;">${esc(location)}</a>` : esc(location)}` : '',
        city ? `City: ${esc(city)}` : '',
        ev.owner ? `Owner: ${esc(ev.owner)}` : '',
        ev.technician ? `Technician: ${esc(ev.technician)}` : '',
        ev.helper ? `Helper: ${esc(ev.helper)}` : '',
        ev.phone ? `Phone: <a href="tel:${esc(ev.phone)}" style="color:#2563eb;text-decoration:none;">${esc(ev.phone)}</a>` : '',
        ev.notes ? `Notes: ${esc(ev.notes)}` : ''
      ].filter(Boolean).join(' · ');
      const clearButton = canClearPending
        ? `<button type="button" data-clear-pending-payment="${id}" title="Mark pending as cleared" style="border:0;background:#dc2626;color:#fff;border-radius:5px;padding:7px 10px;cursor:pointer;font-size:12px;font-weight:800;white-space:nowrap;display:inline-flex;align-items:center;gap:6px;"><i class="fas fa-trash"></i> Clear</button>`
        : '';
      return `
        <div class="pending-payment-item" style="border:1px solid #fecaca;background:#fff7f7;border-radius:6px;margin-bottom:9px;overflow:hidden;">
          <button type="button" data-pending-payment-toggle aria-expanded="false" aria-controls="${panelId}" style="width:100%;border:0;background:transparent;padding:10px 12px;display:grid;grid-template-columns:minmax(0,1fr) auto auto;align-items:center;gap:10px;text-align:left;cursor:pointer;color:#111827;">
            <span style="min-width:0;font-size:14px;line-height:1.25;">
              <strong style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${esc(ev.name || 'No Name')}${ownerLabel ? ` <span style="font-weight:700;color:#64748b;font-size:12px;">(${esc(ownerLabel)})</span>` : ''}${ageLabel ? ` <span style="font-weight:700;color:#991b1b;font-size:11px;">${esc(ageLabel)}</span>` : ''}</strong>
              <span style="color:#64748b;font-size:12px;">${id}</span>
            </span>
            <strong style="color:#dc2626;font-size:15px;white-space:nowrap;">${fmt(amount)}</strong>
            <i class="fas fa-caret-down" data-pending-caret style="color:#991b1b;font-size:15px;transition:transform .18s ease;"></i>
          </button>
          <div id="${panelId}" data-pending-payment-details style="display:none;border-top:1px solid #fecaca;padding:9px 12px 11px;background:#fff;">
            <div style="color:#475569;font-size:12px;line-height:1.45;">${detailBits || 'No extra details available.'}</div>
            ${clearButton ? `<div style="margin-top:10px;display:flex;justify-content:flex-end;">${clearButton}</div>` : ''}
          </div>
        </div>
      `;
    }).join('');
  }
  window.renderPendingPaymentsModal = renderPendingPaymentsModal;

  function openPendingPaymentsModal() {
    let modal = document.getElementById('pending-payments-modal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'pending-payments-modal';
      modal.style.cssText = [
        'position:fixed',
        'inset:0',
        'z-index:2147483646',
        'display:none',
        'align-items:center',
        'justify-content:center',
        'background:rgba(15,23,42,.46)',
        'padding:16px'
      ].join(';');
      modal.innerHTML = `
        <div style="width:min(520px,calc(100vw - 28px));max-height:min(680px,calc(100vh - 32px));background:#fff;border-radius:8px;box-shadow:0 22px 60px rgba(0,0,0,.32);display:flex;flex-direction:column;overflow:hidden;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 15px;border-bottom:1px solid #e5e7eb;background:#f8fafc;">
            <div>
              <div style="display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;">
                <span style="font-size:16px;font-weight:900;color:#111827;">Pending Payments</span>
                <strong id="pending-payments-total" style="font-size:16px;color:#dc2626;">₹0</strong>
              </div>
              <div id="pending-payments-count" style="font-size:12px;color:#64748b;margin-top:2px;">0 pendings</div>
            </div>
            <button type="button" data-close-pending-payments="1" aria-label="Close" style="border:0;background:#e5e7eb;color:#111827;border-radius:50%;width:30px;height:30px;cursor:pointer;font-size:18px;font-weight:900;line-height:1;">&times;</button>
          </div>
          <div id="pending-payments-list" style="padding:12px 15px;overflow:auto;"></div>
        </div>
      `;
      modal.addEventListener('click', e => {
        if (e.target === modal || e.target.closest('[data-close-pending-payments]')) {
          modal.style.display = 'none';
          return;
        }
        const clearBtn = e.target.closest('[data-clear-pending-payment]');
        if (clearBtn) {
          if (!isAdminRole()) {
            alert('Only admin users can clear pending payments.');
            return;
          }
          const id = clearBtn.getAttribute('data-clear-pending-payment');
          if (!id) return;
          if (!window.confirm('Mark this pending amount as paid / cleared?')) return;
          submitPendingPaymentUpdate(id, 0);
          return;
        }
        const toggle = e.target.closest('[data-pending-payment-toggle]');
        if (toggle) {
          const item = toggle.closest('.pending-payment-item');
          const details = item ? item.querySelector('[data-pending-payment-details]') : null;
          const caret = toggle.querySelector('[data-pending-caret]');
          if (!details) return;
          const isOpen = details.style.display !== 'none';
          details.style.display = isOpen ? 'none' : 'block';
          toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
          if (caret) caret.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
        }
      });
      document.body.appendChild(modal);
    }
    renderPendingPaymentsModal();
    modal.style.display = 'flex';
  }
  window.openPendingPaymentsModal = openPendingPaymentsModal;

  function attachPaymentReceivedLongPress(titleSpan, orderId) {
    if (!titleSpan || !orderId) return;
    let timer = null;
    let startX = 0;
    let startY = 0;
    let longPressTriggered = false;

    const clearTimer = () => {
      if (timer) {
        clearTimeout(timer);
        timer = null;
      }
    };

    titleSpan.title = 'Hold 2 seconds to mark payment received';
    titleSpan.style.cursor = 'pointer';
    titleSpan.style.userSelect = 'none';
    titleSpan.style.touchAction = 'manipulation';
    titleSpan.addEventListener('click', e => {
      if (longPressTriggered) {
        e.preventDefault();
        e.stopPropagation();
        longPressTriggered = false;
        return;
      }
      if (typeof w.editInstall === 'function') w.editInstall(orderId);
    });
    titleSpan.addEventListener('pointerdown', e => {
      if (e.pointerType === 'mouse' && e.button !== 0) return;
      startX = e.clientX;
      startY = e.clientY;
      clearTimer();
      timer = setTimeout(async () => {
        timer = null;
        longPressTriggered = true;
        const result = await askPaymentReceivedConfirmation();
        if (result && result.action === 'paid') markEventPaymentReceived(orderId);
        if (result && result.action === 'pending') submitPendingPaymentUpdate(orderId, result.amount);
      }, 2000);
    });
    titleSpan.addEventListener('pointermove', e => {
      if (!timer) return;
      if (Math.abs(e.clientX - startX) > 8 || Math.abs(e.clientY - startY) > 8) clearTimer();
    });
    titleSpan.addEventListener('pointerup', clearTimer);
    titleSpan.addEventListener('pointercancel', clearTimer);
    titleSpan.addEventListener('pointerleave', clearTimer);
  }

  function buildAdminEventCommentHtml(comment, orderId, escFn) {
    const text = String(comment || '').trim();
    if (!text) return '';
    const esc = typeof escFn === 'function' ? escFn : (value) => String(value ?? '');
    const deleteButton = canShowAdminEventCommentDelete()
      ? `<button class="event-admin-comment-delete" type="button" data-admin-comment-delete="${esc(orderId || '')}" title="Delete admin comment" aria-label="Delete admin comment" style="appearance:none;border:0;background:rgba(255,255,255,.22);color:#fff;width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:16px;font-weight:900;line-height:1;cursor:pointer;flex:0 0 20px;padding:0;">&times;</button>`
      : '';
    return `<div class="event-admin-comment" style="background:#dc2626;color:#fff;padding:5px 8px;margin:4px 0 7px;border-radius:4px;font-size:12px;font-weight:700;line-height:1.35;display:flex;align-items:flex-start;justify-content:space-between;gap:8px;"><span style="min-width:0;overflow-wrap:anywhere;">${esc(text)}</span>${deleteButton}</div>`;
  }

  async function deleteAdminEventComment(orderId) {
    const id = String(orderId || '').trim();
    if (!id) return;
    if (!isAdminRole()) {
      alert('Only admin can delete admin comments');
      return;
    }
    if (!window.confirm('Delete this admin comment?')) return;

    try {
      const res = await fetch('../smart/installs.php', {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          deleteAdminEventComment: true,
          id
        })
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Failed to delete admin comment');
      }
      if (Array.isArray(w.allInstalls)) {
        w.allInstalls = w.allInstalls.map(ev => (
          String(ev && ev.id) === id ? { ...ev, admin_event_comment: '' } : ev
        ));
      }
      const form = document.getElementById('installForm');
      if (form && form.admin_event_comment && String(w.editingId || '') === id) {
        form.admin_event_comment.value = '';
      }
      if (typeof w.render === 'function') w.render();
      refreshInstallHistoryPanel();
    } catch (err) {
      alert((err && err.message) ? err.message : 'Failed to delete admin comment');
    }
  }

  function historyEscape(value) {
    if (typeof w.escapeHtml === 'function') return w.escapeHtml(value);
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    }[ch]));
  }

  function formatHistoryTime(value) {
    const raw = String(value || '').trim();
    if (!raw) return '';
    const date = new Date(raw.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return raw;
    return date.toLocaleString('en-IN', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    });
  }

  function formatHistoryDetails(details) {
    if (!details || typeof details !== 'object') return '';
    if (details.changed && typeof details.changed === 'object') {
      return Object.entries(details.changed).map(([label, values]) => {
        const oldValue = values && typeof values === 'object' ? values.old : '';
        const newValue = values && typeof values === 'object' ? values.new : '';
        return `<div><strong>${historyEscape(label)}:</strong> ${historyEscape(oldValue || '-')} -&gt; ${historyEscape(newValue || '-')}</div>`;
      }).join('');
    }

    const readable = {
      old_date: 'Old date',
      new_date: 'New date',
      old_time: 'Old time',
      new_time: 'New time',
      old_amount: 'Old amount',
      new_amount: 'New amount',
      old_amount_paid: 'Old paid',
      new_amount_paid: 'New paid',
      old_fully_paid: 'Was fully paid',
      new_fully_paid: 'Now fully paid',
      filename: 'File',
      record_status: 'Status'
    };

    return Object.entries(details)
      .filter(([key]) => key !== 'old_extras' && key !== 'new_extras' && key !== 'old_comment' && key !== 'new_comment')
      .map(([key, value]) => `<div><strong>${historyEscape(readable[key] || key.replace(/_/g, ' '))}:</strong> ${historyEscape(value === true ? 'Yes' : value === false ? 'No' : (value || '-'))}</div>`)
      .join('');
  }

  function renderInstallHistory(rows, filterId) {
    const list = document.getElementById('install-history-list');
    const subtitle = document.getElementById('install-history-subtitle');
    if (!list) return;
    const id = String(filterId || '').trim();
    if (subtitle) subtitle.textContent = id ? `History for ${id}` : 'Latest actions';
    if (!Array.isArray(rows) || rows.length === 0) {
      list.innerHTML = '<div class="install-history-empty">No history found</div>';
      return;
    }
    list.innerHTML = rows.map(row => {
      const orderId = row.order_idno || '';
      const orderName = row.order_name || '';
      const details = formatHistoryDetails(row.details);
      const actionLabel = row.action_label || row.action_type || 'Action';
      return `
        <div class="install-history-item">
          <div class="install-history-item-title">
            <span>${historyEscape(row.username || 'Unknown')}</span>
            <span>${historyEscape(formatHistoryTime(row.created_at))}</span>
          </div>
          <div class="install-history-meta">
            ${historyEscape(orderName || 'No name')} &middot; ${historyEscape(orderId || '-')}
          </div>
          <div class="install-history-details"><div><strong>Action:</strong> ${historyEscape(actionLabel)}</div>${details}</div>
        </div>
      `;
    }).join('');
  }

  async function loadInstallHistory(filterId) {
    if (!isAdminRole()) {
      throw new Error('Only admin can view install history');
    }
    const id = String(filterId || '').trim();
    const list = document.getElementById('install-history-list');
    if (list) list.innerHTML = '<div class="install-history-empty">Loading history...</div>';
    const url = `../smart/installs.php?get_history=1&limit=120${id ? `&id=${encodeURIComponent(id)}` : ''}`;
    const res = await fetch(url, { cache: 'no-store', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Failed to load history');
    }
    renderInstallHistory(data.history || [], id);
  }

  function openInstallHistory(filterId) {
    if (!isAdminRole()) {
      alert('Only admin can view install history');
      return;
    }
    const panel = document.getElementById('install-history-panel');
    if (!panel) return;
    panel.classList.add('is-open');
    panel.dataset.currentId = String(filterId || '').trim();
    loadInstallHistory(panel.dataset.currentId).catch(err => {
      const list = document.getElementById('install-history-list');
      if (list) list.innerHTML = `<div class="install-history-empty">${historyEscape(err.message || 'Failed to load history')}</div>`;
    });
  }

  function refreshInstallHistoryPanel() {
    const panel = document.getElementById('install-history-panel');
    if (!panel || !panel.classList.contains('is-open')) return;
    loadInstallHistory(panel.dataset.currentId || '').catch(() => {});
  }

  function closeInstallHistory() {
    const panel = document.getElementById('install-history-panel');
    if (panel) panel.classList.remove('is-open');
  }

  function initInstallHistoryPanel() {
    const staleOpenBtn = document.getElementById('install-history-open-btn');
    if (staleOpenBtn) staleOpenBtn.remove();
    if (!isAdminRole()) {
      const panel = document.getElementById('install-history-panel');
      if (panel) panel.remove();
      return;
    }
    const closeBtn = document.getElementById('install-history-close');
    const panel = document.getElementById('install-history-panel');
    const header = document.getElementById('install-history-drag');
    if (closeBtn) closeBtn.addEventListener('click', closeInstallHistory);
    if (!panel || !header || header.dataset.historyDragReady === '1') return;
    header.dataset.historyDragReady = '1';

    let moving = false;
    let startX = 0;
    let startY = 0;
    let startLeft = 0;
    let startTop = 0;

    const move = (ev) => {
      if (!moving) return;
      const left = Math.max(8, Math.min(window.innerWidth - panel.offsetWidth - 8, startLeft + ev.clientX - startX));
      const top = Math.max(8, Math.min(window.innerHeight - 48, startTop + ev.clientY - startY));
      panel.style.left = `${left}px`;
      panel.style.top = `${top}px`;
      panel.style.right = 'auto';
    };

    const up = () => {
      moving = false;
      document.removeEventListener('mousemove', move, true);
      document.removeEventListener('mouseup', up, true);
    };

    header.addEventListener('mousedown', (ev) => {
      if (ev.button !== 0) return;
      if (ev.target.closest('button')) return;
      const rect = panel.getBoundingClientRect();
      moving = true;
      startX = ev.clientX;
      startY = ev.clientY;
      startLeft = rect.left;
      startTop = rect.top;
      document.addEventListener('mousemove', move, true);
      document.addEventListener('mouseup', up, true);
    });
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
        if (!isAdminRole() && isPastCalendarDate(dateStr)) {
          alert('Only admin can move an event to a past date.');
          return;
        }
        if (item && typeof w.saveInstalls === 'function') {
          w.saveInstalls({ ...item, date: dateStr });
        }
      };

      const installs = Array.isArray(w.allInstalls) ? w.allInstalls : [];
      const todaysInstalls = installs
        .filter(ev => normalizeInstallDate(ev.date) === dateStr)
        .sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
      todaysInstalls.forEach(ev => {
        const div = document.createElement('div'); div.className = `event ${ev.type || ''}`;
        div.style.position = 'relative';
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
        const historyIcon = isAdminRole() ? `<i class="fas fa-clock-rotate-left" onclick="openInstallHistory('${ev.id}')" title="History"></i>` : '';
        if (label === 'current') {
          const origin = 'HSR Layout';
          const destination = `${ev.location}, Bengaluru`;
          const esc = typeof window.escapeHtml === 'function' ? window.escapeHtml : (s) => String(s ?? '');
          const installInlineMapHtml = buildInstallInlineMapHtml(ev, esc);
          const installMapToggleHtml = installInlineMapHtml
            ? `<button class="install-map-inline-toggle" type="button" data-install-map-toggle="${esc(ev.id || '')}" aria-label="Toggle inline map" aria-expanded="false"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></button>`
            : '';
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
              : brandLower === 'secureye'
              ? 'https://smartronic.online/content/uploads/2025/01/Secureye_logo.png'
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
            ${buildCityBadgeHtml(ev.city, window.escapeHtml)}
            <h3><span${idClassAttr}> ${ev.id.replace('-', '<br>')}</span><a class="name-link" href="tel:${ev.phone}">${ev.name || 'No Name'}</a></h3>
            ${buildAdminEventCommentHtml(ev.admin_event_comment, ev.id, window.escapeHtml)}
            ${ev.notes ? `<div class="event-notes" style="background:#fffbcc;border-left:1px solid #f0b414;padding:4px 8px;margin:4px 0 9px;color:#333;"><strong>${window.escapeHtml(ev.notes)}</strong></div>` : ''}
            ${brandLogo ? `<div style="margin: 4px 0; display: flex; align-items: center; justify-content: left;">
              ${brandLogo}
              ${camTypeBox}
            </div>` : ''}
            <div class="details">
              <span class="hdd">${ev.hdd || ''}</span>
              <span class="cams"> ${ev.resolution || ''} x <strong>${ev.cams || 0}</strong></span>
              <span class="cams type">${ev.bullets || 0} B + ${ev.dome || 0} D</span>
              <span class="area"><span id="dist-${ev.id}"></span> <a href="${ev.map || ''}" target="_blank">${ev.location || ''}</a>  @ ${ev.time || ''} ${installMapToggleHtml}</span>
              <span class="monitor">${monitorIcon}</span> <span class="rack">${rackIcon}</span></div>
            ${installInlineMapHtml ? `<div class="install-inline-map-wrap">${installInlineMapHtml}</div>` : ''}
            <div class="names">${(ev.lead_campaign && ev.lead_campaign.toString().trim().length < 8) ? `<span style="display:inline-flex;align-items:center;height:16px;background:#9ca3af;color:#fff;font-size:10px;line-height:1;padding:0 6px;border-radius:3px;margin-right:6px;font-weight:800;">${window.escapeHtml(ev.lead_campaign.toString().trim())}</span>` : ''}<strong>${ev.owner || ''}</strong> | <a href="#" onclick="sendToWhatsapp(event, ${JSON.stringify(ev).replace(/"/g, '&quot;')})">${ev.technician || ''}</a> | ${ev.helper || ''}</div>
	            <div class="icons">${historyIcon}<i class="fas fa-edit" onclick="editInstall('${ev.id}')" title="Edit"></i><i class="fas fa-trash" onclick="deleteInstall('${ev.id}')" title="Delete"></i></div>
	          `;
          attachPaymentReceivedLongPress(div.querySelector('h3 span'), ev.id);
	          if (destination && typeof window.requestInstallDistance === 'function') {
            window.requestInstallDistance({ origin, destination, mapUrl: ev.map || '', coords: (ev.map_lat && ev.map_lng) ? { lat: ev.map_lat, lng: ev.map_lng } : null })
              .then(data => {
                const distEl = div.querySelector(`#dist-${ev.id}`);
                if (!distEl) return;
                if (data && data.distance_km !== undefined) distEl.innerHTML = `${Math.round(data.distance_km)}`; else distEl.innerHTML = '';
              })
              .catch(err => console.error('Distance fetch error:', err));
          }

          
        } else {
          div.innerHTML = `
            ${buildCityBadgeHtml(ev.city, window.escapeHtml)}
            <span class="past-event"><strong>${ev.id || 0}</strong> | ${ev.name || 'No Name'}</span>
            <div class="icons past">${historyIcon}<i class="fas fa-edit" onclick="editInstall('${ev.id}')" title="Edit"></i><i class="fas fa-trash" onclick="deleteInstall('${ev.id}')" title="Delete"></i></div>
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

  w.showProfitLines = w.showProfitLines || false;
  w.weeklyProfitBreakups = w.weeklyProfitBreakups instanceof Map ? w.weeklyProfitBreakups : new Map();
  w.monthlyProfitExpanded = w.monthlyProfitExpanded || false;
  w.monthlyProfitState = w.monthlyProfitState || { key: '', loading: false, data: null, token: 0 };
  const PROFIT_AUTO_HIDE_SECONDS = 30;
  if (typeof w.profitAutoHideTimer === 'undefined') w.profitAutoHideTimer = null;
  if (typeof w.profitCountdownTimer === 'undefined') w.profitCountdownTimer = null;
  if (typeof w.profitCountdownRemaining === 'undefined') w.profitCountdownRemaining = 0;
  if (typeof w.profitAutoHidePaused === 'undefined') w.profitAutoHidePaused = false;

  function fmtInr(n) {
    return `₹${Math.round(n).toLocaleString('en-IN')}`;
  }

  function getCurrentWeekProfitEventIds() {
    return Array.from(document.querySelectorAll('.week.current .event'))
      .map(el => String(el.dataset.id || '').trim())
      .filter(Boolean);
  }

  function getCurrentProfitMonth() {
    const base = new Date(w.currentMonday || new Date());
    const start = new Date(base.getFullYear(), base.getMonth(), 1);
    const end = new Date(base.getFullYear(), base.getMonth() + 1, 0);
    return {
      start,
      end,
      startIso: window.formatDate ? window.formatDate(start) : start.toISOString().slice(0, 10),
      endIso: window.formatDate ? window.formatDate(end) : end.toISOString().slice(0, 10),
      label: start.toLocaleString('default', { month: 'short', year: 'numeric' }),
      days: end.getDate()
    };
  }

  function getCurrentMonthProfitEvents() {
    const month = getCurrentProfitMonth();
    const installs = Array.isArray(w.allInstalls) ? w.allInstalls : [];
    return installs.filter(ev => {
      const date = normalizeInstallDate(ev && ev.date);
      return date && date >= month.startIso && date <= month.endIso && String(ev && ev.id || '').trim() !== '';
    });
  }

  function getMonthProfitKey(events) {
    const month = getCurrentProfitMonth();
    const ids = events.map(ev => String(ev && ev.id || '').trim()).filter(Boolean).sort().join(',');
    return `${month.startIso}|${month.endIso}|${ids}`;
  }

  async function calculateProfitBreakup(ev) {
    const actualAmount = parseFloat(String(ev && ev.price != null ? ev.price : '').replace(/[^0-9.]/g, ''));
    const amountPaid = parseFloat(String(ev && ev.amount_paid != null ? ev.amount_paid : '').replace(/[^0-9.]/g, ''));
    const customerPayable = !isNaN(actualAmount) ? actualAmount : amountPaid;

    let sellerWithGst = NaN;
    if (typeof w.calculateInstallPricing === 'function') {
      const pricing = await w.calculateInstallPricing({ ...ev, customerPayable });
      sellerWithGst = pricing.materialCost;
      if (pricing.missing && pricing.missing.length) {
        console.warn('Missing data.json pricing for event', ev && ev.id, pricing.missing);
      }
    }

    if (isNaN(customerPayable) || isNaN(sellerWithGst)) {
      return { profit: null, quoteAmount: null, materialCost: null };
    }
    return {
      profit: customerPayable - sellerWithGst,
      quoteAmount: customerPayable,
      materialCost: sellerWithGst
    };
  }

  function startMonthlyProfitLoad() {
    if (!w.monthlyProfitExpanded || !w.showProfitLines) return;
    const events = getCurrentMonthProfitEvents();
    const key = getMonthProfitKey(events);
    if (w.monthlyProfitState.loading && w.monthlyProfitState.key === key) return;
    if (w.monthlyProfitState.data && w.monthlyProfitState.key === key) return;

    const token = (w.monthlyProfitState.token || 0) + 1;
    w.monthlyProfitState = { key, loading: true, data: null, token };
    renderWeeklyProfitTotal();

    Promise.all(events.map(ev => calculateProfitBreakup(ev).catch(err => {
      console.warn('Failed to compute monthly profit for event', ev && ev.id, err);
      return { profit: null, quoteAmount: null, materialCost: null };
    }))).then(rows => {
      if (!w.monthlyProfitState || w.monthlyProfitState.token !== token) return;
      let total = 0;
      let quoteTotal = 0;
      let materialCostTotal = 0;
      let ready = 0;
      rows.forEach(value => {
        if (!value) return;
        if (typeof value.profit === 'number' && Number.isFinite(value.profit)) {
          total += value.profit;
          ready += 1;
        }
        if (typeof value.quoteAmount === 'number' && Number.isFinite(value.quoteAmount)) quoteTotal += value.quoteAmount;
        if (typeof value.materialCost === 'number' && Number.isFinite(value.materialCost)) materialCostTotal += value.materialCost;
      });
      const month = getCurrentProfitMonth();
      w.monthlyProfitState = {
        key,
        loading: false,
        token,
        data: {
          total,
          quoteTotal,
          materialCostTotal,
          ready,
          count: events.length,
          days: month.days,
          label: month.label
        }
      };
      renderWeeklyProfitTotal();
    });
  }

  function ensureWeeklyProfitTotal() {
    const role = getCookie('auth_role');
    if (role !== 'admin') return null;
    let el = document.getElementById('weekly-profit-total');
    if (el) return el;
    el = document.createElement('div');
    el.id = 'weekly-profit-total';
    el.style.cssText = `
      position: fixed;
      bottom: 10px;
      right: 10px;
      z-index: 99999;
      min-width: 172px;
      padding: 10px 14px;
      border-radius: 8px;
      background: linear-gradient(135deg, #14532d, #15803d);
      color: #fff;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      display: none;
      user-select: none;
    `;
    el.addEventListener('click', event => {
      const pause = event.target && event.target.closest ? event.target.closest('[data-profit-countdown-pause]') : null;
      if (pause) {
        event.preventDefault();
        event.stopPropagation();
        pauseProfitAutoHideCountdown();
        return;
      }
      const toggle = event.target && event.target.closest ? event.target.closest('[data-month-profit-toggle]') : null;
      if (!toggle) return;
      event.preventDefault();
      event.stopPropagation();
      w.monthlyProfitExpanded = !w.monthlyProfitExpanded;
      if (!w.monthlyProfitExpanded && w.monthlyProfitState) {
        w.monthlyProfitState.loading = false;
      }
      renderWeeklyProfitTotal();
    });
    document.body.appendChild(el);
    return el;
  }

  function clearProfitAutoHideTimers() {
    if (w.profitAutoHideTimer) {
      clearTimeout(w.profitAutoHideTimer);
      w.profitAutoHideTimer = null;
    }
    if (w.profitCountdownTimer) {
      clearInterval(w.profitCountdownTimer);
      w.profitCountdownTimer = null;
    }
  }

  function updateProfitCountdownDisplay() {
    const badge = document.getElementById('weekly-profit-countdown');
    if (!badge) return;
    if (w.profitAutoHidePaused) {
      badge.textContent = 'Pinned';
      badge.title = 'Profit will stay visible until you tap the rupee button';
      return;
    }
    const remaining = Math.max(0, Number(w.profitCountdownRemaining || 0));
    badge.textContent = `${remaining}s`;
    badge.title = 'Tap to keep profit visible';
  }

  function hideProfitLinesFromTimer() {
    if (!w.showProfitLines || w.profitAutoHidePaused) return;
    clearProfitAutoHideTimers();
    w.profitCountdownRemaining = 0;
    w.showProfitLines = false;
    document.querySelectorAll('.event .profit-line').forEach(el => {
      el.style.display = 'none';
    });
    renderWeeklyProfitTotal();
  }

  function startProfitAutoHideCountdown() {
    clearProfitAutoHideTimers();
    w.profitAutoHidePaused = false;
    w.profitCountdownRemaining = PROFIT_AUTO_HIDE_SECONDS;
    updateProfitCountdownDisplay();
    w.profitCountdownTimer = setInterval(() => {
      if (!w.showProfitLines || w.profitAutoHidePaused) {
        clearProfitAutoHideTimers();
        return;
      }
      w.profitCountdownRemaining = Math.max(0, Number(w.profitCountdownRemaining || 0) - 1);
      updateProfitCountdownDisplay();
      if (w.profitCountdownRemaining <= 0) hideProfitLinesFromTimer();
    }, 1000);
    w.profitAutoHideTimer = setTimeout(hideProfitLinesFromTimer, PROFIT_AUTO_HIDE_SECONDS * 1000);
  }

  function pauseProfitAutoHideCountdown() {
    if (!w.showProfitLines) return;
    w.profitAutoHidePaused = true;
    clearProfitAutoHideTimers();
    updateProfitCountdownDisplay();
  }

  function renderWeeklyProfitTotal() {
    const el = ensureWeeklyProfitTotal();
    if (!el) return;
    if (!w.showProfitLines) {
      el.style.display = 'none';
      return;
    }

    const ids = getCurrentWeekProfitEventIds();
    const idSet = new Set(ids);
    Array.from(w.weeklyProfitBreakups.keys()).forEach(id => {
      if (!idSet.has(id)) w.weeklyProfitBreakups.delete(id);
    });

    let total = 0;
    let quoteTotal = 0;
    let materialCostTotal = 0;
    let ready = 0;
    w.weeklyProfitBreakups.forEach(value => {
      if (typeof value === 'number' && Number.isFinite(value)) {
        total += value;
        ready += 1;
        return;
      }
      if (value && typeof value === 'object') {
        if (typeof value.profit === 'number' && Number.isFinite(value.profit)) {
          total += value.profit;
          ready += 1;
        }
        if (typeof value.quoteAmount === 'number' && Number.isFinite(value.quoteAmount)) {
          quoteTotal += value.quoteAmount;
        }
        if (typeof value.materialCost === 'number' && Number.isFinite(value.materialCost)) {
          materialCostTotal += value.materialCost;
        }
      }
    });

    const month = getCurrentProfitMonth();
    const monthEvents = getCurrentMonthProfitEvents();
    const monthKey = getMonthProfitKey(monthEvents);
    const monthState = w.monthlyProfitState || {};
    const monthData = monthState.key === monthKey ? (monthState.data || null) : null;
    const monthLoading = monthState.key === monthKey && monthState.loading;
    const caretIcon = w.monthlyProfitExpanded ? 'fa-caret-down' : 'fa-caret-up';
    const countdownText = w.profitAutoHidePaused ? 'Pinned' : `${Math.max(0, Number(w.profitCountdownRemaining || PROFIT_AUTO_HIDE_SECONDS))}s`;
    const monthlyHtml = w.monthlyProfitExpanded ? `
      <div style="margin-top:9px;padding-top:8px;border-top:1px solid rgba(255,255,255,.24);">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:12px;margin-bottom:4px;">
          <span>Month Profit</span>
          <span style="opacity:.9;">1-${month.days} ${month.label}</span>
        </div>
        ${monthLoading ? `
          <div style="font-size:13px;opacity:.9;">Calculating month...</div>
        ` : monthData ? `
          <div style="font-size:18px;line-height:1;">${fmtInr(monthData.total)}</div>
          <span style="font-size:11px;opacity:.88;">${monthData.count} installs</span>
          <div style="margin-top:6px;font-size:12px;line-height:1.25;">${fmtInr(monthData.quoteTotal)} - ${fmtInr(monthData.materialCostTotal)}</div>
        ` : `
          <div style="font-size:13px;opacity:.9;">Tap to calculate month</div>
        `}
      </div>
    ` : '';

    el.innerHTML = `

      <div style="display:flex;align-items:center;justify-content:space-between;gap:7px;font-size:13px;margin-bottom:4px;">

        <span>Weekly Profit</span>
        <span style="display:inline-flex;align-items:center;gap:6px;">
          <button type="button" id="weekly-profit-countdown" data-profit-countdown-pause aria-label="Pause profit auto hide timer" title="${w.profitAutoHidePaused ? 'Profit will stay visible until you tap the rupee button' : 'Tap to keep profit visible'}" style="height:24px;min-width:42px;border:0;border-radius:999px;background:rgba(255,255,255,.2);color:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;padding:0 8px;font-size:11px;font-weight:700;">
            ${countdownText}
          </button>
          <button type="button" data-month-profit-toggle aria-label="${w.monthlyProfitExpanded ? 'Hide month profit' : 'Show month profit'}" title="${w.monthlyProfitExpanded ? 'Hide month profit' : 'Show month profit'}" style="width:24px;height:24px;border:0;border-radius:6px;background:rgba(255,255,255,.16);color:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;padding:0;">
            <i class="fa-solid ${caretIcon}"></i>
          </button>
        </span>
      </div>
      <div style="font-size:20px;line-height:1;">${fmtInr(total)}</div>
      <span style="font-size:11px;opacity:.88;margin-bottom:3px;">${ids.length} installs</span>

      <div style="margin-top:8px;padding-top:7px;border-top:1px solid rgba(255,255,255,.24);font-size:13px;line-height:1.25;">
        ${fmtInr(quoteTotal)} - ${fmtInr(materialCostTotal)}
      </div>
       <div style="margin-top:2px;font-size:11px;opacity:.9;">Payments - Material</div>
       ${monthlyHtml}
    `;
    el.style.display = '';
    updateProfitCountdownDisplay();
    startMonthlyProfitLoad();
  }

  function resetWeeklyProfitTotal() {
    w.weeklyProfitBreakups.clear();
    renderWeeklyProfitTotal();
  }

  async function computeAndInsertProfit(ev, containerDiv) {
    try {
      const role = getCookie('auth_role');
      if (role !== 'admin') return;
      if (!w.showProfitLines) return;
      const namesEl = containerDiv.querySelector('.names');
      if (!namesEl) return;
      const eventId = String(ev && ev.id || '').trim();
      if (eventId) {
        w.weeklyProfitBreakups.set(eventId, { profit: null, quoteAmount: null, materialCost: null });
        renderWeeklyProfitTotal();
      }
      const profitEl = document.createElement('div');
      profitEl.className = 'profit-line';
      profitEl.style.display = 'flex';
      profitEl.textContent = 'Calculating...';
      namesEl.insertAdjacentElement('afterend', profitEl);
      const breakup = await calculateProfitBreakup(ev);
      if (breakup && typeof breakup.profit === 'number' && Number.isFinite(breakup.profit)) {
        profitEl.textContent = `${fmtInr(breakup.quoteAmount)} - ${fmtInr(breakup.materialCost)} = ${fmtInr(breakup.profit)}`;
        profitEl.dataset.profitValue = String(breakup.profit);
        profitEl.dataset.quoteAmount = String(breakup.quoteAmount);
        profitEl.dataset.materialCost = String(breakup.materialCost);
        profitEl.dataset.ready = 'true';
        if (eventId) w.weeklyProfitBreakups.set(eventId, breakup);
      } else {
        profitEl.textContent = 'n/a';
        profitEl.dataset.ready = 'true';
        if (eventId) w.weeklyProfitBreakups.set(eventId, { profit: null, quoteAmount: null, materialCost: null });
      }
      renderWeeklyProfitTotal();
    } catch (err) {
      console.warn('Failed to compute profit for event', ev && ev.id, err);
    }
  }

  function ensureProfitToggle() {
    const role = getCookie('auth_role');
    if (role !== 'admin') return;
    renderWeeklyProfitTotal();
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
    if (!w.showProfitLines) {
      w.profitAutoHidePaused = false;
      w.profitCountdownRemaining = 0;
      clearProfitAutoHideTimers();
      const lines = document.querySelectorAll('.event .profit-line');
      lines.forEach(el => el.style.display = 'none');
      renderWeeklyProfitTotal();
      return;
    }
    resetWeeklyProfitTotal();
    updateProfitLineVisibility();
    startProfitAutoHideCountdown();
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
      renderWeeklyProfitTotal();
      return;
    }
    events.forEach(evDiv => {
      const id = evDiv.dataset.id;
      let line = evDiv.querySelector('.profit-line');
      if (line) {
        line.style.display = 'flex';
        const profit = parseFloat(String(line.dataset.profitValue || '').replace(/[^0-9.-]/g, ''));
        const quoteAmount = parseFloat(String(line.dataset.quoteAmount || '').replace(/[^0-9.-]/g, ''));
        const materialCost = parseFloat(String(line.dataset.materialCost || '').replace(/[^0-9.-]/g, ''));
        if (!isNaN(profit)) {
          w.weeklyProfitBreakups.set(String(id), {
            profit,
            quoteAmount: !isNaN(quoteAmount) ? quoteAmount : null,
            materialCost: !isNaN(materialCost) ? materialCost : null
          });
          renderWeeklyProfitTotal();
          return;
        }
        line.remove();
      }
      const installs = Array.isArray(w.allInstalls) ? w.allInstalls : [];
      const ev = installs.find(x => String(x && x.id) === String(id));
      if (ev) computeAndInsertProfit(ev, evDiv);
    });
    renderWeeklyProfitTotal();
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
    div.style.position = 'relative';
    div.dataset.id = ev.id;
    div.draggable = false;

    const esc = typeof window.escapeHtml === 'function' ? window.escapeHtml : (s) => String(s ?? '');
    const monitorIcon = ev.monitor ? '<i class="fas fa-tv" title="Monitor"></i>' : '';
    const rackIcon = ev.rack ? '<i class="fas fa-server" title="Rack"></i>' : '';
    const historyIcon = isAdminRole() ? `<i class="fas fa-clock-rotate-left" onclick="openInstallHistory('${ev.id}')" title="History"></i>` : '';
    const installInlineMapHtml = buildInstallInlineMapHtml(ev, esc);
    const installMapToggleHtml = installInlineMapHtml
      ? `<button class="install-map-inline-toggle" type="button" data-install-map-toggle="${esc(ev.id || '')}" aria-label="Toggle inline map" aria-expanded="false"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></button>`
      : '';
    div.innerHTML = `
      ${buildCityBadgeHtml(ev.city, esc)}
      <h3><span onclick="editInstall('${ev.id}')" ${ev.fully_paid ? ' class="paid"' : ''}> ${String(ev.id || '').replace('-', '<br>')}</span><a class="name-link" href="tel:${ev.phone}">${esc(ev.name || 'No Name')}</a></h3>
      ${buildAdminEventCommentHtml(ev.admin_event_comment, ev.id, esc)}
      ${ev.notes ? `<div class="event-notes" style="background:#fffbcc;border-left:1px solid #f0b414;padding:4px 8px;margin:4px 0 9px;color:#333;"><strong>${esc(ev.notes)}</strong></div>` : ''}
      <div class="details">
        <span class="hdd">${esc((ev.hdd || '').toString().replace(/\s+/g, ''))}</span>
        <span class="cams"> ${esc(ev.resolution || '')} x <strong>${esc(ev.cams || 0)}</strong></span>
        <span class="cams type">${esc(ev.bullets || 0)} B + ${esc(ev.dome || 0)} D</span>
        <span class="area"><span id="dist-${esc(ev.id)}"></span> <a href="${esc(ev.map || '')}" target="_blank">${esc(ev.location || '')}</a>  @ ${esc(ev.time || '')} ${installMapToggleHtml}</span>
        <span class="monitor">${monitorIcon}</span> <span class="rack">${rackIcon}</span>
      </div>
      ${installInlineMapHtml ? `<div class="install-inline-map-wrap">${installInlineMapHtml}</div>` : ''}
      <div class="names">${(ev.lead_campaign && ev.lead_campaign.toString().trim().length < 8) ? `<span style="display:inline-flex;align-items:center;height:16px;background:#9ca3af;color:#fff;font-size:10px;line-height:1;padding:0 6px;border-radius:3px;margin-right:6px;font-weight:800;">${esc(ev.lead_campaign.toString().trim())}</span>` : ''}<strong>${esc(ev.owner || '')}</strong> | <a href="#" onclick="sendToWhatsapp(event, ${JSON.stringify(ev).replace(/"/g, '&quot;')})">${esc(ev.technician || '')}</a> | ${esc(ev.helper || '')}</div>
      <div class="icons">${historyIcon}<i class="fas fa-edit" onclick="editInstall('${ev.id}')" title="Edit"></i><i class="fas fa-trash" onclick="deleteInstall('${ev.id}')" title="Delete"></i></div>
    `;

    const origin = 'HSR Layout';
    const destination = ev.location ? `${ev.location}, Bengaluru` : '';
    if (destination && typeof window.requestInstallDistance === 'function') {
      window.requestInstallDistance({ origin, destination, mapUrl: ev.map || '', coords: (ev.map_lat && ev.map_lng) ? { lat: ev.map_lat, lng: ev.map_lng } : null })
        .then(data => {
          const safeId = (window.CSS && typeof window.CSS.escape === 'function')
            ? window.CSS.escape(String(ev.id))
            : String(ev.id).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
          const distEl = div.querySelector(`#dist-${safeId}`);
          if (!distEl) return;
          if (data && data.distance_km !== undefined) distEl.innerHTML = `<span class="distance">${Math.round(data.distance_km)}</span>`; else distEl.innerHTML = '';
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

  document.addEventListener('click', (e) => {
    const btn = e.target && e.target.closest ? e.target.closest('[data-admin-comment-delete]') : null;
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    deleteAdminEventComment(btn.getAttribute('data-admin-comment-delete'));
  }, true);

  function unscheduleEvent(id, srcDate) {
    const installs = Array.isArray(w.allInstalls) ? w.allInstalls : [];
    const ev = installs.find(x => String(x.id) === String(id));
    if (!ev) return;
    const fromDate = normalizeUnscheduledDate(srcDate) || normalizeUnscheduledDate(ev.date);
    const updatedEvent = {
      ...ev,
      notes: appendMovedFrom(ev.notes, fromDate),
      date: null
    };
    const order = loadUnscheduledOrder().filter(x => x !== String(ev.id));
    order.unshift(String(ev.id));
    saveUnscheduledOrder(order);
    if (typeof window.saveInstalls === 'function') window.saveInstalls(updatedEvent, { silent: true });
  }

  function isAdminRole() {
    const runtimeRole = String(w.INSTALLS_CURRENT_ROLE || '').trim().toLowerCase();
    const role = runtimeRole || (((document.cookie.match(/(?:^|; )auth_role=([^;]*)/) || [])[1] || '').trim().toLowerCase());
    return role === 'admin';
  }

  function canShowAdminEventCommentDelete() {
    return String(w.INSTALLS_CURRENT_ROLE || '').trim().toLowerCase() === 'admin';
  }

  function isPastCalendarDate(dateStr) {
    const raw = dateStr == null ? '' : String(dateStr).trim();
    if (!raw || raw === '0000-00-00' || raw.toLowerCase() === 'null') return false;
    const today = String(w.INSTALLS_TODAY || window.formatDate(new Date()) || '').trim();
    if (!today) return false;
    return raw < today;
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
  w.openInstallHistory = openInstallHistory;
  w.refreshInstallHistoryPanel = refreshInstallHistoryPanel;
  w.closeInstallHistory = closeInstallHistory;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initInstallHistoryPanel);
  } else {
    initInstallHistoryPanel();
  }

  if (!w.installInlineMapToggleBound) {
    w.installInlineMapToggleBound = true;
    document.addEventListener('click', (event) => {
      const toggle = event.target && event.target.closest ? event.target.closest('[data-install-map-toggle]') : null;
      if (!toggle) return;
      event.preventDefault();
      event.stopPropagation();
      const eventCard = toggle.closest('.event');
      if (!eventCard) return;
      const isOpen = eventCard.classList.toggle('map-inline-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  }

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
  window.openInstallHistory = w.openInstallHistory;
  window.refreshInstallHistoryPanel = w.refreshInstallHistoryPanel;
  window.closeInstallHistory = w.closeInstallHistory;

})(window);
