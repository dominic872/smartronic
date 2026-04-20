// Main installs functionality - core form handling and data management
(function (window) {
  'use strict';

  // Google API Call Counter
  window.googleApiCalls = window.googleApiCalls || {
    geocoding: 0,
    distanceMatrix: 0,
    staticMaps: 0,
    directions: 0,
    total: 0
  };

  // Create API counter UI
  function createApiCounter() {
    if (document.getElementById('api-counter')) return;

    const counter = document.createElement('div');
    counter.id = 'api-counter';
    counter.style.cssText = `
      position: fixed;
      bottom: 10px;
      right: 10px;
      background: linear-gradient(135deg, #1a237e, #0d47a1);
      color: white;
      padding: 10px 15px;
      border-radius: 8px;
      font-size: 12px;
      z-index: 99999;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      cursor: move;
      min-width: 150px;
      user-select: none;
    `;

    // Inner HTML with Close Button
    counter.innerHTML = `
      <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 6px;">
        <div style="font-weight: 700; font-size: 14px; padding-right: 15px;">
           <i class="fas fa-chart-line"></i> Google API Calls
        </div>
        <div id="api-counter-close" style="cursor: pointer; opacity: 0.7; transition: opacity 0.2s; font-size: 14px;">
           <i class="fas fa-times"></i>
        </div>
      </div>
      <div id="api-counter-content">
        <div style="display: flex; justify-content: space-between;"><span>Total:</span><strong id="api-total">0</strong></div>
      </div>
    `;

    // Toggle detail view state
    let expanded = false;

    // Close logic
    const closeBtn = counter.querySelector('#api-counter-close');
    closeBtn.onmouseover = () => closeBtn.style.opacity = '1';
    closeBtn.onmouseout = () => closeBtn.style.opacity = '0.7';
    closeBtn.onclick = (e) => {
      e.stopPropagation();
      counter.style.display = 'none'; // Hide instead of remove so we can potentially re-show
    };

    // Drag Logic
    let isDragging = false;
    let dragStartX, dragStartY;
    let initialLeft, initialTop;

    counter.onmousedown = (e) => {
      if (e.target.closest('#api-counter-close')) return;

      isDragging = false;
      dragStartX = e.clientX;
      dragStartY = e.clientY;

      const rect = counter.getBoundingClientRect();
      initialLeft = rect.left;
      initialTop = rect.top;

      // Switch to explicit left/top positioning for dragging
      counter.style.right = 'auto';
      counter.style.left = initialLeft + 'px';
      counter.style.top = initialTop + 'px';
      counter.style.transform = 'scale(1.02)';
      counter.style.transition = 'none'; // Disable transition during drag

      document.onmousemove = (e) => {
        // If moved more than 3px, consider it a drag
        if (!isDragging && (Math.abs(e.clientX - dragStartX) > 3 || Math.abs(e.clientY - dragStartY) > 3)) {
          isDragging = true;
        }
        if (isDragging) {
          const dx = e.clientX - dragStartX;
          const dy = e.clientY - dragStartY;
          counter.style.left = (initialLeft + dx) + 'px';
          counter.style.top = (initialTop + dy) + 'px';
        }
      };

      document.onmouseup = () => {
        document.onmousemove = null;
        document.onmouseup = null;
        counter.style.transform = 'scale(1)';
        counter.style.transition = 'all 0.2s';
      };
    };

    // Click handler for expand (only if not dragged)
    counter.onclick = (e) => {
      if (isDragging) return;
      if (e.target.closest('#api-counter-close')) return;

      expanded = !expanded;
      updateApiCounterUI(expanded);
    };

    document.body.appendChild(counter);
  }

  // Update the API counter display
  function updateApiCounterUI(expanded = false) {
    const content = document.getElementById('api-counter-content');
    if (!content) return;

    const c = window.googleApiCalls;

    if (expanded) {
      content.innerHTML = `
        <div style="display: flex; justify-content: space-between; padding: 2px 0;"><span>Geocoding:</span><strong>${c.geocoding}</strong></div>
        <div style="display: flex; justify-content: space-between; padding: 2px 0;"><span>Distance:</span><strong>${c.distanceMatrix}</strong></div>
        <div style="display: flex; justify-content: space-between; padding: 2px 0;"><span>Static Maps:</span><strong>${c.staticMaps}</strong></div>
        <div style="display: flex; justify-content: space-between; padding: 2px 0; border-top: 1px solid rgba(255,255,255,0.3); margin-top: 4px; font-size: 13px;"><span>Total:</span><strong>${c.total}</strong></div>
      `;
    } else {
      content.innerHTML = `
        <div style="display: flex; justify-content: space-between;"><span>Total:</span><strong id="api-total">${c.total}</strong></div>
      `;
    }
  }

  // Increment counter function
  window.incrementApiCounter = function (type) {
    window.googleApiCalls[type] = (window.googleApiCalls[type] || 0) + 1;
    window.googleApiCalls.total++;
    updateApiCounterUI(false);
    console.log(`[API Counter] ${type} call #${window.googleApiCalls[type]} (Total: ${window.googleApiCalls.total})`);
  };

  // Initialize counter on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', createApiCounter);
  } else {
    createApiCounter();
  }

  // Global state
  let editingId = null;

  function updatePdfSentIndicator(status) {
    const indicator = document.getElementById('popup-pdf-sent-indicator');
    if (!indicator) return;
    const normalized = String(status || '').trim().toLowerCase();
    indicator.style.display = normalized === 'yes' ? 'inline-flex' : 'none';
  }

  function syncPdfSentIndicator(id) {
    const orderId = String(id || '').trim();
    if (!orderId) {
      updatePdfSentIndicator('');
      return Promise.resolve();
    }

    return fetch(`../smart/installs.php?id=${encodeURIComponent(orderId)}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      cache: 'no-store'
    })
      .then(response => response.json())
      .then(data => {
        const pdfSent = data && typeof data.pdf_sent !== 'undefined' ? data.pdf_sent : '';
        updatePdfSentIndicator(pdfSent);
        if (window.currentInstallData) {
          window.currentInstallData.pdf_sent = pdfSent;
        }
      })
      .catch(error => {
        console.warn('Unable to sync pdf_sent indicator:', error);
      });
  }

  // Core DOM elements
  const popup = document.getElementById('order-popup');
  const form = document.getElementById('installForm');

  // Save installs data
  function saveInstalls(data, options = {}) {
    if (!data) {
      console.error('No data provided to saveInstalls');
      return;
    }
    const silent = !!options.silent;

    console.log('Attempting to save data:', data);

    const requestHeaders = {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    };

    console.log('Request headers:', requestHeaders);
    console.log('Request body:', JSON.stringify(data));

    fetch('../smart/installs.php', {
      method: 'POST',
      headers: requestHeaders,
      body: JSON.stringify(data)
    })
      .then(response => {
        console.log('Response received:');
        console.log('- Status:', response.status);
        console.log('- StatusText:', response.statusText);
        console.log('- OK:', response.ok);
        console.log('- Headers:', Object.fromEntries(response.headers.entries()));

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return response.text(); // Get as text first to see what we're getting
      })
      .then(responseText => {
        console.log('Raw response text:', responseText);
        console.log('Response length:', responseText.length);

        if (!responseText || responseText.trim() === '') {
          throw new Error('Server returned empty response');
        }

        try {
          const result = JSON.parse(responseText);
          console.log('Parsed response:', result);

          if (result.status === 'ok') {
            if (!silent) alert('Install saved successfully!');
            if (typeof window.render === 'function') {
              window.render();
            }
            // Don't close popup automatically, let user stay in the form
            // if (typeof window.closeOrderPopup === 'function') {
            //   window.closeOrderPopup();
            // }
          } else {
            throw new Error(result.message || 'Save failed with unknown error');
          }
        } catch (parseError) {
          console.error('Failed to parse response as JSON:', parseError);
          console.error('Raw response (first 500 chars):', responseText.substring(0, 500));

          // Check if response looks like HTML
          if (responseText.trim().startsWith('<')) {
            throw new Error('Server returned HTML instead of JSON - check for PHP errors');
          } else {
            throw new Error(`Server returned invalid JSON: ${parseError.message}`);
          }
        }
      })
      .catch(error => {
        console.error('Error saving install:', error);
        console.error('Error name:', error.name);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);

        let errorMessage = 'Failed to save install';
        if (error.message) {
          errorMessage += ': ' + error.message;
        }

        alert(errorMessage);
      });
  }

  // Open form for editing or creating
  function openForm(data = {}) {
    if (!form) {
      console.error('Form element not found!');
      return;
    }

    console.log('Opening form with data:', data); // Debug log

    form.reset();

    // Define the fields that exist in the requirements form
    const validFormFields = [
      'id', 'name', 'cams', 'bullets', 'dome', 'hdd', 'monitor', 'type',
      'location', 'time', 'date', 'owner', 'technician', 'helper',
      'resolution', 'brand', 'cam_type', 'map', 'rack', 'notes'
    ];

    // Small delay to ensure the form is visible before populating
    setTimeout(() => {
      Object.entries(data).forEach(([key, value]) => {
        // Only try to populate fields that exist in our form
        if (validFormFields.includes(key)) {
          const field = form.querySelector(`#${key}`);
          if (field) {
            // Special handling for HDD: normalize spaces so that values like "1TB" match option values like "1 TB"
            if (key === 'hdd' && field.tagName === 'SELECT' && typeof value === 'string') {
              const normalized = value.replace(/\s+/g, '').toLowerCase();
              let matched = false;
              for (let i = 0; i < field.options.length; i++) {
                const optValNorm = (field.options[i].value || '').replace(/\s+/g, '').toLowerCase();
                if (optValNorm === normalized) {
                  field.selectedIndex = i;
                  matched = true;
                  break;
                }
              }
              if (!matched) {
                // fallback to direct assignment
                field.value = value;
              }
            } else {
              field.value = value;
            }
            console.log(`Set field ${key} to:`, value); // Debug log
          } else {
            console.warn(`Expected field ${key} not found in form`);
          }
        }
        // Skip fields that don't belong to this form (no warning needed)
      });
      if (!data.brand && form.brand) {
        form.brand.value = 'PRAMA';
      }
    }, 50);

    editingId = data.id || null;
    window.editingId = editingId; // For backward compatibility
    updatePdfSentIndicator(data.pdf_sent || '');

    if (typeof window.setCurrentOrderId === 'function') {
      window.setCurrentOrderId(editingId);
    }

    if (popup) {
      popup.style.display = 'block';
      document.body.classList.add('popup-open');
    }

    // Load to order items by default and ensure the requirement tab is visible
    if (typeof window.redirectToOrderItems === 'function') {
      window.redirectToOrderItems('');
    }

    setTimeout(() => {
      syncPdfSentIndicator(editingId);
    }, 0);
  }

  // Close form
  function closeForm() {
    if (popup) {
      popup.style.display = 'none';
      document.body.classList.remove('popup-open');
    }
    editingId = null;
    window.editingId = null;
    updatePdfSentIndicator('');
  }

  // Form submission handler
  if (form) {
    form.onsubmit = function (e) {
      e.preventDefault();

      // Collect form data and map to database field names
      const data = {
        id: form.id.value,
        name: form.name.value,
        cams: form.cams.value,           // Maps to quantity in DB
        bullets: form.bullets.value,
        dome: form.dome.value,
        hdd: (form.hdd.value || '').replace(/\s+/g, ''),  // Maps to storage in DB
        monitor: form.monitor.value,
        type: form.type.value,           // Maps to product in DB
        location: form.location.value,   // Maps to area in DB
        time: form.time.value,
        date: form.date.value,
        owner: form.owner.value,
        technician: form.technician.value,
        helper: form.helper.value,
        resolution: form.resolution.value,
        brand: form.brand.value,
        cam_type: form.cam_type.value,
        map: form.map.value,
        rack: form.rack.value,
        notes: form.notes.value
      };

      console.log('Submitting form data:', data); // Debug log
      saveInstalls(data);
    };
  }

  // Edit install function
  function editInstall(id) {
    fetch(`../smart/installs.php?id=${encodeURIComponent(id)}`)
      .then(response => response.json())
      .then(data => {
        if (data) {
          // Map database fields to form fields
          const formData = {
            id: data.idno || data.id,        // Use display ID (S-157) not internal ID
            name: data.name || '',
            cams: data.quantity || data.cams || '',
            bullets: data.bullets || '',
            dome: data.dome || '',
            hdd: data.storage || data.hdd || '',      // Map storage -> hdd
            monitor: data.monitor || '',
            type: data.product || data.type || '',    // Map product -> type
            location: data.area || data.location || '',
            time: data.time || '',
            date: data.date || '',
            owner: data.Owner || data.owner || '',    // Handle case difference
            technician: data.technician || '',
            helper: data.helper || '',
            resolution: data.resolution || '',
            brand: data.brand || '',
            cam_type: data.cam_type || '',
            map: data.Map || data.map || '',          // Handle case difference
            rack: data.rack || '',
            notes: data.notes || data.note || '',     // Handle note vs notes
            pdf_sent: data.pdf_sent || ''
          };

          console.log('Original API data:', data);
          console.log('Mapped form data:', formData);

          // Store the data globally for reference
          window.currentInstallData = formData;
          openForm(formData);
          syncPdfSentIndicator(formData.id || id);

          // Load payment data when switching to invoice tab
          setTimeout(() => {
            if (typeof window.loadPaymentData === 'function') {
              window.loadPaymentData(id);
            }

            // Automatically generate quote details
            if (typeof window.getQuoteDetails === 'function') {
              window.getQuoteDetails(formData);
            }
          }, 500);
        }
      })
      .catch(error => {
        console.error('Error loading install:', error);
        alert('Failed to load install data');
      });
  }

  // Delete install function
  function deleteInstall(id) {
    const authRole = getCookie('auth_role');
    if (authRole !== 'admin') {
      const ev = (window.allInstalls || []).find(x => String(x.id) === String(id));
      const isUnscheduled = !ev || !ev.date || String(ev.date).trim() === '' || String(ev.date).trim() === '0000-00-00';
      if (!isUnscheduled) {
        alert('Only admin users can delete scheduled installs.');
        return;
      }
    }

    if (!confirm('Are you sure you want to delete this install?')) return;

    fetch('../smart/installs.php', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: id })
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          if (typeof window.render === 'function') {
            window.render();
          }
        } else {
          alert(data.message || 'Failed to delete install');
        }
      })
      .catch(error => {
        console.error('Error deleting install:', error);
        alert('Failed to delete install');
      });
  }

  // Helper function to get cookie value
  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
  }

  // WhatsApp integration
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
*ID*: ${ev.id || ""}
*SMARTONIC*: ${ev.owner || ""}
*HELPER*: ${ev.helper || ""}
*NOTES*: ${ev.notes || "No Notes"}\n
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

      const encodedMessage = encodeURIComponent(message.trim());
      const whatsappUrl = `https://wa.me/${phone}?text=${encodedMessage}`;
      window.open(whatsappUrl, '_blank');
    } catch (error) {
      console.error('Error sending to WhatsApp:', error);
      alert('Failed to send WhatsApp message');
    }
  }

  // Toggles map preview for all events in the day
  window.toggleDayMaps = async function (el) {
    console.log('toggleDayMaps called', el);
    const day = el.closest('.day');
    if (!day) {
      console.error('Child day element not found');
      return;
    }

    // Prevent multiple clicks
    if (day.dataset.loading === 'true') return;
    day.dataset.loading = 'true';

    // Show loading state on globe button
    const globeBtn = day.querySelector('.day-map-toggle');
    const globeIcon = globeBtn ? globeBtn.querySelector('i') : null;
    if (globeIcon) {
      globeIcon.classList.remove('fa-globe');
      globeIcon.classList.add('fa-spinner', 'fa-spin');
    }

    // Reset loading state helper
    const resetLoading = () => {
      day.dataset.loading = 'false';
      if (globeIcon) {
        globeIcon.classList.remove('fa-spinner', 'fa-spin');
        globeIcon.classList.add('fa-globe');
      }
    };

    // Toggle logic: if master map exists, remove it and associated markers
    const existingMap = day.querySelector('.master-map-preview');
    if (existingMap) {
      existingMap.remove();
      // Also remove any temporary marker badges we added
      day.querySelectorAll('.map-marker-badge').forEach(b => b.remove());
      resetLoading();
      return;
    }

    const locations = [];
    const promises = [];
    let markerIndex = 1;

    // Helper to add location with name
    const addLocation = async (latLngOrAddress, element, label) => {
      // 1. Create Badge Immediately (Loading State)
      const badge = document.createElement('span');
      badge.className = 'map-marker-badge';
      badge.style.cssText = `
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 24px;
        background: #e0e0e0;
        color: #999;
        border-radius: 12px;
        padding: 0 8px;
        font-size: 11px;
        font-weight: 700;
        margin-left: 8px;
        box-shadow: none;
        border: 2px solid #fff;
        cursor: wait;
        transition: all 0.2s ease;
      `;
      badge.innerHTML = '<i class="fas fa-circle-notch fa-spin" style="font-size: 10px;"></i>';
      badge.title = 'Resolving location...';

      // Insert badge (order matters, so we append immediately)
      const nameLink = element.querySelector('.name-link') || element.querySelector('h3 a') || element.querySelector('.note-title') || element.querySelector('h3');
      if (nameLink) {
        nameLink.appendChild(badge);
      } else {
        element.appendChild(badge);
      }

      // 2. Resolve Maps URL eagerly
      const isMapsUrl = latLngOrAddress.match(/https?:\/\/[^\s]*(?:maps|goo\.gl)[^\s]*/i) || latLngOrAddress.match(/place\//);
      if (isMapsUrl) {
        try {
          const response = await fetch(`../smart/resolve_map_url.php?url=${encodeURIComponent(latLngOrAddress)}`);
          const data = await response.json();
          if (data.success && data.coords) {
            console.log(`[Map Debug] Resolved Short URL ${latLngOrAddress} to ${data.coords.lat},${data.coords.lng}`);
            latLngOrAddress = `${data.coords.lat},${data.coords.lng}`;
          }
        } catch (e) {
          console.warn(`[Map Debug] Failed to resolve short URL ${latLngOrAddress}`, e);
        }
      }

      // 3. Update Badge to Active State
      badge.style.background = 'linear-gradient(135deg, #e53935 0%, #c62828 100%)';
      badge.style.color = 'white';
      badge.style.boxShadow = '0 2px 6px rgba(229, 57, 53, 0.4)';
      badge.style.cursor = 'pointer';
      badge.innerText = label;
      badge.title = 'Click to get Plus Code';
      badge.onclick = async (e) => {
        // ... (existing click handler logic will be here, we need to adapt it or leave it)
        // Since we are rewriting the function, we need to include the click handler logic.
        // We will invoke a shared handler or inline it.
        e.stopPropagation();
        const loc = latLngOrAddress; // Use the RESOLVED location
        // Reuse the logic from previous implementation (simplified)
        badge.innerText = '...';
        try {
          // ... existing logic ...
          // We can just call a helper or inline. The logic for OLC is standardized now.
          let position = null;
          const coordsMatch = loc.match(/^(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)$/);
          if (coordsMatch) {
            position = { lat: parseFloat(coordsMatch[1]), lng: parseFloat(coordsMatch[2]) };
          } else {
            // Geocode address fallback
            const geocoder = new google.maps.Geocoder();
            const result = await new Promise((resolve, reject) => {
              geocoder.geocode({ address: loc }, (res, stat) => {
                if (stat === 'OK' && res[0]) resolve(res[0].geometry.location);
                else reject(new Error('Geocoding failed'));
              });
            });
            position = { lat: result.lat(), lng: result.lng() };
          }

          if (typeof OpenLocationCode !== 'undefined') {
            const lat = typeof position.lat === 'function' ? position.lat() : position.lat;
            const lng = typeof position.lng === 'function' ? position.lng() : position.lng;
            const plusCode = OpenLocationCode.encode(parseFloat(lat), parseFloat(lng));
            await navigator.clipboard.writeText(plusCode);
            badge.innerText = '✓';
            setTimeout(() => badge.innerText = label, 1500);
          }
        } catch (err) {
          console.error(err);
          badge.innerText = '!';
        }
      };

      // Extract name from element
      let name = '';
      if (element.classList.contains('event')) {
        const nameLink = element.querySelector('.name-link') || element.querySelector('h3 a');
        name = nameLink ? nameLink.textContent.trim() : '';
        if (!name) {
          const h3 = element.querySelector('h3');
          name = h3 ? h3.textContent.replace(/\d+-\d+/, '').trim() : '';
        }
      } else if (element.classList.contains('note')) {
        const noteTitle = element.querySelector('.note-title');
        name = noteTitle ? noteTitle.textContent.trim() : '';
      }

      // Return the location object instead of pushing
      return {
        location: latLngOrAddress,
        label: label,
        name: name || `Location ${label}`,
        element: element
      };
    };

    // Add CSS animation if not already added
    if (!document.getElementById('map-badge-styles')) {
      const style = document.createElement('style');
      style.id = 'map-badge-styles';
      style.textContent = `
        @keyframes badge-pulse {
          0%, 100% { transform: scale(1); }
          50% { transform: scale(1.05); }
        }
        .map-marker-badge:hover {
          transform: scale(1.15) !important;
          box-shadow: 0 4px 12px rgba(229, 57, 53, 0.6) !important;
        }
        
        /* Date hover effect - show "Open Map" */
        .day .date {
          position: relative;
          cursor: pointer;
          transition: all 0.2s ease;
        }
        .day .date:hover {
          opacity: 0.85;
        }
        .day .date::after {
          content: '🗺️ Open Map';
          position: absolute;
          left: 50%;
          top: 50%;
          transform: translate(-50%, -50%);
          background: rgba(0,0,0,0.85);
          color: white;
          padding: 4px 10px;
          border-radius: 4px;
          font-size: 11px;
          font-weight: 600;
          white-space: nowrap;
          opacity: 0;
          pointer-events: none;
          transition: opacity 0.2s ease;
          z-index: 10;
        }
        .day .date:hover::after {
          opacity: 1;
        }
      `;
      document.head.appendChild(style);
    }

    // 1. Process Events
    const events = day.querySelectorAll('.event');
    events.forEach(ev => {
      const areaSpan = ev.querySelector('.area');
      if (!areaSpan) return;

      const link = areaSpan.querySelector('a');
      let location = '';
      if (link) {
        // location = link.innerText.trim(); // Prefer HREF parsing
        const href = link.href;
        if (href) {
          const qMatch = href.match(/[?&]q=([^&]+)/);
          const llMatch = href.match(/[?&]ll=([^&]+)/);
          const atMatch = href.match(/@([\d.-]+,[\d.-]+)/);

          if (qMatch) location = decodeURIComponent(qMatch[1]);
          else if (llMatch) location = decodeURIComponent(llMatch[1]);
          else if (atMatch) location = atMatch[1];
          // Check if it's a Google Maps URL (short or long) that didn't match above params
          else if (href.match(/https?:\/\/[^\s]*(?:maps|goo\.gl)[^\s]*/i) || href.match(/place\//)) {
            location = href;
          }
          else location = link.innerText.trim();
        } else {
          location = link.innerText.trim();
        }
      }

      if (location) {
        // Generate a label. 1-9, then A-Z
        let label = markerIndex <= 9 ? markerIndex.toString() : String.fromCharCode('A'.charCodeAt(0) + (markerIndex - 10));
        if (markerIndex > 35) label = '.'; // Fallback for too many

        promises.push(addLocation(location, ev, label));
        markerIndex++;
      }
    });

    // 2. Process Notes (look for links AND text content in ANY part of the note)
    const notes = day.querySelectorAll('.note');
    console.log(`[Map Debug] Found ${notes.length} notes in day ${day.dataset.date}`);

    notes.forEach(note => {
      let foundLocationInNote = false;

      // First, search all links in the note (desc, title if any, etc)
      const links = note.querySelectorAll('a');
      console.log(`[Map Debug] Note ${note.dataset.id}: found ${links.length} links`);

      links.forEach(link => {
        if (foundLocationInNote) return; // Only one location per note

        let location = '';
        const href = link.href || '';
        const linkText = link.textContent || '';
        console.log(`[Map Debug] Checking link - href: "${href}", text: "${linkText}"`);

        // Skip tel: links
        if (href.startsWith('tel:')) return;

        // Check for ANY Google Maps URL formats
        const isMapUrl = href.includes('maps.google') ||
          href.includes('goo.gl') ||
          href.includes('google.com/maps') ||
          href.includes('maps.app') ||
          href.includes('/maps/') ||
          href.includes('map') && href.includes('google');

        if (isMapUrl) {
          console.log(`[Map Debug] Detected map URL: ${href}`);

          // Try various extraction patterns
          const qMatch = href.match(/[?&]q=([^&]+)/);
          const llMatch = href.match(/[?&]ll=([^&]+)/);
          const atMatch = href.match(/@([\d.-]+,[\d.-]+)/);
          const destMatch = href.match(/[?&]destination=([^&]+)/);
          const queryMatch = href.match(/[?&]query=([^&]+)/);
          // Plus codes format: /maps/place/XXXX+XXX
          const plusCodeMatch = href.match(/\/place\/([A-Z0-9+]+[^/]*)/i);
          // Direct coordinates in path: /12.9716,77.5946
          const pathCoordsMatch = href.match(/\/(-?\d+\.?\d*),(-?\d+\.?\d*)/);

          if (qMatch) location = decodeURIComponent(qMatch[1]);
          else if (destMatch) location = decodeURIComponent(destMatch[1]);
          else if (queryMatch) location = decodeURIComponent(queryMatch[1]);
          else if (llMatch) location = decodeURIComponent(llMatch[1]);
          else if (atMatch) location = atMatch[1];
          else if (plusCodeMatch) location = decodeURIComponent(plusCodeMatch[1]);
          else if (pathCoordsMatch) location = `${pathCoordsMatch[1]},${pathCoordsMatch[2]}`;
          else {
            // Fallback: use the whole URL and let Google geocode it
            location = href;
          }
        }

        if (location) {
          console.log(`[Map Debug] ✓ Extracted location from note link: "${location}"`);
          let label = markerIndex <= 9 ? markerIndex.toString() : String.fromCharCode('A'.charCodeAt(0) + (markerIndex - 10));
          if (markerIndex > 35) label = '.';
          promises.push(addLocation(location, note, label));
          markerIndex++;
          foundLocationInNote = true;
        }
      });

      // If no location found in links, check .note-desc text content for coordinates or map URLs
      if (!foundLocationInNote) {
        const noteDesc = note.querySelector('.note-desc');
        if (noteDesc) {
          const textContent = noteDesc.textContent || '';
          const innerHTML = noteDesc.innerHTML || '';
          console.log(`[Map Debug] Note ${note.dataset.id} desc text: "${textContent.substring(0, 150)}..."`);

          let location = '';

          // Look for "Map:" or "Location:" prefix in text
          const mapFieldMatch = textContent.match(/(?:Map|Location|Address)\s*[:：]\s*(.+?)(?:\||$|\n)/i);
          if (mapFieldMatch) {
            location = mapFieldMatch[1].trim();
            console.log(`[Map Debug] Found Map field in text: "${location}"`);
          }

          // Look for lat,lng coordinates (e.g., "12.9716,77.5946" or "12.9716, 77.5946")
          if (!location) {
            const coordsMatch = textContent.match(/(-?\d{1,3}\.\d{3,})\s*,\s*(-?\d{1,3}\.\d{3,})/);
            if (coordsMatch) {
              location = `${coordsMatch[1]},${coordsMatch[2]}`;
              console.log(`[Map Debug] Found coordinates in text: "${location}"`);
            }
          }

          // Look for Google Maps URLs in text (that might not be linkified)
          if (!location) {
            const urlMatch = textContent.match(/https?:\/\/[^\s]*(?:maps|goo\.gl)[^\s]*/i);
            if (urlMatch) {
              const url = urlMatch[0];
              console.log(`[Map Debug] Found URL in text: "${url}"`);

              // Try to extract location from the URL
              const qMatch = url.match(/[?&]q=([^&\s]+)/);
              const llMatch = url.match(/[?&]ll=([^&\s]+)/);
              const atMatch = url.match(/@([\d.-]+,[\d.-]+)/);

              if (qMatch) location = decodeURIComponent(qMatch[1]);
              else if (llMatch) location = decodeURIComponent(llMatch[1]);
              else if (atMatch) location = atMatch[1];
              else location = url; // Use the whole URL as fallback
            }
          }

          // Look for Plus Codes (e.g., "7J4V+W74" or "7J4V+W74 Bangalore")
          if (!location) {
            const plusCodeMatch = textContent.match(/\b([A-Z0-9]{4,8}\+[A-Z0-9]{2,}(?:\s+[A-Za-z\s,]+)?)/);
            if (plusCodeMatch) {
              location = plusCodeMatch[1].trim();
              console.log(`[Map Debug] Found Plus Code: "${location}"`);
            }
          }

          if (location) {
            console.log(`[Map Debug] ✓ Using location from note desc: "${location}"`);
            let label = markerIndex <= 9 ? markerIndex.toString() : String.fromCharCode('A'.charCodeAt(0) + (markerIndex - 10));
            if (markerIndex > 35) label = '.';
            promises.push(addLocation(location, note, label));
            markerIndex++;
          } else {
            console.log(`[Map Debug] ✗ No location found in note ${note.dataset.id}`);
          }
        }
      }
    });

    // Wait for all locations to be processed
    try {
      // Promise.all ensures we wait for resolution, but we need to collect the results
      // which are the location objects (or null if failed/empty?)
      // addLocation always returns an object.
      const resolvedLocations = await Promise.all(promises);
      resolvedLocations.forEach(loc => {
        if (loc) locations.push(loc);
      });
    } catch (err) {
      console.error('Error processing locations:', err);
    }

    resetLoading(); // Finished processing

    console.log(`[Map Debug] Total locations collected: ${locations.length}`, locations);

    if (locations.length === 0) {
      console.log('No locations found to map');
      return;
    }

    // Helper to check if location is geocodable (not a short URL)
    function isGeocodable(loc) {
      const location = loc.location || '';
      // Short URLs can't be geocoded by Static Maps API
      if (location.includes('goo.gl/') || location.includes('maps.app/')) {
        console.log(`[Map Debug] Short URL cannot be geocoded: ${location}`);
        return false;
      }
      return true;
    }

    // Filter locations for static map (only geocodable ones)
    const geocodableLocations = locations.filter(isGeocodable);
    console.log(`[Map Debug] Geocodable locations for static map: ${geocodableLocations.length}`);

    // Build Static Map URL with only geocodable locations
    window.incrementApiCounter('staticMaps');
    const key = 'AIzaSyB7BKkBQEI0WpbFFjn8K4VWKRaYeIs3GhU';
    let url = `https://maps.googleapis.com/maps/api/staticmap?size=600x350&maptype=roadmap&key=${key}`;

    geocodableLocations.forEach(loc => {
      url += `&markers=color:red%7Clabel:${loc.label}%7C${encodeURIComponent(loc.location)}`;
    });

    // Also add a message if some locations couldn't be mapped
    const unmappedCount = locations.length - geocodableLocations.length;
    if (unmappedCount > 0) {
      console.log(`[Map Debug] ${unmappedCount} location(s) have short URLs and will only show in full map view`);
    }

    // Determine placement: Insert after the day header (date + globe)
    const headerEl = day.querySelector('.day-header') || day.querySelector('.date');
    const img = document.createElement('img');
    img.className = 'master-map-preview';
    img.src = url;
    img.style.width = '100%';
    img.style.height = 'auto'; // Maintain aspect ratio
    img.style.maxHeight = '350px';
    img.style.objectFit = 'contain';
    img.style.borderRadius = '8px';
    img.style.marginBottom = '10px';
    img.style.border = '1px solid #ccc';
    img.style.marginTop = '10px';
    img.style.cursor = 'pointer';
    img.title = 'Click to open full map view';

    // Store locations data on the image for later use
    img._locationsData = locations;
    img._dayElement = day;

    // Click handler to open full map popup
    img.onclick = function () {
      openFullMapPopup(this._locationsData, this._dayElement);
    };

    if (headerEl && headerEl.parentNode === day) {
      headerEl.after(img);
    } else {
      day.appendChild(img);
    }
  };

  // Full Map Popup with interactive Google Map and horizontal event/note list
  function openFullMapPopup(locations, dayElement) {
    // Remove existing popup if any
    const existingPopup = document.getElementById('full-map-popup');
    if (existingPopup) existingPopup.remove();

    // Create popup backdrop
    const backdrop = document.createElement('div');
    backdrop.id = 'full-map-popup';
    backdrop.style.cssText = `
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.85);
      z-index: 99999;
      display: flex;
      flex-direction: column;
      padding: 20px;
      box-sizing: border-box;
    `;

    // Close button
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
    closeBtn.style.cssText = `
      position: absolute;
      top: 15px;
      right: 15px;
      background: #fff;
      border: none;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      font-size: 18px;
      cursor: pointer;
      z-index: 100001;
      box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    `;
    closeBtn.onclick = () => backdrop.remove();
    backdrop.appendChild(closeBtn);

    // Map container
    const mapContainer = document.createElement('div');
    mapContainer.id = 'full-map-container';
    mapContainer.style.cssText = `
      flex: 1;
      min-height: 300px;
      border-radius: 12px;
      overflow: hidden;
      margin-bottom: 15px;
    `;
    backdrop.appendChild(mapContainer);

    // Horizontal scroll container for events/notes
    const listContainer = document.createElement('div');
    listContainer.style.cssText = `
      display: flex;
      gap: 15px;
      overflow-x: auto;
      padding: 10px 0;
      scroll-snap-type: x mandatory;
      -webkit-overflow-scrolling: touch;
    `;

    // Collect events and notes from the day element
    const events = dayElement.querySelectorAll('.event');
    const notes = dayElement.querySelectorAll('.note');

    // Store markers globally for card interaction
    let markersMap = {};

    // Add events to list - only show items that have map locations
    // Use the locations array to determine which items have locations
    const itemsWithLocations = locations.map(loc => loc.element);

    locations.forEach((loc, idx) => {
      const element = loc.element;
      const type = element.classList.contains('event') ? 'event' : 'note';
      const card = createItemCard(element, type, loc.label, loc.name, loc.location);

      // Make card clickable to open marker info window
      card.style.cursor = 'pointer';
      card.dataset.label = loc.label;
      card.onclick = () => {
        const marker = markersMap[loc.label];
        if (marker && marker.infoWindow) {
          // Close any open info windows first
          Object.values(markersMap).forEach(m => {
            if (m.infoWindow) m.infoWindow.close();
          });
          marker.infoWindow.open(marker.map, marker.marker);
          // Scroll map to marker
          marker.map.panTo(marker.marker.getPosition());
        }
      };

      listContainer.appendChild(card);
    });

    // Show message if no items have locations
    if (locations.length === 0) {
      const noItemsMsg = document.createElement('div');
      noItemsMsg.style.cssText = 'color: white; padding: 20px; text-align: center; width: 100%;';
      noItemsMsg.textContent = 'No items with map locations found for this day.';
      listContainer.appendChild(noItemsMsg);
    }

    backdrop.appendChild(listContainer);

    // Add to DOM
    document.body.appendChild(backdrop);
    document.body.style.overflow = 'hidden';

    // Helper function to close popup and restore scroll
    const closePopup = () => {
      backdrop.remove();
      document.body.style.overflow = '';
      document.body.style.height = '';
    };

    // Close button handler
    closeBtn.onclick = closePopup;

    // Close on backdrop click (but not on map or cards)
    backdrop.onclick = (e) => {
      if (e.target === backdrop) {
        closePopup();
      }
    };

    // Close on ESC key
    const escHandler = (e) => {
      if (e.key === 'Escape') {
        closePopup();
        document.removeEventListener('keydown', escHandler);
      }
    };
    document.addEventListener('keydown', escHandler);

    // Initialize the Google Map and get markers reference
    initFullMap(mapContainer, locations, (markers) => {
      markersMap = markers;
    });
  }

  // Helper to create item cards for the horizontal list
  function createItemCard(element, type, label, name, locationStr) {
    const card = document.createElement('div');
    card.style.cssText = `
      flex: 0 0 280px;
      background: #1e1e1e;
      color: #ffffff;
      border-radius: 6px;
      padding: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.5);
      scroll-snap-align: start;
      position: relative;
      border: 1px solid #333;
    `;

    // Index badge with fancy styling
    const badge = document.createElement('span');
    badge.textContent = label;
    badge.style.cssText = `
      position: absolute;
      top: 4px;
      left: 9px;
      background: linear-gradient(135deg, rgb(229, 57, 53) 0%, rgb(198, 40, 40) 100%);
      color: rgb(255, 255, 255);
      min-width: 24px;
      height: 24px;
      border-radius: 4px;
      padding: 0px 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 11px;
      box-shadow: rgba(229, 57, 53, 0.5) 0px 3px 8px;
      border: 2px solid rgb(30, 30, 30);
      z-index: 2;
    `;
    card.appendChild(badge);

    // Location display helper
    const locHtml = locationStr ? `<div style="font-size: 11px; color: #aaa; margin-top: 8px; border-top: 1px solid #444; padding-top: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><i class="fas fa-map-pin" style="margin-right: 4px;"></i> ${locationStr}</div>` : '';

    if (type === 'event') {
      // Replicate .event styling logic
      // Check classes for border color
      if (element.classList.contains('DVR')) card.style.borderLeft = '6px solid #1976d2';
      else if (element.classList.contains('NVR')) card.style.borderLeft = '6px solid #388e3c';
      else if (element.classList.contains('WIFI')) card.style.borderLeft = '6px solid #f57c00';
      
      if (element.classList.contains('missing')) card.style.border = '2px dashed red';

      // Extract event details
      const h3 = element.querySelector('h3');
      // Clone content or extract text. Let's try to clone structure but style for dark mode
      let nameText = h3 ? h3.textContent.trim() : 'Event';
      
      // Parse name: C49Paul jagadish 23 -> C-49 | Paul jagadish
      // Regex to split ID, Name and remove trailing number
      // Assuming ID starts with C or S followed by digits
      // Expanded regex to be more permissive with whitespace and ID formats
      // Original: /^([CS]\d+)(.+?)(?:\s+\d+)?$/i
      // New: Capture ID (start), Name (middle), and optional trailing number (end)
      const nameMatch = nameText.match(/^([A-Z-]*\d+)\s*(.+?)(?:\s+\d+)?$/i);
      
      if (nameMatch) {
          let id = nameMatch[1];
          // Insert hyphen if missing (C49 -> C-49) and not already hyphenated
          if (!id.includes('-') && /^[A-Z]+\d+$/i.test(id)) {
             id = id.replace(/^([A-Z]+)(\d+)$/i, '$1-$2');
          }
          
          const name = nameMatch[2].trim();
          nameText = `${id} | ${name}`;
      } else {
          // Fallback if regex fails but we want to ensure pipe format if possible
          // e.g. if h3 text is just "C-49 Name"
          // Check if it starts with ID pattern
          const splitMatch = nameText.match(/^([A-Z]+-?\d+)\s+(.+)$/i);
          if (splitMatch) {
               nameText = `${splitMatch[1]} | ${splitMatch[2]}`;
          }
      }

      // Get details
      const details = element.querySelector('.details');
      let detailsHtml = '';
      if (details) {
          // Clone and modify styles for dark mode
          const dClone = details.cloneNode(true);
          
          // Apply shared styles for .cams, .hdd, .cams.type: inline-block, white bg, black text, padding, border
          const detailSpans = dClone.querySelectorAll('.cams, .hdd');
          detailSpans.forEach(el => {
             // Reset existing classes impact if needed, or just override
             el.style.display = 'inline-block';
             el.style.background = 'white';
             el.style.color = '#000000';
             el.style.border = '1px solid #ccc'; // defaulting border
             el.style.fontSize = '12.8px';
             el.style.padding = '0 4px';
             el.style.borderRadius = '3px'; // Optional: slight rounding usually looks better
             el.style.marginRight = '4px';
             el.style.marginBottom = '4px';
             
             // Specific overrides if class is .cams.type
             if (el.classList.contains('type')) {
                 el.style.border = '1px solid #000';
             }
          });

          // Remove .area from details clone to avoid duplicate display
          const areaInDetails = dClone.querySelector('.area');
          if (areaInDetails) {
              areaInDetails.remove();
          }
          
          detailsHtml = dClone.innerHTML;
      }
      
      const area = element.querySelector('.area');
       let areaText = '';
       
       if (area) {
           // Safer extraction: split distance from address
           const aClone = area.cloneNode(true);
           const distSpan = aClone.querySelector('span[id^="dist-"]');
           const distText = distSpan ? distSpan.textContent.trim() : '';
           
           // Remove distance span from clone to get the rest of the text
           if (distSpan) distSpan.remove();
           
           let restText = aClone.textContent.trim();
           
           // Clean up: remove dist-F-xxxx patterns if present
           restText = restText.replace(/dist-[\w-]+/gi, '').trim();
           
           if (distText && !isNaN(parseFloat(distText))) {
               areaText = `${distText} KM | ${restText}`;
           } else {
               areaText = restText;
           }
       }

      const names = element.querySelector('.names');
      let namesHtml = '';
      if (names) {
         // We can just extract text or clone structure. Structure is better.
         const nClone = names.cloneNode(true);
         // Styles for .name-item .number need to be lighter
         nClone.querySelectorAll('.number').forEach(el => el.style.color = '#aaa');
         // Styles for links (a tags) to be white
         nClone.querySelectorAll('a').forEach(el => {
             el.style.color = '#ffffff';
             el.style.textDecoration = 'none'; // Optional: remove underline if desired, but color is the request
         });
         nClone.style.borderTop = '1px dashed #444';
         namesHtml = nClone.outerHTML;
      }

      card.innerHTML += `
        <div style="font-weight: 600; font-size: 18px; margin-bottom: 6px; color: #fff; padding-left: 45px;">${nameText}</div>
        <div style="font-size: 12px; color: #ccc; margin-bottom: 5px;">${detailsHtml}</div>
        <div style="font-size: 11px; color: #ccc;">${namesHtml}</div>
        ${locHtml}
      `;
    } else {
      // Extract note details
      const title = element.querySelector('.note-title');
      const desc = element.querySelector('.note-desc');
      const noteType = element.querySelector('.note-type');
      const phone = element.querySelector('.note-phone');

      const typeColor = {
        'issue': '#dc3545',
        'inspection': '#fd7e14',
        'general': '#28a745'
      }[(noteType?.textContent || '').toLowerCase()] || '#28a745';

      card.style.borderLeft = `4px solid ${typeColor}`;

      // Update styling as requested: 12px uppercase for type, 18px for title, padding-left 45px
      card.innerHTML += `
        <div style="font-weight: 600; font-size: 12px; color: ${typeColor}; text-transform: uppercase; margin-bottom: 5px; padding-left: 45px;">${noteType?.textContent || 'Note'}</div>
        <div style="font-weight: 600; font-size: 18px; color: #fff; margin-bottom: 20px; padding-left: 45px;">${title?.textContent || ''}</div>
        ${desc ? `<div style="font-size: 12px; color: #ccc; margin-bottom: 5px; max-height: 60px; overflow: hidden;">${desc.textContent.substring(0, 100)}${desc.textContent.length > 100 ? '...' : ''}</div>` : ''}
        ${phone ? `<div style="font-size: 12px; color: #64b5f6;"><i class="fas fa-phone"></i> ${phone.textContent.trim()}</div>` : ''}
        ${locHtml}
      `;
    }
    return card;
  }

  // Initialize interactive Google Map with markers
  function initFullMap(container, locations, onMarkersReady) {
    // Default center (Bangalore) and origin for distance calculation
    const defaultCenter = { lat: 12.9716, lng: 77.5946 };
    const hsrLayout = { lat: 12.9121, lng: 77.6446 }; // HSR Layout, Bangalore

    const map = new google.maps.Map(container, {
      zoom: 11,
      center: defaultCenter,
      mapTypeId: 'roadmap',
      styles: [
        { featureType: 'poi', stylers: [{ visibility: 'simplified' }] }
      ]
    });

    const bounds = new google.maps.LatLngBounds();
    const geocoder = new google.maps.Geocoder();
    const distanceService = new google.maps.DistanceMatrixService();
    let markersPlaced = 0;
    const totalLocations = locations.length;
    const markersMap = {}; // Store markers for card interaction

    // Helper to calculate distance from HSR Layout
    function calculateDistance(position, callback) {
      distanceService.getDistanceMatrix({
        origins: [hsrLayout],
        destinations: [position],
        travelMode: google.maps.TravelMode.DRIVING,
        unitSystem: google.maps.UnitSystem.METRIC
      }, (response, status) => {
        if (status === 'OK' && response.rows[0].elements[0].status === 'OK') {
          const distance = response.rows[0].elements[0].distance.text;
          const duration = response.rows[0].elements[0].duration.text;
          callback({ distance, duration });
        } else {
          callback(null);
        }
      });
    }

    // Helper to check if it's a short URL
    function isShortUrl(location) {
      return location.includes('goo.gl/') || location.includes('maps.app/');
    }

    // Helper to extract coordinates from resolved URL
    function extractCoordsFromUrl(url) {
      // Try @lat,lng format
      const atMatch = url.match(/@(-?\d+\.?\d*),(-?\d+\.?\d*)/);
      if (atMatch) return { lat: parseFloat(atMatch[1]), lng: parseFloat(atMatch[2]) };

      // Try ?q=lat,lng format
      const qMatch = url.match(/[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/);
      if (qMatch) return { lat: parseFloat(qMatch[1]), lng: parseFloat(qMatch[2]) };

      // Try /place/lat,lng format
      const placeMatch = url.match(/\/place\/(-?\d+\.?\d*),(-?\d+\.?\d*)/);
      if (placeMatch) return { lat: parseFloat(placeMatch[1]), lng: parseFloat(placeMatch[2]) };

      return null;
    }

    // Callback when all markers are placed
    function onComplete() {
      fitBounds(map, bounds);
      if (typeof onMarkersReady === 'function') {
        onMarkersReady(markersMap);
      }
    }

    // Create a marker with distance calculation, Plus Code, and nearby distances
    function createMarkerWithDistance(position, label, name, isShortUrl = false) {
      const marker = new google.maps.Marker({
        position: position,
        map: map,
        label: {
          text: label,
          color: '#fff',
          fontWeight: 'bold'
        },
        icon: {
          path: google.maps.SymbolPath.CIRCLE,
          scale: 14,
          fillColor: isShortUrl ? '#ff9800' : '#e53935',
          fillOpacity: 1,
          strokeColor: '#fff',
          strokeWeight: 2
        },
        title: name || `Location ${label}`
      });

      bounds.extend(position);

      // Get lat/lng for links
      const lat = typeof position.lat === 'function' ? position.lat() : position.lat;
      const lng = typeof position.lng === 'function' ? position.lng() : position.lng;
      const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;

      // Calculate Plus Code using OpenLocationCode library (accurate, no API call)
      let plusCode = null;
      try {
        if (typeof OpenLocationCode !== 'undefined') {
          // Ensure coordinates are numbers
          const numLat = parseFloat(lat);
          const numLng = parseFloat(lng);
          if (!isNaN(numLat) && !isNaN(numLng)) {
            plusCode = OpenLocationCode.encode(numLat, numLng);
            console.log(`[Plus Code] Calculated locally: ${plusCode} for ${numLat},${numLng}`);
          }
        }
      } catch (e) {
        console.warn('[Plus Code] Error calculating:', e);
      }

      // Format coordinates for display (debugging)
      const coordDisplay = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

      // Create initial loading content
      const loadingContent = `
        <div style="padding: 12px; min-width: 250px; max-width: 320px;">
          <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
            <span style="background: linear-gradient(135deg, #e53935, #c62828); color: white; min-width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px;">${label}</span>
            <strong style="font-size: 14px; color: #333;">${name}</strong>
          </div>
          <div style="font-size: 12px; color: #666; padding: 8px 0; text-align: center;">
            <i class="fas fa-spinner fa-spin"></i> Loading distances...
          </div>
        </div>
      `;

      const infoWindow = new google.maps.InfoWindow({
        content: loadingContent
      });

      marker.addListener('click', () => {
        // Close other info windows
        Object.values(markersMap).forEach(m => {
          if (m.infoWindow) m.infoWindow.close();
        });
        infoWindow.open(map, marker);
      });

      // Store marker reference
      markersMap[label] = {
        marker: marker,
        infoWindow: infoWindow,
        map: map,
        position: { lat, lng },
        name: name,
        plusCode: plusCode
      };

      // Get distance from HSR Layout (single API call)
      window.incrementApiCounter('distanceMatrix');
      calculateDistance({ lat, lng }, (hsrDistance) => {
        // Calculate distances to other markers
        const otherDistances = [];
        const currentPos = new google.maps.LatLng(lat, lng);

        Object.entries(markersMap).forEach(([otherLabel, otherData]) => {
          if (otherLabel !== label && otherData.position) {
            const otherPos = new google.maps.LatLng(otherData.position.lat, otherData.position.lng);
            const dist = google.maps.geometry.spherical.computeDistanceBetween(currentPos, otherPos);
            otherDistances.push({
              label: otherLabel,
              name: otherData.name,
              distance: dist < 1000 ? `${Math.round(dist)} m` : `${(dist / 1000).toFixed(1)} km`
            });
          }
        });

        // Sort by distance
        otherDistances.sort((a, b) => parseFloat(a.distance) - parseFloat(b.distance));

        // Build Location Details section (Plus Code + Coords)
        let locationDetailsHtml = `
            <div style="background: #f5f5f5; padding: 8px; border-radius: 6px; margin-bottom: 10px;">
        `;

        if (plusCode) {
          locationDetailsHtml += `
              <div style="font-size: 11px; color: #666; margin-bottom: 4px;">Plus Code</div>
              <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <code id="pluscode-${label}" style="font-size: 13px; font-weight: 600; color: #1976d2; flex: 1;">${plusCode}</code>
                <button onclick="navigator.clipboard.writeText('${plusCode}').then(() => { this.innerHTML = '✓'; setTimeout(() => this.innerHTML = '<i class=\\'fas fa-copy\\'></i>', 1500); })" 
                        style="background: #1976d2; color: white; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 12px;" title="Copy Plus Code">
                  <i class="fas fa-copy"></i>
                </button>
              </div>
          `;
        } else {
          locationDetailsHtml += `<div style="font-size: 11px; color: #999; margin-bottom: 8px; font-style: italic;">Plus Code unavailable</div>`;
        }

        locationDetailsHtml += `
              <div style="font-size: 11px; color: #555; padding-top: 4px; border-top: 1px dashed #ddd;">
                <span style="color: #888;">Coords:</span> 
                <span style="font-family: monospace; font-weight: 600;">${coordDisplay}</span>
              </div>
            </div>
        `;

        // Build HSR distance section
        let hsrHtml = '';
        if (hsrDistance) {
          hsrHtml = `
            <div style="font-size: 12px; color: #333; padding: 6px 0; border-top: 1px solid #eee;">
              <i class="fas fa-home" style="color: #e53935;"></i> 
              <strong>${hsrDistance.distance}</strong> from HSR Layout
              <span style="color: #888; font-size: 11px;">(${hsrDistance.duration})</span>
            </div>
          `;
        }

        // Build other distances section
        let otherHtml = '';
        if (otherDistances.length > 0) {
          otherHtml = `
            <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #eee;">
              <div style="font-size: 11px; color: #888; margin-bottom: 6px; font-weight: 600;">
                <i class="fas fa-route"></i> Nearby Locations
              </div>
              ${otherDistances.slice(0, 5).map(d => `
                <div style="font-size: 11px; color: #555; padding: 3px 0; display: flex; justify-content: space-between;">
                  <span><span style="background: #e53935; color: white; padding: 1px 5px; border-radius: 8px; font-size: 10px; margin-right: 4px;">${d.label}</span>${d.name.substring(0, 20)}${d.name.length > 20 ? '...' : ''}</span>
                  <strong style="color: #1976d2;">${d.distance}</strong>
                </div>
              `).join('')}
            </div>
          `;
        }

        // Build complete content
        const fullContent = `
          <div style="padding: 12px; min-width: 250px; max-width: 320px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
              <span style="background: linear-gradient(135deg, #e53935, #c62828); color: white; min-width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px;">${label}</span>
              <strong style="font-size: 14px; color: #333;">${name}</strong>
            </div>
            ${locationDetailsHtml}
            <a href="${mapsUrl}" target="_blank" style="display: flex; align-items: center; gap: 6px; background: #4285f4; color: white; text-decoration: none; padding: 8px 12px; border-radius: 6px; font-size: 13px; margin-bottom: 10px;">
              <i class="fas fa-external-link-alt"></i> Open in Google Maps
            </a>
            ${hsrHtml}
            ${otherHtml}
          </div>
        `;

        infoWindow.setContent(fullContent);
      });

      return marker;
    }

    locations.forEach((loc, idx) => {
      const label = loc.label || (idx + 1).toString();
      const location = loc.location;

      // Check if location is lat,lng format
      const coordsMatch = location.match(/^(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)$/);

      if (coordsMatch) {
        // Direct coordinates
        const position = {
          lat: parseFloat(coordsMatch[1]),
          lng: parseFloat(coordsMatch[2])
        };
        createMarkerWithDistance(position, label, loc.name);
        markersPlaced++;
        if (markersPlaced === totalLocations) onComplete();
      } else if (isShortUrl(location)) {
        // Short URL - resolve it via PHP proxy
        console.log(`[Map Debug] Resolving short URL via proxy: ${location}`);

        fetch(`resolve_map_url.php?url=${encodeURIComponent(location)}`)
          .then(res => res.json())
          .then(data => {
            if (data.success && data.coords) {
              console.log(`[Map Debug] ✓ Resolved short URL to: ${data.coords.lat}, ${data.coords.lng}`);
              const position = {
                lat: data.coords.lat,
                lng: data.coords.lng
              };
              createMarkerWithDistance(position, label, loc.name, true);
            } else {
              console.warn(`[Map Debug] Could not resolve short URL: ${location}`, data.error);
              // Create a marker with info window to open the link manually
              const infoContent = `
                <div style="padding: 10px; min-width: 200px;">
                  <p style="margin: 0 0 10px 0;"><strong>Location ${label}</strong></p>
                  <p style="margin: 0 0 10px 0; font-size: 12px; color: #666;">Could not resolve coordinates automatically.</p>
                  <a href="${location}" target="_blank" style="display: inline-block; padding: 8px 16px; background: #1976d2; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">
                    <i class="fas fa-external-link-alt"></i> Open in Google Maps
                  </a>
                </div>
              `;
              // Place marker at center of existing bounds or default
              if (!bounds.isEmpty()) {
                placeMarkerWithInfo(map, bounds.getCenter(), label, bounds, infoContent);
              }
            }
            markersPlaced++;
            if (markersPlaced === totalLocations) onComplete();
          })
          .catch(err => {
            console.error(`[Map Debug] Error resolving short URL: ${location}`, err);
            markersPlaced++;
            if (markersPlaced === totalLocations) onComplete();
          });
      } else {
        // Geocode the address
        window.incrementApiCounter('geocoding');
        geocoder.geocode({ address: location }, (results, status) => {
          if (status === 'OK' && results[0]) {
            const position = results[0].geometry.location;
            createMarkerWithDistance(position, label, loc.name);
          } else {
            console.warn(`[Map Debug] Geocoding failed for: ${location}`, status);
          }
          markersPlaced++;
          if (markersPlaced === totalLocations) onComplete();
        });
      }
    });
  }

  // Place a marker with an info window
  function placeMarkerWithInfo(map, position, label, bounds, infoContent) {
    const marker = new google.maps.Marker({
      position: position,
      map: map,
      label: {
        text: label,
        color: '#fff',
        fontWeight: 'bold'
      },
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        scale: 12,
        fillColor: '#ff9800', // Orange for short URL markers
        fillOpacity: 1,
        strokeColor: '#fff',
        strokeWeight: 2
      }
    });

    const infoWindow = new google.maps.InfoWindow({
      content: infoContent
    });

    marker.addListener('click', () => {
      infoWindow.open(map, marker);
    });

    bounds.extend(position);
  }

  // Place a marker on the map with name info
  function placeMarker(map, position, label, bounds, name) {
    const marker = new google.maps.Marker({
      position: position,
      map: map,
      label: {
        text: label,
        color: '#fff',
        fontWeight: 'bold'
      },
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        scale: 14,
        fillColor: '#e53935',
        fillOpacity: 1,
        strokeColor: '#fff',
        strokeWeight: 2
      },
      title: name || `Location ${label}`
    });

    // Add info window with name
    if (name) {
      const infoContent = `
        <div style="padding: 8px; min-width: 150px;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="background: linear-gradient(135deg, #e53935, #c62828); color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px;">${label}</span>
            <strong style="font-size: 14px; color: #333;">${name}</strong>
          </div>
        </div>
      `;
      const infoWindow = new google.maps.InfoWindow({
        content: infoContent
      });

      marker.addListener('click', () => {
        infoWindow.open(map, marker);
      });
    }

    bounds.extend(position);
  }

  // Fit map to show all markers
  function fitBounds(map, bounds) {
    if (!bounds.isEmpty()) {
      map.fitBounds(bounds);
      // Don't zoom in too much for single marker
      const listener = google.maps.event.addListener(map, 'idle', () => {
        if (map.getZoom() > 15) map.setZoom(15);
        google.maps.event.removeListener(listener);
      });
    }
  }

  // Export functions to global scope
  window.saveInstalls = saveInstalls;
  window.openForm = openForm;
  window.closeForm = closeForm;
  window.editInstall = editInstall;
  window.deleteInstall = deleteInstall;
  window.sendToWhatsapp = sendToWhatsapp;
  window.updatePdfSentIndicator = updatePdfSentIndicator;
  window.syncPdfSentIndicator = syncPdfSentIndicator;

})(window);
