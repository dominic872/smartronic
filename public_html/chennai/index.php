<?php
declare(strict_types=1);

function smartronic_chennai_prefix_path(string $url): string
{
    if ($url === '' || preg_match('#^(?:https?:)?//#i', $url)) {
        return $url;
    }
    if ($url[0] === '/' || $url[0] === '#' || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:') || str_starts_with($url, 'data:')) {
        return $url;
    }

    foreach (['content/', 'includes/', 'assets/', 'invoice/'] as $prefix) {
        if (str_starts_with($url, $prefix)) {
            return '../' . $url;
        }
    }

    return $url;
}

function smartronic_chennai_prefix_srcset(string $srcset): string
{
    $parts = array_map('trim', explode(',', $srcset));
    foreach ($parts as &$part) {
        if ($part === '') {
            continue;
        }
        $segments = preg_split('/\s+/', $part, 2);
        $segments[0] = smartronic_chennai_prefix_path($segments[0]);
        $part = implode(' ', array_filter($segments, static fn($value) => $value !== ''));
    }
    unset($part);

    return implode(', ', $parts);
}

function smartronic_chennai_force_assignee(string $assign): void
{
    $_COOKIE['assignee'] = $assign;
    $expires = time() + (60 * 60 * 24 * 30);
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    if (PHP_VERSION_ID >= 70300) {
        setcookie('assignee', $assign, [
            'expires' => $expires,
            'path' => '/',
            'secure' => $secure,
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        return;
    }

    $cookie = 'assignee=' . rawurlencode($assign) . '; Max-Age=' . (60 * 60 * 24 * 30) . '; Path=/; SameSite=Lax';
    if ($secure) {
        $cookie .= '; Secure';
    }
    header('Set-Cookie: ' . $cookie, false);
}

smartronic_chennai_force_assignee('SUR');

ob_start();
include dirname(__DIR__) . '/index.php';
$html = (string) ob_get_clean();

$html = preg_replace_callback(
    '/\b(href|src)=("|\')([^"\']+)\2/i',
    static function (array $matches): string {
        return $matches[1] . '=' . $matches[2] . smartronic_chennai_prefix_path($matches[3]) . $matches[2];
    },
    $html
);

$html = preg_replace_callback(
    '/\bsrcset=(["\'])([^"\']+)\1/i',
    static function (array $matches): string {
        return 'srcset=' . $matches[1] . smartronic_chennai_prefix_srcset($matches[2]) . $matches[1];
    },
    $html
);

$replacements = [
    '<meta property="og:url" content="https://smartronic.online" />' => '<meta property="og:url" content="https://smartronic.online/chennai/" />',
    '<link rel="canonical" href="/">' => '<link rel="canonical" href="/chennai/">',
    '<link rel="shortlink" href="/">' => '<link rel="shortlink" href="/chennai/">',
    '<a href="/" class="custom-logo-link" rel="home" aria-current="page">' => '<a href="/chennai/" class="custom-logo-link" rel="home" aria-current="page">',
    '<a href="/" class="custom-logo-link" rel="home">' => '<a href="/chennai/" class="custom-logo-link" rel="home">',
    'name="city" value="Bangalore"' => 'name="city" value="Chennai"',
    '<input type="hidden" name="key" value="9f4a73c2e9b84bdc902f1a7e5d13acbd">' => '<input type="hidden" name="key" value="9f4a73c2e9b84bdc902f1a7e5d13acbd"><input type="hidden" name="assignee" value="SUR">',
    'Serving South Bangalore with Excellence' => 'Serving Chennai with Excellence',
    'At <strong>Smartronic.online</strong>, we proudly serve all areas in South Bangalore, ensuring top-notch services and seamless connectivity. We cover prominent neighbourhoods like <strong>HSR Layout, Koramangala, Bellandur, BTM Layout, Bommanahalli, Jakkasandra, Madiwala, Sarjapur Road, Ejipura, Agara, and Venkatapura. </strong><br><br>Additionally, our services extend to <strong>Jayanagar, Indiranagar, Marathahalli, Electronic City, Domlur, Whitefield, Ulsoor, and MG Road</strong>, making us your trusted partner for innovative solutions across Bangalore.' => 'At <strong>Smartronic.online</strong>, we proudly serve key areas across Chennai, delivering reliable CCTV, smart automation, and security solutions with seamless installation support. We cover prominent neighbourhoods like <strong>T. Nagar, Anna Nagar, Adyar, Velachery, OMR, Porur, Ambattur, Tambaram, Medavakkam, Sholinganallur, and Perungudi.</strong><br><br>Additionally, our services extend to <strong>Nungambakkam, Mylapore, Chromepet, Pallikaranai, Thoraipakkam, ECR, Ashok Nagar, and Guindy</strong>, making us your trusted partner for innovative solutions across Chennai.',
    '809 A, 25th Cross, Sector 2, HSR Layout, Bangalore 560102' => 'Chennai, Tamil Nadu',
];

$html = str_replace(array_keys($replacements), array_values($replacements), $html);

echo $html;
