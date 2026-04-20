(function () {
  function initMainConsent() {
    const consent = document.getElementById('whatsapp-consent');
    const whatsappInput = document.getElementById('num-whatsapp');
    const submitButton = document.getElementById('next-button');
    const termsLink = document.getElementById('terms-of-service-link');

    if (!consent || !submitButton) return;

    const isValidWhatsapp = function () {
      if (!whatsappInput) return true;
      const digits = (whatsappInput.value.match(/\d/g) || []).length;
      return digits >= 10;
    };

    const syncSubmitState = function () {
      submitButton.disabled = !(consent.checked && isValidWhatsapp());
    };

    if (termsLink) {
      termsLink.addEventListener('click', function (event) {
        event.stopPropagation();
      });
    }

    consent.addEventListener('change', syncSubmitState);

    if (whatsappInput) {
      whatsappInput.addEventListener('input', function () {
        window.setTimeout(syncSubmitState, 0);
      });
    }

    syncSubmitState();
    window.addEventListener('load', function () {
      window.setTimeout(syncSubmitState, 0);
    });
  }

  function initHeaderReveal() {
    const header = document.querySelector('header');
    if (!header) return;

    let lastScrollTop = 0;
    const scrollThreshold = 100;
    let ticking = false;

    const onScroll = function () {
      const currentScroll = window.pageYOffset || document.documentElement.scrollTop;

      if (currentScroll > lastScrollTop && currentScroll > scrollThreshold) {
        header.classList.add('hidden-header');
      } else if (currentScroll < lastScrollTop) {
        header.classList.remove('hidden-header');
      }

      lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
      ticking = false;
    };

    window.addEventListener('scroll', function () {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(onScroll);
    }, { passive: true });
  }

  function initTrackingAndGoToTop() {
    let scrollDepth = 0;
    const startTime = Date.now();
    let scrollDepthTicking = false;

    const updateScrollDepth = function () {
      const doc = document.documentElement;
      const scrollable = Math.max(1, doc.scrollHeight - window.innerHeight);
      const scrolled = Math.ceil((window.scrollY / scrollable) * 100);
      scrollDepth = Math.max(scrollDepth, scrolled);
      scrollDepthTicking = false;
    };

    window.addEventListener('scroll', function () {
      if (scrollDepthTicking) return;
      scrollDepthTicking = true;
      window.requestAnimationFrame(updateScrollDepth);
    }, { passive: true });

    const goToTopBtn = document.getElementById('goToTop');
    const quotationSection = document.getElementById('quotation');
    if (goToTopBtn) {
      let goToTopTicking = false;

      const updateGoToTopState = function () {
        const scrollPosition = window.scrollY;

        goToTopBtn.style.display = scrollPosition > 100 ? 'block' : 'none';

        if (quotationSection) {
          const quotationRect = quotationSection.getBoundingClientRect();
          if (quotationRect.top < window.innerHeight && quotationRect.bottom > 1500) {
            goToTopBtn.classList.add('hide-button');
          } else {
            goToTopBtn.classList.remove('hide-button');
          }
        }

        goToTopTicking = false;
      };

      window.addEventListener('scroll', function () {
        if (goToTopTicking) return;
        goToTopTicking = true;
        window.requestAnimationFrame(updateGoToTopState);
      }, { passive: true });

      updateGoToTopState();

      goToTopBtn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }

    function sendData(data) {
      const body = JSON.stringify(data);

      if (navigator.sendBeacon) {
        const blob = new Blob([body], { type: 'application/json' });
        navigator.sendBeacon('https://smartronic.online/admin/track.php', blob);
        return;
      }

      fetch('https://smartronic.online/admin/track.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: body,
        keepalive: true
      }).catch(function () {});
    }

    window.addEventListener('pagehide', function () {
      const timeOnPage = Math.ceil((Date.now() - startTime) / 1000);
      sendData({
        scrollDepth: scrollDepth,
        timeOnPage: timeOnPage
      });
    });

    const loadTrackingPixel = function () {
      const img = document.getElementById('tracking-image');
      if (!img) return;
      img.src = 'https://smartronic.online/admin/capture.php?timestamp=' + Date.now();
    };

    if (window.requestIdleCallback) {
      window.requestIdleCallback(loadTrackingPixel, { timeout: 2500 });
    } else {
      window.setTimeout(loadTrackingPixel, 2000);
    }
  }

  function initQuotationOverlay() {
    const overlay = document.getElementById('quotation-overlay');
    const closeBtn = document.getElementById('close-overlay');
    const quoteSection = document.querySelector('#quotation')?.innerHTML;

    if (!overlay || !closeBtn || !quoteSection) return;

    function showQuotationOverlay() {
      if (window.location.hash === '#show-quotation') {
        overlay.style.display = 'block';
        document.body.classList.add('overlay-active');
        document.querySelector('#quotation-overlay-form').innerHTML += quoteSection;
        document.querySelector('#quotation').innerHTML = '';
      }
    }

    showQuotationOverlay();
    window.addEventListener('hashchange', showQuotationOverlay);

    closeBtn.addEventListener('click', function () {
      overlay.style.display = 'none';
      document.body.classList.remove('overlay-active');
      document.querySelector('#quotation-overlay-form').innerHTML = '';
      document.querySelector('#quotation').innerHTML = quoteSection;
      window.location.hash = '';
      if (typeof window.handleCCTVFormSubmission === 'function') {
        window.handleCCTVFormSubmission();
      }
    });
  }

  function initPromoPopup() {
    const popup = document.getElementById('promoPopup');
    const popupClose = document.getElementById('promoPopupClose');
    const popupForm = document.getElementById('promoPopupForm');
    const popupPhone = document.getElementById('promoPopupPhone');
    const popupName = document.getElementById('promoPopupName');
    const popupConsent = document.getElementById('promoPopupConsent');
    const popupTermsLink = document.getElementById('promoPopupTermsLink');
    const popupSubmit = document.getElementById('promoPopupSubmit');
    const popupMessage = document.getElementById('promoPopupMessage');
    const mainPhone = document.getElementById('num-whatsapp');
    const mainForm = document.getElementById('cctv-requirement-form');
    const formHolder = document.querySelector('.form-holder');
    const successMessage = document.querySelector('.form-success-message');
    const popupStateKey = 'smartronicPromoPopupState';
    const popupGads = document.body ? (document.body.dataset.popupGads || '') : '';

    if (!popup || !popupForm || !popupPhone || !popupName) return;

    const isValidPopupPhone = function () {
      const phone = popupPhone.value.replace(/\D+/g, '').slice(-10);
      return phone.length === 10;
    };

    const syncPopupSubmitState = function () {
      if (!popupSubmit) return;
      const consentOk = !popupConsent || popupConsent.checked;
      const isLoading = popupSubmit.dataset.loading === '1';
      popupSubmit.disabled = isLoading || !consentOk || !isValidPopupPhone();
    };

    const focusFirstPopupField = function () {
      const firstField = popupForm.querySelector('input[type="tel"], input[type="text"], textarea, select');
      if (!firstField || typeof firstField.focus !== 'function') return;
      window.requestAnimationFrame(function () {
        try {
          firstField.focus({ preventScroll: true });
        } catch (error) {
          firstField.focus();
        }
      });
    };

    let popupOpen = false;
    let popupLocked = false;
    let popupScrollY = 0;
    let mainFormEngaged = false;
    let mainFormSubmitted = false;
    const popupDelay = window.innerWidth <= 767 ? 3000 : 10000;

    try {
      if (window.sessionStorage.getItem(popupStateKey) === 'closed') {
        popupLocked = true;
      }
    } catch (error) {}

    const syncMainFormName = function (nameValue) {
      if (!mainForm) return;
      let hiddenNameInput = mainForm.querySelector('input[name="customer-name"]');
      if (!hiddenNameInput) {
        hiddenNameInput = document.createElement('input');
        hiddenNameInput.type = 'hidden';
        hiddenNameInput.name = 'customer-name';
        hiddenNameInput.id = 'customer-name';
        mainForm.appendChild(hiddenNameInput);
      }
      hiddenNameInput.value = nameValue;
    };

    window.showLeadSuccessState = function () {
      if (formHolder) {
        formHolder.style.display = 'none';
      }
      if (successMessage) {
        successMessage.style.display = 'block';
      }
    };

    const setPopupState = function (value) {
      popupLocked = value === 'closed';
      try {
        window.sessionStorage.setItem(popupStateKey, value);
      } catch (error) {}
    };

    const lockPageScroll = function () {
      popupScrollY = window.scrollY || window.pageYOffset || 0;
      document.documentElement.classList.add('overlay-active');
      document.body.classList.add('overlay-active');
      document.body.style.top = '-' + popupScrollY + 'px';
    };

    const unlockPageScroll = function () {
      document.documentElement.classList.remove('overlay-active');
      document.body.classList.remove('overlay-active');
      document.body.style.top = '';
      window.scrollTo(0, popupScrollY);
    };

    const openPopup = function (options) {
      const opts = options || {};
      if (popupLocked || popupOpen || mainFormSubmitted) return;
      if (!opts.ignoreEngaged && mainFormEngaged) return;
      popup.classList.add('is-open');
      popup.setAttribute('aria-hidden', 'false');
      syncPopupSubmitState();
      lockPageScroll();
      popupOpen = true;
      focusFirstPopupField();
    };

    const closePopup = function (lockPopup) {
      popup.classList.remove('is-open');
      popup.setAttribute('aria-hidden', 'true');
      if (popupOpen) {
        unlockPageScroll();
      }
      popupOpen = false;
      if (lockPopup) {
        setPopupState('closed');
      }
    };

    window.setTimeout(function () {
      openPopup();
    }, popupDelay);

    document.addEventListener('mousemove', function (event) {
      if (popupLocked || popupOpen) return;
      if (window.innerWidth > 767 && event.clientY <= 90) {
        openPopup({ ignoreEngaged: true });
      }
    });

    if (mainForm) {
      const markMainFormEngaged = function () {
        mainFormEngaged = true;
      };
      mainForm.addEventListener('focusin', markMainFormEngaged);
      mainForm.addEventListener('input', markMainFormEngaged);
      mainForm.addEventListener('change', markMainFormEngaged);
      mainForm.addEventListener('submit', function () {
        mainFormSubmitted = true;
        setPopupState('closed');
        closePopup(false);
      });
    }

    window.addEventListener('smartronic:leadSubmitted', function () {
      mainFormSubmitted = true;
      setPopupState('closed');
      closePopup(false);
    });

    if (popupClose) {
      popupClose.addEventListener('click', function () {
        closePopup(true);
      });
    }

    if (popupTermsLink) {
      popupTermsLink.addEventListener('click', function (event) {
        event.stopPropagation();
      });
    }

    popupPhone.addEventListener('input', function () {
      popupMessage.textContent = '';
      popupMessage.classList.remove('is-error');
      syncPopupSubmitState();
    });

    popupName.addEventListener('input', function () {
      popupMessage.textContent = '';
      popupMessage.classList.remove('is-error');
    });

    if (popupConsent) {
      popupConsent.addEventListener('change', function () {
        popupMessage.textContent = '';
        popupMessage.classList.remove('is-error');
        syncPopupSubmitState();
      });
    }

    syncPopupSubmitState();

    popupForm.addEventListener('submit', function (event) {
      event.preventDefault();

      const phone = popupPhone.value.replace(/\D+/g, '').slice(-10);
      const name = popupName.value.trim();

      if (!phone || phone.length !== 10) {
        popupMessage.textContent = 'Please enter a valid WhatsApp number.';
        popupMessage.classList.add('is-error');
        return;
      }

      if (!name) {
        popupMessage.textContent = 'Please enter your name.';
        popupMessage.classList.add('is-error');
        return;
      }

      if (popupConsent && !popupConsent.checked) {
        popupMessage.textContent = 'Please accept the Terms of Service.';
        popupMessage.classList.add('is-error');
        syncPopupSubmitState();
        return;
      }

      popupMessage.textContent = 'Submitting...';
      popupMessage.classList.remove('is-error');
      if (popupSubmit) {
        popupSubmit.dataset.loading = '1';
        popupSubmit.disabled = true;
        popupSubmit.textContent = 'Submitting...';
      }

      const payload = new URLSearchParams();
      payload.set('action', 'popup_lead_capture');
      payload.set('customer_name', name);
      payload.set('whatsapp_number', phone);
      payload.set('gads', popupGads);
      payload.set('popup_device', window.innerWidth <= 767 ? 'pop-mobile' : 'pop-desk');

      fetch('admin_v2/admin-ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: payload.toString()
      })
        .then(function (response) {
          if (!response.ok) throw new Error('Failed request');
          return response.json();
        })
        .then(function (data) {
          if (!data || !data.success) {
            throw new Error((data && data.message) || 'Unable to save lead.');
          }

          if (typeof window.gtagSendEvent === 'function') {
            window.gtagSendEvent('https://smartronic.online');
          }
          popupMessage.textContent = 'Thank you. We will contact you shortly.';
          popupMessage.classList.remove('is-error');
          if (mainPhone) {
            mainPhone.value = phone;
            mainPhone.dispatchEvent(new Event('input', { bubbles: true }));
          }
          syncMainFormName(name);
          mainFormSubmitted = true;
          setPopupState('closed');
          closePopup(false);
          if (typeof window.showLeadSuccessState === 'function') {
            window.showLeadSuccessState();
          }
          window.dispatchEvent(new CustomEvent('smartronic:leadSubmitted', { detail: { source: 'promo-popup' } }));
          const headerHeight = document.querySelector('header') ? document.querySelector('header').offsetHeight : 0;
          if (successMessage) {
            window.setTimeout(function () {
              window.scrollTo({
                top: Math.max(0, successMessage.offsetTop - headerHeight - 20),
                behavior: 'smooth'
              });
            }, 50);
          }
        })
        .catch(function (error) {
          popupMessage.textContent = error && error.message ? error.message : 'Something went wrong. Please try again.';
          popupMessage.classList.add('is-error');
        })
        .finally(function () {
          if (popupSubmit) {
            popupSubmit.dataset.loading = '0';
            popupSubmit.textContent = 'Get now';
          }
          syncPopupSubmitState();
        });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initMainConsent();
    initHeaderReveal();
    initTrackingAndGoToTop();
    initQuotationOverlay();
    initPromoPopup();
  });
})();
