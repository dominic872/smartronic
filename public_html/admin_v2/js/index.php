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
      text-align: center;
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
  </style>
</head>
<body>

  <h1>INVOICE</h1>

  <div class="invoice-details">
    <p><strong>Invoice No.:</strong> S202505-05-02</p>
    <p><strong>Date:</strong> 04 May 2025</p>
    <p><strong>Customer Name:</strong> Arun Lal</p>
  </div>

  <div class="billing">
    <h2>Billing Address:</h2>
    <p>Arun Lal<br>
    +91 80500 54746<br>
    Bengaluru, Karnataka, India</p>
  </div>

  <div class="items">
    <h2>Package Details</h2>
    <table>
      <thead>
        <tr>
          <th>Package</th>
          <th>Quantity</th>
          <th>Unit Price</th>
          <th>Total Price</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Complete CCTV Surveillance System<br><small>Includes: HIKVISION Full HD 8-Channel DVR, 2 MP Outdoor Cameras (6 units) with Normal Night Vision, Motion Detection, HDD (CCTV Storage), 3+1 CCTV Cable, BNC & DC Connectors, SMPS (Power Supply)</small></td>
          <td>1</td>
          <td>₹ 25,000.00</td>
          <td>₹ 25,000.00</td>
        </tr>
        <tr>
          <td>Extra Cable of 80 meters</td>
          <td>—</td>
          <td>—</td>
          <td>₹ 3,200.00</td>
        </tr>
        <tr>
          <td>DVR rack and fitting</td>
          <td>—</td>
          <td>—</td>
          <td>₹ 1,200.00</td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="total">
    <table>
      <tr>
        <td>Total Amount Payable</td>
        <td>₹ 29,400.00</td>
      </tr>
      <tr>
        <td>Total Amount PAID</td>
        <td>₹ 29,400.00</td>
      </tr>
    </table>
  </div>

  <div class="terms">
    <h2>Terms & Conditions</h2>
    <ul>
      <li>This invoice is valid for the mentioned transaction only.</li>
      <li>No returns or refunds after installation.</li>
      <li>Warranty coverage as per the manufacturer’s policy is 2 years.</li>
      <li>Please do not remove the barcode on the products to avail of the warranty.</li>
      <li>A 2-year service warranty applies to all Hikvision products.</li>
      <li>This warranty does not cover pins, cables, issues from loose connections, oxidation, corrosion, SMPS, accessories, or network issues.</li>
      <li>For out-of-warranty service, a visiting charge of ₹750 will apply.</li>
      <li>Support: 849 6080849</li>
    </ul>
  </div>

  <div class="footer">
    Mobile: 8496080849 | hello@smartronic.online | www.smartronic.online<br>
    Smartronic, 809, 25th Cross, HSR Layout, Bangalore 560 102
  </div>

</body>
</html>
