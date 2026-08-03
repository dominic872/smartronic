// Extras overlay and form management for installs system
(function (window) {
  'use strict';

  let currentCustomItems = [];
  const PENDING_AMOUNT_LABEL = 'Amount stands as unpaid';
  const PENDING_WARRANTY_NOTE = 'NOTICE:  No warranty, service warranty, or support from Smartronic shall apply unless the outstanding amount is paid in full within 48 hours. Failure to make payment within the stipulated period shall automatically void all warranty and support entitlements.';

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    }[char]));
  }

  function isAdminUser() {
    return String(window.INSTALLS_CURRENT_ROLE || '').toLowerCase() === 'admin';
  }

  function getEmptyExtrasPayload() {
    return {
      cable: { rate: 0, length: 0, total: 0 },
      rack: { size: '', rate: 0 },
      monitor: { size: '', rate: 0 },
      router: { size: '', rate: 0 },
      customItems: [],
      pendingWarranty: { label: PENDING_AMOUNT_LABEL, amount: 0, note: PENDING_WARRANTY_NOTE }
    };
  }

  function normalizeCustomItems(payload) {
    const items = Array.isArray(payload?.customItems) ? payload.customItems : [];
    const normalized = items
      .map(item => ({
        label: String(item?.label || '').trim(),
        item: String(item?.item || '').trim(),
        total: Number.parseFloat(item?.total) || 0
      }))
      .filter(item => item.label || item.item || item.total > 0);

    if (!normalized.length && payload?.custom) {
      const legacyItem = {
        label: String(payload.custom.label || '').trim(),
        item: String(payload.custom.item || '').trim(),
        total: Number.parseFloat(payload.custom.total) || 0
      };
      if (legacyItem.label || legacyItem.item || legacyItem.total > 0) {
        normalized.push(legacyItem);
      }
    }

    return normalized;
  }

  function normalizeExtrasPayload(payload) {
    const base = getEmptyExtrasPayload();
    const normalized = {
      cable: {
        rate: Number.parseFloat(payload?.cable?.rate) || 0,
        length: Number.parseFloat(payload?.cable?.length) || 0,
        total: Number.parseFloat(payload?.cable?.total) || 0
      },
      rack: {
        size: String(payload?.rack?.size || '').trim(),
        rate: Number.parseFloat(payload?.rack?.rate) || 0
      },
      monitor: {
        size: String(payload?.monitor?.size || '').trim(),
        rate: Number.parseFloat(payload?.monitor?.rate) || 0
      },
      router: {
        size: String(payload?.router?.size || '').trim(),
        rate: Number.parseFloat(payload?.router?.rate) || 0
      },
      customItems: normalizeCustomItems(payload),
      pendingWarranty: {
        label: PENDING_AMOUNT_LABEL,
        amount: Number.parseFloat(payload?.pendingWarranty?.amount) || 0,
        note: PENDING_WARRANTY_NOTE
      }
    };

    if (!normalized.cable.total) {
      normalized.cable.total = +(normalized.cable.rate * normalized.cable.length).toFixed(2);
    }

    return { ...base, ...normalized };
  }

  function formatINR(value) {
    const amount = Number(value);
    if (!Number.isFinite(amount)) return 'n/a';
    return `₹${Math.round(amount).toLocaleString('en-IN')}`;
  }

  function getInstallFormSnapshot() {
    const getVal = id => {
      const el = document.getElementById(id);
      return el ? (el.value || '') : '';
    };
    return {
      id: getVal('id'),
      name: getVal('name'),
      cams: getVal('cams'),
      bullets: getVal('bullets'),
      dome: getVal('dome'),
      hdd: getVal('hdd'),
      type: getVal('type'),
      resolution: getVal('resolution'),
      brand: getVal('brand'),
      cam_type: getVal('cam_type'),
      rack: getVal('rack'),
      monitor: getVal('monitor')
    };
  }

  async function updateProfitDetailsOverlay(installData) {
    const content = document.getElementById('profit-content');
    const results = document.getElementById('results');
    const finalProfitValue = document.getElementById('final-profit-value');
    const limitedProfitValue = document.getElementById('limited-profit-value');
    if (!content) return;

    if (results) {
      results.innerHTML = '<div style="color:#6b7280;font-size:13px;">Calculating from data.json...</div>';
    }

    try {
      const input = installData || window.currentInstallData || getInstallFormSnapshot();
      if (typeof window.calculateInstallPricing !== 'function') {
        throw new Error('Pricing calculator is not loaded.');
      }
      const pricing = await window.calculateInstallPricing(input);
      const actualProfit = Number.isFinite(pricing.actualProfit) ? pricing.actualProfit : NaN;
      const limitedProfit = pricing.limitedTotal - pricing.materialCost;

      content.dataset.materialCost = String(pricing.materialCost || 0);
      content.dataset.customerPayable = Number.isFinite(pricing.customerPayable) ? String(pricing.customerPayable) : '';
      content.dataset.camType = pricing.camType || '';
      content.dataset.priceSource = pricing.source || 'data.json';

      if (finalProfitValue) finalProfitValue.textContent = formatINR(actualProfit);
      if (limitedProfitValue) limitedProfitValue.textContent = formatINR(limitedProfit);

      if (results) {
        const missingHtml = pricing.missing && pricing.missing.length
          ? `<div style="margin-top:8px;color:#b91c1c;font-size:12px;">Missing price: ${pricing.missing.map(escapeHtml).join(', ')}</div>`
          : '';
        results.innerHTML = `
          <div style="font-size:13px;line-height:1.45;color:#374151;">
            <div style="font-weight:700;margin-bottom:6px;">Price source: data.json</div>
            <div>${escapeHtml(pricing.brand)} ${escapeHtml(pricing.resolution)} ${escapeHtml(pricing.camType || '')}</div>
            <div>Camera: ${formatINR(pricing.cameraUnitPrice)} x ${pricing.cams} = <strong>${formatINR(pricing.cameraTotal)}</strong></div>
            <div>Recorder: <strong>${formatINR(pricing.recorderPrice)}</strong></div>
            <div>HDD: <strong>${formatINR(pricing.hddPrice)}</strong></div>
            <div>Accessories: <strong>${formatINR(pricing.accessories)}</strong></div>
            <div>Material + GST: <strong>${formatINR(pricing.materialCost)}</strong></div>
            <div>Customer payable: <strong>${formatINR(pricing.customerPayable)}</strong></div>
            ${missingHtml}
          </div>
        `;
      }
    } catch (err) {
      console.warn('Unable to update profit overlay from data.json:', err);
      if (results) {
        results.innerHTML = `<div style="color:#b91c1c;font-size:13px;">Could not calculate profit from data.json.</div>`;
      }
    }
  }

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
      pendingLabel: document.getElementById('ex_pending_label'),
      pendingAmount: document.getElementById('ex_pending_amount'),
      pendingNote: document.getElementById('ex_pending_note'),
      addCustomBtn: document.getElementById('ex-add-custom-btn'),
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
        <div>
          <label for="overlay-actual-amount" style="display: block; margin-bottom: 4px; font-size: 14px; font-weight: 500; color: #495057;">Actual Amount (₹):</label>
          <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <input type="number" id="overlay-actual-amount" placeholder="Enter actual amount" step="0.01" min="0" style="width: 50% !important; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
            <button type="button" id="overlay-actual-amount-save" style="background: #111827; color: white; padding: 6px 14px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
              <i class="fas fa-save"></i> Update
            </button>
          </div>
        </div>
      </form>
    `;

    // Create and add profit details section to overlay
    const profitSection = document.createElement('div');
    profitSection.className = 'profit-details-overlay';
    profitSection.innerHTML = `
      <h4 style="margin: 0 0 15px 0; color: #333; font-size: 16px; font-weight: 600;">Profit Details</h4>
      <div id="results" style="margin-bottom: 15px;">Calculating from data.json...</div>
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
      const actualAmountInput = document.getElementById('overlay-actual-amount');
      const actualAmountLabel = document.querySelector('label[for="overlay-actual-amount"]');
      const actualAmountButton = document.getElementById('overlay-actual-amount-save');
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

        if (actualAmountInput) {
          actualAmountInput.disabled = true;
          actualAmountInput.style.opacity = '0.5';
          actualAmountInput.style.cursor = 'not-allowed';
          actualAmountInput.placeholder = 'Admin access required';
          if (actualAmountLabel) {
            actualAmountLabel.style.opacity = '0.5';
          }
        }
        if (actualAmountButton) {
          actualAmountButton.disabled = true;
          actualAmountButton.style.opacity = '0.5';
          actualAmountButton.style.cursor = 'not-allowed';
          actualAmountButton.title = 'Only admin users can update actual amount';
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

        if (actualAmountInput) {
          actualAmountInput.disabled = false;
          actualAmountInput.style.opacity = '1';
          actualAmountInput.style.cursor = 'text';
          actualAmountInput.placeholder = 'Enter actual amount';
          if (actualAmountLabel) {
            actualAmountLabel.style.opacity = '1';
          }
        }
        if (actualAmountButton) {
          actualAmountButton.disabled = false;
          actualAmountButton.style.opacity = '1';
          actualAmountButton.style.cursor = 'pointer';
          actualAmountButton.title = '';
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
                  const overlayActualAmount = document.getElementById('overlay-actual-amount');
                  if (overlayFullyPaid) overlayFullyPaid.checked = !!d.fully_paid;
                  if (overlayAmountPaid) overlayAmountPaid.value = (d.amount_paid ?? '');
                  if (overlayActualAmount) overlayActualAmount.value = String(d.actual_amount ?? '').replace(/[^0-9.]/g, '');
                }
              })
              .catch(err2 => console.warn('Fallback payment load failed:', err2));
          }
        }
      } catch (err) {
        console.warn('Unable to load payment data on overlay open:', err);
      }

      updateProfitDetailsOverlay();
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

    if (isAdminUser() && !document.getElementById('download-word-fab')) {
      const wordBtn = document.createElement('button');
      wordBtn.id = 'download-word-fab';
      wordBtn.type = 'button';
      wordBtn.title = 'Download Word invoice';
      wordBtn.setAttribute('aria-label', 'Download Word invoice');
      wordBtn.innerHTML = '<i class="fas fa-file-word"></i>';
      wordBtn.style.cssText = [
        'position:absolute', 'right:18px', 'bottom:138px', 'width:50px', 'height:50px',
        'border-radius:50%', 'border:none', 'background:#1f6fbe', 'color:#fff',
        'cursor:pointer', 'z-index:2147483642', 'box-shadow:0 4px 12px rgba(0,0,0,0.2)',
        'display:none', 'align-items:center', 'justify-content:center',
        'font-size:18px', 'transition:all 0.3s ease'
      ].join(';');
      wordBtn.addEventListener('click', () => { if (typeof window.downloadWordInvoice === 'function') window.downloadWordInvoice(); });
      container.appendChild(wordBtn);
    }

    // Show/hide button based on active tab
    window.updateExtrasButtonVisibility = function () {
      const btn = document.getElementById('extras-open-btn');
      const pdfBtn = document.getElementById('download-pdf-fab');
      const wordBtn = document.getElementById('download-word-fab');
      const invoiceTab = document.getElementById('tab-invoice');
      if (btn && invoiceTab) {
        // Check if invoice tab is currently displayed
        const isInvoiceTabActive = invoiceTab.style.display !== 'none';
        btn.style.display = isInvoiceTabActive ? 'flex' : 'none';
        if (pdfBtn) pdfBtn.style.display = isInvoiceTabActive ? 'flex' : 'none';
        if (wordBtn) wordBtn.style.display = isInvoiceTabActive && isAdminUser() ? 'flex' : 'none';
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
    if (refs.saveBtn.dataset.moduleReady === '1') return;
    refs.saveBtn.dataset.moduleReady = '1';

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

    const rerenderPreview = () => {
      if (typeof window.renderExtrasTable === 'function') {
        window.renderExtrasTable(collectExtrasPayload());
      }
    };

    [
      refs.cableRate,
      refs.cableLen,
      refs.cableTotal,
      refs.rackSize,
      refs.rackRate,
      refs.monitorSize,
      refs.monitorRate,
      refs.routerSize,
      refs.routerRate,
      refs.pendingLabel,
      refs.pendingAmount,
      refs.pendingNote
    ].forEach(input => input?.addEventListener('input', rerenderPreview));

    if (refs.addCustomBtn && refs.addCustomBtn.dataset.bound !== '1') {
      refs.addCustomBtn.dataset.bound = '1';
      refs.addCustomBtn.addEventListener('click', () => {
        const label = (refs.customLabel?.value || '').trim();
        const item = (refs.customItem?.value || '').trim();
        const total = Number.parseFloat(refs.customTotal?.value) || 0;

        if (!label && !item && total <= 0) {
          alert('Enter custom item details before adding.');
          return;
        }

        currentCustomItems.push({ label, item, total });

        if (refs.customLabel) refs.customLabel.value = '';
        if (refs.customItem) refs.customItem.value = '';
        if (refs.customTotal) refs.customTotal.value = '';

        rerenderPreview();
      });
    }

    if (refs.tableBody && refs.tableBody.dataset.bound !== '1') {
      refs.tableBody.dataset.bound = '1';
      refs.tableBody.addEventListener('click', (event) => {
        const deleteBtn = event.target.closest('[data-extra-delete]');
        if (!deleteBtn) return;

        const kind = deleteBtn.getAttribute('data-extra-kind');
        const index = Number.parseInt(deleteBtn.getAttribute('data-extra-index') || '-1', 10);

        if (kind === 'custom' && index >= 0) {
          currentCustomItems.splice(index, 1);
        } else if (kind === 'cable') {
          if (refs.cableRate) refs.cableRate.value = '';
          if (refs.cableLen) refs.cableLen.value = '';
          if (refs.cableTotal) refs.cableTotal.value = '';
        } else if (kind === 'rack') {
          if (refs.rackSize) refs.rackSize.value = '';
          if (refs.rackRate) refs.rackRate.value = '';
        } else if (kind === 'monitor') {
          if (refs.monitorSize) refs.monitorSize.value = '';
          if (refs.monitorRate) refs.monitorRate.value = '';
        } else if (kind === 'router') {
          if (refs.routerSize) refs.routerSize.value = '';
          if (refs.routerRate) refs.routerRate.value = '';
        }

        rerenderPreview();
      });
    }

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
        if (typeof window.refreshInstallHistoryPanel === 'function') {
          window.refreshInstallHistoryPanel();
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

    const payload = normalizeExtrasPayload({
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
      customItems: currentCustomItems.slice(),
      pendingWarranty: {
        label: (r.pendingLabel?.value || 'Amount stands as unpaid').trim(),
        amount: num(r.pendingAmount?.value),
        note: (r.pendingNote?.value || PENDING_WARRANTY_NOTE).trim()
      }
    });

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
    if (Array.isArray(payload.customItems)) {
      payload.customItems.forEach(item => {
        total += Number(item?.total) || 0;
      });
    }
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
        payload = normalizeExtrasPayload(payload);
        const dbPendingAmount = Number.parseFloat(data.pending_amount);
        if (Number.isFinite(dbPendingAmount) && dbPendingAmount > 0) {
          payload.pendingWarranty.amount = dbPendingAmount;
          payload.pendingWarranty.label = PENDING_AMOUNT_LABEL;
          payload.pendingWarranty.note = PENDING_WARRANTY_NOTE;
        }
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
        const dbPendingAmount = Number.parseFloat(data.pending_amount);
        const emptyPayload = normalizeExtrasPayload({
          pendingWarranty: {
            amount: Number.isFinite(dbPendingAmount) ? dbPendingAmount : 0,
            label: PENDING_AMOUNT_LABEL,
            note: PENDING_WARRANTY_NOTE
          }
        });
        fillExtrasForm(emptyPayload);
        // Clear extras
        if (typeof window.renderExtrasTable === 'function') {
          window.renderExtrasTable(emptyPayload);
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

    const payload = normalizeExtrasPayload(p);
    currentCustomItems = payload.customItems.slice();

    if (r.cableRate) r.cableRate.value = '';
    if (r.cableLen) r.cableLen.value = '';
    if (r.cableTotal) r.cableTotal.value = '';
    if (r.rackSize) r.rackSize.value = '';
    if (r.rackRate) r.rackRate.value = '';
    if (r.monitorSize) r.monitorSize.value = '';
    if (r.monitorRate) r.monitorRate.value = '';
    if (r.routerSize) r.routerSize.value = '';
    if (r.routerRate) r.routerRate.value = '';

    if (payload.cable) {
      console.log('🔌 Setting cable values:', payload.cable);
      if (r.cableRate) r.cableRate.value = payload.cable.rate ?? '';
      if (r.cableLen) r.cableLen.value = payload.cable.length ?? '';
      if (r.cableTotal) r.cableTotal.value = payload.cable.total ?? '';
    }
    if (payload.rack) {
      console.log('🗜️ Setting rack values:', payload.rack);
      if (r.rackSize) r.rackSize.value = payload.rack.size ?? '';
      if (r.rackRate) r.rackRate.value = payload.rack.rate ?? '';
    }
    if (payload.monitor) {
      console.log('🖥️ Setting monitor values:', payload.monitor);
      if (r.monitorSize) r.monitorSize.value = payload.monitor.size ?? '';
      if (r.monitorRate) r.monitorRate.value = payload.monitor.rate ?? '';
    }
    if (payload.router) {
      console.log('🌐 Setting router values:', payload.router);
      if (r.routerSize) r.routerSize.value = payload.router.size ?? '';
      if (r.routerRate) r.routerRate.value = payload.router.rate ?? '';
    }

    if (r.customLabel) r.customLabel.value = '';
    if (r.customItem) r.customItem.value = '';
    if (r.customTotal) r.customTotal.value = '';
    if (r.pendingLabel) r.pendingLabel.value = PENDING_AMOUNT_LABEL;
    if (r.pendingAmount) r.pendingAmount.value = payload.pendingWarranty?.amount || '';
    if (r.pendingNote) r.pendingNote.value = PENDING_WARRANTY_NOTE;
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
  window.updateProfitDetailsOverlay = updateProfitDetailsOverlay;

  // Add missing renderExtrasTable function
  function renderExtrasTable(p) {
    console.log('📋 renderExtrasTable called with payload:', p);
    const r = getExtrasFormRefs();
    console.log('🎯 renderExtrasTable form refs:', r);
    if (!r.tableBody) {
      console.error('❌ No table body found in renderExtrasTable');
      return;
    }
    const payload = normalizeExtrasPayload(p);
    const rows = [];
    const fmt = n => Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
    const actionCell = (kind, index = '') => `
      <button
        type="button"
        data-extra-delete="1"
        data-extra-kind="${escapeHtml(kind)}"
        data-extra-index="${escapeHtml(index)}"
        title="Remove item"
        aria-label="Remove item"
        style="border:none;background:transparent;color:#dc3545;cursor:pointer;font-size:28px;font-weight:700;line-height:1;padding:0 4px;display:inline-flex;align-items:center;justify-content:center;"
      >
        -
      </button>
    `;

    if (payload.cable && (payload.cable.total || 0) > 0) {
      console.log('🔌 Adding cable row:', payload.cable);
      rows.push({ item: 'Cable 3+1', details: `Rate ${fmt(payload.cable.rate)} x Len ${fmt(payload.cable.length)}`, amount: payload.cable.total, kind: 'cable' });
    }
    if (payload.rack && (payload.rack.rate || 0) > 0) {
      console.log('🗜️ Adding rack row:', payload.rack);
      rows.push({ item: 'Rack', details: `Size ${payload.rack.size || ''}`, amount: payload.rack.rate, kind: 'rack' });
    }
    if (payload.monitor && (payload.monitor.rate || 0) > 0) {
      console.log('🖥️ Adding monitor row:', payload.monitor);
      rows.push({ item: 'Monitor', details: `Size ${payload.monitor.size || ''}`, amount: payload.monitor.rate, kind: 'monitor' });
    }
    if (payload.router && (payload.router.rate || 0) > 0) {
      console.log('🌐 Adding router row:', payload.router);
      rows.push({ item: 'Router', details: `Size ${payload.router.size || ''}`, amount: payload.router.rate, kind: 'router' });
    }
    if (Array.isArray(payload.customItems)) {
      payload.customItems.forEach((customItem, index) => {
        if ((customItem.total || 0) <= 0 && !customItem.label && !customItem.item) {
          return;
        }
        console.log('⚙️ Adding custom row:', customItem);
        const label = (customItem.label || 'Custom').trim();
        const item = (customItem.item || '').trim();
        rows.push({ item: label, details: item, amount: customItem.total, kind: 'custom', index });
      });
    }

    console.log('📋 Final rows for table:', rows);

    r.tableBody.innerHTML = rows.map(row => `
      <tr>
        <td style="padding:6px;border:1px solid #eee;">${escapeHtml(row.item)}</td>
        <td style="padding:6px;border:1px solid #eee;">${escapeHtml(row.details)}</td>
        <td style="padding:6px;border:1px solid #eee;text-align:right;">${fmt(row.amount)}</td>
        <td style="padding:6px;border:1px solid #eee;text-align:center;">${actionCell(row.kind, row.index)}</td>
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
    applyPendingWarrantyToInvoice(payload.pendingWarranty);
    console.log('✅ renderExtrasTable completed');
  }
  window.renderExtrasTable = renderExtrasTable;

  function applyPendingWarrantyToInvoice(pendingWarranty) {
    const host = document.getElementById('invoice-content');
    const invoice = host ? host.querySelector('#invoice') : null;
    if (!host || !invoice) return;

    host.querySelectorAll('#pending-warranty-row, #pending-warranty-notice, #pending-warranty-image').forEach(el => el.remove());
    host.querySelectorAll('[data-warranty-struck="1"]').forEach(el => {
      el.style.textDecoration = '';
      el.style.opacity = '';
      el.removeAttribute('data-warranty-struck');
    });

    const amount = Number.parseFloat(pendingWarranty?.amount) || 0;
    if (amount <= 0) return;

    const label = String(pendingWarranty?.label || 'Amount stands as unpaid').trim();
    const note = String(pendingWarranty?.note || PENDING_WARRANTY_NOTE).trim();
    const fmt = n => Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });

    const totalTable = host.querySelector('.total table');
    if (totalTable) {
      const row = document.createElement('tr');
      row.id = 'pending-warranty-row';
      row.innerHTML = `
        <td style="color:#b91c1c;font-weight:800;">${escapeHtml(label)}</td>
        <td style="color:#b91c1c;font-weight:900;text-align:right;">₹${fmt(amount)}</td>
      `;
      totalTable.appendChild(row);
    }

    const notice = document.createElement('div');
    notice.id = 'pending-warranty-notice';
    notice.innerHTML = `<i class="fas fa-triangle-exclamation" style="font-size:18px;line-height:1;"></i><span>${escapeHtml(note)}</span>`;
    notice.style.cssText = [
      'display:flex',
      'align-items:flex-start',
      'gap:10px',
      'margin:18px 0 0',
      'padding:12px 14px',
      'border-radius:6px',
      'background:#dc2626',
      'color:#fff',
      'font-weight:800',
      'font-size:13px',
      'line-height:1.4'
    ].join(';');
    const totalEl = host.querySelector('.total');
    if (totalEl && totalEl.parentNode) {
      totalEl.insertAdjacentElement('afterend', notice);
    } else {
      invoice.insertBefore(notice, invoice.firstChild);
    }

    host.querySelectorAll('#termsSection li, .terms li').forEach(li => {
      if (/warranty/i.test(li.textContent || '')) {
        li.style.textDecoration = 'line-through';
        li.style.opacity = '0.72';
        li.setAttribute('data-warranty-struck', '1');
      }
    });

    const imageWrap = document.createElement('div');
    imageWrap.id = 'pending-warranty-image';
    imageWrap.style.cssText = [
      'float:right',
      'width:258px',
      'height:auto',
      'margin:-16px -8px 4px 18px',
      'text-align:right',
      'transform:rotate(-35deg)',
      'transform-origin:center center'
    ].join(';');
    imageWrap.innerHTML = '<img src="https://www.smartronic.online/content/uploads/2025/01/no-warranty.png" alt="No warranty applicable" style="width:258px;height:auto;display:block;">';
    const detailsEl = host.querySelector('.invoice-details');
    const billingEl = host.querySelector('.billing');
    if (detailsEl) {
      detailsEl.insertBefore(imageWrap, detailsEl.firstChild);
    } else if (billingEl) {
      billingEl.insertBefore(imageWrap, billingEl.firstChild);
    } else {
      invoice.insertBefore(imageWrap, invoice.firstChild);
    }
  }
  window.applyPendingWarrantyToInvoice = applyPendingWarrantyToInvoice;

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
    if (!rows.length) {
      console.log('🚫 No extras rows to render in invoice');
      return;
    }
    const fmt = n => Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
    const wrapper = document.createElement('div');
    wrapper.id = 'extras-invoice-section';
    wrapper.className = 'items';
    wrapper.innerHTML = `
      <h3>Extra Items</h3>
      <table>
        <thead><tr><th style="text-align:left;">Item</th><th style="text-align:left;">Details</th><th style="text-align:right;">Amount</th></tr></thead>
        <tbody>${rows.map(r => `<tr><td>${escapeHtml(r.item)}</td><td>${escapeHtml(r.details)}</td><td style="text-align:right;">${fmt(r.amount)}</td></tr>`).join('')}</tbody>
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
