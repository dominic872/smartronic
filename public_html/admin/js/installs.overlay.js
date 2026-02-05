// Extras overlay and form management for installs system
(function(window) {
  'use strict';

  // Form reference getter
  function getExtrasFormRefs() {
    const refs = {
      cableRate: document.getElementById('ex_cable_rate'),
      cableLen: document.getElementById('ex_cable_len'),
      cableTotal: document.getElementById('ex_cable_total'),
      rackSize: document.getElementById('ex_rack_size'),
      rackRate: document.getElementById('ex_rack_rate'),
      monitorSize: document.getElementById('ex_monitor_size'),
      monitorRate: document.getElementById('ex_monitor_rate'),
      routerSize: document.getElementById('ex_router_size'),
      routerRate: document.getElementById('ex_router_rate'),
      customLabel: document.getElementById('ex_custom_label'),
      customItem: document.getElementById('ex_custom_item'),
      customTotal: document.getElementById('ex_custom_total'),
      saveBtn: document.getElementById('ex-save-btn'),
      tableBody: (document.getElementById('extras-table') || {}).tBodies ? 
        document.getElementById('extras-table').tBodies[0] : null,
      totalCell: document.getElementById('extras-total-cell')
    };
    
    console.log('🎯 getExtrasFormRefs found elements:', {
      cableRate: !!refs.cableRate,
      cableLen: !!refs.cableLen,
      cableTotal: !!refs.cableTotal,
      rackSize: !!refs.rackSize,
      rackRate: !!refs.rackRate,
      monitorSize: !!refs.monitorSize,
      monitorRate: !!refs.monitorRate,
      routerSize: !!refs.routerSize,
      routerRate: !!refs.routerRate,
      customLabel: !!refs.customLabel,
      customItem: !!refs.customItem,
      customTotal: !!refs.customTotal,
      saveBtn: !!refs.saveBtn,
      tableBody: !!refs.tableBody,
      totalCell: !!refs.totalCell
    });
    
    return refs;
  }

  // Setup the extras overlay
  function setupExtrasOverlay() {
    const card = document.querySelector('.extras-card');
    if (!card || card.dataset.overlayReady === '1') return;
    
    card.dataset.overlayReady = '1';
    const container = document.getElementById('install-popup') || 
                     document.getElementById('order-popup') || 
                     document.body;

    // Create overlay shell and backdrop
    const overlay = document.createElement('div');
    overlay.id = 'extras-overlay';
    overlay.style.cssText = [
      'position:absolute', 'top:0', 'left:0', 'height:100%', 'width:33.333%',
      'min-width:360px', 'max-width:520px', 'background:#fff',
      'box-shadow:0 10px 30px rgba(0,0,0,0.35)', 'transform:translateX(-100%)',
      'transition:transform .28s ease', 'z-index:2147483645', 'display:none',
      'flex-direction:column'
    ].join(';');

    const backdrop = document.createElement('div');
    backdrop.id = 'extras-backdrop';
    backdrop.style.cssText = [
      'position:absolute', 'inset:0', 'background:rgba(0,0,0,0.35)',
      'z-index:2147483644', 'display:none'
    ].join(';');

    // Style the card to fit overlay
    card.style.margin = '0';
    card.style.border = '0';
    card.style.borderRadius = '0';
    card.style.height = '100%';
    card.style.overflow = 'auto';
    card.style.boxShadow = 'none';
    card.style.padding = '20px';

    // Create and add payment details section to overlay
    const paymentSection = document.createElement('div');
    paymentSection.className = 'payment-details-overlay';
    paymentSection.innerHTML = `
      <h4>Payment Details</h4>
      <form id="overlay-payment-form">
        <div style="margin-bottom: 12px;">
          <label style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
            <input type="checkbox" id="overlay-fully-paid" style="margin: 0;"> Mark as fully paid
          </label>
        </div>
        <div style="margin-bottom: 12px;">
          <label style="display: block; margin-bottom: 4px; font-size: 14px; font-weight: 500;">Amount Paid (₹):</label>
          <input type="number" id="overlay-amount-paid" placeholder="Enter amount" step="0.01" min="0" style="width: 100%; padding: 6px 8px; border: 1px solid #ced4da; border-radius: 4px; font-size: 12px;">
        </div>
        <button type="submit" style="background: #007cba; color: white; padding: 8px; border: none; border-radius: 4px; cursor: pointer; width: 40px; height: 32px; display: flex; align-items: center; justify-content: center;">
          <i class="fas fa-save"></i>
        </button>
      </form>
    `;

    // Create and add profit details section to overlay
    const profitSection = document.createElement('div');
    profitSection.className = 'profit-details-overlay';
    profitSection.innerHTML = `
      <h4>Profit Details</h4>
      <div id="profit-content" style="font-size: 14px; line-height: 1.5;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
          <span>Final Profit:</span>
          <span id="final-profit-value" style="font-weight: 600;">₹0</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
          <span>Limited Profit:</span>
          <span id="limited-profit-value" style="font-weight: 600;">₹0</span>
        </div>
      </div>
    `;

    // Insert sections at the beginning of the card
    card.insertBefore(profitSection, card.firstChild);
    card.insertBefore(paymentSection, card.firstChild);

    // Mount overlay
    overlay.appendChild(card);
    container.appendChild(backdrop);
    container.appendChild(overlay);

    // Global open/close helpers
    window.openExtrasOverlay = function() {
      overlay.style.display = 'flex';
      overlay.style.transform = 'translateX(0)';
      backdrop.style.display = '';
      const btn = document.getElementById('extras-open-btn');
      if (btn) {
        btn.innerHTML = '<i class="fas fa-times"></i>';
        btn.style.background = '#dc3545';
        btn.style.zIndex = '2147483646'; // Higher than overlay
      }
    };

    window.closeExtrasOverlay = function() {
      overlay.style.transform = 'translateX(-100%)';
      backdrop.style.display = 'none';
      // Hide overlay after animation
      setTimeout(() => {
        overlay.style.display = 'none';
      }, 280);
      const btn = document.getElementById('extras-open-btn');
      if (btn) {
        btn.innerHTML = '<i class="fas fa-plus"></i>';
        btn.style.background = '#28a745';
        btn.style.zIndex = '2147483643';
      }
    };

    window.toggleExtrasOverlay = function() {
      const isOpen = overlay.style.transform === 'translateX(0px)' && 
                     overlay.style.display === 'flex';
      if (isOpen) {
        window.closeExtrasOverlay();
      } else {
        window.openExtrasOverlay();
      }
    };

    // Backdrop and ESC to close
    backdrop.addEventListener('click', () => window.closeExtrasOverlay());
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') window.closeExtrasOverlay();
    });

    // Add a persistent open button
    if (!document.getElementById('extras-open-btn')) {
      const openBtn = document.createElement('button');
      openBtn.id = 'extras-open-btn';
      openBtn.type = 'button';
      openBtn.innerHTML = '<i class="fas fa-plus"></i>';
      openBtn.style.cssText = [
        'position:absolute', 'right:18px', 'bottom:18px', 'width:50px', 'height:50px',
        'border-radius:50%', 'border:none', 'background:#28a745', 'color:#fff',
        'cursor:pointer', 'z-index:2147483643', 'box-shadow:0 4px 12px rgba(0,0,0,0.2)',
        'display:flex', 'align-items:center', 'justify-content:center',
        'font-size:18px', 'transition:all 0.3s ease'
      ].join(';');
      openBtn.addEventListener('click', () => window.toggleExtrasOverlay());
      container.appendChild(openBtn);
    }
  }

  // Initialize extras module
  function initExtrasModule() {
    const refs = getExtrasFormRefs();
    if (!refs || !refs.saveBtn) return; // not ready yet

    setupExtrasOverlay();

    // Auto compute cable total
    const recompute = () => {
      const r = parseFloat(refs.cableRate?.value || '0');
      const l = parseFloat(refs.cableLen?.value || '0');
      if (refs.cableTotal && !isNaN(r) && !isNaN(l)) {
        refs.cableTotal.value = (r * l).toFixed(2);
      }
    };
    refs.cableRate?.addEventListener('input', recompute);
    refs.cableLen?.addEventListener('input', recompute);

    // Save button handler
    refs.saveBtn.onclick = async () => {
      const payload = collectExtrasPayload();
      const orderId = (document.getElementById('id') || {}).value || '';
      if (!orderId) {
        alert('Order ID is required to save extras');
        return;
      }
      
      try {
        const res = await fetch('installs.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ 
            extrasOnly: true, 
            id: orderId, 
            extras: JSON.stringify(payload) 
          })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed');
        
        if (typeof window.renderExtrasTable === 'function') {
          window.renderExtrasTable(payload);
        }
        if (typeof window.applyExtrasToInvoiceTotals === 'function') {
          window.applyExtrasToInvoiceTotals(sumExtras(payload));
        }
        
        window.closeExtrasOverlay();
      } catch (e) {
        console.error('Save extras failed', e);
        alert('Failed to save extras');
      }
    };
  }

  // Collect extras payload
  function collectExtrasPayload() {
    const r = getExtrasFormRefs();
    const num = v => { const n = parseFloat(v); return isNaN(n) ? 0 : n; };
    
    const payload = {
      cable: {
        rate: num(r.cableRate?.value),
        length: num(r.cableLen?.value),
        total: num(r.cableTotal?.value)
      },
      rack: { 
        size: (r.rackSize?.value || '').trim(), 
        rate: num(r.rackRate?.value) 
      },
      monitor: { 
        size: (r.monitorSize?.value || '').trim(), 
        rate: num(r.monitorRate?.value) 
      },
      router: { 
        size: (r.routerSize?.value || '').trim(), 
        rate: num(r.routerRate?.value) 
      },
      custom: { 
        label: (r.customLabel?.value || '').trim(), 
        item: (r.customItem?.value || '').trim(), 
        total: num(r.customTotal?.value) 
      }
    };
    
    // Ensure cable total is consistent if empty
    if (!payload.cable.total) {
      payload.cable.total = +(payload.cable.rate * payload.cable.length).toFixed(2);
    }
    
    return payload;
  }

  // Calculate sum of extras
  function sumExtras(payload) {
    if (!payload) return 0;
    let total = 0;
    if (payload.cable) total += payload.cable.total || 0;
    if (payload.rack) total += payload.rack.rate || 0;
    if (payload.monitor) total += payload.monitor.rate || 0;
    if (payload.router) total += payload.router.rate || 0;
    if (payload.custom) total += payload.custom.total || 0;
    return total;
  }

  // Load extras from database
  async function loadExtrasFromDB() {
    const orderId = (document.getElementById('id') || {}).value || '';
    console.log('📊 loadExtrasFromDB called with orderId:', orderId);
    if (!orderId) {
      console.warn('⚠️ No order ID found');
      return;
    }
    
    try {
      const res = await fetch(`installs.php?get_extras=1&id=${encodeURIComponent(orderId)}`, { 
        cache: 'no-store' 
      });
      console.log('🌐 Fetch response status:', res.status);
      const data = await res.json();
      console.log('📦 Raw server response:', data);
      let payload = null;
      
      if (data && data.success && typeof data.extras === 'string' && data.extras.trim()) {
        try {
          payload = JSON.parse(data.extras);
          console.log('✅ Parsed extras payload:', payload);
        } catch (parseErr) {
          console.error('❌ JSON parse error:', parseErr);
          payload = null;
        }
      } else {
        console.log('🚫 No valid extras data found');
      }
      
      if (payload) {
        console.log('📝 Filling extras form with payload:', payload);
        fillExtrasForm(payload);
        if (typeof window.renderExtrasTable === 'function') {
          console.log('📋 Rendering extras table');
          window.renderExtrasTable(payload);
        } else {
          console.warn('⚠️ renderExtrasTable function not available');
        }
        if (typeof window.applyExtrasToInvoiceTotals === 'function') {
          const total = sumExtras(payload);
          console.log('💰 Applying extras to invoice totals:', total);
          window.applyExtrasToInvoiceTotals(total);
        } else {
          console.warn('⚠️ applyExtrasToInvoiceTotals function not available');
        }
      } else {
        console.log('🧹 Clearing extras (no payload)');
        // Clear extras
        if (typeof window.renderExtrasTable === 'function') {
          window.renderExtrasTable({});
        }
        if (typeof window.applyExtrasToInvoiceTotals === 'function') {
          window.applyExtrasToInvoiceTotals(0);
        }
      }
    } catch (e) {
      console.error('❌ Load extras failed:', e);
    }
  }

  // Fill extras form with data
  function fillExtrasForm(p) {
    console.log('📝 fillExtrasForm called with payload:', p);
    const r = getExtrasFormRefs();
    console.log('🎯 Form refs found:', r);
    if (!r) {
      console.error('❌ No form refs available');
      return;
    }
    
    if (p.cable) {
      console.log('🔌 Setting cable values:', p.cable);
      if (r.cableRate) r.cableRate.value = p.cable.rate ?? '';
      if (r.cableLen) r.cableLen.value = p.cable.length ?? '';
      if (r.cableTotal) r.cableTotal.value = p.cable.total ?? '';
    }
    if (p.rack) {
      console.log('🗜️ Setting rack values:', p.rack);
      if (r.rackSize) r.rackSize.value = p.rack.size ?? '';
      if (r.rackRate) r.rackRate.value = p.rack.rate ?? '';
    }
    if (p.monitor) {
      console.log('🖥️ Setting monitor values:', p.monitor);
      if (r.monitorSize) r.monitorSize.value = p.monitor.size ?? '';
      if (r.monitorRate) r.monitorRate.value = p.monitor.rate ?? '';
    }
    if (p.router) {
      console.log('🌐 Setting router values:', p.router);
      if (r.routerSize) r.routerSize.value = p.router.size ?? '';
      if (r.routerRate) r.routerRate.value = p.router.rate ?? '';
    }
    if (p.custom) {
      console.log('⚙️ Setting custom values:', p.custom);
      if (r.customLabel) r.customLabel.value = p.custom.label ?? '';
      if (r.customItem) r.customItem.value = p.custom.item ?? '';
      if (r.customTotal) r.customTotal.value = p.custom.total ?? '';
    }
    console.log('✅ fillExtrasForm completed');
  }

  // Export functions to global scope
  window.getExtrasFormRefs = getExtrasFormRefs;
  window.setupExtrasOverlay = setupExtrasOverlay;
  window.initExtrasModule = initExtrasModule;
  window.collectExtrasPayload = collectExtrasPayload;
  window.sumExtras = sumExtras;
  window.loadExtrasFromDB = loadExtrasFromDB;
  window.fillExtrasForm = fillExtrasForm;

})(window);