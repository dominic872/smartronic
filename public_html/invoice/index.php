<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<script>alert("FILE LOADED - Version 22:17 - index.php is executing!");</script>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js" crossorigin="anonymous"></script>
  <script>
    function logToMonitor(msg, color='blue') {
        let mon = document.getElementById('live-monitor');
        if (!mon) {
            mon = document.createElement('div');
            mon.id = 'live-monitor';
            mon.style = 'position:fixed; top:0; left:0; width:100%; z-index:99999; background:black; color:white; font-size:12px; padding:10px; border-bottom:3px solid red; white-space:pre-wrap; max-height:150px; overflow:auto;';
            document.documentElement.appendChild(mon);
        }
        const time = new Date().toLocaleTimeString();
        mon.innerHTML = `<span style="color:${color}">[${time}] ${msg}</span><br>` + mon.innerHTML;
    }
    logToMonitor("Page loading started...", "yellow");
    window.onerror = function(m, u, l){ logToMonitor("FATAL ERROR: " + m + " at line " + l, "red"); return false; };
  </script>
  <style>
    /* Base layout — scoped to invoice wrapper to avoid affecting parent page */
    .invoice-body { 
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; 
      color: #1f2937; 
      line-height: 1.5; 
    }
    #invoice { 
      background: #ffffff; 
      padding: 32px; 
      border-radius: 8px; 
      box-shadow: 0 1px 2px rgba(0,0,0,0.06);
    }

    /* Headings */
    .invoice-body h1, .invoice-body h2, .invoice-body h3 { 
      text-align: left; 
      padding-bottom: 12px; 
      border-bottom: 1px solid #e5e7eb; 
      color: #406bc7; 
      font-weight: 600; 
      margin: 0 0 16px 0;
    }
    .invoice-body h1 { font-size: 26px; }
    .invoice-body h2 { font-size: 18px; }

    /* Sections spacing */
    .invoice-details, .billing, .items, .total, .terms, .footer { margin-top: 24px; }

    /* Tables */
    .items table, .total table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    .items thead th { background: #f3f4f6; color: #111827; }
    .items th, .items td, .total td { border: 1px solid #d1d5db; padding: 10px; vertical-align: top; }
    .items tbody tr:nth-child(odd) { background: #fafafa; }

    /* Totals alignment */
    .total td:first-child { width: 60%; text-align: right; color: #374151; }
    .total td:last-child { text-align: right; font-weight: 700; color: #111827; }

    /* Footer */
    .footer { text-align: center; font-size: 12px; color: #6b7280; margin-top: 36px; }

    /* Print styles for PDF generation */ 
    @media print {
      html, body { background: red !important; }
      .invoice-body { width: auto; background: none; margin: 0; }
      #invoice { padding: 40px; box-shadow: none; }
      .invoice-body a { color: #111 !important; text-decoration: none; }
    }
  </style>

<?php
$id = $_GET['id'] ?? '';

if (empty($id)): 
?>


  <style>


    .form-container {
      margin-bottom: 30px;
      text-align: center;
    }

    .form-container input[type="text"] {
      padding: 10px;
      font-size: 16px;
      width: 200px;
    }

    .form-container input[type="submit"] {
      padding: 10px 20px;
      font-size: 16px;
      margin-left: 10px;
      cursor: pointer;
    }
  </style>

<body>

<div class="form-container">
  <form method="get">
    <label for="id">Enter Invoice ID:</label>
    <input type="text" name="id" id="id" value="<?php echo htmlspecialchars($id); ?>" required>
    <input type="submit" value="View Invoice">
  </form>
</div>
<?php 
endif;
?>
<?php
if (!empty($id)): 

    $isLocalhost = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
    $host = $isLocalhost ? 'localhost' : '127.0.0.1:3306';
    $username = $isLocalhost ? 'root' : 'u398852039_smartronic';
    $password = $isLocalhost ? 'root' : 'Chennai@40!';
    $database = 'u398852039_smartronic';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_GET['id'] ?? '';
$sql = "SELECT idno, phone, quantity, price, product, storage, resolution, name, owner, note, area, date, location, map FROM orders WHERE idno = '$id'";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    $phone = $row['phone'];
    $quantity = $row['quantity'];
    $price = $row['price'];
    $product = $row['product'];
    $storage = $row['storage'];
    $resolution = $row['resolution'];
    $name = $row['name'];
    $owner = $row['owner'];
    $note = $row['note'];
    $area = $row['area'];
    $date = $row['date'];
    $location = $row['location'];
    $map = $row['map'];
} else {
    die("Invoice not found.");
}
?>
<?php
$formatted_date = date("Ym-d", strtotime($date));
$hash = crc32(strtolower(trim($name)));
$code = str_pad(abs($hash) % 10000, 4, "0", STR_PAD_LEFT);
$invoice_number = $formatted_date . "-" . $id . "-" . $code;
?>

<div class="invoice-body">

  <!-- Controls: textarea and Proforma toggle -->
  <div id="scannerCollapsibleHeader" style="display:flex; align-items:center; gap:8px; margin-bottom:10px; cursor:pointer; color:#1e40af; font-weight:700;">
    <i class="fas fa-receipt"></i> Transaction Scanner
    <i class="fas fa-caret-down" style="margin-left:auto;"></i>
  </div>

  <div id="invoiceControls" style="border: 1px solid #e2e8f0; border-radius: 8px; background:#fff;">
    <div id="invoiceControlsHeader" style="display:flex; align-items:center; gap: 12px; padding: 10px; cursor: pointer;">
      <strong>Edit Invoice/ Proforma</strong>
      <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 14px; margin-left:auto;">
        <input type="checkbox" id="proformaToggle"> Porforma Invoice
      </label>
    </div>
    <div id="invoiceControlsContent" style="display:none; padding: 10px;">
      <input type="text" id="customerNameInput" placeholder="Customer name" style="width: 100%; max-width: 480px; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;" value="<?php echo htmlspecialchars($name); ?>">
      <div id="noteEditorToolbar" style="display: flex; gap: 8px; margin-top:8px;">
        <button type="button" data-cmd="bold" style="background: #f3f4f6; border: 1px solid #d1d5db; padding: 6px 10px; border-radius: 6px;">B</button>
        <button type="button" data-cmd="italic" style="background: #f3f4f6; border: 1px solid #d1d5db; padding: 6px 10px; border-radius: 6px;">I</button>
        <button type="button" data-cmd="underline" style="background: #f3f4f6; border: 1px solid #d1d5db; padding: 6px 10px; border-radius: 6px;">U</button>
        <button type="button" data-cmd="p" style="background: #f3f4f6; border: 1px solid #d1d5db; padding: 6px 10px; border-radius: 6px;">P</button>
        <button type="button" onclick="downloadPDF()" style="margin-left:auto; background: #007bff; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px;">Download PDF</button>
      </div>
      <div style="position:relative; width: 100%; max-width: 480px; margin-top:8px;">
        <div id="noteEditor" contenteditable="true" style="width: 100%; min-height: 120px; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;"></div>
        <button id="applyInvoiceChanges" style="position:absolute; bottom:8px; right:8px; width:28px; height:28px; border-radius:50%; background:#10b981; color:#fff; border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; font-weight:700;">✓</button>
      </div>
    </div>
  </div>

<!-- Transaction Scanner Overlay / Panel -->
<div id="scanner-panel" class="scanner-container" style="margin-bottom: 24px; padding: 25px; border-radius: 16px; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); position: relative; z-index: 10; display:none;">
    <h2 style="margin-top: 0; display: flex; align-items: center; gap: 10px; font-size: 20px; color: #1e40af; font-weight: 700; border: none;">
        <i class="fas fa-receipt"></i> Transaction Scanner
    </h2>
    <p style="font-size: 14px; color: #4b5563; margin-bottom: 20px;">Paste a screenshot (Ctrl+V) or click below to extract payment details.</p>
    
    <div id="drop-zone-container" style="display: flex; gap: 10px; margin-bottom: 20px;">
        <label for="file-input" id="drop-zone" style="flex: 1; height: 140px; border: 2px dashed #3b82f6; border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; background: #f8faff; box-sizing: border-box;">
            <div id="drop-status" style="text-align: center; pointer-events: none;">
                <i class="fas fa-upload" style="font-size: 32px; color: #3b82f6; margin-bottom: 12px;"></i>
                <div style="font-weight: 600; color: #1e40af; font-size: 16px;">Click or Drop Screenshot</div>
                <div style="font-size: 12px; color: #60a5fa; margin-top: 4px;">JPG, PNG</div>
                <div id="processing-msg" style="display: none; color: #2563eb; font-weight: 700; margin-top: 10px; font-size: 14px;">
                    <i class="fas fa-sync fa-spin"></i> Initializing OCR...
                </div>
            </div>
        </label>
        
        <button id="btn-paste-manual" style="width: 120px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; gap: 8px;">
            <i class="fas fa-paste" style="font-size: 24px; color: #475569;"></i>
            <span style="font-size: 13px; font-weight: 600; color: #475569;">Paste Button</span>
        </button>
    </div>
    <input type="file" id="file-input" style="visibility: hidden; position: absolute; height: 0; width: 0;" accept="image/*">

    <!-- Results Table -->
    <div id="scanner-results" style="margin-top: 25px; animation: fadeIn 0.3s ease-out;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">
            <h3 style="margin: 0; font-size: 16px; color: #1e293b; font-weight: 600; border: none;">Scanned Transactions</h3>
            <button onclick="clearTransactions()" style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; padding: 6px 14px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s;">Clear All</button>
        </div>
        <div style="overflow-x: auto; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff;">
            <table id="txn-table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left;">
                        <th style="padding: 12px; border-bottom: 1px solid #e2e8f0; color: #475569;">Date</th>
                        <th style="padding: 12px; border-bottom: 1px solid #e2e8f0; color: #475569;">From/Payer</th>
                        <th style="padding: 12px; border-bottom: 1px solid #e2e8f0; color: #475569;">To/Payee</th>
                        <th style="padding: 12px; border-bottom: 1px solid #e2e8f0; color: #475569;">ID/Account</th>
                        <th style="padding: 12px; border-bottom: 1px solid #e2e8f0; color: #475569;">Amount</th>
                        <th style="padding: 12px; border-bottom: 1px solid #e2e8f0; color: #475569;">TXN ID</th>
                    </tr>
                </thead>
                <tbody id="txn-tbody">
                    <tr id="no-data-msg"><td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8; font-style: italic;">No transactions scanned yet</td></tr>
                </tbody>
            </table>
        </div>
        <!-- Raw Debug Output (Hidden by default, used for troubleshooting) -->
        <details style="margin-top: 10px; font-size: 11px; color: #94a3b8;">
            <summary style="cursor: pointer;">See raw text (Debug)</summary>
            <pre id="debug-text" style="background: #f1f5f9; padding: 10px; border-radius: 4px; white-space: pre-wrap;"></pre>
        </details>
    </div>
</div>
<style>
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    #drop-zone:hover { background: #eff6ff; border-color: #2563eb; }
    #drop-zone:active { transform: scale(0.98); }
</style>

<div id="invoice">
  <div class="wp-block-site-logo">
    <a href="/" class="custom-logo-link" rel="home">
      <img width="200" height="49" src="/content/uploads/2025/01/smarthome-black2.svg" class="custom-logo" alt="Smartronic">
    </a>
  </div>

  <h1>INVOICE</h1>

  <div class="invoice-details">


<p><strong>Invoice No.:</strong> <?php echo $invoice_number; ?></p>


    
    <p><strong>Date:</strong> <?php echo date("m/d/Y", strtotime($date)); ?></p>
    <p><strong>Customer Name:</strong> <span id="customerNameDisplay"><?php echo htmlspecialchars($name); ?></span></p>
  </div>

  <div class="billing">
    <h2>Billing Address:</h2>
    <!-- Dynamic note shown directly under Billing title -->
    <div id="noteOutput" style="margin-top: 8px; color: #374151; white-space: pre-line;"></div>
    <p><strong id="billingNameDisplay"> <?php echo htmlspecialchars($name); ?></strong></p>
    <p><?php echo @htmlspecialchars($phone); ?><br>
    <?php echo @htmlspecialchars($area); ?>, Bangalore, Karnataka.</p>
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
          <td>
            <strong>Complete CCTV Surveillance System</strong><br />
            <?php echo htmlspecialchars($product); ?> | <?php echo htmlspecialchars($resolution); ?> X <?php echo htmlspecialchars($quantity); ?>  Outdoor / Indoor Cameras with Night Vision, Motion Detection, <?php echo htmlspecialchars($storage); ?> HDD (CCTV Storage)

        
        
          </td>
            
          
          <td>1</td>
          <td>₹<?php echo @htmlspecialchars($price); ?></td>
          <td> <strong>₹<?php echo @htmlspecialchars($price); ?> </strong></td>
        </tr>
        <tr>
          <td colspan="4">
          <strong>Includes:</strong><br />
          HIKVISION Full HD <?php echo htmlspecialchars($product); ?> | <?php echo htmlspecialchars($resolution); ?> X <?php echo htmlspecialchars($quantity); ?>  Outdoor / Indoor Cameras with Night Vision, Motion Detection, <?php echo htmlspecialchars($storage); ?> HDD (CCTV Storage), BNC & DC Connectors, SMPS (Power Supply), installation and nessesary accessories.


          </td>
      </tbody>
    </table>
  </div>

  <div class="total">
    <table>
      <tr>
        <td>Total Amount Payable</td>
        <td id="payable">₹<?php echo @htmlspecialchars($price); ?> </td>
      </tr>
      <tr id="paidRow">
        <td>Total Amount PAID</td>
        <td id="paid"><strong>₹<?php echo @htmlspecialchars($price); ?> </strong></td>
      </tr>
    </table>
  </div>

  <div class="terms" id="termsSection">
    <h2>Terms & Conditions</h2>

<ul>
  <li><strong>Invoice validity:</strong> This invoice is valid only for the mentioned transaction.</li>
  <li><strong>Returns &amp; refunds:</strong> No returns or refunds are accepted after installation.</li>
  <li><strong>Manufacturer warranty:</strong> Warranty coverage as per the manufacturer’s policy — 2 years.</li>
  <li><strong>Barcode:</strong> Please do not remove the product barcode to claim warranty.</li>
  <br><br><br>
  <li><strong>Service warranty:</strong> A 2-year service warranty is applicable for all Hikvision products.</li>
  <li><strong>Exclusions:</strong> Warranty does not cover pins, cables, loose connections, oxidation, corrosion, SMPS, or network issues.</li>
  <li><strong>Additional exclusions:</strong> SD cards, adapters, and camera stands are not covered under warranty. Wi-Fi, solar, and SIM-based cameras are covered only under brand/manufacturer warranty.</li>
  <li><strong>Out-of-warranty service:</strong> A visiting charge of ₹750 will apply for out-of-warranty products.</li>
  <li><strong>Office hours:</strong> Monday – Friday, 10:00 AM to 6:00 PM.</li>
  <li><strong>Emergency service:</strong> Emergency or 24×7 service is not available.</li>
  <li><strong>Service requests:</strong> Please send all service requests via WhatsApp for proper tracking and timely resolution.</li>
  <li><strong>Support:</strong> <a href="tel:+918496080849">849 6080849</a> (WhatsApp preferred)</li>
  <li><strong>Conduct policy:</strong> Any defamatory actions such as posting negative blogs, public complaints, or bad reviews will result in immediate termination of the service warranty.</li>
</ul>


  </div>

  <div class="footer">
    Mobile: 8496080849 | hello@smartronic.online | www.smartronic.online<br>
    13th Main Road, ITI Layout, HSR Layout, Bangalore 560068
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    function downloadPDF() {
      const element = document.getElementById('invoice');
      html2pdf().from(element).save('Smartronic_Invoice_<?php echo $id; ?>.pdf');
    }
    window.downloadPDF = downloadPDF;

    // Dynamic note binding — prefill from Billing p tags and replace them
    const noteOutput = document.getElementById('noteOutput');
    const noteEditor = document.getElementById('noteEditor');
    const billingEl = document.querySelector('.billing');
    const billingPs = billingEl ? billingEl.querySelectorAll('p') : [];
    const addressText = Array.from(billingPs).map(el => el.textContent.trim()).join('\n');
    if (noteOutput) {
      noteOutput.innerHTML = addressText;
    }
    if (noteEditor) {
      noteEditor.innerHTML = noteOutput ? noteOutput.innerHTML : addressText;
    }
    // Hide original p tags so the note is shown instead
    Array.from(billingPs).forEach(p => { p.style.display = 'none'; });
    if (noteEditor && noteOutput) {
      const updateNoteHtml = () => { noteOutput.innerHTML = noteEditor.innerHTML; };
      noteEditor.addEventListener('input', updateNoteHtml);
      noteEditor.addEventListener('mousedown', updateNoteHtml);
    }

    const applyBtn = document.getElementById('applyInvoiceChanges');
    const billingNameEl = document.getElementById('billingNameDisplay');
    const invoiceNameEl = document.getElementById('customerNameDisplay');
    const nameInput = document.getElementById('customerNameInput');
    if (applyBtn) {
      applyBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const newName = nameInput ? nameInput.value.trim() : '';
        const newNoteHtml = noteEditor ? noteEditor.innerHTML : '';
        if (newNoteHtml && noteOutput) noteOutput.innerHTML = newNoteHtml;
        if (newName) {
          if (billingNameEl) billingNameEl.textContent = newName;
          if (invoiceNameEl) invoiceNameEl.textContent = newName;
        }
      });
    }

    const invHeader = document.getElementById('invoiceControlsHeader');
    const invContent = document.getElementById('invoiceControlsContent');
    if (invHeader && invContent) {
      invHeader.addEventListener('click', function() {
        invContent.style.display = (invContent.style.display === 'none' || !invContent.style.display) ? 'block' : 'none';
      });
    }

    const scanHeader = document.getElementById('scannerCollapsibleHeader');
    const scannerPanel = document.getElementById('scanner-panel');
    if (scanHeader && scannerPanel) {
      scanHeader.addEventListener('click', function() {
        scannerPanel.style.display = (scannerPanel.style.display === 'none' || !scannerPanel.style.display) ? 'block' : 'none';
      });
    }

    // Proforma toggle
    const proformaToggle = document.getElementById('proformaToggle');
    const invoiceContainer = document.getElementById('invoice');
    const paidRow = document.getElementById('paidRow');
    const termsSection = document.getElementById('termsSection');
    const originalTextNodes = new Map();

    function updateInvoiceWord(isChecked) {
      if (!invoiceContainer) return;
      try {
        const walker = document.createTreeWalker(invoiceContainer, NodeFilter.SHOW_TEXT, null);
        let node;
        while ((node = walker.nextNode())) {
          if (!originalTextNodes.has(node)) {
            originalTextNodes.set(node, node.nodeValue);
          }
          const sourceText = originalTextNodes.get(node);
          if (isChecked) {
            node.nodeValue = sourceText.replace(/\bInvoice\b/gi, 'Porforma Invoice');
          } else {
            node.nodeValue = sourceText;
          }
        }
      } catch (e) {
        // Fallback: iterate elements and replace textContent
        const all = invoiceContainer.querySelectorAll('*');
        all.forEach(el => {
          const orig = originalTextNodes.get(el) || el.textContent;
          originalTextNodes.set(el, orig);
          el.textContent = isChecked ? orig.replace(/\bInvoice\b/gi, 'Porforma Invoice') : orig;
        });
      }
      if (paidRow) paidRow.style.display = isChecked ? 'none' : '';
      if (termsSection) termsSection.style.display = isChecked ? 'none' : '';
    }

    if (proformaToggle) {
      proformaToggle.addEventListener('change', (e) => updateInvoiceWord(e.target.checked));
      updateInvoiceWord(proformaToggle.checked);
    }
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js" crossorigin="anonymous"></script>
<script>
  // Independent Transaction Scanner Script
  (function() {
    logToMonitor("Scanner script parsed.", "green");

    function initScanner() {
        logToMonitor("Scanner: initScanner called.", "lime");
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const processingMsg = document.getElementById('processing-msg');
        const scannerResults = document.getElementById('scanner-results');
        const txnTbody = document.getElementById('txn-tbody');
        const dropStatus = document.getElementById('drop-status');

        const btnPasteManual = document.getElementById('btn-paste-manual');

        if (!dropZone || !fileInput) {
            console.error("Scanner elements not found!");
            return;
        }

        console.log("Scanner initialized - attaching events");

        // Manual Paste Button
        if (btnPasteManual) {
            btnPasteManual.addEventListener('click', async () => {
                try {
                    const clipboardItems = await navigator.clipboard.read();
                    let found = false;
                    for (const item of clipboardItems) {
                        for (const type of item.types) {
                            if (type.startsWith('image/')) {
                                const blob = await item.getType(type);
                                processImage(blob);
                                found = true;
                                break;
                            }
                        }
                        if (found) break;
                    }
                    if (!found) alert("No image found in your clipboard. Please copy a screenshot first.");
                } catch (err) {
                    alert("Click 'Allow' to let the site read your clipboard, or use Ctrl+V keyboard shortcut.");
                    console.error(err);
                }
            });
        }
        
        fileInput.addEventListener('change', (e) => {
            logToMonitor("Scanner: fileInput changed.", "cyan");
            if (e.target.files.length > 0) {
                logToMonitor("Scanner: File selected: " + e.target.files[0].name, "lime");
                processImage(e.target.files[0]);
            }
        });

        // Global Paste Handling
        document.addEventListener('paste', (e) => {
            logToMonitor("Scanner: Global Paste detected.", "cyan");
            const items = (e.clipboardData || e.originalEvent.clipboardData).items;
            let found = false;
            for (const item of items) {
                if (item.type.indexOf("image") !== -1) {
                    logToMonitor("Scanner: Image found in paste!", "lime");
                    processImage(item.getAsFile());
                    found = true;
                }
            }
            if (!found) logToMonitor("Scanner: Paste detected but no image found.", "gray");
        });

        // Drag & Drop
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.background = '#eff6ff'; });
        dropZone.addEventListener('dragleave', () => { dropZone.style.background = '#f8faff'; });
        dropZone.addEventListener('drop', (e) => { 
            e.preventDefault(); 
            dropZone.style.background = '#f8faff';
            logToMonitor("Scanner: Drop detected.", "cyan");
            if (e.dataTransfer.files.length > 0) {
                logToMonitor("Scanner: File dropped: " + e.dataTransfer.files[0].name, "lime");
                processImage(e.dataTransfer.files[0]);
            }
        });

        async function processImage(imageFile) {
            if (!imageFile) { logToMonitor("Scanner: ERROR - No image file.", "red"); return; }
            logToMonitor("Scanner: Starting OCR for " + (imageFile.name || "Pasted Image"), "orange");
            
            if (typeof Tesseract === 'undefined') {
                logToMonitor("CRITICAL: Tesseract NOT LOADED.", "red");
                alert("Tesseract library failed to load. Please check your internet.");
                return;
            }

            if (processingMsg) processingMsg.style.display = 'block';
            
            try {
                logToMonitor("Scanner: Tesseract found. Communicating...", "orange");
                const result = await Tesseract.recognize(imageFile, 'eng', {
                    logger: m => {
                        if (m.status === 'recognizing text' && processingMsg) {
                            processingMsg.innerHTML = `<i class="fas fa-sync fa-spin"></i> Reading: ${Math.round(m.progress * 100)}%`;
                        }
                    }
                });
                
                const text = result.data.text;
                logToMonitor("Scanner: OCR COMPLETE. Text found: " + (text ? text.length : 0) + " chars", "lime");
                
                const debugEl = document.getElementById('debug-text');
                if (debugEl) debugEl.textContent = text || "--- NO TEXT FOUND ---";
                
                if (!text || text.trim().length === 0) {
                    logToMonitor("Scanner: WARNING - No text detected in image.", "red");
                } else {
                    extractDetails(text);
                }
            } catch (err) {
                logToMonitor("Scanner CRASH: " + err.message, "red");
                console.error(err);
            } finally {
                if (processingMsg) {
                    processingMsg.style.display = 'none';
                    processingMsg.innerHTML = `<i class="fas fa-sync fa-spin"></i> Reading OCR...`;
                }
                if (dropStatus) dropStatus.style.opacity = '1';
            }
        }

        function extractDetails(text) {
            console.log("Extracting details...");
            const amountMatch = text.match(/(?:₹|Rs\.?|INR|[A-Z]{3})\s*([\d,]+\.?\d{0,2})/i) || text.match(/Amount\s*[:.-]?\s*([\d,]+\.?\d{0,2})/i);
            const txnIdMatch = text.match(/(?:Txn|Transaction|Ref|UTR|Id)\s*(?:ID|No|Number)?\s*[:.-]?\s*([A-Z0-9]{10,})/i);
            const dateMatch = text.match(/(\d{1,2}\s[A-Za-z]{3}\s\d{2,4})|(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})/);
            const payeeIdMatch = text.match(/([a-zA-Z0-9.-]+@[a-zA-Z]{3,})/i) || text.match(/UPI\s*ID\s*[:.-]?\s*([a-zA-Z0-9.-]+@[a-zA-Z]{3,})/i);
            
            const lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 2);
            let payee = "-";
            let payer = "-";

            const paidToIdx = lines.findIndex(l => l.match(/Paid\s*to|To:|Paying/i));
            if (paidToIdx !== -1) {
                if (lines[paidToIdx].match(/Paid\s*to(?!\s*$)/i)) payee = lines[paidToIdx].replace(/Paid\s*to|To:|Paying/i, '').trim();
                else if (lines[paidToIdx+1]) payee = lines[paidToIdx+1];
            } else if (lines[0] && !lines[0].match(/Status|Success|Payment|Paid/i)) {
                payee = lines[0];
            }

            const fromIdx = lines.findIndex(l => l.match(/From:|Paid\s*by|Debited\s*from/i));
            if (fromIdx !== -1) {
                if (lines[fromIdx].match(/Paid\s*by(?!\s*$)|From:(?!\s*$)/i)) payer = lines[fromIdx].replace(/From:|Paid\s*by|Debited\s*from/i, '').trim();
                else if (lines[fromIdx+1]) payer = lines[fromIdx+1];
            }

            const data = {
                date: dateMatch ? dateMatch[0] : new Date().toLocaleDateString(),
                payer: payer.substring(0, 30),
                payee: payee.substring(0, 30),
                payeeId: payeeIdMatch ? (payeeIdMatch[1] || payeeIdMatch[0]) : "-",
                amount: amountMatch ? "₹" + amountMatch[1] : "-",
                txnId: txnIdMatch ? (txnIdMatch[1] || txnIdMatch[0]) : "-"
            };

            appendRow(data);
        }

        function appendRow(data) {
            console.log("Appending row to table");
            
            // Remove "No data" message
            const noData = document.getElementById('no-data-msg');
            if (noData) noData.remove();
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td style="padding: 12px; border-bottom: 1px solid #e2e8f0;">${data.date}</td>
                <td style="padding: 12px; border-bottom: 1px solid #e2e8f0;">${data.payer}</td>
                <td style="padding: 12px; border-bottom: 1px solid #e2e8f0;">${data.payee}</td>
                <td style="padding: 12px; border-bottom: 1px solid #e2e8f0; font-family: monospace;">${data.payeeId}</td>
                <td style="padding: 12px; border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #16a34a;">${data.amount}</td>
                <td style="padding: 12px; border-bottom: 1px solid #e2e8f0; font-family: monospace;">${data.txnId}</td>
            `;
            if(txnTbody) {
                txnTbody.prepend(row);
                // Scroll into view
                scannerResults.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        // Expose to window so onclick works
        window.clearTransactions = () => {
            logToMonitor("Scanner: clearTransactions clicked.", "orange");
            if(txnTbody) {
                txnTbody.innerHTML = '<tr id="no-data-msg"><td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8; font-style: italic;">No transactions scanned yet</td></tr>';
            }
            const debugEl = document.getElementById('debug-text');
            if (debugEl) debugEl.textContent = '';
        };
    }

    // Run init immediately if DOM is ready, otherwise wait
    if (document.readyState === "complete" || document.readyState === "interactive") {
        initScanner();
    } else {
        document.addEventListener("DOMContentLoaded", initScanner);
    }
  })();
</script>

</div>

<?php 
endif;
?>