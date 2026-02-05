// Main installs functionality - core form handling and data management
(function (window) {
  'use strict';

  // Global state
  let editingId = null;

  // Core DOM elements
  const popup = document.getElementById('order-popup');
  const form = document.getElementById('installForm');

  // Save installs data
  function saveInstalls(data) {
    if (!data) {
      console.error('No data provided to saveInstalls');
      return;
    }

    console.log('Attempting to save data:', data);

    const requestHeaders = {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    };

    console.log('Request headers:', requestHeaders);
    console.log('Request body:', JSON.stringify(data));

    fetch('installs.php', {
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
            alert('Install saved successfully!');
            if (typeof window.render === 'function') {
              window.render();
            }
            if (typeof window.closeOrderPopup === 'function') {
              window.closeOrderPopup();
            }
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
      'resolution', 'map', 'rack', 'notes'
    ];

    // Small delay to ensure the form is visible before populating
    setTimeout(() => {
      Object.entries(data).forEach(([key, value]) => {
        // Only try to populate fields that exist in our form
        if (validFormFields.includes(key)) {
          const field = form.querySelector(`#${key}`);
          if (field) {
            field.value = value;
            console.log(`Set field ${key} to:`, value); // Debug log
          } else {
            console.warn(`Expected field ${key} not found in form`);
          }
        }
        // Skip fields that don't belong to this form (no warning needed)
      });
    }, 50);

    editingId = data.id || null;
    window.editingId = editingId; // For backward compatibility

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
  }

  // Close form
  function closeForm() {
    if (popup) {
      popup.style.display = 'none';
      document.body.classList.remove('popup-open');
    }
    editingId = null;
    window.editingId = null;
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
    fetch(`installs.php?id=${encodeURIComponent(id)}`)
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
            map: data.Map || data.map || '',          // Handle case difference
            rack: data.rack || '',
            notes: data.notes || data.note || ''      // Handle note vs notes
          };

          console.log('Original API data:', data);
          console.log('Mapped form data:', formData);

          // Store the data globally for reference
          window.currentInstallData = formData;
          openForm(formData);

          // Load payment data when switching to invoice tab
          setTimeout(() => {
            if (typeof window.loadPaymentData === 'function') {
              window.loadPaymentData(id);
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
    if (!confirm('Are you sure you want to delete this install?')) return;

    fetch('installs.php', {
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

      const encodedMessage = encodeURIComponent(message.trim());
      const whatsappUrl = `https://wa.me/${phone}?text=${encodedMessage}`;
      window.open(whatsappUrl, '_blank');
    } catch (error) {
      console.error('Error sending to WhatsApp:', error);
      alert('Failed to send WhatsApp message');
    }
  }

  // Toggles map preview for all events in the day
  window.toggleDayMaps = function (el) {
    console.log('toggleDayMaps called', el);
    const day = el.closest('.day');
    if (!day) {
      console.error('Child day element not found');
      return;
    }

    // If maps are already shown, remove them (toggle functionality)
    const existingMaps = day.querySelectorAll('.map-preview-img');
    if (existingMaps.length > 0) {
      existingMaps.forEach(img => img.remove());
      return;
    }

    const events = day.querySelectorAll('.event');
    events.forEach(ev => {
      const areaSpan = ev.querySelector('.area');
      if (!areaSpan) return;

      const link = areaSpan.querySelector('a');
      // If no link, we can't get href, but maybe we can use location text if available?
      let location = '';
      if (link) {
        location = link.innerText.trim(); // Default fallback
        const href = link.href;
        if (href) {
          // Try to extract query or coordinates
          const qMatch = href.match(/[?&]q=([^&]+)/);
          const llMatch = href.match(/[?&]ll=([^&]+)/);
          const atMatch = href.match(/@([\d.-]+,[\d.-]+)/);

          if (qMatch) location = decodeURIComponent(qMatch[1]);
          else if (llMatch) location = decodeURIComponent(llMatch[1]);
          else if (atMatch) location = atMatch[1];
        }
      } else {
        return;
      }

      if (!location) return;

      const img = document.createElement('img');
      img.className = 'map-preview-img';
      // Style to ensure it looks good and fits
      img.style.display = 'block';
      img.style.width = '100%';
      img.style.height = '150px';
      img.style.objectFit = 'cover';
      img.style.borderRadius = '6px';
      img.style.marginTop = '8px';
      img.style.marginBottom = '8px';
      img.style.border = '1px solid #ddd';

      const key = 'AIzaSyB7BKkBQEI0WpbFFjn8K4VWKRaYeIs3GhU';

      img.src = `https://maps.googleapis.com/maps/api/staticmap?center=${encodeURIComponent(location)}&zoom=14&size=400x300&maptype=roadmap&markers=color:red%7C${encodeURIComponent(location)}&key=${key}`;

      // Insert just above .area
      console.log('Inserting map for event', ev.dataset.id);
      areaSpan.parentNode.insertBefore(img, areaSpan);
    });
  };

  // Export functions to global scope
  window.saveInstalls = saveInstalls;
  window.openForm = openForm;
  window.closeForm = closeForm;
  window.editInstall = editInstall;
  window.deleteInstall = deleteInstall;
  window.sendToWhatsapp = sendToWhatsapp;

})(window);