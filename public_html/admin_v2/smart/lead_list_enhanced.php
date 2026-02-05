<?php
ob_start();
require_once '../auth.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../config.php';

// Check if the cookie exists and has the right value
if (
    !isset($_COOKIE['auth_role']) || 
    ($_COOKIE['auth_role'] !== 'admin' && $_COOKIE['auth_role'] !== 'market')
) {
    echo "No access";
    exit;
}

// Set timezone to India
date_default_timezone_set('Asia/Kolkata');

$isAdmin = isset($_COOKIE['auth_role']) && $_COOKIE['auth_role'] === 'admin';

$onlyMineCode = null;
if (isset($nameAssign)) {
  $na = strtolower(trim((string)$nameAssign));
  if (strpos($na, 'zoya') !== false) $onlyMineCode = 'zoy';
  else if (strpos($na, 'varsha') !== false) $onlyMineCode = 'var';
  else if (strpos($na, 'amreen') !== false) $onlyMineCode = 'amr';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
  <title>Lead List</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="lead_list_styles_enhanced.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
  <style>
    @media (max-width: 768px) {
      #main-logo {
        content: url('https://smartronic.online/content/uploads/2025/01/smartronic_small_logo.png');
        width: 40px;
        height: 40px;
      }
    }
    /* Spec styles moved to lead_list_styles_enhanced.css */
    .lead-comments {
      font-size: 14px;
      color: #000;
      margin-top: 5px;
      padding: 5px;
      background-color: #f9f9f9;
      border-radius: 4px;
      border: 1px solid #ccc;
      width: 100%;
    }
    .qr-placeholder {
        display: none !important;
    }
    .assign-badge {
        border: 1px solid #ccc;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        background-color: transparent;
        color: #555;
    }
    .lead-avatar-section h3 {
        margin: 0;
        padding: 0;
    }
    .idno-badge {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        background-color: #f8f9fa;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin: 0 auto;
    }
    .id-letter {
        font-size: 24px;
        font-weight: 800;
        color: #4F46E5;
        line-height: 1;
        margin-bottom: 2px;
    }
    .id-number {
        font-size: 16px;
        font-weight: 700;
        color: #374151;
        line-height: 1;
    }
    .prominent {
        background-color: #fffacd !important;
        border: 1px solid #f0e68c !important;
    }
    .status-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-badge.status-not-interested, .status-badge.status-wrong-number, .status-badge.status-dnc {
        background-color: #ffcdd2 !important;
    }
    .status-badge.status-follow-up {
        background-color: #fff9c4 !important;
    }
    .status-badge.status-busy, .status-badge.status-not-answering, .status-badge.status-cut-the-call {
        background-color: #eeeeee !important;
    }
    .close-edit-btn {
        position: absolute;
        top: -15px;
        right: 5px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: white;
        border: 1px solid #ccc;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 10;
        transition: all 0.2s;
        font-size: 18px;
        line-height: 1;
        padding-bottom: 2px; /* Visual center adjustment */
    }
    .close-edit-btn:hover {
        background: #f0f0f0;
        transform: scale(1.1);
        color: #d32f2f;
        border-color: #d32f2f;
    }

    /* --- FIXES START --- */
    /* Fix horizontal scroll in calendar */
    .datepicker-modal { max-width: 95vw; min-width: 280px; overflow-x: hidden; }
    .datepicker-container { padding: 0 !important; }
    .datepicker-content { padding: 0 !important; }
    .datepicker-date-display { overflow: hidden; }
    .datepicker-calendar-container { overflow: hidden; }

    /* Fix month font size */
    .datepicker-controls .month-display { font-size: 1.2rem !important; font-weight: 600; }

    /* Remove background from dropdown trigger and set font color to black */
    .select-dropdown.dropdown-trigger {
        background-color: transparent !important;
        color: #000 !important;
        border-bottom: 1px solid #9e9e9e !important;
    }
    
    /* Ensure input text is black */
    .input-field input[type=text]:not(.browser-default) {
        color: #000;
    }

    /* Fix flickering on hover by removing transform */
    .card.lead-card:hover { transform: none !important; }
    /* --- FIXES END --- */
  </style>
</head>
<body class="grey lighten-4">
  <!-- Header with Logo -->
  <div class="page-header" id="pageHeader">
    <div class="logo-section">
      <a href="/" class="custom-logo-link" rel="home" aria-current="page">
        <img id="main-logo" width="200" height="40" src="https://smartronic.online/content/uploads/2025/01/smarthome-black2.svg" class="custom-logo" alt="Smartronic | CCTV with Free Installation | Smart Home Automation" decoding="async">
      </a>
      <?php echo "<h2>Hello, $nameAssign!</h2>";?>
    </div>
    <div class="header-right">
      <div id="activeUsersBar" class="active-users-bar"></div>
    </div>
  </div>

  <div class="page-bg-text" id="pageBgText">Smartronic Lead List</div>

  <!-- Floating date indicator -->
  <div class="floating-date visible" id="floatingDate"></div>
  
  <div id="newLeadsFab" class="new-leads-fab">
    New <big>0</big> Lead
  </div>

  <button id="favoritesFab" class="favorites-btn" type="button" aria-label="Favourites">
    <i class="fa-regular fa-heart"></i>
  </button>
  <button id="historyFab" class="history-btn" type="button" aria-label="History">
    <i class="fa-solid fa-clock-rotate-left"></i>
  </button>

  <button id="floatingMenuToggle" class="floating-menu-btn" type="button" aria-label="Toggle floating buttons">
    <i class="fa-solid fa-xmark"></i>
  </button>
  
  <!-- Search Bar -->
  <div id="searchBar" class="hidden">
    <div class="search-left">
      <img class="search-logo" src="https://smartronic.online/content/uploads/2025/01/smartronic_small_logo.png" alt="Smartronic" decoding="async">
      <?php if ($isAdmin) { ?>
        <div class="mine-links">
          <a href="#" class="mine-filter-link active" data-mine="">All</a>
          <span class="mine-sep">|</span>
          <a href="#" class="mine-filter-link" data-mine="zoy">Zoya</a>
          <span class="mine-sep">|</span>
          <a href="#" class="mine-filter-link" data-mine="var">Varsha</a>
          <span class="mine-sep">|</span>
          <a href="#" class="mine-filter-link" data-mine="amr">Amreen</a>
        </div>
      <?php } else if (!empty($onlyMineCode)) { ?>
        <div class="mine-links">
          <a href="#" class="mine-filter-link" data-mine="<?php echo htmlspecialchars($onlyMineCode, ENT_QUOTES, 'UTF-8'); ?>">Only mine</a>
        </div>
      <?php } ?>
      <div class="follow-links">
        <span class="follow-label">Follow:</span>
        <a href="#" class="follow-filter-link" data-follow="yesterday">Yesterday</a>
        <span class="follow-sep">|</span>
        <a href="#" class="follow-filter-link" data-follow="today">Today</a>
        <span class="follow-sep">|</span>
        <a href="#" class="follow-filter-link" data-follow="tomorrow">Tomorrow</a>
      </div>
    </div>
    <div class="search-field">
      <input type="text" id="searchInput" list="searchSuggestions" autocomplete="off" placeholder="Search name, whatsapp, MID, date, area, comments" />
      <button type="button" id="searchSubmit" class="search-submit" aria-label="Search">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
    </div>
    <button type="button" id="clearBtn" class="search-clear-btn" aria-label="Clear">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <datalist id="searchSuggestions"></datalist>
  </div>

  <section class="crf-form-wrapper">
    <div class="container">
      <div class="crf-container">
        <div class="crf-left-col">

          <div id="container">
            <div id="top-sentinel"></div>
            <!-- rows go here -->
            <div id="bottom-sentinel"></div>
          </div>

          <div id="status">Loading...</div>

          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Fullscreen Quote Modal -->
<div id="quoteModal" class="quote-modal">
  <div class="quote-modal-content">
    <button id="closeQuoteModal" class="close-quote-btn"><i class="fa fa-times"></i></button>
    <iframe id="quoteIframe" src="" frameborder="0"></iframe>
  </div>
