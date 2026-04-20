// Invoice and material content loading for installs system
(function(window) {
  'use strict';

  // Invoice loading with PDF support
  async function loadInvoiceIntoContainer() {
    const params = new URLSearchParams();
    const fields = [
      'id', 'name', 'cams', 'bullets', 'dome', 'hdd', 'monitor', 'type', 
      'location', 'time', 'date', 'owner', 'technician', 'helper', 
      'resolution', 'map', 'rack', 'notes'
    ];
    
    fields.forEach(field => {
      const input = document.getElementById(field);
      if (input && input.value) {
        params.append(field, input.value);
      }
    });
    params.append('render_invoice', '1');

    const container = document.getElementById('invoice-content');
    if (container) container.innerHTML = 'Loading invoice...';
    
    try {
      const res = await fetch(`../smart/installs.php?${params.toString()}`, { cache: 'no-store' });
      
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      }
      
      const html = await res.text();
      if (container) {
        container.innerHTML = html || 'No invoice content available.';
      }
    } catch (e) {
      console.error('Invoice load error', e);
      if (container) {
        container.innerHTML = `Failed to load invoice: ${e.message}`;
      }
    } finally {
      // Initialize modules after content loads
      try {
        console.log('🔧 Initializing invoice modules...');
        if (typeof window.initExtrasModule === 'function') {
          window.initExtrasModule();
          console.log('✅ initExtrasModule called');
        } else {
          console.warn('⚠️ initExtrasModule not available');
        }
        if (typeof window.loadExtrasFromDB === 'function') {
          console.log('🔍 Loading extras from DB...');
          await window.loadExtrasFromDB();
          console.log('✅ loadExtrasFromDB completed');
        } else {
          console.warn('⚠️ loadExtrasFromDB not available');
        }
        try {
          const host = document.getElementById('invoice-content');
          if (host) {
            initInvoiceControls(host);
            initPaymentLinkSection(host);
            setupDownloadPdf(host);
            try {
              const toolbarBtn = host.querySelector('#noteEditorToolbar button');
              if (toolbarBtn && /download pdf/i.test(toolbarBtn.textContent || '')) {
                toolbarBtn.style.display = 'none';
              }
            } catch (_) {}
            console.log('✅ Invoice controls initialized');
          }
        } catch (err2) {
          console.warn('⚠️ Failed to initialize invoice controls:', err2);
        }
      } catch (err) {
        console.warn('Error initializing invoice modules:', err);
      }
    }
  }

  function initInvoiceControls(scopeEl) {
    const noteInput = scopeEl.querySelector('#noteInput');
    const noteOutput = scopeEl.querySelector('#noteOutput');
    const billingEl = scopeEl.querySelector('.billing');
    const billingPs = billingEl ? billingEl.querySelectorAll('p') : [];
    const addressText = Array.from(billingPs).map(el => (el.textContent||'').trim()).join('\n');
    const nameInput = scopeEl.querySelector('#customerNameInput');
    const noteEditor = scopeEl.querySelector('#noteEditor');
    const toolbar = scopeEl.querySelector('#noteEditorToolbar');
    const billingNameEl = scopeEl.querySelector('#billingNameDisplay');
    const invoiceNameEl = scopeEl.querySelector('#customerNameDisplay');
    const invoiceControls = scopeEl.querySelector('#invoiceControls');
    const invHeader = scopeEl.querySelector('#invoiceControlsHeader');
    const invContent = scopeEl.querySelector('#invoiceControlsContent');
    const scanHeader = scopeEl.querySelector('#scannerCollapsibleHeader');
    const scannerPanel = scopeEl.querySelector('#scanner-panel');

    if (noteInput) {
      if (!noteInput.value || !noteInput.value.trim()) noteInput.value = addressText;
    }
    if (noteOutput) {
      noteOutput.innerHTML = noteInput ? noteInput.value : addressText;
    }
    if (noteEditor) {
      const initial = noteOutput ? noteOutput.innerHTML : addressText;
      noteEditor.innerHTML = initial;
    }
    if (nameInput && !nameInput.value && billingNameEl) {
      nameInput.value = (billingNameEl.textContent||'').trim();
    }
    Array.from(billingPs).forEach(p => { p.style.display = 'none'; });
    if (noteInput && noteOutput && !noteEditor) {
      const updateNote = () => { noteOutput.innerHTML = noteInput.value; };
      noteInput.addEventListener('input', updateNote);
      noteInput.addEventListener('mousedown', updateNote);
    }
    if (noteEditor && noteOutput) {
      const updateNoteHtml = () => { noteOutput.innerHTML = noteEditor.innerHTML; };
      noteEditor.addEventListener('input', updateNoteHtml);
      noteEditor.addEventListener('mousedown', updateNoteHtml);
    }

    const proformaToggle = scopeEl.querySelector('#proformaToggle');
    const invoiceContainer = scopeEl.querySelector('#invoice');
    const paidRow = scopeEl.querySelector('#paidRow');
    const termsEl = scopeEl.querySelector('#termsSection');
    const originalTextNodes = new Map();

    function updateInvoiceWord(isChecked) {
      if (!invoiceContainer) return;
      try {
        const walker = document.createTreeWalker(invoiceContainer, NodeFilter.SHOW_TEXT, null);
        let node;
        while ((node = walker.nextNode())) {
          if (!originalTextNodes.has(node)) {
            originalTextNodes.set(node, node.nodeValue);
          }
          const sourceText = originalTextNodes.get(node);
          node.nodeValue = isChecked ? sourceText.replace(/\bInvoice\b/gi, 'Porforma Invoice') : sourceText;
        }
      } catch (e) {
        const all = invoiceContainer.querySelectorAll('*');
        all.forEach(el => {
          const orig = originalTextNodes.get(el) || el.textContent;
          originalTextNodes.set(el, orig);
          el.textContent = isChecked ? orig.replace(/\bInvoice\b/gi, 'Porforma Invoice') : orig;
        });
      }
      if (paidRow) paidRow.style.display = isChecked ? 'none' : '';
      if (termsEl) termsEl.style.display = isChecked ? 'none' : '';
    }

    if (proformaToggle) {
      proformaToggle.addEventListener('change', (e) => updateInvoiceWord(e.target.checked));
      updateInvoiceWord(proformaToggle.checked);
    }

    const applyBtn = scopeEl.querySelector('#applyInvoiceChanges');
    if (applyBtn) {
      applyBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const newName = nameInput ? nameInput.value.trim() : '';
        const newNoteHtml = noteEditor ? noteEditor.innerHTML : (noteInput ? noteInput.value : '');
        if (newNoteHtml && noteOutput) noteOutput.innerHTML = newNoteHtml;
        if (noteInput && noteEditor) noteInput.value = noteEditor.textContent;
        if (newName) {
          if (billingNameEl) billingNameEl.textContent = newName;
          if (invoiceNameEl) invoiceNameEl.textContent = newName;
        }
      });
    }

    if (toolbar && noteEditor) {
      const btns = toolbar.querySelectorAll('[data-cmd]');
      btns.forEach(btn => {
        btn.addEventListener('click', () => {
          const cmd = btn.getAttribute('data-cmd');
          noteEditor.focus();
          if (cmd === 'p') {
            document.execCommand('formatBlock', false, 'p');
          } else {
            document.execCommand(cmd, false, null);
          }
        });
      });

      try {
        const allBtns = Array.from(toolbar.querySelectorAll('button'));
        allBtns.forEach(b => {
          const txt = String(b.textContent || '').trim();
          const onclick = String(b.getAttribute('onclick') || '');
          if (/download\s*pdf/i.test(txt) || /downloadPDF\s*\(/.test(onclick)) b.remove();
        });
      } catch (_) {}
    }

    let invoiceEditorHeader = null;
    let invoiceEditorCaret = null;
    try {
      if (invoiceControls && invContent) {
        if (invoiceControls.getAttribute('style')) invoiceControls.removeAttribute('style');
        invoiceControls.classList.add('paylink-form-card');

        if (invHeader) invHeader.style.display = 'none';

        const existingHeader = invoiceControls.querySelector('#invoice-editor-header');
        if (existingHeader) {
          invoiceEditorHeader = existingHeader;
          invoiceEditorCaret = invoiceEditorHeader.querySelector('#invoice-editor-caret');
        } else {
          invoiceEditorHeader = document.createElement('div');
          invoiceEditorHeader.id = 'invoice-editor-header';
          invoiceEditorHeader.className = 'paylink-collapsible-header';
          invoiceEditorHeader.setAttribute('role', 'button');
          invoiceEditorHeader.setAttribute('tabindex', '0');
          invoiceEditorHeader.setAttribute('aria-expanded', 'false');
          invoiceEditorHeader.innerHTML = `
            <i class="fas fa-pen-to-square" aria-hidden="true"></i>
            Edit Invoice / Proforma
            <i id="invoice-editor-caret" class="fas fa-caret-down paylink-caret" aria-hidden="true"></i>
          `;
          invoiceEditorCaret = invoiceEditorHeader.querySelector('#invoice-editor-caret');
          invoiceControls.insertBefore(invoiceEditorHeader, invContent);
        }

        const proformaToggle = scopeEl.querySelector('#proformaToggle');
        if (proformaToggle) {
          const existingRow = invoiceControls.querySelector('#invoice-proforma-row');
          if (!existingRow) {
            const row = document.createElement('div');
            row.id = 'invoice-proforma-row';
            row.className = 'invoice-proforma-row';

            const label = document.createElement('label');
            label.className = 'invoice-proforma-label';
            label.appendChild(proformaToggle);
            label.appendChild(document.createTextNode(' Porforma Invoice'));

            row.appendChild(label);
            invoiceControls.insertBefore(row, invContent);
          } else if (!existingRow.contains(proformaToggle)) {
            existingRow.textContent = '';
            const label = document.createElement('label');
            label.className = 'invoice-proforma-label';
            label.appendChild(proformaToggle);
            label.appendChild(document.createTextNode(' Porforma Invoice'));
            existingRow.appendChild(label);
          }
        }

        const proformaRow = invoiceControls.querySelector('#invoice-proforma-row');
        if (proformaRow && invoiceEditorHeader) {
          invoiceEditorHeader.insertAdjacentElement('afterend', proformaRow);
        }
      }
    } catch (_) {}

    function toggleInvoiceEditor() {
      if (!invContent || !invoiceEditorHeader) return;
      const isOpen = invContent.style.display !== 'none' && !!invContent.style.display;
      const nextOpen = !isOpen;
      invContent.style.display = nextOpen ? 'block' : 'none';
      if (invoiceEditorCaret) invoiceEditorCaret.style.transform = nextOpen ? 'rotate(180deg)' : 'rotate(0deg)';
      invoiceEditorHeader.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
    }

    if (invoiceEditorHeader && invContent) {
      invoiceEditorHeader.addEventListener('click', toggleInvoiceEditor);
      invoiceEditorHeader.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggleInvoiceEditor();
        }
      });
    } else if (invHeader && invContent) {
      invHeader.addEventListener('click', () => {
        invContent.style.display = (invContent.style.display === 'none' || !invContent.style.display) ? 'block' : 'none';
      });
    }

    if (scanHeader && scannerPanel) {
      scanHeader.addEventListener('click', () => {
        scannerPanel.style.display = (scannerPanel.style.display === 'none' || !scannerPanel.style.display) ? 'block' : 'none';
      });
    }
  }

  function initPaymentLinkSection(scopeEl) {
    if (!scopeEl || scopeEl.querySelector('#paylink-panel')) return;

    const scannerPanel = scopeEl.querySelector('#scanner-panel');
    const invoiceEl = scopeEl.querySelector('#invoice');
    if (!scannerPanel && !invoiceEl) return;

    const wrapper = document.createElement('div');
    wrapper.id = 'paylink-wrapper';

    wrapper.innerHTML = `
      <div id="paylink-collapsible-header" class="paylink-collapsible-header" role="button" tabindex="0" aria-expanded="false">
        <i class="fas fa-link" aria-hidden="true"></i>
        Create Payment Link
        <i id="paylink-caret" class="fas fa-caret-down paylink-caret" aria-hidden="true"></i>
      </div>
      <div id="paylink-panel" class="paylink-panel">
        <div class="paylink-form-card">
          <div class="paylink-form-title">Create Razorpay Payment Link</div>
          <form id="paylink-form" autocomplete="off">
            <div class="paylink-grid">
              <div class="paylink-field">
                <label for="paylink-name">Customer Name</label>
                <input id="paylink-name" name="name" type="text" required>
              </div>
              <div class="paylink-field">
                <label for="paylink-phone">Customer Phone (10 digit)</label>
                <input id="paylink-phone" name="phone" type="tel" inputmode="numeric" required>
              </div>
              <div class="paylink-field">
                <label for="paylink-amount">Amount (INR)</label>
                <input id="paylink-amount" name="amount" type="number" min="1" step="1" required>
              </div>
              <div class="paylink-field">
                <label for="paylink-description">Description</label>
                <input id="paylink-description" name="description" type="text" required>
              </div>
            </div>

            <div class="paylink-actions">
              <button class="paylink-btn paylink-btn-primary" type="submit" id="paylink-submit">
                Create Link <i class="fas fa-arrow-right" aria-hidden="true"></i>
              </button>
              <span id="paylink-status" class="paylink-status"></span>
            </div>

            <div id="paylink-result" class="paylink-result-box" style="display:none;">
              <div><span style="color:#6b7280; font-weight:800;">Reference:</span> <span id="paylink-ref" class="paylink-result-link"></span></div>
              <div style="margin-top: 6px;">
                <span style="color:#6b7280; font-weight:800;">Link:</span>
                <div><a id="paylink-link" class="paylink-result-link" href="#" target="_blank" rel="noopener"></a></div>
              </div>
              <div class="paylink-result-actions">
                <button type="button" class="paylink-btn paylink-btn-flat" id="paylink-copy">Copy Link</button>
                <a class="paylink-btn paylink-btn-wa" id="paylink-wa" href="#" target="_blank" rel="noopener">WhatsApp</a>
              </div>
            </div>
          </form>
        </div>
      </div>
    `;

    const scannerHeader = scopeEl.querySelector('#scannerCollapsibleHeader');
    const invoiceControls = scopeEl.querySelector('#invoiceControls');

    if (scannerPanel && scannerPanel.parentNode) {
      scannerPanel.insertAdjacentElement('afterend', wrapper);
    } else if (scannerHeader && scannerHeader.parentNode) {
      scannerHeader.insertAdjacentElement('afterend', wrapper);
    } else if (invoiceControls && invoiceControls.parentNode) {
      invoiceControls.parentNode.insertBefore(wrapper, invoiceControls);
    } else if (invoiceEl && invoiceEl.parentNode) {
      invoiceEl.parentNode.insertBefore(wrapper, invoiceEl);
    } else {
      scopeEl.appendChild(wrapper);
    }

    if (invoiceControls && wrapper.parentNode) {
      wrapper.insertAdjacentElement('afterend', invoiceControls);
    }

    const header = wrapper.querySelector('#paylink-collapsible-header');
    const panel = wrapper.querySelector('#paylink-panel');
    const caret = wrapper.querySelector('#paylink-caret');

    function setExpanded(expanded) {
      if (!panel) return;
      panel.style.display = expanded ? 'block' : 'none';
      if (caret) caret.style.transform = expanded ? 'rotate(180deg)' : 'rotate(0deg)';
      if (header) header.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }

    setExpanded(false);

    if (header) {
      header.addEventListener('click', () => setExpanded(panel.style.display === 'none' || !panel.style.display));
      header.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          setExpanded(panel.style.display === 'none' || !panel.style.display);
        }
      });
    }

    const nameInput = wrapper.querySelector('#paylink-name');
    const phoneInput = wrapper.querySelector('#paylink-phone');
    const amountInput = wrapper.querySelector('#paylink-amount');
    const descInput = wrapper.querySelector('#paylink-description');

    try {
      const invName = scopeEl.querySelector('#customerNameDisplay');
      if (nameInput && invName && invName.textContent) nameInput.value = invName.textContent.trim();
    } catch (_) {}

    try {
      const billing = scopeEl.querySelector('.billing');
      const ps = billing ? billing.querySelectorAll('p') : [];
      const phoneText = ps && ps[1] ? (ps[1].textContent || '') : (ps && ps[0] ? (ps[0].textContent || '') : '');
      const digits = String(phoneText).replace(/\D+/g, '');
      const normalized = digits.length > 10 ? digits.slice(-10) : digits;
      if (phoneInput && normalized.length === 10) phoneInput.value = normalized;
    } catch (_) {}

    try {
      if (descInput && !String(descInput.value || '').trim()) {
        descInput.value = 'Smartronic | CCTV Surveillance System';
      }
    } catch (_) {}

    function parseInr(text) {
      const clean = String(text || '').replace(/[^0-9.]/g, '');
      const num = Number(clean);
      return Number.isFinite(num) ? num : null;
    }

    try {
      const payableEl = scopeEl.querySelector('#payable');
      const payable = parseInr(payableEl ? payableEl.textContent : '');
      if (amountInput && (!String(amountInput.value || '').trim()) && payable && payable > 0) {
        amountInput.value = String(Math.round(payable));
      }
    } catch (_) {}

    try {
      const existing = String(amountInput ? (amountInput.value || '') : '').trim();
      if (amountInput && !existing) {
        let orderId = '';
        try {
          if (typeof window.getCurrentOrderId === 'function') orderId = String(window.getCurrentOrderId() || '');
        } catch (_) {}
        if (orderId) {
          fetch(`/admin_v2/smart/installs.php?get_payment=1&id=${encodeURIComponent(orderId)}`, { cache: 'no-store' })
            .then(r => r.json())
            .then(d => {
              if (!amountInput) return;
              const now = String(amountInput.value || '').trim();
              if (now) return;
              const amt = d && d.success ? Number(d.amount_paid) : NaN;
              if (Number.isFinite(amt) && amt > 0) {
                amountInput.value = String(Math.round(amt));
              }
            })
            .catch(() => {});
        }
      }
    } catch (_) {}

    const form = wrapper.querySelector('#paylink-form');
    const submitBtn = wrapper.querySelector('#paylink-submit');
    const statusText = wrapper.querySelector('#paylink-status');
    const resultBox = wrapper.querySelector('#paylink-result');
    const refOut = wrapper.querySelector('#paylink-ref');
    const linkOut = wrapper.querySelector('#paylink-link');
    const waBtn = wrapper.querySelector('#paylink-wa');
    const copyBtn = wrapper.querySelector('#paylink-copy');

    function setBusy(busy) {
      if (submitBtn) submitBtn.disabled = busy;
      if (statusText) {
        if (busy) statusText.textContent = 'Creating…';
        else if (statusText.textContent === 'Creating…') statusText.textContent = '';
      }
    }

    function normalizePhone(raw) {
      const digits = String(raw || '').replace(/\D+/g, '');
      if (digits.length > 10) return digits.slice(-10);
      return digits;
    }

    function showStatus(msg) {
      if (!statusText) return;
      statusText.textContent = msg || '';
    }

    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (resultBox) resultBox.style.display = 'none';
        setExpanded(true);
        setBusy(true);
        try {
          const name = String((wrapper.querySelector('#paylink-name') || {}).value || '').trim();
          const phone = normalizePhone(String((wrapper.querySelector('#paylink-phone') || {}).value || ''));
          const amount = Number((wrapper.querySelector('#paylink-amount') || {}).value || 0);
          const description = String((wrapper.querySelector('#paylink-description') || {}).value || '').trim();

          if (!name || phone.length !== 10 || !Number.isFinite(amount) || amount <= 0 || !description) {
            showStatus('Fill all fields correctly');
            return;
          }

          const res = await fetch('/payments/create_payment_link.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, phone, amount, description })
          });

          const data = await res.json().catch(() => null);
          if (!res.ok || !data || !data.success) {
            const msg = data && (data.error || data.details) ? String(data.error || data.details) : 'Failed to create link';
            showStatus(msg);
            return;
          }

          if (refOut) refOut.textContent = data.reference_id || '';
          if (linkOut) {
            linkOut.textContent = data.link || '';
            linkOut.href = data.link || '#';
          }

          if (waBtn) {
            const waMsg = encodeURIComponent(`Hi ${name}, please complete your payment using this link: ${data.link}`);
            waBtn.href = `https://wa.me/91${phone}?text=${waMsg}`;
          }

          if (resultBox) resultBox.style.display = '';
          showStatus('');
        } finally {
          setBusy(false);
        }
      });
    }

    if (copyBtn) {
      copyBtn.addEventListener('click', async () => {
        const link = linkOut && linkOut.href ? linkOut.href : '';
        if (!link || link === '#') return;
        try {
          await navigator.clipboard.writeText(link);
          showStatus('Copied');
          setTimeout(() => showStatus(''), 1200);
        } catch (_) {
          try {
            const ta = document.createElement('textarea');
            ta.value = link;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            ta.remove();
            showStatus('Copied');
            setTimeout(() => showStatus(''), 1200);
          } catch (_) {}
        }
      });
    }
  }

  function setupDownloadPdf(scopeEl) {
    window.downloadPDF = async function() {
      const invoiceHost = scopeEl.querySelector('#invoice-content') || scopeEl;
      const invoiceEl = invoiceHost.querySelector('#invoice');
      if (!invoiceEl) {
        alert('Invoice content not loaded');
        return;
      }

      let id = 'current';
      try {
        if (typeof window.getCurrentOrderId === 'function') id = window.getCurrentOrderId() || id;
      } catch (_) {}

      const clone = invoiceHost.cloneNode(true);
      clone.querySelectorAll('script, #scannerCollapsibleHeader, #invoiceControls, #scanner-panel, #paylink-wrapper, #paylink-panel, #paylink-collapsible-header').forEach((node) => node.remove());

      const pdfButton = document.getElementById('download-pdf-fab');
      const originalHtml = pdfButton ? pdfButton.innerHTML : '';
      if (pdfButton) {
        pdfButton.disabled = true;
        pdfButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
      }

      try {
        const res = await fetch('../smart/installs.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            invoicePdf: true,
            id,
            html: clone.innerHTML
          })
        });

        if (!res.ok) {
          throw new Error(`HTTP ${res.status}`);
        }

        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `Smartronic_Invoice_${id}.pdf`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
        if (typeof window.updatePdfSentIndicator === 'function') {
          window.updatePdfSentIndicator('yes');
        }
        if (window.currentInstallData) {
          window.currentInstallData.pdf_sent = 'yes';
        }
      } catch (err) {
        console.error('Failed to generate invoice PDF', err);
        alert('Failed to generate invoice PDF');
      } finally {
        if (pdfButton) {
          pdfButton.disabled = false;
          pdfButton.innerHTML = originalHtml;
        }
      }
    };
  }

  

  // Material content loading
  async function loadMaterialIntoContainer() {
    // If already loaded and initialized, just re-fill from form
    if (window.__materialLoaded) {
      try {
        if (typeof window.fillMaterialFromForm === 'function') {
          window.fillMaterialFromForm();
        }
      } catch (err) {
        console.warn('Error filling material from form:', err);
      }
      return;
    }

    const params = new URLSearchParams();
    const fields = [
      'id', 'name', 'cams', 'bullets', 'dome', 'hdd', 'monitor', 'type',
      'location', 'time', 'date', 'owner', 'technician', 'helper',
      'resolution', 'map', 'rack', 'notes'
    ];
    
    fields.forEach(field => {
      const input = document.getElementById(field);
      if (input && input.value) {
        params.append(field, input.value);
      }
    });
    params.append('render_material', '1');

    const host = document.getElementById('material-content');
    if (host) host.textContent = 'Loading material...';
    
    try {
      const res = await fetch(`../smart/installs.php?${params.toString()}`, { cache: 'no-store' });
      
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      }
      
      const html = await res.text();
      if (!host) return;
      
      // Inject HTML
      host.innerHTML = html || 'No material content available.';

      // Enforce global layout guards AFTER injection so late styles in material cannot override
      try {
        let guard = document.getElementById('material-global-guards');
        if (!guard) {
          guard = document.createElement('style');
          guard.id = 'material-global-guards';
          document.head.appendChild(guard);
        }
        guard.textContent = `
          html, body { margin: 0 !important; padding: 0 !important; }
        `;
      } catch(_) {}

      // Execute scripts in the injected HTML
      const tmp = document.createElement('div');
      tmp.innerHTML = html;
      const scripts = Array.from(tmp.querySelectorAll('script'));

      // Load external scripts sequentially
      for (const s of scripts.filter(sc => sc.src)) {
        await new Promise((resolve) => {
          const el = document.createElement('script');
          el.src = s.src;
          el.onload = resolve;
          el.onerror = resolve; // don't block on errors
          document.head.appendChild(el);
        });
      }

      // Execute inline scripts
      for (const s of scripts.filter(sc => !sc.src)) {
        try {
          const el = document.createElement('script');
          el.type = s.type || 'text/javascript';
          el.text = s.textContent || '';
          host.appendChild(el);
        } catch (scriptErr) {
          console.warn('Error executing inline script:', scriptErr);
        }
      }

      window.__materialLoaded = true;
      // Ensure Material FABs exist
      try {
        if (typeof window.ensureMaterialFABs === 'function') {
          window.ensureMaterialFABs();
        }
      } catch (_) {}
      
      // Hide the top mobile "Show" button inside material content
      try {
        const showBtn = document.querySelector('#material-content #mobile-summary-toggle');
        if (showBtn) showBtn.style.display = 'none';
      } catch (_) {}
      
      // Make sure FABs are visible when landing on Material tab
      try {
        if (typeof window.updateMaterialFabVisibility === 'function') {
          window.updateMaterialFabVisibility('material');
        }
      } catch (_) {}
      
      // Try to fill the form with current data
      try {
        if (typeof window.fillMaterialFromForm === 'function') {
          window.fillMaterialFromForm();
        }
      } catch (fillErr) {
        console.warn('Error filling material form:', fillErr);
      }
    } catch (e) {
      console.error('Material load error', e);
      if (host) {
        host.textContent = `Failed to load material: ${e.message}`;
      }
    }
  }

  // Create Material floating action buttons (make and share)
  function ensureMaterialFABs() {
    const container = document.getElementById('install-popup') || document.getElementById('order-popup') || document.body;
    if (!container) return;

    // Wrapper to hold both buttons neatly
    let wrapper = document.getElementById('material-fab-wrap');
    if (!wrapper) {
      wrapper = document.createElement('div');
      wrapper.id = 'material-fab-wrap';
      wrapper.style.cssText = [
        'position:absolute','right:18px','bottom:18px','display:flex','flex-direction:column','gap:10px','z-index:2147483642'
      ].join(';');
      container.appendChild(wrapper);
    }

    if (!document.getElementById('material-make-btn')) {
      const makeBtn = document.createElement('button');
      makeBtn.id = 'material-make-btn';
      makeBtn.type = 'button';
      makeBtn.title = 'Generate/Refresh Material';
      makeBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i>';
      makeBtn.style.cssText = [
        'width:48px','height:48px','border-radius:50%','border:none','background:#007cba','color:#fff',
        'cursor:pointer','box-shadow:0 4px 12px rgba(0,0,0,0.2)','display:none','align-items:center','justify-content:center',
        'font-size:18px','transition:all .2s ease'
      ].join(';');
      makeBtn.addEventListener('click', () => {
        try {
          // Generate/refresh content only
          if (typeof window.fillMaterialFromForm === 'function') {
            window.fillMaterialFromForm();
          } else {
            const genBtn = document.querySelector('#material-content button[type="submit"], #material-content button');
            if (genBtn) genBtn.click();
          }
          if (typeof window.generateTable === 'function') {
            window.generateTable({ preventDefault: () => {} });
          }
          if (typeof window.updateDependentFields === 'function') {
            window.updateDependentFields();
          }
          // Open the summary (replace top Show button behavior)
          const cont = document.querySelector('#material-content #mobile-summary-container');
          const btn = document.querySelector('#material-content #mobile-summary-toggle');
          if (cont) cont.classList.add('show');
          if (btn) btn.textContent = 'Close';
        } catch (err) { console.warn('Material make failed', err); }
      });
      wrapper.appendChild(makeBtn);
    }

    if (!document.getElementById('material-share-btn')) {
      const shareBtn = document.createElement('button');
      shareBtn.id = 'material-share-btn';
      shareBtn.type = 'button';
      shareBtn.title = 'Copy & Share';
      shareBtn.innerHTML = '<i class="fas fa-share-from-square"></i>';
      shareBtn.style.cssText = [
        'width:48px','height:48px','border-radius:50%','border:none','background:#10b981','color:#fff',
        'cursor:pointer','box-shadow:0 4px 12px rgba(0,0,0,0.2)','display:none','align-items:center','justify-content:center',
        'font-size:18px','transition:all .2s ease'
      ].join(';');
      shareBtn.addEventListener('click', () => {
        try {
          const btn = document.querySelector('#material-content #copy-share-btn');
          if (btn) {
            btn.click();
          } else {
            alert('Share button not available yet. Generate the material first.');
          }
        } catch (err) { console.warn('Material share failed', err); }
      });
      wrapper.appendChild(shareBtn);
    }
  }

  function updateMaterialFabVisibility(activeTabName) {
    const makeBtn = document.getElementById('material-make-btn');
    const shareBtn = document.getElementById('material-share-btn');
    const isMaterial = activeTabName === 'material' || (document.getElementById('tab-material')?.style.display !== 'none');
    if (makeBtn) makeBtn.style.display = isMaterial ? 'flex' : 'none';
    if (shareBtn) shareBtn.style.display = isMaterial ? 'flex' : 'none';
  }

  // Function to populate material form fields with data from the requirement form
  function fillMaterialFromForm() {
    console.log('Filling material from form...');
    
    const getVal = (id) => {
      const element = document.getElementById(id);
      return element ? element.value || '' : '';
    };
    
    // Get values from requirement form
    const name = getVal('name');
    const idno = getVal('id');
    const location = getVal('location');
    const resolution = getVal('resolution'); // "2 MP" or "5 MP"
    const brand = getVal('brand');
    const camType = getVal('cam_type');
    const bullets = parseInt(getVal('bullets') || '0', 10) || 0;
    const dome = parseInt(getVal('dome') || '0', 10) || 0;
    const monitor = getVal('monitor');
    const rack = getVal('rack');
    const hdd = (getVal('hdd') || '').replace(/\s+/g, '');
    const type = getVal('type'); // DVR/NVR type
    
    console.log('Values from requirement form:', {
      name, idno, location, resolution, brand, camType, bullets, dome, monitor, rack, hdd, type
    });

    if (typeof window.setMaterialBrand === 'function') {
      window.setMaterialBrand(brand);
    }
    
    // Populate basic material form fields
    const nameField = document.getElementById('user-name');
    const idField = document.getElementById('user-id');
    
    if (nameField) {
      nameField.value = name;
      console.log('Set user-name to:', name);
    }
    
    if (idField) {
      idField.value = idno;
      console.log('Set user-id to:', idno);
    }
    
    // Set camera quantities and resolution
    if (bullets > 0) {
      // Find bullet camera quantity field
      const bulletQty = document.querySelector('[name="qty-0"]') || document.getElementById('resolution-bullet-qty');
      if (bulletQty) {
        bulletQty.value = bullets;
        console.log('Set bullet camera quantity to:', bullets);
      }
      
      // Set bullet camera resolution (skip if select doesn't exist)
      const bulletSelect = document.getElementById('resolution-bullet');
      if (bulletSelect && bulletSelect.options && resolution) {
        bulletSelect.value = resolution;
        console.log('Set bullet camera resolution to:', resolution);
      }
      const bulletCamTypeSelect = document.getElementById('resolution-bullet-cam-type');
      if (bulletCamTypeSelect && camType) {
        const normalizedCamType = String(camType).trim().toLowerCase();
        if (normalizedCamType.includes('hybrid')) {
          bulletCamTypeSelect.value = 'Hybrid';
        } else if (normalizedCamType.includes('full')) {
          bulletCamTypeSelect.value = 'Full colour';
        } else {
          bulletCamTypeSelect.value = 'Normal with mic';
        }
        console.log('Set bullet camera type to:', bulletCamTypeSelect.value);
      }
    }
    
    if (dome > 0) {
      // Find dome camera quantity field
      const domeQty = document.querySelector('[name="qty-1"]') || document.getElementById('resolution-dome-qty');
      if (domeQty) {
        domeQty.value = dome;
        console.log('Set dome camera quantity to:', dome);
      }
      
      // Set dome camera resolution (skip if select doesn't exist)
      const domeSelect = document.getElementById('resolution-dome');
      if (domeSelect && domeSelect.options && resolution) {
        domeSelect.value = resolution;
        console.log('Set dome camera resolution to:', domeSelect.value);
      }
      const domeCamTypeSelect = document.getElementById('resolution-dome-cam-type');
      if (domeCamTypeSelect && camType) {
        const normalizedCamType = String(camType).trim().toLowerCase();
        if (normalizedCamType.includes('hybrid')) {
          domeCamTypeSelect.value = 'Hybrid';
        } else if (normalizedCamType.includes('full')) {
          domeCamTypeSelect.value = 'Full colour';
        } else {
          domeCamTypeSelect.value = 'Normal with mic';
        }
        console.log('Set dome camera type to:', domeCamTypeSelect.value);
      }
    }
    
    // Set DVR type (skip if select doesn't exist)
    const dvrSelect = document.getElementById('type');
    if (dvrSelect && dvrSelect.options && type) {
      const totalCams = bullets + dome;
      const options = dvrSelect.options;
      
      // Find appropriate DVR based on resolution and camera count
      for (let i = 0; i < options.length; i++) {
        const option = options[i].value;
        if (option.includes(resolution)) {
          const channels = parseInt(option.split(' ')[2]) || 0;
          if (channels >= totalCams) {
            dvrSelect.selectedIndex = i;
            console.log('Set DVR type to:', option);
            break;
          }
        }
      }
      
      // Set DVR quantity
      const dvrQty = document.querySelector('[name="qty-2"]') || document.getElementById('type-qty');
      if (dvrQty) {
        dvrQty.value = 1;
        console.log('Set DVR quantity to: 1');
      }
    }
    
    // Set HDD (scope to material content to avoid matching requirement form field)
    const hddSelect = document.querySelector('#material-content #hdd') || document.getElementById('hdd');
    if (hddSelect && hdd) {
      const options = hddSelect.options;
      for (let i = 0; i < options.length; i++) {
        if (options[i].value.replace(/\s+/g, '') === hdd) {
          hddSelect.selectedIndex = i;
          console.log('Set HDD to:', options[i].value);
          break;
        }
      }
      
      // Set HDD quantity
      const hddQty = document.querySelector('#material-content [name="qty-3"]') || document.querySelector('#material-content #hdd-qty') || document.querySelector('[name="qty-3"]') || document.getElementById('hdd-qty');
      if (hddQty) {
        hddQty.value = 1;
        console.log('Set HDD quantity to: 1');
      }
    }
    
    // Set Monitor/LED TV
    const monitorSelect = document.getElementById('monitor');
    if (monitorSelect && monitor) {
      const options = monitorSelect.options;
      for (let i = 0; i < options.length; i++) {
        if (options[i].value === monitor) {
          monitorSelect.selectedIndex = i;
          console.log('Set monitor to:', monitor);
          break;
        }
      }
      
      // Set monitor quantity
      const monitorQty = document.querySelector('[name="qty-11"]') || document.getElementById('monitor-qty');
      if (monitorQty && monitor) {
        monitorQty.value = 1;
        console.log('Set monitor quantity to: 1');
      }
    }
    
    // Set Rack
    if (rack) {
      const rackQty = document.querySelector('[name*="qty-13"]') || document.getElementById('rack-qty');
      if (rackQty) {
        rackQty.value = 1;
        console.log('Set rack quantity to: 1');
      }
    }
    
    // Small delay to ensure all fields are set before triggering updates
    setTimeout(() => {
      // Trigger any dependent field updates if they exist
      try {
        if (typeof updateDependentFields === 'function') {
          console.log('Calling updateDependentFields...');
          updateDependentFields();
        }
        if (typeof generateTable === 'function') {
          console.log('Calling generateTable...');
          generateTable({ preventDefault: () => {} });
        }
      } catch (err) {
        console.warn('Error updating dependent fields:', err);
      }
    }, 100);
    
    console.log('Material form population completed');
  }

  // Export functions to global scope
  window.loadInvoiceIntoContainer = loadInvoiceIntoContainer;
  window.loadMaterialIntoContainer = loadMaterialIntoContainer;
  window.fillMaterialFromForm = fillMaterialFromForm;
  window.ensureMaterialFABs = ensureMaterialFABs;
  window.updateMaterialFabVisibility = updateMaterialFabVisibility;

})(window);
