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
      const res = await fetch(`installs.php?${params.toString()}`, { cache: 'no-store' });
      
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
      } catch (err) {
        console.warn('Error initializing invoice modules:', err);
      }
    }
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
      const res = await fetch(`installs.php?${params.toString()}`, { cache: 'no-store' });
      
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      }
      
      const html = await res.text();
      if (!host) return;
      
      // Inject HTML
      host.innerHTML = html || 'No material content available.';

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

  // Export functions to global scope
  window.loadInvoiceIntoContainer = loadInvoiceIntoContainer;
  window.loadMaterialIntoContainer = loadMaterialIntoContainer;
  window.ensureInvoicePdfSupport = ensureInvoicePdfSupport;

})(window);