</div>


  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
  <script src="../js/install_form_component.js"></script>
  <script>

    let lastScrollDirection = 'down';
    let offsetStart = 0;
    let offsetEnd = 0;
    let isLoading = false;
    let searchQuery = '';
    let mineQuery = '';
    let followQuery = '';
    const PAGE_SIZE = 100;
    const MAX_ROWS = 200;
    let editedCards = new Map(); // Track edited cards
    let lastScrollCardsCount = 0; // Track card count for scroll detection
    let lastScrollPosition = 0; // Track scroll position
    const SCROLL_THRESHOLD = 500; // Scroll 1000px before auto-save
    let floatingDateVisible = false;
    let currentFloatingDate = '';
    const allLeadsMap = new Map(); // Store minimal data for all fetched leads to track stats accurately
    let favoritesViewActive = false;
    let historyViewActive = false;
    const FAVORITES_KEY = 'smartronic_favorites_mids';
    const HISTORY_KEY = 'smartronic_history_mids';
    let viewGeneration = 0;
    let listFetchController = null;
    let midsFetchController = null;

    function bumpViewGeneration() {
      viewGeneration += 1;
      if (listFetchController) {
        try { listFetchController.abort(); } catch (e) {}
        listFetchController = null;
      }
      if (midsFetchController) {
        try { midsFetchController.abort(); } catch (e) {}
        midsFetchController = null;
      }
    }

    function parseCommaList(value) {
      return String(value || '')
        .split(',')
        .map(v => v.trim())
        .filter(Boolean);
    }

    function readStoredList(key) {
      try {
        return parseCommaList(localStorage.getItem(key));
      } catch (e) {
        return [];
      }
    }

    function writeStoredList(key, values) {
      try {
        localStorage.setItem(key, values.join(','));
      } catch (e) {}
    }

    function normalizeMid(value) {
      const v = String(value || '').trim();
      return v;
    }

    function isFavoritedMid(mid) {
      const v = normalizeMid(mid);
      if (!v) return false;
      return readStoredList(FAVORITES_KEY).includes(v);
    }

    function toggleFavoriteMid(mid) {
      const v = normalizeMid(mid);
      if (!v) return { changed: false, isFav: false, reason: 'empty' };
      const list = readStoredList(FAVORITES_KEY);
      const idx = list.indexOf(v);
      if (idx >= 0) {
        list.splice(idx, 1);
        writeStoredList(FAVORITES_KEY, list);
        return { changed: true, isFav: false };
      }
      if (list.length >= 50) return { changed: false, isFav: false, reason: 'limit' };
      list.unshift(v);
      const next = Array.from(new Set(list)).slice(0, 50);
      writeStoredList(FAVORITES_KEY, next);
      return { changed: true, isFav: true };
    }

    function addToHistory(mid) {
      const v = normalizeMid(mid);
      if (!v) return;
      const list = readStoredList(HISTORY_KEY);
      const next = [v, ...list.filter(x => x !== v)].slice(0, 20);
      writeStoredList(HISTORY_KEY, next);
    }

    function setFavButtonState(btn, isFav) {
      if (!btn) return;
      btn.classList.toggle('is-fav', !!isFav);
      const icon = btn.querySelector('i');
      if (!icon) return;
      icon.className = isFav ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
    }

    function clearLeadListDom() {
      if (!container) return;
      container.querySelectorAll('.day-separator').forEach(el => el.remove());
      container.querySelectorAll('.col.s12').forEach(el => el.remove());
    }

    function loadMidsView(mode, mids) {
      const list = Array.isArray(mids) ? mids.map(normalizeMid).filter(Boolean) : [];
      const capped = Array.from(new Set(list)).slice(0, mode === 'favorites' ? 50 : 20);
      if (capped.length === 0) {
        showAutoSaveNotification(mode === 'favorites' ? 'No favourites saved yet' : 'No history yet', null, 'error');
        return;
      }

      bumpViewGeneration();
      const gen = viewGeneration;
      favoritesViewActive = mode === 'favorites';
      historyViewActive = mode === 'history';
      isLoading = false;
      lastScrollDirection = 'down';
      offsetStart = 0;
      offsetEnd = 0;
      editedCards.clear();
      allLeadsMap.clear();
      clearLeadListDom();

      status.innerText = 'Loading...';
      const midsCsv = capped.join(',');
      midsFetchController = new AbortController();
      fetch(`lead_fetch.php?mids=${encodeURIComponent(midsCsv)}`, { signal: midsFetchController.signal })
        .then(res => {
          if (gen !== viewGeneration) return null;
          if (!res.ok) throw new Error('Server error');
          return res.json();
        })
        .then(data => {
          if (gen !== viewGeneration) return;
          if (!Array.isArray(data) || data.length === 0) {
            status.innerText = 'No records.';
            return;
          }
          clearLeadListDom();
          data.forEach(row => {
            if (row.id && row.created_at) allLeadsMap.set(parseInt(row.id), row.created_at);
            const el = createRow(row);
            container.insertBefore(el, bottomSentinel);
          });
          rebuildDaySeparators();
          status.innerText = '';
          window.scrollTo({ top: 0, behavior: 'smooth' });
        })
        .catch(err => {
          if (gen !== viewGeneration) return;
          if (err && err.name === 'AbortError') return;
          console.error(err);
          status.innerText = 'Error loading records.';
        });
    }

    function exitMidsView() {
      favoritesViewActive = false;
      historyViewActive = false;
      resetStateAndReload();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function parseMysqlDateTime(value) {
      if (!value || value === '-') return null;
      const str = String(value).trim();
      const normalized = str
        .replace(/(\.\d{1,6})(?=(Z|[+-]\d{2}:?\d{2})?$)/, '')
        .replace(' ', 'T');
      const d = new Date(normalized);
      if (!isNaN(d.getTime())) return d;

      const m = str.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{1,6}))?)?)?(?:Z|([+-])(\d{2}):?(\d{2}))?$/);
      if (!m) return null;
      const year = parseInt(m[1], 10);
      const monthIndex = parseInt(m[2], 10) - 1;
      const day = parseInt(m[3], 10);
      const hour = m[4] ? parseInt(m[4], 10) : 0;
      const minute = m[5] ? parseInt(m[5], 10) : 0;
      const second = m[6] ? parseInt(m[6], 10) : 0;
      const sign = m[8];
      const tzHour = m[9] ? parseInt(m[9], 10) : null;
      const tzMin = m[10] ? parseInt(m[10], 10) : null;

      if (sign && tzHour !== null && tzMin !== null) {
        const offsetMinutes = (tzHour * 60 + tzMin) * (sign === '-' ? -1 : 1);
        const utcMs = Date.UTC(year, monthIndex, day, hour, minute, second) - offsetMinutes * 60 * 1000;
        return new Date(utcMs);
      }

      return new Date(year, monthIndex, day, hour, minute, second);
    }

    // Function to get relative time string
    function getRelativeTime(dateString) {
      if (!dateString || dateString === '-') return '';
      
      const date = parseMysqlDateTime(dateString);
      if (!date) return '';
      const now = new Date();
      const diffMs = now - date;
      const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
      
      if (diffDays === 0) return 'Today';
      if (diffDays === 1) return '1 day ago';
      if (diffDays === 2) return '2 days ago';
      if (diffDays === 3) return '3 days ago';
      if (diffDays <= 7) return 'This week';
      if (diffDays <= 14) return 'Last week';
      if (diffDays <= 21) return '2 weeks ago';
      if (diffDays <= 30) return '3 weeks ago';
      if (diffDays <= 60) return 'Last month';
      
      const months = Math.floor(diffDays / 30);
      if (months < 12) return `${months} months ago`;
      
      const years = Math.floor(months / 12);
      return `${years} year${years > 1 ? 's' : ''} ago`;
    }
    
    // Function to get HDD color class
    function getHddColorClass(hdd) {
      if (!hdd || hdd.toLowerCase() === 'dont-know') return '';
      const size = hdd.toLowerCase().replace(/\s/g, '');
      if (size.includes('500gb')) return 'hdd-500gb';
      if (size.includes('1tb')) return 'hdd-1tb';
      if (size.includes('2tb')) return 'hdd-2tb';
      if (size.includes('3tb')) return 'hdd-3tb';
      if (size.includes('4tb')) return 'hdd-4tb';
      if (size.includes('6tb')) return 'hdd-6tb';
      return '';
    }
    
    // Function to get DVR color class
    function getDvrColorClass(dvr) {
      if (!dvr || dvr.toLowerCase() === 'dont-know') return '';
      const type = dvr.toLowerCase();
      if (type === 'dvr') return 'dvr-dvr';
      if (type === 'nvr') return 'dvr-nvr';
      if (type === 'wifi') return 'dvr-wifi';
      return '';
    }
    function getCallStatusClass(status) {
        if (!status) return '';
        const s = status.toLowerCase();
        if (s.includes('ordered')) {
            return 'status-ordered';
        }
        if (s.includes('not interested') || s.includes('wrong number') || s.includes('dnc')) {
            return 'status-not-interested';
        }
        if (s.includes('follow up')) {
            return 'status-follow-up';
        }
        if (s.includes('busy') || s.includes('not answering') || s.includes('cut the call')) {
            return 'status-busy';
        }
        return '';
    }

    function getCallStatusIcon(status) {
        if (!status) return '';
        const s = status.toLowerCase();
        if (s.includes('ordered')) return '✅';
        if (s.includes('not answering')) return '📵';
        if (s.includes('busy')) return '⏳';
        if (s.includes('cut the call')) return '✂️';
        if (s.includes('not interested')) return '👎';
        if (s.includes('wrong number')) return '❌';
        if (s.includes('dnc')) return '🚫';
        if (s.includes('follow up')) return '📅';
        return '';
    }

    function formatDisplayId(id) {
      if (!id) return { letter: '', number: '' };
      // Handle L-123 format
      if (id.includes('-')) {
        const parts = id.split('-');
        return { letter: parts[0], number: parts[1] };
      }
      // Handle L123 format or just numbers
      if (id.length > 1 && isNaN(id[0])) {
         // Assuming first character is letter
         return { letter: id[0], number: id.substring(1) };
      }
      // Just numbers or other format
      return { letter: '', number: id };
    }

    // Generate unique color pattern from phone number
    function generateAvatarPattern(phone) {
      if (!phone) return { bg: '#f9f9f9', pattern: '#ccc', shape: '#999', shapeType: 'circle', bgPattern: 'stripes' };
      
      // Simple hash function
      let hash = 0;
      for (let i = 0; i < phone.length; i++) {
        hash = phone.charCodeAt(i) + ((hash << 5) - hash);
      }
      
      // Generate hue from hash (0-360)
      const hue = Math.abs(hash % 360);
      const saturation = 25 + (Math.abs(hash) % 15); // 25-40% for neutral colors
      const lightness = 80 + (Math.abs(hash >> 8) % 10); // 80-90% for light background
      
      const bgColor = `hsl(${hue}, ${saturation}%, ${lightness}%)`;
      const patternColor = `hsl(${hue}, ${saturation + 15}%, ${lightness - 15}%)`; // Slightly darker for pattern
      
      // Generate different hue for shape (offset by 120-240 degrees for contrast)
      const shapeHueOffset = 120 + (Math.abs(hash >> 4) % 120);
      const shapeHue = (hue + shapeHueOffset) % 360;
      const shapeColor = `hsl(${shapeHue}, 55%, 55%)`; // More saturated, medium lightness
      
      // Determine shape type based on hash
      const shapes = ['circle', 'square', 'diamond', 'hexagon'];
      const shapeType = shapes[Math.abs(hash >> 2) % shapes.length];
      
      // Determine background pattern type
      const patterns = ['stripes', 'dots', 'grid', 'zigzag'];
      const bgPattern = patterns[Math.abs(hash >> 6) % patterns.length];
      
      return { bg: bgColor, pattern: patternColor, shape: shapeColor, shapeType: shapeType, bgPattern: bgPattern };
    }

    // Pick a Font Awesome icon deterministically from a seed (phone/id)
    function getUniqueIconClass(seed) {
      const ICONS = [
        'fa-user', 'fa-camera', 'fa-video', 'fa-shield', 'fa-bolt',
        'fa-home', 'fa-map-marker-alt', 'fa-wrench', 'fa-cogs', 'fa-bell',
        'fa-key', 'fa-lock', 'fa-phone', 'fa-microchip', 'fa-wifi',
        'fa-satellite-dish', 'fa-robot', 'fa-store', 'fa-truck', 'fa-lightbulb'
      ];
      const str = String(seed || 'seed');
      let hash = 0;
      for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
      }
      const idx = Math.abs(hash) % ICONS.length;
      return ICONS[idx];
    }

    function updateCallStatusHighlight(selectElement) {
        const wrapper = selectElement.parentElement; // This should be the .select-wrapper from materialize
        if (!wrapper) return;

        // Clear existing status classes
        wrapper.classList.remove('status-not-interested', 'status-wrong-number', 'status-dnc', 'status-follow-up', 'status-busy', 'status-not-answering', 'status-cut-the-call');

        const selectedValue = selectElement.value;
        if (selectedValue === 'Not Interested' || selectedValue === 'Wrong Number' || selectedValue === 'DNC') {
            wrapper.classList.add('status-not-interested');
        } else if (selectedValue === 'Follow Up') {
            wrapper.classList.add('status-follow-up');
        } else if (selectedValue === 'Busy' || selectedValue === 'Not answering/ Switch Off' || selectedValue === 'Cut the call') {
            wrapper.classList.add('status-busy');
        }
    }

    function escapeHtml(str) {
      return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function escapeAttr(str) {
      return escapeHtml(str).replace(/`/g, '&#96;');
    }

    function buildTraceCalloutHtml(raw) {
      const trace = String(raw || '').trim();
      if (!trace) return '<div class="trace-empty">No history</div>';
      const parts = trace
        .split('>')
        .map(s => s.trim())
        .filter(Boolean);
      return `<div class="trace-list">${parts.map(p => `<div class="trace-item">${escapeHtml(p)}</div>`).join('')}</div>`;
    }

    let tracePopoverEl = null;
    let tracePopoverAnchorId = '';

    function ensureTracePopoverEl() {
      if (tracePopoverEl) return tracePopoverEl;
      const el = document.createElement('div');
      el.id = 'trace-popover';
      el.className = 'trace-popover';
      el.style.display = 'none';
      el.addEventListener('click', (e) => {
        e.stopPropagation();
      });
      document.body.appendChild(el);
      tracePopoverEl = el;
      return el;
    }

    function hideTracePopover() {
      if (!tracePopoverEl) return;
      tracePopoverEl.style.display = 'none';
      tracePopoverEl.style.visibility = '';
      tracePopoverEl.style.top = '';
      tracePopoverEl.style.left = '';
      tracePopoverAnchorId = '';
    }

    function countQuoteLinks(raw) {
      return String(raw || '')
        .split(',')
        .map(s => s.trim())
        .filter(Boolean)
        .length;
    }

    function base64DecodeUnicode(b64) {
      const s = String(b64 ?? '');
      return decodeURIComponent(Array.prototype.map.call(atob(s), (ch) => '%' + ('00' + ch.charCodeAt(0).toString(16)).slice(-2)).join(''));
    }

    function getQuoteMinMaxFromUrl(url) {
      try {
        const u = new URL(url, window.location.origin);
        const qstate = u.searchParams.get('qstate');
        if (!qstate) return { minQuote: 0, maxQuote: 0 };
        const state = JSON.parse(base64DecodeUnicode(qstate));
        return {
          minQuote: parseInt(state.minQuote, 10) || 0,
          maxQuote: parseInt(state.maxQuote, 10) || 0
        };
      } catch (_) {
        return { minQuote: 0, maxQuote: 0 };
      }
    }

    function getQuoteShortLabelFromUrl(url, opts) {
      try {
        const u = new URL(url, window.location.origin);
        const qstate = u.searchParams.get('qstate');
        if (qstate) {
          const state = JSON.parse(base64DecodeUnicode(qstate));
          const parts = [];
          const includeAmounts = !(opts && opts.includeAmounts === false);
          const category = (state.category || '').toString().trim();
          const brand = (state.brand || '').toString().trim();
          const mp = (state.mp || '').toString().trim();
          const hddSize = (state.hddSize || '').toString().trim().replace(/\s+/g, '');
          const cameraCount = parseInt(state.cameraCount, 10) || 0;
          const pct = parseInt(state.additionalPercentage, 10) || 0;
          const disc = parseInt(state.additionalDiscount, 10) || 0;
          const minQuote = parseInt(state.minQuote, 10) || 0;
          const maxQuote = parseInt(state.maxQuote, 10) || 0;

          if (category) parts.push(category);
          if (brand) parts.push(brand);
          if (mp && category !== 'NVR') parts.push(mp);
          if (cameraCount) parts.push(cameraCount + ' CAM');
          if (hddSize) parts.push(hddSize);
          if (pct) parts.push((pct > 0 ? '+' : '') + pct + '%');
          if (disc) parts.push('-₹' + disc.toLocaleString('en-IN'));
          if (includeAmounts && (minQuote || maxQuote)) {
            const minText = minQuote ? ('₹' + minQuote.toLocaleString('en-IN')) : '';
            const maxText = maxQuote ? ('₹' + maxQuote.toLocaleString('en-IN')) : '';
            parts.push([minText, maxText].filter(Boolean).join(' - '));
          }
          return parts.join(' | ');
        }

        const quote = u.searchParams.get('quote');
        if (quote) {
          const decoded = decodeURIComponent(quote);
          const params = decoded.split('|').map(p => p.trim());
          const cams = params[1] || '';
          const dvr = params[2] || '';
          const hdd = params[3] || '';
          const res = params[4] || '';
          const parts = [];
          if (dvr) parts.push(dvr);
          if (res && cams) parts.push(res + ' x ' + cams);
          if (hdd) parts.push(hdd);
          return parts.join(' | ');
        }
      } catch (_) {}
      return '';
    }

    function renderSavedQuotesLinks(quoteLinks) {
      const links = Array.isArray(quoteLinks) ? quoteLinks.filter(Boolean) : [];
      if (!links.length) return `<span class="saved-quotes-empty">No saved quotes</span>`;
      return links.map((u, idx) => `
        <a href="#" class="action-link action-quote open-quote saved-quote-link" data-quote-url="${escapeAttr(u)}">
          <span class="saved-quote-left">
            <i class="fa fa-file-invoice"></i>
            <span class="saved-quote-label">${escapeHtml(getQuoteShortLabelFromUrl(u, { includeAmounts: false }) || ('Quote ' + (links.length - idx)))}</span>
          </span>
          ${(() => {
            const mm = getQuoteMinMaxFromUrl(u);
            const minText = mm.minQuote ? ('₹' + mm.minQuote.toLocaleString('en-IN')) : '';
            const maxText = mm.maxQuote ? ('₹' + mm.maxQuote.toLocaleString('en-IN')) : '';
            const parts = [];
            if (minText) parts.push('MIN ' + minText);
            if (maxText) parts.push('MAX ' + maxText);
            const mmText = parts.join(' | ');
            return mmText ? `<span class="saved-quote-minmax">${escapeHtml(mmText)}</span>` : '';
          })()}
        </a>
      `).join('');
    }

    const ORDER_DRAFT_PREFIX = 'orderDraft:';

    function readOrderDraft(leadId) {
      if (!leadId) return null;
      try {
        const raw = localStorage.getItem(ORDER_DRAFT_PREFIX + String(leadId));
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== 'object') return null;
        return parsed;
      } catch (_) {
        return null;
      }
    }

    function writeOrderDraft(leadId, patch) {
      if (!leadId) return;
      try {
        const current = readOrderDraft(leadId) || {};
        const next = Object.assign({}, current, patch || {});
        localStorage.setItem(ORDER_DRAFT_PREFIX + String(leadId), JSON.stringify(next));
      } catch (_) {}
    }

    const LEAD_MARKERS_KEY = 'smartronic_lead_markers_v1';
    const MARKER_CONFIG = {
      1: { days: 1, color: '#94a3b8' },
      2: { days: 3, color: '#f59e0b' },
      3: { days: 7, color: '#10b981' }
    };
    let leadMarkersCache = null;

    function readLeadMarkers() {
      if (leadMarkersCache) return leadMarkersCache;
      try {
        const raw = localStorage.getItem(LEAD_MARKERS_KEY);
        if (!raw) return (leadMarkersCache = {});
        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) return (leadMarkersCache = {});
        leadMarkersCache = parsed;
        return leadMarkersCache;
      } catch (_) {
        return (leadMarkersCache = {});
      }
    }

    function writeLeadMarkers(next) {
      leadMarkersCache = next && typeof next === 'object' && !Array.isArray(next) ? next : {};
      try {
        localStorage.setItem(LEAD_MARKERS_KEY, JSON.stringify(leadMarkersCache));
      } catch (_) {}
    }

    function startOfDayMs(ts) {
      const d = new Date(ts);
      d.setHours(0, 0, 0, 0);
      return d.getTime();
    }

    function markerExpiryMs(setAtMs, days) {
      const d = startOfDayMs(setAtMs);
      return d + (Number(days) || 0) * 24 * 60 * 60 * 1000;
    }

    function isMarkerExpired(entry, nowMs) {
      if (!entry || typeof entry !== 'object') return true;
      const marker = Number(entry.marker);
      const cfg = MARKER_CONFIG[marker];
      if (!cfg) return true;
      const setAt = Number(entry.setAt);
      if (!setAt || !isFinite(setAt)) return true;
      const expiry = markerExpiryMs(setAt, cfg.days);
      return (Number(nowMs) || Date.now()) >= expiry;
    }

    function getLeadMarker(leadId) {
      const id = String(leadId || '').trim();
      if (!id) return null;
      const map = readLeadMarkers();
      const entry = map[id];
      if (!entry) return null;
      if (isMarkerExpired(entry, Date.now())) {
        const next = Object.assign({}, map);
        delete next[id];
        writeLeadMarkers(next);
        return null;
      }
      const marker = Number(entry.marker);
      return MARKER_CONFIG[marker] ? marker : null;
    }

    function setLeadMarker(leadId, markerId) {
      const id = String(leadId || '').trim();
      const marker = Number(markerId);
      if (!id) return null;
      const cfg = MARKER_CONFIG[marker];
      const map = readLeadMarkers();
      const next = Object.assign({}, map);
      const current = next[id];
      const currentMarker = current ? Number(current.marker) : null;
      if (!cfg) {
        delete next[id];
        writeLeadMarkers(next);
        return null;
      }
      if (currentMarker === marker && !isMarkerExpired(current, Date.now())) {
        delete next[id];
        writeLeadMarkers(next);
        return null;
      }
      next[id] = { marker, setAt: Date.now() };
      writeLeadMarkers(next);
      return marker;
    }

    function renderMarkerButtonsHtml(leadId) {
      const selected = getLeadMarker(leadId);
      return `
        <button type="button" class="lead-marker-btn marker-1${selected === 1 ? ' is-selected' : ''}" data-marker="1" aria-label="Marker 1" title="Resets at 12:00 AM (daily)"></button>
        <button type="button" class="lead-marker-btn marker-2${selected === 2 ? ' is-selected' : ''}" data-marker="2" aria-label="Marker 2" title="Resets at 12:00 AM (after 3 days)"></button>
        <button type="button" class="lead-marker-btn marker-3${selected === 3 ? ' is-selected' : ''}" data-marker="3" aria-label="Marker 3" title="Resets at 12:00 AM (after 7 days)"></button>
      `;
    }

    function applyMarkerDom(cardElement, markerId) {
      if (!cardElement) return;
      cardElement.classList.remove('marker-1', 'marker-2', 'marker-3');
      const marker = Number(markerId);
      if (MARKER_CONFIG[marker]) cardElement.classList.add(`marker-${marker}`);
      const buttons = cardElement.querySelectorAll('.lead-marker-btn');
      buttons.forEach((btn) => {
        const v = Number(btn.getAttribute('data-marker'));
        btn.classList.toggle('is-selected', MARKER_CONFIG[marker] && v === marker);
      });
    }

    function refreshAllMarkers() {
      if (!container) return;
      const now = Date.now();
      const map = readLeadMarkers();
      let changed = false;
      const next = Object.assign({}, map);
      Object.keys(next).forEach((leadId) => {
        if (isMarkerExpired(next[leadId], now)) {
          delete next[leadId];
          changed = true;
        }
      });
      if (changed) writeLeadMarkers(next);
      container.querySelectorAll('.lead-card').forEach((card) => {
        const leadId = (card.getAttribute('data-lead-id') || '').trim();
        applyMarkerDom(card, getLeadMarker(leadId));
      });
    }

    function scheduleMarkerSweep() {
      const now = new Date();
      const nextMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 2, 0);
      const delay = Math.max(1000, nextMidnight.getTime() - now.getTime());
      setTimeout(() => {
        refreshAllMarkers();
        scheduleMarkerSweep();
      }, delay);
    }

    function formatIsoToDisplay(iso) {
      const m = String(iso || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
      if (!m) return '';
      const year = parseInt(m[1], 10);
      const monthIndex = parseInt(m[2], 10) - 1;
      const day = parseInt(m[3], 10);
      if (!year || monthIndex < 0 || monthIndex > 11 || !day) return '';
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      return `${String(day).padStart(2, '0')} ${months[monthIndex]} ${String(year).slice(-2)}`;
    }

    // Function to create inline edit form
    function createInlineEditForm(leadData) {
      const rid = leadData.id || '';
      const code = `L-${rid}`;
      const qPhone = leadData.whatsapp_number || '';
      const qCams = leadData.num_cameras || '';
      const qDvr = leadData.dvr_type || '';
      const qHdd = leadData.hdd_size || '';
      const qRes = leadData.camera_resolution || '';
      const qName = leadData.Name || '';
      const quoteParam = encodeURIComponent(`${qPhone}|${qCams}|${qDvr}|${qHdd}|${qRes}|${qName}|${code}`);
      const quoteUrl = `quote.php?quote=${quoteParam}&lead_id=${encodeURIComponent(rid)}&mid=${encodeURIComponent(leadData.MID || leadData.mid || '')}`;
      const quoteLinksRaw = leadData.quote_links || '';
      const quoteLinks = quoteLinksRaw.split(',').map(s => s.trim()).filter(Boolean).slice().reverse();
      const draft = readOrderDraft(rid);
      const draftQuote = draft && draft.quote !== undefined && String(draft.quote).trim() !== '' ? String(draft.quote) : '';
      const quoteValue = draftQuote !== '' ? draftQuote : (leadData.quote || '');
      const installationIso = draft && draft.installation_date ? String(draft.installation_date) : '';
      const installationText = installationIso ? formatIsoToDisplay(installationIso) : 'Installation date';
      const isAlreadyOrdered = String(leadData.call_status || '').toLowerCase().includes('ordered');
      const lockOrderInputs = isAlreadyOrdered && String(quoteValue || '').trim() !== '' && String(installationIso || '').trim() !== '';
      
      return `
        <div class="inline-edit-form">
          <form class="compact-form" data-lead-id="${rid}">
            <input type="hidden" name="leadId" value="${rid}">
            <input type="hidden" name="whatsapp_number" value="${leadData.whatsapp_number || ''}">
            <input type="hidden" name="Message" value="${leadData.Message || ''}">
            <input type="hidden" name="map_link" value="${leadData.map_link || ''}">
            <div class="two-col-grid">
              <div class="grid-left">
                <div class="form-row">
                  <div class="input-group">
                    <label for="leadName-${rid}">Name</label>
                    <input id="leadName-${rid}" type="text" name="Name" value="${leadData.Name || ''}" placeholder="Enter Lead Name">
                  </div>
                  <div class="input-group">
                    <label for="leadArea-${rid}">Area</label>
                    <input id="leadArea-${rid}" type="text" name="Area" value="${leadData.Area || ''}" placeholder="Location">
                  </div>
                </div>

                <div class="form-row tech-specs-row">
                  <div class="input-group">
                    <label for="leadNumCameras-${rid}">Cameras</label>
                    <input id="leadNumCameras-${rid}" type="number" name="num_cameras" value="${leadData.num_cameras || ''}" placeholder="#">
                  </div>
                  <div class="input-group">
                    <label for="leadDvrType-${rid}">DVR Type</label>
                    <select id="leadDvrType-${rid}" name="dvr_type">
                      <option value="">Type</option>
                      <option value="DVR" ${leadData.dvr_type === 'DVR' ? 'selected' : ''}>DVR</option>
                      <option value="NVR" ${leadData.dvr_type === 'NVR' ? 'selected' : ''}>NVR</option>
                      <option value="WiFi" ${leadData.dvr_type === 'WiFi' ? 'selected' : ''}>WiFi</option>
                    </select>
                  </div>
                  <div class="input-group">
                    <label for="leadHdd-${rid}">HDD Size</label>
                    <select id="leadHdd-${rid}" name="hdd_size">
                      <option value="">TB</option>
                      <option value="500GB" ${leadData.hdd_size === '500GB' ? 'selected' : ''}>500GB</option>
                      <option value="1 TB" ${leadData.hdd_size === '1 TB' ? 'selected' : ''}>1 TB</option>
                      <option value="2 TB" ${leadData.hdd_size === '2 TB' ? 'selected' : ''}>2 TB</option>
                    </select>
                  </div>
                  <div class="input-group">
                    <label for="leadCameraResolution-${rid}">Res</label>
                    <select id="leadCameraResolution-${rid}" name="camera_resolution">
                      <option value="">MP</option>
                      <option value="2 MP" ${leadData.camera_resolution === '2 MP' ? 'selected' : ''}>2 MP</option>
                      <option value="5 MP" ${leadData.camera_resolution === '5 MP' ? 'selected' : ''}>5 MP</option>
                    </select>
                  </div>
                </div>

                <div class="form-row details-row">
                  <div class="input-group">
                    <label for="leadAssign-${rid}">Assign To</label>
                    <input id="leadAssign-${rid}" type="text" name="Assign" value="${leadData.Assign || ''}" placeholder="Staff">
                  </div>
                </div>

                <div class="form-row">
                  <div class="input-group full-width">
                    <label for="leadComments-${rid}">Notes:</label>
                    <textarea id="leadComments-${rid}" class="materialize-textarea" name="comments" rows="2" placeholder="Add notes..." required>${leadData.comments || ''}</textarea>
                  </div>
                </div>
              </div>

              <div class="grid-right">
                <!-- Moved QR Code here -->
                <div class="lead-qr-code" data-phone="${leadData.whatsapp_number || ''}" style="margin-bottom: 12px;"></div>

                <div class="input-group">
                  <label for="callStatus-${rid}">Call Status</label>
                  <select id="callStatus-${rid}" name="call_status">
                    <option value="" disabled ${!leadData.call_status ? 'selected' : ''}>Select Status</option>
                    <option value="Not answering/ Switch Off" class="left circle" ${leadData.call_status === 'Not answering/ Switch Off' ? 'selected' : ''}>📵 Not answering/ Switch Off</option>
                    <option value="Busy" class="left circle" ${leadData.call_status === 'Busy' ? 'selected' : ''}>⏳ Busy</option>
                    <option value="Cut the call" class="left circle" ${leadData.call_status === 'Cut the call' ? 'selected' : ''}>✂️ Cut the call</option>
                    <option value="Not Interested" class="left circle" ${leadData.call_status === 'Not Interested' ? 'selected' : ''}>👎 Not Interested</option>
                    <option value="Wrong Number" class="left circle" ${leadData.call_status === 'Wrong Number' ? 'selected' : ''}>❌ Wrong Number</option>
                    <option value="DNC" class="left circle" ${leadData.call_status === 'DNC' ? 'selected' : ''}>🚫 DNC</option>
                    <option value="Follow Up" class="left circle" ${leadData.call_status === 'Follow Up' ? 'selected' : ''}>📅 Follow Up</option>
                    <option value="Follow Up Tomorrow" class="left circle">☀️ Follow Up Tomorrow</option>
                    <option value="Ordered" class="left circle" ${leadData.call_status === 'Ordered' ? 'selected' : ''}>✅ Ordered</option>
                  </select>
                </div>
                <div class="input-group">
                  <label for="leadFollowUp-${rid}">Follow Up</label>
                  <input id="leadFollowUp-${rid}" type="text" name="Follow_up" value="${leadData.Follow_up || ''}" class="follow-up-input datepicker" placeholder="Select Date">
                </div>
              </div>
            </div>

            <div class="inline-edit-actions inline-edit-actions-split">
              <a href="#" class="cancel-edit-btn cancel-edit-link">Cancel</a>
              <button type="button" class="btn-base btn-primary save-lead-btn">
                <i class="fa fa-save"></i> Save Changes
              </button>
            </div>

            <div class="input-group saved-quotes-wrap">
              <div class="saved-quotes-grid">
                <div class="saved-quotes-left">
                  <a href="#" class="action-link action-quote open-quote quote-launch-link" data-quote-url="${quoteUrl}">
                    <i class="fa fa-file-invoice"></i> Open Quotation
                  </a>
                  <label class="place-order-label" for="leadQuote-${rid}">Place order</label>
                  <div class="install-date-row" ${lockOrderInputs ? 'data-locked="1"' : ''}>
                    <span class="install-date-text">${escapeHtml(installationText)}</span>
                    <button type="button" class="install-date-btn" aria-label="Select installation date" title="Select installation date" ${lockOrderInputs ? 'disabled' : ''}>
                      <i class="fa fa-calendar-alt"></i>
                    </button>
                    <input id="installDate-${rid}" type="text" class="installation-date-input datepicker" name="installation_date" value="${escapeAttr(installationIso)}" autocomplete="off" aria-hidden="true" tabindex="-1" ${lockOrderInputs ? 'readonly' : ''}>
                  </div>
                  <div class="quote-amount-wrap">
                    <input id="leadQuote-${rid}" type="text" name="quote" value="${escapeAttr(quoteValue)}" placeholder="Final Amount" ${lockOrderInputs ? 'readonly' : ''}>
                    <button type="button" class="place-order-btn quote-place-order-btn" aria-label="Place order" title="Place order" ${lockOrderInputs ? 'disabled' : ''}>
                      <i class="fa fa-check"></i>
                    </button>
                  </div>
                </div>
                <div class="saved-quotes-right">
                  <div class="saved-quotes-subtitle">Saved Quotes</div>
                  <div class="saved-quotes" data-lead-id="${rid}">
                    ${quoteLinks.length
                      ? quoteLinks.map((u, idx) => {
                          const label = getQuoteShortLabelFromUrl(u, { includeAmounts: false }) || ('Quote ' + (quoteLinks.length - idx));
                          const mm = getQuoteMinMaxFromUrl(u);
                          const minText = mm.minQuote ? ('₹' + mm.minQuote.toLocaleString('en-IN')) : '';
                          const maxText = mm.maxQuote ? ('₹' + mm.maxQuote.toLocaleString('en-IN')) : '';
                          const parts = [];
                          if (minText) parts.push('MIN ' + minText);
                          if (maxText) parts.push('MAX ' + maxText);
                          const mmText = parts.join(' | ');
                          return `
                            <a href="#" class="action-link action-quote open-quote saved-quote-link" data-quote-url="${escapeAttr(u)}">
                              <span class="saved-quote-left">
                                <i class="fa fa-file-invoice"></i>
                                <span class="saved-quote-label">${escapeHtml(label)}</span>
                              </span>
                              ${mmText ? `<span class="saved-quote-minmax">${escapeHtml(mmText)}</span>` : ``}
                            </a>
                          `;
                        }).join('')
                      : `<span class="saved-quotes-empty">No saved quotes</span>`
                    }
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      `;
    }

    // Function to toggle inline edit form
    function toggleInlineEdit(cardElement, leadData) {
      const formSection = cardElement.querySelector('.lead-form-section');
      
      // Check if already in edit mode
      const isEditing = formSection.querySelector('.inline-edit-form');
      
      if (isEditing) {
        // Cancel edit mode - clear the form section
        formSection.innerHTML = '';
        
        // Remove the data attribute indicating editing
        cardElement.removeAttribute('data-editing');
        
        // Remove from edited cards tracking
        const rid = leadData.id || '';
        editedCards.delete(rid);
      } else {
        // Enter edit mode - show inline form in portion 2
        const rid = leadData.id || '';
        formSection.innerHTML = `
          <div style="position: relative;">
            <button class="close-edit-btn">&times;</button>
            ${createInlineEditForm(leadData)}
          </div>
        `;
        
        // Mark this card as editing
        cardElement.setAttribute('data-editing', 'true');
        cardElement.setAttribute('data-lead-id', rid);
        
        // Add to edited cards tracking
        editedCards.set(rid, { element: cardElement, data: leadData });
        
        // Load QR code if not already loaded
        loadQRCode(cardElement);
        
        // Initialize Materialize components
        M.updateTextFields();
        const selectElements = cardElement.querySelectorAll('select');
        M.FormSelect.init(selectElements);

        const callStatusSelect = cardElement.querySelector(`#callStatus-${rid}`);
        if (callStatusSelect) {
            updateCallStatusHighlight(callStatusSelect);
            callStatusSelect.addEventListener('change', (e) => {
              updateCallStatusHighlight(e.target);
              
              const selectedValue = e.target.value;
              const followUpInput = cardElement.querySelector(`#leadFollowUp-${rid}`);
              
              if (selectedValue === 'Follow Up Tomorrow' || 
                  selectedValue === 'Not answering/ Switch Off' || 
                  selectedValue === 'Busy' || 
                  selectedValue === 'Cut the call') {
                
                // Calculate tomorrow's date
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const day = String(tomorrow.getDate()).padStart(2, '0');
                const month = months[tomorrow.getMonth()];
                const year = String(tomorrow.getFullYear()).slice(-2);
                const formattedDate = `${day} ${month} ${year}`;
                
                if (followUpInput) {
                  followUpInput.value = formattedDate;
                  // Trigger Materialize update if needed
                  M.updateTextFields();
                  
                  if (selectedValue === 'Follow Up Tomorrow') {
                    e.target.value = 'Follow Up';
                    // Re-init select to show the change
                    M.FormSelect.init(e.target);
                  }
                }
              } else if (selectedValue === 'Follow Up') {
                // Trigger datepicker
                if (followUpInput) {
                  const instance = M.Datepicker.getInstance(followUpInput);
                  if (instance) {
                    instance.open();
                  }
                }
              } else if (selectedValue === 'Not Interested' || selectedValue === 'Wrong Number' || selectedValue === 'DNC') {
                  // Set follow up to NA
                  if (followUpInput) {
                      followUpInput.value = 'NA';
                      M.updateTextFields();
                  }
              }
            });
        }
        
        // Initialize datepicker for Follow Up field
        const followUpInputs = cardElement.querySelectorAll('.follow-up-input');
        M.Datepicker.init(followUpInputs, {
          format: 'dd mmm yy',
          autoClose: true,
          showClearBtn: true,
          minDate: new Date()
        });

        const installInputs = cardElement.querySelectorAll('.installation-date-input');
        const minInstallDate = new Date();
        minInstallDate.setHours(0, 0, 0, 0);
        M.Datepicker.init(installInputs, {
          format: 'yyyy-mm-dd',
          autoClose: true,
          showClearBtn: true,
          minDate: minInstallDate,
          onSelect: function(date) {
            const input = this && this.el ? this.el : null;
            if (!input) return;
            const row = input.closest('.install-date-row');
            if (row && row.getAttribute('data-locked') === '1') return;
            const label = row ? row.querySelector('.install-date-text') : null;
            const btn = row ? row.querySelector('.install-date-btn') : null;
            const icon = btn ? btn.querySelector('i') : null;
            if (row) row.classList.remove('is-error');
            if (label) label.style.color = '';
            if (btn) btn.style.color = '';
            if (icon) icon.style.color = '';
            if (!label) return;
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const day = String(date.getDate()).padStart(2, '0');
            const month = months[date.getMonth()];
            const year = String(date.getFullYear()).slice(-2);
            const followUpFormatted = `${day} ${month} ${year}`;
            label.textContent = followUpFormatted;
            const leadId = input.closest('form') ? input.closest('form').dataset.leadId : '';
            if (leadId) writeOrderDraft(leadId, { installation_date: input.value });
            const followUpInput = input.closest('.lead-card') ? input.closest('.lead-card').querySelector('input[name="Follow_up"]') : null;
            if (followUpInput) {
              followUpInput.value = followUpFormatted;
              if (typeof M !== 'undefined') M.updateTextFields();
            }
          },
          onClose: function() {
            const input = this && this.el ? this.el : null;
            if (!input) return;
            const row = input.closest('.install-date-row');
            const label = row ? row.querySelector('.install-date-text') : null;
            if (!label) return;
            if (!input.value || !input.value.trim()) label.textContent = 'Installation date';
          }
        });
        for (const input of installInputs) {
          if (!input || !input.value || !input.value.trim()) continue;
          const row = input.closest('.install-date-row');
          const label = row ? row.querySelector('.install-date-text') : null;
          if (label) {
            const display = formatIsoToDisplay(input.value);
            label.textContent = display || 'Installation date';
          }
          const followUpInput = input.closest('.lead-card') ? input.closest('.lead-card').querySelector('input[name="Follow_up"]') : null;
          if (followUpInput) {
            const current = (followUpInput.value || '').trim();
            if (current === '' || current === '-' || current.toLowerCase() === 'na') {
              const display = formatIsoToDisplay(input.value);
              if (display) {
                followUpInput.value = display;
                if (typeof M !== 'undefined') M.updateTextFields();
              }
            }
          }
        }
        // Bind keyup events to update placeholders in real-time
        setupRealtimeFormBindings(cardElement, rid);

        // Scroll the card to the center of the screen
        setTimeout(() => {
            cardElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
      }
    }

    // Function to load QR code when editing
    function loadQRCode(cardElement) {
      const qrContainer = cardElement.querySelector('.lead-qr-code');
      if (!qrContainer) return;
      
      // Check if QR code already loaded
      if (qrContainer.querySelector('img')) return;
      
      const phone = qrContainer.getAttribute('data-phone');
      if (!phone) return;
      
      // Default to phone call (tel:)
      const callUrl = encodeURIComponent('tel:+91' + phone);
      qrContainer.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=96x96&data=${callUrl}&bgcolor=3498db&color=fff" alt="QR Code" title="Click to switch to WhatsApp" data-mode="call" data-phone="${phone}">`;
      
      // Add click event to toggle between call and WhatsApp
      const qrImg = qrContainer.querySelector('img');
      qrImg.addEventListener('click', function(e) {
        e.preventDefault();
        toggleQRMode(this);
      });
    }
    
    // Function to toggle QR code between call and WhatsApp
    function toggleQRMode(imgElement) {
      const currentMode = imgElement.getAttribute('data-mode');
      const phone = imgElement.getAttribute('data-phone');
      
      if (currentMode === 'call') {
        // Switch to WhatsApp
        const whatsappUrl = encodeURIComponent('https://wa.me/91' + phone);
        imgElement.src = `https://api.qrserver.com/v1/create-qr-code/?size=108x108&data=${whatsappUrl}&bgcolor=3498db&color=fff`;
        imgElement.setAttribute('data-mode', 'whatsapp');
        imgElement.setAttribute('title', 'Click to switch to Call');
        imgElement.classList.add('whatsapp-mode');
      } else {
        // Switch to Call
        const callUrl = encodeURIComponent('tel:+91' + phone);
        imgElement.src = `https://api.qrserver.com/v1/create-qr-code/?size=108x108&data=${callUrl}&bgcolor=3498db&color=fff`;
        imgElement.setAttribute('data-mode', 'call');
        imgElement.setAttribute('title', 'Click to switch to WhatsApp');
        imgElement.classList.remove('whatsapp-mode');
      }
    }

    // Helper to mask phone number
    function maskPhoneNumber(phone) {
      if (!phone) return '';
      // Remove non-digits
      const p = phone.replace(/\D/g, '');
      if (p.length < 5) return phone; // Too short to mask
      
      // Mask 3 digits in the middle
      // For 10 digit number: 9876543210 -> 9876XXX210
      // We keep first 4 and last 3 visible, mask 3 in between?
      // Or Keep first 2, mask 3, keep rest?
      
      // "mask the number 2-3 digits" - interpreting as masking 3 digits
      if (p.length >= 7) {
         const startLen = Math.floor((p.length - 3) / 2);
         const start = p.substring(0, startLen);
         const end = p.substring(startLen + 3);
         return `${start}***${end}`;
      }
      return phone;
    }

    function createRow(row) {
      const el = document.createElement('div');
      el.className = 'col s12';

      // Use display_id field which is either MID or id
      const displayId = row.display_id || row.MID || row.id || '';
      const rid = row.id || '';
      const code = `L-${rid}`;
      const name = row.Name || '';
      const displayName = name || 'NAME';
      const nameClass = name ? 'lead-name' : 'lead-name-placeholder';
      const phone = row.whatsapp_number || '';
      const cams = row.num_cameras || '';
      const dvr = row.dvr_type || '';
      const hdd = row.hdd_size || '';
      const res = row.camera_resolution || '';
      const assign = row.Assign || '-';
      const area = row.Area || '-';
      const follow = row.Follow_up || '-';
      const comment = row.comments || '-';
      const created = row.created_at || '-';
      const msg = row.Message || '-';
      const map = row.map_link || '';
      const call_status = row.call_status || '';
      const col1 = row.Column_1 || '';
      const originallyAssigned = (row.originally_assigned || '').trim();
      const editTrace = (row.edit_trace || '').trim();
      
      // Mask phone number
      const maskedPhone = maskPhoneNumber(phone);

      // Format date (dd mmm yy)
      let formattedDate = '-';
      let relativeTime = '';
      if (created && created !== '-') {
        const dateObj = parseMysqlDateTime(created);
        if (dateObj) {
          const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
          const day = String(dateObj.getDate()).padStart(2, '0');
          const month = months[dateObj.getMonth()];
          const year = String(dateObj.getFullYear()).slice(-2);
          formattedDate = `${day} ${month} ${year}`;
          relativeTime = getRelativeTime(created);
        }
      }

      // Format technical specs - replace dont-know with X
      const hddColorClass = getHddColorClass(hdd);
      const dvrColorClass = getDvrColorClass(dvr);
      const hddDisplay = (hdd && hdd.toLowerCase() !== 'dont-know') ? `<span class="spec-value ${hddColorClass}">${hdd}</span>` : '<span class="spec-unknown">X</span>';
      const resDisplay = (res && res.toLowerCase() !== 'dont-know') ? `<span class="spec-value">${res}</span>` : '<span class="spec-unknown">X</span>';
      const camDisplay = (cams && cams.toString().toLowerCase() !== 'dont-know') ? `<span class="spec-value">${cams}</span>` : '<span class="spec-unknown">X</span>';
      const recDisplay = (dvr && dvr.toLowerCase() !== 'dont-know') ? `<span class="spec-value ${dvrColorClass}">${dvr}</span>` : '<span class="spec-unknown">X</span>';

      // Generate unique avatar pattern for this phone number
      const avatarPattern = generateAvatarPattern(phone);
      
      // Generate pattern style based on pattern type
      let patternStyle = '';
      switch(avatarPattern.bgPattern) {
        case 'stripes':
          patternStyle = `background: repeating-linear-gradient(45deg, ${avatarPattern.bg} 0px, ${avatarPattern.bg} 8px, ${avatarPattern.pattern} 8px, ${avatarPattern.pattern} 16px);`;
          break;
        case 'dots':
          patternStyle = `background-color: ${avatarPattern.bg}; background-image: radial-gradient(${avatarPattern.pattern} 2px, transparent 2px); background-size: 10px 10px;`;
          break;
        case 'grid':
          patternStyle = `background-color: ${avatarPattern.bg}; background-image: linear-gradient(${avatarPattern.pattern} 1px, transparent 1px), linear-gradient(90deg, ${avatarPattern.pattern} 1px, transparent 1px); background-size: 8px 8px;`;
          break;
        case 'zigzag':
          patternStyle = `background: linear-gradient(135deg, ${avatarPattern.bg} 25%, transparent 25%) -8px 0, linear-gradient(225deg, ${avatarPattern.bg} 25%, transparent 25%) -8px 0, linear-gradient(315deg, ${avatarPattern.bg} 25%, transparent 25%), linear-gradient(45deg, ${avatarPattern.bg} 25%, transparent 25%); background-size: 16px 16px; background-color: ${avatarPattern.pattern};`;
          break;
      }
      
      const patternId = phone ? phone.slice(-4) : '0000'; // Last 4 digits for pattern recognition
      
      // Compute icon and shape class
      const shapeClass = `avatar-${avatarPattern.shapeType}`;
      const iconClassName = getUniqueIconClass(phone || rid || displayId || patternId);
      
      // Format ID for display
      const idParts = formatDisplayId(displayId);
      const midKey = normalizeMid(row.MID || displayId || rid);
      const isFav = isFavoritedMid(midKey);

      // ✅ Build the quote parameter string
      const quoteParam = encodeURIComponent(`${phone}|${cams}|${dvr}|${hdd}|${res}|${name}|${code}`);
      const quoteUrl = `quote.php?quote=${quoteParam}&lead_id=${encodeURIComponent(rid)}&mid=${encodeURIComponent(row.MID || '')}`;
      const quoteCount = countQuoteLinks(row.quote_links || '');
      const isOpenAssign = (assign || '').trim() === '' || (assign || '').trim().toLowerCase() === 'open';
      const isAssignOpenExact = (assign || '').trim().toLowerCase() === 'open';
      const showWas = isOpenAssign && originallyAssigned && originallyAssigned !== '-' && originallyAssigned.toLowerCase() !== 'na' && originallyAssigned.toLowerCase() !== 'open';
      const hasHistory = !!(editTrace && editTrace.trim() && editTrace.trim() !== '-');
      const markerId = getLeadMarker(rid);
      const markerClass = markerId ? `marker-${markerId}` : '';
      const markerButtonsHtml = renderMarkerButtonsHtml(rid);

      el.innerHTML = `
        <div class="card lead-card ${markerClass}"
             data-created-at="${created}"
             data-lead-id="${rid}"
             data-quote-links="${escapeAttr(row.quote_links || '')}"
             data-day-total="${row.day_total || ''}"
             data-day-rank="${row.day_rank || ''}"
             data-mid="${row.MID || ''}"
             data-column-1="${col1}"
             data-name="${name}"
             data-whatsapp-number="${phone}"
             data-num-cameras="${cams}"
             data-dvr-type="${dvr}"
             data-hdd-size="${hdd}"
             data-camera-resolution="${res}"
             data-assign="${assign}"
             data-originally-assigned="${escapeAttr(originallyAssigned)}"
             data-edit-trace="${escapeAttr(editTrace)}"
             data-area="${area}"
             data-follow-up="${follow}"
             data-comments="${comment}"
             data-message="${msg}"
             data-map-link="${map}"
             data-call-status="${call_status}">
          
          <!-- Portion 1: Lead Info -->
          <div class="lead-info-section" style="position: relative;">
            ${phone ? `<div class="lead-avatar-section edit-lead-btn" data-code="${code}" data-phone="${phone}" style="cursor: pointer;" title="Click to Edit">
               <div class="qr-placeholder" style="${patternStyle}">
                 <div class="avatar-shape ${shapeClass}" style="background-color: #ffffff; border: 1px solid #999; transform: rotate(-45deg);">
                   <i class="fa ${iconClassName} avatar-icon" style="color: ${avatarPattern.shape}; font-size: 16px;"></i>
                 </div>
               </div>
               <h3>
                 <span class="idno-badge">
                   <span class="id-letter">${idParts.letter}</span>
                   <span class="id-number">${idParts.number}</span>
                 </span>
               </h3>
               <div class="fav-toggle-wrap">
                 <button type="button" class="fav-toggle ${isFav ? 'is-fav' : ''}" data-mid="${midKey}" aria-label="Favourite">
                   <i class="${isFav ? 'fa-solid fa-heart' : 'fa-regular fa-heart'}"></i>
                 </button>
               </div>
               <!-- Mobile call and WhatsApp icons hidden by default, shown on mobile via CSS -->
               <div class="mobile-contact-icons" style="display: none;">
                 <a href="tel:+91${phone}" class="call-icon"><i class="fa fa-phone"></i></a>
                 <a href="https://wa.me/91${phone}" class="whatsapp-icon" target="_blank"><i class="fa fa-whatsapp"></i></a>
               </div>
            </div>` : ''}

            <div class="lead-info-content">
              <div class="lead-specs">
                <div class="spec-tag">
                  <span class="spec-label">HDD</span>
                  ${hddDisplay}
                </div>
                <div class="spec-tag">
                  <span class="spec-label">RES</span>
                  <div class="spec-row">
                    ${resDisplay} <span class="spec-x">x</span> ${camDisplay}
                  </div>
                </div>
                <div class="spec-tag">
                  <span class="spec-label">DVR</span>
                  ${recDisplay}
                </div>
              </div>

              <div class="lead-header" style="flex-direction: column; align-items: flex-start; gap: 2px;">
                <h3 class="${nameClass}" style="margin-bottom: 0;">${displayName}</h3>
                ${maskedPhone ? `<div class="lead-masked-phone" style="font-size: 13px; color: #666; font-family: monospace; letter-spacing: 0.5px; font-weight: 500;">${maskedPhone}</div>` : ''}
              </div>

              ${call_status ? `<div class="lead-status-row"><span class="status-badge ${getCallStatusClass(call_status)}">${getCallStatusIcon(call_status)} ${call_status}</span></div>` : ''}
              <div class="lead-marker-strip" data-lead-id="${rid}">
                ${markerButtonsHtml}
              </div>

              <div class="lead-meta-row">
                ${area && area !== '-' ? `<span class="lead-phone"><i class="fa fa-map-marker-alt"></i> ${area}</span>` : ''}
                ${follow && follow !== '-' ? `<span class="lead-phone"><i class="fa fa-calendar-alt"></i> ${follow}</span>` : ''}
              </div>
              ${comment && comment !== '-' ? `<div class="lead-comments">${comment}</div>` : ''}
            </div>
          </div>
          
          <!-- Portion 2: Expandable Form -->
          <div class="lead-form-section"></div>
          
          <!-- Portion 3: Controls -->
          <div class="lead-controls-section">
            <div class="lead-controls-left">
               ${assign && assign !== '-' ? `<span class="assign-badge${isAssignOpenExact ? ' is-open open-animate' : ''}">${assign}</span>` : ''}
               ${col1 ? `<span class="col1-badge">${col1}</span>` : ''}
               <span class="lead-phone" style="font-size: 12px;">${formattedDate}${relativeTime ? ` • ${relativeTime}` : ''}${showWas ? ` | Was: ${escapeAttr(originallyAssigned)}` : ''}</span>
            </div>
            <div class="lead-actions">
              ${hasHistory ? `
              <button type="button" class="trace-toggle" aria-label="History" title="History">
                <i class="fa-solid fa-clock-rotate-left"></i>
              </button>` : ''}
              <span class="quote-count-indicator ${quoteCount ? '' : 'is-empty'}" title="Quotes sent">
                <i class="fa fa-file-invoice"></i>
                <span class="quote-count-number">${quoteCount}</span>
              </span>
            </div>
          </div>
        </div>
      `;

      return el;
    }

    let daySeparatorObserver = null;

    function ensureDaySeparatorObserver() {
      if (daySeparatorObserver) return;
      daySeparatorObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          const el = entry.target;
          if (entry.isIntersecting) {
            if (el.dataset.inview === '1') return;
            el.dataset.inview = '1';
            el.classList.remove('day-separator-animate');
            void el.offsetWidth;
            el.classList.add('day-separator-animate');
          } else {
            el.dataset.inview = '0';
          }
        });
      }, {
        root: null,
        rootMargin: '-45% 0px -45% 0px',
        threshold: 0.01
      });
    }

    function observeDaySeparators() {
      if (!container) return;
      ensureDaySeparatorObserver();
      daySeparatorObserver.disconnect();
      container.querySelectorAll('.day-separator-inner').forEach((el) => {
        el.dataset.inview = '0';
        daySeparatorObserver.observe(el);
      });
    }

    function rebuildDaySeparators() {
      if (!container || !topSentinel || !bottomSentinel) return;

      container.querySelectorAll('.day-separator').forEach(el => el.remove());

      const cards = Array.from(container.querySelectorAll('.lead-card'));
      if (cards.length === 0) return;

      let lastDateKey = null;
      for (const card of cards) {
        const createdAt = card.getAttribute('data-created-at');
        const d = parseMysqlDateTime(createdAt);
        if (!d || isNaN(d.getTime())) continue;

        const dateKey = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        if (dateKey === lastDateKey) continue;
        lastDateKey = dateKey;

        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const label = `${String(d.getDate()).padStart(2, '0')} ${months[d.getMonth()]} ${String(d.getFullYear()).slice(-2)}`;

        const dayTotal = card.getAttribute('data-day-total');
        const totalText = dayTotal ? `${dayTotal} leads` : '';

        const sepOuter = document.createElement('div');
        sepOuter.className = 'day-separator';
        sepOuter.setAttribute('data-day', dateKey);
        sepOuter.innerHTML = `
          <div class="day-separator-inner">
            <div class="day-separator-date">${label}</div>
            ${totalText ? `<div class="day-separator-count">${totalText}</div>` : ''}
          </div>
        `;

        const wrapper = card.closest('.col');
        if (wrapper) {
          container.insertBefore(sepOuter, wrapper);
        } else {
          container.insertBefore(sepOuter, card);
        }
      }

      observeDaySeparators();
    }

    function fetchRows(direction = 'down') {
      if (favoritesViewActive || historyViewActive) return;
      if (isLoading) return;
      isLoading = true;
      status.innerText = 'Loading...';

      let fetchOffset;
      if (direction === 'down') {
        fetchOffset = offsetEnd;
      } else {
        if (offsetStart === 0) {
          status.innerText = '';
          isLoading = false;
          return;
        }
        fetchOffset = Math.max(0, offsetStart - PAGE_SIZE);
      }

      const gen = viewGeneration;
      listFetchController = new AbortController();
      fetch(`lead_fetch.php?offset=${fetchOffset}&search=${encodeURIComponent(searchQuery)}&mine=${encodeURIComponent(mineQuery)}&follow=${encodeURIComponent(followQuery)}`, { signal: listFetchController.signal })
        .then(res => {
          if (gen !== viewGeneration) return null;
          if (!res.ok) throw new Error("Server error");
          return res.json();
        })
        .then(data => {
          if (gen !== viewGeneration) return;
          if (!Array.isArray(data) || data.length === 0) {
            status.innerText = 'No more records.';
            isLoading = false;
            return;
          }

          if (direction === 'down') {
            data.forEach(row => {
              // Track lead data for stats
              if (row.id && row.created_at) {
                allLeadsMap.set(parseInt(row.id), row.created_at);
              }

              // Prevent duplicates
              if (!container.querySelector(`.lead-card[data-lead-id="${row.id}"]`)) {
                const el = createRow(row);
                container.insertBefore(el, bottomSentinel);
                // offsetEnd increment handled after loop
              }
            });
            // Always advance offsetEnd by the number of fetched items to keep in sync with DB offsets
            offsetEnd += data.length;
          } else {
            const firstRowBefore = topSentinel.nextElementSibling;
            const prevTop = firstRowBefore ? firstRowBefore.getBoundingClientRect().top : 0;

            const frag = document.createDocumentFragment();
            let addedCount = 0;
            
            for (let i = 0; i < data.length; i++) {
              // Track lead data for stats
              if (data[i].id && data[i].created_at) {
                allLeadsMap.set(parseInt(data[i].id), data[i].created_at);
              }

              // Prevent duplicates
              if (!container.querySelector(`.lead-card[data-lead-id="${data[i].id}"]`)) {
                frag.appendChild(createRow(data[i]));
                addedCount++;
              }
            }
            container.insertBefore(frag, topSentinel.nextSibling);

            if (addedCount > 0) {
                requestAnimationFrame(() => {
                  if (firstRowBefore) {
                    const newTop = firstRowBefore.getBoundingClientRect().top;
                    const delta = newTop - prevTop;
                    window.scrollBy(0, delta);
                  }
                });
            }

            // Update offsetStart based on fetched count, not added count, to keep SQL offsets in sync
            offsetStart = Math.max(0, offsetStart - data.length);
          }

          // Check if we've scrolled enough to trigger auto-save
          checkAndAutoSaveOnScroll();
          
          trimExcessRows();
          rebuildDaySeparators();
          status.innerText = '';
          isLoading = false;
        })
        .catch(err => {
          if (gen !== viewGeneration) return;
          if (err && err.name === 'AbortError') return;
          console.error(err);
          status.innerText = 'Error loading records.';
          isLoading = false;
        });
    }

    // ✅ Check if edited card has left the viewport and auto-save
    function checkAndAutoSaveOnScroll() {
      // Update floating date as user scrolls
      updateFloatingDate();
      
      if (editedCards.size === 0) return;

      const headerHeight = document.getElementById('pageHeader') ? document.getElementById('pageHeader').offsetHeight : 0;
      const searchHeight = document.getElementById('searchBar') ? document.getElementById('searchBar').offsetHeight : 0;
      const topOffset = headerHeight + searchHeight;
      const windowHeight = window.innerHeight;

      // Check each edited card
      editedCards.forEach((editedCard, leadId) => {
        const card = editedCard.element;
        const rect = card.getBoundingClientRect();

        // Check if card has scrolled completely out of view (top or bottom)
        // Leaving 50px buffer zone
        const isAbove = rect.bottom < topOffset + 50; 
        const isBelow = rect.top > windowHeight - 50;

        if (isAbove || isBelow) {
          console.log(`Card ${leadId} scrolled out of view. Auto-saving...`);
          
          const form = card.querySelector('form');
          if (form) {
            const formData = new FormData(form);
            const leadData = {};
            for (let [key, value] of formData.entries()) {
              leadData[key] = value;
            }
            
            saveLead(leadData, true, leadId);
            
            // Collapse
            const code = `L-${leadId}`;
            fetch(`lead_fetch.php?leadId=${encodeURIComponent(code)}`)
              .then(res => res.json())
              .then(data => {
                if (data && data.id) {
                  toggleInlineEdit(card, data);
                }
              })
              .catch(console.error);
          }
        }
      });
    }

    // Function to update floating date based on current scroll position
    function updateFloatingDate() {
      const floatingDateElement = document.getElementById('floatingDate');
      if (!floatingDateElement) return;
      
      // Get all lead cards
      const cards = document.querySelectorAll('.lead-card');
      if (cards.length === 0) return;
      
      // Always show the floating date
      if (!floatingDateVisible) {
        floatingDateElement.classList.add('visible');
        floatingDateVisible = true;
      }
      
      let closestCard = null;
      let closestDistance = Infinity;
      let closestCardDateObj = null;
      const scrollTop = window.scrollY || window.pageYOffset;
      const viewportCenter = scrollTop + (window.innerHeight / 2);
      
      cards.forEach(card => {
        const rect = card.getBoundingClientRect();
        const cardTop = rect.top + scrollTop;
        const cardCenter = cardTop + (rect.height / 2);
        const distance = Math.abs(viewportCenter - cardCenter);
        
        const createdAt = card.getAttribute('data-created-at');
        const d = parseMysqlDateTime(createdAt);
        if (!d || isNaN(d.getTime())) return;

        if (distance < closestDistance) {
          closestDistance = distance;
          closestCard = card;
          closestCardDateObj = d;
        }
      });
      
      // Display the closest card's data
      if (closestCard && closestCardDateObj) {
            // Format the card's date and time
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const cardDay = String(closestCardDateObj.getDate()).padStart(2, '0');
            const cardMonth = months[closestCardDateObj.getMonth()];
            const cardYear = String(closestCardDateObj.getFullYear()).slice(-2);
            const cardDateText = `${cardDay} ${cardMonth} ${cardYear}`;
            
            const cardHours = String(closestCardDateObj.getHours()).padStart(2, '0');
            const cardMinutes = String(closestCardDateObj.getMinutes()).padStart(2, '0');
            const cardTime = `${cardHours}:${cardMinutes}`;
            
            const closestDateOnly = new Date(closestCardDateObj.getFullYear(), closestCardDateObj.getMonth(), closestCardDateObj.getDate());
            let cardPosition = 0;
            let loadedDayCount = 0;
            cards.forEach(card => {
              const c = card.getAttribute('data-created-at');
              const d = parseMysqlDateTime(c);
              if (!d) return;
              const dateOnly = new Date(d.getFullYear(), d.getMonth(), d.getDate());
              if (dateOnly.getTime() !== closestDateOnly.getTime()) return;
              loadedDayCount++;
              if (card === closestCard) {
                cardPosition = loadedDayCount;
              }
            });

            const totalFromDom = parseInt(closestCard.getAttribute('data-day-total'), 10);
            const rankFromDom = parseInt(closestCard.getAttribute('data-day-rank'), 10);
            const hasTotal = Number.isFinite(totalFromDom) && totalFromDom > 0;
            const hasRank = Number.isFinite(rankFromDom) && rankFromDom > 0;

            const cardDateRecords = hasTotal ? totalFromDom : (loadedDayCount || '?');
            let cardPositionText = '?';
            if (hasTotal && hasRank) {
              cardPositionText = String(totalFromDom - rankFromDom + 1);
            } else if (Number.isFinite(cardDateRecords) && cardPosition > 0) {
              cardPositionText = String(cardDateRecords - cardPosition + 1);
            }
            
            // Update the display
            currentFloatingDate = cardDateText;
            
            // Determine background color based on card's time (hour)
            const cardHour = closestCardDateObj.getHours();
            let cardBgColor = 'rgb(172 172 172 / 84%)'; // Light grey for night (so text is visible)
            let textColor = 'black';
            
            if (cardHour >= 5 && cardHour < 12) {
              cardBgColor = 'rgba(173, 216, 230, 0.8)'; // Light blue (morning)
              textColor = 'black';
            } else if (cardHour >= 12 && cardHour < 17) {
              cardBgColor = 'rgba(255, 200, 100, 0.8)'; // Light orange (noon)
              textColor = 'black';
            } else if (cardHour >= 17 && cardHour < 20) {
              cardBgColor = 'rgba(200, 120, 60, 0.8)'; // Muted orange (evening)
              textColor = 'white';
            } else {
              cardBgColor = 'rgb(100 100 100 / 90%)'; // Darker grey for night
              textColor = 'white';
            }
            
            // Display card's date, time, and position within records from same date
            floatingDateElement.textContent = `${cardDateText} ${cardTime} (${cardPositionText}/${cardDateRecords})`;
            floatingDateElement.style.background = cardBgColor;
            floatingDateElement.style.color = textColor;
      }
    }

    // ✅ Auto-save all currently edited cards and collapse them
    function autoSaveAllEditedCards() {
      if (editedCards.size === 0) return;
      
      console.log('Auto-saving', editedCards.size, 'edited cards...');
      
      editedCards.forEach((editedCard, leadId) => {
        const card = editedCard.element;
        const form = card.querySelector('form');
        
        if (form && card.parentElement) { // Make sure card is still in DOM
          const formData = new FormData(form);
          const leadData = {};
          for (let [key, value] of formData.entries()) {
            leadData[key] = value;
          }
          
          console.log('Saving lead:', leadId, leadData);
          
          // Save and show notification
          saveLead(leadData, true, leadId);
          
          // Collapse the card after saving by fetching its data and toggling
          const code = `L-${leadId}`;
          fetch(`lead_fetch.php?leadId=${encodeURIComponent(code)}`)
            .then(res => {
              if (!res.ok) throw new Error("Failed to fetch lead details");
              return res.json();
            })
            .then(data => {
              if (data && data.id) {
                // Collapse the card back to view mode
                toggleInlineEdit(card, data);
              }
            })
            .catch(err => {
              console.error("Error fetching lead data for collapse:", err);
            });
        }
      });
      
      // Clear edited cards after saving
      editedCards.clear();
    }

    function trimExcessRows() {
      // Remove any empty row wrappers left behind by previous trims
      container.querySelectorAll('.col.s12').forEach(col => {
        if (!col.querySelector('.lead-card')) col.remove();
      });

      const items = Array.from(container.querySelectorAll('.lead-card'));
      if (items.length <= MAX_ROWS) return;

      const extra = items.length - MAX_ROWS;

      // Removed condition (offsetEnd - offsetStart > MAX_ROWS) to ensure we always trim DOM bloat
      if (lastScrollDirection === 'down') {
          // Check if we need to auto-save edited cards before removing them
          for (let i = 0; i < extra; i++) {
            const card = items[i];
            const leadId = card.getAttribute('data-lead-id');
            const isNew = card.getAttribute('data-is-new') === 'true';
            
            // If this card is being edited, auto-save it
            if (leadId && editedCards.has(leadId)) {
              const editedCard = editedCards.get(leadId);
              const form = card.querySelector('form');
              if (form) {
                const formData = new FormData(form);
                const leadData = {};
                for (let [key, value] of formData.entries()) {
                  leadData[key] = value;
                }
                
                // Save without showing default toast (we'll show auto-save notification with jump link)
                saveLead(leadData, false, leadId);
              }
              // Remove from tracking
              editedCards.delete(leadId);
            }
            
            const wrapper = card.closest('.col.s12');
            if (wrapper && wrapper.parentElement === container) wrapper.remove();
            else card.remove();
            
            // Only increment offset if no other copy of this lead exists (prevent offset inflation from duplicates)
            // Note: We just removed 'card', so we check if any OTHER card with same ID exists
            if (!container.querySelector(`.lead-card[data-lead-id="${leadId}"]`)) {
                // If it's a new lead inserted dynamically, removing it shouldn't affect DB offset
                if (!isNew) {
                    offsetStart++;
                }
            }
          }
        }
        else {
          // Check if we need to auto-save edited cards before removing them
          for (let i = 0; i < extra; i++) {
            const card = items[items.length - 1 - i];
            const leadId = card.getAttribute('data-lead-id');
            const isNew = card.getAttribute('data-is-new') === 'true';
            
            // If this card is being edited, auto-save it
            if (leadId && editedCards.has(leadId)) {
              const editedCard = editedCards.get(leadId);
              const form = card.querySelector('form');
              if (form) {
                const formData = new FormData(form);
                const leadData = {};
                for (let [key, value] of formData.entries()) {
                  leadData[key] = value;
                }
                
                // Save without showing default toast (we'll show auto-save notification with jump link)
                saveLead(leadData, false, leadId);
              }
              // Remove from tracking
              editedCards.delete(leadId);
            }
            
            const wrapper = card.closest('.col.s12');
            if (wrapper && wrapper.parentElement === container) wrapper.remove();
            else card.remove();
            
            // Only decrement offset if no other copy of this lead exists
            if (!container.querySelector(`.lead-card[data-lead-id="${leadId}"]`)) {
                // If it's a new lead inserted dynamically, removing it shouldn't affect DB offset
                if (!isNew) {
                    offsetEnd--;
                }
            }
          }
        }
    }

    function resetStateAndReload() {
      bumpViewGeneration();
      isLoading = false;
      favoritesViewActive = false;
      historyViewActive = false;
      const favBtn = document.querySelector('.favorites-btn');
      if (favBtn) favBtn.innerHTML = '<i class="fa-regular fa-heart"></i>';
      const histBtn = document.querySelector('.history-btn');
      if (histBtn) histBtn.innerHTML = '<i class="fa-solid fa-clock-rotate-left"></i>';
      lastScrollDirection = 'down';
      offsetStart = 0;
      offsetEnd = 0;
      allLeadsMap.clear(); // Clear stats when reloading
      editedCards.clear();
      container.querySelectorAll('.day-separator').forEach(el => el.remove());
      container.querySelectorAll('.col.s12').forEach(el => el.remove());
      fetchRows('down');
    }
    
    // ✅ Save lead data
    function saveLead(leadData, showNotification = true, leadId = null) {
      fetch('lead_save.php', {
        method: 'POST',
        body: JSON.stringify(leadData),
        headers: { 'Content-Type': 'application/json' }
      })
      .then(async response => {
        const text = await response.text();
        console.log('Raw response text:', text);

        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          throw new Error('Invalid JSON: ' + text);
        }

        if (data.success) {
          if (showNotification) {
            showAutoSaveNotification(data.message || 'Lead saved successfully', data.id || leadData.leadId);
          }

          const savedId = data.id || leadData.leadId || leadId;
          const savedCard = container ? container.querySelector(`.lead-card[data-lead-id="${savedId}"]`) : null;
          const savedMid = savedCard ? (savedCard.getAttribute('data-mid') || '') : '';
          addToHistory(savedMid || savedId);
          if (typeof data.assign === 'string' && data.assign.trim() !== '') {
            leadData.Assign = data.assign.trim();
          }
          if (typeof data.edit_trace === 'string' && data.edit_trace.trim() !== '') {
            leadData.edit_trace = data.edit_trace;
          }
          
          // ✅ update that card directly
          updateSingleCard(leadData, savedId);
        } else {
          if (showNotification) {
            showAutoSaveNotification(data.message || 'Failed to save lead', data.id || leadData.leadId, 'error');
          }
        }
      })
      .catch(error => {
        console.error('Fetch error:', error);
        if (showNotification) {
          showAutoSaveNotification('Error saving lead. Please try again.', leadId, 'error');
        }
      });
    }

    // ✅ Show auto-save notification at the top with jump link
    function showAutoSaveNotification(message, leadId = null, type = 'success') {
      // Remove existing notification if any
      const existingNotification = document.getElementById('auto-save-notification');
      if (existingNotification) {
        existingNotification.remove();
      }

      // Set background color based on type
      const backgroundColor = type === 'error' ? '#f44336' : '#4caf50';

      // Create notification element
      const notification = document.createElement('div');
      notification.id = 'auto-save-notification';
      notification.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background-color: ${backgroundColor};
        color: white;
        padding: 10px 0;
        z-index: 9999;
        font-weight: 500;
        animation: slideDown 0.3s ease-in-out;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
      `;
      notification.innerHTML = `
        <span>${message}</span>
        ${leadId ? `<a href="#" class="jump-to-card" data-lead-id="${leadId}" style="color: white; text-decoration: underline; cursor: pointer;">View</a>` : ''}
      `;

      // Add animation style
      if (!document.querySelector('style[data-auto-save]')) {
        const style = document.createElement('style');
        style.setAttribute('data-auto-save', 'true');
        style.textContent = `
          @keyframes slideDown {
            from {
              opacity: 0;
              transform: translateY(-100%);
            }
            to {
              opacity: 1;
              transform: translateY(0);
            }
          }
        `;
        document.head.appendChild(style);
      }

      document.body.appendChild(notification);

      // Auto-remove notification after 5 seconds
      setTimeout(() => {
        notification.style.animation = 'slideDown 0.3s ease-in-out reverse';
        setTimeout(() => {
          notification.remove();
        }, 300);
      }, 5000);

      // Add event listener for jump link
      if (leadId) {
        const jumpLink = notification.querySelector('.jump-to-card');
        if (jumpLink) {
          jumpLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove notification immediately
            notification.remove();
            
            // Try to find the card in current view using the edit button's data-code attribute
            const code = `L-${leadId}`;
            const editBtn = container.querySelector(`.edit-lead-btn[data-code="${code}"]`);
            
            if (editBtn) {
              const card = editBtn.closest('.lead-card');
              if (card) {
                // Scroll to the card
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Add temporary highlight effect
                card.style.transition = 'background-color 0.5s';
                card.style.backgroundColor = '#e8f5e9';
                setTimeout(() => {
                  card.style.backgroundColor = '';
                }, 2000);
              }
            } else {
              // Card not in current view, show message
              showAutoSaveNotification('Card may be out of view. Try scrolling to find it.', leadId, 'error');
            }
          });
        }
      }
    }

    // ✅ Update only the edited card without reloading the page
    function updateSingleCard(updatedData, leadId) {
      const code = `L-${leadId}`;
      const existingEditBtn = container.querySelector(`.edit-lead-btn[data-code="${code}"]`);
      if (!existingEditBtn) {
        console.warn('No matching card found to update.');
        return;
      }
      
      const existingCard = existingEditBtn.closest('.lead-card');
      const currentMid = existingCard ? (existingCard.getAttribute('data-mid') || '') : '';
      const currentCol1 = existingCard ? (existingCard.getAttribute('data-column-1') || '') : '';
      const currentQuoteLinks = existingCard ? (existingCard.getAttribute('data-quote-links') || '') : '';
      const currentCreatedAt = existingCard ? (existingCard.getAttribute('data-created-at') || '') : '';
      const currentDayTotal = existingCard ? (existingCard.getAttribute('data-day-total') || '') : '';
      const currentDayRank = existingCard ? (existingCard.getAttribute('data-day-rank') || '') : '';
      const currentOriginallyAssigned = existingCard ? (existingCard.getAttribute('data-originally-assigned') || '') : '';
      const currentEditTrace = existingCard ? (existingCard.getAttribute('data-edit-trace') || '') : '';

      const merged = {
        id: leadId,
        MID: currentMid,
        Column_1: currentCol1,
        created_at: currentCreatedAt,
        day_total: currentDayTotal,
        day_rank: currentDayRank,
        originally_assigned: currentOriginallyAssigned,
        edit_trace: currentEditTrace,
        Name: existingCard ? (existingCard.getAttribute('data-name') || '') : '',
        whatsapp_number: existingCard ? (existingCard.getAttribute('data-whatsapp-number') || '') : '',
        hdd_size: existingCard ? (existingCard.getAttribute('data-hdd-size') || '') : '',
        num_cameras: existingCard ? (existingCard.getAttribute('data-num-cameras') || '') : '',
        camera_resolution: existingCard ? (existingCard.getAttribute('data-camera-resolution') || '') : '',
        dvr_type: existingCard ? (existingCard.getAttribute('data-dvr-type') || '') : '',
        Assign: existingCard ? (existingCard.getAttribute('data-assign') || '') : '',
        Area: existingCard ? (existingCard.getAttribute('data-area') || '') : '',
        Follow_up: existingCard ? (existingCard.getAttribute('data-follow-up') || '') : '',
        comments: existingCard ? (existingCard.getAttribute('data-comments') || '') : '',
        Message: existingCard ? (existingCard.getAttribute('data-message') || '') : '',
        map_link: existingCard ? (existingCard.getAttribute('data-map-link') || '') : '',
        call_status: existingCard ? (existingCard.getAttribute('data-call-status') || '') : '',
        quote: updatedData.quote || '',
        quote_links: currentQuoteLinks
      };

      Object.keys(updatedData || {}).forEach((k) => {
        if (typeof updatedData[k] !== 'undefined' && updatedData[k] !== null) {
          merged[k] = updatedData[k];
        }
      });

      if (!merged.created_at) merged.created_at = new Date().toISOString();
      if (typeof merged.quote_links === 'undefined' || merged.quote_links === null || merged.quote_links === '') {
        merged.quote_links = currentQuoteLinks;
      }

      // Recreate the updated card using existing function
      const newCard = createRow(merged);

      const cardContainer = existingCard.closest('.col');
      cardContainer.replaceWith(newCard);

      // Apply gradient save effect on the new container
      const newCardEl = newCard.querySelector('.lead-card');
      if (newCardEl) {
          newCardEl.classList.add('save-gradient');
          setTimeout(() => {
            newCardEl.classList.remove('save-gradient');
          }, 4000); // show effect for 4 seconds
      }
    }

    let container, topSentinel, bottomSentinel, searchInput, clearBtn, status, searchSubmit, searchSuggestions; // Declare variables here
    
    document.addEventListener('DOMContentLoaded', function() {
      const bgImages = [
        'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1444703686981-a3abbc4d4fe3?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1462331940025-496dfbfc7564?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1470770903676-69b98201ea1c?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1487958449943-2429e8be8625?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1444723121867-7a241cacace9?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1524492412937-b28074a5d7da?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1547153760-18fc86324498?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1426604966848-d7adac402bff?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1528909514045-2fa4ac7a08ba?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1465146344425-f00d5f5c8f07?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1444044205806-38f3ed106c10?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1472214103451-9374bd1c798e?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1471879832106-c7ab9e0cee23?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1474511320723-9a56873867b5?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1496307042754-b4aa456c4a2d?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1437622368342-7a3d73a34c8f?auto=format&fit=crop&w=2400&q=80'
      ];
      const setBackgroundImage = (baseUrl) => {
        return new Promise((resolve, reject) => {
          const url = `${baseUrl}${baseUrl.includes('?') ? '&' : '?'}sig=${Date.now()}-${Math.floor(Math.random() * 1e9)}`;
          const img = new Image();
          img.referrerPolicy = 'no-referrer';
          img.onload = () => resolve(url);
          img.onerror = () => reject(new Error('Background image failed to load'));
          img.src = url;
        });
      };

      const trySetRandomBackground = async () => {
        const remaining = bgImages.slice();
        for (let attempt = 0; attempt < 8 && remaining.length > 0; attempt++) {
          const idx = Math.floor(Math.random() * remaining.length);
          const baseUrl = remaining.splice(idx, 1)[0];
          try {
            const loadedUrl = await setBackgroundImage(baseUrl);
            document.documentElement.style.setProperty('--page-bg-image', `url("${loadedUrl}")`);
            return;
          } catch (e) {
          }
        }
      };

      trySetRandomBackground();

      // Assign elements inside DOMContentLoaded
      container = document.getElementById('container');
      topSentinel = document.getElementById('top-sentinel');
      bottomSentinel = document.getElementById('bottom-sentinel');
      searchInput = document.getElementById('searchInput');
      clearBtn = document.getElementById('clearBtn');
      searchSubmit = document.getElementById('searchSubmit');
      searchSuggestions = document.getElementById('searchSuggestions');
      status = document.getElementById('status');
      const mineLinks = Array.from(document.querySelectorAll('.mine-filter-link'));
      const followLinks = Array.from(document.querySelectorAll('.follow-filter-link'));

      // Initialize lastScrollCardsCount
      lastScrollCardsCount = 0;

      // Event delegation for 'Edit Lead' buttons
      if (container) { // Check if container is not null before adding event listener
        container.addEventListener('click', function(event) {
          const favBtn = event.target.closest('.fav-toggle');
          if (favBtn) {
            event.preventDefault();
            event.stopPropagation();
            const mid = favBtn.getAttribute('data-mid') || '';
            const result = toggleFavoriteMid(mid);
            if (result.changed) {
              setFavButtonState(favBtn, result.isFav);
            } else if (result.reason === 'limit') {
              showAutoSaveNotification('Max 50 favourites allowed', null, 'error');
            }
            return;
          }

          const markerBtn = event.target.closest('.lead-marker-btn');
          if (markerBtn) {
            event.preventDefault();
            event.stopPropagation();
            const card = markerBtn.closest('.lead-card');
            const leadId = card ? (card.getAttribute('data-lead-id') || '').trim() : '';
            const marker = markerBtn.getAttribute('data-marker');
            const nextMarker = setLeadMarker(leadId, marker);
            if (card) applyMarkerDom(card, nextMarker);
            return;
          }

          const traceBtn = event.target.closest('.trace-toggle');
          if (traceBtn) {
            event.preventDefault();
            event.stopPropagation();
            const card = traceBtn.closest('.lead-card');
            if (!card) return;
            const leadId = (card.getAttribute('data-lead-id') || '').trim();
            if (tracePopoverAnchorId && tracePopoverAnchorId === leadId) {
              hideTracePopover();
              return;
            }

            const pop = ensureTracePopoverEl();
            pop.innerHTML = buildTraceCalloutHtml(card.getAttribute('data-edit-trace') || '');
            pop.style.display = 'block';
            pop.style.visibility = 'hidden';

            const popRect = pop.getBoundingClientRect();
            const btnRect = traceBtn.getBoundingClientRect();

            let top = btnRect.top - popRect.height - 10;
            if (top < 10) top = btnRect.bottom + 10;

            let left = btnRect.right - popRect.width;
            left = Math.max(10, Math.min(left, window.innerWidth - popRect.width - 10));

            pop.style.top = `${Math.round(top)}px`;
            pop.style.left = `${Math.round(left)}px`;
            pop.style.visibility = 'visible';
            tracePopoverAnchorId = leadId;
            return;
          }

          // Handle edit button clicks
          if (event.target.closest('.edit-lead-btn')) {
            event.preventDefault(); // Prevent default link behavior
            const editBtn = event.target.closest('.edit-lead-btn');
            const code = editBtn.dataset.code;
            
            // Check if there's another card in edit mode and collapse it
            const editingCard = container.querySelector('[data-editing="true"]');
            if (editingCard) {
              const editingCode = editingCard.getAttribute('data-lead-id');
              const prefixedCode = `L-${editingCode}`;
              
              // Only collapse if it's a different card
              if (prefixedCode !== code) {
                // Get form data and save before collapsing
                const form = editingCard.querySelector('form');
                if (form) {
                  const formData = new FormData(form);
                  const leadData = {};
                  for (let [key, value] of formData.entries()) {
                    leadData[key] = value;
                  }
                  
                  // Save without showing default toast (we'll show auto-save notification with jump link)
                  saveLead(leadData, false, editingCode);
                }
                
                // Fetch lead data and collapse the card
                fetch(`lead_fetch.php?leadId=${encodeURIComponent(prefixedCode)}`)
                  .then(res => {
                    if (!res.ok) throw new Error("Failed to fetch lead details");
                    return res.json();
                  })
                  .then(data => {
                    if (data && data.id) {
                      toggleInlineEdit(editingCard, data);
                    }
                  })
                  .catch(err => {
                    console.error("Error fetching lead data:", err);
                  });
              }
            }
            
            // Prefer local data attached to the card to avoid backend dependency
            const cardElement = editBtn.closest('.lead-card');
            const ds = cardElement ? cardElement.dataset : {};
            const rid = code && code.startsWith('L-') ? code.slice(2) : code;
            if (cardElement && ds.leadId) {
              const localLeadData = {
                id: ds.leadId || rid,
                Name: ds.name || '',
                whatsapp_number: ds.whatsappNumber || '',
                num_cameras: ds.numCameras || '',
                dvr_type: ds.dvrType || '',
                hdd_size: ds.hddSize || '',
                camera_resolution: ds.cameraResolution || '',
                Assign: ds.assign || '',
                Area: ds.area || '',
                Follow_up: ds.followUp || '',
                comments: ds.comments || '',
                Message: ds.message || '',
                map_link: ds.mapLink || '',
                call_status: ds.callStatus || '',
                quote_links: ds.quoteLinks || ''
              };
              toggleInlineEdit(cardElement, localLeadData);
            } else {
              // Fallback to server fetch when local data not present
              fetch(`lead_fetch.php?leadId=${encodeURIComponent(code)}`)
                .then(res => {
                  if (!res.ok) throw new Error("Failed to fetch lead details");
                  return res.json();
                })
                .then(data => {
                  if (data && data.error) {
                    showAutoSaveNotification(`Error loading lead details: ${data.error}`, null, 'error');
                    return;
                  }
                  if (!data || !data.id) {
                    showAutoSaveNotification("Lead not found", null, 'error');
                    return;
                  }
                  toggleInlineEdit(cardElement, data);
                })
                .catch(err => {
                  console.error("Error fetching lead data:", err);
                  showAutoSaveNotification("Error loading lead details", null, 'error');
                });
            }
          }
          // Handle save button clicks in inline forms
          else if (event.target.closest('.save-lead-btn')) {
            event.preventDefault();
            const saveBtn = event.target.closest('.save-lead-btn');
            const form = saveBtn.closest('form');
            const cardElement = saveBtn.closest('.lead-card');
            const leadId = cardElement.getAttribute('data-lead-id');
            const formData = new FormData(form);
            const leadData = {};
            for (let [key, value] of formData.entries()) {
              leadData[key] = value;
            }
            
            // --- VALIDATION START ---
            if (!leadData.call_status || leadData.call_status.trim() === '') {
                showAutoSaveNotification('Please select a Call Status', null, 'error');
                // Find the specific Call Status dropdown wrapper
                const callStatusSelect = form.querySelector('select[name="call_status"]');
                if (callStatusSelect) {
                    const wrapper = callStatusSelect.closest('.select-wrapper');
                    if (wrapper) {
                        const trigger = wrapper.querySelector('input.select-dropdown');
                        if (trigger) trigger.click();
                    }
                }
                return;
            }
            
            if (!leadData.Follow_up || leadData.Follow_up.trim() === '') {
                showAutoSaveNotification('Please enter Follow Up date', null, 'error');
                const followUpInput = form.querySelector('input[name="Follow_up"]');
                if (followUpInput) {
                     // Try to open datepicker if instance exists
                     if (typeof M !== 'undefined' && M.Datepicker) {
                        const instance = M.Datepicker.getInstance(followUpInput);
                        if (instance) instance.open();
                        else followUpInput.focus();
                     } else {
                        followUpInput.focus();
                     }
                }
                return;
            }

            // Date validation (Present or Future)
            if (leadData.Follow_up !== 'NA' && leadData.Follow_up !== '-') {
                 const parts = leadData.Follow_up.split(' ');
                 if (parts.length === 3) {
                     const day = parseInt(parts[0]);
                     const monthStr = parts[1];
                     const year = 2000 + parseInt(parts[2]);
                     const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                     const month = months.indexOf(monthStr);
                     
                     if (month !== -1) {
                         const followDate = new Date(year, month, day);
                         const today = new Date();
                         today.setHours(0,0,0,0);
                         
                         if (followDate < today) {
                             showAutoSaveNotification('Follow Up date must be today or future', null, 'error');
                             return;
                         }
                     }
                 }
            }
            // --- VALIDATION END ---

            // Save the lead
            saveLead(leadData, true, leadId);
            
            // Collapse the card after saving
            const code = `L-${leadId}`;
            fetch(`lead_fetch.php?leadId=${encodeURIComponent(code)}`)
              .then(res => {
                if (!res.ok) throw new Error("Failed to fetch lead details");
                return res.json();
              })
              .then(data => {
                if (data && data.error) {
                  showAutoSaveNotification(`Error loading lead details: ${data.error}`, null, 'error');
                  return;
                }
                if (data && data.id) {
                  // Collapse the card back to view mode
                  toggleInlineEdit(cardElement, data);
                }
              })
              .catch(err => {
                console.error("Error fetching lead data for collapse:", err);
              });
          }
          // Handle Installation Date button clicks
          else if (event.target.closest('.install-date-btn')) {
            event.preventDefault();
            const btn = event.target.closest('.install-date-btn');
            if (btn.disabled) return;
            const row = btn.closest('.install-date-row');
            const input = row ? row.querySelector('input[name="installation_date"]') : null;
            if (input && typeof M !== 'undefined' && M.Datepicker) {
              const instance = M.Datepicker.getInstance(input);
              if (instance) instance.open();
            }
          }
          // Handle Place Order button clicks
          else if (event.target.closest('.place-order-btn')) {
            event.preventDefault();
            const btn = event.target.closest('.place-order-btn');
            if (btn.disabled) return;
            const form = btn.closest('form');
            const leadId = form.dataset.leadId;
            const cardElement = btn.closest('.lead-card');
            const quoteInput = form.querySelector('input[name="quote"]');
            
            if (!quoteInput || !quoteInput.value.trim()) {
                showAutoSaveNotification("Quote amount is mandatory to place order!", leadId, 'error');
                if(quoteInput) quoteInput.focus();
                return;
            }

            const installationInput = form.querySelector('input[name="installation_date"]');
            const installationDate = installationInput ? installationInput.value.trim() : '';
            if (!installationDate) {
                showAutoSaveNotification("Installation date is mandatory to place order!", leadId, 'error');
                const row = form.querySelector('.install-date-row') || (installationInput ? installationInput.closest('.install-date-row') : null);
                const label = row ? row.querySelector('.install-date-text') : null;
                const rowBtn = row ? row.querySelector('.install-date-btn') : null;
                const rowIcon = rowBtn ? rowBtn.querySelector('i') : null;
                if (row) row.classList.add('is-error');
                if (label) label.style.color = '#ef4444';
                if (rowBtn) rowBtn.style.color = '#ef4444';
                if (rowIcon) rowIcon.style.color = '#ef4444';
                if (installationInput && typeof M !== 'undefined' && M.Datepicker) {
                  const instance = M.Datepicker.getInstance(installationInput);
                  if (instance) instance.open();
                  else installationInput.focus();
                }
                return;
            }
            const okRow = installationInput ? installationInput.closest('.install-date-row') : null;
            if (okRow) {
              okRow.classList.remove('is-error');
              const label = okRow.querySelector('.install-date-text');
              const okBtn = okRow.querySelector('.install-date-btn');
              const okIcon = okBtn ? okBtn.querySelector('i') : null;
              if (label) label.style.color = '';
              if (okBtn) okBtn.style.color = '';
              if (okIcon) okIcon.style.color = '';
            }
            
            const formData = new FormData(form);
            const leadData = {};
            for (let [key, value] of formData.entries()) {
              leadData[key] = value;
            }
            leadData.leadId = leadId;
            leadData.quote = quoteInput.value.trim();
            leadData.installation_date = installationDate;
            leadData.call_status = 'Ordered';
            leadData.place_order = 1;

            fetch('lead_save.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(leadData)
            })
              .then(res => res.json())
              .then(data => {
                if (data.success) {
                  showAutoSaveNotification(data.message || 'Order placed.', leadId);
                  writeOrderDraft(leadId, { quote: leadData.quote, installation_date: leadData.installation_date });
                  if (data.assign) leadData.Assign = data.assign;
                  if (data.quote_links !== undefined) leadData.quote_links = data.quote_links;
                  if (data.edit_trace !== undefined) leadData.edit_trace = data.edit_trace;
                  updateSingleCard(leadData, leadId);

                  const code = `L-${leadId}`;
                  fetch(`lead_fetch.php?leadId=${encodeURIComponent(code)}`)
                    .then(res => {
                      if (!res.ok) throw new Error("Failed to fetch lead details");
                      return res.json();
                    })
                    .then(ld => {
                      if (ld && ld.error) {
                        showAutoSaveNotification(`Error loading lead details: ${ld.error}`, null, 'error');
                        return;
                      }
                      if (ld && ld.id) {
                        toggleInlineEdit(cardElement, ld);
                      }
                    })
                    .catch(err => {
                      console.error("Error fetching lead data for collapse:", err);
                    });
                } else {
                  showAutoSaveNotification(data.message || "Failed to place order.", leadId, 'error');
                }
              })
              .catch(err => {
                console.error("Place order error:", err);
                showAutoSaveNotification("Failed to place order.", leadId, 'error');
              });
          }
          // Handle cancel button clicks in inline forms
          else if (event.target.closest('.cancel-edit-btn')) {
            event.preventDefault();
            const cancelBtn = event.target.closest('.cancel-edit-btn');
            const cardElement = cancelBtn.closest('.lead-card');
            const leadId = cardElement.getAttribute('data-lead-id');
            const code = `L-${leadId}`;
            
            // Cancel edit mode locally without backend fetch
            toggleInlineEdit(cardElement, { id: leadId });
          }
          // Handle close (x) button clicks in inline forms
          else if (event.target.closest('.close-edit-btn')) {
            event.preventDefault();
            const closeBtn = event.target.closest('.close-edit-btn');
            const cardElement = closeBtn.closest('.lead-card');
            const leadId = cardElement.getAttribute('data-lead-id');
            const code = `L-${leadId}`;
            
            // Close edit mode locally without backend fetch
            toggleInlineEdit(cardElement, { id: leadId });
          }
        });
        console.log('Click event listener set up on container.');
      } else {
        console.error('Container element not found, cannot set up click listener.');
      }

      function submitSearch() {
        const q = (searchInput ? searchInput.value : '').trim();
        if (/^\d+$/.test(q) && q.length > 0 && q.length < 3) {
          if (status) {
            status.innerText = 'Enter at least 3 digits';
            setTimeout(() => {
              if (status && status.innerText === 'Enter at least 3 digits') status.innerText = '';
            }, 1200);
          }
          return;
        }
        searchQuery = q;
        resetStateAndReload();
      }

      function updateSearchSuggestions() {
        if (!searchSuggestions || !container || !searchInput) return;
        const q = (searchInput.value || '').trim().toLowerCase();
        searchSuggestions.innerHTML = '';
        const isDigitsOnly = /^\d+$/.test(q);
        if ((isDigitsOnly && q.length < 3) || (!isDigitsOnly && q.length < 2)) return;

        const seen = new Set();
        const options = [];
        const cards = container.querySelectorAll('.lead-card');
        for (const card of cards) {
          const createdAt = (card.getAttribute('data-created-at') || '').trim();
          const dateOnly = createdAt ? createdAt.slice(0, 10) : '';
          const values = [
            card.getAttribute('data-whatsapp-number') || '',
            card.getAttribute('data-name') || '',
            card.getAttribute('data-mid') || '',
            card.getAttribute('data-area') || '',
            dateOnly,
            (card.getAttribute('data-comments') || '').slice(0, 48)
          ];

          for (const raw of values) {
            const v = (raw || '').trim();
            if (!v || v === '-') continue;
            if (!v.toLowerCase().includes(q)) continue;
            if (seen.has(v)) continue;
            seen.add(v);
            options.push(v);
            if (options.length >= 12) break;
          }
          if (options.length >= 12) break;
        }

        for (const v of options) {
          const opt = document.createElement('option');
          opt.value = v;
          searchSuggestions.appendChild(opt);
        }
      }

      let suggestTimer = null;

      if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') {
            e.preventDefault();
            submitSearch();
          }
        });
        searchInput.addEventListener('input', () => {
          clearTimeout(suggestTimer);
          suggestTimer = setTimeout(updateSearchSuggestions, 120);
        });
      }

      if (searchSubmit) {
        searchSubmit.addEventListener('click', () => {
          submitSearch();
        });
      }

      if (clearBtn) {
        clearBtn.addEventListener('click', () => {
          if (searchInput) searchInput.value = '';
          searchQuery = '';
          if (searchSuggestions) searchSuggestions.innerHTML = '';
          resetStateAndReload();
        });
      }

      if (mineLinks.length > 0) {
        const syncMineLinks = () => {
          mineLinks.forEach((link) => {
            const code = (link.getAttribute('data-mine') || '').trim();
            link.classList.toggle('active', code === mineQuery);
          });
        };

        mineLinks.forEach((link) => {
          link.addEventListener('click', (e) => {
            e.preventDefault();
            const code = (link.getAttribute('data-mine') || '').trim();
            if (code === mineQuery) mineQuery = '';
            else mineQuery = code;
            syncMineLinks();
            resetStateAndReload();
          });
        });

        syncMineLinks();
      }

      if (followLinks.length > 0) {
        const syncFollowLinks = () => {
          followLinks.forEach((link) => {
            const code = (link.getAttribute('data-follow') || '').trim();
            link.classList.toggle('active', code === followQuery);
          });
        };

        followLinks.forEach((link) => {
          link.addEventListener('click', (e) => {
            e.preventDefault();
            const code = (link.getAttribute('data-follow') || '').trim();
            if (code === followQuery) followQuery = '';
            else followQuery = code;
            syncFollowLinks();
            resetStateAndReload();
          });
        });

        syncFollowLinks();
      }

      document.addEventListener('click', (e) => {
        if (e.target.closest('.trace-toggle')) return;
        if (e.target.closest('#trace-popover')) return;
        hideTracePopover();
      });

      window.addEventListener('scroll', hideTracePopover, { passive: true });
      window.addEventListener('resize', hideTracePopover);

      // Initialize Materialize components that need it
      M.updateTextFields(); // For input labels

      // Load initial leads
      fetchRows('down');
      
      // Initialize scroll position after initial load
      setTimeout(() => {
        lastScrollCardsCount = container.querySelectorAll('.lead-card').length;
        lastScrollPosition = window.scrollY || window.pageYOffset;
        console.log('Initial state - Cards:', lastScrollCardsCount, 'Scroll position:', lastScrollPosition);
      }, 500);

      refreshAllMarkers();
      scheduleMarkerSweep();
      
      // Header and Search scroll behavior
      const pageHeader = document.getElementById('pageHeader');
      const searchBar = document.getElementById('searchBar');
      const headerSearchIcon = document.getElementById('headerSearchIcon');
      // searchInput already declared above
      let lastScroll = 0;
      
      if (pageHeader && searchBar) {
        // Toggle Search Bar
        if (headerSearchIcon) {
            headerSearchIcon.addEventListener('click', () => {
                pageHeader.classList.add('hidden');
                searchBar.classList.remove('hidden');
                if (searchInput) searchInput.focus();
            });
        }

        window.addEventListener('scroll', () => {
          const currentScroll = window.pageYOffset;
          
          if (currentScroll <= 0) {
            // At top: Show Header, Hide Search
            pageHeader.classList.remove('hidden');
            searchBar.classList.add('hidden');
          } else if (currentScroll > lastScroll && currentScroll > 50) {
            // Scrolling down: Hide Header, Show Search
            pageHeader.classList.add('hidden');
            searchBar.classList.remove('hidden');
          } else if (currentScroll < lastScroll) {
            // Scrolling up: Show Header, Hide Search (Rule: Never Together)
            pageHeader.classList.remove('hidden');
            searchBar.classList.add('hidden');
          }
          
          lastScroll = currentScroll;
        });
      }
    });

    // Intersection Observer setup (remains outside DOMContentLoaded as it uses globally declared variables)
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          if (entry.target.id === 'bottom-sentinel') {
            lastScrollDirection = 'down';
            fetchRows('down');
          }
          if (entry.target.id === 'top-sentinel') {
            lastScrollDirection = 'up';
            fetchRows('up');
          }
        }
      });
    }, {
      root: null,
      rootMargin: '200px 0px',
      threshold: 0.01
    });
    
    // Observe sentinels after they are assigned in DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
      if (topSentinel) observer.observe(topSentinel);
      if (bottomSentinel) observer.observe(bottomSentinel);
      
      // Add scroll event listener to detect scrolling continuously
      let scrollTimeout;
      window.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
          console.log('Scroll event triggered');
          checkAndAutoSaveOnScroll();
        }, 100); // More frequent check for floating date
      });
      // Quote Modal logic
const quoteModal = document.getElementById('quoteModal');
const quoteIframe = document.getElementById('quoteIframe');
const closeQuoteModal = document.getElementById('closeQuoteModal');

// Event delegation: detect click on Quote link
container.addEventListener('click', (e) => {
  if (e.target.closest('.open-quote')) {
    e.preventDefault();
    const quoteUrl = e.target.closest('.open-quote').dataset.quoteUrl;
    openQuoteModal(quoteUrl);
  }
});

// Function to open the quote modal
let scrollPosition = 0;

function openQuoteModal(url) {
  scrollPosition = window.scrollY;
  quoteIframe.src = url;
  quoteModal.classList.add('active');
  
  document.body.style.top = `-${scrollPosition}px`;
  document.body.style.position = 'fixed';
  document.body.style.width = '100%';
  document.body.style.overflowY = 'scroll'; // Prevent layout shift
}

function refreshSavedQuotesForLead(leadId) {
  const lid = parseInt(leadId, 10);
  if (!lid) return;
  fetch(`lead_fetch.php?leadId=${encodeURIComponent('L-' + lid)}`)
    .then(res => res.json())
    .then(data => {
      if (!data || data.error) return;
      const card = container.querySelector(`.lead-card[data-lead-id="${lid}"]`);
      if (!card) return;
      const saved = card.querySelector(`.saved-quotes[data-lead-id="${lid}"]`);
      if (!saved) return;
      const raw = data.quote_links || '';
      card.setAttribute('data-quote-links', raw);
      const quoteCount = countQuoteLinks(raw);
      const indicator = card.querySelector('.quote-count-indicator');
      if (indicator) {
        indicator.classList.toggle('is-empty', quoteCount === 0);
        const num = indicator.querySelector('.quote-count-number');
        if (num) num.textContent = String(quoteCount);
      }
      const links = raw.split(',').map(s => s.trim()).filter(Boolean).slice().reverse();
      saved.innerHTML = renderSavedQuotesLinks(links);
      if (editedCards && editedCards.has(lid)) {
        const entry = editedCards.get(lid);
        if (entry && entry.data) entry.data.quote_links = raw;
      }
    })
    .catch(() => {});
}

window.addEventListener('message', (event) => {
  if (event.origin !== window.location.origin) return;
  const payload = event.data || {};
  if (payload.type === 'quoteSaved') {
    const lid = payload.leadId;
    refreshSavedQuotesForLead(lid);
    if (typeof showAutoSaveNotification === 'function') {
      showAutoSaveNotification('Quote saved.', lid);
    }
  }
});

// Close button event
closeQuoteModal.addEventListener('click', () => {
  quoteModal.classList.remove('active');
  quoteIframe.src = '';
  
  document.body.style.position = '';
  document.body.style.top = '';
  document.body.style.width = '';
  document.body.style.overflowY = '';
  
  window.scrollTo(0, scrollPosition);
});

// Close when clicking outside the modal content
quoteModal.addEventListener('click', (e) => {
  if (e.target === quoteModal) {
    quoteModal.classList.remove('active');
    quoteIframe.src = '';
    
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.width = '';
    document.body.style.overflowY = '';
    
    window.scrollTo(0, scrollPosition);
  }
});

    // --- Realtime UI update helpers (name, specs, assign) ---
    // Helper to build spec span based on value
    function buildSpecSpan(value) {
      const v = (value || '').trim();
      const isUnknown = v.length === 0 || v.toLowerCase() === 'dont-know';
      const safeText = isUnknown ? 'X' : v;
      const cls = isUnknown ? 'spec-unknown' : 'spec-value';
      return `<span class="${cls}">${safeText}</span>`;
    }

    function updateNameFromForm(cardElement, rid) {
      const nameInput = cardElement.querySelector(`#leadName-${rid}`);
      // Updated selector: .lead-info-content is the new wrapper
      const displayEl = cardElement.querySelector('.lead-info-content .lead-name, .lead-info-content .lead-name-placeholder');
      if (!displayEl || !nameInput) return;
      const val = (nameInput.value || '').trim();
      if (val) {
        displayEl.textContent = val;
        displayEl.classList.add('lead-name');
        displayEl.classList.remove('lead-name-placeholder');
      } else {
        displayEl.textContent = 'NAME';
        displayEl.classList.add('lead-name-placeholder');
        displayEl.classList.remove('lead-name');
      }
    }

    // --- Realtime UI update helpers moved to separate script block ---


    });
    

  </script>
    <script>
    // --- Realtime UI update helpers (name, specs, assign) ---
    console.log('Lead List Enhanced: Helper functions loaded (Global)');

    function normalizeAssignValue(v) {
      return String(v || '').trim().toLowerCase();
    }

    function syncAssignBadgeOpenState(assignTag, assignVal) {
      const next = normalizeAssignValue(assignVal);
      const was = normalizeAssignValue(assignTag.dataset.assignValue || '');
      assignTag.dataset.assignValue = String(assignVal || '').trim();
      const isOpen = next === 'open';
      assignTag.classList.toggle('is-open', isOpen);
      if (isOpen && was !== 'open') {
        assignTag.classList.remove('open-animate');
        void assignTag.offsetWidth;
        assignTag.classList.add('open-animate');
      }
    }

    // Ensure global availability explicitly
    window.updateAssignFromForm = function(cardElement, rid) {
      const assignInput = cardElement.querySelector(`#leadAssign-${rid}`);
      const assignVal = assignInput ? assignInput.value.trim() : '';
      const leftSection = cardElement.querySelector('.lead-controls-section .lead-controls-left');
      if (!leftSection) return;
      let assignTag = leftSection.querySelector('.assign-badge');
      if (assignVal) {
        if (!assignTag) {
          assignTag = document.createElement('span');
          assignTag.className = 'assign-badge';
          leftSection.insertBefore(assignTag, leftSection.firstChild);
        }
        assignTag.textContent = assignVal;
        syncAssignBadgeOpenState(assignTag, assignVal);
      } else if (assignTag) {
        assignTag.remove();
      }
    };
    // Also define as regular function for backward compatibility/local scope preference if needed
    function updateAssignFromForm(cardElement, rid) {
        return window.updateAssignFromForm(cardElement, rid);
    }

    // Helper to build spec span based on value
    function buildSpecSpan(value) {
      const v = (value || '').trim();
      const isUnknown = v.length === 0 || v.toLowerCase() === 'dont-know';
      const safeText = isUnknown ? 'X' : v;
      const cls = isUnknown ? 'spec-unknown' : 'spec-value';
      return `<span class="${cls}">${safeText}</span>`;
    }

    function updateNameFromForm(cardElement, rid) {
      const nameInput = cardElement.querySelector(`#leadName-${rid}`);
      // Updated selector: .lead-info-content is the new wrapper
      const displayEl = cardElement.querySelector('.lead-info-content .lead-name, .lead-info-content .lead-name-placeholder');
      if (!displayEl || !nameInput) return;
      const val = (nameInput.value || '').trim();
      if (val) {
        displayEl.textContent = val;
        displayEl.classList.add('lead-name');
        displayEl.classList.remove('lead-name-placeholder');
      } else {
        displayEl.textContent = 'NAME';
        displayEl.classList.add('lead-name-placeholder');
        displayEl.classList.remove('lead-name');
      }
    }

    function updateSpecsFromForm(cardElement, rid) {
      const hddInput = cardElement.querySelector(`#leadHdd-${rid}`);
      const resInput = cardElement.querySelector(`#leadCameraResolution-${rid}`);
      const camsInput = cardElement.querySelector(`#leadNumCameras-${rid}`);
      const dvrInput = cardElement.querySelector(`#leadDvrType-${rid}`);
      
      // Updated selector: .lead-specs .spec-tag
      const tags = cardElement.querySelectorAll('.lead-specs .spec-tag');
      if (!tags || tags.length < 3) return;

      // HDD
      const hddTag = tags[0];
      const hddVal = hddInput ? hddInput.value : '';
      const hddSpan = hddTag.querySelector('.spec-value, .spec-unknown');
      
      const isHddUnknown = !hddVal || hddVal.trim().toLowerCase() === 'dont-know';
      const hddText = isHddUnknown ? 'X' : hddVal.trim();
      const hddClass = isHddUnknown ? 'spec-unknown' : `spec-value ${getHddColorClass(hddVal)}`;

      if (hddSpan) {
        hddSpan.textContent = hddText;
        hddSpan.className = hddClass;
      } else {
        // If span doesn't exist but label might
        if (hddTag.querySelector('.spec-label')) {
             // Remove any text nodes that might be there and append span
             // Actually safest is to just rebuild
             hddTag.innerHTML = `<span class="spec-label">HDD</span> <span class="${hddClass}">${hddText}</span>`;
        } else {
             hddTag.innerHTML = `<span class="spec-label">HDD</span> <span class="${hddClass}">${hddText}</span>`;
        }
      }

      // RES
      const resTag = tags[1];
      const resVal = resInput ? resInput.value : '';
      const camsVal = camsInput ? camsInput.value : '';
      
      // We expect a .spec-row container now
      let specRow = resTag.querySelector('.spec-row');
      if (!specRow) {
          resTag.innerHTML = `<span class="spec-label">RES</span> <div class="spec-row">${buildSpecSpan(resVal)} <span class="spec-x">x</span> ${buildSpecSpan(camsVal)}</div>`;
      } else {
          specRow.innerHTML = `${buildSpecSpan(resVal)} <span class="spec-x">x</span> ${buildSpecSpan(camsVal)}`;
      }

      // DVR
      const recTag = tags[2];
      const dvrVal = dvrInput ? dvrInput.value : '';
      const recSpan = recTag.querySelector('.spec-value, .spec-unknown');
      
      const isDvrUnknown = !dvrVal || dvrVal.trim().toLowerCase() === 'dont-know';
      const dvrText = isDvrUnknown ? 'X' : dvrVal.trim();
      const dvrClass = isDvrUnknown ? 'spec-unknown' : `spec-value ${getDvrColorClass(dvrVal)}`;

      if (recSpan) {
        recSpan.textContent = dvrText;
        recSpan.className = dvrClass;
      } else {
        if (recTag.querySelector('.spec-label')) {
             recTag.innerHTML = `<span class="spec-label">DVR</span> <span class="${dvrClass}">${dvrText}</span>`;
        } else {
             recTag.innerHTML = `<span class="spec-label">DVR</span> <span class="${dvrClass}">${dvrText}</span>`;
        }
      }
    }

    

    function setupRealtimeFormBindings(cardElement, rid) {
      const nameInput = cardElement.querySelector(`#leadName-${rid}`);
      const hddInput = cardElement.querySelector(`#leadHdd-${rid}`);
      const resInput = cardElement.querySelector(`#leadCameraResolution-${rid}`);
      const camsInput = cardElement.querySelector(`#leadNumCameras-${rid}`);
      const dvrInput = cardElement.querySelector(`#leadDvrType-${rid}`);
      const assignInput = cardElement.querySelector(`#leadAssign-${rid}`);
      const quoteInput = cardElement.querySelector(`#leadQuote-${rid}`);
      const installInput = cardElement.querySelector(`#installDate-${rid}`);

      if (nameInput) {
        nameInput.addEventListener('keyup', () => updateNameFromForm(cardElement, rid));
        updateNameFromForm(cardElement, rid);
      }
      [hddInput, resInput, camsInput, dvrInput].forEach(inp => {
        if (inp) inp.addEventListener('keyup', () => updateSpecsFromForm(cardElement, rid));
      });
      updateSpecsFromForm(cardElement, rid);

      if (assignInput) {
        assignInput.addEventListener('keyup', () => updateAssignFromForm(cardElement, rid));
        updateAssignFromForm(cardElement, rid);
      }

      if (quoteInput) {
        if (quoteInput.readOnly || quoteInput.disabled) return;
        const saveQuoteDraft = () => writeOrderDraft(rid, { quote: quoteInput.value });
        quoteInput.addEventListener('input', saveQuoteDraft);
        saveQuoteDraft();
      }

      if (installInput) {
        if (installInput.readOnly || installInput.disabled) return;
        const saveInstallDraft = () => writeOrderDraft(rid, { installation_date: installInput.value });
        installInput.addEventListener('change', saveInstallDraft);
        saveInstallDraft();
      }
    }
    // Scroll to Top Button Logic
    const scrollToTopBtn = document.createElement('button');
    scrollToTopBtn.className = 'scroll-to-top-btn';
    scrollToTopBtn.innerHTML = '<i class="fa fa-arrow-up"></i>';
    scrollToTopBtn.title = 'Go to top';
    document.body.appendChild(scrollToTopBtn);

    // Refresh / Go to Latest Button Logic
    const refreshBtn = document.createElement('button');
    refreshBtn.className = 'refresh-btn';
    // Top arrow with a line on the top
    refreshBtn.innerHTML = '<i class="fa fa-arrow-up" style="position: relative;"><span style="position: absolute; top: -3px; left: 50%; transform: translateX(-50%); width: 10px; height: 2px; background-color: currentColor;"></span></i>';
    refreshBtn.title = 'Go to Latest';
    refreshBtn.style.cssText = `
        position: fixed;
        bottom: 80px;
        right: 20px;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background-color: #4CAF50;
        color: white;
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        display: none;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        z-index: 1000;
        transition: transform 0.2s, background-color 0.2s;
    `;
    // Add hover effect
    refreshBtn.onmouseover = () => refreshBtn.style.backgroundColor = '#45a049';
    refreshBtn.onmouseout = () => refreshBtn.style.backgroundColor = '#4CAF50';
    
    document.body.appendChild(refreshBtn);

    const favoritesBtn = document.getElementById('favoritesFab');
    const historyBtn = document.getElementById('historyFab');
    const newLeadsFab = document.getElementById('newLeadsFab');
    const floatingMenuToggle = document.getElementById('floatingMenuToggle');
    let floatingButtonsHidden = false;

    function syncFloatingMenuState() {
      document.body.classList.toggle('floating-hidden', floatingButtonsHidden);
      if (floatingMenuToggle) {
        floatingMenuToggle.innerHTML = floatingButtonsHidden
          ? '<i class="fa-solid fa-bars"></i>'
          : '<i class="fa-solid fa-xmark"></i>';
      }
      const shouldShowScrollButtons = window.scrollY > 300;
      if (shouldShowScrollButtons && !floatingButtonsHidden) {
        scrollToTopBtn.style.display = 'flex';
        refreshBtn.style.display = 'flex';
      } else {
        scrollToTopBtn.style.display = 'none';
        refreshBtn.style.display = 'none';
      }
    }

    function syncMidViewButtons() {
      if (!favoritesBtn || !historyBtn) return;
      favoritesBtn.innerHTML = favoritesViewActive ? '<i class="fa-solid fa-xmark"></i>' : '<i class="fa-regular fa-heart"></i>';
      historyBtn.innerHTML = historyViewActive ? '<i class="fa-solid fa-xmark"></i>' : '<i class="fa-solid fa-clock-rotate-left"></i>';
    }

    syncMidViewButtons();
    syncFloatingMenuState();

    if (floatingMenuToggle) {
      floatingMenuToggle.addEventListener('click', () => {
        floatingButtonsHidden = !floatingButtonsHidden;
        syncFloatingMenuState();
      });
    }

    window.addEventListener('scroll', () => {
      if (!floatingButtonsHidden && window.scrollY > 300) {
        scrollToTopBtn.style.display = 'flex';
        refreshBtn.style.display = 'flex';
      } else {
        scrollToTopBtn.style.display = 'none';
        refreshBtn.style.display = 'none';
      }
    });

    scrollToTopBtn.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
    
    refreshBtn.addEventListener('click', () => {
      resetStateAndReload();
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });

    if (favoritesBtn) favoritesBtn.addEventListener('click', () => {
      if (favoritesViewActive) {
        exitMidsView();
      } else {
        historyViewActive = false;
        loadMidsView('favorites', readStoredList(FAVORITES_KEY));
      }
      syncMidViewButtons();
    });

    if (historyBtn) historyBtn.addEventListener('click', () => {
      if (historyViewActive) {
        exitMidsView();
      } else {
        favoritesViewActive = false;
        loadMidsView('history', readStoredList(HISTORY_KEY));
      }
      syncMidViewButtons();
    });

    // ==========================================
    // Real-time Auto Update Feature
    // ==========================================
    let newLeadsCount = 0;

    if (newLeadsFab) {
      newLeadsFab.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        newLeadsFab.classList.remove('visible');
        newLeadsCount = 0;
        newLeadsFab.innerHTML = 'New <big>0</big> Lead';
      });
    }

    let lastSeenLeadId = 0;
    
    // Function to find the highest lead ID currently in the list
    function updateLastSeenLeadId() {
        const cards = document.querySelectorAll('.lead-card');
        cards.forEach(card => {
            const leadId = parseInt(card.getAttribute('data-lead-id') || 0);
            if (leadId > lastSeenLeadId) {
                lastSeenLeadId = leadId;
            }
        });
        console.log('Current highest Lead ID:', lastSeenLeadId);
    }

    // Call this initially to set baseline
    // We'll call it again after initial load is complete
    
    function playNotificationSound() {
        const audio = new Audio('/content/uploads/2025/01/mixkit-happy-bells-notification-937.wav');
        audio.play().catch(e => console.error('Audio playback failed', e));
    }

    let lastPresenceActivityAt = Date.now();

    function touchPresenceActivity() {
        const now = Date.now();
        if (now - lastPresenceActivityAt < 5000) return;
        lastPresenceActivityAt = now;
    }

    ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach((evt) => {
        window.addEventListener(evt, touchPresenceActivity, { passive: true });
    });

    function renderActiveUsersBar(users) {
        const bar = document.getElementById('activeUsersBar');
        if (!bar) return;
        if (!Array.isArray(users)) return;
        bar.innerHTML = '';
        const nameMap = {
            amr: 'Amreen',
            var: 'Varsha',
            zoy: 'Zoya'
        };
        const allow = new Set(Object.keys(nameMap));
        const seen = new Set();
        users.forEach((u) => {
            const code = (u && u.code ? String(u.code) : '').trim();
            if (!code) return;
            const key = code.toLowerCase();
            if (!allow.has(key)) return;
            if (seen.has(key)) return;
            seen.add(key);

            const chip = document.createElement('span');
            chip.className = 'active-user-chip';

            const dot = document.createElement('span');
            dot.className = 'presence-dot' + (u && u.active ? ' active' : '');

            const label = document.createElement('span');
            label.textContent = nameMap[key];

            chip.appendChild(dot);
            chip.appendChild(label);
            bar.appendChild(chip);
        });
    }

    function pollPresence() {
        fetch(`lead_check_updates.php?presence_last_active=${encodeURIComponent(Math.floor(lastPresenceActivityAt / 1000))}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.success && Array.isArray(data.users)) {
                    renderActiveUsersBar(data.users);
                }
            })
            .catch(() => {});
    }

    pollPresence();
    setInterval(pollPresence, 20000);

    function checkForNewLeads() {
        if (favoritesViewActive || historyViewActive) return;
        if (lastSeenLeadId === 0) {
            updateLastSeenLeadId();
            if (lastSeenLeadId === 0) return; // Still 0, maybe list empty or loading
        }

        console.log('Checking for new leads since ID:', lastSeenLeadId);

        fetch(`lead_check_updates.php?last_id=${lastSeenLeadId}&mine=${encodeURIComponent(mineQuery)}&follow=${encodeURIComponent(followQuery)}&presence_last_active=${encodeURIComponent(Math.floor(lastPresenceActivityAt / 1000))}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.success && Array.isArray(data.users)) {
                    renderActiveUsersBar(data.users);
                }
                if (data.success && data.new_leads && data.new_leads.length > 0) {
                    const newLeads = data.new_leads;
                    console.log(`Found ${newLeads.length} new leads!`);
                    
                    // Update FAB
                    newLeadsCount += newLeads.length;
                    if (newLeadsFab) {
                        newLeadsFab.classList.add('visible');
                        newLeadsFab.innerHTML = `New <big>${newLeadsCount}</big> Lead${newLeadsCount === 1 ? '' : 's'}`;
                    }

                    // Play Notification Sound
                    playNotificationSound();

                    // showAutoSaveNotification(`Found ${newLeads.length} new lead(s)! Adding to list...`, null, 'success');
                    
                    // Process new leads
                    // We need to add them in reverse order (newest first) to the top, 
                    // but the API returns them ASC (oldest to newest).
                    // So we iterate normally and prepend each one, which effectively reverses them if we use prepend?
                    // Wait, prepend adds as first child. 
                    // If we have [101, 102, 103] (ASC)
                    // prepend(101) -> [101, ...]
                    // prepend(102) -> [102, 101, ...]
                    // prepend(103) -> [103, 102, 101, ...]
                    // Yes, this results in DESC order at the top. Correct.

                    newLeads.forEach(row => {
                        // Check for duplicates before adding
                        if (document.querySelector(`.lead-card[data-lead-id="${row.id}"]`)) {
                            return; // Skip duplicate
                        }

                        const newCard = createRow(row);
                        
                        // Add highlight effect
                        const cardInner = newCard.querySelector('.lead-card');
                        if (cardInner) {
                            // Mark as new lead for offset handling
                            cardInner.setAttribute('data-is-new', 'true');
                            
                            cardInner.classList.add('new-lead-highlight');
                            cardInner.classList.add('new-lead-highlight');
                            // Remove highlight after some time
                            setTimeout(() => {
                                cardInner.classList.remove('new-lead-highlight');
                            }, 10000);
                        }

                        // Prepend to container
                        if (container && topSentinel) {
                            // Insert after topSentinel
                            container.insertBefore(newCard, topSentinel.nextSibling);
                        } else if (container) {
                            container.prepend(newCard);
                        }

                        // Update lastSeenLeadId
                        const newId = parseInt(row.id);
                        if (newId > lastSeenLeadId) {
                            lastSeenLeadId = newId;
                        }
                    });

                    rebuildDaySeparators();
                    
                    // Update offsetStart to account for added items?
                    // Actually, if we add items to top, our existing scroll logic might get confused 
                    // if it relies on strict index counts, but here we use sentinels.
                    // However, we should increment offsetEnd so we don't reload these if we scroll down and up?
                    // The fetchRows logic uses offset based on SQL LIMIT. 
                    // If we inject rows, the existing DOM has more items.
                    // This is fine for infinite scroll as long as we don't duplicate.
                    
                } else {
                    console.log('No new leads found.');
                }
            })
            .catch(err => console.error('Error checking for updates:', err));
    }

    // Start polling every 60 seconds
    setInterval(checkForNewLeads, 60000);

    // Initial update of ID after a short delay to ensure list is populated
    setTimeout(updateLastSeenLeadId, 2000);

    </script>
</body>
</html>
