<?php
declare(strict_types=1);

error_reporting(0);
ini_set('display_errors', '0');

$autoloadCandidates = [
  __DIR__ . '/../vendor/autoload.php',
  __DIR__ . '/../../vendor/autoload.php',
  __DIR__ . '/../admin_v2/vendor/autoload.php',
  __DIR__ . '/../admin/vendor/autoload.php',
];

$autoloadPath = null;
foreach ($autoloadCandidates as $candidate) {
  if (is_file($candidate)) {
    $autoloadPath = $candidate;
    break;
  }
}

if ($autoloadPath === null) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Composer autoload not found. Install DomPDF via composer and ensure vendor/autoload.php is available.';
  exit;
}

require_once $autoloadPath;

function get_param(string $key, ?string $default = null): ?string {
  $value = $_POST[$key] ?? $_GET[$key] ?? null;
  if ($value === null) return $default;
  $value = is_string($value) ? trim($value) : '';
  return $value === '' ? $default : $value;
}

function file_uri(string $path): string {
  $real = realpath($path);
  if ($real === false) return '';
  return 'file://' . $real;
}

function validate_image_path(string $path): bool {
  if (!file_exists($path)) return false;
  if (!is_readable($path)) return false;
  $imageInfo = @getimagesize($path);
  return $imageInfo !== false;
}

function get_base64_image(string $path): string {
  if (!file_exists($path) || !is_readable($path)) {
    error_log("Image file not found or not readable: $path");
    return '';
  }
  $imageData = file_get_contents($path);
  if ($imageData === false) {
    error_log("Failed to read image file: $path");
    return '';
  }
  $mimeType = mime_content_type($path);
  if ($mimeType === false) {
    error_log("Failed to detect mime type for: $path");
    $mimeType = 'image/png'; // fallback
  }
  $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
  error_log("Successfully converted image to base64: $path (" . strlen($base64) . " chars)");
  return $base64;
}

function ensure_logo_file(string $targetPath): void {
  // Check if JPG version exists first
  $jpgPath = preg_replace('/\.png$/', '.jpg', $targetPath);
  if (is_file($jpgPath)) return;
  
  // Fallback to PNG for backward compatibility
  if (is_file($targetPath)) return;
  
  $dir = dirname($jpgPath);
  if (!is_dir($dir)) @mkdir($dir, 0775, true);

  // Try to copy JPG version first
  $fallbackJpgSource = realpath(__DIR__ . '/../content/uploads/2025/01/smartronic_small_logo.jpg');
  if ($fallbackJpgSource && is_file($fallbackJpgSource)) {
    @copy($fallbackJpgSource, $jpgPath);
    if (is_file($jpgPath)) return;
  }
  
  // Fallback to PNG version
  $fallbackPngSource = realpath(__DIR__ . '/../content/uploads/2025/01/smartronic_small_logo.png');
  if ($fallbackPngSource && is_file($fallbackPngSource)) {
    @copy($fallbackPngSource, $targetPath);
    if (is_file($targetPath)) return;
  }

  $onePxPng = base64_decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO1gT1kAAAAASUVORK5CYII=',
    true
  );
  if ($onePxPng !== false) {
    @file_put_contents($targetPath, $onePxPng);
  }
}

$baseDir = __DIR__;
$assetsDir = $baseDir . '/assets';
$templatesDir = $baseDir . '/templates';
$partialsDir = $baseDir . '/partials';

ensure_logo_file($assetsDir . '/logo.jpg');

$type = strtolower((string)get_param('type', 'invoice'));
$id = (string)get_param('id', '1');
$download = (string)get_param('download', '0') === '1';
$asHtml = (string)get_param('html', '0') === '1';

// Get customer details from URL parameters
$customerName = (string)get_param('name', '');
$customerPhone = (string)get_param('phone', '');
$customerLocation = (string)get_param('location', '');
$numCameras = (string)get_param('num_cameras', '');
$message = (string)get_param('message', '');

