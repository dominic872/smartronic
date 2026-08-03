(function () {
  const form = document.getElementById('ro-lead-form');
  const status = document.getElementById('form-status');
  const nameInput = document.getElementById('customer-name');
  const phoneInput = document.getElementById('whatsapp-number');
  const planChoice = document.getElementById('plan-choice');
  const serviceType = document.getElementById('service-type');
  const selectedPlan = document.getElementById('selected-plan');
  const selectedService = document.getElementById('selected-service');
  const nameError = document.getElementById('name-error');
  const phoneError = document.getElementById('phone-error');

  function track(eventName) {
    if (typeof window.gtag === 'function') {
      window.gtag('event', eventName);
    }
  }

  document.querySelectorAll('[data-track="call"]').forEach((link) => {
    link.addEventListener('click', () => track('ro_v2_call_click'));
  });

  document.querySelectorAll('[data-track="whatsapp"]').forEach((link) => {
    link.addEventListener('click', () => track('ro_v2_whatsapp_click'));
  });

  document.querySelectorAll('[data-plan]').forEach((link) => {
    link.addEventListener('click', () => {
      if (!planChoice || !selectedPlan) return;
      const plan = link.getAttribute('data-plan') || '';
      const match = Array.from(planChoice.options).find((option) => option.textContent.indexOf(plan) === 0);
      if (match) {
        planChoice.value = match.value;
        selectedPlan.value = match.value;
      }
    });
  });

  function cleanPhone(value) {
    const digits = String(value || '').replace(/\D+/g, '');
    return digits.length > 10 ? digits.slice(-10) : digits;
  }

  function setStatus(message, type) {
    if (!status) return;
    status.textContent = message;
    status.classList.remove('success', 'fail');
    if (type) status.classList.add(type);
  }

  function validate() {
    let ok = true;
    const name = nameInput ? nameInput.value.trim() : '';
    const phone = cleanPhone(phoneInput ? phoneInput.value : '');
    const consent = document.getElementById('consent');

    if (nameError) nameError.textContent = '';
    if (phoneError) phoneError.textContent = '';
    if (nameInput) nameInput.removeAttribute('aria-invalid');
    if (phoneInput) phoneInput.removeAttribute('aria-invalid');

    if (name.length < 2) {
      if (nameError) nameError.textContent = 'Please enter your name.';
      if (nameInput) nameInput.setAttribute('aria-invalid', 'true');
      ok = false;
    }

    if (phone.length !== 10 || !/^[6-9]\d{9}$/.test(phone)) {
      if (phoneError) phoneError.textContent = 'Please enter a valid 10-digit mobile number.';
      if (phoneInput) phoneInput.setAttribute('aria-invalid', 'true');
      ok = false;
    }

    if (consent && !consent.checked) {
      setStatus('Please allow us to contact you about this enquiry.', 'fail');
      ok = false;
    }

    return ok;
  }

  function syncHiddenFields() {
    if (selectedPlan && planChoice) selectedPlan.value = planChoice.value;
    if (selectedService && serviceType) selectedService.value = serviceType.value;
  }

  if (phoneInput) {
    phoneInput.addEventListener('input', () => {
      phoneInput.value = cleanPhone(phoneInput.value);
    });
  }

  if (planChoice) planChoice.addEventListener('change', syncHiddenFields);
  if (serviceType) serviceType.addEventListener('change', syncHiddenFields);

  if (form) {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      syncHiddenFields();

      if (form.company && form.company.value) return;
      if (!validate()) return;

      const submitButton = form.querySelector('button[type="submit"]');
      const payload = new FormData(form);
      const phone = cleanPhone(payload.get('whatsapp_number'));
      payload.set('whatsapp_number', phone);

      const pageParams = new URLSearchParams(window.location.search);
      if (pageParams.has('gad_campaignid')) {
        payload.set('gad_campaignid', pageParams.get('gad_campaignid') || '');
      }

      const endpoint = '/admin_v2/admin-ajax.php' + (window.location.search || '');

      if (submitButton) submitButton.disabled = true;
      setStatus('Submitting your enquiry...', '');

      try {
        const response = await fetch(endpoint, {
          method: 'POST',
          body: payload,
          credentials: 'same-origin'
        });
        const data = await response.json();
        if (!response.ok || !data || data.success !== true) {
          throw new Error((data && (data.data || data.message)) || 'Unable to submit right now.');
        }

        form.reset();
        syncHiddenFields();
        setStatus('Thank you. Smartronic will contact you shortly on WhatsApp.', 'success');
        track('conversion_event_request_quote');
        window.dispatchEvent(new CustomEvent('smartronic:leadSubmitted', { detail: { source: 'ro-water-purifier-v2' } }));
      } catch (error) {
        setStatus(error.message || 'Unable to submit right now. Please call or WhatsApp us.', 'fail');
      } finally {
        if (submitButton) submitButton.disabled = false;
      }
    });
  }
})();
