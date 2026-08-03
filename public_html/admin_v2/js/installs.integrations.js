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

  async function getQuoteDetails(installDetails){
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
      : brandLower === 'secureye'
      ? 'https://smartronic.online/content/uploads/2025/01/Secureye_logo.png'
      : '';

    try {
      if (typeof window.calculateInstallPricing !== 'function') {
        throw new Error('Pricing calculator is not loaded.');
      }
      const data = await window.calculateInstallPricing(installDetails);
      console.log('📦 data.json pricing response:', data);

      let el = document.getElementById('results');
      if (!el) {
        el = document.getElementById('result');
      }

      if (el) {
        const inr = n => Number.isFinite(Number(n)) ? `₹${Math.round(Number(n)).toLocaleString('en-IN')}` : 'n/a';
        const brandLine = brand
          ? `<div style="display:flex;align-items:center;gap:8px;margin:0 0 10px 0;padding:8px;background:#fff;border:1px solid #e5e7eb;border-radius:6px;">
              ${brandLogoPath ? `<img src="${brandLogoPath}" alt="${brand}" style="height:18px;width:auto;object-fit:contain;">` : ''}
              <strong>${brand}</strong>${camType ? `<span style="color:#666;">${camType}</span>` : ''}
            </div>`
          : '';
        const missingHtml = data.missing && data.missing.length
          ? `<div style="color:#b91c1c;margin-top:8px;">Missing price: ${data.missing.join(', ')}</div>`
          : '';
        const quoteHtml = `
          <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 14px; line-height: 1.4;">
            <h4 style="margin: 0 0 10px 0; color: #333;">📋 Quote Details</h4>
            ${brandLine}
            <div style="border-bottom: 1px solid #ddd; padding-bottom: 8px; margin-bottom: 8px;">
              Source: <strong>data.json</strong><br>
              🎥 Camera: ${data.resolution} ${data.camType || ''} x ${data.cams} @ ${inr(data.cameraUnitPrice)} = <strong>${inr(data.cameraTotal)}</strong><br>
              📦 Recorder: ${data.channel}CH = <strong>${inr(data.recorderPrice)}</strong><br>
              💽 HDD: <strong>${inr(data.hddPrice)}</strong><br>
              🧰 Accessories: <strong>${inr(data.accessories)}</strong>
            </div>
            <div style="border-bottom: 1px solid #ddd; padding-bottom: 8px; margin-bottom: 8px;">
              💰 Subtotal: ${inr(data.subtotal)}<br>
              🧾 GST (18%): ${inr(data.gst)}<br>
              💵 <strong>Material Cost: ${inr(data.materialCost)}</strong><br>
              🧩 Installation: ${inr(data.installCharge)}
            </div>
            <div style="background: #e6f3ff; padding: 8px; border-radius: 4px;">
              ✅ Limited Total: <strong style="color: #0066cc;">${inr(data.limitedTotal)}</strong><br>
              ✅ Final Total (${data.profitPercentage}%): <strong style="color: #0066cc;">${inr(data.finalTotal)}</strong><br>
              <small style="color: #666;">Actual Profit: ${inr(data.actualProfit)}</small>
              ${missingHtml}
            </div>
          </div>
        `;
        el.innerHTML = quoteHtml;
      }
      if (typeof window.updateProfitDetailsOverlay === 'function') {
        window.updateProfitDetailsOverlay(installDetails);
      }
    } catch (err) {
      console.error('Error calculating quote details from data.json:', err);
      let el = document.getElementById('results');
      if (!el) {
        el = document.getElementById('result');
      }
      if (el) el.innerHTML = '<div style="color: red; padding: 10px;">❌ Error calculating quote details from data.json</div>';
    }
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
            id: params.get('id') || '', name: params.get('name') || '', cams: params.get('cams') || '', bullets: '', dome: '', hdd: (params.get('hdd') || '').replace(/\s+/g, ''), monitor: '', type: params.get('type') || '', city: params.get('city') || 'Bangalore', location: params.get('location') || '', time: '', date: params.get('date') || '', owner: params.get('owner') || params.get('Assign') || params.get('assign') || '', technician: '', helper: '', resolution: params.get('resolution') || '', map: params.get('map') || ''
          };
          if (typeof window.openForm === 'function') window.openForm(formData);
        }
      });
  });
})();
