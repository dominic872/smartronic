'use strict';

(function (w) {
  const WEEKS_PER_BATCH = 4;
  const INITIAL_PAST_WEEKS = 2;
  const INITIAL_FUTURE_WEEKS = 2;
  const EDGE_DISTANCE = 700;

  let earliestMonday = null;
  let latestMonday = null;
  let loadingPast = false;
  let loadingFuture = false;
  let scrollBound = false;
  let initialPositionPending = true;

  const buildDetailedWeek = w.buildWeek;

  function cloneDate(date) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
  }

  function shiftDays(date, days) {
    const shifted = cloneDate(date);
    shifted.setDate(shifted.getDate() + days);
    return shifted;
  }

  function weekKey(date) {
    return window.formatDate(date);
  }

  function weekRangeLabel(monday) {
    const sunday = shiftDays(monday, 6);
    const start = monday.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
    const end = sunday.toLocaleDateString('en-IN', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    });
    return `${start} - ${end}`;
  }

  function createTimelineWeek(monday) {
    const week = buildDetailedWeek(cloneDate(monday), 'current');
    const currentKey = weekKey(window.getMonday(new Date()));
    const key = weekKey(monday);

    week.classList.remove('current', 'prev', 'next', 'extended-week');
    week.classList.add('timeline-week');
    week.dataset.weekStart = key;

    if (key === currentKey) {
      week.classList.add('current');
      week.id = 'currentWeek';
    } else {
      week.removeAttribute('id');
    }

    const heading = document.createElement('div');
    heading.className = 'timeline-week-heading';
    heading.textContent = weekRangeLabel(monday);
    week.insertBefore(heading, week.firstChild);
    return week;
  }

  function appendWeeks(count) {
    const fragment = document.createDocumentFragment();
    let monday = cloneDate(latestMonday);

    for (let index = 0; index < count; index += 1) {
      monday = shiftDays(monday, 7);
      fragment.appendChild(createTimelineWeek(monday));
    }

    latestMonday = monday;
    w.weeksContainer.appendChild(fragment);
  }

  function prependWeeks(count) {
    const oldHeight = document.documentElement.scrollHeight;
    const oldScrollY = window.scrollY;
    const fragment = document.createDocumentFragment();
    const weeks = [];
    let monday = cloneDate(earliestMonday);

    for (let index = 0; index < count; index += 1) {
      monday = shiftDays(monday, -7);
      weeks.unshift(createTimelineWeek(monday));
    }

    weeks.forEach(week => fragment.appendChild(week));
    earliestMonday = monday;
    w.weeksContainer.insertBefore(fragment, w.weeksContainer.firstChild);

    const addedHeight = document.documentElement.scrollHeight - oldHeight;
    window.scrollTo(0, oldScrollY + addedHeight);
  }

  function loadPastWeeks() {
    if (loadingPast) return;
    loadingPast = true;
    prependWeeks(WEEKS_PER_BATCH);
    requestAnimationFrame(() => {
      loadingPast = false;
    });
  }

  function loadFutureWeeks() {
    if (loadingFuture) return;
    loadingFuture = true;
    appendWeeks(WEEKS_PER_BATCH);
    requestAnimationFrame(() => {
      loadingFuture = false;
    });
  }

  function handleInfiniteScroll() {
    if (window.scrollY < EDGE_DISTANCE) {
      loadPastWeeks();
    }

    const remaining = document.documentElement.scrollHeight - window.innerHeight - window.scrollY;
    if (remaining < EDGE_DISTANCE) {
      loadFutureWeeks();
    }
  }

  function bindInfiniteScroll() {
    if (scrollBound) return;
    scrollBound = true;
    window.addEventListener('scroll', handleInfiniteScroll, { passive: true });
  }

  w.buildCalendar = function buildInfiniteTimeline() {
    if (!w.weeksContainer) {
      w.weeksContainer = document.getElementById('calendarWeeks');
    }
    if (!w.weeksContainer) return;

    w.weeksContainer.innerHTML = '';

    const currentMonday = window.getMonday(new Date());
    earliestMonday = shiftDays(currentMonday, -INITIAL_PAST_WEEKS * 7);
    latestMonday = shiftDays(currentMonday, INITIAL_FUTURE_WEEKS * 7);

    const fragment = document.createDocumentFragment();
    for (
      let monday = cloneDate(earliestMonday);
      monday <= latestMonday;
      monday = shiftDays(monday, 7)
    ) {
      fragment.appendChild(createTimelineWeek(monday));
    }
    w.weeksContainer.appendChild(fragment);

    if (w.monthLabel) {
      w.monthLabel.textContent = 'INSTALLS TIMELINE';
    }
    if (typeof w.ensureWeeksToggle === 'function') {
      w.ensureWeeksToggle();
    }

    bindInfiniteScroll();

    if (initialPositionPending) {
      initialPositionPending = false;
      requestAnimationFrame(() => {
        const today = document.querySelector('.installs-v2 .day.today');
        if (today) today.scrollIntoView({ block: 'center' });
      });
    }
  };
})(window);
