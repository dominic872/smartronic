<?php
declare(strict_types=1);

$docTitle = (string)($pdfContext['docTitle'] ?? '');
$stylePath = (string)($pdfContext['stylePath'] ?? '');
$title = $docTitle;
$asHtml = (bool)($pdfContext['asHtml'] ?? false);

function h(?string $v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function inr(float $amount): string {
  $formatted = number_format($amount, 2);
  return '₹ ' . $formatted;
}

function generateQuotationNumber(array $items): string {
  // Format: YYMMDDHH[no_of_cams][recorder_type]
  $date = new DateTime();
  $year = $date->format('y'); // 26
  $month = $date->format('m'); // 02
  $day = $date->format('d'); // 18
  $hour = $date->format('H'); // 13 (current hour)
  
  // Use num_cameras from data if available, otherwise count from items
  $cameraCount = 0;
  if (isset($data['num_cameras']) && $data['num_cameras'] !== '') {
    $cameraCount = (int)$data['num_cameras'];
  } else {
    // Fallback: Count cameras from items
    foreach ($items as $item) {
      $name = strtolower($item['name'] ?? '');
      if (strpos($name, 'camera') !== false || strpos($name, 'mp') !== false) {
        $cameraCount += (int)($item['qty'] ?? 0);
      }
    }
  }
  
  // Detect recorder type from items
  $recorderType = 'NVR'; // default
  
  foreach ($items as $item) {
    $name = strtolower($item['name'] ?? '');
    
    // Detect recorder type
    if (strpos($name, 'dvr') !== false) {
      $recorderType = 'DVR';
    } elseif (strpos($name, 'nvr') !== false) {
      $recorderType = 'NVR';
    } elseif (strpos($name, 'wifi') !== false || strpos($name, 'wi-fi') !== false) {
      $recorderType = 'WIFI';
    }
  }
  
  return $year . $month . $day . $hour . $cameraCount . ' ' . $recorderType;
}

$inlineCss = '';
if ($stylePath !== '' && is_file($stylePath)) {
  $css = file_get_contents($stylePath);
  $inlineCss = is_string($css) ? $css : '';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= h($title ?? 'Document') ?></title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            padding: 22px 82px 26px 82px;
        }

        .header {
            margin-bottom: 14px;
            padding-bottom: 8px;
        }

        .brand-table {
            width: 100%;
            border-collapse: collapse;
        }

        .brand-left {
            vertical-align: middle;
            text-align: left;
        }

        .brand-right {
            vertical-align: middle;
            text-align: center;
        }

        .brand-logo {
            height: 54px;
            margin-bottom: 6px;
        }
        .brand-logo-img {
            height: 54px;
            width: 220px;
        }

        .brand-rule-red {
            border: 0;
            border-top: 1px solid #dc2626;
            margin: 10px 0 0 0;
        }

        .title {
            font-size: 24px;
            font-weight: 800;
            color: #5d89c9;
            letter-spacing: 0.2px;
            text-transform: uppercase;
        }

        .sub-title {
            font-size: 14px;
            margin-top: 5px;
        }

        .section {
            margin-top: 15px;
        }

        .bold {
            font-weight: bold;
        }
        <?php if ($asHtml): ?>
        body {
            background: #e5e7eb;
        }
        .html-preview {
            width: 700px;
            max-width: calc(100% - 24px);
            margin: 18px auto;
            background: #ffffff;
            padding: 22px 82px 26px 82px;
            border: 1px solid #d1d5db;
        }
        <?php endif; ?>
<?php echo $inlineCss !== '' ? "\n" . $inlineCss : ''; ?>
    </style>
</head>

<body>
<?php if ($asHtml): ?><div class="html-preview"><?php endif; ?>

<div class="header">
    <table class="brand-table" cellspacing="0" cellpadding="0">
        <tr>
            <td class="brand-left" width="100%">
                <img class="brand-logo-img" src="https://www.smartronic.online/content/uploads/2025/01/smartronic-trans.jpg" alt="Smartronic" />
            </td>
        </tr>
    </table>
    <hr class="brand-rule-red" />
    <div class="title"><?= h($title ?? 'PROFORMA INVOICE') ?></div>
    <br>
    <div class="doc-meta">
        <div class=""><strong>Date:</strong> <?= h(date('d F Y')) ?></div>
        <div class=""><strong>Quotation No:</strong> <?= h(generateQuotationNumber($data['items'] ?? [])) ?></div>
    </div>
    <br><br>
<div class="doc-meta">
    To:<br><br>
    
    <strong><?= h($data['name'] ?? 'Smartronic') ?></strong><br/>
    <?= h($data['phone'] ?? '8496080849') ?><br>
    <?= h($data['location'] ?? 'Bangalore') ?>
</div>

</div>
