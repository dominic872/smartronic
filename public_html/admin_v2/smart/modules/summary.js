/**
 * Summary Module - Handles summary display, pricing calculations, and status bar
 */
(function(window) {
  const GST_RATE = 0.18;

  function updateSummary(summaryItemsEl, summarySubtotalEl, summaryGstEl, summaryTotalEl, btnWP) {
    const uiState = window.SmartronicUIState;
    const utils = window.SmartronicUtils;
    const selectedItems = uiState.getSelectedItems();
    const { currentCategory, currentBrand, currentMP } = uiState.getCurrentState();
    
    // Filter items to only show those from the current context
    const contextFilteredItems = uiState.filterItemsByContext(selectedItems);
    
    summaryItemsEl.innerHTML = '';
    contextFilteredItems.forEach(item => {
      const row = document.createElement('div');
      row.className = 'summary-item';
      
      // Create quantity controls for cameras
      let qtyControls = '';
      if (item.meta?.component === 'Camera') {
        qtyControls = `
          <div class="camera-qty-controls">
            <button class="qty-btn minus-btn" data-id="${item.id}">-</button>
            <span class="qty-display">${item.qty || 1}</span>
            <button class="qty-btn plus-btn" data-id="${item.id}">+</button>
          </div>
        `;
      } else {
        qtyControls = `<div class="small">Qty: ${item.qty || 1}</div>`;
      }
      
      row.innerHTML = `
        <div class="meta">
          <div style="font-weight:700;">${item.label}</div>
          ${qtyControls}
        </div>
        <div class="price">${item.price ? utils.formatINR(item.price * (item.qty || 1)) : 'Price on Inquiry'}</div>
      `;
      summaryItemsEl.appendChild(row);
    });

    // Add event listeners to quantity buttons
    document.querySelectorAll('.minus-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const item = selectedItems.find(i => i.id === id);
        if (item && item.qty > 1) {
          item.qty -= 1;
          updateSummary(summaryItemsEl, summarySubtotalEl, summaryGstEl, summaryTotalEl, btnWP);
          window.SmartronicCore.saveSessionData(
            currentCategory, currentBrand, currentMP,
            parseInt(document.getElementById('num-cameras-input').value || 6),
            selectedItems
          );
          
          if (item.meta?.component === 'Camera') {
            const cameraButtons = document.querySelectorAll('.camera-btn');
            cameraButtons.forEach(btn => {
              if (btn.dataset.cameraLabel === item.meta.cameraLabel) {
                utils.updateCameraButtonText(btn, item.qty);
              }
            });
          }
        }
      });
    });

    document.querySelectorAll('.plus-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const item = selectedItems.find(i => i.id === id);
        if (item) {
          item.qty += 1;
          updateSummary(summaryItemsEl, summarySubtotalEl, summaryGstEl, summaryTotalEl, btnWP);
          window.SmartronicCore.saveSessionData(
            currentCategory, currentBrand, currentMP,
            parseInt(document.getElementById('num-cameras-input').value || 6),
            selectedItems
          );
          
          if (item.meta?.component === 'Camera') {
            const cameraButtons = document.querySelectorAll('.camera-btn');
            cameraButtons.forEach(btn => {
              if (btn.dataset.cameraLabel === item.meta.cameraLabel) {
                utils.updateCameraButtonText(btn, item.qty);
              }
            });
          }
        }
      });
    });

    updateCameraStatusBar();

    const subtotal = contextFilteredItems.reduce((acc, it) => acc + (it.price ? it.price * (it.qty || 1) : 0), 0);
    const gst = subtotal * GST_RATE;
    const total = subtotal + gst;

    summarySubtotalEl.textContent = utils.formatINR(subtotal);
    summaryGstEl.textContent = utils.formatINR(gst);
    summaryTotalEl.textContent = utils.formatINR(total);
    btnWP.disabled = contextFilteredItems.length === 0;
  }

  function updateCameraStatusBar() {
    const requiredCount = parseInt(document.getElementById('num-cameras-input')?.value || 6, 10);
    const uiState = window.SmartronicUIState;
    const selectedItems = uiState.getSelectedItems();
    const { currentCategory, currentBrand, currentMP } = uiState.getCurrentState();
    
    // Only count cameras from the current context
    const selectedCount = selectedItems
      .filter(item => 
        item.meta?.component === 'Camera' && 
        item.meta?.category === currentCategory && 
        item.meta?.brand === currentBrand && 
        item.meta?.mp === currentMP)
      .reduce((sum, item) => sum + (item.qty || 0), 0);
    
    const requiredEl = document.getElementById('camera-required-count');
    const selectedEl = document.getElementById('camera-selected-count');
    
    if (requiredEl && selectedEl) {
      const oldSelected = parseInt(selectedEl.textContent || '0');
      if (oldSelected !== selectedCount) {
        selectedEl.classList.add('updated');
        setTimeout(() => selectedEl.classList.remove('updated'), 300);
      }
      
      requiredEl.textContent = requiredCount;
      selectedEl.textContent = selectedCount;
      
      const statusBar = document.getElementById('camera-status-bar');
      if (statusBar) {
        if (selectedCount === requiredCount && selectedCount > 0) {
          statusBar.style.background = 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)';
        } else if (selectedCount > requiredCount) {
          statusBar.style.background = 'linear-gradient(135deg, #eb3349 0%, #f45c43 100%)';
        } else {
          statusBar.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        }
      }
    }
  }

  function buildWhatsAppMessage() {
    const utils = window.SmartronicUtils;
    const uiState = window.SmartronicUIState;
    const selectedItems = uiState.getSelectedItems();
    const { currentCategory, currentBrand, currentMP } = uiState.getCurrentState();
    
    const camCount = parseInt(document.getElementById('num-cameras-input')?.value || 0, 10);
    let msg = [];
    msg.push('SmArtronic CCTV Quote');
    msg.push('------------------------');
    msg.push('Cameras required: ' + camCount);
    msg.push('');
    
    const contextFilteredItems = uiState.filterItemsByContext(selectedItems);
    
    contextFilteredItems.forEach((it, idx) => {
      const priceTag = it.price ? ('₹' + Math.round(it.price).toLocaleString('en-IN')) : 'Price on Inquiry';
      msg.push(`${idx + 1}. ${it.label} — ${priceTag}${it.qty > 1 ? ' (x' + it.qty + ')' : ''}`);
    });
    
    const subtotal = contextFilteredItems.reduce((acc, it) => acc + (it.price ? it.price * (it.qty || 1) : 0), 0);
    const gst = subtotal * GST_RATE;
    const total = subtotal + gst;
    msg.push('');
    msg.push('Subtotal: ' + utils.formatINR(subtotal));
    msg.push('GST (18%): ' + utils.formatINR(gst));
    msg.push('Total: ' + utils.formatINR(total));
    return encodeURIComponent(msg.join('\n'));
  }

  // Export functions
  window.SmartronicSummary = {
    updateSummary,
    updateCameraStatusBar,
    buildWhatsAppMessage,
    GST_RATE
  };
})(window);
