/* script.js - main logic with live summary & WhatsApp generator */
(function () {
  // ✅ Prevent any accidental form submits (Enter or default submit buttons)
  document.addEventListener("submit", (e) => e.preventDefault());

  /* ---------- WhatsApp Textarea Expand/Collapse ---------- */
  const textareaToggleIcon = document.getElementById('textarea-toggle-icon');
  const whatsappPreview = document.getElementById('whatsapp-preview');
  
  console.log('🎨 Textarea expand/collapse script loaded!');
  console.log('Icon element:', textareaToggleIcon);
  console.log('Textarea element:', whatsappPreview);
  
  if (textareaToggleIcon && whatsappPreview) {
    console.log('✅ Both elements found, attaching click handler');
    
    // Initially hide textarea
    whatsappPreview.classList.remove('visible');
    
    textareaToggleIcon.addEventListener('click', function() {
      console.log('🖱️ Edit icon clicked!');
      
      // Toggle visibility
      if (whatsappPreview.classList.contains('visible')) {
        whatsappPreview.classList.remove('visible');
        whatsappPreview.classList.remove('expanded');
        this.classList.remove('fa-compress');
        this.classList.add('fa-edit');
        this.title = 'Click to edit message';
        console.log('👁️ Hiding textarea');
      } else {
        whatsappPreview.classList.add('visible');
        this.classList.remove('fa-edit');
        this.classList.add('fa-compress');
        this.title = 'Click to hide';
        console.log('👁️ Showing textarea');
      }
    });
  } else {
    console.error('❌ Elements not found!', {textareaToggleIcon, whatsappPreview});
  }

  /* ---------- WhatsApp Textarea Copy to Clipboard ---------- */
  const textareaCopyIcon = document.getElementById('textarea-copy-icon');
  
  if (textareaCopyIcon && whatsappPreview) {
    textareaCopyIcon.addEventListener('click', function() {
      // Select the text in the textarea
      whatsappPreview.select();
      whatsappPreview.setSelectionRange(0, 99999); // For mobile devices
      
      // Copy the text to clipboard
      try {
        const successful = document.execCommand('copy');
        if (successful) {
          console.log('📋 Text copied to clipboard');
          // Show visual feedback
          const originalIcon = this.className;
          const originalTitle = this.title;
          this.classList.remove('fa-copy');
          this.classList.add('fa-check');
          this.title = 'Copied!';
          this.style.color = '#4CAF50'; // Green color for success
          
          // Reset icon after 2 seconds
          setTimeout(() => {
            this.classList.remove('fa-check');
            this.classList.add('fa-copy');
            this.title = originalTitle;
            this.style.color = '#667eea'; // Original color
          }, 2000);
        } else {
          console.error('❌ Failed to copy text');
        }
      } catch (err) {
        console.error('❌ Error copying text: ', err);
      }
      
      // Deselect the text
      window.getSelection().removeAllRanges();
    });
  } else {
    console.error('❌ Copy icon or textarea not found!', {textareaCopyIcon, whatsappPreview});
  }

  /* ---------- HDD Toggle Link ---------- */
  const hddToggleLink = document.getElementById('hdd-toggle-link');
  const hddSectionTitle = document.getElementById('hdd-section-title');
  const hddSectionHeader = document.getElementById('hdd-section-header');
  const preferredHddSection = document.getElementById('preferred-hdd');
  const brandwiseHddSection = document.getElementById('brandwise-hdd');
  
  let isPreferredHddActive = true;
  
  if (hddToggleLink) {
    hddToggleLink.addEventListener('click', function() {
      if (isPreferredHddActive) {
        // Switch to Brandwise
        preferredHddSection.style.display = 'none';
        brandwiseHddSection.style.display = 'block';
        hddSectionTitle.textContent = 'Brandwise HDDs';
        hddToggleLink.textContent = 'Switch to Preferred HDDs';
        isPreferredHddActive = false;
      } else {
        // Switch to Preferred
        preferredHddSection.style.display = 'block';
        brandwiseHddSection.style.display = 'none';
        hddSectionTitle.textContent = 'Preferred HDDs';
        hddToggleLink.textContent = 'Switch to Brandwise HDDs';
        isPreferredHddActive = true;
      }
    });
  }

  /* ---------- Elements ---------- */
  const whatsappInput = document.getElementById('num-whatsapp');
  const range = document.getElementById('num-cameras');
  const num = document.getElementById('num-cameras-input');

  // Note: Synchronization and status bar updates will be set up in boot()

  const categoryContainer = document.getElementById('category-buttons');
  const brandContainer = document.getElementById('brand-buttons');
  const mpContainer = document.getElementById('mp-buttons');
  const accessoriesContainer = document.getElementById('accessories');
  let selectedTagsEl = document.getElementById('selected-tags');

  const summaryItemsEl = document.getElementById('summary-items');
  const summarySubtotalEl = document.getElementById('summary-subtotal');
  const summaryGstEl = document.getElementById('summary-gst');
  const summaryTotalEl = document.getElementById('summary-total');

  const btnWP = document.getElementById('btn-generate-wp');
  const btnClear = document.getElementById('btn-clear');
  let originalBtnWPHtml = btnWP ? btnWP.innerHTML : '';
  
  // Discount and percentage controls
  let additionalDiscount = 0;
  let additionalPercentage = 0;
  
  const discountDropdown = document.getElementById('discount-dropdown');
  const discountAmount = document.getElementById('discount-amount');
  const percentageAmount = document.getElementById('percentage-amount');
  if (percentageAmount) {
    percentageAmount.inputmode = 'numeric'; // Set inputmode to numeric
  }
  const percentageMinus = document.getElementById('percentage-minus');
  const percentagePlus = document.getElementById('percentage-plus');
  
  // Discount dropdown change handler
  if (discountDropdown) {
    discountDropdown.addEventListener('change', function() {
      additionalDiscount = parseInt(this.value) || 0;
      updateSummary();
    });
  }
  
  // Percentage buttons
  if (percentageMinus) {
    percentageMinus.addEventListener('click', function() {
      // Allow lowering below 0 to reduce MAX (down to -100)
      additionalPercentage = Math.max(-100, (additionalPercentage || 0) - 1);
      const percentageAmountEl = document.getElementById('percentage-amount');
      if (percentageAmountEl) percentageAmountEl.value = Math.round(additionalPercentage);
      updateSummary();
      updateWhatsAppPreview(); // Ensure WhatsApp preview is updated
    });
  }
  
  if (percentagePlus) {
    percentagePlus.addEventListener('click', function() {
      // Allow raising up to 100
      additionalPercentage = Math.min(100, (additionalPercentage || 0) + 1);
      const percentageAmountEl = document.getElementById('percentage-amount');
      if (percentageAmountEl) percentageAmountEl.value = Math.round(additionalPercentage);
      updateSummary();
      updateWhatsAppPreview(); // Ensure WhatsApp preview is updated
    });
  }

  if (percentageAmount) {
    percentageAmount.addEventListener('input', function() {
      let value = parseInt(this.value, 10);
      if (isNaN(value)) {
        value = 0;
      }
      // Allow negative to reduce MAX; clamp to [-100, 100]
      value = Math.max(-100, Math.min(100, value));
      // Treat input as additional percentage markup directly
      additionalPercentage = value;
      console.log('Additional percentage input changed:', value);
      updateSummary();
      updateWhatsAppPreview(); // Ensure WhatsApp preview is updated
    });
    
    // Also listen for 'change' event to catch manual entry
    percentageAmount.addEventListener('change', function() {
      let value = parseInt(this.value, 10);
      if (isNaN(value)) {
        value = 0;
      }
      value = Math.max(-100, Math.min(100, value));
      additionalPercentage = value;
      console.log('Additional percentage changed (change event):', value);
      updateSummary();
      updateWhatsAppPreview(); // Ensure WhatsApp preview is updated
    });
  }

  const btnTogglePreferred = document.getElementById('btn-preferred-hdd');
  const btnToggleBrandwise = document.getElementById('btn-brandwise-hdd');

  const GST_RATE = 0.18;
  let selectedItems = [];

  /* ---------- Session Storage ---------- */
  const SESSION_KEY = 'smartronic_dvr_selections';

  /* ===== Auto-select on render via MutationObserver (BRAND/MP SAFE) ===== */

  let componentObserver = null;

  function ensureComponentObserver() {
    const container = document.getElementById("componentPopup");
    if (!container) return;

    if (componentObserver) componentObserver.disconnect();

    componentObserver = new MutationObserver(() => {
      autoSelectInPopup();
    });

    componentObserver.observe(container, { childList: true, subtree: true });
  }

  /** Run auto-select ONLY for the currently displayed brand/mp popup. */
  function autoSelectInPopup() {
    const container = document.getElementById("componentPopup");
    if (!container || container.style.display === "none") return;

    // Guard: run once per brand/mp render
    const key = currentCategory === 'NVR' ? `${currentCategory}|${currentBrand}` : `${currentCategory}|${currentBrand}|${currentMP}`;
    if (container._autoDoneKey === key) return;

    const camBtns = Array.from(container.querySelectorAll(".camera-btn"));
    const recBtns = Array.from(container.querySelectorAll(".recorder-btn:not(.disabled)"));

    const anyCamSelected = selectedItems.some(i =>
      i.meta?.component === "Camera" &&
      i.meta?.brand === currentBrand &&
      i.meta?.category === currentCategory &&
      (currentCategory === 'NVR' ? true : i.meta?.mp === currentMP)
    );
    const anyRecSelected = selectedItems.some(i =>
      i.meta?.component === "Recorder" &&
      i.meta?.brand === currentBrand &&
      i.meta?.category === currentCategory &&
      (currentCategory === 'NVR' ? true : i.meta?.mp === currentMP)
    );

    console.log('🔍 Auto-select check:', {
      currentBrand,
      currentMP,
      anyCamSelected,
      anyRecSelected,
      totalSelectedItems: selectedItems.length,
      selectedItemsDetails: selectedItems.map(i => ({component: i.meta?.component, brand: i.meta?.brand, mp: i.meta?.mp}))
    });

    // Only proceed when at least one of camera/recorder blocks exists
    if (camBtns.length === 0 && recBtns.length === 0) return;

    console.log("🟢 Observer sees components for", currentBrand, currentMP,
      { cameras: camBtns.length, recorders: recBtns.length, anyCamSelected, anyRecSelected });

    // 1) Recorder: Select the minimum required channel based on camera count
    if (!anyRecSelected && recBtns.length) {
      const cameraCount = parseInt(document.getElementById('num-cameras-input')?.value || 6, 10);
      
      // Determine required channel based on camera count
      let requiredChannel;
      if (cameraCount <= 4) requiredChannel = 4;
      else if (cameraCount <= 8) requiredChannel = 8;
      else if (cameraCount <= 16) requiredChannel = 16;
      else requiredChannel = 32;
      
      console.log('🎯 Auto-selecting recorder for', cameraCount, 'cameras - required channel:', requiredChannel + 'CH');
      
      // Find the recorder with the required channel count
      let targetRecorder = null;
      recBtns.forEach(btn => {
        const channel = parseInt(btn.dataset.channel);
        const isDisabled = btn.disabled || btn.classList.contains('disabled');
        console.log('  Recorder option:', btn.textContent, 'channel:', channel, 'disabled:', isDisabled);
        
        if (channel === requiredChannel && !isDisabled) {
          targetRecorder = btn;
        }
      });
      
      if (targetRecorder) {
        console.log(`⚙️ Auto-select recorder: ${targetRecorder.textContent} (${requiredChannel}CH for ${cameraCount} cameras)`);
        targetRecorder.click();
      } else {
        console.warn('⚠️ Required', requiredChannel + 'CH recorder not found or disabled');
      }
    } else {
      console.log('⏭️ Skipping recorder auto-select - anyRecSelected:', anyRecSelected, 'recBtns:', recBtns.length);
    }

    // 2) Camera: pick the lowest data-price (no reliance on _brand/_mp)
    console.log('Auto-select camera check: anyCamSelected=', anyCamSelected, 'camBtns.length=', camBtns.length);
    if (!anyCamSelected && camBtns.length) {
      // Clear all camera button states in current context before auto-selecting
      camBtns.forEach(btn => {
        btn.classList.remove("selected");
        updateCameraButtonText(btn, 0);
      });
      
      let lowestC = null, minValC = Infinity;
      camBtns.forEach(btn => {
        const v = parseFloat(btn.dataset.price) || Infinity;
        if (v < minValC) { minValC = v; lowestC = btn; }
      });

      if (lowestC) {
        const total = parseInt(document.getElementById('num-cameras-input')?.value || 6, 10);
        const camLabel = lowestC.dataset.cameraLabel || (lowestC._camData?.label) || "Camera";

        // Remove any existing cameras for this brand/mp context
        selectedItems = selectedItems.filter(i =>
          !(i.meta?.component === "Camera" && 
            i.meta?.brand === currentBrand && 
            i.meta?.category === currentCategory &&
            (currentCategory === 'NVR' ? true : i.meta?.mp === currentMP))
        );

        // Push selection (use dataset price; _camData if available)
        const camData = lowestC._camData || { label: camLabel, value: minValC };
        selectedItems.push({
          id: `cam::${currentCategory}::${currentBrand}::${camData.mp || ''}::${camLabel}`,
          label: `${currentCategory} ${currentBrand} ${camData.mp || ''} - Camera: ${camLabel}`,
          price: minValC,
          qty: total,
          meta: {
            component: "Camera",
            brand: currentBrand,
            mp: camData.mp || '',
            category: currentCategory,
            cameraLabel: camLabel,
            raw: camData
          }
        });

        lowestC.classList.add("selected");
        updateCameraButtonText(lowestC, total);
        console.log(`⚙️ Auto-select camera: ${camLabel} - ₹${minValC} (${total})`);
        updateSummary();
        saveSessionData();
        autoSelectHdd();
      }
    }

    // Mark as done for this context to prevent repeated runs
    container._autoDoneKey = key;
  }

  function saveSessionData() {
    // Guard: skip saving until context is valid
    if (!currentCategory || !currentBrand) {
      console.warn('saveSessionData: Skipping save - context incomplete', { currentCategory, currentBrand, currentMP });
      return;
    }
    if (currentCategory === 'DVR' && !currentMP) {
      console.warn('saveSessionData: Skipping save for DVR - MP not set');
      return;
    }

    const contextKey = currentCategory === 'NVR' ? `${currentCategory}::${currentBrand}` : `${currentCategory}::${currentBrand}::${currentMP}`;

    // Filter selectedItems to only save items relevant to the current context.
    // Note: Accessories are NOT saved per-context, they're saved globally
    const itemsToSave = selectedItems.filter(item => {
        // Skip accessories - they're saved globally
        if (item.meta?.type === 'accessory') {
            return false;
        }

        // For components, check if they belong to the current saving context.
        if (item.meta?.category === currentCategory && item.meta?.brand === currentBrand) {
            if (currentCategory === 'NVR') {
                return true; // NVR context is just Category+Brand for components.
            }
            if (currentCategory === 'DVR') {
                return item.meta?.mp === currentMP; // DVR context includes MP.
            }
        }

        // Special handling for HDD, which is linked to a specific contextKey.
        if (item.meta?.component === 'HDD') {
            return item.meta?.contextKey === contextKey;
        }

        return false;
    });

    const sessionData = {
      category: currentCategory,
      brand: currentBrand,
      mp: currentMP,
      cameraCount: parseInt(document.getElementById('num-cameras-input')?.value || 6),
      selectedItems: itemsToSave.map(item => { // Use the filtered list
        const baseItem = {
          id: item.id,
          label: item.label,
          price: item.price,
          qty: item.qty,
          meta: { ...item.meta }
        };

        if (item.meta?.component === "Camera" && item.meta.raw) {
          baseItem.meta.category = item.meta.category || currentCategory;
          baseItem.meta.brand = item.meta.brand || currentBrand;  // Keep original brand!
          baseItem.meta.mp = item.meta.mp || currentMP;
          baseItem.meta.cameraLabel = item.meta.raw.label;
          baseItem.meta.cameraValue = item.meta.raw.value;
          baseItem.meta.raw = item.meta.raw;
        } else if (item.meta?.component === "Recorder" && item.meta.raw) {
          baseItem.meta.category = item.meta.category || currentCategory;
          baseItem.meta.brand = item.meta.brand || currentBrand;  // Keep original brand!
          baseItem.meta.mp = item.meta.mp || currentMP;
          baseItem.meta.recorderLabel = item.meta.raw.label;
          baseItem.meta.recorderValue = item.meta.raw.value;
          baseItem.meta.channel = item.meta.channel;
          baseItem.meta.raw = item.meta.raw;
        } else if (item.meta?.component === "HDD") {
          const contextKey = currentCategory === 'NVR' 
            ? `${currentCategory}::${currentBrand}` 
            : `${currentCategory}::${currentBrand}::${currentMP}`;
          baseItem.meta.brand = item.meta.brand;
          baseItem.meta.capacity = item.meta.capacity;
          baseItem.meta.contextKey = item.meta.contextKey || contextKey;
          baseItem.meta.category = item.meta.category || currentCategory;
          baseItem.meta.brandContext = item.meta.brandContext || currentBrand;
          baseItem.meta.mpContext = item.meta.mpContext || currentMP;
        } else if (item.meta?.type === "accessory") {
          baseItem.meta.type = "accessory";
        }

        return baseItem;
      }),
      timestamp: Date.now()
    };

    let allSessionData = {};
    try {
      const existing = localStorage.getItem(SESSION_KEY);
      if (existing) allSessionData = JSON.parse(existing);
    } catch (e) {
      console.warn('Error loading existing session data:', e);
    }

    allSessionData[contextKey] = sessionData;
    
    // Save accessories globally (not per context)
    const globalAccessories = selectedItems.filter(item => item.meta?.type === 'accessory');
    if (globalAccessories.length > 0) {
      allSessionData['_global_accessories'] = globalAccessories.map(item => ({
        id: item.id,
        label: item.label,
        price: item.price,
        qty: item.qty,
        meta: { type: 'accessory' }
      }));
    }
    
    console.log('Saving session data for', contextKey, sessionData);
    localStorage.setItem(SESSION_KEY, JSON.stringify(allSessionData));
  }

  function clearSessionData() { localStorage.removeItem(SESSION_KEY); }

  function loadSessionData() {
    try {
      const saved = localStorage.getItem(SESSION_KEY);
      if (saved) {
        const allData = JSON.parse(saved);
        const contextKey = currentCategory === 'NVR' ? `${currentCategory}::${currentBrand}` : `${currentCategory}::${currentBrand}::${currentMP}`;
        const data = allData[contextKey];
        if (data) {
          if (Date.now() - data.timestamp < 60 * 60 * 1000) {
            console.log('Loaded session data for', contextKey, data);
            return data;
          } else {
            delete allData[contextKey];
            localStorage.setItem(SESSION_KEY, JSON.stringify(allData));
          }
        }
      }
    } catch (e) {
      console.warn('Failed to load session data:', e);
    }
    return null;
  }

  function restoreSelectionsFromSession() {
    const sessionData = loadSessionData();
    
    // Always restore global accessories from storage
    try {
      const saved = localStorage.getItem(SESSION_KEY);
      if (saved) {
        const allData = JSON.parse(saved);
        const globalAccessories = allData['_global_accessories'];
        if (globalAccessories && Array.isArray(globalAccessories)) {
          // Remove existing accessories from selectedItems
          selectedItems = selectedItems.filter(item => item.meta?.type !== 'accessory');
          // Add global accessories back
          globalAccessories.forEach(acc => {
            if (!selectedItems.find(i => i.id === acc.id)) {
              selectedItems.push(acc);
            }
          });
          // Update accessory button states
          setTimeout(() => {
            globalAccessories.forEach(acc => {
              const btn = document.querySelector(`#accessories button[data-id="${acc.id}"]`) ||
                         Array.from(document.querySelectorAll('#accessories .option-btn')).find(b => 
                           b.dataset.id === acc.id || b.textContent.trim() === acc.label.trim()
                         );
              if (btn && !btn.classList.contains('selected')) {
                btn.classList.add('selected');
              }
            });
          }, 100);
          console.log('✅ Restored global accessories:', globalAccessories.length);
        }
      }
    } catch (e) {
      console.warn('Error restoring global accessories:', e);
    }
    
    if (!sessionData) {
      console.log('No session data to restore for', currentCategory, currentBrand, currentMP);
      return false;
    }

    console.log('Restoring session data for', currentCategory, currentBrand, currentMP, sessionData);

    if (sessionData.cameraCount) {
      const numInput = document.getElementById('num-cameras-input');
      const rangeInput = document.getElementById('num-cameras');
      if (numInput) numInput.value = sessionData.cameraCount;
      if (rangeInput) rangeInput.value = sessionData.cameraCount;
      console.log('Restored camera count:', sessionData.cameraCount);
    }

    // ... rest of restore logic remains the same

    const sessionCameras = sessionData.selectedItems?.filter(i =>
      i.meta?.component === "Camera" && i.meta?.brand === currentBrand &&
      i.meta?.category === currentCategory &&
      (currentCategory === 'NVR' ? true : i.meta?.mp === currentMP)
    ) || [];
    
    const lastSelectedCamera = sessionCameras.length > 0 ? sessionCameras[sessionCameras.length - 1] : null;

    console.log('Session camera to restore for current context:', lastSelectedCamera);

    if (lastSelectedCamera) {
      selectedItems = selectedItems.filter(item =>
        !(item.meta?.component === 'Camera' &&
          item.meta?.brand === currentBrand &&
          item.meta?.category === currentCategory &&
          (currentCategory === 'NVR' ? true : item.meta?.mp === currentMP))
      );
      
      selectedItems.push({ ...lastSelectedCamera });

      setTimeout(() => {
        // Clear all camera buttons in current context first
        document.querySelectorAll(".camera-btn").forEach(btn => {
          if (btn._brand === currentBrand && btn._category === currentCategory && 
              (currentCategory === 'NVR' ? true : btn._mp === currentMP)) {
            btn.classList.remove("selected");
            updateCameraButtonText(btn, 0);
          }
        });
        
        // Then select the restored camera
        document.querySelectorAll(".camera-btn").forEach(btn => {
          if (btn._brand === currentBrand && btn._category === currentCategory &&
              (currentCategory === 'NVR' ? true : btn._mp === currentMP)) {
            if (lastSelectedCamera.meta?.cameraLabel === btn.dataset.cameraLabel) {
              btn.classList.add("selected");
              updateCameraButtonText(btn, lastSelectedCamera.qty);
              console.log('Restored and selected button:', btn.textContent);
            }
          }
        });
      }, 50);
    }

    const sessionRecorder = sessionData.selectedItems?.find(i =>
      i.meta?.component === "Recorder" && i.meta?.brand === currentBrand && 
      i.meta?.category === currentCategory &&
      (currentCategory === 'NVR' ? true : i.meta?.mp === currentMP) // Adjust filter for NVR
    );
    console.log('Session recorder to restore:', sessionRecorder);
    if (sessionRecorder) {
      selectedItems = selectedItems.filter(i =>
        !(i.meta?.component === 'Recorder' && 
          i.meta.brand === currentBrand && 
          i.meta?.category === currentCategory &&
          (currentCategory === 'NVR' ? true : i.meta.mp === currentMP)) // Adjust filter for NVR
      );
      console.log('selectedItems after removing old recorders for restore:', selectedItems.map(i => i.id));

      setTimeout(() => {
        // Clear all recorder buttons in current context first
        const recorderButtons = document.querySelectorAll(".recorder-btn");
        recorderButtons.forEach(btn => {
          // Check if button matches current context
          const btnMatchesContext = (currentCategory === 'NVR') 
            ? (btn._brand === currentBrand)
            : (btn._brand === currentBrand && btn._mp === currentMP);
          
          if (btnMatchesContext) {
            btn.classList.remove("selected");
          }
        });
        
        // Then select the restored recorder
        const enabledRecorderButtons = document.querySelectorAll(".recorder-btn:not(.disabled)");
        console.log('All recorder buttons in DOM for restore:', Array.from(enabledRecorderButtons).map(b => b.textContent));
        enabledRecorderButtons.forEach(btn => {
          const btnChannel = parseInt(btn.dataset.channel);
          const sessionChannel = sessionRecorder.meta?.channel;
          const sessionLabel = sessionRecorder.meta?.recorderLabel || sessionRecorder.label;
          
          // Check if button matches current context
          const btnMatchesContext = (currentCategory === 'NVR') 
            ? (btn._brand === currentBrand)
            : (btn._brand === currentBrand && btn._mp === currentMP);

          if (btnMatchesContext && ((btnChannel === sessionChannel) || btn.textContent.includes(sessionLabel))) {
            btn.classList.add("selected");
            const existing = selectedItems.find(item =>
              item.meta?.component === 'Recorder' && item.meta.brand === currentBrand && 
              item.meta?.category === currentCategory &&
              (currentCategory === 'NVR' ? true : item.meta.mp === currentMP)
            );
            if (!existing) {
              selectedItems.push({
                id: sessionRecorder.id,
                label: sessionRecorder.label,
                price: sessionRecorder.price,
                qty: sessionRecorder.qty || 1,
                meta: sessionRecorder.meta
              });
            }
            console.log('Restored and selected recorder button:', btn.textContent);
          }
        });
      }, 100);
    }

    const hddContextKey = currentCategory === 'NVR' 
      ? `${currentCategory}::${currentBrand}` 
      : `${currentCategory}::${currentBrand}::${currentMP}`;
    const sessionHdd = sessionData.selectedItems?.find(i => 
      i.meta?.component === "HDD" && i.meta?.contextKey === hddContextKey
    );
    console.log('Session HDD to restore:', sessionHdd);
    if (sessionHdd) {
      // Only remove HDDs for the current brand, keep other brands' HDDs
      selectedItems = selectedItems.filter(i => 
        !(i.meta?.component === 'HDD' && i.meta?.contextKey === hddContextKey)
      );
      console.log('selectedItems after removing old HDDs for restore:', selectedItems.map(i => i.id));

      setTimeout(() => {
        renderHddSections();
        setTimeout(() => {
          const hddButtons = document.querySelectorAll("#preferred-hdd .choice-btn, #brandwise-hdd .choice-btn");
          console.log('All HDD buttons in DOM for restore:', Array.from(hddButtons).map(b => b.textContent));
          hddButtons.forEach(btn => {
            if (btn.dataset.brand === sessionHdd.meta.brand && btn.dataset.capacity === sessionHdd.meta.capacity) {
              btn.classList.add("selected");
              const existing = selectedItems.find(item => item.meta?.component === 'HDD');
              if (!existing) {
                selectedItems.push({
                  id: sessionHdd.id,
                  label: sessionHdd.label,
                  price: sessionHdd.price,
                  qty: sessionHdd.qty || 1,
                  meta: sessionHdd.meta
                });
              }
              console.log('Restored and selected HDD button:', btn.textContent);
            }
          });
        }, 50);
      }, 150);
    }

    setTimeout(() => {
      console.log('Final selectedItems after restore:', selectedItems.length, selectedItems.map(i => i.id));
      const contextItems = selectedItems.filter(i =>
        (i.meta?.component === 'Camera' || i.meta?.component === 'Recorder') &&
        i.meta?.brand === currentBrand &&
        i.meta?.category === currentCategory &&
        (currentCategory === 'NVR' ? true : i.meta?.mp === currentMP) // Adjust filter for NVR
      );
      console.log('Context-specific items after restore:', contextItems.map(i => ({
        label: i.label, type: i.meta?.component, qty: i.qty, meta: i.meta
      })));
      updateSummary();
      
      // Auto-select HDD after restoration/auto-select completes
      autoSelectHdd();
    }, 300);

    return true;
  }

  // Helper function to update camera button text with quantity
  function updateCameraButtonText(btn, qty) {
    const originalText = btn.dataset.originalLabel || btn.textContent.replace(/\s*\(\d+\)$/, '');
    if (!btn.dataset.originalLabel) btn.dataset.originalLabel = originalText;
    
    // Clear button content
    btn.innerHTML = '';
    
    // Re-add icon if it exists in the camera data
    const camData = btn._camData;
    if (camData && camData.icon) {
      const icon = document.createElement('i');
      icon.className = `fas fa-${camData.icon}`;
      icon.style.marginRight = '8px';
      btn.appendChild(icon);
    }
    
    // Add label text
    const labelText = document.createTextNode(originalText);
    btn.appendChild(labelText);

    // Add quantity in a styled circle
    if (qty > 0) { // Only show quantity if greater than 0
      const qtySpan = document.createElement('span');
      qtySpan.textContent = qty;
      qtySpan.style.display = 'inline-flex';
      qtySpan.style.alignItems = 'center';
      qtySpan.style.justifyContent = 'center';
      qtySpan.style.width = '24px';
      qtySpan.style.height = '24px';
      qtySpan.style.borderRadius = '4px';
      qtySpan.style.backgroundColor = 'rgb(52 152 220)';
      qtySpan.style.color = 'white';
      qtySpan.style.fontSize = '12px';
      qtySpan.style.fontWeight = 'bold';
      qtySpan.style.marginLeft = '8px';
      qtySpan.style.boxShadow = 'rgba(0, 0, 0, 0.2) 0px 2px 4px';
      qtySpan.style.border = '1px solid white';
      btn.appendChild(qtySpan);
    }
  }

  /* ---------- Utility ---------- */
  function safeGetTypeRoot() {
    return (typeof data === "object" && data && data.Type) ? data.Type : {};
  }

  function formatINR(n) {
    if (n === null || n === undefined || isNaN(n)) return '—';
    return '₹' + Math.round(n).toLocaleString('en-IN');
  }

  function parsePrice(v) {
    if (v === null || v === undefined) return null;
    const num = parseFloat((v + '').replace(/[^\d\.]/g, ''));
    return isNaN(num) ? null : num;
  }

  // Tags helpers
  function normalizeChannel(ch) {
    if (!ch) return null;
    const match = String(ch).match(/(\d+)\s*CH/i);
    return match ? `${match[1]} CH` : String(ch).toUpperCase().replace(/\s*CH/i, ' CH');
  }

  function extractCameraType(label) {
    if (!label) return null;
    const l = String(label).toUpperCase();
    if (l.includes('COLOR NIGHT')) return 'Color Night';
    if (l.includes('FULL COLOUR')) return 'Full Colour';
    if (l.includes('HYBRID')) return 'Hybrid';
    if (l.includes('HYBID NIGHT')) return 'Hybrid Night';
    if (l.includes('NORMAL NV')) return 'Normal Night';
    if (l.includes('NORMAL NIGHT')) return 'Normal Night';
    return null;
  }

  /* ---------- Sorting Functions ---------- */
  function sortByPriceLowToHigh(items) {
    if (!Array.isArray(items)) return items;
    return items.sort((a, b) => {
      const priceA = parsePrice(a.value) || 0;
      const priceB = parsePrice(b.value) || 0;
      return priceA - priceB;
    });
  }

  function sortHddByPriceLowToHigh(brandData) {
    if (!brandData || !Array.isArray(brandData)) return brandData;
    return brandData.sort((a, b) => {
      const priceA = parsePrice(a.value) || 0;
      const priceB = parsePrice(b.value) || 0;
      return priceA - priceB;
    });
  }

  /* ---------- Category / Brand / MP Rendering ---------- */
  let currentCategory = '';
  let currentBrand = '';
  let currentMP = '';

  function renderCategoryButtons() {
    const typeRoot = safeGetTypeRoot();
    const categories = Object.keys(typeRoot || {});
    categoryContainer.innerHTML = "";

    if (categories.length === 0) {
      categoryContainer.innerHTML = '<div class="small-muted">No categories available</div>';
      return;
    }

    categoryContainer.style.display = "flex";
    categoryContainer.classList.add("button-row");

    categories.forEach(category => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "choice-btn";
      btn.textContent = category;
      btn.dataset.category = category;
      btn.addEventListener("click", () => selectCategory(category));
      categoryContainer.appendChild(btn);
    });
  }

  function selectCategory(category) {
    const previousMP = currentMP;

    currentCategory = category;
    currentBrand = "";
    currentMP = ""; // Always reset MP when category changes.

    const selCat = document.getElementById("selected-category");
    const selBrand = document.getElementById("selected-brand");
    const selMP = document.getElementById("selected-mp");
    if (selCat) selCat.value = category;
    if (selBrand) selBrand.value = "";
    if (selMP) selMP.value = ""; // Reset MP dropdown

    // Clear all category button states first, then set the selected one
    categoryContainer.querySelectorAll(".choice-btn").forEach(b => {
      b.classList.remove("selected");
    });
    categoryContainer.querySelectorAll(".choice-btn").forEach(b => {
      if (b.textContent === category) {
        b.classList.add("selected");
      }
    });

    // Clear all brand and MP button states when switching category
    brandContainer.querySelectorAll(".choice-btn").forEach(b => {
      b.classList.remove("selected");
    });
    mpContainer.querySelectorAll(".choice-btn").forEach(b => {
      b.classList.remove("selected");
    });

    // Clear component popup button states
    const popup = document.getElementById("componentPopup");
    if (popup) {
      popup.querySelectorAll(".recorder-btn, .camera-btn").forEach(b => {
        b.classList.remove("selected");
      });
      popup.style.display = "none";
    }

    renderBrandButtons(category);
    mpContainer.style.display = "none";

    updateSummary();
    saveSessionData(); // Save current state before switching

    setTimeout(() => {
      const sessionData = loadSessionData();
      if (sessionData && sessionData.category === category) {
        const typeRoot = safeGetTypeRoot();
        const categoryData = typeRoot?.[category];

        if (categoryData) {
          let targetBrand = null;
          let targetMP = null;

          if (sessionData.brand && categoryData[sessionData.brand]) {
            targetBrand = sessionData.brand;
            // For NVR, MP should be null/empty; for DVR, use saved MP or default
            targetMP = category === 'NVR' ? '' : (sessionData.mp || Object.keys(categoryData[sessionData.brand])[0]);
          } else {
            targetBrand = Object.keys(categoryData)[0];
            targetMP = category === 'NVR' ? '' : Object.keys(categoryData[targetBrand])[0];
          }

          if (targetBrand) { // targetMP can be null for NVR
            currentBrand = targetBrand;
            currentMP = targetMP;

            const selBrand = document.getElementById("selected-brand");
            const selMP = document.getElementById("selected-mp");
            if (selBrand) selBrand.value = targetBrand;
            if (selMP) selMP.value = targetMP || "";

            setTimeout(() => { selectBrand(targetBrand, true); }, 50);
          }
        }
      } else {
        setTimeout(() => {
          const typeRoot = safeGetTypeRoot();
          const categoryData = typeRoot?.[category];
          if (categoryData) {
            const defaultBrand = Object.keys(categoryData)[0];
            if (defaultBrand) {
              currentBrand = defaultBrand;
              const selBrand = document.getElementById("selected-brand");
              if (selBrand) selBrand.value = defaultBrand;
              selectBrand(defaultBrand, false);
            }
          }
        }, 50);
      }
    }, 100);
  }

  function renderBrandButtons(category) {
    const typeRoot = safeGetTypeRoot();
    const categoryData = typeRoot?.[category];
    brandContainer.innerHTML = "";

    if (!categoryData || typeof categoryData !== "object") {
      brandContainer.style.display = "none";
      return;
    }

    brandContainer.style.display = "flex";
    brandContainer.classList.add("button-row");

    const brands = Object.keys(categoryData);
    brands.forEach(brand => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "choice-btn";
      btn.textContent = brand;
      btn.dataset.brand = brand;
      btn.addEventListener("click", () => selectBrand(brand));
      brandContainer.appendChild(btn);
    });
  }

  function selectBrand(brand, shouldRestore = false) {
    console.log('selectBrand called:', brand, 'shouldRestore:', shouldRestore);

    currentBrand = brand;

    const selBrand = document.getElementById("selected-brand");
    if (selBrand) selBrand.value = brand;

    // Clear all brand button states first, then set the selected one
    brandContainer.querySelectorAll(".choice-btn").forEach(b => {
      b.classList.remove("selected");
    });
    brandContainer.querySelectorAll(".choice-btn").forEach(b => {
      if (b.textContent === brand) {
        b.classList.add("selected");
      }
    });

    // Clear MP button states when switching brand
    mpContainer.querySelectorAll(".choice-btn").forEach(b => {
      b.classList.remove("selected");
    });

    // Clear component popup button states when switching brand
    const popup = document.getElementById("componentPopup");
    if (popup) {
      popup.querySelectorAll(".recorder-btn, .camera-btn").forEach(b => {
        b.classList.remove("selected");
      });
    }

    // NOTE: Do not filter selectedItems here. The summary and restore functions will handle context.

    if (currentCategory === 'DVR') {
      renderMPButtons(currentCategory, brand);

      const typeRoot = safeGetTypeRoot();
      const brandData = typeRoot?.[currentCategory]?.[brand];

      if (!brandData) {
        console.warn("No brand data for", brand);
        return;
      }

      const previousMP = currentMP;
      let mpToUse = previousMP && brandData[previousMP] ? previousMP : null;
      if (!mpToUse) {
        const mpKeys = Object.keys(brandData);
        mpToUse = mpKeys.find(mp => mp.toLowerCase() === "2mp") || mpKeys[0];
      }
      
      // This will trigger renderComponentItems
      selectMP(mpToUse, true);

    } else { // For NVR and other types
      currentMP = ''; // Reset MP
      const selMP = document.getElementById("selected-mp");
      if (selMP) selMP.value = '';
      
      mpContainer.innerHTML = '';
      mpContainer.style.display = 'none';
      
      // For NVR, we need to trigger the component rendering
      setTimeout(() => {
        renderComponentItems(currentCategory, currentBrand, '', true);
      }, 50);
    }

    // Clear any existing HDD for the new context BEFORE re-rendering sections,
    // so that manual selection restore during render is not immediately removed.
    const contextKey = currentCategory === 'NVR'
      ? `${currentCategory}::${currentBrand}`
      : `${currentCategory}::${currentBrand}::${currentMP || ''}`;
    selectedItems = selectedItems.filter(i =>
      !(i.meta?.component === 'HDD' && i.meta?.contextKey === contextKey)
    );

    // Clear any HDD rows from the existing summary UI if present
    const summaryItems = document.getElementById('summary-items');
    if (summaryItems) {
      const hddElements = summaryItems.querySelectorAll('[data-component="HDD"]');
      hddElements.forEach(el => el.remove());
    }

    // Now render HDD sections which will restore manual selection if available
    renderHddSections();

    updateSummary();
    saveSessionData();
    
    // Update WhatsApp preview after context change
    setTimeout(() => {
      updateWhatsAppPreview();
    }, 500);
  }

  function renderMPButtons(category, brand) {
    const typeRoot = safeGetTypeRoot();
    const brandData = typeRoot?.[category]?.[brand];
    mpContainer.innerHTML = "";

    if (!brandData || typeof brandData !== "object" || category === 'NVR') {
      mpContainer.style.display = "none";
      return;
    }

    const mpOptions = Object.keys(brandData);
    mpContainer.style.display = "flex";
    mpContainer.classList.add("button-row");

    mpOptions.forEach(mp => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "choice-btn";
      btn.textContent = mp.toUpperCase();
      btn.dataset.mp = mp;
      btn.addEventListener("click", () => selectMP(mp, true));
      mpContainer.appendChild(btn);
    });
  }

  function selectMP(mp, triggerClick = false) {
    currentMP = mp;
    const selMP = document.getElementById("selected-mp");
    if (selMP) selMP.value = mp;

    // Clear all MP button states first, then set the selected one
    mpContainer.querySelectorAll(".choice-btn").forEach(b => {
      b.classList.remove("selected");
    });
    mpContainer.querySelectorAll(".choice-btn").forEach(b => {
      if (b.textContent.toLowerCase() === mp.toLowerCase()) {
        b.classList.add("selected");
      }
    });

    // Clear component popup button states when switching MP
    const popup = document.getElementById("componentPopup");
    if (popup) {
      popup.querySelectorAll(".recorder-btn, .camera-btn").forEach(b => {
        b.classList.remove("selected");
      });
    }

    renderHddSections();
    renderComponentItems(currentCategory, currentBrand, currentMP, triggerClick);

    // restore handled inside render; auto-select handled by observer
    saveSessionData();
    
    // After switching MP, ensure manual HDD selection is applied if it exists
    const contextKey = currentCategory === 'NVR' 
      ? `${currentCategory}::${currentBrand}` 
      : `${currentCategory}::${currentBrand}::${currentMP}`;
    
    // Clear any existing HDD for this context to force reselection
    selectedItems = selectedItems.filter(i => 
      !(i.meta?.component === 'HDD' && i.meta?.contextKey === contextKey)
    );
    
    if (manualHddSelections[contextKey]) {
      console.log('🔁 Applying manual HDD selection for context:', contextKey);
      // The HDD should be automatically selected by renderHddSections, but let's make sure
      setTimeout(() => {
        const manualHdd = manualHddSelections[contextKey];
        const hddButtons = document.querySelectorAll("#preferred-hdd .choice-btn, #brandwise-hdd .choice-btn");
        let foundButton = false;
        hddButtons.forEach(btn => {
          if (btn.dataset.brand === manualHdd.brand && 
              btn.dataset.capacity === manualHdd.capacity) {
            // Only click if not already selected
            if (!btn.classList.contains('selected')) {
              console.log('🔁 Auto-selecting manual HDD:', manualHdd);
              btn.click();
            }
            foundButton = true;
          }
        });
        
        // If we couldn't find the exact button, try to find one with similar capacity
        if (!foundButton) {
          console.log('⚠️ Could not find exact HDD button, trying approximate match');
          hddButtons.forEach(btn => {
            if (btn.dataset.capacity === manualHdd.capacity) {
              if (!btn.classList.contains('selected')) {
                console.log('🔁 Auto-selecting approximate HDD match:', manualHdd.capacity);
                btn.click();
              }
            }
          });
        }
      }, 200);
    } else {
      // Auto-select HDD for new context
      setTimeout(() => {
        autoSelectHdd();
      }, 300);
    }
    
    // Update summary and WhatsApp preview after context change
    setTimeout(() => {
      updateSummary();
      updateWhatsAppPreview();
    }, 500);
  }

  /* ---------- Accessories ---------- */
  function renderAccessories() {
    accessoriesContainer.innerHTML = '';
    const items = (typeof data === 'object' && data && data.items) ? data.items : {};
    
    // Restore global accessories from storage on initial render
    try {
      const saved = localStorage.getItem(SESSION_KEY);
      if (saved) {
        const allData = JSON.parse(saved);
        const globalAccessories = allData['_global_accessories'];
        if (globalAccessories && Array.isArray(globalAccessories)) {
          // Remove existing accessories from selectedItems
          selectedItems = selectedItems.filter(item => item.meta?.type !== 'accessory');
          // Add global accessories back
          globalAccessories.forEach(acc => {
            if (!selectedItems.find(i => i.id === acc.id)) {
              selectedItems.push(acc);
            }
          });
        }
      }
    } catch (e) {
      console.warn('Error loading global accessories on render:', e);
    }
    
    console.log('🎨 Rendering accessories:', items);
    
    Object.keys(items).forEach(key => {
      const it = items[key];
      
      console.log(`📦 Checking item: ${key}`, { show: it.show, icon: it.icon });
      
      // Only show items where show is true
      if (it.show !== true) {
        console.log(`⏭️ Skipping ${key} - show is not true`);
        return;
      }
      
      console.log(`✅ Rendering ${key} with icon: ${it.icon}`);
      
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'option-btn';
      
      // Add icon if present
      if (it.icon) {
        const icon = document.createElement('i');
        icon.className = `fas fa-${it.icon}`;
        icon.style.marginRight = '8px';
        btn.appendChild(icon);
        console.log(`🎯 Icon added: fas fa-${it.icon}`);
      }
      
      // Add label text
      const labelText = document.createTextNode(it.label || key);
      btn.appendChild(labelText);
      
      // Store ID on button for easier restoration
      btn.dataset.id = 'accessory::' + key;
      
      // Check if this accessory is already selected
      const id = 'accessory::' + key;
      const found = selectedItems.find(i => i.id === id);
      if (found) {
        btn.classList.add('selected');
      }
      
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const id = 'accessory::' + key;
        const found = selectedItems.find(i => i.id === id);
        if (found) {
          selectedItems = selectedItems.filter(i => i.id !== id);
          btn.classList.remove('selected');
        } else {
          selectedItems.push({
            id,
            label: it.label || key,
            price: parsePrice(it.value),
            qty: 1,
            meta: { type: 'accessory' }
          });
          btn.classList.add('selected');
        }
        updateSummary();
        saveSessionData();
      });
      accessoriesContainer.appendChild(btn);
    });
  }

  /* ---------- Recorder & Camera Popup ---------- */
  function renderComponentItems(category, brand, mp, triggerClick = false) {
    console.log('renderComponentItems called:', { category, brand, mp, triggerClick });

    let container = document.getElementById("componentPopup");
    if (!container) {
      container = document.createElement("div");
      container.id = "componentPopup";
      container.className = "component-popup-inline";
      const mpButtons = document.querySelector("#mp-buttons");
      if (mpButtons) mpButtons.after(container);
    }

    // Set current context on the container (handy for debugging/guards)
    container.dataset.category = category;
    container.dataset.brand = brand;
    container.dataset.mp = mp || '';
    container._autoDoneKey = null; // reset observer guard on each render
    container.innerHTML = "";
    container.style.display = triggerClick ? "block" : "none";
    if (!triggerClick) return;

    // Start/refresh observer to react to DOM as soon as buttons appear
    ensureComponentObserver();

    const typeRoot = safeGetTypeRoot();
    if (category === 'DVR') {
        const mpData = typeRoot?.[category]?.[brand]?.[mp] || null;
        console.log('MP Data:', mpData);
        if (!mpData) return;

        const cameraCount = parseInt(document.getElementById("num-cameras-input").value || 0, 10);

        /* ---------- Recorder Section ---------- */
        if (mpData.Recorder && typeof mpData.Recorder === "object") {
          const recSection = document.createElement("div");
          recSection.className = "component-section";
          recSection.innerHTML = "<h4>Recorders</h4>";

          const recButtons = document.createElement("div");
          recButtons.className = "button-row";

          // determine minimum allowed channel
          let allowedChannel;
          if (cameraCount <= 4) allowedChannel = 4;
          else if (cameraCount <= 8) allowedChannel = 8;
          else if (cameraCount <= 16) allowedChannel = 16;
          else allowedChannel = 32;

          const recorderKeys = Object.keys(mpData.Recorder);
          const allChannels = recorderKeys
            .map(k => parseInt(k))
            .filter(n => !isNaN(n))
            .sort((a, b) => a - b);

          allChannels.forEach(channel => {
            const matchingKey = recorderKeys.find(k => parseInt(k) === channel);
            const arr = (matchingKey && Array.isArray(mpData.Recorder[matchingKey]))
              ? mpData.Recorder[matchingKey] : [];

            arr.forEach(rec => {
              const btn = document.createElement("button");
              btn.type = "button";
              btn.className = "option-btn recorder-btn";
              const label = rec.label || `${channel}CH Recorder`;
              btn.textContent = label;
              btn.dataset.channel = channel;
              btn.dataset.value = rec.value || "";
              // store context for restores (optional)
              btn._brand = brand;
              btn._mp = mp;

              if (channel < allowedChannel) {
                btn.disabled = true;
                btn.classList.add("disabled");
              }

              btn.addEventListener("click", function (e) {
                e.preventDefault();
                if (btn.disabled) return;
                
                // Only clear selected state from recorders in the same context (brand/MP)
                document.querySelectorAll(".recorder-btn").forEach(b => {
                  if (b._brand === brand && b._mp === mp) {
                    b.classList.remove("selected");
                  }
                });
                
                // Only remove recorders for the CURRENT brand/MP, keep others
                selectedItems = selectedItems.filter(i => 
                  !(i.meta?.component === 'Recorder' && 
                    i.meta.brand === brand && 
                    i.meta.mp === mp)
                );
                
                // Add the selected recorder
                btn.classList.add("selected");
                selectedItems.push({
                  id: `rec::${category}::${brand}::${mp}::${channel}CH::${label}`,
                  label: `${category} ${brand} ${mp} - Recorder ${channel}CH: ${label}`,
                  price: parsePrice(rec.value),
                  qty: 1,
                  meta: { component: "Recorder", channel, raw: rec, brand, mp, category },
                });
                
                updateSummary();
                saveSessionData();
              });

              recButtons.appendChild(btn);
            });
          });

          recSection.appendChild(recButtons);
          container.appendChild(recSection);
        }

        /* ---------- Camera Section ---------- */
        if (mpData.Camera) {
          const camSection = document.createElement("div");
          camSection.className = "component-section";
          camSection.innerHTML = "<h4>Cameras</h4>";

          const camButtons = document.createElement("div");
          camButtons.className = "button-row";

          const cameras = Array.isArray(mpData.Camera)
            ? mpData.Camera
            : Object.values(mpData.Camera).flat();

          const sortedCameras = sortByPriceLowToHigh(cameras);

          sortedCameras.forEach(cam => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "option-btn camera-btn";
            
            // Add icon if present
            if (cam.icon) {
              const icon = document.createElement('i');
              icon.className = `fas fa-${cam.icon}`;
              icon.style.marginRight = '8px';
              btn.appendChild(icon);
            }
            
            // Add label text
            const labelText = document.createTextNode(cam.label || "Camera");
            btn.appendChild(labelText);
            
            btn.dataset.price = parsePrice(cam.value) || 0;
            btn.dataset.cameraLabel = cam.label || 'Camera';
            btn._camData = cam; // convenience only
            btn._brand = brand; // retained so restore UI can match if needed
            btn._mp = mp;
            btn._category = category; // Set category for proper context matching

            attachCameraButtonLogic(btn, cam, category, brand, mp);
            camButtons.appendChild(btn);
          });

          camSection.appendChild(camButtons);
          container.appendChild(camSection);
        }
    } else if (category === 'NVR') {
        const brandData = typeRoot?.[category]?.[brand] || null;
        if (!brandData) return;

        const cameraCount = parseInt(document.getElementById("num-cameras-input").value || 0, 10);

        /* ---------- Recorder Section (NVR) ---------- */
        if (brandData.Recorder && typeof brandData.Recorder === "object") {
            const recSection = document.createElement("div");
            recSection.className = "component-section";
            recSection.innerHTML = "<h4>Recorders</h4>";

            const recButtons = document.createElement("div");
            recButtons.className = "button-row";

            let allowedChannel;
            if (cameraCount <= 4) allowedChannel = 4;
            else if (cameraCount <= 8) allowedChannel = 8;
            else if (cameraCount <= 16) allowedChannel = 16;
            else allowedChannel = 32;

            const recorderKeys = Object.keys(brandData.Recorder);
            const allChannels = recorderKeys
                .map(k => parseInt(k))
                .filter(n => !isNaN(n))
                .sort((a, b) => a - b);

            allChannels.forEach(channel => {
                const matchingKey = recorderKeys.find(k => parseInt(k) === channel);
                const arr = (matchingKey && Array.isArray(brandData.Recorder[matchingKey]))
                  ? brandData.Recorder[matchingKey] : [];

                arr.forEach(rec => {
                    const btn = document.createElement("button");
                    btn.type = "button";
                    btn.className = "option-btn recorder-btn";
                    const label = rec.label || `${channel}CH Recorder`;
                    btn.textContent = label;
                    btn.dataset.channel = channel;
                    btn.dataset.value = rec.value || "";
                    btn._brand = brand;
                    btn._mp = ''; // No MP for NVR recorders

                    if (channel < allowedChannel) {
                        btn.disabled = true;
                        btn.classList.add("disabled");
                    }

                    btn.addEventListener("click", function (e) {
                        e.preventDefault();
                        if (btn.disabled) return;
                        
                        // Only clear selected state from recorders in the same brand (NVR context)
                        document.querySelectorAll(".recorder-btn").forEach(b => {
                          if (b._brand === brand) {
                            b.classList.remove("selected");
                          }
                        });
                        
                        // Only remove recorders for the CURRENT brand, keep others
                        selectedItems = selectedItems.filter(i => 
                          !(i.meta?.component === 'Recorder' && i.meta.brand === brand)
                        );
                        
                        // Add the selected recorder
                        btn.classList.add("selected");
                        selectedItems.push({
                          id: `rec::${category}::${brand}::${channel}CH::${label}`,
                          label: `${category} ${brand} - Recorder ${channel}CH: ${label}`,
                          price: parsePrice(rec.value),
                          qty: 1,
                          meta: { component: "Recorder", channel, raw: rec, brand, mp: '', category },
                        });
                        
                        updateSummary();
                        saveSessionData();
                    });
                    recButtons.appendChild(btn);
                });
            });
            recSection.appendChild(recButtons);
            container.appendChild(recSection);
        }

        /* ---------- Camera Section (NVR) ---------- */
        if (brandData.Camera) {

            const camSection = document.createElement("div");
            camSection.className = "component-section";
            camSection.innerHTML = "<h4>Cameras</h4>";
            container.appendChild(camSection);

            const nvrCamContainer = document.createElement("div");
            nvrCamContainer.className = "button-row";
            camSection.appendChild(nvrCamContainer);

            const entryCameras = Array.isArray(brandData.Camera) ? brandData.Camera : Object.values(brandData.Camera).flat();
            const sortedCameras = sortByPriceLowToHigh(entryCameras);

            sortedCameras.forEach(cam => {
                const camBtn = document.createElement("button");
                camBtn.type = "button";
                camBtn.className = "option-btn camera-btn";
                
                if (cam.icon) {
                  const icon = document.createElement('i');
                  icon.className = `fas fa-${cam.icon}`;
                  icon.style.marginRight = '8px';
                  camBtn.appendChild(icon);
                }
                
                const labelText = document.createTextNode(cam.label || "Camera");
                camBtn.appendChild(labelText);
                
                camBtn.dataset.price = parsePrice(cam.value) || 0;
                camBtn.dataset.cameraLabel = cam.label || 'Camera';
                camBtn._camData = cam;
                camBtn._brand = brand;
                camBtn._mp = cam.mp || ''; // Use cam.mp or empty string
                camBtn._category = category; // Set category for proper context matching

                attachCameraButtonLogic(camBtn, cam, category, brand, cam.mp || '');
                nvrCamContainer.appendChild(camBtn);
            });
        }
    }

    /* ---------- Restore (observer will auto-select if needed) ---------- */
    setTimeout(() => {
      const restored = restoreSelectionsFromSession();
      console.log('Restore after render =>', restored ? 'restored' : 'no session data');

      // Only mark as auto-done if we actually restored cameras/recorders for this context
      const hasContextItems = selectedItems.some(i =>
        (i.meta?.component === 'Camera' || i.meta?.component === 'Recorder') &&
        i.meta?.brand === currentBrand &&
        i.meta?.category === currentCategory &&
        (currentCategory === 'NVR' ? true : i.meta?.mp === currentMP)
      );
      
      // Set the auto-done key correctly for NVR
      const autoDoneKey = currentCategory === 'NVR' ? `${currentCategory}|${currentBrand}` : `${currentCategory}|${currentBrand}|${currentMP}`;
      
      if (restored && hasContextItems) {
        container._autoDoneKey = autoDoneKey;
        console.log('✅ Restoration successful - blocking auto-select');
      } else {
        console.log('⏩ No items restored - auto-select will run');
      }
    }, 0);
  }

  /* ---------- Camera click (no long press) ---------- */
  function attachCameraButtonLogic(btn, cam, category, brand, mp) {
    function getTotalCameraCount() {
      return parseInt(document.getElementById('num-cameras-input').value || 1);
    }

    function selectCamera() {
      const totalCameras = getTotalCameraCount();
      const id = `cam::${category}::${brand}::${mp}::${cam.label}`;
      const isAlreadySelected = btn.classList.contains('selected');

      console.log('--- selectCamera START ---');
      console.log('Clicked camera:', { category, brand, mp, label: cam.label, isAlreadySelected });

      // If the clicked camera is already selected, do nothing.
      if (isAlreadySelected) {
        console.log('Camera already selected. No change.');
        console.log('--- selectCamera END ---');
        return;
      }

      // Visually deselect all other camera buttons in the same context (brand/category/mp)
      document.querySelectorAll('.camera-btn').forEach(otherBtn => {
        if (otherBtn._brand === brand && otherBtn._category === category && otherBtn._mp === mp) {
          otherBtn.classList.remove('selected');
          updateCameraButtonText(otherBtn, 0);
        }
      });

      // Remove cameras from selectedItems that match the specific context (cat, brand, mp)
      selectedItems = selectedItems.filter(item =>
        !(item.meta?.component === 'Camera' && 
          item.meta.category === category && 
          item.meta.brand === brand && 
          item.meta.mp === mp)
      );

      // Add the new camera
      selectedItems.push({
        id,
        label: `${category} ${brand} ${mp} - Camera: ${cam.label}`,
        price: parsePrice(cam.value),
        qty: totalCameras,
        meta: { component: 'Camera', raw: cam, brand, mp, category, cameraLabel: cam.label },
      });

      // Select the clicked button and update its text
      btn.classList.add('selected');
      updateCameraButtonText(btn, totalCameras);

      console.log('selectedItems AFTER logic:', selectedItems.map(i => i.id));
      updateSummary();
      saveSessionData();
      autoSelectHdd();
      console.log('--- selectCamera END ---');
    }

    // Data & flags
    btn._camData = { ...cam, brand, mp };
    btn.dataset.cameraLabel = cam.label || 'Camera';
    btn.dataset.originalLabel = cam.label || 'Camera';
    btn._hasListener = true;
    btn._brand = brand;
    btn._mp = mp;
    btn._category = category;

    // Click -> select camera (radio button mode)
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      selectCamera();
    });
  }

  /* ---------- Update all camera sliders when total changes ---------- */
  function updateAllCameraSliders() {
    // Placeholder for future slider updates if needed
    updateSummary();
    saveSessionData();
  }

  /* ---------- HDD Rendering Section (Preferred + Brandwise) ---------- */
  function renderHddSections() {
    const preferredContainer = document.getElementById("preferred-hdd");
    const brandwiseContainer = document.getElementById("brandwise-hdd");
    const hddSectionHeader = document.getElementById("hdd-section-header");

    if (!preferredContainer || !brandwiseContainer) return;

    const hddData = (typeof data !== "undefined" && data && data.HDD) ? data.HDD : {};
    preferredContainer.innerHTML = "";
    brandwiseContainer.innerHTML = "";
    
    // Show HDD header when rendering sections
    if (hddSectionHeader) {
      hddSectionHeader.style.display = 'flex';
    }

    // Find previously selected HDD for the CURRENT context (matches save/select format)
    const currentContextKey = currentCategory === 'NVR' 
      ? `${currentCategory}::${currentBrand}` 
      : `${currentCategory}::${currentBrand}::${currentMP}`;
    
    // First check if there's a manual selection for this context
    let prevHdd = null;
    if (manualHddSelections[currentContextKey]) {
      // Use manual selection
      const manualHdd = manualHddSelections[currentContextKey];
      prevKey = `${manualHdd.brand}::${manualHdd.capacity}`;
      console.log('🔧 Using manual HDD selection for context:', currentContextKey, '| Manual HDD:', prevKey);
    } else {
      // Check for existing HDD in selectedItems
      prevHdd = selectedItems.find(i => 
        i.meta?.component === "HDD" && 
        i.meta?.contextKey === currentContextKey
      );
      prevKey = prevHdd ? `${prevHdd.meta.brand}::${prevHdd.meta.capacity}` : "";
      console.log('📦 Rendering HDD sections for context:', currentContextKey, '| Previous HDD:', prevKey);
    }

    const preferredList = [];
    Object.keys(hddData).forEach(brand => {
      (hddData[brand] || []).forEach(h => { if (h.preferred) preferredList.push({ ...h, brand }); });
    });

    const sortedPreferredList = sortByPriceLowToHigh(preferredList);

    if (sortedPreferredList.length) {
      const wrap = document.createElement("div");
      wrap.className = "button-row";

      sortedPreferredList.forEach(h => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "choice-btn";
        btn.textContent = h.label;
        btn.dataset.brand = h.brand;
        btn.dataset.capacity = h.capacity;

        if (prevKey === `${h.brand}::${h.capacity}`) btn.classList.add("selected");

        btn.addEventListener("click", (e) => {
          e.preventDefault();
          selectHdd(btn, h, h.brand);
        });
        wrap.appendChild(btn);
      });
      preferredContainer.appendChild(wrap);
    } else {
      preferredContainer.innerHTML = "<div class='small-muted'>No preferred HDDs found</div>";
    }

    Object.keys(hddData).forEach(brand => {
      const arr = hddData[brand];
      if (!Array.isArray(arr) || arr.length === 0) return;

      const sortedArr = sortHddByPriceLowToHigh(arr);

      const section = document.createElement("div");
      section.className = "brand-section";

      const header = document.createElement("div");
      header.className = "brand-header";
      header.textContent = brand;

      const body = document.createElement("div");
      body.className = "brand-body";

      const wasOpen = document.querySelector(`.brand-section[data-brand="${brand}"] .brand-body.open`);
      if (wasOpen) body.classList.add("open");

      header.addEventListener("click", (e) => {
        e.preventDefault();
        body.classList.toggle("open");
      });

      sortedArr.forEach(h => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "choice-btn";
        btn.textContent = h.label;
        btn.dataset.brand = brand;
        btn.dataset.capacity = h.capacity;

        if (prevKey === `${brand}::${h.capacity}`) btn.classList.add("selected");

        btn.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();
          selectHdd(btn, h, brand);
        });

        body.appendChild(btn);
      });

      section.appendChild(header);
      section.appendChild(body);
      section.dataset.brand = brand;
      brandwiseContainer.appendChild(section);
    });

    // Ensure summary and pricing reflect any manual HDD selection for this context
    // If a manual HDD is recorded but not present in selectedItems yet, add it.
    if (manualHddSelections[currentContextKey]) {
      const manual = manualHddSelections[currentContextKey];
      const exists = selectedItems.some(i => i.meta?.component === 'HDD' && i.meta?.contextKey === currentContextKey);
      if (!exists) {
        selectedItems.push({
          id: `hdd::${currentContextKey}::${manual.brand}::${manual.capacity}`,
          label: `${manual.brand} ${manual.label}`,
          price: parsePrice(manual.value),
          qty: 1,
          meta: {
            component: 'HDD',
            brand: manual.brand,
            capacity: manual.capacity,
            contextKey: currentContextKey,
            category: currentCategory,
            brandContext: currentBrand,
            mpContext: currentMP,
          }
        });
        updateSummary();
        saveSessionData();
      }
    }
  }

  /* ---------- Smart HDD auto-select based on camera count + resolution ---------- */
  // Track manual HDD selections per brand/MP context
  let manualHddSelections = {}; // Format: { "DVR::HIKVISION::5MP": { brand: "Seagate", capacity: "2TB", ... }, ... }
  
  function autoSelectHdd() {
    const camCount = parseInt(document.getElementById("num-cameras-input")?.value || 0, 10);
    const res = (currentMP || "").toUpperCase();
    if (!camCount || !data?.HDD) return;
    
    // If URL params specify HDD, do not auto-select!
    if (window.urlQuoteParams && window.urlQuoteParams.hddSize) {
      console.log('👉 URL HDD param exists:', window.urlQuoteParams.hddSize, '- skipping auto-select');
      return;
    }

    // For DVR we require MP; for NVR we don't
    if (currentCategory !== 'NVR' && !res) return;
    
    // Check if user has manually selected HDD for this context
    const contextKey = currentCategory === 'NVR' 
      ? `${currentCategory}::${currentBrand}` 
      : `${currentCategory}::${currentBrand}::${currentMP}`;
    
    // If any HDD already exists in selectedItems for this context, do not auto-select
    const existingHdd = selectedItems.find(i => i.meta?.component === 'HDD' && i.meta?.contextKey === contextKey);
    if (existingHdd) {
      console.log('👉 HDD already selected for', contextKey, '- skipping auto-select');
      return;
    }

    // If session has HDD for this context, wait for restore instead of auto-selecting
    try {
      const saved = localStorage.getItem(SESSION_KEY);
      if (saved) {
        const all = JSON.parse(saved);
        const sess = all[contextKey];
        const sessHdd = sess?.selectedItems?.find(i => i.meta?.component === 'HDD' && i.meta?.contextKey === contextKey);
        if (sessHdd) {
          console.log('👉 Session HDD exists for', contextKey, '- skipping auto-select');
          return;
        }
      }
    } catch (e) { /* ignore */ }

    if (manualHddSelections[contextKey]) {
      console.log('👉 User has manually selected HDD for', contextKey, '- skipping auto-select');
      return; // Skip auto-selection if user has made a manual choice
    }

    let target = "500 GB";
    if (currentCategory === 'NVR') {
      if (camCount <= 6) target = "500 GB";
      else if (camCount <= 16) target = "2 TB";
      else target = "4 TB";
    } else if (res.includes("5MP")) {
      if (camCount <= 6) target = "1 TB";
      else if (camCount <= 8) target = "2 TB";
      else if (camCount <= 16) target = "4 TB";
      else target = "6 TB";
    } else {
      if (camCount <= 6) target = "500 GB";
      else if (camCount <= 12) target = "1 TB";
      else if (camCount <= 20) target = "2 TB";
      else target = "4 TB";
    }

    setTimeout(() => {
      const btns = document.querySelectorAll("#preferred-hdd .choice-btn");
      btns.forEach(b => { if (b.textContent.toLowerCase().includes(target.toLowerCase())) b.click(); });
    }, 150);
  }

  function selectHdd(btn, hdd, brand, isManual = true) {
    document.querySelectorAll("#preferred-hdd .choice-btn, #brandwise-hdd .choice-btn").forEach(b => b.classList.remove("selected"));
    btn.classList.add("selected");
    
    const contextKey = currentCategory === 'NVR' ? `${currentCategory}::${currentBrand}` : `${currentCategory}::${currentBrand}::${currentMP}`;
    
    if (isManual) {
      manualHddSelections[contextKey] = {
        brand: brand,
        capacity: hdd.capacity,
        label: hdd.label,
        value: hdd.value
      };
      console.log('✅ Manual HDD selection recorded for', contextKey, ':', manualHddSelections[contextKey]);
    }

    // Clear any existing HDD for this context to force reselection
    selectedItems = selectedItems.filter(i => 
      !(i.meta?.component === 'HDD' && i.meta?.contextKey === contextKey)
    );
    
    // Make sure we also clear any existing HDD from the live summary display
    const summaryItems = document.getElementById('summary-items');
    if (summaryItems) {
      // Remove any HDD items from the summary display
      const hddElements = summaryItems.querySelectorAll('[data-component="HDD"]');
      hddElements.forEach(el => el.remove());
    }

    // Add the new HDD with the correct context key
    selectedItems.push({
      id: `hdd::${contextKey}::${brand}::${hdd.capacity}`,
      label: `${brand} ${hdd.label}`,
      price: parsePrice(hdd.value),
      qty: 1,
      meta: { 
        component: "HDD", 
        brand, 
        capacity: hdd.capacity,
        contextKey: contextKey, // Full context key
        category: currentCategory,
        brandContext: currentBrand,
        mpContext: currentMP
      }
    });

    updateSummary();
    saveSessionData();
  }

  /* ---------- Toggle Buttons ---------- */
  if (btnTogglePreferred) {
    btnTogglePreferred.addEventListener("click", (e) => {
      e.preventDefault();
      const pref = document.getElementById("preferred-hdd");
      const brandw = document.getElementById("brandwise-hdd");
      if (pref && brandw) { pref.style.display = "block"; brandw.style.display = "none"; }
      btnTogglePreferred.classList.add("active");
      if (btnToggleBrandwise) btnToggleBrandwise.classList.remove("active");
      renderHddSections();
    });
  }

  if (btnToggleBrandwise) {
    btnToggleBrandwise.addEventListener("click", (e) => {
      e.preventDefault();
      const pref = document.getElementById("preferred-hdd");
      const brandw = document.getElementById("brandwise-hdd");
      if (pref && brandw) { pref.style.display = "none"; brandw.style.display = "block"; }
      if (btnTogglePreferred) btnTogglePreferred.classList.remove("active");
      btnToggleBrandwise.classList.add("active");
      renderHddSections();
    });
  }

  /* ---------- Toggle selection ---------- */
  function toggleSelectItem(item, buttonEl) {
    const found = selectedItems.find(i => i.id === item.id);
    if (found) {
      selectedItems = selectedItems.filter(i => i.id !== item.id);
      buttonEl.classList.remove('selected');
    } else {
      selectedItems.push(item);
      buttonEl.classList.add('selected');
    }
    updateSummary();
    saveSessionData();
  }

  /* ---------- Summary ---------- */
  function updateSummary() {
    if (!currentCategory || !currentBrand) {
      console.warn('[SUMMARY] Skipping - context not set:', {currentCategory, currentBrand});
      return;
    }
    if (currentCategory === 'DVR' && !currentMP) {
        console.warn('[SUMMARY] Skipping for DVR - MP not set');
        return;
    }

    const contextFilteredItems = selectedItems.filter(item => {
      const contextKey = currentCategory === 'NVR' ? `${currentCategory}::${currentBrand}` : `${currentCategory}::${currentBrand}::${currentMP}`;

      // Rule 1: Always include accessories, regardless of context
      if (item.meta?.type === 'accessory') {
        return true;
      }
      
      // Rule 2: Only include HDD if it matches the current context
      if (item.meta?.component === 'HDD') {
        return item.meta?.contextKey === contextKey;
      }

      // Rule 3: For other components (cameras, recorders), filter by the current context
      if (item.meta?.category === currentCategory && item.meta?.brand === currentBrand) {
        if (currentCategory === 'DVR') {
          return item.meta?.mp === currentMP;
        }
        if (currentCategory === 'NVR') {
          // For NVR, mp is not part of the context
          return true;
        }
        // Fallback for any other potential categories
        return item.meta?.mp === currentMP;
      }

      // If it's not an accessory and not in the current context, exclude it.
      return false;
    });
    
    console.log('[SUMMARY UPDATE] Current Context:', {currentCategory, currentBrand, currentMP}, 'Filtered:', contextFilteredItems.length, 'Total:', selectedItems.length);
    console.log('[DEBUG BRANDS]', selectedItems.map(i => ({label: i.label, brand: i.meta?.brand, category: i.meta?.category, mp: i.meta?.mp})));
    console.log('[FILTERED ITEMS TO DISPLAY]', contextFilteredItems.map(i => ({label: i.label, brand: i.meta?.brand, category: i.meta?.category, mp: i.meta?.mp, component: i.meta?.component})));
    console.log('[WILL RENDER]', contextFilteredItems.length, 'items for brand:', currentBrand);
    
    // Sort contextFilteredItems based on component type for display order
    const sortedContextFilteredItems = [...contextFilteredItems].sort((a, b) => {
      const getOrder = (item) => {
        if (item.meta?.component === 'Camera') return 1;
        if (item.meta?.component === 'Recorder') return 2;
        if (item.meta?.component === 'HDD') return 3;
        if (item.meta?.type === 'accessory' && item.label?.includes('Monitor')) return 4;
        if (item.meta?.type === 'accessory' && item.label?.includes('Rack')) return 5;
        if (item.meta?.type === 'accessory') return 6;
        return 7; // Fallback for any other types
      };
      return getOrder(a) - getOrder(b);
    });

    // Ensure tags container exists and render selected tags
    if (!selectedTagsEl) {
      const sticky = document.getElementById('sticky-totals');
      if (sticky) {
        selectedTagsEl = document.createElement('div');
        selectedTagsEl.id = 'selected-tags';
        selectedTagsEl.className = 'selected-tags';
        // Place tags inside sticky totals, below the totals grid
        sticky.appendChild(selectedTagsEl);
      }
    }

    // Inject minimal styles if not present
    if (!document.getElementById('selected-tags-style')) {
      const style = document.createElement('style');
      style.id = 'selected-tags-style';
      style.textContent = `
        .selected-tags { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 8px; }
        .selected-tag { background: #e8f0fe; color: #1a73e8; border: 1px solid #c6dafc; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        @media (max-width: 980px) { .selected-tag { font-size: 11px; } }
      `;
      document.head.appendChild(style);
    }

    if (selectedTagsEl) {
      selectedTagsEl.innerHTML = '';
            const cam = sortedContextFilteredItems.find(i => i.meta?.component === 'Camera');
      const rec = sortedContextFilteredItems.find(i => i.meta?.component === 'Recorder');
      const hdd = sortedContextFilteredItems.find(i => i.meta?.component === 'HDD');
      const tags = [];
       const channelTag = rec?.meta?.channel ? normalizeChannel(rec.meta.channel) : null;
         if (channelTag && currentCategory) {
      tags.push(channelTag + ' CH ' + currentCategory);
    } else if (channelTag) {
      tags.push(channelTag + ' CH');
    } else if (currentCategory) {
      tags.push(currentCategory);
    }

      if (currentBrand) tags.push(currentBrand);



      const mpTag = (currentCategory === 'NVR')
        ? (cam?.meta?.mp || '')
        : (currentMP || cam?.meta?.mp || '');
      if (mpTag) tags.push(mpTag);

      // Add cameras count from num-cameras-input
      const cameraCountTagVal = parseInt(document.getElementById('num-cameras-input')?.value || 0, 10);

      // Prefer camera button label for the quantity chip; fallback to channel when missing
      if (!Number.isNaN(cameraCountTagVal) && cameraCountTagVal > 0) {
        // Use only the camera button label (no brand/type/resolution)
        const cameraLabel = (cam?.meta?.cameraLabel || cam?.label || '').trim();
        console.log('[DEBUG CAMERA LABEL]', cameraLabel);
        if (cameraLabel) {
          tags.push(cameraLabel + ' x ' + cameraCountTagVal + ' CAM');
        } else if (channelTag) {
          tags.push(channelTag + ' x ' + cameraCountTagVal + ' CAM');
        }
      }

      const capacityTag = hdd?.meta?.capacity ? String(hdd.meta.capacity).replace(/\s+/g, '') : null;
      if (capacityTag) tags.push(capacityTag);

      // Include accessory labels as tags (context-aware)
      const accessories = sortedContextFilteredItems.filter(i => i.meta?.type === 'accessory');
      accessories.forEach(acc => tags.push(acc.label));

      const shrinkTag = (label) => {
        const s = String(label || '').trim();
        if (s.length <= 12) return s;
        const preserved = new Set(['x', 'X', 'CH', 'CAM', 'TB', 'MP', 'DVR', 'NVR']);
        return s.split(/\s+/).map(tok => {
          if (!tok) return '';
          if (preserved.has(tok)) return tok;
          if (/\d/.test(tok)) return tok;
          if (/^[A-Z]{1,3}$/.test(tok)) return tok;
          const parts = tok.split(/[-_/]+/);
          if (parts.length > 1) {
            return parts.map(p => (/\d/.test(p) ? p : (p[0] || '').toUpperCase())).join('');
          }
          return (tok[0] || '').toUpperCase();
        }).filter(Boolean).join(' ');
      };

      tags.filter(Boolean).forEach(t => {
        const chip = document.createElement('span');
        chip.className = 'selected-tag';
        chip.textContent = shrinkTag(t);
        selectedTagsEl.appendChild(chip);
      });
    }

    summaryItemsEl.innerHTML = '';
    sortedContextFilteredItems.forEach(item => {
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
        <div class="price">${item.price ? formatINR(item.price * (item.qty || 1)) : 'Price on Inquiry'}</div>
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
          updateSummary();
          saveSessionData();
          // Update camera button text if this is a camera
          if (item.meta?.component === 'Camera') {
            const cameraButtons = document.querySelectorAll('.camera-btn');
            cameraButtons.forEach(btn => {
              if (btn.dataset.cameraLabel === item.meta.cameraLabel) {
                updateCameraButtonText(btn, item.qty);
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
          updateSummary();
          saveSessionData();
          // Update camera button text if this is a camera
          if (item.meta?.component === 'Camera') {
            const cameraButtons = document.querySelectorAll('.camera-btn');
            cameraButtons.forEach(btn => {
              if (btn.dataset.cameraLabel === item.meta.cameraLabel) {
                updateCameraButtonText(btn, item.qty);
              }
            });
          }
        }
      });
    });

    // Update camera status bar
    updateCameraStatusBar();

    // Calculate additional items based on camera count
    const cameraCount = parseInt(document.getElementById('num-cameras-input')?.value || 6, 10);
    const additionalItems = [];
    
    // 1. SMPS (Power Supply) based on camera count
    let smpsValue = 0;
    let smpsLabel = '';
    if (cameraCount <= 4) {
      smpsValue = parsePrice(data.items?.['SMPS 4']?.value) || 500;
      smpsLabel = data.items?.['SMPS 4']?.label || 'SMPS 4CH - ₹500';
    } else if (cameraCount <= 8) {
      smpsValue = parsePrice(data.items?.['SMPS 8']?.value) || 800;
      smpsLabel = data.items?.['SMPS 8']?.label || 'SMPS 8CH - ₹800';
    }
    if (smpsValue > 0) {
      additionalItems.push({ label: smpsLabel, price: smpsValue, qty: 1 });
    }
    
    // 2. BNC WIRED (2 per camera)
    const bncValue = parsePrice(data.items?.['BNC WIRED']?.value) || 20;
    const bncLabel = data.items?.['BNC WIRED']?.label || 'BNC Wired - ₹20';
    additionalItems.push({ label: bncLabel, price: bncValue, qty: cameraCount * 2 });
    
    // 3. DC connectors (1 per camera)
    console.log('🔍 Data check:', {
      dataExists: typeof data !== 'undefined',
      itemsExists: !!data?.items,
      DCExists: !!data?.items?.['DC'],
      DCValue: data?.items?.['DC']?.value,
      DCLabel: data?.items?.['DC']?.label,
      allItems: data?.items ? Object.keys(data.items) : 'no items'
    });
    const dcValue = parsePrice(data.items?.['DC']?.value) || 20;
    const dcLabel = data.items?.['DC']?.label || 'DC Connector - ₹20';
    additionalItems.push({ label: dcLabel, price: dcValue, qty: cameraCount });
    // 4. Back Box (1 per camera)
    const backBoxValue = parsePrice(data.items?.['BACK BOX']?.value) || 20;
    const backBoxLabel = data.items?.['BACK BOX']?.label || 'Back Box - ₹20';
    additionalItems.push({ label: backBoxLabel, price: backBoxValue, qty: cameraCount });
    
    // 5. Dlink 3+1 Switch (1 per 4 cameras, rounded up)
    const switchCount = Math.ceil(cameraCount / 4);
    const dlinkValue = parsePrice(data.items?.['Dlink']?.value) || 875;
    const dlinkLabel = data.items?.['Dlink']?.label || 'Dlink 3+1 Cable - ₹875';
    additionalItems.push({ label: dlinkLabel, price: dlinkValue, qty: switchCount });
    console.log("additionalItems", additionalItems)

    const subtotal = contextFilteredItems.reduce((acc, it) => acc + (it.price ? it.price * (it.qty || 1) : 0), 0);
    const additionalTotal = additionalItems.reduce((acc, it) => acc + (it.price * it.qty), 0);
    const finalSubtotal = subtotal + additionalTotal;
    const gst = finalSubtotal * GST_RATE;
    
    // Installation charge (₹600 per camera) - added AFTER GST
    const installationCharge = 600 * cameraCount;
    const total = finalSubtotal + gst + installationCharge;

    // Display additional items in summary
    additionalItems.forEach(item => {
      const row = document.createElement('div');
      row.className = 'summary-item auto-calculated';
      row.innerHTML = `
        <div class="meta">
          <div style="font-weight:700; color: #666;">${item.label} <small>(auto)</small></div>
          <div class="small">Qty: ${item.qty}</div>
        </div>
        <div class="price" style="color: #666;">${formatINR(item.price * item.qty)}</div>
      `;
      summaryItemsEl.appendChild(row);
    });

    summarySubtotalEl.textContent = formatINR(finalSubtotal);
    summaryGstEl.textContent = formatINR(gst);
    
    summaryTotalEl.textContent = formatINR(total);

    // Compute MIN cost (Total + 3000) and update sticky totals display
    const minTotal = total + 3000;
    const stickyTotal = document.getElementById('sticky-total');
    if (stickyTotal) {
      stickyTotal.textContent = formatINR(minTotal);
    }
    
    // Add profit row (use values from data.additionalItems)
    const profitPercentage = parseFloat(data.additionalItems?.profitPercentage?.value) || 15; // Changed from 0.15 to 15 (15%)
    const profitFlat = parseFloat(data.additionalItems?.profit?.value) || 0;
    // First add flat profit, then apply percentage on the grand total
    const totalWithFlatProfit = total + profitFlat;
    const profitAmount = totalWithFlatProfit * (profitPercentage / 100); // Convert percentage to decimal
    const finalTotal = totalWithFlatProfit + profitAmount;
    
    console.log('📊 Summary Calculation:', {
      contextFilteredItems: contextFilteredItems.length,
      subtotal: contextFilteredItems.reduce((acc, it) => acc + (it.price ? it.price * (it.qty || 1) : 0), 0),
      additionalTotal: additionalItems.reduce((acc, it) => acc + (it.price * it.qty), 0),
      finalSubtotal: finalSubtotal,
      gst: gst,
      installationCharge: installationCharge,
      total: total,
      profitPercentage: profitPercentage,
      profitFlat: profitFlat,
      profitAmount: profitAmount,
      finalTotal: finalTotal
    });
    
    // Add Material Cost row (subtotal + GST) - BEFORE installation charge
    const materialCost = finalSubtotal + gst;
    const summaryTotalsEl = document.querySelector('.summary-totals');
    const totalRow = summaryTotalsEl.querySelector('.total-row');
    
    let materialCostRow = document.getElementById('material-cost-row');
    if (!materialCostRow && summaryTotalsEl) {
      materialCostRow = document.createElement('div');
      materialCostRow.id = 'material-cost-row';
      materialCostRow.className = 'row no-margin';
      materialCostRow.style.borderTop = '1px solid #ddd';
      materialCostRow.style.marginTop = '8px';
      materialCostRow.style.paddingTop = '8px';
      materialCostRow.innerHTML = `
        <div class="col s7" style="font-weight: 600;">Material Cost</div>
        <div class="col s5 right-align" id="material-cost-amount" style="font-weight: 600;">${formatINR(materialCost)}</div>
      `;
      // Insert before the total row
      if (totalRow) {
        summaryTotalsEl.insertBefore(materialCostRow, totalRow);
      } else {
        summaryTotalsEl.appendChild(materialCostRow);
      }
    } else if (materialCostRow) {
      materialCostRow.querySelector('#material-cost-amount').textContent = formatINR(materialCost);
    }
    
    // Add installation charge row after material cost and before total
    let installationRow = document.getElementById('installation-charge-row');
    if (!installationRow && summaryTotalsEl) {
      installationRow = document.createElement('div');
      installationRow.id = 'installation-charge-row';
      installationRow.className = 'row no-margin';
      installationRow.innerHTML = `
        <div class="col s7">Installation Charge (${cameraCount} cameras)</div>
        <div class="col s5 right-align" id="installation-charge-amount">${formatINR(installationCharge)}</div>
      `;
      // Insert before the total row
      if (totalRow) {
        summaryTotalsEl.insertBefore(installationRow, totalRow);
      } else {
        summaryTotalsEl.appendChild(installationRow);
      }
    } else if (installationRow) {
      // Update existing row
      installationRow.querySelector('.col.s7').textContent = `Installation Charge (${cameraCount} cameras)`;
      installationRow.querySelector('#installation-charge-amount').textContent = formatINR(installationCharge);
    }

    // Add Min Cost to Customer row (shows Total + 3000)
    let profitRow = document.getElementById('profit-row');
    if (!profitRow && summaryTotalsEl) {
      profitRow = document.createElement('div');
      profitRow.id = 'profit-row';
      profitRow.className = 'row no-margin';
      profitRow.style.marginTop = '4px';
      profitRow.innerHTML = `
        <div class="col s7" style="color: #4caf50; font-weight: 600;">Min cost to the customer</div>
        <div class="col s5 right-align" id="profit-amount" style="color: #4caf50; font-weight: 600;">${formatINR(minTotal)}</div>
      `;
      summaryTotalsEl.appendChild(profitRow);
    } else if (profitRow) {
      // Update existing row
      profitRow.querySelector('.col.s7').innerHTML = `Min cost to the customer`;
      profitRow.querySelector('#profit-amount').textContent = formatINR(minTotal);
    }

    // Add Profit row (Cost to Customer - Material Cost) with eye icon toggle
    const profitValue = finalTotal - materialCost;
    let profitDisplayRow = document.getElementById('profit-display-row');
    if (!profitDisplayRow && summaryTotalsEl) {
      profitDisplayRow = document.createElement('div');
      profitDisplayRow.id = 'profit-display-row';
      profitDisplayRow.className = 'row no-margin';
      profitDisplayRow.style.marginTop = '4px';
      profitDisplayRow.innerHTML = `
        <div class="col s7" style="color: #2196f3; font-weight: 600;">Profit <i class="fas fa-eye" id="profit-eye-icon" style="cursor: pointer; margin-left: 8px; font-size: 14px;"></i></div>
        <div class="col s5 right-align" id="profit-display-amount" style="color: #2196f3; font-weight: 600; user-select: none;">•••</div>
      `;
      summaryTotalsEl.appendChild(profitDisplayRow);
      
      // Add eye icon toggle handlers
      const eyeIcon = profitDisplayRow.querySelector('#profit-eye-icon');
      const profitAmount = profitDisplayRow.querySelector('#profit-display-amount');
      
      if (eyeIcon) {
        // Support both mouse and touch events for mobile
        const showProfit = function() {
          profitAmount.textContent = formatINR(profitValue);
          eyeIcon.classList.add('fa-eye-slash');
          eyeIcon.classList.remove('fa-eye');
        };
        
        const hideProfit = function() {
          if (profitAmount.textContent !== '•••') {
            profitAmount.textContent = '•••';
            eyeIcon.classList.add('fa-eye');
            eyeIcon.classList.remove('fa-eye-slash');
          }
        };
        
        eyeIcon.addEventListener('mousedown', showProfit);
        eyeIcon.addEventListener('touchstart', function(e) {
          e.preventDefault();
          showProfit();
        });
        
        document.addEventListener('mouseup', hideProfit);
        document.addEventListener('touchend', hideProfit);
      }
    } else if (profitDisplayRow) {
      profitDisplayRow.querySelector('#profit-display-amount').textContent = '•••';
      
      // Re-attach handlers if they don't exist
      const eyeIcon = profitDisplayRow.querySelector('#profit-eye-icon');
      const profitAmount = profitDisplayRow.querySelector('#profit-display-amount');
      
      if (eyeIcon && !eyeIcon._hasHandlers) {
        eyeIcon._hasHandlers = true;
        
        // Support both mouse and touch events for mobile
        const showProfit = function() {
          profitAmount.textContent = formatINR(profitValue);
          eyeIcon.classList.add('fa-eye-slash');
          eyeIcon.classList.remove('fa-eye');
        };
        
        const hideProfit = function() {
          if (profitAmount.textContent !== '•••') {
            profitAmount.textContent = '•••';
            eyeIcon.classList.add('fa-eye');
            eyeIcon.classList.remove('fa-eye-slash');
          }
        };
        
        eyeIcon.addEventListener('mousedown', showProfit);
        eyeIcon.addEventListener('touchstart', function(e) {
          e.preventDefault();
          showProfit();
        });
        
        document.addEventListener('mouseup', hideProfit);
        document.addEventListener('touchend', hideProfit);
      }
    }
    
    // Update sticky profit row with discount and percentage calculations
    const stickyProfitRow = document.getElementById('sticky-profit-row');
    const stickyProfitAmount = document.getElementById('sticky-profit-amount');
    const differenceAmount = document.getElementById('difference-amount');
    const discountAmountEl = document.getElementById('discount-amount');
    const percentageAmountEl = document.getElementById('percentage-amount');
    
    // MIN total already computed above
    if (stickyTotal) {
      stickyTotal.textContent = formatINR(minTotal);
    }

    // Base MAX = MIN + profitPercentage from data.json
    const baseMaxUnrounded = minTotal * (1 + (profitPercentage / 100));
    const baseMaxTotal = Math.round(baseMaxUnrounded);
    // Remove target margin behavior; percentage-amount directly controls additionalPercentage

    // Apply UI percentage and discount to derive sticky-profit-amount
    // Apply additionalPercentage (can be negative) to base MAX
    let displayMaxTotal = Math.round(baseMaxTotal * (1 + ((additionalPercentage || 0) / 100)));
    const maxAfterDiscount = Math.max(0, displayMaxTotal - (additionalDiscount || 0));
    if (stickyProfitRow && stickyProfitAmount) {
      stickyProfitAmount.textContent = formatINR(maxAfterDiscount);
    }

    // Update sticky profit margin (MAX - materialCost) with eye icon toggle
    const stickyProfitMarginAmount = document.getElementById('sticky-profit-margin-amount');
    const stickyProfitMarginRow = document.getElementById('sticky-profit-margin-row');
    const stickyProfitEyeIcon = document.getElementById('sticky-profit-eye-icon');

    const stickyProfitMargin = maxAfterDiscount - materialCost;

    console.log('💼 Sticky Profit Margin:', { stickyProfitMargin, maxAfterDiscount, materialCost, elementFound: !!stickyProfitMarginAmount });

    if (stickyProfitMarginAmount) {
      stickyProfitMarginAmount.textContent = '•••';
      stickyProfitMarginAmount.dataset.profitValue = stickyProfitMargin;
      // Support both mouse and touch events for mobile
      const showProfit = function() {
        stickyProfitMarginAmount.textContent = formatINR(parseFloat(stickyProfitMarginAmount.dataset.profitValue));
        stickyProfitEyeIcon.classList.add('fa-eye-slash');
        stickyProfitEyeIcon.classList.remove('fa-eye');
      };
      
      const hideProfit = function() {
        if (stickyProfitMarginAmount.textContent !== '•••') {
          stickyProfitMarginAmount.textContent = '•••';
          stickyProfitEyeIcon.classList.add('fa-eye');
          stickyProfitEyeIcon.classList.remove('fa-eye-slash');
        }
      };
      
      stickyProfitEyeIcon.addEventListener('mousedown', showProfit);
      stickyProfitEyeIcon.addEventListener('touchstart', function(e) {
        e.preventDefault();
        showProfit();
      });
      
      document.addEventListener('mouseup', hideProfit);
      document.addEventListener('touchend', hideProfit);
    } else {
      console.warn('⚠️ Sticky profit margin element not found');
    }

    // DIFFERENCE = MAX - MIN
    if (differenceAmount) {
      const diff = Math.max(0, maxAfterDiscount - minTotal);
      differenceAmount.textContent = '+ ' + formatINR(diff);
    }
    
    // Removed percentage-display update as per request
    
    // Update discount amount display (finalTotal - discount)
    if (discountAmountEl) {
      const discountedAmount = finalTotal - additionalDiscount;
      discountAmountEl.textContent = formatINR(discountedAmount);
    }
    
    // Update discount amount display (finalTotal - discount)
    if (discountAmountEl) {
      const discountedAmount = finalTotal - additionalDiscount;
      discountAmountEl.textContent = formatINR(discountedAmount);
    }
    
    // Keep percentage input reflecting the user-controlled additionalPercentage
    if (percentageAmountEl) {
      percentageAmountEl.value = Math.round(additionalPercentage);
    }
    
    btnWP.disabled = contextFilteredItems.length === 0;
    
    // Update WhatsApp preview
    updateWhatsAppPreview();
  }

  /* ---------- Update Camera Status Bar ---------- */
  function updateCameraStatusBar() {
    const requiredCount = parseInt(document.getElementById('num-cameras-input')?.value || 6, 10);
    // Only count cameras from the CURRENT CONTEXT (category, brand, MP)
    const selectedCount = selectedItems
      .filter(item => 
        item.meta?.component === 'Camera' && 
        item.meta?.category === currentCategory && 
        item.meta?.brand === currentBrand && 
        (currentCategory === 'NVR' ? true : item.meta?.mp === currentMP)) // Adjust filter for NVR
      .reduce((sum, item) => sum + (item.qty || 0), 0);
    
    const requiredEl = document.getElementById('camera-required-count');
    const selectedEl = document.getElementById('camera-selected-count');
    
    if (requiredEl && selectedEl) {
      // Update with animation
      const oldSelected = parseInt(selectedEl.textContent || '0');
      if (oldSelected !== selectedCount) {
        selectedEl.classList.add('updated');
        setTimeout(() => selectedEl.classList.remove('updated'), 300);
      }
      
      requiredEl.textContent = requiredCount;
      selectedEl.textContent = selectedCount;
      
      // Update status bar font colors based on match
      const statusBar = document.getElementById('camera-status-bar');
      const floatingBtn = document.getElementById('btn-generate-wp');
      
      if (statusBar) {
        const labels = statusBar.querySelectorAll('.status-label');
        const counts = statusBar.querySelectorAll('.status-count');
        const separator = statusBar.querySelector('.status-separator');
        
        if (selectedCount === requiredCount && selectedCount > 0) {
          // Green when perfect match
          labels.forEach(el => el.style.color = '#11998e');
          counts.forEach(el => {
            el.style.color = '#11998e';
            el.style.borderColor = '#11998e';
          });
          if (separator) separator.style.color = '#11998e';
          
          // Update floating button to green
          if (floatingBtn) {
            floatingBtn.style.backgroundColor = '#11998e';
            floatingBtn.style.boxShadow = '0 4px 20px rgba(17,153,142,0.3)';
          }
        } else if (selectedCount > requiredCount) {
          // Red when exceeding
          labels.forEach(el => el.style.color = '#eb3349');
          counts.forEach(el => {
            el.style.color = '#eb3349';
            el.style.borderColor = '#eb3349';
          });
          if (separator) separator.style.color = '#eb3349';
          
          // Update floating button to red
          if (floatingBtn) {
            floatingBtn.style.backgroundColor = '#eb3349';
            floatingBtn.style.boxShadow = '0 4px 20px rgba(235,51,73,0.3)';
          }
        } else {
          // Default purple
          labels.forEach(el => el.style.color = '#667eea');
          counts.forEach(el => {
            el.style.color = '#764ba2';
            el.style.borderColor = '#667eea';
          });
          if (separator) separator.style.color = '#667eea';
          
          // Update floating button to purple
          if (floatingBtn) {
            floatingBtn.style.backgroundColor = '#667eea';
            floatingBtn.style.boxShadow = '0 4px 20px rgba(102,126,234,0.3)';
          }
        }
        
        // Update floating button text based on camera count match
        if (floatingBtn) {
          if (selectedCount === requiredCount && selectedCount > 0) {
            floatingBtn.innerHTML = originalBtnWPHtml; // Restore original HTML
            // Remove styles when counts match
            floatingBtn.style.paddingLeft = '';
            floatingBtn.style.paddingRight = '';
            floatingBtn.style.whiteSpace = ''; // text-wrap-mode is not a direct style property, use white-space
            floatingBtn.disabled = false; // Enable button
            floatingBtn.style.pointerEvents = ''; // Re-enable pointer events
            floatingBtn.style.cursor = ''; // Restore default cursor
            floatingBtn.style.transform = ''; // Remove rotation
          } else {
            floatingBtn.textContent = `${requiredCount} / ${selectedCount}`;
            // Add styles when counts don't match
            floatingBtn.style.paddingLeft = '25px';
            floatingBtn.style.paddingRight = '50px';
            floatingBtn.style.whiteSpace = 'nowrap';
            floatingBtn.disabled = true; // Disable button
            floatingBtn.style.pointerEvents = 'none'; // Disable pointer events
            floatingBtn.style.cursor = 'not-allowed'; // Change cursor to indicate disabled state
            floatingBtn.style.transform = 'rotate(-30deg)'; // Add rotation
          }
        }
      }
    }
  }
 
  /* ---------- WhatsApp ---------- */
  function buildWhatsAppMessage() {
    console.log('Building WhatsApp message with percentage:', additionalPercentage);
    const camCount = parseInt(num.value || 0, 10);
    const customerName = document.getElementById('customer-name')?.value?.trim() || '';
    
    // Helper function to get channel count
    const getChannel = n => {
      const ch = [4, 8, 16, 32].find(c => n <= c);
      if (!ch) return 32;
      return ch;
    };
    
    let msg = [];
    
    // Add greeting if customer name provided
    if (customerName) {
     msg.push(`Dear ${customerName},`);
msg.push('');
msg.push(`Thank you for choosing *Smartronic.online* for your CCTV security needs!`);
msg.push(`We appreciate your interest and are glad to share a customized quotation designed to give you the best value and protection for your property.`);
msg.push('');
    }
    
    // Filter items to ONLY show those from the current context (category, brand, MP)
    const contextFilteredItems = selectedItems.filter(item => {
      if (item.meta?.component === 'Camera' || item.meta?.component === 'Recorder') {
        return item.meta?.category === currentCategory && 
               item.meta?.brand === currentBrand && 
               (currentCategory === 'NVR' ? true : item.meta?.mp === currentMP);
      }
      if (item.meta?.component === 'HDD') {
        // HDDs should be filtered by context key
        const contextKey = currentCategory === 'NVR' 
          ? `${currentCategory}::${currentBrand}` 
          : `${currentCategory}::${currentBrand}::${currentMP}`;
        return item.meta?.contextKey === contextKey;
      }
      if (item.meta?.type === 'accessory') {
        return true;
      }
      return item.meta?.category === currentCategory && 
             item.meta?.brand === currentBrand && 
             (currentCategory === 'NVR' ? true : item.meta?.mp === currentMP);
    });

    // Calculate totals
    const cameraCount = camCount;
    const additionalItems = [];
    
    // SMPS
    let smpsValue = 0, smpsLabel = '';
    if (cameraCount <= 4) {
      smpsValue = parsePrice(data.items?.['SMPS 4']?.value) || 500;
      smpsLabel = 'SMPS 4CH';
    } else if (cameraCount <= 8) {
      smpsValue = parsePrice(data.items?.['SMPS 8']?.value) || 800;
      smpsLabel = 'SMPS 8CH';
    }
    if (smpsValue > 0) additionalItems.push({ label: smpsLabel, price: smpsValue, qty: 1 });
    
    // BNC, DC, Back Box, Dlink
    const bncValue = parsePrice(data.items?.['BNC WIRED']?.value) || 20;
    const bncLabel = data.items?.['BNC WIRED']?.label || 'BNC Wired - ₹20';
    additionalItems.push({ label: bncLabel, price: bncValue, qty: cameraCount * 2 });
    
    const dcValue = parsePrice(data.items?.['DC']?.value) || 20;
    const dcLabel = data.items?.['DC']?.label || 'DC Connector - ₹20';
    additionalItems.push({ label: dcLabel, price: dcValue, qty: cameraCount });
    
    const backBoxValue = parsePrice(data.items?.['BACK BOX']?.value) || 20;
    const backBoxLabel = data.items?.['BACK BOX']?.label || 'Back Box - ₹20';
    additionalItems.push({ label: backBoxLabel, price: backBoxValue, qty: cameraCount });
    
    const dlinkValue = parsePrice(data.items?.['Dlink']?.value) || 437.5;
    const dlinkLabel = data.items?.['Dlink']?.label || 'Dlink 3+1 Cable - ₹875';
    additionalItems.push({ label: dlinkLabel, price: dlinkValue, qty: Math.ceil(cameraCount / 4) });
    
    const subtotal = contextFilteredItems.reduce((acc, it) => acc + (it.price ? it.price * (it.qty || 1) : 0), 0);
    const additionalTotal = additionalItems.reduce((acc, it) => acc + (it.price * it.qty), 0);
    const finalSubtotal = subtotal + additionalTotal;
    const gst = finalSubtotal * GST_RATE;
    const installationCharge = 600 * cameraCount;
    const total = finalSubtotal + gst + installationCharge;
    const minTotal = total + 3000; // Align with sticky MIN
    
    // Use SAME profit calculation as summary
    const profitPercentage = parseFloat(data.additionalItems?.profitPercentage?.value) || 15; // Changed from 0.15 to 15 (15%)
    const profitFlat = parseFloat(data.additionalItems?.profit?.value) || 0;
    // First add flat profit, then apply percentage on the grand total
    const totalWithFlatProfit = total + profitFlat;
    const profitAmount = totalWithFlatProfit * (profitPercentage / 100); // Convert percentage to decimal
    let finalTotal = totalWithFlatProfit + profitAmount;
    
    // Apply additional percentage (can be negative)
    finalTotal = finalTotal * (1 + ((additionalPercentage || 0) / 100));
    
    // Apply additional discount if set (same as in updateSummary)
    const actualCost = finalTotal - additionalDiscount; // Align with sticky MAX after discount
    // For WhatsApp: show only MAX, derived from sticky logic (MIN + profit%, then +percentage and -discount)
    const baseMaxWA = Math.round(minTotal * (1 + (profitPercentage / 100)));
    let stickyMaxWA = Math.round(baseMaxWA * (1 + ((additionalPercentage || 0) / 100)));
    stickyMaxWA = Math.max(0, stickyMaxWA - (additionalDiscount || 0));
    
    console.log('📊 WhatsApp Message Calculation:', {
      contextFilteredItems: contextFilteredItems.length,
      subtotal: subtotal,
      additionalTotal: additionalTotal,
      finalSubtotal: finalSubtotal,
      gst: gst,
      installationCharge: installationCharge,
      total: total,
      profitPercentage: profitPercentage,
      profitFlat: profitFlat,
      profitAmount: profitAmount,
      finalTotal: finalTotal,
      additionalPercentage: additionalPercentage,
      additionalDiscount: additionalDiscount,
      actualCost: actualCost
    });
    
    // For marketing display: show inflated price, then "discount" to actual price
    // We inflate it by dividing by 0.80 (which is same as multiplying by 1.25)
    const totalBeforeDiscount = Math.round(stickyMaxWA * 1.20); // Total Cost = sticky MAX + 20%
    
    // Get the MP of the selected NVR camera, or currentMP for DVR
    const displayMP = currentCategory === 'NVR' 
      ? (contextFilteredItems.find(item => item.meta?.component === 'Camera')?.meta?.mp || '')
      : currentMP;

    // Build main quote message
    let systemText = `*${currentBrand} ${currentCategory} Full HD ${getChannel(camCount)}-Channel System* | ${displayMP} × ${camCount} Cameras`;
    
        const stickyProfitEl = document.getElementById('sticky-profit-amount');
    const stickyProfitText = (stickyProfitEl && stickyProfitEl.textContent) ? stickyProfitEl.textContent.trim() : stickyProfitText;
// Show only MAX value in header
    systemText += ` | 🎉 *Now at 20% OFF: ${stickyProfitText}*`;
    
    msg.push(systemText);
    msg.push('');
    msg.push(`- ${currentMP} Outdoor/Indoor Cameras (${camCount} units) – Smart Night Vision, Motion Detection & Packed with Advanced Features for Complete Security`);

    // Find HDD in selected items
    const hddItem = contextFilteredItems.find(i => i.meta?.component === 'HDD');
    if (hddItem) {
      const hddName = hddItem.label.split(' - ')[0] || 'Hard Disk';
      msg.push(`- ${hddName} or TOSHIBA/ Seagate/ WD/ Consistent Hard Disk - Included`);
    } else {
      msg.push(`- TOSHIBA/ Seagate/ WD/ Consistent Hard Disk - Included`);
    }
    msg.push(`- Complete Cabling & Accessories (RJ45/BNC, DC, Camera Box, POE/SMPS) – *All Included!*`);
msg.push(`- *Professional Installation – Absolutely Free!*`);
msg.push(`- Includes up to 90m of premium cable. Extra cable if needed: ₹20/m + ₹20/m labour.`);
msg.push(`- Casing/piping optional: ₹20/m (additional to the above)`);
msg.push(`- Please arrange a stool/ladder or bear rental if required.`);
msg.push('');
    msg.push(`💰 Total Cost: ${formatINR(totalBeforeDiscount)}`);
    msg.push(`——————————————`);
    msg.push(`🎉 *Now at 20% OFF: ${stickyProfitText}*`);
msg.push('');

// Add conditional discount message if discount is selected
if (additionalDiscount > 0) {
  msg.push(`*If you can confirm the order today, I can offer you an extra ₹${additionalDiscount.toLocaleString('en-IN')} discount as a special deal.*`);
  msg.push(`*We just have a few open installation slots left this week, so it's a great chance to lock it in and save a bit more. 😊*`);
  msg.push('');
}

msg.push(`⭐ *Limited-Time Offer Includes:*`);
msg.push('- 100% Genuine Products (No Duplicates)');
msg.push(`- 2-Year *Brand Warranty* + 2-Year *Smartronic Service Warranty*`);
msg.push(`(Smartronic covers free service & no hidden or extended charges for DVR, Cameras, and HDD — including onsite support for the same for 2 years.)`);
msg.push('- Free Installation & Materials');
msg.push('- Optional 5-Year Replacement Warranty with AMC');
msg.push('');
msg.push(`💡 *Why Choose Smartronic.online?*`);
msg.push('- Smart Motion Detection Cameras with Clear Night Vision in Black & White or Colour');
msg.push('- Transparent, Affordable Pricing – No Hidden Costs');
msg.push('- Fast, Hassle-Free Installation & Service Support');
msg.push('- Trusted by 500+ Happy Customers Across Bangalore!');
msg.push('');
msg.push(`🛒 *Book Today & Lock Your 20% Discount!*`);
msg.push(`📞 Quick support: Call the same number or just reply *YES* to confirm!`);

    return msg.join('\n');
  }
  
  // Update WhatsApp preview textarea whenever summary changes
  function updateWhatsAppPreview() {
    const previewTextarea = document.getElementById('whatsapp-preview');
    if (previewTextarea) {
      previewTextarea.value = buildWhatsAppMessage();
    }
  }

  function base64EncodeUnicode(str) {
    const s = String(str ?? '');
    return btoa(encodeURIComponent(s).replace(/%([0-9A-F]{2})/g, (_, hex) => String.fromCharCode(parseInt(hex, 16))));
  }

  function base64DecodeUnicode(b64) {
    const s = String(b64 ?? '');
    return decodeURIComponent(Array.prototype.map.call(atob(s), (ch) => '%' + ('00' + ch.charCodeAt(0).toString(16)).slice(-2)).join(''));
  }

  function buildShareableQuoteUrl() {
    const currentParams = new URLSearchParams(window.location.search);
    const u = new URL(window.location.href);
    u.search = '';

    const cameraCount = parseInt(document.getElementById('num-cameras-input')?.value || 0, 10) || 6;
    const phone = (whatsappInput?.value || '').trim();
    const customerName = (document.getElementById('customer-name')?.value || '').trim();

    const hddItem = selectedItems.find(i => i?.meta?.component === 'HDD');
    const hddSize = (hddItem?.meta?.capacity || '').toString().trim();

    const camItem = selectedItems.find(i => i?.meta?.component === 'Camera');
    const mp = (currentCategory === 'NVR')
      ? ((camItem?.meta?.mp || '').toString().trim() || (currentMP || '').toString().trim())
      : (currentMP || '').toString().trim();

    const quoteParts = [
      phone.replace(/\D/g, ''),
      String(cameraCount),
      (currentCategory || '').toString().trim(),
      hddSize,
      mp,
      customerName,
      (window.urlQuoteParams?.id || '').toString().trim()
    ].map(v => (v ?? '').toString());
    u.searchParams.set('quote', quoteParts.join('|'));

    const leadIdParam = currentParams.get('lead_id');
    const midParam = currentParams.get('mid');
    if (leadIdParam) u.searchParams.set('lead_id', leadIdParam);
    if (midParam) u.searchParams.set('mid', midParam);

    const tagTexts = Array.from(document.querySelectorAll('#selected-tags .selected-tag')).map(el => (el.textContent || '').trim()).filter(Boolean);
    if (tagTexts.length) {
      u.searchParams.set('tags', tagTexts.join(','));
    }

    u.searchParams.set('disc', String(additionalDiscount || 0));
    u.searchParams.set('pct', String(additionalPercentage || 0));

    const parseMoneyText = (txt) => {
      const digits = String(txt || '').replace(/[^\d]/g, '');
      return digits ? parseInt(digits, 10) : 0;
    };

    const minQuote = parseMoneyText(document.getElementById('sticky-total')?.textContent);
    const maxQuote = parseMoneyText(document.getElementById('sticky-profit-amount')?.textContent);

    const state = {
      v: 1,
      phone: phone.replace(/\D/g, ''),
      customerName,
      cameraCount,
      category: currentCategory || '',
      brand: currentBrand || '',
      mp: currentMP || '',
      hddSize,
      additionalDiscount: additionalDiscount || 0,
      additionalPercentage: additionalPercentage || 0,
      minQuote,
      maxQuote,
      selectedItems: selectedItems || []
    };
    u.searchParams.set('qstate', base64EncodeUnicode(JSON.stringify(state)));
    return u.toString();
  }

  function applyUrlQuoteState() {
    const s = window.urlQuoteState;
    if (!s || s._applied) return;
    s._applied = true;

    if (typeof s.additionalDiscount === 'number') additionalDiscount = s.additionalDiscount;
    if (typeof s.additionalPercentage === 'number') additionalPercentage = s.additionalPercentage;

    if (discountDropdown) {
      discountDropdown.value = String(additionalDiscount || 0);
    }
    if (percentageAmount) {
      percentageAmount.value = String(Math.round(additionalPercentage || 0));
    }

    if (Array.isArray(s.selectedItems)) {
      selectedItems = s.selectedItems;
    }

    setTimeout(() => {
      try {
        const accessoryIds = new Set((selectedItems || []).filter(it => it?.meta?.type === 'accessory').map(it => it.id));
        document.querySelectorAll('#accessories .option-btn').forEach(btn => {
          const id = btn.dataset.id;
          if (!id) return;
          if (accessoryIds.has(id)) btn.classList.add('selected');
        });
      } catch (_) {}

      updateSummary();
      updateWhatsAppPreview();
    }, 0);
  }

  btnWP.addEventListener('click', (e) => {
    e.preventDefault();
    console.log('🖱️ btn-generate-wp clicked');
    // Get message from textarea (editable)
    const previewTextarea = document.getElementById('whatsapp-preview');
    const message = previewTextarea ? previewTextarea.value : buildWhatsAppMessage();
    const encoded = encodeURIComponent(message);

    try {
      const shareUrl = buildShareableQuoteUrl();
      console.log('🔗 Quote link:', shareUrl);

      const params = new URLSearchParams(window.location.search);
      const leadId = params.get('lead_id') || '';
      const mid = params.get('mid') || '';
      if (leadId || mid) {
        fetch('lead_save.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            leadId: leadId ? parseInt(leadId, 10) : null,
            mid,
            quote_link_append: shareUrl
          })
        })
          .then(r => r.json().catch(() => null))
          .then(payload => {
            if (payload && payload.success) {
              console.log('✅ Quote stored for lead:', payload.id || leadId || mid);
              try {
                if (window.parent && window.parent !== window) {
                  window.parent.postMessage({ type: 'quoteSaved', leadId: payload.id || leadId, mid, quoteUrl: shareUrl }, window.location.origin);
                }
              } catch (_) {}
            } else {
              console.warn('Failed to store quote:', payload);
            }
          })
          .catch(err => console.warn('Failed to store quote:', err));
      } else {
        console.warn('Missing lead_id/mid in quote iframe URL; quote not stored.');
      }
    } catch (err) {
      console.warn('Failed to build quote link:', err);
    }
    
    const custNumber = (whatsappInput && whatsappInput.value && (whatsappInput.value.match(/\d/g) || []).length >= 10)
      ? whatsappInput.value.replace(/\D/g, '')
      : '';
    
    // Build WhatsApp URL
    let url = 'https://api.whatsapp.com/send?text=' + encoded;
    
    // Add phone number with country code 91 (India) if provided
    if (custNumber) {
      url = url + '&phone=91' + custNumber;
    }
    
    window.open(url, '_blank');
  });

  /* ---------- Clear ---------- */
  btnClear.addEventListener('click', (e) => {
    e.preventDefault();
    selectedItems = [];
    document.querySelectorAll('.option-btn,.choice-btn').forEach(b => b.classList.remove('selected'));
    if (num && range) { num.value = 6; range.value = 6; }
    const popup = document.getElementById("componentPopup");
    if (popup) popup.style.display = "none";
    renderCategoryButtons();
    renderAccessories();
    updateSummary();
    clearSessionData();
  });

  /* ---------- Init (wait for data.json) ---------- */
  function boot() {
    console.log('Boot function called, checking for data...');
    // Check if data is available from data.json
    if (typeof data === "undefined" || !data) {
      console.log('Data not available yet, waiting...');
      // If data is not available, wait a bit more for async loading
      setTimeout(boot, 100);
      return;
    }
    
    console.log('Data is available:', data);
    
    // Check if data has the required structure
    if (!data.Type && !data.HDD) {
      console.log('Data structure is incorrect, waiting...');
      setTimeout(boot, 100);
      return;
    }

    console.log('Data structure is correct, proceeding with initialization...');
    
    // Set up camera count synchronization and status bar updates
    if (range && num) {
      range.addEventListener('input', function() { 
        num.value = this.value;
        updateCameraStatusBar();
        updateWhatsAppPreview();
      });
      num.addEventListener('input', function() { 
        range.value = this.value;
        updateCameraStatusBar();
        updateWhatsAppPreview();
      });
    }
    
    // Add listener for customer name to update WhatsApp preview
    const customerNameInput = document.getElementById('customer-name');
    if (customerNameInput) {
      customerNameInput.addEventListener('input', updateWhatsAppPreview);
    }

    renderCategoryButtons();
    renderAccessories();
    updateSummary();

    clearSessionData();

    // Check if URL parameters exist
    const hasUrlParams = window.urlQuoteParams && Object.keys(window.urlQuoteParams).length > 0;
    
    const defaultCategory = hasUrlParams && window.urlQuoteParams.recorderType ? window.urlQuoteParams.recorderType : "DVR";
    const defaultBrand = hasUrlParams && window.urlQuoteParams.brand ? window.urlQuoteParams.brand : "HIKVISION";
    const defaultMP = hasUrlParams && window.urlQuoteParams.mp ? window.urlQuoteParams.mp : "2MP";
    const defaultCameras = hasUrlParams && window.urlQuoteParams.cameras ? window.urlQuoteParams.cameras : 6;

    if (num && range) { 
      num.value = defaultCameras; 
      range.value = defaultCameras; 
      // Trigger the input event to update status bar
      const event = new Event('input', { bubbles: true });
      num.dispatchEvent(event);
    }

    currentCategory = defaultCategory;
    currentBrand = defaultBrand;
    currentMP = defaultMP;

    const selCat = document.getElementById("selected-category");
    const selBrand = document.getElementById("selected-brand");
    const selMP = document.getElementById("selected-mp");
    if (selCat) selCat.value = defaultCategory;
    if (selBrand) selBrand.value = defaultBrand;
    if (selMP) selMP.value = defaultMP;

    selectCategory(defaultCategory);
    setTimeout(() => { 
      selectBrand(defaultBrand, false); 
      
      // After brand is selected, select the MP from URL params
      if (hasUrlParams && window.urlQuoteParams.mp) {
        setTimeout(() => {
          selectMP(defaultMP, true);
        }, 150);
      }
    }, 100);

    const prefBtn = document.getElementById("btn-preferred-hdd");
    const brandBtn = document.getElementById("btn-brandwise-hdd");
    const pref = document.getElementById("preferred-hdd");
    const brandw = document.getElementById("brandwise-hdd");

    if (pref && brandw) { pref.style.display = "block"; brandw.style.display = "none"; }
    if (prefBtn) prefBtn.classList.add("active");
    if (brandBtn) brandBtn.classList.remove("active");

    setTimeout(() => { renderHddSections(); }, 400);
    
    // If URL params specify HDD, select it instead of auto-selecting
    if (hasUrlParams && window.urlQuoteParams.hddSize) {
      setTimeout(() => {
        const hddSize = window.urlQuoteParams.hddSize.trim();
        const findAndClickHdd = (attempts = 0) => {
           const btns = document.querySelectorAll("#preferred-hdd .choice-btn, #brandwise-hdd .choice-btn");
           if (btns.length === 0 && attempts < 10) {
               console.log('⏳ Waiting for HDD buttons to render...', attempts);
               setTimeout(() => findAndClickHdd(attempts + 1), 200);
               return;
           }

           let hddSelected = false;
           console.log('🔍 Looking for HDD:', hddSize);
           
           // Normalize search term: remove non-alphanumeric chars, lowercase
           const searchNorm = hddSize.replace(/[^a-zA-Z0-9]/g, '').toLowerCase(); 

           // 1. Try fuzzy match on button text
           for (const b of btns) {
             const btnText = b.textContent.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
             if (btnText.includes(searchNorm)) {
               b.click();
               hddSelected = true;
               console.log('✅ HDD selected from URL (fuzzy):', hddSize, '- button:', b.textContent);
               break;
             }
           }
           
           if (!hddSelected) {
             // 2. Try capacity match via dataset or regex
             const capacityOnly = hddSize.match(/(\d+)\s*(TB|GB)/i);
             if (capacityOnly) {
                const capSearch = (capacityOnly[1] + capacityOnly[2]).toLowerCase(); // e.g. "2tb"
                for (const b of btns) {
                   // Check dataset capacity if available
                   if (b.dataset.capacity && b.dataset.capacity.replace(/\s/g, '').toLowerCase() === capSearch) {
                      b.click();
                      hddSelected = true;
                      console.log('✅ HDD selected from URL (dataset):', hddSize, '- button:', b.textContent);
                      break;
                   }
                }
             }
           }
           
           if (!hddSelected && attempts < 10) {
             // Retry a few more times in case rendering is partial
             console.log('⚠️ HDD not found yet, retrying...', attempts);
             setTimeout(() => findAndClickHdd(attempts + 1), 200);
             return;
           }
           
           if (!hddSelected) {
             console.warn('⚠️ HDD not found:', hddSize, '- falling back to auto-select');
             // Remove param so autoSelectHdd can take over
             if (window.urlQuoteParams) delete window.urlQuoteParams.hddSize;
             autoSelectHdd();
           }
        };
        findAndClickHdd();
      }, 600);
    } else {
      // Auto-select HDD based on camera count if no URL param
      setTimeout(() => {
        autoSelectHdd();
      }, 500);
    }

    setTimeout(() => {
      const mpBtn = document.querySelector(`#mp-buttons .choice-btn.selected`);
      if (mpBtn) mpBtn.classList.add("selected");
    }, 600);

    updateSummary();
    
    // Initial WhatsApp preview update
    setTimeout(() => {
      updateWhatsAppPreview();
      applyUrlQuoteState();
    }, 700);
  }
  
  // Make boot function available globally
  window.boot = boot;

  // Function to parse URL parameters and initialize form values
  function initUrlParams() {
    console.log('🔄 parsing URL parameters...');
    // Parse URL parameters to pre-fill form
    const urlParams = new URLSearchParams(window.location.search);
    const quote = urlParams.get('quote');

    const qstate = urlParams.get('qstate');
    if (qstate) {
      try {
        const decoded = base64DecodeUnicode(qstate);
        const state = JSON.parse(decoded);
        if (state && typeof state === 'object') {
          window.urlQuoteState = state;

          if (state.phone) {
            const phoneInput = document.getElementById('num-whatsapp');
            if (phoneInput) phoneInput.value = String(state.phone);
          }

          if (state.customerName) {
            const nameInput = document.getElementById('customer-name');
            if (nameInput) nameInput.value = String(state.customerName);
          }

          if (state.cameraCount) {
            const cameraInput = document.getElementById('num-cameras-input');
            const cameraRange = document.getElementById('num-cameras');
            const cameraCount = parseInt(state.cameraCount, 10) || 6;
            if (cameraInput) cameraInput.value = cameraCount;
            if (cameraRange) cameraRange.value = cameraCount;
          }

          const disc = (typeof state.additionalDiscount === 'number') ? state.additionalDiscount : (parseInt(urlParams.get('disc') || '0', 10) || 0);
          const pct = (typeof state.additionalPercentage === 'number') ? state.additionalPercentage : (parseInt(urlParams.get('pct') || '0', 10) || 0);

          if (discountDropdown) {
            discountDropdown.value = String(disc);
            discountDropdown.dispatchEvent(new Event('change', { bubbles: true }));
          }
          if (percentageAmount) {
            percentageAmount.value = String(Math.round(pct));
            percentageAmount.dispatchEvent(new Event('input', { bubbles: true }));
          }

          window.urlQuoteParams = {
            phone: String(state.phone || ''),
            cameras: parseInt(state.cameraCount, 10) || 6,
            recorderType: String(state.category || ''),
            hddSize: String(state.hddSize || ''),
            mp: String(state.mp || '').replace(/\s+/g, ''),
            customerName: String(state.customerName || ''),
            brand: String(state.brand || ''),
            id: ''
          };

          console.log('✅ URL Quote State stored:', { ...state, selectedItems: Array.isArray(state.selectedItems) ? `[${state.selectedItems.length} items]` : state.selectedItems });
        }
      } catch (e) {
        console.warn('Failed to parse qstate:', e);
      }
    }
    
    if (quote && !window.urlQuoteState) {
      const params = quote.split('|').map(param => param.trim());
      console.log('📋 URL Quote Parameters:', params);
      
      if (params.length >= 4) {
        // params[0] = phone number (8217662342)
        // params[1] = camera count (6)
        // params[2] = recorder type (DVR/NVR)
        // params[3] = HDD size (1TB, 2TB, etc.)
        // params[4] = MP (5 MP, 2 MP, etc.)
        // params[5] = customer name
        // params[6] = id number (O-4)
        
        // Set phone number
        const phoneInput = document.getElementById('num-whatsapp');
        if (phoneInput && params[0]) {
          phoneInput.value = params[0];
        }
        
        // Set customer name
        const nameInput = document.getElementById('customer-name');
        if (nameInput && params[5]) {
          nameInput.value = params[5];
        }
        
        // Set number of cameras
        const cameraInput = document.getElementById('num-cameras-input');
        const cameraRange = document.getElementById('num-cameras');
        if (cameraInput && params[1]) {
          const cameraCount = parseInt(params[1]) || 6;
          cameraInput.value = cameraCount;
          if (cameraRange) {
            cameraRange.value = cameraCount;
          }
        }
        
        // Convert MP format: "5 MP" -> "5MP", "2 MP" -> "2MP"
        let mpValue = params[4] ? params[4].replace(/\s+/g, '') : '';
        
        // Store params in window for access during boot()
        window.urlQuoteParams = {
          phone: params[0] || '',
          cameras: parseInt(params[1]) || 6,
          recorderType: params[2] || '',  // DVR or NVR
          hddSize: params[3] || '',       // 1TB, 2TB, etc.
          mp: mpValue,                     // 5MP, 2MP, etc. (no space)
          customerName: params[5] || '',
          id: params[6] || '',
          brand: ''
        };
        
        console.log('✅ URL Quote Params stored:', window.urlQuoteParams);
      }
    }
    
    const form = document.getElementById("cctv-requirement-form");
    if (form) {
      form.addEventListener("keydown", (e) => {
        if (e.key === "Enter") e.preventDefault();
      });
    }
  }

  // Make initUrlParams available globally
  window.initUrlParams = initUrlParams;

  // Execute immediately since script is loaded dynamically
  initUrlParams();
  
  // Also try on DOMContentLoaded just in case
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUrlParams);
  } else {
    console.log('DOM already ready, initUrlParams executed');
  }
  
  // Don't call boot() here anymore since we're calling it manually after data is loaded
  // boot() will be called from quote.php after data is loaded
  console.log('Scripts initialization completed, waiting for data to be loaded before calling boot()');

  /* ---------- Smart Re-render on Form Changes (Preserve State) ---------- */
  ["input", "change"].forEach(evt => {
    const form = document.getElementById("cctv-requirement-form");
    if (!form) return;

    form.addEventListener(evt, (event) => {
      // Camera count slider
      if (event.target.id === 'num-cameras' || event.target.id === 'num-cameras-input') {
        const newTotal = parseInt(event.target.value) || 1;

        
        // If exactly one camera selected in current context, set its qty to slider
        const contextCameraItems = selectedItems.filter(item =>
          item.meta?.component === 'Camera' &&
          item.meta?.category === currentCategory &&
          item.meta?.brand === currentBrand &&
          (currentCategory === 'NVR' ? true : item.meta?.mp === currentMP)
        );

        if (contextCameraItems.length === 1) {
          contextCameraItems[0].qty = newTotal;
          const cameraButtons = document.querySelectorAll('.camera-btn');
          cameraButtons.forEach(btn => {
            if (btn.closest('#componentPopup') && btn.dataset.cameraLabel === (contextCameraItems[0].meta?.cameraLabel)) {
              updateCameraButtonText(btn, newTotal);
            }
          });
        }

        // Only consider cameras from the current context
        const currentlyAssigned = selectedItems
          .filter(item => 
            item.meta?.component === 'Camera' && 
            item.meta?.category === currentCategory && 
            item.meta?.brand === currentBrand && 
            (currentCategory === 'NVR' ? true : item.meta?.mp === currentMP))
          .reduce((sum, item) => sum + (item.qty || 0), 0);

        if (currentlyAssigned > newTotal) {
          const ratio = newTotal / currentlyAssigned;
          selectedItems.forEach(item => {
            if (item.meta?.component === 'Camera' && 
                item.meta?.category === currentCategory && 
                item.meta?.brand === currentBrand && 
                (currentCategory === 'NVR' ? true : item.meta?.mp === currentMP)) {
              item.qty = Math.max(1, Math.floor(item.qty * ratio));
            }
          });

          const newAssigned = selectedItems
            .filter(item => 
              item.meta?.component === 'Camera' && 
              item.meta?.category === currentCategory && 
              item.meta?.brand === currentBrand && 
              (currentCategory === 'NVR' ? true : item.meta?.mp === currentMP))
            .reduce((sum, item) => sum + (item.qty || 0), 0);

          if (newAssigned > newTotal) {
            const cameraItems = selectedItems.filter(item => 
              item.meta?.component === 'Camera' && 
              item.meta?.category === currentCategory && 
              item.meta?.brand === currentBrand && 
              (currentCategory === 'NVR' ? true : item.meta?.mp === currentMP));
            if (cameraItems.length > 0) {
              cameraItems[cameraItems.length - 1].qty -= (newAssigned - newTotal);
            }
          }
        }

        // Update button labels for current context
        selectedItems.forEach(item => {
          if (item.meta?.component === 'Camera') {
            if (item.meta.brand === currentBrand && (currentCategory === 'NVR' ? true : item.meta.mp === currentMP)) {
              const cameraButtons = document.querySelectorAll('.camera-btn');
              cameraButtons.forEach(btn => {
                if (btn.closest('#componentPopup') && btn.dataset.cameraLabel === item.meta.cameraLabel) {
                  updateCameraButtonText(btn, item.qty);
                }
              });
            }
          }
        });

        updateAllCameraSliders();
        updateSummary();
        autoSelectHdd();
        saveSessionData();
        
        // Determine required recorder channel based on new camera count
        let requiredChannel;
        if (newTotal <= 4) requiredChannel = 4;
        else if (newTotal <= 8) requiredChannel = 8;
        else if (newTotal <= 16) requiredChannel = 16;
        else requiredChannel = 32;
        
        // Check if current recorder matches the required channel
        const currentRecorder = selectedItems.find(i => 
          i.meta?.component === 'Recorder' && 
          i.meta?.brand === currentBrand && 
          (currentCategory === 'NVR' ? true : i.meta?.mp === currentMP)
        );
        
        // If current recorder doesn't match required channel (either too small OR too large), remove it
        if (currentRecorder && currentRecorder.meta?.channel !== requiredChannel) {
          console.log('🔄 Camera count changed to', newTotal, '- switching from', currentRecorder.meta?.channel + 'CH to', requiredChannel + 'CH');
          selectedItems = selectedItems.filter(i => 
            !(i.meta?.component === 'Recorder' && 
              i.meta?.brand === currentBrand && 
              (currentCategory === 'NVR' ? true : i.meta?.mp === currentMP)
          )
        );
        }
        
        // Re-render recorder buttons to update enabled/disabled channels and trigger auto-select
        if (currentCategory && currentBrand && (currentCategory === 'NVR' || currentMP)) {
          renderComponentItems(currentCategory, currentBrand, currentMP, true);
        }
        return;
      }

      // Ignore HDD brand tab toggles & accessories
      if (
        event.target.closest("#preferred-hdd") ||
        event.target.closest("#brandwise-hdd") ||
        event.target.closest("#accessories")
      ) return;

      const prevSelections = structuredClone(selectedItems);

      if (currentCategory && currentBrand && (currentCategory === 'NVR' || currentMP)) {
        renderComponentItems(currentCategory, currentBrand, currentMP, true);
      }

      prevSelections.forEach(prev => {
        const idx = selectedItems.findIndex(i => i.id === prev.id);
        if (idx === -1) {
          selectedItems.push(prev);
        } else {
          selectedItems[idx].qty = prev.qty;
          selectedItems[idx].price = prev.price;
          selectedItems[idx].label = prev.label;
          selectedItems[idx].meta = Object.assign({}, selectedItems[idx].meta || {}, prev.meta || {});
        }
      });

      updateSummary();
      autoSelectHdd();
      saveSessionData(); 
    });
  });

})();
