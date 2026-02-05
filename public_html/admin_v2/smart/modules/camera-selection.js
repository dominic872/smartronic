/**
 * Camera Selection Module - Handles camera selection and quantity management
 */
(function(window) {
  const GST_RATE = 0.18;

  function attachCameraButtonLogic(btn, cam, category, brand, mp, callbacks) {
    function getTotalCameraCount() {
      return parseInt(document.getElementById('num-cameras-input').value || 1);
    }

    function selectCamera() {
      const totalCameras = parseInt(document.getElementById('num-cameras-input').value || 1, 10);
      const id = `cam::${category}::${brand}::${mp}::${cam.label}`;
      const uiState = window.SmartronicUIState;
      const selectedItems = uiState.getSelectedItems();

      // Check if this camera is already selected
      const existingIndex = selectedItems.findIndex(item => 
        item.meta?.component === 'Camera' && 
        item.meta.brand === brand && 
        item.meta.mp === mp &&
        item.meta.cameraLabel === cam.label
      );
      
      if (existingIndex !== -1) {
        // Camera already selected, remove it (deselect)
        selectedItems.splice(existingIndex, 1);
        btn.classList.remove('selected');
        const originalText = btn.dataset.originalLabel || btn.textContent.replace(/\s*\(\d+\)$/, '');
        btn.textContent = originalText;
      } else {
        // Add this camera with quantity 1 initially
        selectedItems.push({
          id,
          label: `${category} ${brand} ${mp} - Camera: ${cam.label}`,
          price: window.SmartronicUtils.parsePrice(cam.value),
          qty: 1,
          meta: { component: 'Camera', raw: cam, brand, mp, category, cameraLabel: cam.label },
        });
        btn.classList.add('selected');
        window.SmartronicUtils.updateCameraButtonText(btn, 1);
      }

      if (callbacks?.onSelectionChange) callbacks.onSelectionChange();
    }

    // Data & flags
    btn._camData = { ...cam, brand, mp };
    btn.dataset.cameraLabel = cam.label || 'Camera';
    btn.dataset.originalLabel = cam.label || 'Camera';
    btn._hasListener = true;
    btn._brand = brand;
    btn._mp = mp;

    // Click -> select camera (radio button mode)
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      selectCamera();
    });
  }

  function updateCameraButtonText(btn, qty) {
    const originalText = btn.dataset.originalLabel || btn.textContent.replace(/\s*\(\d+\)$/, '');
    if (!btn.dataset.originalLabel) btn.dataset.originalLabel = originalText;
    btn.textContent = `${originalText} (${qty})`;
  }

  function updateAllCameraSliders() {
    // Placeholder for future slider updates
    console.log('updateAllCameraSliders called');
  }

  // Export functions
  window.SmartronicCameraSelection = {
    attachCameraButtonLogic,
    updateCameraButtonText,
    updateAllCameraSliders,
    GST_RATE
  };
})(window);
