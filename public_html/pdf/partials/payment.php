<?php
declare(strict_types=1);
$asHtml = (bool)($pdfContext['asHtml'] ?? false);
$assetsDir = (string)($pdfContext['assetsDir'] ?? (__DIR__ . '/../assets'));
$qrPath = 'https://smartronic.online/pdf/assets/upi_qr.jpg';
$qrSrc = $asHtml
  ? '/pdf/assets/upi_qr.jpg'
  : $qrPath;
?>
<br>
<div class="payment-details section">
  <div class="section-title"><strong>Payment Details</strong></div>
  <p>For UPI Payments please transfer to <strong>8884831000</strong> or scan the below QR code</p>

  <div class="payment-qr-wrap">
    <img class="payment-qr-img" src="<?php echo h($qrSrc); ?>" alt="UPI QR Code">
  </div>

  
  <div >Please share the payment screenshot or reference number after payment.</div>
<br />
  <p>For cheque or account transfers, please use the below bank details:</p>
  <br />
  <strong>Account Name:</strong> Nishita D Crecentia <br />
  <strong>Account Number:</strong> 50100760782462<br />
  <strong>Account Type:</strong> Savings<br />
  <strong>Bank Name:</strong> HDFC Bank<br />
  <strong>Branch:</strong> HSR Layout III, Bengaluru<br />
  <strong>IFSC Code:</strong> HDFC0004094<br />

 <br/><br/>
 <strong>Please share the payment screenshot or reference number after payment</strong></p>
 <ul>
 <li>Validity of this quotation: 30 days.</li>
 <li>Payment terms: Please pay 30% before the beginning of the work or as per the agreed terms.</li>
 <li>Delivery Timeline: 1 week</li>
 <li>Warranty: Warranty applicable as per the brand 2 years for Hikvision and 2 years for
HDD</li>
 <li>Installation charges are included in the above pricing.</li>
 </ul>
</div>
</div>

