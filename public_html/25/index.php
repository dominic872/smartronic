<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Guest Pickup Organizer</title>

  <!-- Materialize CSS (Material design) -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet"/>
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>

  <style>
    :root{
      --brand-1: #20313b;
      --accent: #2b85d6;
      --accent-2: #d17b2f;
      --muted: #6f7a83;
      --card-bg: #ffffff;
      --bg-grad-a: #f3f7fb;
      --bg-grad-b: #eef4fb;
    }

    /* Larger base font for visibility */
    html { font-size: 16px; }
    body { font-family: "Roboto", "Helvetica", Arial, sans-serif; font-weight: 400; color:var(--brand-1);
           background: linear-gradient(180deg, var(--bg-grad-a), var(--bg-grad-b)); margin:0; min-height:100%; }

    /* Header */
    .app-header {
      display:flex;
      align-items:center;
      gap:14px;
      padding:12px 16px;
      position: sticky;
      top:0;
      z-index:40;
      background: rgba(255,255,255,0.72);
      backdrop-filter: blur(6px);
      border-bottom: 1px solid rgba(0,0,0,0.06);
    }
    .logo { height:64px; width:auto; border-radius:10px; box-shadow:0 6px 18px rgba(20,20,50,0.06); }
    .title { font-weight:700; font-size:1.35rem; margin:0; letter-spacing:-0.01em; color:var(--brand-1); }
    .subtitle { margin:0; font-size:0.95rem; color:var(--muted); font-weight:600; }

    /* container */
    .container-fluid { max-width:1100px; margin: 18px auto; padding: 0 12px; }

    /* layout */
    .grid { display:grid; grid-template-columns: 1fr; gap:16px; }
    @media(min-width:980px) { .grid { grid-template-columns: 460px 1fr; } }

    /* cards */
    .card { border-radius:12px; background:var(--card-bg); box-shadow:0 10px 28px rgba(20,20,50,0.06); overflow:hidden; }
    .form-body { padding:18px; }

    /* bigger form inputs */
    .input-field input, .input-field textarea {
      font-size:1rem;
      padding:10px 12px;
    }
    label { font-size:0.97rem; }

    .field-title { font-weight:700; font-size:1rem; margin-bottom:8px; color:var(--brand-1); }
    .form-actions { display:flex; gap:12px; align-items:center; justify-content:flex-end; margin-top:12px; flex-wrap:wrap; }

    .btn-save { min-width:130px; font-size:1rem; padding:0 16px; }

    /* Desktop table styling larger fonts */
    .table-wrap { padding:14px; overflow:auto; }
    table.striped th, table.striped td { font-size:0.98rem; padding:12px 10px; vertical-align: middle; }

    .chip-small { padding:6px 10px; border-radius:999px; background:#fff; color:var(--accent); font-weight:700; border:1px solid rgba(0,0,0,0.04); }

    /* guest cards for mobile */
    .card-list { display:flex; flex-direction:column; gap:12px; padding:12px; }
    .guest-card { padding:14px; border-radius:12px; background:#fff; display:flex; gap:12px; align-items:flex-start; box-shadow: 0 6px 18px rgba(20,20,50,0.04); }
    .guest-card .left { flex:0 0 56px; display:flex; align-items:center; justify-content:center; font-weight:700; color:#fff; border-radius:10px; height:56px; width:56px; background:var(--accent); font-size:1.05rem; }
    .guest-card .body { flex:1; }
    .guest-card .meta { color:var(--muted); font-size:0.96rem; margin-top:8px; }

    .deleted-row { opacity:0.45; filter:grayscale(.15); }

    /* responsive switches */
    @media(min-width:980px){
      .mobile-only { display:none; }
      .desktop-only { display:block; }
    }
    @media(max-width:979px){
      .mobile-only { display:block; }
      .desktop-only { display:none; }
      .logo { height:56px; }
      .app-header { padding:10px 12px; }
    }

    /* floating toggle button */
    .fab-toggle { position: fixed; right:18px; bottom:18px; z-index:60; box-shadow:0 10px 30px rgba(20,20,50,0.18); border-radius:50%; width:60px; height:60px; display:flex; align-items:center; justify-content:center; font-size:1.25rem; cursor:pointer; }

    /* subtle focus styles */
    input:focus, textarea:focus { outline: none; box-shadow: 0 4px 18px rgba(40,132,216,0.12); border-radius:6px; }

    footer { margin-top:18px; color:var(--muted); font-size:0.92rem; text-align:center; padding-bottom:28px; }
  </style>
</head>
<body>

<header class="app-header" role="banner">
  <img src="25.png" alt="logo" class="logo" />
  <div>
    <p class="title">Silver Jubilee</p>
    <p class="subtitle">BRO. J. RAJESH &amp; JIREH FAMILY</p>
  </div>

  <div style="margin-left:auto; display:flex; gap:10px; align-items:center;">
    <a class="btn-flat hide-on-med-and-down" id="refreshBtn" title="Refresh list"><i class="fa fa-sync" style="font-size:1.05rem;"></i></a>
    <!-- removed previous floating btn here: we use fixed fab-toggle instead -->
  </div>
</header>

<main class="container-fluid" role="main">
  <div class="grid">

    <!-- FORM CARD -->
    <section class="card" id="formCard" aria-labelledby="formHeading" role="region">
      <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 18px;">
        <div>
          <h5 id="formHeading" style="margin:0; font-size:1.05rem; font-weight:700;">Add / Register Guest</h5>
          <div style="margin-top:6px; color:var(--muted); font-size:0.95rem;">Arrival window: <strong>25-09-2025</strong> — <strong>30-09-2025</strong></div>
        </div>
        <a class="btn-flat hide-on-med-and-down" id="collapseBtn" title="Collapse form"><i class="fa fa-chevron-up" style="font-size:1rem;"></i></a>
      </div>

      <div class="form-body">
        <form id="guestForm" autocomplete="off">
          <input type="hidden" name="action" value="create">
          <div class="input-field">
            <input id="name" name="name" type="text" required />
            <label for="name">Guest name</label>
          </div>

          <div class="input-field">
            <input id="phone" name="phone" type="tel" required />
            <label for="phone">Phone number</label>
          </div>

          <div class="input-field" style="max-width:200px;">
            <input id="members" name="members" type="number" min="1" value="1" />
            <label for="members">Members</label>
          </div>

          <div style="margin-top:8px; display:flex; gap:14px; flex-wrap:wrap;">
            <div style="flex:1; min-width:160px;">
              <div class="field-title">Arrival</div>
              <div class="input-field">
                <input id="arrival_date" name="arrival_date" type="text" class="datepicker" placeholder="DD-MM-YYYY" />
                <label for="arrival_date">Date</label>
              </div>
              <div class="input-field">
                <input id="arrival_time" name="arrival_time" type="time" />
                <label class="active" for="arrival_time">Time</label>
              </div>
              <div class="input-field">
                <input id="arrival_place" name="arrival_place" type="text" />
                <label for="arrival_place">Pick-up place (airport/station)</label>
              </div>
            </div>

            <div style="flex:1; min-width:160px;">
              <div class="field-title">Departure</div>
              <div class="input-field">
                <input id="departure_date" name="departure_date" type="text" class="datepicker" placeholder="DD-MM-YYYY" />
                <label for="departure_date">Date</label>
              </div>
              <div class="input-field">
                <input id="departure_time" name="departure_time" type="time" />
                <label class="active" for="departure_time">Time</label>
              </div>
              <div class="input-field">
                <input id="departure_place" name="departure_place" type="text" />
                <label for="departure_place">Drop place</label>
              </div>
            </div>
          </div>

          <div class="input-field" style="margin-top:10px;">
            <textarea id="notes" name="notes" class="materialize-textarea" style="font-size:0.98rem;"></textarea>
            <label for="notes">Notes (optional)</label>
          </div>

          <div class="form-actions">
            <button class="btn waves-effect waves-light btn-save" type="submit"><i class="fa fa-save left"></i> Save</button>
            <button type="reset" class="btn-flat" style="font-size:0.95rem;">Reset</button>
          </div>
        </form>
      </div>
    </section>

    <!-- LIST CARD -->
    <section class="card" aria-labelledby="listHeading">
      <div style="padding:14px 18px; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h5 id="listHeading" style="margin:0; font-size:1.05rem; font-weight:700;">Guest List</h5>
          <div style="margin-top:6px; color:var(--muted); font-size:0.95rem;">Sorted ascending by arrival date + time. Soft-deleted entries are grayed out.</div>
        </div>
        <div class="center hide-on-small-only">
          <span class="chip-small" id="countChip">0 records</span>
        </div>
      </div>

      <!-- Desktop table -->
      <div class="table-wrap desktop-only" id="tableWrap">
        <table class="striped responsive-table" id="guestsTable" aria-live="polite">
          <thead>
            <tr>
              <th style="font-size:1rem;">Name</th>
              <th style="font-size:1rem;">Phone</th>
              <th style="font-size:1rem;">Members</th>
              <th style="font-size:1rem;">Arrival</th>
              <th style="font-size:1rem;">Departure</th>
              <th style="font-size:1rem;">Created</th>
              <th style="font-size:1rem;">Notes</th>
              <th class="center" style="font-size:1rem;">Delete</th>
            </tr>
          </thead>
          <tbody id="guestsBody"></tbody>
        </table>
      </div>

      <!-- Mobile card list -->
      <div class="card-list mobile-only" id="guestsCards" style="padding:12px;"></div>
    </section>

  </div>

  <footer>
    <small>Made for event pickup organization · Soft-delete retains historical records</small>
  </footer>
</main>

<!-- Floating toggle button -->
<div id="fabToggle" class="fab-toggle" title="Show / hide form" role="button" aria-pressed="true" aria-label="Toggle form"
     style="background:var(--accent); color:#fff;">
  <i id="fabIcon" class="fa fa-plus" aria-hidden="true"></i>
</div>

<!-- Materialize and JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<script>
  const apiUrl = 'api.php';

  document.addEventListener('DOMContentLoaded', function() {
    // Init datepickers with dd-mm-yyyy display
    const elems = document.querySelectorAll('.datepicker');
    M.Datepicker.init(elems, {
      format: 'dd-mm-yyyy',
      autoClose: true,
      minDate: new Date(2025,8,25),
      maxDate: new Date(2025,8,30),
      showClearBtn: true
    });

    // textarea autosize
    const notes = document.getElementById('notes');
    if (notes) M.textareaAutoResize(notes);

    // Form collapse toggle (desktop button)
    const formCard = document.getElementById('formCard');
    const collapseBtn = document.getElementById('collapseBtn');
    collapseBtn && collapseBtn.addEventListener('click', () => toggleForm());

    // Floating toggle button (plus/minus)
    const fab = document.getElementById('fabToggle');
    fab.addEventListener('click', () => toggleForm());

    // Setup form submit
    document.getElementById('guestForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      await createRecord();
    });

    // Refresh button
    document.getElementById('refreshBtn').addEventListener('click', loadList);

    // start visible
    formVisible = true;
    updateFab();

    // initial load
    loadList();
  });

  // --- form toggle logic ---
  let formVisible = true;
  function toggleForm() {
    formVisible = !formVisible;
    const formCard = document.getElementById('formCard');
    if (!formCard) return;
    formCard.style.display = formVisible ? 'block' : 'none';
    updateFab();
  }
  function updateFab() {
    const icon = document.getElementById('fabIcon');
    const fab = document.getElementById('fabToggle');
    if (!icon || !fab) return;
    if (formVisible) {
      icon.className = 'fa fa-minus';
      fab.setAttribute('aria-pressed','true');
      fab.style.background = 'var(--accent)';
    } else {
      icon.className = 'fa fa-plus';
      fab.setAttribute('aria-pressed','false');
      fab.style.background = 'var(--accent-2)';
    }
  }

  // ----- date helpers (convert between dd-mm-yyyy and yyyy-mm-dd) -----
  function toISODate(ddmmyyyy) {
    if (!ddmmyyyy) return '';
    const parts = ddmmyyyy.split('-');
    if (parts.length !== 3) return '';
    const [d,m,y] = parts;
    if (!d || !m || !y) return '';
    // pad
    const D = d.padStart(2,'0');
    const M = m.padStart(2,'0');
    return `${y}-${M}-${D}`;
  }

  function toDDMMYYYY(yyyymmdd) {
    if (!yyyymmdd) return '';
    const parts = yyyymmdd.split('-');
    if (parts.length !== 3) return '';
    const [y,m,d] = parts;
    if (!y || !m || !d) return '';
    return `${d}-${m}-${y}`;
  }

  // ----- create record (convert dates before sending) -----
  async function createRecord() {
    const form = document.getElementById('guestForm');
    const f = new FormData(form);

    // Convert date format dd-mm-yyyy -> yyyy-mm-dd for DB
    const arrivalUser = f.get('arrival_date');
    const departureUser = f.get('departure_date');
    if (arrivalUser) f.set('arrival_date', toISODate(arrivalUser));
    if (departureUser) f.set('departure_date', toISODate(departureUser));

    if (!f.get('name') || !f.get('phone')) {
      M.toast({html: 'Please provide name & phone', classes: 'red'});
      return;
    }

    try {
      const res = await fetch(apiUrl + '?action=create', {
        method: 'POST',
        body: f
      });
      const j = await res.json();
      if (j.ok) {
        M.toast({html: 'Saved', classes: 'green'});
        form.reset();
        // clear datepicker inputs (Materialize keeps instances)
        document.querySelectorAll('.datepicker').forEach(el => el.value = '');
        loadList();
      } else {
        M.toast({html: j.error || 'Save failed', classes: 'red'});
      }
    } catch (err) {
      console.error(err);
      M.toast({html: 'Network error', classes:'red'});
    }
  }

  // ----- load & render -----
  async function loadList() {
    try {
      const res = await fetch(apiUrl + '?action=list');
      const j = await res.json();
      if (!j.ok) { M.toast({html:'Failed to fetch list', classes:'red'}); return; }
      renderAll(j.rows || []);
    } catch (err) {
      console.error(err);
      M.toast({html:'Network error', classes:'red'});
    }
  }

  function renderAll(rows) {
    // sort ascending by arrival_date/time (nulls to end)
    rows.sort((a,b) => {
      const da = a.arrival_date || '9999-12-31';
      const db = b.arrival_date || '9999-12-31';
      if (da !== db) return da < db ? -1 : 1;
      const ta = a.arrival_time || '23:59:59';
      const tb = b.arrival_time || '23:59:59';
      return ta < tb ? -1 : (ta > tb ? 1 : 0);
    });

    renderTable(rows);
    renderCards(rows);

    const chip = document.getElementById('countChip');
    if (chip) chip.textContent = `${rows.length} record${rows.length !== 1 ? 's' : ''}`;
  }

  function renderTable(rows) {
    const tbody = document.getElementById('guestsBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    for (const r of rows) {
      const tr = document.createElement('tr');
      if (parseInt(r.deleted)) tr.classList.add('deleted-row');

      const arrival = (r.arrival_date ? toDDMMYYYY(r.arrival_date) : '-') + ' ' + (r.arrival_time ? r.arrival_time : '') +
                      (r.arrival_place ? (' / ' + escapeHtml(r.arrival_place)) : '');
      const departure = (r.departure_date ? toDDMMYYYY(r.departure_date) : '-') + ' ' + (r.departure_time ? r.departure_time : '') +
                      (r.departure_place ? (' / ' + escapeHtml(r.departure_place)) : '');

      tr.innerHTML = `
        <td>${escapeHtml(r.name)}</td>
        <td>${escapeHtml(r.phone)}</td>
        <td>${r.members || 1}</td>
        <td>${escapeHtml(arrival)}</td>
        <td>${escapeHtml(departure)}</td>
        <td>${escapeHtml(r.created_at || '')}</td>
        <td>${escapeHtml(r.notes || '')}</td>
        <td class="center">
          <a href="#" class="delete-btn" data-id="${r.id}" title="Soft delete">
            <i class="fa fa-trash" style="color:${r.deleted ? '#9e9e9e':'#e53935'}"></i>
          </a>
        </td>
      `;
      tbody.appendChild(tr);
    }

    attachDeleteHandlers();
  }

  function renderCards(rows) {
    const container = document.getElementById('guestsCards');
    if (!container) return;
    container.innerHTML = '';

    for (const r of rows) {
      const card = document.createElement('div');
      card.className = 'guest-card' + (parseInt(r.deleted) ? ' deleted-row' : '');
      const initials = (r.name || '?').split(' ').map(s => s[0]).join('').slice(0,2).toUpperCase();
      const arrival = (r.arrival_date ? toDDMMYYYY(r.arrival_date) : '-') + ' ' + (r.arrival_time ? r.arrival_time : '') + (r.arrival_place ? (' • ' + escapeHtml(r.arrival_place)) : '');
      const departure = (r.departure_date ? toDDMMYYYY(r.departure_date) : '-') + ' ' + (r.departure_time ? r.departure_time : '') + (r.departure_place ? (' • ' + escapeHtml(r.departure_place)) : '');

      card.innerHTML = `
        <div class="left">${escapeHtml(initials)}</div>
        <div class="body">
          <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
            <div style="font-weight:700; font-size:1rem;">${escapeHtml(r.name)}</div>
            <div style="display:flex; gap:8px; align-items:center;">
              <a class="btn-flat delete-btn-mobile" data-id="${r.id}" title="Soft delete"><i class="fa fa-trash" style="color:${r.deleted ? '#9e9e9e':'#e53935'}"></i></a>
            </div>
          </div>
          <div class="meta">
            <div><strong>Phone:</strong> ${escapeHtml(r.phone)} • <strong>Members:</strong> ${r.members || 1}</div>
            <div style="margin-top:8px;"><strong>Arrival:</strong> ${escapeHtml(arrival)}</div>
            <div style="margin-top:6px;"><strong>Departure:</strong> ${escapeHtml(departure)}</div>
            <div style="margin-top:8px; color:var(--muted); font-size:0.9rem;">Created: ${escapeHtml(r.created_at || '')}</div>
            <div style="background-color: #fffcd0; margin-top:8px; color:var(--muted); font-size:0.9rem;">Notes: ${escapeHtml(r.notes || '')}</div>
          </div>
        </div>
      `;
      container.appendChild(card);
    }

    // mobile delete handlers
    document.querySelectorAll('.delete-btn-mobile').forEach(btn => {
      btn.addEventListener('click', async (ev) => {
        ev.preventDefault();
        const id = btn.getAttribute('data-id');
        if (!confirm('Confirm soft-delete for this record? It will remain visible but grayed out.')) return;
        await doDelete(id);
      });
    });
  }

  function attachDeleteHandlers() {
    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', async (ev) => {
        ev.preventDefault();
        const id = btn.getAttribute('data-id');
        if (!confirm('Confirm soft-delete for this record? It will remain visible but grayed out.')) return;
        await doDelete(id);
      });
    });
  }

  async function doDelete(id) {
    try {
      const fd = new FormData();
      fd.append('id', id);
      const res = await fetch(apiUrl + '?action=delete', {
        method: 'POST',
        body: fd
      });
      const j = await res.json();
      if (j.ok) {
        M.toast({html:'Record soft-deleted', classes:'amber darken-1'});
        loadList();
      } else {
        M.toast({html: j.error || 'Delete failed', classes:'red'});
      }
    } catch (err) {
      console.error(err);
      M.toast({html:'Network error', classes:'red'});
    }
  }

  // safe HTML escape
  function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
</script>

</body>
</html>
