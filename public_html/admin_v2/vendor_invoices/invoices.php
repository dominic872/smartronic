<?php
include '../auth.php'; // Security Check
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Invoice Scanner</title>
    
    <!-- Libraries -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src='https://unpkg.com/tesseract.js@v2.1.0/dist/tesseract.min.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Styles -->
    <style>
        body {
            background: #f8f8f8;
            color: #111111;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }
        
        /* Glassmorphism updated for light theme */
        .glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .glass-header {
            background: rgba(255, 255, 255, 0.9);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* Inputs */
        input, textarea {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #111111;
            transition: all 0.2s;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #f40009;
            box-shadow: 0 0 0 2px rgba(244, 0, 9, 0.1);
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.1); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(0, 0, 0, 0.2); }

        .drop-active {
            border-color: #f40009 !important;
            background: rgba(244, 0, 9, 0.05) !important;
        }

        .details-row {
            display: none;
            background: #fdfdfd;
            border-left: 2px solid #f40009;
        }
        .row-expanded .details-row {
            display: grid;
        }
        .invoice-row {
            cursor: pointer;
        }
    </style>
    
    <script>
        // Setup PDF.js worker
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
    </script>
</head>
<body class="p-4 md:p-8">

    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div class="flex items-center gap-3">
            <a href="/" class="custom-logo-link">
                <img width="200" height="40" src="https://smartronic.online/content/uploads/2025/01/smarthome-black2.svg" class="custom-logo" alt="Smartronic Logo">
            </a>
            <h1 class="text-2xl font-bold text-gray-800 ml-4">Invoice Scanner</h1>
        </div>
        <div class="text-sm text-gray-500">
            Scanning as <span class="text-gray-800 font-semibold"><?php echo htmlspecialchars($username ?? 'User'); ?></span>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left: Upload & Extraction -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- Drop Zone -->
            <div id="drop-zone" class="glass p-8 border-2 border-dashed border-gray-600 rounded-2xl flex flex-col items-center justify-center text-center cursor-pointer transition-colors hover:bg-white/5 relative group h-64">
                <input type="file" id="file-input" class="hidden" accept="image/*,.pdf" />
                
                <!-- Idle State -->
                <div id="drop-idle" class="space-y-3 pointer-events-none">
                    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-2 text-2xl group-hover:scale-110 transition-transform">
                        <i class="fas fa-cloud-upload-alt text-red-500"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-700">Drag & Drop Invoice</p>
                    <p class="text-sm text-gray-500">Supports JPG, PNG, PDF</p>
                    <p class="text-xs text-gray-400 mt-2">or <span class="text-red-500 underline">Browse Files</span> / Ctrl+V to Paste</p>
                </div>

                <!-- Preview State -->
                <div id="drop-preview" class="hidden absolute inset-0 rounded-2xl overflow-hidden bg-black/40 flex items-center justify-center">
                    <img id="preview-img" class="max-w-full max-h-full object-contain p-2" />
                    <button id="clear-preview" class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white w-8 h-8 rounded-full shadow-lg z-10"><i class="fas fa-times"></i></button>
                    
                    <!-- Progress Overlay -->
                    <div id="ocr-progress" class="absolute inset-0 bg-white/90 flex flex-col items-center justify-center hidden z-20 backdrop-blur-sm">
                        <div class="w-12 h-12 border-4 border-red-500 border-t-transparent rounded-full animate-spin mb-3"></div>
                        <p class="text-red-600 font-medium animate-pulse">Reading Invoice...</p>
                        <p id="ocr-status" class="text-xs text-gray-500 mt-1">Initializing...</p>
                    </div>
                </div>
            </div>

            <!-- Extraction Form -->
            <div id="extraction-card" class="glass p-6 opacity-50 pointer-events-none transition-all">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800"><i class="fas fa-magic mr-2 text-red-500"></i>Extracted Data</h2>
                    <span id="confidence-badge" class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-500">Waiting for input...</span>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Invoice #</label>
                            <input type="text" id="inp-inv-num" class="w-full px-3 py-2 rounded-lg" placeholder="e.g. INV-001">
                        </div>
                         <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Date</label>
                            <input type="date" id="inp-date" class="w-full px-3 py-2 rounded-lg">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Detected Items (Line Items)</label>
                        <textarea id="inp-items" rows="3" class="w-full px-3 py-2 rounded-lg font-mono text-xs" placeholder="Item 1... 100.00"></textarea>
                        <p class="text-[10px] text-gray-500 mt-1 text-right">Edit if needed</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Total Amount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-400 font-mono">₹</span>
                            <input type="number" id="inp-total" step="0.01" class="w-full pl-8 pr-3 py-2 rounded-lg font-bold text-lg text-green-600" placeholder="0.00">
                        </div>
                    </div>

                    <button id="btn-add" class="w-full py-3 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-900/20 transition-all transform hover:translate-y-px">
                        <i class="fas fa-plus mr-2"></i> Save Invoice
                    </button>
                    <input type="hidden" id="inp-image-data-url" />
                </div>
            </div>

        </div>

        <!-- Right: Invoice Table -->
        <div class="lg:col-span-7 h-[calc(100vh-8rem)] flex flex-col">
            <div class="glass flex-1 flex flex-col overflow-hidden relative">
                <div class="glass-header p-4 flex justify-between items-center bg-gray-50 sticky top-0 z-10 backdrop-blur-md">
                    <h2 class="font-semibold text-gray-800">Recent Invoices</h2>
                    <button onclick="fetchInvoices()" class="text-xs bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition"><i class="fas fa-sync-alt text-gray-600"></i></button>
                </div>

                <div class="overflow-y-auto flex-1 p-4" id="invoice-list-container">
                    <!-- Table Header -->
                    <div class="grid grid-cols-12 gap-4 text-xs font-medium text-gray-400 pb-2 border-b border-gray-200 mb-2 px-2">
                        <div class="col-span-2">Date</div>
                        <div class="col-span-3">Invoice #</div>
                        <div class="col-span-4">Items Summary</div>
                        <div class="col-span-2 text-right">Amount</div>
                        <div class="col-span-1 text-center">Act</div>
                    </div>

                    <!-- List Items -->
                    <div id="invoice-list" class="space-y-2">
                        <!-- Items injected here -->
                        <div class="text-center py-10 text-gray-500 italic">No invoices loaded</div>
                    </div>
                </div>

                <!-- Footer Grand Total -->
                <div class="p-4 bg-gray-50 border-t border-gray-200 mt-auto">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">Grand Total</span>
                        <span class="text-2xl font-bold text-gray-800 tracking-tight">₹ <span id="grand-total">0.00</span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logic -->
    <script>
        // --- State ---
        let currentWorker = null;
        let isProcessing = false;

        // --- Elements ---
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const previewImg = document.getElementById('preview-img');
        const dropIdle = document.getElementById('drop-idle');
        const dropPreview = document.getElementById('drop-preview');
        const ocrProgress = document.getElementById('ocr-progress');
        const ocrStatus = document.getElementById('ocr-status');
        const clearBtn = document.getElementById('clear-preview');
        
        const extractionCard = document.getElementById('extraction-card');
        const inpInvNum = document.getElementById('inp-inv-num');
        const inpDate = document.getElementById('inp-date');
        const inpTotal = document.getElementById('inp-total');
        const inpItems = document.getElementById('inp-items');
        const btnAdd = document.getElementById('btn-add');
        
        // --- Init ---
        document.addEventListener('DOMContentLoaded', () => {
             fetchInvoices();
             setupDragAndDrop();
             setupPaste();
        });

        // --- Drag & Drop ---
        function setupDragAndDrop() {
            dropZone.addEventListener('click', (e) => {
                if (e.target !== clearBtn && !isProcessing && getComputedStyle(dropPreview).display === 'none') {
                    fileInput.click();
                }
            });

            fileInput.addEventListener('change', handleFileSelect);

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.add('drop-active'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.remove('drop-active'), false);
            });

            dropZone.addEventListener('drop', handleDrop, false);
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        function handleFileSelect(e) {
            handleFiles(e.target.files);
        }

        function setupPaste() {
            document.addEventListener('paste', (e) => {
                const items = (e.clipboardData || e.originalEvent.clipboardData).items;
                for (let index in items) {
                    const item = items[index];
                    if (item.kind === 'file') {
                        const blob = item.getAsFile();
                        handleFiles([blob]);
                        return;
                    }
                }
            });
        }

        function handleFiles(files) {
            if (files.length === 0) return;
            const file = files[0];
            
            // Validate
            if (!file.type.match('image.*') && !file.type.match('application/pdf')) {
                alert('Only Images and PDFs are supported');
                return;
            }

            processFile(file);
        }

        async function processFile(file) {
            if (isProcessing) return;
            isProcessing = true;
            
            // Show Preview UI
            dropIdle.classList.add('hidden');
            dropPreview.classList.remove('hidden');
            ocrProgress.classList.remove('hidden');
            extractionCard.classList.add('opacity-50', 'pointer-events-none'); // Disable form
            
            try {
                let imageDataUrl = '';

                // Handle PDF
                if (file.type === 'application/pdf') {
                    ocrStatus.innerText = "Converting PDF to Image...";
                    imageDataUrl = await convertPdfToImage(file);
                } else {
                    // Start reading image
                    ocrStatus.innerText = "Reading Image File...";
                    imageDataUrl = await readFileAsDataURL(file);
                }
                
                // Show Image
                previewImg.src = imageDataUrl;
                document.getElementById('inp-image-data-url').value = imageDataUrl; // Store if needed

                // Start OCR
                ocrStatus.innerText = "Initializing OCR Engine...";
                await runOCR(imageDataUrl);

            } catch (err) {
                console.error(err);
                alert("Error processing file: " + err.message);
                resetPreview();
            } finally {
                isProcessing = false;
                ocrProgress.classList.add('hidden');
            }
        }

        function readFileAsDataURL(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => resolve(e.target.result);
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        // --- PDF Logic ---
        async function convertPdfToImage(file) {
            const arrayBuffer = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument(arrayBuffer).promise;
            
            // Render first page only for now (simplification)
            // Ideally we iterate all pages, but typically invoices are 1 page
            const page = await pdf.getPage(1);
            
            const viewport = page.getViewport({ scale: 2.0 }); // High res for OCR
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            await page.render({ canvasContext: context, viewport: viewport }).promise;
            
            return canvas.toDataURL('image/jpeg', 0.8);
        }

        // --- OCR Logic ---
        async function runOCR(imageUrl) {
            ocrStatus.innerText = "Scanning Text...";
            
            const { createWorker } = Tesseract;
            const worker = createWorker({
                logger: m => {
                    if (m.status === 'recognizing text') {
                        ocrStatus.innerText = `Scanning: ${Math.round(m.progress * 100)}%`;
                    }
                }
            });
            
            await worker.load();
            await worker.loadLanguage('eng');
            await worker.initialize('eng');
            
            const { data: { text } } = await worker.recognize(imageUrl);
            
            console.log("OCR Result:", text);
            // await worker.terminate(); // Keep worker alive for potential future use or terminate if memory is an issue

            parseInvoiceText(text);
            
            // Enable form
            extractionCard.classList.remove('opacity-50', 'pointer-events-none');
        }

        // --- Parsing Logic ---
        function parseInvoiceText(text) {
            // Normalize
            const lines = text.split('\n').filter(l => l.trim().length > 0);
            
            // 1. Date Detection
            // Pattern 1: Labels like "Dated" or "dt." or "_dt."
            let rawDate = "";
            const dtMatch = text.match(/(?:Dated|dt\.|_dt\.)\s*[:.]?\s*(\d{1,2}[-/.](?:[A-Za-z]{3,9}|[0-9]{1,2})[-/.][0-9]{2,4})/i);
            
            // Pattern 2: Combined line like "#15/1 GROUND FLOOR ASS/31661/25-26 13-Dec-25"
            // We look for any date pattern at the end of such a line
            const combinedLineMatch = text.match(/GROUND\s*FLOOR.*(ASS\/[0-9/\\-]{4,})\s*(\d{1,2}[-/.][A-Za-z]{3,9}[-/.][0-9]{2,4})/i);

            if (dtMatch) {
                rawDate = dtMatch[1];
            } else if (combinedLineMatch) {
                rawDate = combinedLineMatch[2];
            } else {
                // Check for "Dated" keyword with vertical value
                const datedIdx = lines.findIndex(l => l.match(/^Dated$/i));
                if (datedIdx !== -1) {
                    for (let i = datedIdx; i <= Math.min(datedIdx + 2, lines.length - 1); i++) {
                        const m = lines[i].match(/(\d{1,2}[-/.](?:[A-Za-z]{3,9}|[0-9]{1,2})[-/.][0-9]{2,4})/);
                        if (m) { rawDate = m[1]; break; }
                    }
                }
            }
            
            // Global fallback
            if (!rawDate) {
                const m = text.match(/(\d{1,2}[-/.](?:[A-Za-z]{3,9}|[0-9]{1,2})[-/.][0-9]{2,4})/);
                if (m) rawDate = m[0];
            }

            if (rawDate) {
                try {
                    const cleanDate = rawDate.trim().replace(/[.\s]/g, '-');
                    let d = new Date(cleanDate);
                    if (isNaN(d.getTime())) {
                        const parts = cleanDate.split(/[-/]/);
                        if (parts.length === 3) {
                            const months = {jan:0,feb:1,mar:2,apr:3,may:4,jun:5,jul:6,aug:7,sep:8,oct:9,nov:10,dec:11};
                            let day = parseInt(parts[0]);
                            let monthStr = parts[1].toLowerCase().substring(0,3);
                            let month = months[monthStr];
                            if (isNaN(month)) month = parseInt(parts[1]) - 1;
                            let year = parseInt(parts[2]);
                            if (year < 100) year += 2000;
                            d = new Date(year, month, day);
                        }
                    }
                    if (!isNaN(d.getTime())) {
                        const yyyy = d.getFullYear();
                        const mm = String(d.getMonth() + 1).padStart(2, '0');
                        const dd = String(d.getDate()).padStart(2, '0');
                        inpDate.value = `${yyyy}-${mm}-${dd}`;
                    }
                } catch(e) { console.warn("Date parse error", e); }
            }

            // 2. Invoice Number Detection
            // Requirement: Prioritize ASS/3. Specially check combined lines from image/samples.
            let detectedInv = "";
            if (combinedLineMatch) {
                detectedInv = combinedLineMatch[1].trim();
            } else {
                const assMatch = text.match(/ASS\/3[A-Z0-9/\\-]{4,}/i);
                if (assMatch) {
                    detectedInv = assMatch[0].trim();
                } else {
                    // Fallback to label search if ASS/3 not explicitly found as one block
                    const invLabels = ["Invoice No", "Inv No", "Invoice #", "Inv #"];
                    for (const label of invLabels) {
                        const labelIdx = lines.findIndex(l => l.toLowerCase().includes(label.toLowerCase()));
                        if (labelIdx !== -1) {
                            for (let i = labelIdx; i <= Math.min(labelIdx + 2, lines.length - 1); i++) {
                                const m = lines[i].match(/(ASS\/[0-9/\\-]{5,}|[A-Z0-9/\\-]{6,})/i);
                                if (m && /\d/.test(m[1])) {
                                    detectedInv = m[1];
                                    break;
                                }
                            }
                        }
                        if (detectedInv) break;
                    }
                }
            }
            
            if (detectedInv) inpInvNum.value = detectedInv;

            // 3. Total Amount
            // Look for "Total", "Grand Total", "Balance Due" followed by number
            // Or look for the largest currency-like number at the bottom half of text
            const totalMatch = text.match(/(?:Total|Grand Total|Balance Due|Amount Due)\s*[:.]?\s*(?:₹|Rs\.?|INR)?\s*([\d,]+\.?\d{0,2})/i);
            
            if (totalMatch) {
                inpTotal.value = totalMatch[1].replace(/,/g, '');
            } else {
                // Fallback: Find all numbers with decimals, sort descending, pick top?
                // Dangerous but often works for invoices where Total is largest number
                const currencyMatches = text.match(/(\d{1,3}(?:,\d{3})*\.\d{2})/g);
                if (currencyMatches) {
                    const numbers = currencyMatches.map(n => parseFloat(n.replace(/,/g, '')));
                    const max = Math.max(...numbers);
                    if (max > 0) inpTotal.value = max;
                }
            }
            
            // 4. Items (Heuristic) & Noise Filtering
            // Look for "Description of Goods" then extract following lines
            const descIdx = lines.findIndex(l => l.match(/Description\s*of\s*Goods/i));
            let rawItemsLines = [];
            if (descIdx !== -1) {
                for (let i = descIdx + 1; i < lines.length; i++) {
                   // Stop if we hit common footer terms
                   if (lines[i].match(/Total|Output|Input|Tax|CGST|SGST|IGST|Amount\s*in\s*words/i)) break;
                   rawItemsLines.push(lines[i]);
                }
            } else {
                // Fallback basic logic
                rawItemsLines = lines.filter(line => /[0-9]/.test(line) && line.length > 5 && !line.match(/Total|Subtotal|Tax/i));
            }

            // FILTER NOISE (Updated with combined line rules)
            const filteredItems = rawItemsLines.filter(line => {
                const l = line.trim();
                if (l.length < 3) return false;
                // Ignore address headers and combined noise lines
                if (l.match(/GROUND\s*FLOOR|BANGALORE|GSTIN|UIN|Buyers\s*Order|dt\.|_dt\./i)) return false;
                // Ignore the specific combined line starting with #15
                if (l.startsWith('#15/')) return false;
                // Ignore phone numbers
                if (l.match(/^\d{10}$/) || l.match(/^\d{5}\s\d{5}$/)) return false;
                // Ignore ASS/3 if it was already used as Invoice#
                if (detectedInv && l.includes(detectedInv) && l.length < detectedInv.length + 15) return false;
                return true;
            });

            inpItems.value = filteredItems.slice(0, 15).join('\n');
            
            document.getElementById('confidence-badge').innerText = "Review Data";
            document.getElementById('confidence-badge').className = "text-xs px-2 py-1 rounded-full bg-blue-600 text-white animate-pulse";
        }
        
        // --- Form Logic ---
        clearBtn.onclick = resetPreview;
        
        function resetPreview(e) {
            if (e) e.stopPropagation();
            previewImg.src = '';
            fileInput.value = '';
            dropIdle.classList.remove('hidden');
            dropPreview.classList.add('hidden');
            extractionCard.classList.add('opacity-50', 'pointer-events-none');
        }

        // --- CRUD ---
        btnAdd.onclick = async () => {
            if (!inpTotal.value || !inpDate.value) {
                alert('Please check Date and Total Amount');
                return;
            }
            
            const payload = {
                invoice_number: inpInvNum.value,
                invoice_date: inpDate.value,
                total_amount: parseFloat(inpTotal.value),
                line_items: inpItems.value.split('\n'), // Store as array of strings
                // image_path: ... (we could upload the file, but for "small app" maybe skip file storage or convert dataurl?)
                // skipping image storage for now to keep it "small" and fast
            };
            
            try {
                btnAdd.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                const res = await fetch('invoices_api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                const data = await res.json(); // Read the response body once

                if (res.ok) { // Check if HTTP status is 2xx
                    if (data.status === 'success') {
                        // Success
                        resetPreview();
                        // Clear form
                        inpInvNum.value = '';
                        inpDate.value = '';
                        inpTotal.value = '';
                        inpItems.value = '';
                        
                        fetchInvoices(); // Reload table
                    } else {
                        alert('Error saving: ' + (data.message || 'Unknown error'));
                    }
                } else { // HTTP status is not 2xx
                    if (res.status === 409) { // Conflict, likely duplicate invoice number
                        alert('Error: ' + (data.message || 'Invoice number already exists.'));
                    } else {
                        alert('Failed to save invoice: ' + (data.message || 'An unexpected error occurred.'));
                    }
                }
            } catch(e) {
                console.error(e);
                alert('Network error');
            } finally {
                btnAdd.innerHTML = '<i class="fas fa-plus mr-2"></i> Save Invoice';
            }
        };

        async function fetchInvoices() {
            try {
                const res = await fetch('invoices_api.php');
                const json = await res.json();
                
                const list = document.getElementById('invoice-list');
                const grandTotalEl = document.getElementById('grand-total');
                
                if (json.status === 'success') {
                    const data = json.data;
                    let grandTotal = 0;
                    
                    if (data.length === 0) {
                        list.innerHTML = '<div class="text-center py-10 text-gray-500 italic">No invoices found</div>';
                    } else {
                        list.innerHTML = data.map(inv => {
                            const amt = parseFloat(inv.total_amount);
                            grandTotal += amt;
                            // Parse items if string
                            // Note: PHP side might have sent object if we decoded it, or string.
                            // Our API sends decoded object if possible.
                            let items = inv.line_items; 
                            if (typeof items === 'string') {
                                try { items = JSON.parse(items); } catch(e) { items = []; }
                            }
                            const itemsOneLiner = Array.isArray(items) ? (items[0] + (items.length > 1 ? ` + ${items.length-1} more` : '')) : '';

                            return `
                                <div class="invoice-group border border-gray-100 rounded-lg overflow-hidden transition-all duration-300">
                                    <div class="invoice-row grid grid-cols-12 gap-4 items-center bg-white p-3 hover:bg-gray-50 transition group text-sm" onclick="this.parentElement.classList.toggle('row-expanded')">
                                        <div class="col-span-2 text-gray-600">${inv.invoice_date}</div>
                                        <div class="col-span-3 font-mono text-red-600 font-medium truncate" title="${inv.invoice_number}">${inv.invoice_number || '-'}</div>
                                        <div class="col-span-4 text-gray-400 truncate text-xs">${itemsOneLiner}</div>
                                        <div class="col-span-2 text-right font-bold text-green-600">₹${amt.toFixed(2)}</div>
                                        <div class="col-span-1 text-center">
                                            <button onclick="event.stopPropagation(); deleteInvoice(${inv.id})" class="text-red-500 hover:text-red-700 transition"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                    <div class="details-row grid grid-cols-12 p-4 border-t border-gray-50 animate-slide-down">
                                        <div class="col-span-12">
                                            <div class="text-[10px] uppercase tracking-wider text-gray-400 mb-2 font-bold">Line Items Detail</div>
                                            <div class="space-y-1">
                                                ${Array.isArray(items) ? items.map(line => `<div class="text-xs text-gray-700 py-1 border-b border-gray-50 last:border-0">${line}</div>`).join('') : '<div class="text-xs text-gray-500">No items available</div>'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    }
                    
                    grandTotalEl.innerText = grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
                }
            } catch(e) {
                console.error(e);
            }
        }

        async function deleteInvoice(id) {
            if (!confirm('Delete this invoice?')) return;
            try {
                 const res = await fetch('invoices_api.php?id=' + id, { method: 'DELETE' });
                 const json = await res.json();
                 if (json.status === 'success') fetchInvoices();
                 else alert(json.message);
            } catch(e) {
                alert('Error deleting');
            }
        }

    </script>
</body>
</html>
