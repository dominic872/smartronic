<?php
declare(strict_types=1);

$partialsDir = (string)($pdfContext['partialsDir'] ?? (__DIR__ . '/../partials'));

$items = is_array($data['items'] ?? null) ? $data['items'] : [];
$computedTotal = 0.0;
foreach ($items as $it) {
  $qty = (int)($it['qty'] ?? 0);
  $rate = (float)($it['rate'] ?? 0);
  $computedTotal += $qty * $rate;
}
$grandTotal = $computedTotal > 0 ? $computedTotal : (float)($data['amount'] ?? 0);

require $partialsDir . '/header.php';
?>

<?php
// Detect recorder type from items
$recorderType = 'NVR'; // default
foreach ($items as $item) {
  $name = strtolower($item['name'] ?? '');
  if (strpos($name, 'dvr') !== false) {
    $recorderType = 'DVR';
  } elseif (strpos($name, 'nvr') !== false) {
    $recorderType = 'NVR';
  } elseif (strpos($name, 'wifi') !== false || strpos($name, 'wi-fi') !== false) {
    $recorderType = 'WIFI';
  }
}
?>

<p>The INVOICE is for an <?php 
$hddItem = null;
foreach ($items as $item) {
  $name = strtolower($item['name'] ?? '');
  if (strpos($name, 'hdd') !== false || strpos($name, 'tb') !== false) {
    $hddItem = $item;
    break;
  }
}
echo $hddItem ? h($hddItem['name'] ?? 'HDD') : 'HDD'; ?> Set-up with <?php echo h($data['num_cameras'] ?? $cameraCount); ?> Cameras, Cabling and Installation</p>

<p>Dear <?php echo h($data['name'] ?? 'Customer'); ?></p>

<p>Thank you for choosing Smartronic.online for your CCTV security needs!
We appreciate your interest and are glad to share a customized quotation designed to give you the best value and protection for your property.</p>



<div class="section">
  <div class="section-title">Proposal for CCTV Installation</div>
  <table class="items" width="100%" cellspacing="0" cellpadding="0">
    <thead>
      <tr>
        <th width="6%" align="left">#</th>
        <th width="54%" align="left">Product / Service</th>
        <th width="10%" align="right">Qty</th>
        <th width="15%" align="right">Rate</th>
        <th width="15%" align="right">Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($items) > 0): ?>
        <?php foreach ($items as $idx => $it): ?>
          <?php
            $qty = (int)($it['qty'] ?? 0);
            $rate = (float)($it['rate'] ?? 0);
            $rowAmount = $qty * $rate;
          ?>
          <tr>
            <td><?php echo h((string)($idx + 1)); ?></td>
            <td><?php echo h((string)($it['name'] ?? '')); ?></td>
            <td align="right"><?php echo h((string)$qty); ?></td>
            <td align="right"><?php echo h(inr($rate)); ?></td>
            <td align="right"><?php echo h(inr($rowAmount)); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="5">Product details will be shared after site inspection.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <table class="totals" width="100%" cellspacing="0" cellpadding="0">
    <tr>
      <td width="65%"></td>
      <td width="20%" class="totals-k" align="right">Total</td>
      <td width="15%" class="totals-v" align="right"><?php echo h(inr($grandTotal)); ?></td>
    </tr>
  </table>
</div>

<?php require $partialsDir . '/terms.php'; ?>

<?php require $partialsDir . '/footer.php'; ?>

