// Transaction Scanner - Standalone Script
// This file can be loaded independently and will initialize when ready

(function () {
    'use strict';

    console.log('[Scanner] Standalone script loaded');

    function initTransactionScanner() {
        console.log('[Scanner] Attempting initialization...');

        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const processingMsg = document.getElementById('processing-msg');
        const scannerResults = document.getElementById('scanner-results');
        const txnTbody = document.getElementById('txn-tbody');
        const dropStatus = document.getElementById('drop-status');
        const btnPasteManual = document.getElementById('btn-paste-manual');

        if (!dropZone || !fileInput) {
            console.warn('[Scanner] Elements not found yet, will retry...');
            return false;
        }

        console.log('[Scanner] All elements found! Attaching events...');

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
            console.log('[Scanner] File selected');
            if (e.target.files.length > 0) {
                processImage(e.target.files[0]);
            }
        });

        // Global Paste Handling
        document.addEventListener('paste', (e) => {
            console.log('[Scanner] Paste detected');
            const items = (e.clipboardData || e.originalEvent.clipboardData).items;
            for (const item of items) {
                if (item.type.indexOf("image") !== -1) {
                    console.log('[Scanner] Image in paste, processing...');
                    processImage(item.getAsFile());
                }
            }
        });

        // Drag & Drop
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.background = '#eff6ff'; });
        dropZone.addEventListener('dragleave', () => { dropZone.style.background = '#f8faff'; });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.background = '#f8faff';
            console.log('[Scanner] Drop detected');
            if (e.dataTransfer.files.length > 0) {
                processImage(e.dataTransfer.files[0]);
            }
        });

        async function processImage(imageFile) {
            if (!imageFile) { console.error('[Scanner] No image file'); return; }
            console.log('[Scanner] Processing:', imageFile.name || 'Pasted Image');

            if (typeof Tesseract === 'undefined') {
                alert("Tesseract library failed to load. Please reload the page.");
                return;
            }

            if (processingMsg) processingMsg.style.display = 'block';

            try {
                console.log('[Scanner] Starting Tesseract with optimized config...');
                const result = await Tesseract.recognize(imageFile, 'eng', {
                    logger: m => {
                        if (m.status === 'recognizing text' && processingMsg) {
                            processingMsg.innerHTML = `<i class="fas fa-sync fa-spin"></i> Reading: ${Math.round(m.progress * 100)}%`;
                        }
                    },
                    // PSM 6 = Assume a uniform block of text (works well for payment screenshots)
                    // PSM 3 = Fully automatic page segmentation (default)
                    tessedit_pageseg_mode: Tesseract.PSM.SINGLE_BLOCK
                });

                const text = result.data.text;
                console.log('[Scanner] OCR Success! Text length:', text ? text.length : 0);

                const debugEl = document.getElementById('debug-text');
                if (debugEl) debugEl.textContent = text || "--- NO TEXT FOUND ---";

                if (!text || text.trim().length === 0) {
                    alert("OCR finished but no text was found in the image.");
                } else {
                    extractDetails(text);
                }
            } catch (err) {
                console.error('[Scanner] OCR Error:', err);
                alert("Scanner Error: " + err.message);
            } finally {
                if (processingMsg) {
                    processingMsg.style.display = 'none';
                    processingMsg.innerHTML = `<i class="fas fa-sync fa-spin"></i> Reading OCR...`;
                }
                if (dropStatus) dropStatus.style.opacity = '1';
            }
        }

        function extractDetails(text) {
            console.log('[Scanner] Extracting details...');

            // Amount - handle various formats
            let amountMatch = text.match(/(?:₹|Rs\.?|INR)\s*([\d,]+(?:\.\d{1,2})?)/i);

            // Fallback: Find all numbers and pick the most likely amount
            if (!amountMatch) {
                console.log('[Scanner] Primary amount match failed, trying smart fallback...');

                // Find all numbers in the text
                const allNumbers = text.match(/\b\d+\b/g);
                if (allNumbers) {
                    // Convert to integers and filter to reasonable payment amounts
                    const reasonableAmounts = allNumbers
                        .map(n => parseInt(n))
                        .filter(n => {
                            // Exclude transaction IDs (very long), dates (4 digits starting with 20), 
                            // single/double digits, and unreasonably large amounts
                            if (n < 10) return false;  // Too small
                            if (n > 999999) return false;  // Too large or likely a transaction ID
                            if (n >= 2000 && n <= 2099) return false;  // Likely a year
                            if (n.toString().length > 8) return false;  // Likely transaction ID
                            return true;
                        });

                    if (reasonableAmounts.length > 0) {
                        // Pick the first reasonable amount (usually the payment amount appears early)
                        const foundAmount = reasonableAmounts[0];
                        amountMatch = [foundAmount.toString(), foundAmount.toString()];
                        console.log('[Scanner] Smart fallback found:', foundAmount, 'from candidates:', reasonableAmounts);
                    }
                }
            }

            let amount = "-";
            if (amountMatch && amountMatch[1]) {
                const cleanAmount = amountMatch[1].replace(/,/g, '');
                amount = "₹" + cleanAmount;
                console.log('[Scanner] Amount extracted:', amount);
            } else {
                console.warn('[Scanner] No amount found in text');
            }

            const txnIdMatch = text.match(/(?:Txn|Transaction|Ref|UTR|Id)\s*(?:ID|No|Number)?\s*[:.-]?\s*([A-Z0-9]{10,})/i);
            const dateMatch = text.match(/(\d{1,2}\s[A-Za-z]{3}(?:\s+|,?\s*)\d{2,4})|(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})/);
            const payeeIdMatch = text.match(/([a-zA-Z0-9.-]+@[a-zA-Z]{3,})/i) || text.match(/UPI\s*ID\s*[:.-]?\s*([a-zA-Z0-9.-]+@[a-zA-Z]{3,})/i);

            // Date formatting - convert to "1 Jan 2026" format
            let formattedDate = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            if (dateMatch && dateMatch[0]) {
                try {
                    const rawDate = dateMatch[0].trim();
                    let parsedDate;

                    // Handle "5 Jan 2025" or "5 Jan, 2025" format
                    if (/\d{1,2}\s[A-Za-z]{3}/i.test(rawDate)) {
                        parsedDate = new Date(rawDate.replace(',', ''));
                    }
                    // Handle "05-01-2025" or "5/1/2025" format
                    else if (/\d{1,2}[/-]\d{1,2}[/-]\d{2,4}/.test(rawDate)) {
                        const parts = rawDate.split(/[/-]/);
                        const day = parseInt(parts[0]);
                        const month = parseInt(parts[1]) - 1;
                        const year = parts[2].length === 2 ? 2000 + parseInt(parts[2]) : parseInt(parts[2]);
                        parsedDate = new Date(year, month, day);
                    }

                    if (parsedDate && !isNaN(parsedDate.getTime())) {
                        formattedDate = parsedDate.toLocaleDateString('en-GB', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric'
                        });
                    }
                } catch (e) {
                    console.warn('[Scanner] Date parsing failed:', e);
                }
            }

            const lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 2);
            let payee = "-";
            let payer = "-";

            const paidToIdx = lines.findIndex(l => l.match(/Paid\s*to|To:|Paying/i));
            if (paidToIdx !== -1) {
                if (lines[paidToIdx].match(/Paid\s*to(?!\s*$)/i)) payee = lines[paidToIdx].replace(/Paid\s*to|To:|Paying/i, '').trim();
                else if (lines[paidToIdx + 1]) payee = lines[paidToIdx + 1];
            } else if (lines[0] && !lines[0].match(/Status|Success|Payment|Paid/i)) {
                payee = lines[0];
            }

            const fromIdx = lines.findIndex(l => l.match(/From:|Paid\s*by|Debited\s*from/i));
            if (fromIdx !== -1) {
                if (lines[fromIdx].match(/Paid\s*by(?!\s*$)|From:(?!\s*$)/i)) payer = lines[fromIdx].replace(/From:|Paid\s*by|Debited\s*from/i, '').trim();
                else if (lines[fromIdx + 1]) payer = lines[fromIdx + 1];
            }

            const data = {
                date: formattedDate,
                payer: payer.substring(0, 30),
                payee: payee.substring(0, 30),
                payeeId: payeeIdMatch ? (payeeIdMatch[1] || payeeIdMatch[0]) : "-",
                amount: amount,
                txnId: txnIdMatch ? (txnIdMatch[1] || txnIdMatch[0]) : "-"
            };

            appendRow(data);
        }

        function appendRow(data) {
            console.log('[Scanner] Appending row...');

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
            if (txnTbody) {
                txnTbody.prepend(row);
                scannerResults.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                console.log('[Scanner] Row added successfully!');
            }
        }

        // Expose to window
        window.clearTransactions = () => {
            console.log('[Scanner] Clearing transactions...');
            if (txnTbody) {
                txnTbody.innerHTML = '<tr id="no-data-msg"><td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8; font-style: italic;">No transactions scanned yet</td></tr>';
            }
            const debugEl = document.getElementById('debug-text');
            if (debugEl) debugEl.textContent = '';
        };

        console.log('[Scanner] Initialization complete!');
        return true;
    }

    // Try to initialize, retry if elements aren't ready
    function tryInit() {
        if (initTransactionScanner()) {
            console.log('[Scanner] Successfully initialized');
        } else {
            console.log('[Scanner] Retrying in 500ms...');
            setTimeout(tryInit, 500);
        }
    }

    // Start initialization when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tryInit);
    } else {
        tryInit();
    }

})();
