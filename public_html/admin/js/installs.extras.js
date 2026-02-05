// Extras, Invoice, and Material integration module
// Requires DOM elements provided by installs.php
(function(){
  // Global store used across modules
  window.orderDetails = window.orderDetails || {};

  function redirectToOrderItems() {
    const fields = [ 'id','name','cams','bullets','dome','hdd','monitor','type','location','time','date','owner','technician','helper','resolution','map','rack','notes' ];
    const params = new URLSearchParams();
    fields.forEach(field => {
      const input = document.getElementById(field);
      if (input && input.value) params.append(field, input.value);
      window.orderDetails[field] = input ? input.value : '';
    });
    const url = `../../admin/order_items_2.php?${params.toString()}`;
    redirectToOrderItems2(url, null);
  }
  window.redirectToOrderItems = redirectToOrderItems;

  async function loadInvoiceIntoContainer(){
    const params = new URLSearchParams();
    const fields = [ 'id','name','cams','bullets','dome','hdd','monitor','type','location','time','date','owner','technician','helper','resolution','map','rack','notes' ];
    fields.forEach(field => { const input = document.getElementById(field); if (input && input.value) params.append(field, input.value); });
    params.append('render_invoice', '1');
    const container = document.getElementById('invoice-content');
    if (container) container.innerHTML = 'Loading invoice...';
    try {
      const res = await fetch(`installs.php?${params.toString()}`, { cache: 'no-store' });
      const html = await res.text();
      if (container) container.innerHTML = html;
    } catch (e) {
      if (container) container.innerHTML = 'Failed to load invoice.';
    } finally {
      initExtrasModule();
      await loadExtrasFromDB();
      await ensureInvoicePdfSupport();
    }
  }
  window.loadInvoiceIntoContainer = loadInvoiceIntoContainer;

  function setupExtrasOverlay(){
    const card = document.querySelector('.extras-card');
    if (!card || card.dataset.overlayReady === '1') return;
    card.dataset.overlayReady = '1';
    const container = document.getElementById('install-popup') || document.getElementById('order-popup') || document.body;

    const overlay = document.createElement('div');
    overlay.id = 'extras-overlay';
    overlay.style.cssText = [ 'position:absolute','top:0','left:0','height:100%','width:33.333%','min-width:360px','max-width:520px','background:#fff','box-shadow:0 10px 30px rgba(0,0,0,0.35)','transform:translateX(-100%)','transition:transform .28s ease','z-index:2147483645','display:none','flex-direction:column' ].join(';');

    const backdrop = document.createElement('div');
    backdrop.id = 'extras-backdrop';
    backdrop.style.cssText = [ 'position:absolute','inset:0','background:rgba(0,0,0,0.35)','z-index:2147483644','display:none' ].join(';');

    card.style.margin = '0'; card.style.border = '0'; card.style.borderRadius = '0'; card.style.height = '100%'; card.style.overflow = 'auto'; card.style.boxShadow = 'none';

    const headerRow = card.firstElementChild;
    if (headerRow) {
      const closeBtn = document.createElement('button');
      closeBtn.type = 'button'; closeBtn.textContent = 'Close'; closeBtn.className = 'extras-close-btn';
      closeBtn.style.cssText = 'padding:6px 10px;border:1px solid #ddd;border-radius:8px;background:#f2f2f2;cursor:pointer;margin-left:8px;';
      closeBtn.addEventListener('click', () => window.closeExtrasOverlay());
      headerRow.appendChild(closeBtn);
    }

    overlay.appendChild(card);
    container.appendChild(backdrop);
    container.appendChild(overlay);

    window.openExtrasOverlay = function(){ 
      overlay.style.display = 'flex';
      overlay.style.transform = 'translateX(0)'; 
      backdrop.style.display = ''; 
      const btn = document.getElementById('extras-open-btn');
      if (btn) { btn.innerHTML = '123<i class="fas fa-times"></i>'; btn.style.background = '#dc3545'; btn.style.zIndex = '2147483646'; }
    };
    window.closeExtrasOverlay = function(){ 
      overlay.style.transform = 'translateX(-100%)'; 
      backdrop.style.display = 'none'; 
      setTimeout(() => { overlay.style.display = 'none'; }, 280);
      const btn = document.getElementById('extras-open-btn');
      if (btn) { btn.innerHTML = '<i class="fas fa-plus"></i>'; btn.style.background = '#28a745'; btn.style.zIndex = '2147483643'; }
    };
    window.toggleExtrasOverlay = function(){
      const isOpen = overlay.style.transform === 'translateX(0px)' && overlay.style.display === 'flex';
      if (isOpen) { window.closeExtrasOverlay(); } else { window.openExtrasOverlay(); }
    };
    backdrop.addEventListener('click', () => window.closeExtrasOverlay());
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') window.closeExtrasOverlay(); });

    if (!document.getElementById('extras-open-btn')) {
      const openBtn = document.createElement('button');
      openBtn.id = 'extras-open-btn'; openBtn.type = 'button'; openBtn.innerHTML = '<i class="fas fa-plus"></i>';
      openBtn.style.cssText = [ 'position:absolute','right:18px','bottom:18px','width:50px','height:50px','border-radius:50%','border:none','background:#28a745','color:#fff','cursor:pointer','z-index:2147483643','box-shadow:0 4px 12px rgba(0,0,0,0.2)','display:flex','align-items:center','justify-content:center','font-size:18px','transition:all 0.3s ease' ].join(';');
      openBtn.addEventListener('click', () => window.toggleExtrasOverlay());
      container.appendChild(openBtn);
    }
  }
  window.setupExtrasOverlay = setupExtrasOverlay;

  async function ensureInvoicePdfSupport(){
    function installDownloadPDF(){
      window.downloadPDF = function(){
        const host = document.getElementById('invoice-content');
        const el = (host && host.querySelector('#invoice')) || document.getElementById('invoice');
        if (!el) { alert('Invoice content not loaded'); return; }
        const id = (document.getElementById('id')||{}).value || 'invoice';
        try { html2pdf().from(el).save(`Smartronic_Invoice_${id}.pdf`); }
        catch (err) { console.error('html2pdf failed', err); alert('Failed to generate PDF'); }
      };
    }
    if (window.html2pdf) { installDownloadPDF(); return; }
    await new Promise((resolve) => {
      const s = document.createElement('script');
      s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
      s.onload = () => { installDownloadPDF(); resolve(); };
      s.onerror = () => { console.warn('Failed to load html2pdf'); resolve(); };
      document.head.appendChild(s);
    });
  }
  window.ensureInvoicePdfSupport = ensureInvoicePdfSupport;

  async function redirectToOrderItems2(url, _urlInvoice){ const popup = document.getElementById('order-popup'); if (popup) popup.style.display = 'flex'; switchTab('material'); }
  window.redirectToOrderItems2 = redirectToOrderItems2;

  async function redirecktToOrderItems(url){ const popup = document.getElementById('order-popup'); if (popup) popup.style.display = 'flex'; switchTab('requirement'); }
  window.redirecktToOrderItems = redirecktToOrderItems;

  function closeOrderPopup(){ const popup = document.getElementById('order-popup'); if (popup) popup.style.display = 'none'; }
  window.closeOrderPopup = closeOrderPopup;

  function switchTab(tabName){
    const tabs = ['requirement','material','invoice'];
    tabs.forEach(name => { const el = document.getElementById('tab-' + name); if (el) el.style.display = (name === tabName) ? 'block' : 'none'; });
    if (tabName === 'invoice') loadInvoiceIntoContainer();
    if (tabName === 'material') loadMaterialIntoContainer();
  }
  window.switchTab = switchTab;

  async function loadMaterialIntoContainer(){
    if (window.__materialLoaded) { try { fillMaterialFromForm(); } catch(_) {} return; }
    const params = new URLSearchParams();
    const fields = [ 'id','name','cams','bullets','dome','hdd','monitor','type','location','time','date','owner','technician','helper','resolution','map','rack','notes' ];
    fields.forEach(field => { const input = document.getElementById(field); if (input && input.value) params.append(field, input.value); });
    params.append('render_material', '1');
    const host = document.getElementById('material-content');
    if (host) host.textContent = 'Loading material...';
    try {
      const res = await fetch(`installs.php?${params.toString()}`, { cache: 'no-store' });
      const html = await res.text();
      if (!host) return;
      host.innerHTML = html;
      const tmp = document.createElement('div'); tmp.innerHTML = html; const scripts = Array.from(tmp.querySelectorAll('script'));
      for (const s of scripts.filter(sc => sc.src)) {
        await new Promise((resolve) => { const el = document.createElement('script'); el.src = s.src; el.onload = resolve; el.onerror = resolve; document.head.appendChild(el); });
      }
      for (const s of scripts.filter(sc => !sc.src)) { const el = document.createElement('script'); el.type = s.type || 'text/javascript'; el.text = s.textContent || ''; host.appendChild(el); }
      window.__materialLoaded = true;
      try { fillMaterialFromForm(); } catch(_) {}
    } catch (e) { if (host) host.textContent = 'Failed to load material.'; }
  }
  window.loadMaterialIntoContainer = loadMaterialIntoContainer;

  function fillMaterialFromForm(){
    const getVal = (id) => (document.getElementById(id)||{}).value || '';
    const name = getVal('name'); const idno = getVal('id'); const location = getVal('location'); const resolution = getVal('resolution');
    const bullets = parseInt(getVal('bullets')||'0',10)||0; const dome = parseInt(getVal('dome')||'0',10)||0; const monitor = getVal('monitor'); const rack = getVal('rack'); const hdd = (getVal('hdd')||'').replace(/\s+/g,'');
    const selStartsWith = (sel, text) => { if (!sel || !text) return; const t = text.trim(); for (let i=0;i<sel.options.length;i++){ const optText = (sel.options[i].text || sel.options[i].value || '').trim(); if (optText.startsWith(t)) { sel.selectedIndex = i; break; } } };
    const selectByHdd = (sel, valNoSpace) => { if (!sel || !valNoSpace) return; for (let i=0;i<sel.options.length;i++){ const optText = (sel.options[i].text || '').replace(/\s+/g,''); if (optText.toUpperCase().startsWith(valNoSpace.toUpperCase())) { sel.selectedIndex = i; break; } } };

    const uname = document.getElementById('user-name'); if (uname) uname.value = name;
    const uid = document.getElementById('user-id'); if (uid) uid.value = idno ? `${idno} | ${location}` : `${location}`;

    const bulletSel = document.getElementById('resolution-bullet'); const domeSel = document.getElementById('resolution-dome'); selStartsWith(bulletSel, resolution); selStartsWith(domeSel, resolution);
    const bulletQty = document.getElementById('resolution-bullet-qty'); const domeQty = document.getElementById('resolution-dome-qty'); if (bulletQty) bulletQty.value = bullets; if (domeQty) domeQty.value = dome;

    const monitorSel = document.getElementById('monitor'); const monitorQty = document.getElementById('monitor-qty'); selStartsWith(monitorSel, monitor); if (monitorQty) monitorQty.value = monitor ? 1 : 0;
    const rackQty = document.getElementById('rack-qty'); if (rackQty) rackQty.value = rack ? 1 : 0;
    const hddSel = document.getElementById('hdd'); selectByHdd(hddSel, hdd); const hddQty = document.getElementById('hdd-qty'); if (hddQty) hddQty.value = 1;

    try { if (typeof updateDependentFields === 'function') updateDependentFields(); } catch(_) {}
    try { if (typeof generateTable === 'function') generateTable({ preventDefault: () => {} }); } catch(_) {}
  }
  window.fillMaterialFromForm = fillMaterialFromForm;

  function getExtrasFormRefs(){
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
      tableBody: (document.getElementById('extras-table')||{}).tBodies ? document.getElementById('extras-table').tBodies[0] : null,
      totalCell: document.getElementById('extras-total-cell')
    };
  }
  window.getExtrasFormRefs = getExtrasFormRefs;

  function initExtrasModule(){
    const refs = getExtrasFormRefs();
    if (!refs || !refs.saveBtn) return;
    setupExtrasOverlay();
    const recompute = () => { const r = parseFloat(refs.cableRate?.value || '0'); const l = parseFloat(refs.cableLen?.value || '0'); if (refs.cableTotal && !isNaN(r) && !isNaN(l)) refs.cableTotal.value = (r * l).toFixed(2); };
    refs.cableRate?.addEventListener('input', recompute); refs.cableLen?.addEventListener('input', recompute);

    refs.saveBtn.onclick = async () => {
      const payload = collectExtrasPayload();
      const orderId = (document.getElementById('id')||{}).value || '';
      if (!orderId) { alert('Order ID is required to save extras'); return; }
      try {
        const res = await fetch('installs.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ extrasOnly: true, id: orderId, extras: JSON.stringify(payload) }) });
        const data = await res.json();
        if (!data.success) throw new Error(data.message||'Failed');
        renderExtrasTable(payload);
        applyExtrasToInvoiceTotals(sumExtras(payload));
        if (typeof window.closeExtrasOverlay === 'function') window.closeExtrasOverlay();
      } catch (e) { console.error('Save extras failed', e); alert('Failed to save extras'); }
    };
  }
  window.initExtrasModule = initExtrasModule;

  function collectExtrasPayload(){
    const r = getExtrasFormRefs();
    const num = v => { const n = parseFloat(v); return isNaN(n) ? 0 : n; };
    const payload = {
      cable: { rate: num(r.cableRate?.value), length: num(r.cableLen?.value), total: num(r.cableTotal?.value) },
      rack: { size: (r.rackSize?.value||'').trim(), rate: num(r.rackRate?.value) },
      monitor: { size: (r.monitorSize?.value||'').trim(), rate: num(r.monitorRate?.value) },
      router: { size: (r.routerSize?.value||'').trim(), rate: num(r.routerRate?.value) },
      custom: { label: (r.customLabel?.value||'').trim(), item: (r.customItem?.value||'').trim(), total: num(r.customTotal?.value) }
    };
    if (!payload.cable.total) payload.cable.total = +(payload.cable.rate * payload.cable.length).toFixed(2);
    return payload;
  }
  window.collectExtrasPayload = collectExtrasPayload;

  async function loadExtrasFromDB(){
    const orderId = (document.getElementById('id')||{}).value || '';
    if (!orderId) return;
    try {
      const res = await fetch(`installs.php?get_extras=1&id=${encodeURIComponent(orderId)}`, { cache: 'no-store' });
      const data = await res.json();
      let payload = null;
      if (data && data.success && typeof data.extras === 'string' && data.extras.trim()) { try { payload = JSON.parse(data.extras); } catch(_) { payload = null; } }
      if (payload) { fillExtrasForm(payload); renderExtrasTable(payload); applyExtrasToInvoiceTotals(sumExtras(payload)); }
      else { renderExtrasTable({}); applyExtrasToInvoiceTotals(0); }
    } catch(e){ console.warn('Load extras failed', e); }
  }
  window.loadExtrasFromDB = loadExtrasFromDB;

  function fillExtrasForm(p){
    const r = getExtrasFormRefs(); if (!r) return;
    if (p.cable){ if (r.cableRate) r.cableRate.value = p.cable.rate ?? ''; if (r.cableLen) r.cableLen.value = p.cable.length ?? ''; if (r.cableTotal) r.cableTotal.value = p.cable.total ?? ''; }
    if (p.rack){ if (r.rackSize) r.rackSize.value = p.rack.size ?? ''; if (r.rackRate) r.rackRate.value = p.rack.rate ?? ''; }
    if (p.monitor){ if (r.monitorSize) r.monitorSize.value = p.monitor.size ?? ''; if (r.monitorRate) r.monitorRate.value = p.monitor.rate ?? ''; }
    if (p.router){ if (r.routerSize) r.routerSize.value = p.router.size ?? ''; if (r.routerRate) r.routerRate.value = p.router.rate ?? ''; }
    if (p.custom){ if (r.customLabel) r.customLabel.value = p.custom.label ?? ''; if (r.customItem) r.customItem.value = p.custom.item ?? ''; if (r.customTotal) r.customTotal.value = p.custom.total ?? ''; }
  }
  window.fillExtrasForm = fillExtrasForm;

  function renderExtrasTable(p){
    console.log('📋 renderExtrasTable called with payload:', p);
    const r = getExtrasFormRefs();
    console.log('🎯 renderExtrasTable form refs:', r);
    if (!r.tableBody) {
      console.error('❌ No table body found in renderExtrasTable');
      return;
    }
    const rows = [];
    const fmt = n => Number(n||0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
    if (p.cable && (p.cable.total||0) > 0) {
      console.log('🔌 Adding cable row:', p.cable);
      rows.push({ item:'Cable 3+1', details:`Rate ${fmt(p.cable.rate)} x Len ${fmt(p.cable.length)}`, amount: p.cable.total });
    }
    if (p.rack && (p.rack.rate||0) > 0) {
      console.log('🗜️ Adding rack row:', p.rack);
      rows.push({ item:'Rack', details:`Size ${p.rack.size||''}`, amount: p.rack.rate });
    }
    if (p.monitor && (p.monitor.rate||0) > 0) {
      console.log('🖥️ Adding monitor row:', p.monitor);
      rows.push({ item:'Monitor', details:`Size ${p.monitor.size||''}`, amount: p.monitor.rate });
    }
    if (p.router && (p.router.rate||0) > 0) {
      console.log('🌐 Adding router row:', p.router);
      rows.push({ item:'Router', details:`Size ${p.router.size||''}`, amount: p.router.rate });
    }
    if (p.custom && (p.custom.total||0) > 0){
      console.log('⚙️ Adding custom row:', p.custom);
      const label = (p.custom.label||'Custom').trim();
      const item = (p.custom.item||'').trim();
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
    
    const total = rows.reduce((s, x) => s + (Number(x.amount)||0), 0);
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

  function sumExtras(p){ let total = 0; if (p && p.cable) total += Number(p.cable.total)||0; if (p && p.rack) total += Number(p.rack.rate)||0; if (p && p.monitor) total += Number(p.monitor.rate)||0; if (p && p.router) total += Number(p.router.rate)||0; if (p && p.custom) total += Number(p.custom.total)||0; return total; }
  window.sumExtras = sumExtras;

  function renderExtrasTableInInvoice(rows, total){
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
    const fmt = n => Number(n||0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
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

  function applyExtrasToInvoiceTotals(extrasTotal){
    const host = document.getElementById('invoice-content'); if (!host) return;
    const parseNum = (s) => { if (!s) return NaN; const m = String(s).replace(/[^0-9.]/g, ''); return parseFloat(m); };
    const fmt = (n) => isNaN(n) ? '' : n.toLocaleString('en-IN', { maximumFractionDigits: 2 });
    const candidates = ['#payable','#total_amount','#grand_total','#total','#totalAmountPayable','.total-amount','.grand-total'];
    candidates.forEach(sel => { const el = host.querySelector(sel); if (el){ if (!el.dataset.baseValue){ const parsed = parseNum(el.textContent); if (!isNaN(parsed)) el.dataset.baseValue = String(parsed); } const base = parseFloat(el.dataset.baseValue); if (!isNaN(base)) el.textContent = fmt(base + (extrasTotal||0)); } });
    const paidCandidates = ['#paid','#total_paid','#amount_paid','.total-paid'];
    paidCandidates.forEach(sel => { const el = host.querySelector(sel); if (el){ if (!el.dataset.baseValue){ const parsed = parseNum(el.textContent); if (!isNaN(parsed)) el.dataset.baseValue = String(parsed); } const base = parseFloat(el.dataset.baseValue); if (!isNaN(base)) el.textContent = fmt(base + (extrasTotal||0)); } });
  }
  window.applyExtrasToInvoiceTotals = applyExtrasToInvoiceTotals;
})();
