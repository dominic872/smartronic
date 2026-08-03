<?php
    if (!isset($_COOKIE['auth_user'])) {
        // Get current URL with query parameters
        $currentUrl = $_SERVER['REQUEST_URI'];  
        // $loginUrl = 'login.php?redirect=' . urlencode($currentUrl);
        // Get full URL including protocol and host
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'smartronic.online';
        $fullUrl = $protocol . $host . $currentUrl;
        $loginUrl = $protocol . $host . '/admin_v2/login.php?redirect=' . urlencode($fullUrl);

        header("Location: $loginUrl");
        exit();
    }

    $authUser = trim((string)($_COOKIE['auth_user'] ?? ''));
    $role = strtolower(trim((string)($_COOKIE['auth_role'] ?? '')));
    $nameAssign = $_COOKIE['auth_name'] ?? '';
    $authPages = trim((string)($_COOKIE['auth_pages'] ?? ''));

    $configPath = __DIR__ . '/config.php';
    if (is_file($configPath)) {
        require_once $configPath;
    }

    if (!function_exists('loadAuthAccessForUser')) {
        function loadAuthAccessForUser(string $username): array {
            global $conn;
            if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) return ['role' => '', 'pages' => ''];
            $stmt = $conn->prepare("SELECT roll, Pages FROM users WHERE username = ? LIMIT 1");
            if (!$stmt) return ['role' => '', 'pages' => ''];
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stmt->bind_result($dbRole, $pages);
            $stmt->fetch();
            $stmt->close();
            return [
                'role' => is_string($dbRole) ? strtolower(trim($dbRole)) : '',
                'pages' => is_string($pages) ? trim($pages) : '',
            ];
        }
    }

    if ($authUser !== '') {
        $freshAuthAccess = loadAuthAccessForUser($authUser);
        if (($freshAuthAccess['role'] ?? '') !== '') {
            $role = $freshAuthAccess['role'];
            setcookie('auth_role', $role, time() + (3 * 86400), "/");
            $_COOKIE['auth_role'] = $role;
        }
        if (($freshAuthAccess['pages'] ?? '') !== '') {
            $authPages = $freshAuthAccess['pages'];
            setcookie('auth_pages', $authPages, time() + (3 * 86400), "/");
            $_COOKIE['auth_pages'] = $authPages;
        }
    }

    if (!function_exists('isElevatedRole')) {
        function isElevatedRole(string $role): bool {
            $role = strtolower(trim($role));
            return in_array($role, ['admin', 'manager'], true);
        }
    }

    if (!function_exists('isStrictAdminRole')) {
        function isStrictAdminRole(string $role): bool {
            return strtolower(trim($role)) === 'admin';
        }
    }

    if (!function_exists('getAllowedSmartPages')) {
        function getAllowedSmartPages(string $role, string $authPages): array {
            if (isElevatedRole($role)) {
                return ['install', 'quote', 'lead', 'gads_stats'];
            }
            if ($role !== 'market') {
                return [];
            }
            $pagesValue = strtolower(trim($authPages));
            if ($pagesValue === 'all') {
                return ['install', 'quote', 'lead'];
            }
            $parts = preg_split('/[\s,]+/', $pagesValue) ?: [];
            $allowed = [];
            foreach ($parts as $part) {
                $part = trim($part);
                if (in_array($part, ['install', 'quote', 'lead'], true)) {
                    $allowed[] = $part;
                }
            }
            return array_values(array_unique($allowed));
        }
    }

    if (!function_exists('canAccessSmartPage')) {
        function canAccessSmartPage(string $pageKey, string $role, string $authPages): bool {
            if ($pageKey === 'gads_stats') {
                return isElevatedRole($role);
            }
            if (isElevatedRole($role)) {
                return true;
            }
            if ($role === 'market' && strtolower(trim($authPages)) === 'all') {
                return true;
            }
            return in_array($pageKey, getAllowedSmartPages($role, $authPages), true);
        }
    }

    if (!function_exists('requireSmartPageAccess')) {
        function requireSmartPageAccess(string $pageKey, string $role, string $authPages): void {
            if (!canAccessSmartPage($pageKey, $role, $authPages)) {
                if (isset($_GET['debug_auth']) && $_GET['debug_auth'] === '1') {
                    header('Content-Type: text/plain; charset=UTF-8');
                    echo "AUTH DEBUG\n";
                    echo "user={$GLOBALS['authUser']}\n";
                    echo "role={$role}\n";
                    echo "pages={$authPages}\n";
                    echo "pageKey={$pageKey}\n";
                    echo "canAccess=no\n";
                    exit;
                }
                echo "No access";
                exit;
            }
        }
    }
?>
