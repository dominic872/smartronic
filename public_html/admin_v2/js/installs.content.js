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
        if (typeof window.ensureInvoicePdfSupport === 'function') {
          await window.ensureInvoicePdfSupport();
        }

        try {
          const host = document.getElementById('invoice-content');
          if (host) {
            initInvoiceControls(host);
            await ensureHtml2PdfLoaded();
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
    }

    if (invHeader && invContent) {
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

  async function ensureHtml2PdfLoaded() {
    if (window.html2pdf) return;
    await new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
      s.onload = resolve;
      s.onerror = () => reject(new Error('Failed to load html2pdf'));
      document.head.appendChild(s);
    });
  }

  function setupDownloadPdf(scopeEl) {
    window.downloadPDF = function() {
      const el = scopeEl.querySelector('#invoice');
      if (!el || !window.html2pdf) return;
      let id = null;
      try { if (typeof window.getCurrentOrderId === 'function') id = window.getCurrentOrderId(); } catch (_) {}
      const fname = 'Smartronic_Invoice_' + (id || 'current') + '.pdf';
      window.html2pdf().from(el).save(fname);
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

  // PDF support for invoices
  async function ensureInvoicePdfSupport() {
    function installDownloadPDF() {
      window.downloadPDF = function() {
        const host = document.getElementById('invoice-content');
        const el = (host && host.querySelector('#invoice')) || document.getElementById('invoice');
        if (!el) {
          alert('Invoice content not loaded');
          return;
        }
        const id = (document.getElementById('id') || {}).value || 'invoice';
        try {
          html2pdf().from(el).save(`Smartronic_Invoice_${id}.pdf`);
        } catch (err) {
          console.error('html2pdf failed', err);
          alert('Failed to generate PDF');
        }
      };
    }
    
    if (window.html2pdf) {
      installDownloadPDF();
      return;
    }
    
    await new Promise((resolve) => {
      const s = document.createElement('script');
      s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
      s.onload = () => {
        installDownloadPDF();
        resolve();
      };
      s.onerror = () => {
        console.warn('Failed to load html2pdf');
        resolve();
      };
      document.head.appendChild(s);
    });
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
    const bullets = parseInt(getVal('bullets') || '0', 10) || 0;
    const dome = parseInt(getVal('dome') || '0', 10) || 0;
    const monitor = getVal('monitor');
    const rack = getVal('rack');
    const hdd = (getVal('hdd') || '').replace(/\s+/g, '');
    const type = getVal('type'); // DVR/NVR type
    
    console.log('Values from requirement form:', {
      name, idno, location, resolution, bullets, dome, monitor, rack, hdd, type
    });
    
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
      
      // Set bullet camera resolution
      const bulletSelect = document.getElementById('resolution-bullet');
      if (bulletSelect && resolution) {
        const options = bulletSelect.options;
        for (let i = 0; i < options.length; i++) {
          if (options[i].value.includes(resolution)) {
            bulletSelect.selectedIndex = i;
            console.log('Set bullet camera resolution to:', options[i].value);
            break;
          }
        }
      }
    }
    
    if (dome > 0) {
      // Find dome camera quantity field
      const domeQty = document.querySelector('[name="qty-1"]') || document.getElementById('resolution-dome-qty');
      if (domeQty) {
        domeQty.value = dome;
        console.log('Set dome camera quantity to:', dome);
      }
      
      // Set dome camera resolution
      const domeSelect = document.getElementById('resolution-dome');
      if (domeSelect && resolution) {
        const options = domeSelect.options;
        for (let i = 0; i < options.length; i++) {
          if (options[i].value.includes(resolution)) {
            domeSelect.selectedIndex = i;
            console.log('Set dome camera resolution to:', options[i].value);
            break;
          }
        }
      }
    }
    
    // Set DVR type
    const dvrSelect = document.getElementById('type');
    if (dvrSelect && type && resolution) {
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
  window.ensureInvoicePdfSupport = ensureInvoicePdfSupport;
  window.fillMaterialFromForm = fillMaterialFromForm;
  window.ensureMaterialFABs = ensureMaterialFABs;
  window.updateMaterialFabVisibility = updateMaterialFabVisibility;

})(window);