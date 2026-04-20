<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice - Smartronic</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 40px;
      color: #333;
    }
    h1, h2 {
     
      text-align: left;
    padding-bottom: 16px;
    border-bottom: 1px solid #999;
    color: #7283ac;
    font-size: 23px;
    font-weight: 200;
    }
    .invoice-details, .billing, .items, .total, .terms, .footer {
      margin-top: 30px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    th, td {
      border: 1px solid #aaa;
      padding: 10px;
      text-align: left;
    }
    th {
      background-color: #eee;
    }
    .total td {
      font-weight: bold;
    }
    .footer {
      text-align: center;
      font-size: 0.9em;
      margin-top: 50px;
    }
    @media print {
  body {
    width: auto;
    background: none;
    margin: 0;
  }
}
@media screen {
    body {
      background: #999;
    margin: auto auto;
    width: 50%;
    }
  }

    #invoice{
      background-color: #fff;
      padding: 80px;
      
    }
  </style>
</head>


<body>



<div id="invoice">
<div class="wp-block-site-logo">
    <a href="/" class="custom-logo-link" rel="home">
      <img width="200" height="49" src="/content/uploads/2025/01/smarthome-black2.svg" class="custom-logo" alt="Smartronic">
    </a>
  </div>

  <div class="terms">
    <h2>Terms & Conditions</h2>
<ul>
  <li><strong>Invoice validity:</strong> This invoice is valid only for the mentioned transaction.</li>

  <li><strong>Returns &amp; refunds:</strong> No returns or refunds are accepted after installation.</li>

  <li><strong>Manufacturer warranty:</strong> Warranty coverage as per the manufacturer’s policy — 2 years.</li>

  <li><strong>Barcode:</strong> Please do not remove the product barcode to claim warranty.</li>

  <li><strong>Service warranty:</strong> A 2-year service warranty is applicable for all Hikvision products, subject to the conditions below.</li>

  <li><strong>Payment condition for warranty activation:</strong> Full payment must be completed on the same day of installation or within 72 hours. Service warranty benefits will be activated only after payment confirmation. Delayed payments may result in suspension or cancellation of service warranty.</li>

  <li><strong>Installation visit condition:</strong> Customers are requested to ensure site readiness at the time of installation. If the technician is required to revisit the site multiple times due to customer-side delays, incomplete arrangements, or rescheduling beyond company control, the service warranty may stand void.</li>

  <li><strong>Exclusions:</strong> Warranty does not cover pins, cables, loose connections, oxidation, corrosion, SMPS, or network issues.</li>

  <li><strong>Additional exclusions:</strong> SD cards, adapters, and camera stands are not covered under warranty. Wi-Fi, solar, and SIM-based cameras are covered only under brand/manufacturer warranty.</li>

  <li><strong>Out-of-warranty service:</strong> A visiting charge of ₹750 will apply for out-of-warranty products.</li>

  <li><strong>Office hours:</strong> Monday – Friday, 10:00 AM to 6:00 PM.</li>

  <li><strong>Emergency service:</strong> Emergency or 24×7 service is not available.</li>

  <li><strong>Service requests:</strong> Please send all service requests via WhatsApp for proper tracking and timely resolution.</li>

  <li><strong>Support:</strong> <a href="tel:+918496080849">849 6080849</a> (WhatsApp preferred)</li>

  <li><strong>Conduct policy:</strong> Any defamatory actions such as posting negative blogs, public complaints, or bad reviews without allowing resolution through official communication channels may result in immediate termination of the service warranty.</li>
</ul>
</div>

  <div class="footer">
    Mobile: 8496080849 | hello@smartronic.online | www.smartronic.online<br>
    13th Main Road, ITI Layout, HSR Layout, Bangalore 560068
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
  function downloadPDF() {
    const element = document.getElementById('invoice');
    html2pdf().from(element).save('Smartronic_Invoice_<?php echo $id; ?>.pdf');
  }
</script>

</body>
</html>
