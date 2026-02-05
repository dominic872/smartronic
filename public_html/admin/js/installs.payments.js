// Payment management functionality for installs system
(function(window) {
  'use strict';

  // Payment form submission handler
  function handlePaymentUpdate(e) {
    e.preventDefault();
    
    const currentId = window.editingId || getCurrentOrderId();
    if (!currentId) {
      alert('No order selected. Please select an order first.');
      return;
    }
    
    const fullyPaid = document.getElementById('fully-paid').checked;
    const amountPaid = document.getElementById('amount-paid').value.trim();
    
    submitPaymentUpdate(currentId, fullyPaid, amountPaid);
  }

  // Handle overlay payment form submission
  function handleOverlayPaymentUpdate(form) {
    const currentId = window.editingId || getCurrentOrderId();
    if (!currentId) {
      alert('No order selected. Please select an order first.');
      return;
    }
    
    const fullyPaid = form.querySelector('#overlay-fully-paid').checked;
    const amountPaid = form.querySelector('#overlay-amount-paid').value.trim();
    
    submitPaymentUpdate(currentId, fullyPaid, amountPaid);
  }

  // Submit payment update to server
  function submitPaymentUpdate(currentId, fullyPaid, amountPaid) {
    // Validate amount if provided
    if (amountPaid && (isNaN(parseFloat(amountPaid)) || parseFloat(amountPaid) < 0)) {
      alert('Please enter a valid positive amount.');
      return;
    }
    
    // Prepare data
    const data = {
      updatePayment: true,
      id: currentId,
      fully_paid: fullyPaid,
      amount_paid: amountPaid ? parseFloat(amountPaid) : null
    };
    
    // Submit via fetch
    fetch('installs.php', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Payment updated successfully!');
        // Sync both forms
        loadPaymentData(currentId);
        if (typeof window.render === 'function') {
          window.render(); // Refresh the calendar view
        }
      } else {
        alert('Error: ' + (data.message || 'Failed to update payment'));
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while updating payment.');
    });
  }

  // Load payment data for an order
  function loadPaymentData(orderId) {
    if (!orderId) return;
    
    // Fetch payment data from server
    fetch(`installs.php?get_payment=1&id=${encodeURIComponent(orderId)}`)
      .then(response => response.json())
      .then(data => {
        if (data && data.success) {
          // Update both forms with existing data
          const fullyPaidCheckbox = document.getElementById('fully-paid');
          const amountPaidInput = document.getElementById('amount-paid');
          const overlayFullyPaid = document.getElementById('overlay-fully-paid');
          const overlayAmountPaid = document.getElementById('overlay-amount-paid');
          
          if (fullyPaidCheckbox) fullyPaidCheckbox.checked = data.fully_paid;
          if (amountPaidInput) amountPaidInput.value = data.amount_paid || '';
          if (overlayFullyPaid) overlayFullyPaid.checked = data.fully_paid;
          if (overlayAmountPaid) overlayAmountPaid.value = data.amount_paid || '';
        }
      })
      .catch(error => {
        console.error('Error loading payment data:', error);
      });
  }

  // Get current order ID
  function getCurrentOrderId() {
    const idField = document.getElementById('id');
    if (idField && idField.value) {
      return idField.value;
    }
    return null;
  }

  // Initialize payment handlers
  function initPaymentHandlers() {
    // Handle main payment form
    const paymentForm = document.getElementById('update-payment-form');
    if (paymentForm) {
      paymentForm.addEventListener('submit', handlePaymentUpdate);
    }
    
    // Handle overlay payment form (will be created dynamically)
    document.addEventListener('submit', function(e) {
      if (e.target.id === 'overlay-payment-form') {
        e.preventDefault();
        handleOverlayPaymentUpdate(e.target);
      }
    });
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPaymentHandlers);
  } else {
    initPaymentHandlers();
  }

  // Export functions to global scope
  window.loadPaymentData = loadPaymentData;
  window.submitPaymentUpdate = submitPaymentUpdate;
  window.handlePaymentUpdate = handlePaymentUpdate;
  window.handleOverlayPaymentUpdate = handleOverlayPaymentUpdate;

})(window);