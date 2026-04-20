<?php
ob_start();
require_once __DIR__ . '/../admin_v2/auth.php';

$allowed = ['admin', 'market'];
if (!in_array($role ?? '', $allowed, true)) {
  http_response_code(403);
  echo 'No access';
  exit;
}

function json_out(array $payload, int $status = 200): void {
  if (ob_get_level() > 0) ob_clean();
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload);
}

function sanitize_phone(string $phone): string {
  $digits = preg_replace('/\D+/', '', $phone);
  if (strlen($digits) > 10) $digits = substr($digits, -10);
  return $digits;
}

function post_json(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '', true);
  return is_array($data) ? $data : [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  error_reporting(0);
  ini_set('display_errors', '0');

  $isJson = isset($_SERVER['CONTENT_TYPE']) && stripos((string)$_SERVER['CONTENT_TYPE'], 'application/json') !== false;
  $data = $isJson ? post_json() : $_POST;

  $name = trim((string)($data['name'] ?? ''));
  $phone = sanitize_phone((string)($data['phone'] ?? ''));
  $amountInr = (float)($data['amount'] ?? 0);
  $description = trim((string)($data['description'] ?? ''));

  if ($name === '' || $phone === '' || strlen($phone) !== 10 || $amountInr <= 0 || $description === '') {
    json_out(['success' => false, 'error' => 'Invalid input'], 400);
    exit;
  }

  $KEY_ID = "rzp_live_S9ixiAt25pt68C";
  $KEY_SECRET = "tIKbWpdvfgrqJ4xDhedzt3ZE";

  $amountPaise = (int)round($amountInr * 100);
  $reference_id = "ORDER_" . time();
  $url = "https://api.razorpay.com/v1/payment_links";

  $payload = [
    "amount" => $amountPaise,
    "currency" => "INR",
    "description" => $description,
    "reference_id" => $reference_id,
    "customer" => [
      "name" => $name,
      "contact" => $phone
    ],
    "notify" => [
      "sms" => true,
      "email" => false
    ],
    "reminder_enable" => true
  ];

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
  curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
  curl_setopt($ch, CURLOPT_USERPWD, $KEY_ID . ":" . $KEY_SECRET);

  $response = curl_exec($ch);
  $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($http_status === 200) {
    $result = json_decode((string)$response, true);
    $shortUrl = is_array($result) ? (string)($result["short_url"] ?? '') : '';
    if ($shortUrl === '') {
      json_out(['success' => false, 'error' => 'Payment link creation failed'], 502);
      exit;
    }
    json_out([
      'success' => true,
      "link" => $shortUrl,
      "reference_id" => $reference_id
    ]);
    exit;
  }

  json_out([
    'success' => false,
    "error" => "Failed",
    "details" => $response
  ], 502);
  exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Payment Link</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
      :root {
        --neutral-50: #f8fafc;
        --neutral-200: #e5e7eb;
        --neutral-300: #d1d5db;
        --neutral-700: #374151;
        --radius-xl: 18px;
        --transition-base: 180ms ease;
      }
      body { background: var(--neutral-50); }
      .page-header {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        padding: 16px 24px;
        position: sticky;
        top: 0;
        z-index: 200;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
      }
      .logo-section {
        display: flex;
        align-items: center;
        gap: 16px;
      }
      .logo-section h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: var(--neutral-700);
      }
      .custom-logo-link { display: inline-block; line-height: 0; }
      .custom-logo { display: block; }
      .header-right {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
      }
      .header-icon-btn {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--neutral-200);
        border-radius: 10px;
        background: #fff;
        color: var(--neutral-700);
        text-decoration: none;
        transition: background var(--transition-base), border-color var(--transition-base), transform var(--transition-base);
      }
      .header-icon-btn:hover {
        background: #fff;
        border-color: var(--neutral-300);
        transform: translateY(-1px);
      }
      .page-wrap { max-width: 860px; margin: 0 auto; padding: 18px 14px 40px; }
      .form-card {
        background: #fff;
        border: 1px solid var(--neutral-200);
        border-radius: var(--radius-xl);
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        padding: 18px;
      }
      .form-title { margin: 0 0 12px; font-weight: 700; font-size: 18px; color: #111827; }
      .result-box {
        margin-top: 14px;
        padding: 12px 12px;
        border: 1px dashed var(--neutral-300);
        border-radius: 12px;
        background: rgba(248, 250, 252, 0.6);
      }
      .result-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
      .result-link { word-break: break-all; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
      @media (max-width: 600px) {
        .page-header { padding: 12px 14px; }
        .logo-section h2 { font-size: 14px; }
      }
    </style>
  </head>
  <body>
    <div class="page-header" id="pageHeader">
      <div class="logo-section">
        <a href="/" class="custom-logo-link" rel="home" aria-current="page">
          <img id="main-logo" width="200" height="40" src="https://smartronic.online/content/uploads/2025/01/smarthome-black2.svg" class="custom-logo" alt="Smartronic" decoding="async">
        </a>
        <?php echo "<h2>Hello, $nameAssign!</h2>";?>
      </div>
      <div class="header-right">
        <a class="header-icon-btn" href="https://smartronic.online/admin_v2/smart/lead_list_enhanced.php" target="_blank" rel="noopener" aria-label="Lead list" title="Lead list">
          <i class="fa-solid fa-users"></i>
        </a>
        <a class="header-icon-btn" href="https://smartronic.online/admin_v2/smart/installs.php" target="_blank" rel="noopener" aria-label="Installs" title="Installs">
          <i class="fa-solid fa-screwdriver-wrench"></i>
        </a>
      </div>
    </div>

    <div class="page-wrap">
      <div class="form-card">
        <div class="form-title">Create Razorpay Payment Link</div>
        <form id="paymentForm" autocomplete="off">
          <div class="row" style="margin-bottom: 0;">
            <div class="input-field col s12 m6">
              <i class="fa-solid fa-user prefix"></i>
              <input id="name" name="name" type="text" required>
              <label for="name">Customer Name</label>
            </div>
            <div class="input-field col s12 m6">
              <i class="fa-solid fa-phone prefix"></i>
              <input id="phone" name="phone" type="tel" inputmode="numeric" required>
              <label for="phone">Customer Phone (10 digit)</label>
            </div>
            <div class="input-field col s12 m6">
              <i class="fa-solid fa-indian-rupee-sign prefix"></i>
              <input id="amount" name="amount" type="number" min="1" step="1" required>
              <label for="amount">Amount (INR)</label>
            </div>
            <div class="input-field col s12 m6">
              <i class="fa-solid fa-pen-to-square prefix"></i>
              <input id="description" name="description" type="text" required>
              <label for="description">Description</label>
            </div>
          </div>

          <div style="display:flex; gap: 10px; flex-wrap: wrap; align-items:center;">
            <button class="btn waves-effect waves-light" type="submit" id="submitBtn">
              Create Link <i class="fa-solid fa-arrow-right right"></i>
            </button>
            <span id="statusText" style="color:#6b7280; font-weight:600;"></span>
          </div>

          <div id="result" class="result-box" style="display:none;">
            <div><span style="color:#6b7280; font-weight:700;">Reference:</span> <span id="refOut" class="result-link"></span></div>
            <div style="margin-top: 6px;">
              <span style="color:#6b7280; font-weight:700;">Link:</span>
              <div><a id="linkOut" class="result-link" href="#" target="_blank" rel="noopener"></a></div>
            </div>
            <div class="result-actions">
              <button type="button" class="btn-flat" id="copyBtn">Copy Link</button>
              <a class="btn green" id="waBtn" href="#" target="_blank" rel="noopener">WhatsApp</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        if (window.M && M.updateTextFields) M.updateTextFields();

        const form = document.getElementById('paymentForm');
        const submitBtn = document.getElementById('submitBtn');
        const statusText = document.getElementById('statusText');
        const resultBox = document.getElementById('result');
        const refOut = document.getElementById('refOut');
        const linkOut = document.getElementById('linkOut');
        const waBtn = document.getElementById('waBtn');
        const copyBtn = document.getElementById('copyBtn');

        function setBusy(busy) {
          if (submitBtn) submitBtn.disabled = busy;
          if (statusText) statusText.textContent = busy ? 'Creating…' : '';
        }

        function normalizePhone(raw) {
          const digits = String(raw || '').replace(/\D+/g, '');
          if (digits.length > 10) return digits.slice(-10);
          return digits;
        }

        form.addEventListener('submit', async (e) => {
          e.preventDefault();
          resultBox.style.display = 'none';
          setBusy(true);
          try {
            const name = document.getElementById('name').value.trim();
            const phone = normalizePhone(document.getElementById('phone').value);
            const amount = Number(document.getElementById('amount').value);
            const description = document.getElementById('description').value.trim();

            const res = await fetch('create_payment_link.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ name, phone, amount, description })
            });

            const data = await res.json().catch(() => null);
            if (!res.ok || !data || !data.success) {
              const msg = data && (data.error || data.details) ? (data.error || data.details) : 'Failed to create link';
              if (window.M && M.toast) M.toast({ html: msg });
              else alert(msg);
              return;
            }

            refOut.textContent = data.reference_id || '';
            linkOut.textContent = data.link || '';
            linkOut.href = data.link || '#';

            const waMsg = encodeURIComponent(`Hi ${name}, please complete your payment using this link: ${data.link}`);
            waBtn.href = `https://wa.me/91${phone}?text=${waMsg}`;

            resultBox.style.display = '';
          } finally {
            setBusy(false);
          }
        });

        copyBtn.addEventListener('click', async () => {
          const link = linkOut && linkOut.href ? linkOut.href : '';
          if (!link || link === '#') return;
          try {
            await navigator.clipboard.writeText(link);
            if (window.M && M.toast) M.toast({ html: 'Copied' });
          } catch (_) {
            const ta = document.createElement('textarea');
            ta.value = link;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            ta.remove();
            if (window.M && M.toast) M.toast({ html: 'Copied' });
          }
        });
      });

      // Hide header when displayed in iframe
      if (window.self !== window.top) {
        const header = document.getElementById('pageHeader');
        if (header) {
          header.style.display = 'none';
        }
      }
    </script>
  </body>
</html>
