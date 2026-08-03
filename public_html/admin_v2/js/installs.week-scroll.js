'use strict';

(function (w) {
  const DESKTOP_MIN_WIDTH = 769;
  const PAGE_EDGE_PADDING = 3;
  const PULL_THRESHOLD = 360;
  const MIN_PULL_MS = 900;
  const RESET_MS = 700;
  const CHANGE_LOCK_MS = 1100;
  const TOUCH_AXIS_LOCK = 8;

  let pullDistance = 0;
  let pullDirection = 0;
  let pullStartedAt = 0;
  let resetTimer = null;
  let changeLocked = false;
  let indicator = null;
  let stylesReady = false;
  let touchStartX = 0;
  let touchStartY = 0;
  let touchLastY = 0;
  let touchTracking = false;
  let touchTarget = null; 

  function ensureStyles() {
    if (stylesReady) return;
    stylesReady = true;
    const style = document.createElement('style');
    style.id = 'week-scroll-styles';
    style.textContent = `
      #week-scroll-indicator {
        position: fixed;
        left: 50%;
        z-index: 99998;
        width: min(360px, calc(100vw - 32px));
        pointer-events: none;
        opacity: 0;
        transform: translateX(-50%) scale(.96);
        transition: opacity .18s ease, transform .18s ease;
      }
      #week-scroll-indicator.top { top: 86px; }
      #week-scroll-indicator.bottom { bottom: 18px; }
      #week-scroll-indicator.show {
        opacity: 1;
        transform: translateX(-50%) scale(1);
      }
      #week-scroll-indicator .week-scroll-card {
        padding: 12px 14px;
        border-radius: 12px;
        background: rgba(17, 24, 39, .94);
        color: #fff;
        text-align: center;
        box-shadow: 0 14px 38px rgba(15, 23, 42, .28);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        transition: transform .12s ease;
      }
      #week-scroll-indicator .week-scroll-kicker {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        opacity: .76;
      }
      #week-scroll-indicator .week-scroll-title {
        margin-top: 3px;
        font-size: 17px;
        font-weight: 900;
      }
      #week-scroll-indicator .week-scroll-bar {
        height: 4px;
        margin-top: 10px;
        overflow: hidden;
        border-radius: 999px;
        background: rgba(255, 255, 255, .22);
      }
      #week-scroll-indicator .week-scroll-bar span {
        display: block;
        width: 0%;
        height: 100%;
        border-radius: inherit;
        background: #22c55e;
        transition: width .08s linear;
      }
      #week-scroll-indicator .week-scroll-bar span.is-hot {
        background: #ef4444;
      }
      .calendar.week-elastic-next {
        animation: weekElasticNext .24s ease;
      }
      .calendar.week-elastic-prev {
        animation: weekElasticPrev .24s ease;
      }
      @keyframes weekElasticNext {
        50% { transform: translateY(-10px); }
      }
      @keyframes weekElasticPrev {
        50% { transform: translateY(10px); }
      }
    `;
    document.head.appendChild(style);
  }

  function isEditableTarget(target) {
    return !!(target && target.closest && target.closest(
      'input, textarea, select, [contenteditable="true"], .popup, .popup2, #order-popup'
    ));
  }

  function cloneDate(date) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
  }

  function addDays(date, days) {
    const next = cloneDate(date);
    next.setDate(next.getDate() + days);
    return next;
  }

  function formatWeekRange(monday) {
    const sunday = addDays(monday, 6);
    const start = monday.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
    const end = sunday.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
    return `${start} - ${end}`;
  }

  function getTargetWeek(direction) {
    const current = w.currentMonday ? cloneDate(w.currentMonday) : window.getMonday(new Date());
    return addDays(current, direction * 7);
  }

  function getIndicator() {
    if (indicator) return indicator;
    ensureStyles();
    indicator = document.createElement('div');
    indicator.id = 'week-scroll-indicator';
    indicator.setAttribute('aria-live', 'polite');
    indicator.innerHTML = `
      <div class="week-scroll-card">
        <div class="week-scroll-kicker"></div>
        <div class="week-scroll-title"></div>
        <div class="week-scroll-bar"><span></span></div>
      </div>
    `;
    document.body.appendChild(indicator);
    return indicator;
  }

  function renderIndicator(direction, progress) {
    const el = getIndicator();
    const targetWeek = getTargetWeek(direction);
    const card = el.querySelector('.week-scroll-card');
    const kicker = el.querySelector('.week-scroll-kicker');
    const title = el.querySelector('.week-scroll-title');
    const bar = el.querySelector('.week-scroll-bar span');

    el.className = direction < 0 ? 'show top' : 'show bottom';
    kicker.textContent = direction < 0 ? 'Previous week loading' : 'Next week loading';
    title.textContent = formatWeekRange(targetWeek);
    bar.style.width = `${Math.round(Math.min(progress, 1) * 100)}%`;
    bar.classList.toggle('is-hot', progress >= 0.8);
    card.style.transform = `translateY(${direction < 0 ? -1 : 1}px) scale(${1 + Math.min(progress, 1) * 0.025})`;
  }

  function hideIndicator() {
    if (!indicator) return;
    indicator.classList.remove('show', 'top', 'bottom');
    const bar = indicator.querySelector('.week-scroll-bar span');
    if (bar) {
      bar.style.width = '0%';
      bar.classList.remove('is-hot');
    }
  }

  function resetPull() {
    pullDistance = 0;
    pullDirection = 0;
    pullStartedAt = 0;
    if (resetTimer) {
      clearTimeout(resetTimer);
      resetTimer = null;
    }
    hideIndicator();
  }

  function getScrollElement() {
    return document.scrollingElement || document.documentElement || document.body;
  }

  function pageScrollEdge(direction) {
    const scroller = getScrollElement();
    const scrollTop = window.pageYOffset || scroller.scrollTop || 0;
    if (direction > 0) {
      const scrollHeight = Math.max(
        scroller.scrollHeight || 0,
        document.body ? document.body.scrollHeight : 0,
        document.documentElement ? document.documentElement.scrollHeight : 0
      );
      return scrollTop + window.innerHeight >= scrollHeight - PAGE_EDGE_PADDING;
    }

    return scrollTop <= PAGE_EDGE_PADDING;
  }

  function settleCurrentWeek(direction) {
    const scrollWeek = () => {
      const week = document.getElementById('currentWeek');
      if (week) {
        week.scrollIntoView({ behavior: 'smooth', block: direction > 0 ? 'start' : 'end' });
      }
    };

    const container = document.getElementById('calendarWeeks');
    if (!container || !window.MutationObserver) {
      setTimeout(scrollWeek, 350);
      return;
    }

    const observer = new MutationObserver(() => {
      observer.disconnect();
      requestAnimationFrame(scrollWeek);
    });
    observer.observe(container, { childList: true });
    setTimeout(() => {
      observer.disconnect();
      scrollWeek();
    }, 1400);
  }

  function commitWeekChange(direction) {
    resetPull();
    changeLocked = true;

    const calendar = document.querySelector('.calendar');
    if (calendar) {
      calendar.classList.add(direction > 0 ? 'week-elastic-next' : 'week-elastic-prev');
      setTimeout(() => calendar.classList.remove('week-elastic-next', 'week-elastic-prev'), 260);
    }

    if (typeof w.changeWeek === 'function') {
      w.changeWeek(direction);
      settleCurrentWeek(direction);
    }

    setTimeout(() => {
      changeLocked = false;
    }, CHANGE_LOCK_MS);
  }

  function handleCalendarWheel(event) {
    if (window.innerWidth < DESKTOP_MIN_WIDTH || changeLocked || isEditableTarget(event.target)) {
      return;
    }

    if (Math.abs(event.deltaY) <= Math.abs(event.deltaX) || Math.abs(event.deltaY) < 2) {
      return;
    }

    const direction = event.deltaY > 0 ? 1 : -1;
    if (!pageScrollEdge(direction)) {
      resetPull();
      return;
    }

    event.preventDefault();

    if (pullDirection !== direction) {
      pullDistance = 0;
      pullDirection = direction;
      pullStartedAt = Date.now();
    }

    pullDistance += Math.abs(event.deltaY);
    const elapsed = pullStartedAt ? Date.now() - pullStartedAt : 0;
    const progress = Math.min(pullDistance / PULL_THRESHOLD, elapsed / MIN_PULL_MS);
    renderIndicator(direction, progress);

    if (resetTimer) clearTimeout(resetTimer);
    resetTimer = setTimeout(resetPull, RESET_MS);

    if (pullDistance >= PULL_THRESHOLD && elapsed >= MIN_PULL_MS) {
      commitWeekChange(direction);
    }
  }

  function handleTouchStart(event) {
    if (window.innerWidth >= DESKTOP_MIN_WIDTH || changeLocked || event.touches.length !== 1 || isEditableTarget(event.target)) {
      touchTracking = false;
      return;
    }

    const touch = event.touches[0];
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;
    touchLastY = touch.clientY;
    touchTracking = true;
    touchTarget = event.target;
    resetPull();
  }

  function handleTouchMove(event) {
    if (!touchTracking || changeLocked || event.touches.length !== 1 || isEditableTarget(touchTarget)) {
      return;
    }

    const touch = event.touches[0];
    const totalX = touch.clientX - touchStartX;
    const totalY = touch.clientY - touchStartY;
    const stepY = touch.clientY - touchLastY;

    if (Math.abs(totalX) > Math.abs(totalY) || Math.abs(totalY) < TOUCH_AXIS_LOCK || Math.abs(stepY) < 1) {
      touchLastY = touch.clientY;
      return;
    }

    const direction = stepY < 0 ? 1 : -1;
    if (!pageScrollEdge(direction)) {
      resetPull();
      touchLastY = touch.clientY;
      return;
    }

    event.preventDefault();

    if (pullDirection !== direction) {
      pullDistance = 0;
      pullDirection = direction;
      pullStartedAt = Date.now();
    }

    pullDistance += Math.abs(stepY);
    touchLastY = touch.clientY;
    const elapsed = pullStartedAt ? Date.now() - pullStartedAt : 0;
    renderIndicator(direction, Math.min(pullDistance / PULL_THRESHOLD, elapsed / MIN_PULL_MS));

    if (pullDistance >= PULL_THRESHOLD && elapsed >= MIN_PULL_MS) {
      touchTracking = false;
      commitWeekChange(direction);
    }
  }

  function handleTouchEnd() {
    touchTracking = false;
    touchTarget = null;
    resetPull();
  }

  function initializeWeekScroll() {
    ensureStyles();
    window.addEventListener('wheel', handleCalendarWheel, { passive: false, capture: true });
    window.addEventListener('touchstart', handleTouchStart, { passive: true, capture: true });
    window.addEventListener('touchmove', handleTouchMove, { passive: false, capture: true });
    window.addEventListener('touchend', handleTouchEnd, { passive: true, capture: true });
    window.addEventListener('touchcancel', handleTouchEnd, { passive: true, capture: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeWeekScroll, { once: true });
  } else {
    initializeWeekScroll();
  }
})(window);
