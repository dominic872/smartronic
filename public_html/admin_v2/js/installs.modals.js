// Modal and popup management for installs system
(function(window) {
  'use strict';

  // Modal state management
  let currentOrderId = null;

  // Popup control functions
  function openOrderPopup() {
    const popup = document.getElementById('order-popup');
    if (popup) {
      popup.style.display = 'flex';
      document.body.classList.add('popup-open');
    }
  }

  function closeOrderPopup() {
    const popup = document.getElementById('order-popup');
    if (popup) {
      popup.style.display = 'none';
      document.body.classList.remove('popup-open');
      // Also close extras overlay if open
      if (typeof window.closeExtrasOverlay === 'function') {
        window.closeExtrasOverlay();
      }
    }
  }

  function switchTab(tabName) {
    const tabs = ['requirement', 'material', 'invoice'];
    tabs.forEach(name => {
      const tabElement = document.getElementById('tab-' + name);
      if (tabElement) {
        tabElement.style.display = (name === tabName) ? 'block' : 'none';
      }
    });

    // Update active state on tab header buttons
    try {
      const btns = {
        requirement: document.getElementById('tab-btn-requirement'),
        material: document.getElementById('tab-btn-material'),
        invoice: document.getElementById('tab-btn-invoice')
      };
      Object.keys(btns).forEach(key => {
        const btn = btns[key];
        if (!btn) return;
        if (key === tabName) {
          btn.classList.add('active');
        } else {
          btn.classList.remove('active');
        }
      });
    } catch (e) {
      // noop
    }

    // Preserve and restore form data if switching to requirement tab
    if (tabName === 'requirement' && window.currentInstallData) {
      setTimeout(() => {
        const form = document.getElementById('installForm');
        if (form) {
          // Define the fields that exist in the requirements form
          const validFormFields = [
            'id', 'name', 'cams', 'bullets', 'dome', 'hdd', 'monitor', 'type',
            'location', 'time', 'date', 'owner', 'technician', 'helper', 
            'resolution', 'map', 'rack', 'notes'
          ];
          
          Object.entries(window.currentInstallData).forEach(([key, value]) => {
            // Only try to populate fields that exist in our form
            if (validFormFields.includes(key)) {
              const field = form.querySelector(`#${key}`);
              if (field && !field.value && value) {  // Only set if field is empty and we have a value
                field.value = value;
                console.log(`Restored field ${key} to:`, value);
              }
            }
          });
        }
      }, 100);
    }
    
    // Load content based on tab
    if (tabName === 'invoice') {
      if (typeof window.loadInvoiceIntoContainer === 'function') {
        window.loadInvoiceIntoContainer();
      }
    } else if (tabName === 'material') {
      // Ensure FABs are present immediately, before async content loading completes
      if (typeof window.ensureMaterialFABs === 'function') {
        window.ensureMaterialFABs();
      }
      if (typeof window.loadMaterialIntoContainer === 'function') {
        window.loadMaterialIntoContainer();
      }
    }
    
    // Update extras button visibility based on active tab
    if (typeof window.updateExtrasButtonVisibility === 'function') {
      window.updateExtrasButtonVisibility();
    }

    // Update material FABs visibility based on active tab
    if (typeof window.updateMaterialFabVisibility === 'function') {
      window.updateMaterialFabVisibility(tabName);
    }
  }

  function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    const caretId = sectionId.replace('-section', '-caret');
    const caret = document.getElementById(caretId);
    
    if (section && caret) {
      const isVisible = section.style.display !== 'none';
      section.style.display = isVisible ? 'none' : 'block';
      caret.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
    }
  }

  // Navigation functions
  async function redirectToOrderItems2(url, _urlInvoice) {
    openOrderPopup();
    switchTab('material');
  }

  async function redirectToOrderItems(url) {
    openOrderPopup();
    switchTab('requirement');
    
    // Ensure the requirement tab is properly shown
    const reqTab = document.getElementById('tab-requirement');
    if (reqTab) {
      reqTab.style.display = 'block';
    }
  }

  // Current order ID management
  function getCurrentOrderId() {
    const idField = document.getElementById('id');
    if (idField && idField.value) {
      return idField.value;
    }
    return currentOrderId;
  }

  function setCurrentOrderId(id) {
    currentOrderId = id;
    window.editingId = id; // For backward compatibility
  }

  // Export functions to global scope
  window.openOrderPopup = openOrderPopup;
  window.closeOrderPopup = closeOrderPopup;
  window.switchTab = switchTab;
  window.toggleSection = toggleSection;
  window.redirectToOrderItems2 = redirectToOrderItems2;
  window.redirectToOrderItems = redirectToOrderItems;
  window.redirecktToOrderItems = redirectToOrderItems; // Backward compatibility
  window.getCurrentOrderId = getCurrentOrderId;
  window.setCurrentOrderId = setCurrentOrderId;

})(window);