$records = [
  '1' => [
    'name' => 'Arjun Kumar',
    'phone' => '8884831000',
    'location' => 'HSR Layout, Bengaluru',
    'amount' => 18500,
    'quote_no' => 'SMRT/QTN/2026/001',
    'date' => date('d M Y'),
    'items' => [
      ['name' => 'CP Plus 2MP Camera', 'qty' => 4, 'rate' => 2200],
      ['name' => '4CH DVR', 'qty' => 1, 'rate' => 4200],
      ['name' => '1TB HDD', 'qty' => 1, 'rate' => 3200],
      ['name' => 'Cabling & Accessories', 'qty' => 1, 'rate' => 1900],
      ['name' => 'Installation & Setup', 'qty' => 1, 'rate' => 1100],
    ],
  ],
  '2' => [
    'name' => 'Meera S',
    'phone' => '9876543210',
    'location' => 'Velachery, Chennai',
    'amount' => 24500,
    'quote_no' => 'SMRT/QTN/2026/002',
    'date' => date('d M Y', strtotime('-2 days')),
    'items' => [
      ['name' => 'Dahua 5MP Camera', 'qty' => 6, 'rate' => 2600],
      ['name' => '8CH DVR', 'qty' => 1, 'rate' => 5600],
      ['name' => '2TB HDD', 'qty' => 1, 'rate' => 4800],
      ['name' => 'Cabling & Accessories', 'qty' => 1, 'rate' => 2500],
      ['name' => 'Installation & Setup', 'qty' => 1, 'rate' => 1500],
    ],
  ],
];

$qstate_b64 = get_param('qstate', '');
if ($qstate_b64) {
    // Decoding JSON from base64 string
    $qstate_json = base64_decode($qstate_b64);
    $qstate = json_decode($qstate_json, true);
    if (is_array($qstate)) {
        $data = [
            'name' => $qstate['name'] ?? '',
            'phone' => $qstate['phone'] ?? '',
            'location' => $qstate['location'] ?? '',
            'amount' => (float)($qstate['amount'] ?? 0),
            'quote_no' => $qstate['quote_no'] ?? 'SMRT/QTN/2026/001',
            'date' => $qstate['date'] ?? date('d M Y'),
            'num_cameras' => $qstate['num_cameras'] ?? 6,
            'category' => $qstate['category'] ?? 'NVR',
            'brand' => $qstate['brand'] ?? 'CP PLUS',
            'mp' => $qstate['mp'] ?? '4MP',
            'hdd_size' => $qstate['hdd_size'] ?? '1TB',
            'items' => []
        ];
    } else {
        $data = $records[$id] ?? $records['1'];
    }
} else {
    if (!isset($records[$id])) {
      http_response_code(404);
      header('Content-Type: text/plain; charset=utf-8');
      echo 'Record not found';
      exit;
    }
    $data = $records[$id];
}

// Override customer details if provided via URL
if (!empty($customerName)) $data['name'] = $customerName;
if (!empty($customerPhone)) $data['phone'] = $customerPhone;
if (!empty($customerLocation)) $data['location'] = $customerLocation;
if (!empty($numCameras)) $data['num_cameras'] = $numCameras;
if (!empty($message)) $data['message'] = $message;
if (($brand = get_param('brand', '')) !== '') $data['brand'] = $brand;
if (($category = get_param('category', '')) !== '') $data['category'] = $category;
if (($mp = get_param('mp', '')) !== '') $data['mp'] = $mp;
if (($hddSizeParam = get_param('hdd_size', '')) !== '') $data['hdd_size'] = $hddSizeParam;

$data['amount'] = (float)($data['amount'] ?? 0);
$data['items'] = is_array($data['items'] ?? null) ? $data['items'] : [];

$templateFile = null;
$docTitle = '';
$docLabel = '';

if ($type === 'invoice') {
  $templateFile = $templatesDir . '/invoice.php';
  $docTitle = 'INVOICE';
  $docLabel = 'Invoice';
} elseif ($type === 'proforma') {
  $templateFile = $templatesDir . '/proforma.php';
  $docTitle = 'PROFORMA INVOICE';
  $docLabel = 'Proforma';
} else {
  http_response_code(400);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Invalid type. Use ?type=invoice or ?type=proforma';
  exit;
}

