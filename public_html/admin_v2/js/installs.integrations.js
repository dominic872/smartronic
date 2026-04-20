// Integration module: WhatsApp, Quote API, Google Places autocomplete
(function(){
  // Helper function to get cookie value
  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
  }
  
  async function sendToWhatsapp(e, ev){
    if (e && e.preventDefault) e.preventDefault();
    const technicianName = ev.technician || '';
    try {
      const res = await fetch('../users.json');
      const users = await res.json();
     
      const user = users.find(u => (u.name||'').toLowerCase() === technicianName.toLowerCase());
      const phone = user ? user.phone : '91888431000';
      const message = `
*Name: ${ev.name || ''}*
*Phone: ${ev.phone || ''}*
*ID*: ${ev.id || ''}\n
----------------------\n
*${ev.type || ''} | Resolution*: ${ev.resolution || ''}*
*Total Cams*: ${ev.total || ''} | B ${ev.bullets || ''} | D ${ev.dome || ''} | *HDD*: ${ev.hdd || ''}
*Monitor*: ${ev.monitor || 'No'} | *Rack*: ${ev.rack || 'No'}\n
----------------------\n
*Location*: ${ev.location || ''}
*Date*: ${ev.date || ''}
*Time*: ${ev.time || ''}
*Map*: ${ev.map || ''}
`;      const encodedMsg = encodeURIComponent(message.trim());
      const waUrl = `https://wa.me/${phone}?text=${encodedMsg}`;
      window.open(waUrl, '_blank');
    } catch (error) {
      console.error('Error loading users.json or matching technician:', error);
      alert('Could not send message. Please check technician name or users.json file.');
    }
  }
  window.sendToWhatsapp = sendToWhatsapp;

  function getQuoteDetails(installDetails){
    // Check if user has admin role
    const authRole = getCookie('auth_role');
    if (authRole !== 'admin') {
      console.log('Quote details restricted to admin users only');
      // Try to find #results first, then fallback to #result
      let el = document.getElementById('results');
      if (!el) {
        el = document.getElementById('result');
      }
      if (el) {
        el.innerHTML = '<div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; border: 1px solid #ffeaa7; font-family: Arial, sans-serif; font-size: 14px;">🔒 Quote details are only available for admin users.</div>';
      }
      return;
    }
    
    const brand = (installDetails.brand || 'PRAMA').trim();
    const camType = (installDetails.cam_type || '').trim();
    const brandLower = brand.toLowerCase();
    const brandLogoPath = brandLower === 'cp plus'
      ? 'https://smartronic.online/content/uploads/2025/01/cp-plus_logo.svg'
      : brandLower === 'hikvision'
      ? 'https://smartronic.online/content/uploads/2025/01/Hikvision_logo.svg'
      : brandLower === 'prama'
      ? 'https://smartronic.online/content/uploads/2025/01/Prama_logo.png'
      : '';

    const requestData = {
      whatsapp_number: '88888888',
      num_cameras: installDetails.cams,
      dvr_type: installDetails.type,
      hdd_size: installDetails.hdd,
      camera_resolution: installDetails.resolution,
      brand,
      cam_type: camType
    };
    fetch('/admin_v2/quote_api.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(requestData)
    })
      .then(response => response.json())
      .then(data => {
        console.log('📦 Quote Response:', data);
        
        // Try to find #results first, then fallback to #result
        let el = document.getElementById('results');
        if (!el) {
          el = document.getElementById('result');
        }
        
        if (el) {
          // Create detailed quote breakdown
          const brandLine = brand
            ? `<div style="display:flex;align-items:center;gap:8px;margin:0 0 10px 0;padding:8px;background:#fff;border:1px solid #e5e7eb;border-radius:6px;">
                ${brandLogoPath ? `<img src="${brandLogoPath}" alt="${brand}" style="height:18px;width:auto;object-fit:contain;">` : ''}
                <strong>${brand}</strong>${camType ? `<span style="color:#666;">${camType}</span>` : ''}
              </div>`
            : '';
          const quoteHtml = `
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 14px; line-height: 1.4;">
              <h4 style="margin: 0 0 10px 0; color: #333;">📋 Quote Details</h4>
              ${brandLine}
              <div style="border-bottom: 1px solid #ddd; padding-bottom: 8px; margin-bottom: 8px;">
                📞 WhatsApp: ${data.whatsapp}<br>
                🎥 Camera: ${data.camera_key} x ${data.num_cams} @ ₹${data.camera_unit_price} = <strong>₹${data.camera_total}</strong><br>
                📦 Recorder: ${data.recorder_key} = <strong>₹${data.recorder_price}</strong><br>
                💽 HDD: ${data.hdd_size} = <strong>₹${data.hdd_price}</strong><br>
                ${data.poe_price > 0 ? `🔌 POE: <strong>₹${data.poe_price}</strong><br>` : ''}
                ${data.smps_price > 0 ? `🔌 SMPS: <strong>₹${data.smps_price}</strong><br>` : ''}
                🧰 Accessories: <strong>₹${data.accessories}</strong>
              </div>
              <div style="border-bottom: 1px solid #ddd; padding-bottom: 8px; margin-bottom: 8px;">
                💰 Subtotal: ₹${data.subtotal}<br>
                🧾 GST (18%): ₹${data.gst}<br>
                💵 <strong>Total Cost(With Tax): ₹${data.with_gst}</strong><br>
                🧩 Installation: ₹${data.install_charge}<br>
                💼 Profit (60%): ₹${data.profit}
              </div>
              <div style="background: #e8f5e8; padding: 8px; border-radius: 4px; margin-bottom: 8px;">
                💵 Total Before Discount: <strong>₹${data.before_discount}</strong><br>
                🎁 Final (20% Off): <strong style="color: #28a745;">₹${data.final_total}</strong>
              </div>
              <div style="background: #e6f3ff; padding: 8px; border-radius: 4px;">
                ✅ Final Limited Profit Total: <strong style="color: #0066cc;">₹${data.final_limited_profit}</strong><br>
                <small style="color: #666;">🧮 Per-Cam Cost: ₹${data.install_per_cam} x ${data.num_cams} = ₹${data.install_cam_cost}</small>
                <small style="color: #666;">Profit:₹${data.final_total-data.subtotal-data.gst}</small>
              </div>
            </div>
          `;
          el.innerHTML = quoteHtml;
        }
      })
      .catch(err => {
        console.error('Error calling quote API:', err);
        let el = document.getElementById('results');
        if (!el) {
          el = document.getElementById('result');
        }
        if (el) el.innerHTML = '<div style="color: red; padding: 10px;">❌ Error fetching quote details</div>';
      });
  }
  window.getQuoteDetails = getQuoteDetails;

  let locationAutocompleteInitialized = false;
  function initLocationAutocomplete(){
    if (locationAutocompleteInitialized) return;
    const input = document.getElementById('location'); if (!input) return;
    const options = {
      types: ['geocode'], componentRestrictions: { country: 'in' }, fields: ['formatted_address'],
      bounds: new google.maps.LatLngBounds(new google.maps.LatLng(12.80, 77.40), new google.maps.LatLng(13.20, 77.80)),
      strictBounds: false
    };
    const autocomplete = new google.maps.places.Autocomplete(input, options);
    autocomplete.addListener('place_changed', () => { const place = autocomplete.getPlace(); if (place && place.formatted_address) input.value = place.formatted_address; });
    locationAutocompleteInitialized = true;
  }
  window.initLocationAutocomplete = initLocationAutocomplete;

  window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');
    if (!id) return;
    fetch('installs_api.php')
      .then(res => res.json())
      .then(data => {
        const exists = data.find(r => r.id === id);
        if (exists) { alert(`Record already exists for date: ${exists.date}`); }
        else {
          const formData = {
            id: params.get('id') || '', name: params.get('name') || '', cams: params.get('cams') || '', bullets: '', dome: '', hdd: (params.get('hdd') || '').replace(/\s+/g, ''), monitor: '', type: params.get('type') || '', location: params.get('location') || '', time: '', date: params.get('date') || '', owner: params.get('owner') || '', technician: '', helper: '', resolution: params.get('resolution') || '', map: params.get('map') || ''
          };
          if (typeof window.openForm === 'function') window.openForm(formData);
        }
      });
  });
})();
