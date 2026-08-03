<?php
session_start();

// ===== DB CONNECTION (your provided creds) ==================================
$isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
$host = $isLocalhost ? 'localhost' : '127.0.0.1:3306';
$username = $isLocalhost ? 'root' : 'u398852039_smartronic';
$password = $isLocalhost ? 'root' : 'Chennai@40!';
$database = 'u398852039_smartronic';

$conn = @new mysqli($host, $username, $password, $database);
if ($conn->connect_error) { http_response_code(500); die('Database Connection Failed: ' . $conn->connect_error); }

// ===== STORAGE TABLE ========================================================
$conn->query("CREATE TABLE IF NOT EXISTS app_json (\n  id TINYINT PRIMARY KEY,\n  data LONGTEXT NOT NULL,\n  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// ===== AUTO-LOAD JSON FILE ==================================================
$dataFile = __DIR__ . '/data.json';
if (!file_exists($dataFile)) {
  file_put_contents($dataFile, json_encode([
    'HDD' => new stdClass(),
    'Type' => new stdClass(),
    'WIFI' => new stdClass(),
    'Wireless' => new stdClass(),
    'items' => new stdClass(), // <-- use object for items
    'additionalItems' => new stdClass() // <-- use object for additionalItems
  ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}

$seed = file_get_contents($dataFile);
if (!$seed) { die('Error: data.json is missing or unreadable.'); }

// ===== INITIALIZE DB IF EMPTY ==============================================
$r = $conn->query("SELECT COUNT(*) c FROM app_json WHERE id=1");
$has = ($r && ($row=$r->fetch_assoc()) && (int)$row['c']>0);
if (!$has) {
  $stmt = $conn->prepare("INSERT INTO app_json (id,data) VALUES (1,?)");
  $stmt->bind_param('s', $seed);
  $stmt->execute();
  $stmt->close();
} else {
  // Force reset to valid structure if missing keys
  $res = $conn->query("SELECT data FROM app_json WHERE id=1");
  $row = $res ? $res->fetch_assoc() : null;
  $current = $row ? json_decode($row['data'], true) : [];
  $changed = false;
  if (!isset($current['items']) || !is_array($current['items']) && !is_object($current['items'])) {
    $current['items'] = new stdClass();
    $changed = true;
  }
  if (!isset($current['additionalItems']) || !is_array($current['additionalItems']) && !is_object($current['additionalItems'])) {
    $current['additionalItems'] = new stdClass();
    $changed = true;
  }
  if ($changed) {
    $jsonString = json_encode($current, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $stmt = $conn->prepare("UPDATE app_json SET data=? WHERE id=1");
    $stmt->bind_param('s', $jsonString);
    $stmt->execute();
    $stmt->close();
    file_put_contents($dataFile, $jsonString);
  }
}

// ===== API ==================================================================
$action = $_GET['action'] ?? '';
if ($action === 'get') {
  header('Content-Type: application/json');
  $res = $conn->query("SELECT data,updated_at FROM app_json WHERE id=1");
  $d = $res->fetch_assoc();
  echo json_encode(['ok'=>true,'updated_at'=>$d['updated_at'],'data'=>json_decode($d['data'],true)]);
  exit;
}

if ($action === 'save' && ($_SERVER['REQUEST_METHOD']??'')==='POST') {
  $raw = file_get_contents('php://input');
  $payload = json_decode($raw,true);
  if ($payload===null) { http_response_code(400); header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'Invalid JSON']); exit; }
  $jsonString = json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  $stmt=$conn->prepare("UPDATE app_json SET data=? WHERE id=1");
  $stmt->bind_param('s',$jsonString);
  $ok=$stmt->execute();
  $stmt->close();

  // also save to local file for backup
  file_put_contents($dataFile, $jsonString);

  header('Content-Type: application/json'); echo json_encode(['ok'=>$ok]);
  exit;
}

if ($action === 'export') {
  $res=$conn->query("SELECT data FROM app_json WHERE id=1"); $d=$res->fetch_assoc();
  header('Content-Type: application/json'); header('Content-Disposition: attachment; filename="data-export.json"'); echo $d['data']; exit;
}

?>
<!-- Keep the rest of your HTML + JS table UI unchanged -->
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>SM Pricing | Table Editor</title>
  <link rel="icon" type="image/png" sizes="32x32" href="/content/uploads/2025/01/cropped-Site-Icon-32x32.png">
  <link rel="apple-touch-icon" href="/content/uploads/2025/01/cropped-Site-Icon-180x180.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      padding: 20px;
      color: #333;
    }
    .card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 4px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      padding: 24px;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .btn {
      padding: 10px 20px;
      border-radius: 5px !important;
      font-weight: 600;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .btn-primary {
      background: #333;
      color: white;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
      text-wrap-mode: nowrap;
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }
    .btn-ghost {
      background: rgba(255, 255, 255, 0.8);
      border: 1px solid rgba(0, 0, 0, 0.1);
      color: #333;
    }
    .btn-ghost:hover {
      background: rgba(255, 255, 255, 1);
      transform: translateY(-1px);
    }
    .chip {
      background: rgba(102, 126, 234, 0.1);
      color: #667eea;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 500;
    }
    .header {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 20px 24px;
      margin-bottom: 20px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    }
    .header h1 {
      margin: 0;
      font-size: 28px;
      font-weight: 700;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      color: #fff !important;
    }
    .nav {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 20px;
    }
    .tab {
      padding: 12px 20px;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.8);
      border: 1px solid rgba(0, 0, 0, 0.1);
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 500;
      color: #333;
    }
    .tab:hover {
      background: rgba(255, 255, 255, 1);
      transform: translateY(-2px);
    }
    .tab.active {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
 
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    th {
      background: #e1e1e1;
      text-align: left;
      font-weight: 600;
      color: #495057;
      padding: 16px 12px;
      border-bottom: 2px solid #dee2e6;
    }
    td {
      padding: 16px 12px;
      border-bottom: 1px solid #dee2e6;
      background: rgba(255, 255, 255, 0.5);
    }
    tr:hover td {
      background: rgba(102, 126, 234, 0.05);
    }
    input, select {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ced4da;
      border-radius: 5px;
      background: white;
      transition: all 0.3s ease;
      font-size: 14px;
      padding: 10px !important;
    }
    input:focus, select:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .section-card {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 16px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    .section-card .mt-2 {
        margin-bottom: 1em;
    }
    .section-title {
      font-size: 18px;
      font-weight: 600;
      color: #495057;
      margin-bottom: 16px;
      padding-bottom: 8px;
      border-bottom: 2px solid #667eea;
    }
    .add-brand-section {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 12px;
      padding: 20px;
      margin-top: 16px;
      border: 2px dashed #dee2e6;
      text-align: center;
      color: #6c757d;
    }
    .responsive-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }
    @media (max-width: 768px) {
      body { padding: 10px; }
      .header { flex-direction: column; align-items: stretch; }
      .header h1 { font-size: 24px; text-align: center; color: white !important; }
      .nav { justify-content: center; }
      .responsive-grid { grid-template-columns: 1fr; }
      table { font-size: 12px; }
      th, td { padding: 8px 6px; }
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-4">
    <header class="flex items-center justify-between gap-2">
      <h1 class="text-2xl white md:text-3xl font-semibold">Product & Pricing — Table Editor</h1>
      <div class="flex gap-2">
        <button id="btnExport" class="btn btn-ghost">Export</button>
        <label class="btn btn-ghost cursor-pointer">Import<input id="fileImport" type="file" accept="application/json" class="hidden"></label>
        <button id="btnSave" class="btn btn-primary">Save All</button>
      </div>
    </header>

    <div class="card flex flex-wrap items-center gap-3">
      <div class="chip">Touch to edit</div>
      <div class="chip">Add/Delete rows</div>
      <div class="chip">Mobile friendly</div>
      <span id="lastSaved" class="text-xs text-gray-500 ml-auto"></span>
    </div>

    <!-- TABS -->
    <nav class="flex flex-wrap gap-2">
      <button data-tab="HDD" class="tab btn btn-ghost">HDD</button>
      <button data-tab="DVR" class="tab btn btn-ghost">DVR</button>
      <button data-tab="NVR" class="tab btn btn-ghost">NVR</button>
      <button data-tab="WIFI" class="tab btn btn-ghost">Wi‑Fi</button>
      <button data-tab="Wireless" class="tab btn btn-ghost">Wireless</button>
      <button data-tab="items" class="tab btn btn-ghost">Items</button>
      <button data-tab="additional" class="tab btn btn-ghost">Additional</button>
    </nav>

    <section id="view" class="space-y-4"></section>

  </div>

<script>
  const $ = (s, r=document)=> r.querySelector(s);
  const $$ = (s, r=document)=> Array.from(r.querySelectorAll(s));
  let state = {}; // full JSON

  // ===== LOAD =====
  async function load() {
    const res = await fetch('?action=get');
    const json = await res.json();
    if (!json.ok) throw new Error('Load failed');
    state = json.data || {};
    
    // Ensure required sections exist
    if (!state.items || typeof state.items !== 'object') state.items = {};
    if (!state.additionalItems || typeof state.additionalItems !== 'object') state.additionalItems = {};
    
    $('#lastSaved').textContent = json.updated_at ? ('Last saved: ' + new Date(json.updated_at).toLocaleString()) : '';
    showTab('HDD');
  }

  // ===== SAVE =====
  async function saveAll() {
    // Ensure required sections exist before saving
    if (!state.items || typeof state.items !== 'object') state.items = {};
    if (!state.additionalItems || typeof state.additionalItems !== 'object') state.additionalItems = {};
    
    const res = await fetch('?action=save', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(state) });
    const js = await res.json();
    if (js.ok) { 
      toast('Saved'); 
      load(); 
    } else { 
      toast('Save failed', true); 
    }
  }

  function toast(msg, err=false){
    const t = document.createElement('div');
    t.textContent = msg; t.className = `fixed bottom-4 left-1/2 -translate-x-1/2 px-3 py-2 rounded-xl ${err?'bg-red-600':'bg-black'} text-white text-sm shadow`;
    document.body.appendChild(t); setTimeout(()=>t.remove(), 1600);
  }

  // ===== RENDERERS ==========================================================
  function showTab(name){
    $$('.tab').forEach(b=> b.classList.toggle('btn-primary', b.dataset.tab===name));
    const v = $('#view'); v.innerHTML='';
    if(name==='HDD') v.appendChild(renderHDD(state.HDD||{}));
    if(name==='DVR') v.appendChild(renderDVR(state?.Type?.DVR||{}));
    if(name==='NVR') v.appendChild(renderNVR(state?.Type?.NVR||{}));
    if(name==='WIFI') v.appendChild(renderWIFI(state.WIFI||{}));
    if(name==='Wireless') v.appendChild(renderWireless(state.Wireless||{}));
    if(name==='items') v.appendChild(renderItems(state.items||{}));
    if(name==='additional') v.appendChild(renderAdditionalItems(state.additionalItems||{}));
  }

  // --- HDD (Brand array of objects) ----------------------------------------
  function renderHDD(hdd){
    const wrap = sectionCard('HDD — Hard Disks');

    Object.entries(hdd).forEach(([brand, rows])=>{
      const card = sectionCard(brand, true);
      const table = el('table');
      table.innerHTML = `<thead><tr>
        <th>Capacity</th><th>Value (₹)</th><th>Label</th><th>Preferred</th><th></th>
      </tr></thead>`;
      const tbody = el('tbody');
      (rows||[]).forEach((row, idx)=> tbody.appendChild(hddRow(brand, idx, row)));
      table.appendChild(tbody);
      const add = primaryBtn('➕ Add Row', ()=>{
        hdd[brand] = hdd[brand]||[];
        hdd[brand].push({capacity:'', value:'', label:'', preferred:''});
        showTab('HDD');
      });
      card.appendChild(table);
      card.appendChild(el('div',{class:'mt-2'},[add]));
      wrap.appendChild(card);
    });

    // Add new brand
    const addBrandInput = el('input'); addBrandInput.placeholder = 'New brand name';
    const addBrandBtn = primaryBtn('➕ Add Brand', ()=>{
      const name = addBrandInput.value.trim(); if(!name) return;
      if(!hdd[name]) hdd[name] = [];
      addBrandInput.value=''; showTab('HDD');
    });
    wrap.appendChild(el('div',{class:'add-brand-section responsive-grid'},[
      el('div',{class:'flex gap-2 items-center'},[
        el('span',{class:'font-semibold'},['Add Brand:']),
        addBrandInput,
        addBrandBtn
      ])
    ]));

    return wrap;
  }

  function hddRow(brand, idx, row){
    const tr = el('tr');
    const capacity = inputCell(row.capacity, v=> row.capacity=v);
    const value = inputCell(row.value, v=> row.value = v);
    const label = inputCell(row.label, v=> row.label = v);
    const pref = selectCell(row.preferred||'', v=> row.preferred=v, ['', 'true']);
    const del = iconBtn('🗑', ()=>{ (state.HDD[brand]||[]).splice(idx,1); showTab('HDD'); });
    tr.append(capacity, value, label, pref, el('td',{},[del]));
    return tr;
  }

  // --- DVR ------------------------------------------------------------------
  function renderDVR(dvr){
    const wrap = sectionCard('DVR — Brands & Models');

    Object.entries(dvr).forEach(([brand, mpMap])=>{
      const brandCard = sectionCard(brand, true);

      // Wrap MP cards in responsive-grid
      const mpGrid = el('div', {class: 'responsive-grid'});
      Object.entries(mpMap||{}).forEach(([mp, cat])=>{
        const mpCard = sectionCard(mp, true);

        // Recorder (object of channel:[{value,label}])
        if (cat.Recorder) {
          const recCard = sectionCard('Recorder', true);
          Object.entries(cat.Recorder).forEach(([variant, arr])=>{
            const table = el('table');
            table.innerHTML = `<thead><tr><th>Variant</th><th>Value (₹)</th><th>Label</th><th></th></tr></thead>`;
            const tbody = el('tbody');
            (arr||[]).forEach((r, idx)=>{
              const tr = el('tr');
              tr.append(
                tdText(variant),
                inputCell(r.value, v=> r.value=v),
                inputCell(r.label, v=> r.label=v),
                el('td',{},[ iconBtn('🗑', ()=>{ arr.splice(idx,1); showTab('DVR'); }) ])
              );
              tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            const add = primaryBtn('➕ Add Row', ()=>{ arr.push({value:'',label:''}); showTab('DVR'); });
            recCard.append(table, el('div',{class:'mt-2'},[add]));
            mpCard.appendChild(recCard);
          });
        }

        // Camera (array of {value,label})
        if (cat.Camera) {
          const camCard = sectionCard('Camera', true);
          const table = el('table');
          table.innerHTML = `<thead><tr><th>Value (₹)</th><th>Label</th><th></th></thead>`;
          const tbody = el('tbody');
          (cat.Camera||[]).forEach((r, idx)=>{
            const tr = el('tr');
            tr.append(
              inputCell(r.value, v=> r.value=v),
              inputCell(r.label, v=> r.label=v),
              el('td',{},[ iconBtn('🗑', ()=>{ cat.Camera.splice(idx,1); showTab('DVR'); }) ])
            );
            tbody.appendChild(tr);
          });
          table.appendChild(tbody);
          const add = primaryBtn('➕ Add Row', ()=>{ cat.Camera.push({value:'',label:''}); showTab('DVR'); });
          camCard.append(table, el('div',{class:'mt-2'},[add]));
          mpCard.appendChild(camCard);
        }

        mpGrid.appendChild(mpCard);
      });

      brandCard.appendChild(mpGrid);

      // Add a new MP under brand
      const newMpName = el('input'); newMpName.placeholder = 'Add MP (e.g. 2MP, 5MP)';
      const addMpBtn = primaryBtn('➕ Add MP', ()=>{
        const name = newMpName.value.trim(); if(!name) return;
        state.Type.DVR[brand] = state.Type.DVR[brand] || {};
        if (!state.Type.DVR[brand][name]) state.Type.DVR[brand][name] = { Recorder:{}, Camera:[] };
        newMpName.value=''; showTab('DVR');
      });
      brandCard.appendChild(el('div',{class:'section-card'},[
        el('div',{class:'section-title'},['Add MP']),
        el('div',{class:'flex gap-2 items-center'},[newMpName, addMpBtn])
      ]));

      wrap.appendChild(brandCard);
    });

    // Add brand
    const addBrandInput = el('input'); addBrandInput.placeholder = 'New brand (e.g. HIKVISION)';
    const addBrandBtn = primaryBtn('➕ Add Brand', ()=>{
      const b = addBrandInput.value.trim(); if(!b) return;
      state.Type = state.Type || {}; state.Type.DVR = state.Type.DVR || {};
      if (!state.Type.DVR[b]) state.Type.DVR[b] = {};
      addBrandInput.value=''; showTab('DVR');
    });
    wrap.appendChild(el('div',{class:'add-brand-section responsive-grid'},[
      el('div',{class:'flex gap-2 items-center'},[
        el('span',{class:'font-semibold'},['Add DVR Brand:']),
        addBrandInput,
        addBrandBtn
      ])
    ]));

    return wrap;
  }

  // --- NVR (similar to DVR but structure differs slightly) ------------------
  function renderNVR(nvr){
    const wrap = sectionCard('NVR — Brands & Models');
    Object.entries(nvr).forEach(([brand, cat])=>{
      const brandCard = sectionCard(brand, true);

      if (cat.Recorder){
        const recCard = sectionCard('Recorder', true);
        Object.entries(cat.Recorder).forEach(([variant, arr])=>{
          const table = el('table');
          table.innerHTML = `<thead><tr><th>Variant</th><th>Value (₹)</th><th>Label</th><th></th></tr></thead>`;
          const tbody = el('tbody');
          (arr||[]).forEach((r, idx)=>{
            const tr = el('tr');
            tr.append(tdText(variant), inputCell(r.value, v=> r.value=v), inputCell(r.label, v=> r.label=v), el('td',{},[iconBtn('🗑',()=>{arr.splice(idx,1); showTab('NVR');})]));
            tbody.appendChild(tr);
          });
          table.appendChild(tbody);
          const add = primaryBtn('➕ Add Row', ()=>{ arr.push({value:'',label:''}); showTab('NVR'); });
          recCard.append(table, el('div',{class:'mt-2'},[add]));
          brandCard.appendChild(recCard);
        });
      }

      if (cat.Camera){
        const camCard = sectionCard('Camera', true);
        // cat.Camera may be {"2MP":[...],"4MP":[...]}
        const mpGrid = el('div', {class: 'responsive-grid'});
        Object.entries(cat.Camera).forEach(([mp, arr])=>{
          const mpCard = sectionCard(mp, true);
          const table = el('table');
          table.innerHTML = `<thead><tr><th>Value (₹)</th><th>Label</th><th></th></tr></thead>`;
          const tbody = el('tbody');
          (arr||[]).forEach((r, idx)=>{
            const tr = el('tr');
            tr.append(inputCell(r.value, v=> r.value=v), inputCell(r.label, v=> r.label=v), el('td',{},[iconBtn('🗑',()=>{arr.splice(idx,1); showTab('NVR');})]));
            tbody.appendChild(tr);
          });
          table.appendChild(tbody);
          const add = primaryBtn('➕ Add Row', ()=>{ arr.push({value:'',label:''}); showTab('NVR'); });
          mpCard.append(table, el('div',{class:'mt-2'},[add]));
          mpGrid.appendChild(mpCard);
        });
        camCard.appendChild(mpGrid);
        brandCard.appendChild(camCard);
      }

      wrap.appendChild(brandCard);
    });

    // add brand
    const addBrandInput = el('input'); addBrandInput.placeholder='New brand';
    const addBrandBtn = primaryBtn('➕ Add Brand', ()=>{
      const b = addBrandInput.value.trim(); if(!b) return;
      if (!state.Type) state.Type={}; if(!state.Type.NVR) state.Type.NVR={};
      if (!state.Type.NVR[b]) state.Type.NVR[b] = { Recorder:{}, Camera:{} };
      addBrandInput.value=''; showTab('NVR');
    });
    wrap.appendChild(el('div',{class:'add-brand-section responsive-grid'},[
      el('div',{class:'flex gap-2 items-center'},[
        el('span',{class:'font-semibold'},['Add NVR Brand:']),
        addBrandInput,
        addBrandBtn
      ])
    ]));

    return wrap;
  }

  // --- WIFI -----------------------------------------------------------------
  function renderWIFI(wifi){
    const wrap = sectionCard('Wi‑Fi Cameras');

    // Camera buckets: 2MP/3MP/4MP/5MP with sub-brands arrays
    if (wifi.Camera){
      Object.entries(wifi.Camera).forEach(([mp, brands])=>{
        const mpCard = sectionCard(mp, true);
        Object.entries(brands||{}).forEach(([brand, arr])=>{
          const brandCard = sectionCard(brand, true);
          const table = el('table');
          table.innerHTML = `<thead><tr><th>Value (₹)</th><th>Label</th><th></th></tr></thead>`;
          const tbody = el('tbody');
          (arr||[]).forEach((r, idx)=>{
            const tr = el('tr');
            tr.append(inputCell(r.value, v=> r.value=v), inputCell(r.label, v=> r.label=v), el('td',{},[iconBtn('🗑',()=>{arr.splice(idx,1); showTab('WIFI');})]));
            tbody.appendChild(tr);
          });
          table.appendChild(tbody);
          const add = primaryBtn('➕ Add Row', ()=>{ arr.push({value:'',label:''}); showTab('WIFI'); });
          brandCard.append(table, el('div',{class:'mt-2'},[add]));
          mpCard.appendChild(brandCard);
        });
        wrap.appendChild(mpCard);
      });
    }

    return wrap;
  }

  // --- Wireless -------------------------------------------------------------
  function renderWireless(w){
    const wrap = sectionCard('Wireless Kits');
    Object.entries(w||{}).forEach(([brand, cats])=>{
      const brandCard = sectionCard(brand, true);
      if (cats.NVR){
        const nvrCard = sectionCard('NVR', true);
        if (cats.NVR.Recorder){
          const table = el('table');
          table.innerHTML = `<thead><tr><th>Type</th><th>Value (₹)</th><th>Label</th><th></th></tr></thead>`;
          const tbody = el('tbody');
          (cats.NVR.Recorder||[]).forEach((r, idx)=>{
            const tr = el('tr');
            tr.append(tdText('Recorder'), inputCell(r.value, v=> r.value=v), inputCell(r.label, v=> r.label=v), el('td',{},[iconBtn('🗑',()=>{cats.NVR.Recorder.splice(idx,1); showTab('Wireless');})]));
            tbody.appendChild(tr);
          });
          table.appendChild(tbody);
          nvrCard.appendChild(table);
        }
        if (cats.NVR.Camera){
          const table2 = el('table');
          table2.innerHTML = `<thead><tr><th>Type</th><th>Value (₹)</th><th>Label</th><th></th></tr></thead>`;
          const tbody2 = el('tbody');
          (cats.NVR.Camera||[]).forEach((r, idx)=>{
            const tr = el('tr');
            tr.append(tdText('Camera'), inputCell(r.value, v=> r.value=v), inputCell(r.label, v=> r.label=v), el('td',{},[iconBtn('🗑',()=>{cats.NVR.Camera.splice(idx,1); showTab('Wireless');})]));
            tbody2.appendChild(tr);
          });
          table2.appendChild(tbody2);
          nvrCard.appendChild(table2);
        }
        brandCard.appendChild(nvrCard);
      }
      wrap.appendChild(brandCard);
    });
    return wrap;
  }

  // --- Items (flat object of {name:{value,label}}) --------------------------
  function renderItems(items){
    const wrap = sectionCard('Other Items');
    const table = el('table');
    table.innerHTML = `<thead><tr><th>Item</th><th>Value (₹)</th><th>Label</th><th></th></tr></thead>`;
    const tbody = el('tbody');
    Object.entries(items||{}).forEach(([name, obj])=>{
      const tr = el('tr');
      tr.append(
        inputCell(name, v=>{ if (v!==name){ items[v]=obj; delete items[name]; showTab('items'); } }),
        inputCell(obj.value||'', v=> obj.value=v),
        inputCell(obj.label||'', v=> obj.label=v),
        el('td',{},[ iconBtn('🗑', ()=>{ delete items[name]; showTab('items'); }) ])
      );
      tbody.appendChild(tr);
    });
    table.appendChild(tbody);

    // add row
    const addInput = el('input'); addInput.placeholder = 'Item name';
    const addBtn = primaryBtn('➕ Add Item', ()=>{
      let key = addInput.value.trim();
      if(!key) return;
      // Initialize state.items if it doesn't exist
      if (!state.items) state.items = {};
      let i=1;
      while(state.items[key]){ key = addInput.value.trim() + ' ' + (++i); }
      state.items[key] = {value:'', label:''};
      addInput.value=''; 
      // Re-render the current tab to show the new item
      showTab('items');
    });

    wrap.appendChild(el('div',{class:'add-brand-section responsive-grid'},[
      el('div',{class:'flex gap-2 items-center'},[
        el('span',{class:'font-semibold'},['Add Item:']),
        addInput,
        addBtn
      ])
    ]));
    return wrap;
  }

  // --- Additional Items (flat object of {name:{value}}) ---------------------
  function renderAdditionalItems(items){
    const wrap = sectionCard('Additional Items');
    const table = el('table');
    table.innerHTML = `<thead><tr><th>Item</th><th>Value</th><th></th></tr></thead>`;
    const tbody = el('tbody');
    Object.entries(items||{}).forEach(([name, obj])=>{
      const tr = el('tr');
      tr.append(
        tdText(name),
        inputCell(obj.value||'', v=> obj.value=v),
        el('td',{},[ iconBtn('🗑', ()=>{ delete items[name]; showTab('additional'); }) ])
      );
      tbody.appendChild(tr);
    });
    table.appendChild(tbody);

    // add row
    const addInput = el('input'); addInput.placeholder = 'Item name';
    const addBtn = primaryBtn('➕ Add Item', ()=>{
      let key = addInput.value.trim();
      if(!key) return;
      // Initialize state.additionalItems if it doesn't exist
      if (!state.additionalItems) state.additionalItems = {};
      let i=1;
      while(state.additionalItems[key]){ key = addInput.value.trim() + ' ' + (++i); }
      state.additionalItems[key] = {value:''};
      addInput.value=''; 
      // Re-render the current tab to show the new item
      showTab('additional');
    });

    wrap.appendChild(el('div',{class:'add-brand-section responsive-grid'},[
      el('div',{class:'flex gap-2 items-center'},[
        el('span',{class:'font-semibold'},['Add Additional Item:']),
        addInput,
        addBtn
      ])
    ]));
    return wrap;
  }

  // ===== small helpers ======================================================
  function sectionCard(title, inner=false){
    const d = el('div', {class: inner? 'section-card' : 'card space-y-6'});
    d.append(el('div',{class:'section-title'},[title]));
    return d;
  }
  function el(tag, attrs={}, children=[]) {
    const e = document.createElement(tag);
    Object.entries(attrs||{}).forEach(([k,v])=>{ if(k==='class') e.className=v; else e.setAttribute(k,v); });
    (Array.isArray(children)?children:[children]).forEach(c=>{ if(c==null) return; if(typeof c==='string') e.appendChild(document.createTextNode(c)); else e.appendChild(c); });
    return e;
  }
  function primaryBtn(txt, on){ const b = el('button',{class:'btn btn-primary'},[txt]); b.addEventListener('click', on); return b; }
  function iconBtn(txt, on){ const b = el('button',{class:'btn btn-ghost'},[txt]); b.addEventListener('click', on); return b; }
  function tdText(txt){ return el('td',{},[txt]); }
  function inputCell(val, on){ const td = el('td'); const i = el('input'); i.value = (val ?? ''); i.addEventListener('input', ()=> on(i.value)); td.appendChild(i); return td; }
  function selectCell(val, on, opts){ const td = el('td'); const s = el('select'); opts.forEach(o=>{ const op = el('option'); op.value=o; op.textContent = (o===''?'—':o); if (o===val) op.selected=true; s.appendChild(op); }); s.addEventListener('change', ()=> on(s.value)); td.appendChild(s); return td; }

  // ===== events =====
  $$('.tab').forEach(b=> b.addEventListener('click', ()=> showTab(b.dataset.tab)));
  $('#btnSave').addEventListener('click', saveAll);
  $('#btnExport').addEventListener('click', ()=> window.location='?action=export');
  $('#fileImport').addEventListener('change', async (e)=>{
    const f = e.target.files[0]; if(!f) return; const text = await f.text();
    try { state = JSON.parse(text); toast('Imported (not saved)'); showTab('HDD'); } catch(e){ toast('Invalid JSON', true); }
  });

  load().catch(e=> toast('Init failed: '+e.message, true));
</script>
</body>
</html>
