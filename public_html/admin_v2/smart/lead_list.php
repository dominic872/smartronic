<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">

  <title>SM Leads | List</title>
  <link rel="icon" type="image/png" sizes="32x32" href="/content/uploads/2025/01/cropped-Site-Icon-32x32.png">
  <link rel="apple-touch-icon" href="/content/uploads/2025/01/cropped-Site-Icon-180x180.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="lead_list_styles.css">
  <!-- Materialize JS is still used for Datepicker/Modal, so keeping it or we need to replace it. 
       For now, let's keep JS but remove CSS if possible, OR keep CSS but override everything. 
       Actually, `lead_list_styles.css` resets basic styles, so keeping Materialize CSS might conflict.
       However, the plan implies a custom look. Let's remove Materialize CSS and see. 
       Wait, the users JS calls `M.Datepicker.init`. This requires Materialize JS AND CSS usually for the picker. 
       I will keep Materialize CSS for now but put it BEFORE my styles so I can override.
       The current file has Materialize CSS. I will Keep it. -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
</head>
<body class="grey lighten-4">
  <section class="crf-form-wrapper">
    <div class="container">
      <div class="crf-container">
        <div class="crf-left-col">
          <!-- Floating date indicator -->
          <div class="floating-date" id="floatingDate"></div>

          <button id="favoritesFab" class="favorites-btn" type="button" aria-label="Favourites">
            <i class="fa-regular fa-heart"></i>
          </button>
          <button id="historyFab" class="history-btn" type="button" aria-label="History">
            <i class="fa-solid fa-clock-rotate-left"></i>
          </button>
          
          <div id="searchBar">
            <input type="text" id="searchInput" placeholder="Search name, location, type..." />
            <button id="clearBtn">Clear</button>
          </div>

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

  <script>
    let lastScrollDirection = 'down';
    let offsetStart = 0;
    let offsetEnd = 0;
    let isLoading = false;
    let searchQuery = '';
    const PAGE_SIZE = 100;
    const MAX_ROWS = 200;
    let editedCards = new Map(); // Track edited cards
    let lastScrollCardsCount = 0; // Track card count for scroll detection
    let lastScrollPosition = 0; // Track scroll position
    const SCROLL_THRESHOLD = 500; // Scroll 1000px before auto-save
    let floatingDateVisible = false;
    let currentFloatingDate = '';

    let favoritesViewActive = false;
    let historyViewActive = false;
    const FAVORITES_KEY = 'smartronic_favorites_mids';
    const HISTORY_KEY = 'smartronic_history_mids';

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
      return String(value || '').trim();
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
      container.querySelectorAll('.col.s12').forEach(el => el.remove());
    }

    function loadMidsView(mode, mids) {
      const list = Array.isArray(mids) ? mids.map(normalizeMid).filter(Boolean) : [];
      const capped = Array.from(new Set(list)).slice(0, mode === 'favorites' ? 50 : 20);
      if (capped.length === 0) {
        showAutoSaveNotification(mode === 'favorites' ? 'No favourites saved yet' : 'No history yet', null, 'error');
        return;
      }

      favoritesViewActive = mode === 'favorites';
      historyViewActive = mode === 'history';
      isLoading = false;
      lastScrollDirection = 'down';
      offsetStart = 0;
      offsetEnd = 0;
      editedCards.clear();
      clearLeadListDom();

      status.innerText = 'Loading...';
      const midsCsv = capped.join(',');
      fetch(`lead_fetch.php?mids=${encodeURIComponent(midsCsv)}`)
        .then(res => {
          if (!res.ok) throw new Error('Server error');
          return res.json();
        })
        .then(data => {
          clearLeadListDom();
          if (!Array.isArray(data) || data.length === 0) {
            status.innerText = 'No records.';
            return;
          }
          data.forEach(row => {
            const el = createRow(row);
            container.insertBefore(el, bottomSentinel);
          });
          status.innerText = '';
          window.scrollTo({ top: 0, behavior: 'smooth' });
        })
        .catch(err => {
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

    // Function to create inline edit form
    function createInlineEditForm(leadData) {
      const rid = leadData.id || '';
      const code = `L-${rid}`;
      
      return `
        <div class="inline-edit-form">
          <form class="compact-form" data-lead-id="${rid}">
            <input type="hidden" name="leadId" value="${rid}">
            <input type="hidden" name="whatsapp_number" value="${leadData.whatsapp_number || ''}">
            <input type="hidden" name="Message" value="${leadData.Message || ''}">
            <input type="hidden" name="map_link" value="${leadData.map_link || ''}">

            <div class="form-row">
              <div class="input-group full-width">
                <label for="leadName-${rid}">Name</label>
                <input id="leadName-${rid}" type="text" name="Name" value="${leadData.Name || ''}" placeholder="Enter Lead Name">
              </div>
            </div>

            <div class="form-row tech-specs-row">
              <div class="input-group">
                <label for="leadNumCameras-${rid}">Cameras</label>
                <input id="leadNumCameras-${rid}" type="text" name="num_cameras" value="${leadData.num_cameras || ''}" placeholder="#">
              </div>
              <div class="input-group">
                <label for="leadDvrType-${rid}">DVR Type</label>
                <input id="leadDvrType-${rid}" type="text" name="dvr_type" value="${leadData.dvr_type || ''}" placeholder="Type">
              </div>
              <div class="input-group">
                <label for="leadHdd-${rid}">HDD Size</label>
                <input id="leadHdd-${rid}" type="text" name="hdd_size" value="${leadData.hdd_size || ''}" placeholder="TB">
              </div>
              <div class="input-group">
                <label for="leadCameraResolution-${rid}">Res</label>
                <input id="leadCameraResolution-${rid}" type="text" name="camera_resolution" value="${leadData.camera_resolution || ''}" placeholder="MP">
              </div>
            </div>

            <div class="form-row details-row">
              <div class="input-group">
                <label for="leadArea-${rid}">Area</label>
                <input id="leadArea-${rid}" type="text" name="Area" value="${leadData.Area || ''}" placeholder="Location">
              </div>
              <div class="input-group">
                <label for="leadFollowUp-${rid}">Follow Up</label>
                <input id="leadFollowUp-${rid}" type="text" name="Follow_up" value="${leadData.Follow_up || ''}" class="follow-up-input datepicker" placeholder="Select Date">
              </div>
              <div class="input-group">
                <label for="leadQuote-${rid}">Quote</label>
                <input id="leadQuote-${rid}" type="text" name="quote" value="${leadData.quote || ''}" placeholder="Amount">
              </div>
              <div class="input-group">
                <label for="leadAssign-${rid}">Assign To</label>
                <input id="leadAssign-${rid}" type="text" name="Assign" value="${leadData.Assign || ''}" placeholder="Staff">
              </div>
            </div>

            <div class="form-row">
              <div class="input-group full-width">
                <label for="leadComments-${rid}">Comments</label>
                <textarea id="leadComments-${rid}" class="materialize-textarea" name="comments" rows="2" placeholder="Add notes...">${leadData.comments || ''}</textarea>
              </div>
            </div>

            <div class="inline-edit-actions">
              <button type="button" class="btn-base btn-secondary cancel-edit-btn">
                Cancel
              </button>
              <button type="button" class="btn-base btn-primary save-lead-btn">
                <i class="fa fa-save"></i> Save Changes
              </button>
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
        cardElement.removeAttribute('data-lead-id');
        
        // Remove from edited cards tracking
        const rid = leadData.id || '';
        editedCards.delete(rid);
      } else {
        // Enter edit mode - show inline form in portion 2
        const rid = leadData.id || '';
        formSection.innerHTML = `
          <div style="position: relative;">
            <button class="close-edit-btn" style="position: absolute; top: 0px; right: 25px; background: none; border: none; font-size: 2rem; cursor: pointer; color: #ec0909; z-index: 10;">&times;</button>
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
        
        // Initialize datepicker for Follow Up field
        const followUpInputs = cardElement.querySelectorAll('.follow-up-input');
        M.Datepicker.init(followUpInputs, {
          format: 'dd mmm yy',
          autoClose: true,
          showClearBtn: true,
          minDate: new Date()
        });
        // Bind keyup events to update placeholders in real-time
        setupRealtimeFormBindings(cardElement, rid);
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
      qrContainer.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=48x48&data=${callUrl}" alt="QR Code" title="Click to switch to WhatsApp" data-mode="call" data-phone="${phone}">`;
      
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
        imgElement.src = `https://api.qrserver.com/v1/create-qr-code/?size=48x48&data=${whatsappUrl}`;
        imgElement.setAttribute('data-mode', 'whatsapp');
        imgElement.setAttribute('title', 'Click to switch to Call');
        imgElement.classList.add('whatsapp-mode');
      } else {
        // Switch to Call
        const callUrl = encodeURIComponent('tel:+91' + phone);
        imgElement.src = `https://api.qrserver.com/v1/create-qr-code/?size=48x48&data=${callUrl}`;
        imgElement.setAttribute('data-mode', 'call');
        imgElement.setAttribute('title', 'Click to switch to WhatsApp');
        imgElement.classList.remove('whatsapp-mode');
      }
    }

    function createRow(row) {
      const el = document.createElement('div');
      el.className = 'col s12';

      // Use display_id field which is either MID or id
      const displayId = row.display_id || row.MID || row.id || '';
      const rid = row.id || '';
      const code = `L-${rid}`;
      const midKey = normalizeMid(row.MID || displayId || rid);
      const isFav = isFavoritedMid(midKey);
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

      // Format date (dd mmm yy)
      let formattedDate = '-';
      if (created && created !== '-') {
        const dateObj = new Date(created);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const day = String(dateObj.getDate()).padStart(2, '0');
        const month = months[dateObj.getMonth()];
        const year = String(dateObj.getFullYear()).slice(-2);
        formattedDate = `${day} ${month} ${year}`;
      }

      // Format technical specs - replace dont-know with X
      const hddDisplay = (hdd && hdd.toLowerCase() !== 'dont-know') ? `<span class="spec-value">${hdd}</span>` : '<span class="spec-unknown">X</span>';
      const resDisplay = (res && res.toLowerCase() !== 'dont-know') ? `<span class="spec-value">${res}</span>` : '<span class="spec-unknown">X</span>';
      const camDisplay = (cams && cams.toString().toLowerCase() !== 'dont-know') ? `<span class="spec-value">${cams}</span>` : '<span class="spec-unknown">X</span>';
      const recDisplay = (dvr && dvr.toLowerCase() !== 'dont-know') ? `<span class="spec-value">${dvr}</span>` : '<span class="spec-unknown">X</span>';

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

      // ✅ Build the quote parameter string
      const quoteParam = encodeURIComponent(`${phone}|${cams}|${dvr}|${hdd}|${res}|${name}|${code}`);
      const quoteUrl = `quote.php?quote=${quoteParam}`;

      el.innerHTML = `
        <div class="card lead-card"
             data-created-at="${created}"
             data-lead-id="${rid}"
             data-mid="${midKey}"
             data-name="${name}"
             data-whatsapp-number="${phone}"
             data-num-cameras="${cams}"
             data-dvr-type="${dvr}"
             data-hdd-size="${hdd}"
             data-camera-resolution="${res}"
             data-assign="${assign}"
             data-area="${area}"
             data-follow-up="${follow}"
             data-comments="${comment}"
             data-message="${msg}"
             data-map-link="${map}">
          
          <!-- Portion 1: Lead Info -->
          <div class="lead-info-section">
            ${phone ? `<div class="lead-qr-code" data-phone="${phone}">
               <div class="qr-placeholder" style="${patternStyle}">
                 <div class="avatar-shape ${shapeClass}" style="background-color: #ffffff; border: 1px solid #999;">
                   <i class="fa ${iconClassName} avatar-icon" style="color: ${avatarPattern.shape}; font-size: 16px;"></i>
                 </div>
               </div>
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
              <div class="lead-header">
                <h3 class="${nameClass}">${displayName}</h3>
                ${assign && assign !== '-' ? `<span class="assign-badge${String(assign).trim().toUpperCase() === 'SUR' ? ' is-sur' : ''}">${assign}</span>` : ''}
              </div>

              <div class="lead-meta-row">
                ${phone ? `<span class="lead-phone"><i class="fa fa-whatsapp"></i> ${phone}</span>` : ''}
                ${area && area !== '-' ? `<span class="lead-phone"><i class="fa fa-map-marker-alt"></i> ${area}</span>` : ''}
                ${follow && follow !== '-' ? `<span class="lead-phone"><i class="fa fa-calendar-alt"></i> ${follow}</span>` : ''}
              </div>

              <div class="lead-specs">
                <span class="spec-tag">HDD <span class="spec-value">${hddDisplay}</span></span>
                <span class="spec-tag">RES <span class="spec-value">${resDisplay}</span> x <span class="spec-value">${camDisplay}</span></span>
                <span class="spec-tag">DVR <span class="spec-value">${recDisplay}</span></span>
              </div>
            </div>
          </div>
          
          <!-- Portion 2: Expandable Form -->
          <div class="lead-form-section"></div>
          
          <!-- Portion 3: Controls -->
          <div class="lead-controls-section">
            <div class="lead-controls-left">
              <!-- Created date or other meta could go here -->
               <span class="lead-phone" style="font-size: 12px;">${formattedDate}</span>
            </div>
            <div class="lead-actions">
              <a href="#" class="action-link action-edit edit-lead-btn" data-code="${code}">
                <i class="fa fa-pen"></i> Edit
              </a>
              <a href="#" class="action-link action-quote open-quote" data-quote-url="${quoteUrl}">
                <i class="fa fa-file-invoice"></i> Quote
              </a>
            </div>
          </div>
        </div>
      `;

      return el;
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

      fetch(`lead_fetch.php?offset=${fetchOffset}&search=${encodeURIComponent(searchQuery)}`)
        .then(res => {
          if (!res.ok) throw new Error("Server error");
          return res.json();
        })
        .then(data => {
          if (!Array.isArray(data) || data.length === 0) {
            status.innerText = 'No more records.';
            isLoading = false;
            return;
          }

          if (direction === 'down') {
            data.forEach(row => {
              const el = createRow(row);
              container.insertBefore(el, bottomSentinel);
              offsetEnd++;
            });
          } else {
            const firstRowBefore = topSentinel.nextElementSibling;
            const prevTop = firstRowBefore ? firstRowBefore.getBoundingClientRect().top : 0;

            const frag = document.createDocumentFragment();
            for (let i = data.length - 1; i >= 0; i--) {
              frag.appendChild(createRow(data[i]));
            }
            container.insertBefore(frag, topSentinel.nextSibling);

            requestAnimationFrame(() => {
              if (firstRowBefore) {
                const newTop = firstRowBefore.getBoundingClientRect().top;
                const delta = newTop - prevTop;
                window.scrollBy(0, delta);
              }
            });

            offsetStart = Math.max(0, offsetStart - data.length);
          }

          // Check if we've scrolled enough to trigger auto-save
          checkAndAutoSaveOnScroll();
          
          trimExcessRows();
          status.innerText = '';
          isLoading = false;
        })
        .catch(err => {
          console.error(err);
          status.innerText = 'Error loading records.';
          isLoading = false;
        });
    }

    // ✅ Check if we've scrolled 1000px and auto-save
    function checkAndAutoSaveOnScroll() {
      console.log('checkAndAutoSaveOnScroll called');
      
      const currentScrollPosition = window.scrollY || window.pageYOffset;
      const scrollDifference = Math.abs(currentScrollPosition - lastScrollPosition);
      
      console.log('Scroll check:', {
        currentScroll: currentScrollPosition,
        lastScroll: lastScrollPosition,
        difference: scrollDifference,
        threshold: SCROLL_THRESHOLD,
        editedCardsCount: editedCards.size
      });
      
      // Update floating date as user scrolls (always update, not just when editing)
      updateFloatingDate();
      
      // Only auto-save if there are edited cards AND we've scrolled enough
      // Don't close the form on small scrolls
      if (editedCards.size > 0 && scrollDifference >= SCROLL_THRESHOLD) {
        console.log('Scroll threshold reached! Auto-saving...');
        autoSaveAllEditedCards();
        lastScrollPosition = currentScrollPosition;
      }
    }

    // Function to update floating date based on current scroll position
    function updateFloatingDate() {
      const floatingDateElement = document.getElementById('floatingDate');
      if (!floatingDateElement) return;
      
      // Get all lead cards
      const cards = document.querySelectorAll('.lead-card');
      if (cards.length === 0) return;
      
      // Count records for today by checking created_at dates
      let todayRecords = 0;
      const today = new Date();
      const todayDateOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());
      
      cards.forEach(card => {
        const createdAt = card.getAttribute('data-created-at');
        if (createdAt && createdAt !== '-') {
          const cardDateObj = new Date(createdAt);
          // Normalize the date to midnight for proper comparison
          const cardDateOnly = new Date(cardDateObj.getFullYear(), cardDateObj.getMonth(), cardDateObj.getDate());
          
          // Check if card's date is today
          if (cardDateOnly.getTime() === todayDateOnly.getTime()) {
            todayRecords++;
          }
        }
      });
      
      // Always show the floating date
      if (!floatingDateVisible) {
        floatingDateElement.classList.add('visible');
        floatingDateVisible = true;
      }
      
      // Find the card that is currently in view (closest to top)
      let closestCard = null;
      let closestDistance = Infinity;
      const scrollTop = window.scrollY || window.pageYOffset;
      const windowHeight = window.innerHeight;
      const viewportCenter = scrollTop + (window.innerHeight / 2);
      
      cards.forEach(card => {
        const rect = card.getBoundingClientRect();
        const cardTop = rect.top + scrollTop;
        const cardCenter = cardTop + (rect.height / 2);
        const distance = Math.abs(viewportCenter - cardCenter);
        
        if (distance < closestDistance) {
          closestDistance = distance;
          closestCard = card;
        }
      });
      
      // Display the closest card's data
      if (closestCard) {
        // Get the actual created_at timestamp from the card's data attribute
        const createdAt = closestCard.getAttribute('data-created-at');
        
        if (createdAt && createdAt !== '-') {
          // Parse the created_at timestamp (e.g., "2025-11-06 19:49:30")
          const cardDateObj = new Date(createdAt);
          
          if (!isNaN(cardDateObj.getTime())) {
            // Format the card's date and time
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const cardDay = String(cardDateObj.getDate()).padStart(2, '0');
            const cardMonth = months[cardDateObj.getMonth()];
            const cardYear = String(cardDateObj.getFullYear()).slice(-2);
            const cardDateText = `${cardDay} ${cardMonth} ${cardYear}`;
            
            const cardHours = String(cardDateObj.getHours()).padStart(2, '0');
            const cardMinutes = String(cardDateObj.getMinutes()).padStart(2, '0');
            const cardTime = `${cardHours}:${cardMinutes}`;
            
            // Count records from the same date as the closest card and find position
            let cardDateRecords = 0;
            let cardPosition = 0;
            const closestCardDateOnly = new Date(cardDateObj.getFullYear(), cardDateObj.getMonth(), cardDateObj.getDate());
            
            cards.forEach((card, index) => {
              const ca = card.getAttribute('data-created-at');
              if (ca && ca !== '-') {
                const cdo = new Date(ca);
                const cdOnly = new Date(cdo.getFullYear(), cdo.getMonth(), cdo.getDate());
                if (cdOnly.getTime() === closestCardDateOnly.getTime()) {
                  cardDateRecords++;
                  // Check if this is the closest card to get its position
                  if (card === closestCard) {
                    cardPosition = cardDateRecords;
                  }
                }
              }
            });
            
            // Update the display
            currentFloatingDate = cardDateText;
            
            // Determine background color based on card's time (hour)
            const cardHour = cardDateObj.getHours();
            let cardBgColor = 'rgb(172 172 172 / 84%)'; // Light grey for night (so text is visible)
            
            if (cardHour >= 5 && cardHour < 12) {
              cardBgColor = 'rgba(173, 216, 230, 0.8)'; // Light blue (morning)
            } else if (cardHour >= 12 && cardHour < 17) {
              cardBgColor = 'rgba(255, 200, 100, 0.8)'; // Light orange (noon)
            } else if (cardHour >= 17 && cardHour < 20) {
              cardBgColor = 'rgba(200, 120, 60, 0.8)'; // Muted orange (evening)
            } else {
              cardBgColor = 'rgb(172 172 172 / 84%)'; // Light grey for night (so text is visible)
            }
            
            // Display card's date, time, and position within records from same date
            floatingDateElement.textContent = `${cardDateText} ${cardTime} (${cardPosition}/${cardDateRecords})`;
            floatingDateElement.style.background = cardBgColor;
          }
        }
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
      const items = Array.from(container.querySelectorAll('.lead-card'));
      if (items.length <= MAX_ROWS) return;

      const extra = items.length - MAX_ROWS;

      if (offsetEnd - offsetStart > MAX_ROWS) {
        if (lastScrollDirection === 'down') {
          // Check if we need to auto-save edited cards before removing them
          for (let i = 0; i < extra; i++) {
            const card = items[i];
            const leadId = card.getAttribute('data-lead-id');
            
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
            
            card.remove();
            offsetStart++;
          }
        }
        else {
          // Check if we need to auto-save edited cards before removing them
          for (let i = 0; i < extra; i++) {
            const card = items[items.length - 1 - i];
            const leadId = card.getAttribute('data-lead-id');
            
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
            
            card.remove();
            offsetEnd--;
          }
        }
      }
    }

    function resetStateAndReload() {
      favoritesViewActive = false;
      historyViewActive = false;
      const favBtn = document.querySelector('.favorites-btn');
      if (favBtn) favBtn.innerHTML = '<i class="fa-regular fa-heart"></i>';
      const histBtn = document.querySelector('.history-btn');
      if (histBtn) histBtn.innerHTML = '<i class="fa-solid fa-clock-rotate-left"></i>';
      offsetStart = 0;
      offsetEnd = 0;
      clearLeadListDom();
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
          
          // ✅ update that card directly
          updateSingleCard(leadData, data.id || leadData.leadId);
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
      const existingCard = container.querySelector(`.edit-lead-btn[data-code="${code}"]`);
      if (!existingCard) {
        console.warn('No matching card found to update.');
        return;
      }

      // Recreate the updated card using existing function
      const newCard = createRow({
        id: leadId,
        Name: updatedData.Name,
        whatsapp_number: updatedData.whatsapp_number,
        hdd_size: updatedData.hdd_size,
        num_cameras: updatedData.num_cameras,
        camera_resolution: updatedData.camera_resolution,
        dvr_type: updatedData.dvr_type,
        Assign: updatedData.Assign,
        Area: updatedData.Area,
        Follow_up: updatedData.Follow_up,
        comments: updatedData.comments,
        Message: updatedData.Message,
        map_link: updatedData.map_link,
        quote: updatedData.quote,
        created_at: updatedData.created_at || new Date().toISOString()
      });

      const cardContainer = existingCard.closest('.col');
      cardContainer.replaceWith(newCard);

      // Apply gradient save effect on the new container
      newCard.classList.add('save-gradient');
      setTimeout(() => {
        newCard.classList.remove('save-gradient');
      }, 4000); // show effect for 4 seconds
    }

    let container, topSentinel, bottomSentinel, searchInput, clearBtn, status; // Declare variables here
    
    document.addEventListener('DOMContentLoaded', function() {
      console.log('DOMContentLoaded fired.');

      // Assign elements inside DOMContentLoaded
      container = document.getElementById('container');
      topSentinel = document.getElementById('top-sentinel');
      bottomSentinel = document.getElementById('bottom-sentinel');
      searchInput = document.getElementById('searchInput');
      clearBtn = document.getElementById('clearBtn');
      status = document.getElementById('status');

      console.log('Container for event delegation:', container);
      console.log('topSentinel:', topSentinel);
      console.log('bottomSentinel:', bottomSentinel);
      console.log('searchInput:', searchInput);
      console.log('clearBtn:', clearBtn);
      console.log('status:', status);

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
                map_link: ds.mapLink || ''
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

      const favoritesBtn = document.getElementById('favoritesFab');
      const historyBtn = document.getElementById('historyFab');

      function syncMidViewButtons() {
        if (!favoritesBtn || !historyBtn) return;
        favoritesBtn.innerHTML = favoritesViewActive ? '<i class="fa-solid fa-xmark"></i>' : '<i class="fa-regular fa-heart"></i>';
        historyBtn.innerHTML = historyViewActive ? '<i class="fa-solid fa-xmark"></i>' : '<i class="fa-solid fa-clock-rotate-left"></i>';
      }

      syncMidViewButtons();

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
        });
        console.log('Click event listener set up on container.');
      } else {
        console.error('Container element not found, cannot set up click listener.');
      }

      if (searchInput) {
        searchInput.addEventListener('input', () => {
          searchQuery = searchInput.value.trim();
          resetStateAndReload();
        });
      }

      if (clearBtn) {
        clearBtn.addEventListener('click', () => {
          searchInput.value = '';
          searchQuery = '';
          resetStateAndReload();
        });
      }

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
function openQuoteModal(url) {
  quoteIframe.src = url;
  quoteModal.classList.add('active');
  document.documentElement.style.overflow = 'hidden';
document.body.style.height = '100%';
document.body.style.position = 'fixed';
}

// Close button event
closeQuoteModal.addEventListener('click', () => {
  quoteModal.classList.remove('active');
quoteIframe.src = '';
document.documentElement.style.overflow = 'auto';
document.body.style.height = 'auto';
document.body.style.position = 'static';

});

// Close when clicking outside the modal content
quoteModal.addEventListener('click', (e) => {
  if (e.target === quoteModal) {
    quoteModal.classList.remove('active');
    quoteIframe.src = '';
    document.body.style.overflow = 'auto';
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

    function updateSpecsFromForm(cardElement, rid) {
      const hddInput = cardElement.querySelector(`#leadHdd-${rid}`);
      const resInput = cardElement.querySelector(`#leadCameraResolution-${rid}`);
      const camsInput = cardElement.querySelector(`#leadNumCameras-${rid}`);
      const dvrInput = cardElement.querySelector(`#leadDvrType-${rid}`);
      
      // Updated selector: .lead-specs .spec-tag
      const tags = cardElement.querySelectorAll('.lead-specs .spec-tag');
      if (!tags || tags.length < 3) return;

      const hddTag = tags[0];
      const hddVal = hddInput ? hddInput.value : '';
      const hddSpan = hddTag.querySelector('.spec-value, .spec-unknown');
      if (hddSpan) {
        const isUnknown = !hddVal || hddVal.trim().toLowerCase() === 'dont-know';
        hddSpan.textContent = isUnknown ? 'X' : hddVal.trim();
        hddSpan.classList.toggle('spec-unknown', isUnknown);
        hddSpan.classList.toggle('spec-value', !isUnknown);
      } else {
        hddTag.innerHTML = `HDD ${buildSpecSpan(hddVal)}`;
      }

      const resTag = tags[1];
      const resVal = resInput ? resInput.value : '';
      const camsVal = camsInput ? camsInput.value : '';
      const resSpans = resTag.querySelectorAll('.spec-value, .spec-unknown');
      if (resSpans && resSpans.length >= 2) {
        const resSpan = resSpans[0];
        const camsSpan = resSpans[1];
        const resUnknown = !resVal || resVal.trim().toLowerCase() === 'dont-know';
        const camsUnknown = !camsVal || camsVal.trim().toLowerCase() === 'dont-know';
        resSpan.textContent = resUnknown ? 'X' : resVal.trim();
        camsSpan.textContent = camsUnknown ? 'X' : camsVal.trim();
        resSpan.classList.toggle('spec-unknown', resUnknown);
        resSpan.classList.toggle('spec-value', !resUnknown);
        camsSpan.classList.toggle('spec-unknown', camsUnknown);
        camsSpan.classList.toggle('spec-value', !camsUnknown);
      } else {
        resTag.innerHTML = `RES ${buildSpecSpan(resVal)} x ${buildSpecSpan(camsVal)}`;
      }

      const recTag = tags[2];
      const dvrVal = dvrInput ? dvrInput.value : '';
      const recSpan = recTag.querySelector('.spec-value, .spec-unknown');
      if (recSpan) {
        const isUnknown = !dvrVal || dvrVal.trim().toLowerCase() === 'dont-know';
        recSpan.textContent = isUnknown ? 'X' : dvrVal.trim();
        recSpan.classList.toggle('spec-unknown', isUnknown);
        recSpan.classList.toggle('spec-value', !isUnknown);
      } else {
        recTag.innerHTML = `DVR ${buildSpecSpan(dvrVal)}`; // Changed prefix from CAM REC to DVR to match createRow
      }
    }

    function updateAssignFromForm(cardElement, rid) {
      const assignInput = cardElement.querySelector(`#leadAssign-${rid}`);
      const assignVal = assignInput ? assignInput.value.trim() : '';
      
      // Updated selector: .lead-header is where the badge lives now
      const headerSection = cardElement.querySelector('.lead-header');
      if (!headerSection) return;
      
      let assignTag = headerSection.querySelector('.assign-badge');
      if (assignVal) {
        if (!assignTag) {
          assignTag = document.createElement('span');
          assignTag.className = 'assign-badge';
          headerSection.appendChild(assignTag);
        }
        assignTag.textContent = assignVal;
        assignTag.classList.toggle('is-sur', assignVal.toUpperCase() === 'SUR');
      } else if (assignTag) {
        assignTag.remove();
      }
    }

    function setupRealtimeFormBindings(cardElement, rid) {
      const nameInput = cardElement.querySelector(`#leadName-${rid}`);
      const hddInput = cardElement.querySelector(`#leadHdd-${rid}`);
      const resInput = cardElement.querySelector(`#leadCameraResolution-${rid}`);
      const camsInput = cardElement.querySelector(`#leadNumCameras-${rid}`);
      const dvrInput = cardElement.querySelector(`#leadDvrType-${rid}`);
      const assignInput = cardElement.querySelector(`#leadAssign-${rid}`);

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
    }

    });
    

  </script>
</body>
</html>
