<?php
require_once __DIR__ . '/../auth.php';
requireSmartPageAccess('gads_stats', $role, $authPages);
require_once __DIR__ . '/lib/GoogleAdsClient.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$client = new GoogleAdsClient();
$env = $client->env();
$message = '';
$error = '';
$campaigns = [];
$accessibleCustomers = [];

try {
    if (isset($_GET['action']) && $_GET['action'] === 'clear_token') {
        $env->set('GOOGLE_ADS_REFRESH_TOKEN', '');
        $message = 'Saved refresh token cleared. Click Connect Google Ads to create a new token.';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_config') {
        $configKeys = [
            'GOOGLE_ADS_DEVELOPER_TOKEN',
            'GOOGLE_ADS_CLIENT_ID',
            'GOOGLE_ADS_CLIENT_SECRET',
            'GOOGLE_ADS_CUSTOMER_ID',
            'GOOGLE_ADS_LOGIN_CUSTOMER_ID',
            'GOOGLE_ADS_API_VERSION',
            'GOOGLE_ADS_REDIRECT_URI',
            'GOOGLE_ADS_REFRESH_TOKEN',
        ];
        foreach ($configKeys as $key) {
            $value = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
            if ($key === 'GOOGLE_ADS_REFRESH_TOKEN' && $value === '' && trim($env->get('GOOGLE_ADS_REFRESH_TOKEN')) !== '') {
                continue;
            }
            if ($key === 'GOOGLE_ADS_CUSTOMER_ID' || $key === 'GOOGLE_ADS_LOGIN_CUSTOMER_ID') {
                $value = preg_replace('/\D+/', '', $value);
            }
            if ($key === 'GOOGLE_ADS_API_VERSION' && $value === '') {
                $value = 'v22';
            }
            $env->set($key, $value);
        }
        $message = 'Google Ads config saved to .env.';
    }

    if (isset($_GET['action']) && $_GET['action'] === 'connect') {
        $missing = $env->required(['GOOGLE_ADS_CLIENT_ID', 'GOOGLE_ADS_CLIENT_SECRET']);
        if ($missing) {
            throw new RuntimeException('Missing OAuth config: ' . implode(', ', $missing));
        }
        $state = bin2hex(random_bytes(16));
        $_SESSION['google_ads_oauth_state'] = $state;
        header('Location: ' . $client->buildAuthUrl($state));
        exit;
    }

    if (isset($_GET['code'])) {
        $state = (string)($_GET['state'] ?? '');
        $expectedState = (string)($_SESSION['google_ads_oauth_state'] ?? '');
        if ($expectedState === '' || !hash_equals($expectedState, $state)) {
            throw new RuntimeException('Google OAuth state mismatch. Please try Connect again.');
        }
        unset($_SESSION['google_ads_oauth_state']);

        $tokenResult = $client->exchangeCodeForRefreshToken((string)$_GET['code']);
        if (empty($tokenResult['refresh_token'])) {
            $message = 'Google connected, but did not return a new refresh token. If this account was connected before, revoke app access in Google Account settings and connect again.';
        } else {
            $message = 'Google Ads connected. Refresh token saved securely.';
        }
    }

    if (isset($_GET['test']) || isset($_GET['code'])) {
        $campaigns = $client->listCampaigns();
        if (!$message) {
            $message = 'Connection test successful.';
        }
    }

    if (isset($_GET['accessible'])) {
        $accessibleCustomers = $client->listAccessibleCustomers();
        if (!$message) {
            $message = 'Accessible customers loaded.';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
    if (stripos($error, 'invalid_grant') !== false) {
        $error .= ' The saved refresh token is invalid or revoked. Clear the token, then click Connect Google Ads and approve access again. Also confirm the OAuth Client Secret is the full value and the Google Cloud redirect URI matches this page exactly.';
    }
}

$hasRefreshToken = trim($env->get('GOOGLE_ADS_REFRESH_TOKEN')) !== '';
$envPath = $env->path();
$envDir = dirname($envPath);
$envExists = is_file($envPath);
$envWritable = ($envExists && is_writable($envPath)) || (!$envExists && is_dir($envDir) && is_writable($envDir));
$apiVersion = trim($env->get('GOOGLE_ADS_API_VERSION', 'v22'));
$apiVersionWarning = $apiVersion === 'v24';
$redirectUri = $client->getOAuthRedirectUri();
$missingConfig = $env->required([
    'GOOGLE_ADS_DEVELOPER_TOKEN',
    'GOOGLE_ADS_CLIENT_ID',
    'GOOGLE_ADS_CLIENT_SECRET',
    'GOOGLE_ADS_CUSTOMER_ID',
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Ads | Connect</title>
  <link rel="icon" type="image/png" sizes="32x32" href="/content/uploads/2025/01/cropped-Site-Icon-32x32.png">
  <link rel="apple-touch-icon" href="/content/uploads/2025/01/cropped-Site-Icon-180x180.png">
    <style>
        body {
            margin: 0;
            background: #f4f7fb;
            color: #14213d;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
        }
        .wrap {
            max-width: 920px;
            margin: 0 auto;
            padding: 28px 16px;
        }
        .panel {
            background: #fff;
            border: 1px solid #dfe7f3;
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(20,33,61,.08);
            padding: 20px;
        }
        h1 { margin: 0 0 8px; font-size: clamp(26px, 5vw, 38px); }
        p { color: #64748b; font-weight: 650; }
        .actions { display:flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }
        .config-form {
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 12px;
            margin-top: 18px;
            padding: 14px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 14px;
        }
        .field {
            display:flex;
            flex-direction:column;
            gap:6px;
        }
        label {
            color:#64748b;
            font-size:12px;
            text-transform:uppercase;
            font-weight:900;
            letter-spacing:.04em;
        }
        input {
            height:42px;
            border:1px solid #dfe7f3;
            border-radius:10px;
            padding:0 10px;
            font:inherit;
            font-weight:650;
        }
        .btn {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            height: 44px;
            padding: 0 16px;
            border-radius: 12px;
            background: #14213d;
            color:#fff;
            text-decoration:none;
            font-weight: 850;
            border: 0;
        }
        .btn.alt { background: #0f9f7a; }
        .status {
            margin-top: 14px;
            padding: 12px;
            border-radius: 12px;
            font-weight: 750;
        }
        .ok { background:#ecfdf5; color:#047857; border:1px solid #bbf7d0; }
        .bad { background:#fff7f7; color:#b91c1c; border:1px solid #fecaca; }
        .grid {
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 10px;
            margin-top: 16px;
        }
        .item {
            background:#f8fafc;
            border:1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
        }
        .label { color:#64748b; font-size:12px; text-transform:uppercase; font-weight:900; letter-spacing:.05em; }
        .value { margin-top:4px; font-weight:900; word-break:break-word; }
        table { width:100%; border-collapse:collapse; margin-top:16px; background:#fff; }
        th, td { padding:10px; border-bottom:1px solid #e2e8f0; text-align:left; }
        th { color:#64748b; font-size:12px; text-transform:uppercase; }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="panel">
            <h1>Connect Google Ads</h1>
            <p>Authorize Google Ads once. The refresh token is stored in the backend `.env` file and used by the hourly sync.</p>

            <?php if ($message): ?><div class="status ok"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="status bad"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($missingConfig): ?>
                <div class="status bad">
                    Missing config: <?php echo htmlspecialchars(implode(', ', $missingConfig), ENT_QUOTES, 'UTF-8'); ?>.
                    Fill the config form below and press Save Config before connecting.
                </div>
            <?php endif; ?>
            <?php if (!$envWritable): ?>
                <div class="status bad">
                    The backend cannot write the .env file at <?php echo htmlspecialchars($envPath, ENT_QUOTES, 'UTF-8'); ?>.
                    Create this file manually or make the folder writable, then save config again.
                </div>
            <?php endif; ?>
            <?php if ($apiVersionWarning): ?>
                <div class="status bad">
                    API Version is set to v24. Change it to v22 and press Save Config.
                </div>
            <?php endif; ?>

            <div class="grid">
                <div class="item"><div class="label">Customer ID</div><div class="value"><?php echo htmlspecialchars($client->getCustomerId(), ENT_QUOTES, 'UTF-8'); ?></div></div>
                <div class="item"><div class="label">MCC Login ID</div><div class="value"><?php echo htmlspecialchars($client->getLoginCustomerId(), ENT_QUOTES, 'UTF-8'); ?></div></div>
                <div class="item"><div class="label">Refresh Token</div><div class="value"><?php echo $hasRefreshToken ? 'Saved' : 'Not connected'; ?></div></div>
                <div class="item"><div class="label">Env Path</div><div class="value"><?php echo htmlspecialchars($envPath, ENT_QUOTES, 'UTF-8'); ?></div></div>
                <div class="item"><div class="label">Env Status</div><div class="value"><?php echo $envExists ? 'Exists' : 'Not created'; ?> / <?php echo $envWritable ? 'Writable' : 'Not writable'; ?></div></div>
                <div class="item"><div class="label">Redirect URI</div><div class="value"><?php echo htmlspecialchars($redirectUri, ENT_QUOTES, 'UTF-8'); ?></div></div>
            </div>

            <form class="config-form" method="post" autocomplete="off">
                <input type="hidden" name="action" value="save_config">
                <?php
                $configFields = [
                    'GOOGLE_ADS_DEVELOPER_TOKEN' => 'Developer Token',
                    'GOOGLE_ADS_CLIENT_ID' => 'OAuth Client ID',
                    'GOOGLE_ADS_CLIENT_SECRET' => 'OAuth Client Secret',
                    'GOOGLE_ADS_CUSTOMER_ID' => 'Customer ID',
                    'GOOGLE_ADS_LOGIN_CUSTOMER_ID' => 'MCC Login Customer ID',
                    'GOOGLE_ADS_API_VERSION' => 'API Version',
                    'GOOGLE_ADS_REDIRECT_URI' => 'Redirect URI',
                    'GOOGLE_ADS_REFRESH_TOKEN' => 'Refresh Token',
                ];
                ?>
                <?php foreach ($configFields as $key => $label): ?>
                    <div class="field">
                        <label for="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></label>
                        <input
                            id="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                            name="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                            type="<?php echo in_array($key, ['GOOGLE_ADS_CLIENT_SECRET', 'GOOGLE_ADS_REFRESH_TOKEN'], true) ? 'password' : 'text'; ?>"
                            value="<?php echo htmlspecialchars($env->get($key, $key === 'GOOGLE_ADS_API_VERSION' ? 'v22' : ''), ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>
                <?php endforeach; ?>
                <div class="field" style="justify-content:end;">
                    <button class="btn" type="submit">Save Config</button>
                </div>
            </form>

            <div class="actions">
                <a class="btn alt" href="?action=connect">Connect Google Ads</a>
                <a class="btn" href="?test=1">Test Campaign List</a>
                <a class="btn" href="?accessible=1">Accessible Customers</a>
                <a class="btn" href="sync_google_ads.php?run=1">Run Sync Now</a>
                <?php if ($hasRefreshToken): ?><a class="btn" href="?action=clear_token">Clear Refresh Token</a><?php endif; ?>
            </div>

            <?php if ($accessibleCustomers): ?>
                <table>
                    <thead><tr><th>Accessible Customer Resource</th></tr></thead>
                    <tbody>
                    <?php foreach (($accessibleCustomers['resourceNames'] ?? []) as $resourceName): ?>
                        <tr><td><?php echo htmlspecialchars((string)$resourceName, ENT_QUOTES, 'UTF-8'); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if ($campaigns): ?>
                <table>
                    <thead><tr><th>Campaign ID</th><th>Name</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($campaigns as $row): $campaign = $row['campaign'] ?? []; ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)($campaign['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($campaign['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)($campaign['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
