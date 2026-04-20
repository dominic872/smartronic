<?php
ob_start();
require_once '../auth.php'; // Assuming we create auth.php in admin folder
requireSmartPageAccess('quote', $role, $authPages);

// Check if user is admin
$isAdmin = isset($_COOKIE['auth_role']) && $_COOKIE['auth_role'] === 'admin';
$isMarket = isset($_COOKIE['auth_role']) && $_COOKIE['auth_role'] === 'market';
$allowedSmartPages = getAllowedSmartPages($role, $authPages);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>CCTV Requirement Form — Quotation</title>

  <!-- Optional materialize for base look -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
  
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Local stylesheet -->
  <link href="styles.css?v=147" rel="stylesheet">
</head>
<body class="grey lighten-4<?php echo (!isset($_COOKIE['auth_role']) || $_COOKIE['auth_role'] !== 'admin') ? ' not-admin' : ' is-admin'; ?><?php echo ($isMarket && !$isAdmin) ? ' is-market' : ''; ?>">

  <section class="crf-form-wrapper">
    <div class="container">
      <h5 class="center-align page-title">CCTV Requirement Form — Quotation</h5>

      <div class="crf-container">
        
        <!-- LEFT COLUMN: Form Selection -->
        <div class="crf-left-col">
          <form id="cctv-requirement-form" class="crf-form" novalidate>
            <!-- Cameras count -->
            <div class="crf-form-row bg-camera">
              <div class="crf-form-col">
                <div class="slider-input-wrapper">
                  <input type="range" id="num-cameras" class="range-slider" min="1" max="32" value="6">
                  <input type="number" id="num-cameras-input" class="slider-input" min="1" max="32" value="6">
                </div>
              </div>
            </div>

           
            <div class="crf-form-row">
              <div class="crf-form-col">
                <label>Type of Camera set Needed</label>

                <div id="category-buttons" class="button-row"></div>
                 <label>Select the Brand</label>
                <div id="brand-buttons" class="button-row" style="display:none;"></div>
                 <label>Select the Resolution</label>
                <div id="mp-buttons" class="button-row" style="display:none;"></div>
                
                <!-- HDD Section with dynamic title -->
                <div class="hdd-section-header" style="display:none;" id="hdd-section-header">
                  <span class="hdd-section-title" id="hdd-section-title">Preferred HDDs</span>
                  <span class="hdd-toggle-link" id="hdd-toggle-link">Switch to Brandwise HDDs</span>
                </div>

                <div id="preferred-hdd" class="hdd-section"></div>
                <div id="brandwise-hdd" class="hdd-section" style="display:none;"></div>


                <!-- Items (recorder + cameras) appear here as buttons -->
                <div id="component-options" style="display:none; margin-top:12px;"></div>

                <input type="hidden" id="selected-category" name="selected-category" value="">
                <input type="hidden" id="selected-brand" name="selected-brand" value="">
                <input type="hidden" id="selected-mp" name="selected-mp" value="">
                <input type="hidden" id="selected-component" name="selected-component" value="">
                <input type="hidden" id="selected-model" name="selected-model" value="">
                <small>Step 1: Category → Step 2: Brand → Step 3: MP → Choose Item</small>
              </div>
            </div>

            <!-- Optional Accessories quick add (from data.items) -->
            <div class="crf-form-row">
              <div class="crf-form-col">
                <label>Accessories / Misc (optional)</label>
                <div id="accessories" class="button-row"></div>
              </div>
            </div> 
          </form>
        </div>

        <!-- RIGHT COLUMN: Customer Info + Message + Summary -->
        <div class="crf-right-col">
          <!-- Sticky Total Summary -->
          <div class="sticky-totals" id="sticky-totals">
            <div class="sticky-totals-grid">
              <div class="row no-margin total-row">
                <span class="amount-label">MIN</span>
                <span class="amount-large" id="sticky-total">₹0</span>
                <span class="half-diff-total" id="sticky-half-diff-total">₹0</span>
                <span class="original-max-display" id="original-max-row" style="display:none;"></span>
              </div>
              <div class="row no-margin" id="sticky-profit-row">
                <span class="amount-label" id="sticky-profit-label">MAX</span>
                <span class="amount-large" id="sticky-profit-amount">₹0</span>
                <span class="difference-amount" id="difference-amount">₹0</span>
              </div>
              <div class="row no-margin discount-row">
                <span class="amount-label">Add. Disc</span>
                <select class="discount-dropdown" id="discount-dropdown">
                  <option value="0">0</option>
                  <option value="500">₹500</option>
                  <option value="1000">₹1000</option>
                  <option value="1500">₹1500</option>
                  <option value="2000">₹2000</option>
                </select>
                <?php if ($isAdmin): ?>
                <div class="sticky-profit-margin-display">
                  <i class="fas fa-eye" id="sticky-profit-eye-icon" style="cursor: pointer; font-size: 12px; color: #2196f3;"></i>
                  <span id="sticky-profit-margin-amount">•••</span>
                </div>
                <?php endif; ?>
              </div>
              <div class="row no-margin percentage-row">
                <span class="amount-label">PLUS %</span>
                <div class="percentage-input-wrapper" style="display: flex; align-items: center; justify-content: center;">
                  <input type="number" id="percentage-amount" value="0" style="width: 50px; text-align: center; border: none; border-radius: 5px; padding: 0; height: 30px; font-size: 20px; color: #9c27b0; font-weight: bold; margin: 0;"/>
                  <span class="percentage-symbol" style="font-size: 20px; color: #9c27b0; font-weight: bold; margin-left: 2px;">%</span>
                </div>
                <div class="percentage-controls">
                  <button type="button" class="percentage-btn" id="percentage-minus">-</button>
                  <button type="button" class="percentage-btn" id="percentage-plus">+</button>
                </div>
              </div>

            </div>
            <!-- Selected Tags: below totals and sticky as part of sticky block -->
            <div id="selected-tags" class="selected-tags" aria-live="polite"></div>
          </div>
          
          <!-- Customer Name -->
          <div class="crf-form-row">
            <div class="crf-form-col">
              <div class="slider-input-wrapper input-with-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="input-icon"><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"/></svg>
                <input type="text" id="customer-name" class="input" placeholder="Enter customer name">
              </div>
            </div>
          </div>

          <!-- WhatsApp number -->
          <div class="crf-form-row">
            <div class="crf-form-col">
              <div class="slider-input-wrapper input-with-icon">
                <svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 448 512" class="input-icon"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"></path></svg>
                <input type="tel" id="num-whatsapp" class="input" placeholder="Enter WhatsApp number">
              </div>
            </div>
          </div>

          <!-- WhatsApp Message Preview -->
          <div class="crf-form-row">
            <div class="crf-form-col">
              <div class="textarea-header">
                <label for="whatsapp-preview">WhatsApp Message Preview</label>
                <span style="margin-left: auto; display: flex; align-items: center; gap: 10px;">
                  <i class="fas fa-file-pdf textarea-pdf-icon" id="textarea-pdf-icon" title="Generate PDF" style="cursor: pointer; color: #dc2626; font-size: 16px; font-weight: bold; padding: 5px; border: 1px solid #dc2626; border-radius: 4px; background: #fef2f2;"></i>
                  <button id="textarea-pdf-button" title="Generate PDF" style="cursor: pointer; color: white; font-size: 12px; font-weight: bold; padding: 5px 10px; border: 1px solid #dc2626; border-radius: 4px; background: #dc2626; display: none;">PDF</button>
                  <i class="fas fa-copy textarea-copy-icon" id="textarea-copy-icon" title="Copy to clipboard" style="cursor: pointer; color: #667eea;"></i>
                  <i class="fas fa-edit textarea-edit-icon" id="textarea-toggle-icon" title="Click to edit message"></i>
                </span>
              </div>
              <div class="textarea-wrapper">
                <textarea id="whatsapp-preview" class="input" rows="15" placeholder="Message will appear here..." style="background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; border: 2px solid #e9ecef; border-radius: 8px; padding: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: border-color 0.3s ease;"></textarea>
              </div>
            </div>
          </div>

          <?php if ($isAdmin): ?>
          <aside id="summary-panel" class="summary-panel">
            <h6>Live Price Summary</h6>
            <div id="camera-status-bar" class="camera-status-bar">
              <span class="status-label">Cameras:</span>
              <span id="camera-selected-count" class="status-count">0</span>
              <span class="status-separator">/</span>
              <span id="camera-required-count" class="status-count">6</span>
              <span class="status-label">selected</span>
            </div>
            <div id="summary-items" class="summary-items"></div>

            <div class="summary-totals">
              <div class="row no-margin">
                <div class="col s7">Subtotal</div>
                <div class="col s5 right-align" id="summary-subtotal">₹0</div>
              </div>
              <div class="row no-margin">
                <div class="col s7">GST (18%)</div>
                <div class="col s5 right-align" id="summary-gst">₹0</div>
              </div>
              <div class="row no-margin total-row">
                <div class="col s7"><strong>Total</strong></div>
                <div class="col s5 right-align" id="summary-total"><strong>₹0</strong></div>
              </div>
            </div>

            <div class="summary-actions">
              <button id="btn-clear" class="btn-flat">Clear selection</button>
            </div>
          </aside>
          <?php endif; ?>

          <?php if (!$isAdmin && $isMarket): ?>
          <aside id="market-summary-panel" class="summary-panel">
            <h6>Product Summary</h6>
            <div id="market-camera-status-bar" class="camera-status-bar">
              <span class="status-label">Cameras:</span>
              <span id="market-camera-selected-count" class="status-count">0</span>
              <span class="status-separator">/</span>
              <span id="market-camera-required-count" class="status-count">6</span>
              <span class="status-label">selected</span>
            </div>
            <div id="market-summary-items" class="summary-items"></div>
            <div class="summary-actions">
              <button id="market-btn-clear" class="btn-flat">Clear selection</button>
            </div>
          </aside>
          <?php endif; ?>

        </div>

      </div>
    </div>
  </section>

  <!-- Floating WhatsApp Button -->
  <div id="wp-selected-price-indicator" class="wp-selected-price-indicator" aria-live="polite"></div>
  <button type="button" id="btn-generate-wp" class="btn-cta btn-floating" disabled>
    <i class="fas fa-paper-plane"></i>
  </button>

  <!-- External data + script -->
  <script>
    function mountQuoteFloatingMenu() {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('floating_menu') === '0') return true;
      if (!window.SmartFloatingMenu || typeof window.SmartFloatingMenu.mount !== 'function') return false;
      const allowedPages = <?php echo json_encode($allowedSmartPages); ?>;
      const links = [];
      if (allowedPages.includes('lead')) {
        links.push({
          label: 'Lead List',
          icon: 'fas fa-users',
          color: '#e11d48',
          url: `${window.location.origin}/admin_v2/smart/lead_list_enhanced.php`
        });
      }
      if (allowedPages.includes('install')) {
        links.push({
          label: 'Installs',
          icon: 'fa-solid fa-screwdriver-wrench',
          color: '#2563eb',
          url: `${window.location.origin}/admin_v2/smart/installs.php`
        });
      }
      if (allowedPages.includes('quote')) {
        links.push({
          label: 'Quote Tool',
          icon: 'fas fa-calculator',
          color: '#6f42c1',
          url: `${window.location.origin}/admin_v2/smart/quote.php`
        });
      }
      window.SmartFloatingMenu.mount({
        links,
        baseBottom: 140,
        step: 60
      });
      return true;
    }

    function initQuoteFloatingMenu() {
      if (mountQuoteFloatingMenu()) return;
      const s = document.createElement('script');
      s.src = '/admin_v2/js/floating_icon_menu.js';
      s.onload = () => { mountQuoteFloatingMenu(); };
      s.onerror = () => {
        const fallback = document.createElement('script');
        fallback.src = '../js/floating_icon_menu.js';
        fallback.onload = () => { mountQuoteFloatingMenu(); };
        document.head.appendChild(fallback);
      };
      document.head.appendChild(s);
      let tries = 0;
      const timer = setInterval(() => {
        tries += 1;
        if (mountQuoteFloatingMenu() || tries >= 20) clearInterval(timer);
      }, 150);
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initQuoteFloatingMenu);
    } else {
      initQuoteFloatingMenu();
    }
  </script>
  <script>
    // Load data from JSON 
    fetch('/admin_v2/smart/data.json?v=157', { cache: 'no-store' })
      .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
          throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
      })
      .then(jsonData => {
        console.log('Data loaded successfully:', jsonData);
        // Make the data available globally
        window.data = jsonData;
        // Now load the scripts that depend on this data
        // Load scripts.js with absolute path, then fall back to relative if needed
        const loadScript = (src, isFallback = false) => {
          const s = document.createElement('script');
          s.src = src;
          s.onload = function() {
            console.log('scripts.js loaded successfully from', src);
            setTimeout(function() {
              if (typeof window.boot === 'function') {
                console.log('Calling boot function after data and script load');
                window.boot();
              } else {
                console.error('Boot function not found even after script load');
                setTimeout(function() {
                  if (typeof window.boot === 'function') {
                    console.log('Calling boot function on second attempt');
                    window.boot();
                  } else {
                    console.error('Boot function still not found after second attempt');
                  }
                }, 1000);
              }
            }, 100);
          };
          s.onerror = function() {
            if (!isFallback) {
              console.warn('Primary load failed, trying relative scripts.js');
              loadScript('scripts.js?v=158', true);
            } else {
              console.error('Failed to load scripts.js after fallback');
            }
          };
          document.head.appendChild(s);
        };

        // Try absolute first, then fallback relative
        loadScript('/admin_v2/smart/scripts.js?v=158');
      })
      .catch(error => {
        console.error('Failed to load data.json:', error);
        // Show error to user
        alert('Failed to load product data. Please refresh the page.');
      });
  </script>

</body>
</html>