if (!is_file($templateFile)) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Template not found';
  exit;
}

$pdfContext = [
  'baseDir' => $baseDir,
  'assetsDir' => $assetsDir,
  'partialsDir' => $partialsDir,
  'templatesDir' => $templatesDir,
  'logoUri' => $asHtml ? '/pdf/assets/logo.jpg' : (extension_loaded('gd') ? file_uri($assetsDir . '/logo.jpg') : get_base64_image($assetsDir . '/logo.jpg')),
  'headerLogoUri' => $asHtml
    ? '/content/uploads/2025/01/smartronic-trans.jpg'
    : (extension_loaded('gd') ? file_uri(__DIR__ . '/../content/uploads/2025/01/smartronic-trans.jpg') : get_base64_image(__DIR__ . '/../content/uploads/2025/01/smartronic-trans.jpg')),
  'footerLogoUri' => $asHtml
    ? '/content/uploads/2025/01/smartronic_small_logo.jpg'
    : (extension_loaded('gd') ? file_uri(__DIR__ . '/../content/uploads/2025/01/smartronic_small_logo.jpg') : get_base64_image(__DIR__ . '/../content/uploads/2025/01/smartronic_small_logo.jpg')),
  'stylePath' => $assetsDir . '/style.css',
  'docTitle' => $docTitle,
  'docLabel' => $docLabel,
  'asHtml' => $asHtml,
];

// Debug: Log which image method is being used
if (!$asHtml) {
  $gdAvailable = extension_loaded('gd');
  error_log("GD Extension Available: " . ($gdAvailable ? 'YES' : 'NO'));
  error_log("Using image method: " . ($gdAvailable ? 'file:// URI' : 'base64 embedding'));
  error_log("Logo paths: main=" . ($assetsDir . '/logo.jpg') . ", header=" . (__DIR__ . '/../content/uploads/2025/01/smartronic-trans.jpg') . ", footer=" . (__DIR__ . '/../content/uploads/2025/01/smartronic_small_logo.jpg'));
}

// Validate image paths for PDF generation (only if GD is available)
if (!$asHtml && extension_loaded('gd')) {
  $logoPath = $assetsDir . '/logo.jpg';
  $headerLogoPath = __DIR__ . '/../content/uploads/2025/01/smartronic-trans.jpg';
  $footerLogoPath = __DIR__ . '/../content/uploads/2025/01/smartronic_small_logo.jpg';
  
  if (!validate_image_path($logoPath)) {
    error_log("Logo image not accessible: $logoPath");
  }
  if (!validate_image_path($headerLogoPath)) {
    error_log("Header logo image not accessible: $headerLogoPath");
  }
  if (!validate_image_path($footerLogoPath)) {
    error_log("Footer logo image not accessible: $footerLogoPath");
  }
}

ob_start();
$template = $templateFile;
require $template;
$html = (string)ob_get_clean();

if ($asHtml) {
  header('Content-Type: text/html; charset=utf-8');
  echo $html;
  exit;
}

$optionsClass = 'Dompdf\\Options';
$dompdfClass = 'Dompdf\\Dompdf';

if (!class_exists($optionsClass) || !class_exists($dompdfClass)) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'DomPDF is not available. Install dompdf/dompdf via composer.';
  exit;
}

$options = new $optionsClass();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('enableRemote', true);
$options->set('enableCssFloat', true);
$options->set('enableJavascript', true);
$options->set('enableHtml5Parser', true);
$options->set('tempDir', sys_get_temp_dir());
$options->set('logOutputFile', sys_get_temp_dir() . '/dompdf.log');
$options->set('fontDir', $baseDir . '/fonts');

$dompdf = new $dompdfClass($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();

$filenameSafe = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)($docLabel . '_' . ($data['quote_no'] ?? 'doc') . '.pdf'));
$dompdf->stream($filenameSafe, ['Attachment' => $download ? 1 : 0]);
exit;
