<?php
    if (!isset($_COOKIE['auth_user'])) {
        // Get current URL with query parameters
        $currentUrl = $_SERVER['REQUEST_URI'];  
        // $loginUrl = 'login.php?redirect=' . urlencode($currentUrl);
        // Get full URL including protocol and host
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $fullUrl = $protocol . $_SERVER['HTTP_HOST'] . $currentUrl;
        $loginUrl = '../../admin/login.php?redirect=' . urlencode($fullUrl);

        header("Location: $loginUrl");
        exit();
    }

    $username = $_COOKIE['auth_user'];
    $role = $_COOKIE['auth_role'] ?? '';
    $nameAssign = $_COOKIE['auth_name'] ?? '';
?>


