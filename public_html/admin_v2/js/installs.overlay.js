// Extras overlay and form management for installs system
(function (window) {
  'use strict';

  // Form reference getter
  function getExtrasFormRefs() {
    return {
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
    card.style.paddingBottom = '30px'; // Extra padding at bottom

    // Create and add payment details section to overlay
    const paymentSection = document.createElement('div');
    paymentSection.className = 'payment-details-overlay';
    paymentSection.innerHTML = `
      <h4 style="margin: 0 0 15px 0; color: #333; font-size: 16px; font-weight: 600;">Payment Details</h4>
      <form id="overlay-payment-form" style="display: flex; flex-direction: column; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <input type="checkbox" id="overlay-fully-paid" style="margin: 0; width: 16px !important; height: 16px;">
          <label for="overlay-fully-paid" style="font-size: 14px; color: #495057; cursor: pointer;">Mark as fully paid</label>
        </div>
        <div>
          <label for="overlay-amount-paid" style="display: block; margin-bottom: 4px; font-size: 14px; font-weight: 500; color: #495057;">Amount Paid (₹):</label>
          <input type="number" id="overlay-amount-paid" placeholder="Enter amount" step="0.01" min="0" style="width: 50% !important; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
        
        <button type="submit" style="background: #007cba; color: white; padding: 6px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500;  align-items: center; justify-content: center; gap: 6px; align-self: flex-start;">
          <i class="fas fa-save"></i> 
        </button>
        </div>
      </form>
    `;

    // Create and add profit details section to overlay
    const profitSection = document.createElement('div');
    profitSection.className = 'profit-details-overlay';
    profitSection.innerHTML = `
      <h4 style="margin: 0 0 15px 0; color: #333; font-size: 16px; font-weight: 600;">Profit wwDetails</h4>
      <div id="results" style="margin-bottom: 15px;">1234</div>
      <div id="profit-content" style="font-size: 14px; line-height: 1.5; border: 1px solid #eee; border-radius: 8px; padding: 12px; background: #f8f9fa;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <span style="color: #495057;">Final Profit:</span>
          <span id="final-profit-value" style="font-weight: 600; color: #28a745;">₹0</span>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <span style="color: #495057;">Limited Profit:</span>
          <span id="limited-profit-value" style="font-weight: 600; color: #007cba;">₹0</span>
        </div>
      </div>
    `;

    // Insert sections at the beginning of the card with proper spacing
    paymentSection.style.marginBottom = '20px';
    profitSection.style.marginBottom = '20px';
    card.insertBefore(profitSection, card.firstChild);
    card.insertBefore(paymentSection, card.firstChild);

    // Mount overlay
    overlay.appendChild(card);
    //overlay.prepend(card);
    container.appendChild(backdrop);
    container.prepend(overlay);


    // Global open/close helpers
    window.openExtrasOverlay = function () {
      overlay.style.display = 'flex';
      overlay.style.transform = 'translateX(0)';
      backdrop.style.display = '';
      const btn = document.getElementById('extras-open-btn');
      if (btn) {
        btn.innerHTML = '<i class="fas fa-times"></i>';
        btn.style.background = '#dc3545';
        btn.style.zIndex = '2147483646'; // Higher than overlay
      }

      // Check user role and restrict payment controls to admin only
      const authRole = getCookie('auth_role');
      const fullyPaidCheckbox = document.getElementById('overlay-fully-paid');
      const fullyPaidLabel = document.querySelector('label[for="overlay-fully-paid"]');
      const amountPaidInput = document.getElementById('overlay-amount-paid');
      const amountPaidLabel = document.querySelector('label[for="overlay-amount-paid"]');
      const paymentForm = document.getElementById('overlay-payment-form');
      const submitButton = paymentForm ? paymentForm.querySelector('button[type="submit"]') : null;

      if (authRole !== 'admin') {
        // Disable fully-paid checkbox
        if (fullyPaidCheckbox) {
          fullyPaidCheckbox.disabled = true;
          fullyPaidCheckbox.style.opacity = '0.5';
          fullyPaidCheckbox.style.cursor = 'not-allowed';
          if (fullyPaidLabel) {
            fullyPaidLabel.style.opacity = '0.5';
            fullyPaidLabel.style.cursor = 'not-allowed';
            fullyPaidLabel.title = 'Only admin users can modify payment details';
          }
        }

        // Disable amount paid input
        if (amountPaidInput) {
          amountPaidInput.disabled = true;
          amountPaidInput.style.opacity = '0.5';
          amountPaidInput.style.cursor = 'not-allowed';
          amountPaidInput.placeholder = 'Admin access required';
          if (amountPaidLabel) {
            amountPaidLabel.style.opacity = '0.5';
          }
        }

        // Disable submit button
        if (submitButton) {
          submitButton.disabled = true;
          submitButton.style.opacity = '0.5';
          submitButton.style.cursor = 'not-allowed';
          submitButton.title = 'Only admin users can save payment details';
        }
      } else {
        // Enable all controls for admin
        if (fullyPaidCheckbox) {
          fullyPaidCheckbox.disabled = false;
          fullyPaidCheckbox.style.opacity = '1';
          fullyPaidCheckbox.style.cursor = 'pointer';
          if (fullyPaidLabel) {
            fullyPaidLabel.style.opacity = '1';
            fullyPaidLabel.style.cursor = 'pointer';
            fullyPaidLabel.title = '';
          }
        }

        if (amountPaidInput) {
          amountPaidInput.disabled = false;
          amountPaidInput.style.opacity = '1';
          amountPaidInput.style.cursor = 'text';
          amountPaidInput.placeholder = 'Enter amount';
          if (amountPaidLabel) {
            amountPaidLabel.style.opacity = '1';
          }
        }

        if (submitButton) {
          submitButton.disabled = false;
          submitButton.style.opacity = '1';
          submitButton.style.cursor = 'pointer';
          submitButton.title = '';
        }
      }

      // Load payment data to populate overlay fields from DB
      try {
        const idEl = document.getElementById('id');
        const orderId = (idEl && idEl.value) ? idEl.value : null;
        if (orderId) {
          if (typeof window.loadPaymentData === 'function') {
            window.loadPaymentData(orderId);
          } else {
            // Fallback: fetch directly if payments module not ready yet
            fetch(`installs.php?get_payment=1&id=${encodeURIComponent(orderId)}`)
              .then(r => r.json())
              .then(d => {
                if (d && d.success) {
                  const overlayFullyPaid = document.getElementById('overlay-fully-paid');
                  const overlayAmountPaid = document.getElementById('overlay-amount-paid');
                  if (overlayFullyPaid) overlayFullyPaid.checked = !!d.fully_paid;
                  if (overlayAmountPaid) overlayAmountPaid.value = (d.amount_paid ?? '');
                }
              })
              .catch(err2 => console.warn('Fallback payment load failed:', err2));
          }
        }
      } catch (err) {
        console.warn('Unable to load payment data on overlay open:', err);
      }
    };

    // Helper function to get cookie value
    function getCookie(name) {
      const value = `; ${document.cookie}`;
      const parts = value.split(`; ${name}=`);
      if (parts.length === 2) return parts.pop().split(';').shift();
      return null;
    }

    window.closeExtrasOverlay = function () {
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

    window.toggleExtrasOverlay = function () {
      const isOpen = overlay.style.transform === 'translateX(0px)' &&
        overlay.style.display === 'flex';
      if (isOpen) {
        window.closeExtrasOverlay();
      } else {
        window.openExtrasOverlay();
        generateQuoteFromForm()
      }
    };

    // Backdrop and ESC to close
    backdrop.addEventListener('click', () => window.closeExtrasOverlay());
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') window.closeExtrasOverlay();
    });

    // Add persistent buttons (only for invoice tab)
    if (!document.getElementById('extras-open-btn')) {
      const openBtn = document.createElement('button');
      openBtn.id = 'extras-open-btn';
      openBtn.type = 'button';
      openBtn.innerHTML = '<i class="fas fa-plus"></i>';
      openBtn.style.cssText = [
        'position:absolute', 'right:18px', 'bottom:18px', 'width:50px', 'height:50px',
        'border-radius:50%', 'border:none', 'background:#28a745', 'color:#fff',
        'cursor:pointer', 'z-index:2147483643', 'box-shadow:0 4px 12px rgba(0,0,0,0.2)',
        'display:none', 'align-items:center', 'justify-content:center',
        'font-size:18px', 'transition:all 0.3s ease'
      ].join(';');
      openBtn.addEventListener('click', () => window.toggleExtrasOverlay());
      container.appendChild(openBtn);
    }

    if (!document.getElementById('download-pdf-fab')) {
      const pdfBtn = document.createElement('button');
      pdfBtn.id = 'download-pdf-fab';
      pdfBtn.type = 'button';
      pdfBtn.innerHTML = '<i class="fas fa-download"></i>';
      pdfBtn.style.cssText = [
        'position:absolute', 'right:18px', 'bottom:78px', 'width:50px', 'height:50px',
        'border-radius:50%', 'border:none', 'background:#28a745', 'color:#fff',
        'cursor:pointer', 'z-index:2147483642', 'box-shadow:0 4px 12px rgba(0,0,0,0.2)',
        'display:none', 'align-items:center', 'justify-content:center',
        'font-size:18px', 'transition:all 0.3s ease'
      ].join(';');
      pdfBtn.addEventListener('click', () => { if (typeof window.downloadPDF === 'function') window.downloadPDF(); });
      container.appendChild(pdfBtn);
    }

    // Show/hide button based on active tab
    window.updateExtrasButtonVisibility = function () {
      const btn = document.getElementById('extras-open-btn');
      const pdfBtn = document.getElementById('download-pdf-fab');
      const invoiceTab = document.getElementById('tab-invoice');
      if (btn && invoiceTab) {
        // Check if invoice tab is currently displayed
        const isInvoiceTabActive = invoiceTab.style.display !== 'none';
        btn.style.display = isInvoiceTabActive ? 'flex' : 'none';
        if (pdfBtn) pdfBtn.style.display = isInvoiceTabActive ? 'flex' : 'none';
      }
    };

    // Initial visibility update
    setTimeout(() => {
      if (typeof window.updateExtrasButtonVisibility === 'function') {
        window.updateExtrasButtonVisibility();
      }
    }, 100);
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

        // Show success message instead of closing popup
        const saveBtn = refs.saveBtn;
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-check"></i> Saved!';
        saveBtn.style.background = '#28a745';

        setTimeout(() => {
          saveBtn.innerHTML = originalText;
          saveBtn.style.background = '#28a745';
        }, 2000);
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

  // Add missing renderExtrasTable function
  function renderExtrasTable(p) {
    console.log('📋 renderExtrasTable called with payload:', p);
    const r = getExtrasFormRefs();
    console.log('🎯 renderExtrasTable form refs:', r);
    if (!r.tableBody) {
      console.error('❌ No table body found in renderExtrasTable');
      return;
    }
    const rows = [];
    const fmt = n => Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });

    if (p.cable && (p.cable.total || 0) > 0) {
      console.log('🔌 Adding cable row:', p.cable);
      rows.push({ item: 'Cable 3+1', details: `Rate ${fmt(p.cable.rate)} x Len ${fmt(p.cable.length)}`, amount: p.cable.total });
    }
    if (p.rack && (p.rack.rate || 0) > 0) {
      console.log('🗜️ Adding rack row:', p.rack);
      rows.push({ item: 'Rack', details: `Size ${p.rack.size || ''}`, amount: p.rack.rate });
    }
    if (p.monitor && (p.monitor.rate || 0) > 0) {
      console.log('🖥️ Adding monitor row:', p.monitor);
      rows.push({ item: 'Monitor', details: `Size ${p.monitor.size || ''}`, amount: p.monitor.rate });
    }
    if (p.router && (p.router.rate || 0) > 0) {
      console.log('🌐 Adding router row:', p.router);
      rows.push({ item: 'Router', details: `Size ${p.router.size || ''}`, amount: p.router.rate });
    }
    if (p.custom && (p.custom.total || 0) > 0) {
      console.log('⚙️ Adding custom row:', p.custom);
      const label = (p.custom.label || 'Custom').trim();
      const item = (p.custom.item || '').trim();
      rows.push({ item: label, details: item, amount: p.custom.total });
    }

    console.log('📋 Final rows for table:', rows);

    r.tableBody.innerHTML = rows.map(row => `
      <tr>
        <td style="padding:6px;border:1px solid #eee;">${row.item}</td>
        <td style="padding:6px;border:1px solid #eee;">${row.details}</td>
        <td style="padding:6px;border:1px solid #eee;text-align:right;">${fmt(row.amount)}</td>
      </tr>
    `).join('');

    const total = rows.reduce((s, x) => s + (Number(x.amount) || 0), 0);
    console.log('💰 Extras table total:', total);

    if (r.totalCell) {
      r.totalCell.textContent = fmt(total);
      console.log('✅ Updated total cell with:', fmt(total));
    } else {
      console.warn('⚠️ No total cell found');
    }

    renderExtrasTableInInvoice(rows, total);
    console.log('✅ renderExtrasTable completed');
  }
  window.renderExtrasTable = renderExtrasTable;

  // Add missing renderExtrasTableInInvoice function  
  function renderExtrasTableInInvoice(rows, total) {
    console.log('📄 renderExtrasTableInInvoice called with rows:', rows, 'total:', total);
    const host = document.getElementById('invoice-content');
    console.log('🏠 Invoice content host found:', !!host);
    if (!host) {
      console.warn('⚠️ No invoice-content element found');
      return;
    }
    const existing = host.querySelector('#extras-invoice-section');
    if (existing) {
      console.log('🧹 Removing existing extras section');
      existing.remove();
    }
    const fmt = n => Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
    const wrapper = document.createElement('div');
    wrapper.id = 'extras-invoice-section';
    wrapper.className = 'items';
    wrapper.innerHTML = `
      <h3>Extra Items</h3>
      <table>
        <thead><tr><th style="text-align:left;">Item</th><th style="text-align:left;">Details</th><th style="text-align:right;">Amount</th></tr></thead>
        <tbody>${rows.map(r => `<tr><td>${r.item}</td><td>${r.details}</td><td style="text-align:right;">${fmt(r.amount)}</td></tr>`).join('')}</tbody>
        <tfoot><tr><td colspan="2" style="text-align:right;font-weight:600;">Extras total</td><td style="text-align:right;font-weight:600;">${fmt(total)}</td></tr></tfoot>
      </table>`;

    console.log('🔍 Looking for .total element in invoice...');
    const totalEl = host.querySelector('.total');
    if (totalEl && totalEl.parentNode) {
      console.log('✅ Inserting extras before .total element');
      totalEl.parentNode.insertBefore(wrapper, totalEl);
    } else {
      console.log('🔍 Looking for .items elements in invoice...');
      const itemsEls = host.querySelectorAll('.items');
      const lastItems = itemsEls.length ? itemsEls[itemsEls.length - 1] : null;
      if (lastItems) {
        console.log('✅ Inserting extras after last .items element');
        lastItems.insertAdjacentElement('afterend', wrapper);
      } else {
        console.log('📎 Appending extras to host');
        host.appendChild(wrapper);
      }
    }
    console.log('✅ renderExtrasTableInInvoice completed');
  }
  window.renderExtrasTableInInvoice = renderExtrasTableInInvoice;

  // Add missing applyExtrasToInvoiceTotals function
  function applyExtrasToInvoiceTotals(extrasTotal) {
    console.log('💰 applyExtrasToInvoiceTotals called with total:', extrasTotal);
    const host = document.getElementById('invoice-content');
    if (!host) {
      console.warn('⚠️ No invoice-content element found for totals update');
      return;
    }
    const parseNum = (s) => {
      if (!s) return NaN;
      const m = String(s).replace(/[^0-9.]/g, '');
      return parseFloat(m);
    };
    const fmt = (n) => isNaN(n) ? '' : n.toLocaleString('en-IN', { maximumFractionDigits: 2 });

    const candidates = ['#payable', '#total_amount', '#grand_total', '#total', '#totalAmountPayable', '.total-amount', '.grand-total'];
    candidates.forEach(sel => {
      const el = host.querySelector(sel);
      if (el) {
        if (!el.dataset.baseValue) {
          const parsed = parseNum(el.textContent);
          if (!isNaN(parsed)) el.dataset.baseValue = String(parsed);
        }
        const base = parseFloat(el.dataset.baseValue);
        if (!isNaN(base)) {
          el.textContent = fmt(base + (extrasTotal || 0));
          console.log(`✅ Updated ${sel} total: ${base} + ${extrasTotal} = ${fmt(base + (extrasTotal || 0))}`);
        }
      }
    });

    const paidCandidates = ['#paid', '#total_paid', '#amount_paid', '.total-paid'];
    paidCandidates.forEach(sel => {
      const el = host.querySelector(sel);
      if (el) {
        if (!el.dataset.baseValue) {
          const parsed = parseNum(el.textContent);
          if (!isNaN(parsed)) el.dataset.baseValue = String(parsed);
        }
        const base = parseFloat(el.dataset.baseValue);
        if (!isNaN(base)) {
          el.textContent = fmt(base + (extrasTotal || 0));
          console.log(`✅ Updated ${sel} paid: ${base} + ${extrasTotal} = ${fmt(base + (extrasTotal || 0))}`);
        }
      }
    });
    console.log('✅ applyExtrasToInvoiceTotals completed');
  }
  window.applyExtrasToInvoiceTotals = applyExtrasToInvoiceTotals;

})(window);