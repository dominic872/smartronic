<?php
declare(strict_types=1);

$partialsDir = (string)($pdfContext['partialsDir'] ?? (__DIR__ . '/../partials'));

require $partialsDir . '/header.php';
?>

<?php
$message = (string)($data['message'] ?? '');
?>

<div id="whatsapp-preview-content">
  <?php
  $formatMessage = static function (string $text): string {
    // Remove common emoji / pictograph characters
    $text = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2300}-\x{23FF}\x{2B00}-\x{2BFF}]/u', '', $text) ?? $text;
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    $lines = explode("\n", $text);
    $html = '';
    $inList = false;

    foreach ($lines as $line) {
      $raw = trim($line);
      // Strip common invisible characters that can appear in WhatsApp text and affect prefix detection
      $raw = preg_replace('/^[\x{200B}-\x{200F}\x{FEFF}]+/u', '', $raw) ?? $raw;
      $raw = preg_replace('/[\x{200B}-\x{200F}\x{FEFF}]+$/u', '', $raw) ?? $raw;

      if ($raw === '') {
        if ($inList) {
          $html .= "</ul>";
          $inList = false;
        }
        $html .= '<br>';
        continue;
      }

      // Treat a trailing single '*' as a "headline" marker from WhatsApp formatting
      // Example: "... ₹28,129*" should become a bold headline line.
      $isHeadlineStar = false;
      $headlineText = '';
      if (str_ends_with($raw, '*') && substr_count($raw, '*') === 1) {
        $isHeadlineStar = true;
        $headlineText = rtrim(substr($raw, 0, -1));
      }

      // Normalize common bullet characters/prefixes to list items
      $isBullet = false;
      $bulletText = $raw;
      if (!$isHeadlineStar) {
        if (preg_match('/^-\s*(.+)$/u', $raw, $m)) {
          $isBullet = true;
          $bulletText = $m[1];
        }
      }

      // Convert WhatsApp *bold* to HTML strong (simple, whole-line or inline)
      $toHtml = static function (string $s): string {
        $s = h($s);
        return preg_replace('/\*([^*]+)\*/', '<strong>$1</strong>', $s) ?? $s;
      };

      if ($isBullet) {
        if (!$inList) {
          $html .= '<ul>';
          $inList = true;
        }
        $html .= '<li>' . $toHtml($bulletText) . '</li>';
        continue;
      }

      if ($isHeadlineStar) {
        if ($inList) {
          $html .= '</ul>';
          $inList = false;
        }
        $html .= '<p><strong>' . h($headlineText) . '</strong></p>';
        continue;
      }

      if ($inList) {
        $html .= '</ul>';
        $inList = false;
      }

      $html .= '<p>' . $toHtml($raw) . '</p>';
    }

    if ($inList) {
      $html .= '</ul>';
    }

    return $html;
  };

  $marker = 'For any queries, please get in touch with us:';
  $pos = strpos($message, $marker);
  if ($pos !== false) {
    $before = substr($message, 0, $pos);
    $after = substr($message, $pos);
    echo $formatMessage($before);
    require $partialsDir . '/payment.php';
    echo $formatMessage($after);
  } else {
    echo $formatMessage($message);
    require $partialsDir . '/payment.php';
  }
  ?>
</div>

<?php require $partialsDir . '/footer.php'; ?>
