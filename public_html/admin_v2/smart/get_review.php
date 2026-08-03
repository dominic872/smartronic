<?php
$filePath = __DIR__ . '/review_messages.txt';
$usedPath = __DIR__ . '/review_messages_used.json';
$messages = [];
if (is_readable($filePath)) {
    $raw = file_get_contents($filePath);
    $parts = preg_split('/\R{2,}/', trim($raw));
    foreach ($parts as $p) {
        $t = trim($p);
        if ($t !== '') $messages[] = $t;
    }
}
if (empty($messages)) {
    $messages = ["Installed 4 CCTV cameras at our house in Banashankari. The technician inspected the place first and suggested good positions for the cameras. Wiring and DVR setup were done neatly and camera clarity is very good."];
}
$used = [];
if (is_readable($usedPath)) {
    $uj = @file_get_contents($usedPath);
    $ua = @json_decode($uj, true);
    if (is_array($ua)) $used = $ua;
}
$normHash = function($t) { return sha1(mb_strtolower(trim($t))); };
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['mark_used'])) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $msg = isset($data['msg']) ? (string)$data['msg'] : (isset($_POST['msg']) ? (string)$_POST['msg'] : '');
    $msg = trim($msg);
    if ($msg !== '') {
        $h = $normHash($msg);
        if (!in_array($h, $used, true)) {
            $used[] = $h;
            @file_put_contents($usedPath, json_encode($used, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}
$filtered = [];
foreach ($messages as $m) {
    if (!in_array($normHash($m), $used, true)) $filtered[] = $m;
}
$messages = $filtered;
if (!empty($messages)) shuffle($messages);
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode(['messages' => $messages], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SM Review | Get Review</title>
  <link rel="icon" type="image/png" sizes="32x32" href="/content/uploads/2025/01/cropped-Site-Icon-32x32.png">
  <link rel="apple-touch-icon" href="/content/uploads/2025/01/cropped-Site-Icon-180x180.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css">
  <style>
    :root { --bg:#ffffff; --fg:#111827; --muted:#6b7280; --neutral-200:#e5e7eb; --brand:#0ea5e9; --radius:10px; }
    * { box-sizing: border-box }
    html, body { margin:0; padding:0; background:#fff; color:var(--fg); font-family:Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif }
    .wrap { max-width:700px; margin:0 auto; padding:16px }
    .header { display:flex; align-items:center; justify-content:space-between; padding:10px 0; }
    .header h1 { margin:0; font-size:18px }
    .btn { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border:1px solid var(--neutral-200); border-radius:8px; background:#fff; color:#111; font-weight:600; cursor:pointer }
    .btn-primary { background:var(--brand); border-color:var(--brand); color:#fff }
    .grid { display:grid; gap:12px; margin-top:14px }
    .card { border:1px solid var(--neutral-200); border-radius:var(--radius); padding:12px; background:#fff; cursor:pointer }
    .card p { margin:0; font-size:14px; line-height:1.5; color:#222 }
    .hint { font-size:12px; color:var(--muted); margin-top:8px }
    .fab { position:fixed; right:16px; bottom:16px; width:56px; height:56px; border-radius:50%; background:#25D366; color:#fff; display:flex; align-items:center; justify-content:center; font-size:24px; box-shadow:0 8px 24px rgba(0,0,0,.15); border:none; cursor:pointer }
    .toast { position:fixed; left:50%; transform:translateX(-50%); bottom:88px; background:#111; color:#fff; font-size:13px; padding:8px 12px; border-radius:8px; display:none }
    .loading-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;z-index:1000}
    .loading-overlay .spinner{width:54px;height:54px;border-radius:50%;border:4px solid rgba(255,255,255,0.6);border-top-color:#0ea5e9;animation:spin .8s linear infinite}
    @keyframes spin{to{transform:rotate(360deg)}}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <h1>Google Review Messages</h1>
      <div>
        <button id="toggleBtn" class="btn" type="button"><i class="fa fa-list"></i> Show All</button>
        <button id="refreshBtn" class="btn" type="button" style="margin-left:8px"><i class="fa fa-sync"></i> Refresh</button>
      </div>
    </div>
    <div class="hint">Tap any message to copy. After copy, you’ll be taken to Google.</div>
    <div id="cards" class="grid" aria-live="polite">
      <?php
        $first = $messages[0];
        $safeFirst = htmlspecialchars($first, ENT_QUOTES, 'UTF-8');
        echo '<div class="card" data-msg="' . $safeFirst . '"><p>' . nl2br($safeFirst) . '</p></div>';
      ?>
    </div>
  </div>
  <button id="waFab" class="fab" title="Open Google"><i class="fab fa-google" aria-hidden="true"></i></button>
  <div id="toast" class="toast" role="status" aria-live="polite">Copied</div>
  <div id="loadingOverlay" class="loading-overlay" aria-hidden="true">
    <div class="spinner" aria-label="Loading"></div>
  </div>
  <script>
    const ALL_MESSAGES = <?php echo json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const cardsEl = document.getElementById('cards');
    const toggleBtn = document.getElementById('toggleBtn');
    const refreshBtn = document.getElementById('refreshBtn');
    const waFab = document.getElementById('waFab');
    const toast = document.getElementById('toast');
    const overlayEl = document.getElementById('loadingOverlay');
    let showingAll = false;

    function showToast(text) {
      if (!toast) return;
      toast.textContent = text || 'Copied';
      toast.style.display = 'block';
      setTimeout(() => { toast.style.display = 'none'; }, 1200);
    }

    function renderOne() {
      cardsEl.innerHTML = '';
      const msg = ALL_MESSAGES.length ? ALL_MESSAGES[Math.floor(Math.random() * ALL_MESSAGES.length)] : '';
      addCard(msg);
    }

    function renderAll() {
      cardsEl.innerHTML = '';
      const items = [...ALL_MESSAGES];
      for (let i = items.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [items[i], items[j]] = [items[j], items[i]];
      }
      items.forEach(addCard);
    }

    function addCard(msg) {
      const safe = String(msg || '');
      const div = document.createElement('div');
      div.className = 'card';
      div.setAttribute('data-msg', safe);
      const p = document.createElement('p');
      p.innerText = safe;
      div.appendChild(p);
      cardsEl.appendChild(div);
    }

    async function copyToClipboard(text) {
      try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          await navigator.clipboard.writeText(text);
          return true;
        }
      } catch (e) {}
      try {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        const ok = document.execCommand('copy');
        document.body.removeChild(ta);
        return ok;
      } catch (e) { return false; }
    }

    cardsEl.addEventListener('click', async (e) => {
      const card = e.target.closest('.card');
      if (!card) return;
      const msg = card.getAttribute('data-msg') || '';
      if (overlayEl) overlayEl.style.display = 'flex';
      const ok = await copyToClipboard(msg);
      showToast(ok ? 'Copied' : 'Copy failed');
      try {
        await fetch('get_reivew.php?mark_used=1', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ msg })
        });
      } catch (_) {}
      const idx = ALL_MESSAGES.indexOf(msg);
      if (idx >= 0) ALL_MESSAGES.splice(idx, 1);
      showingAll ? renderAll() : renderOne();
      setTimeout(() => { window.location.href = 'https://g.page/r/CRG2P3UgHmSpEBE/review'; }, 300);
    });

    toggleBtn.addEventListener('click', () => {
      showingAll = !showingAll;
      toggleBtn.innerHTML = showingAll ? '<i class="fa fa-list"></i> Show One' : '<i class="fa fa-list"></i> Show All';
      showingAll ? renderAll() : renderOne();
    });

    refreshBtn.addEventListener('click', async () => {
      try {
        const res = await fetch('get_reivew.php?format=json', { cache: 'no-store' });
        if (res.ok) {
          const data = await res.json();
          if (Array.isArray(data.messages)) {
            ALL_MESSAGES.length = 0;
            data.messages.forEach(m => ALL_MESSAGES.push(m));
            showingAll ? renderAll() : renderOne();
            showToast('Refreshed');
          }
        }
      } catch (_) {}
    });

    if (waFab) {
      waFab.addEventListener('click', () => {
        window.location.href = 'https://www.google.com/';
      });
    }

    (function init() {
      const params = new URLSearchParams(window.location.search);
      const show = params.get('show');
      showingAll = (show === 'all');
      toggleBtn.innerHTML = showingAll ? '<i class="fa fa-list"></i> Show One' : '<i class="fa fa-list"></i> Show All';
      showingAll ? renderAll() : renderOne();
    })();
  </script>
</body>
</html>
<?php ?>
