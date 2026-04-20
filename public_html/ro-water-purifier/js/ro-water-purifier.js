document.addEventListener('DOMContentLoaded', () => {
    const animatedSections = document.querySelectorAll('.new-comparison-section, .water-usage-section');

    if (animatedSections.length) {
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries, observerInstance) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-animated');
                    observerInstance.unobserve(entry.target);
                }
            });
        }, observerOptions);

        animatedSections.forEach(section => observer.observe(section));
    }

    // Elements for Can Water
    const canPriceWrap = document.getElementById('can-price-question');
    const canPriceInput = document.getElementById('can-price');
    const riskDisplay = document.getElementById('risk-display');

    // Elements for Filter
    const filterPriceWrap = document.getElementById('filter-price-question');
    const filterPriceInput = document.getElementById('filter-price');
    const riskDisplayFilter = document.getElementById('risk-display-filter');

    const radios = document.querySelectorAll('input[name="water-usage-type"]');

    function updatePriceVisibility() {
        const selected = document.querySelector('input[name="water-usage-type"]:checked');
        
        // Hide all first
        if (canPriceWrap) canPriceWrap.style.display = 'none';
        if (riskDisplay) riskDisplay.style.display = 'none';
        if (filterPriceWrap) filterPriceWrap.style.display = 'none';
        // riskDisplayFilter is inside filterPriceWrap so it hides with it, but good to be explicit if it was outside
        // In current HTML structure, risk displays are inside the question wrappers.
        
        if (selected && selected.value === 'can-water') {
            if (canPriceWrap) canPriceWrap.style.display = 'block';
            if (canPriceInput && !canPriceInput.value) canPriceInput.value = '120';
            // riskDisplay is inside canPriceWrap in HTML structure? No, it's in col-2.
            // Let's check structure: #can-price-question -> .can-calculator-grid -> .can-calc-col-2 -> #risk-display
            // Yes, risk-display is inside #can-price-question, so toggling the wrapper is enough.
            // Wait, previous code had specific toggles for riskDisplay. Let's keep it safe.
            if (riskDisplay) riskDisplay.style.display = 'block';
            
            updateRiskMeter();
        } else if (selected && selected.value === 'other-filters') {
            if (filterPriceWrap) filterPriceWrap.style.display = 'block';
            if (filterPriceInput && !filterPriceInput.value) filterPriceInput.value = '7500'; 
            if (riskDisplayFilter) riskDisplayFilter.style.display = 'block';
            
            updateFilterRiskMeter();
        }
    }

    function updateRiskMeter() {
        if (!canPriceInput) return;
        const price = parseInt(canPriceInput.value) || 0;
        const riskMeter = document.querySelector('#risk-display .risk-meter-sprite');
        const riskText = document.getElementById('risk-text');
        const annualCostEl = document.getElementById('annual-cost');
        const monthlyCansEl = document.getElementById('monthly-cans');
        
        // Comparison Elements
        const currentAnnualCostEl = document.getElementById('current-annual-cost');
        const savings1yEl = document.getElementById('savings-1y-val');
        const savings5yEl = document.getElementById('savings-5y-val');
        
        if (!riskMeter || !riskText || !annualCostEl || !monthlyCansEl) return;

        // Calculate values
        const cansPerYear = 365 / 2.5; // ~146
        const annualCost = price * cansPerYear;
        const total5Years = annualCost * 5;

        // Care Zero Costs (Fixed)
        const czAnnual = 2796;
        const czTotal = 13980;
        
        // Savings
        const savings1y = annualCost - czAnnual;
        const savings5y = total5Years - czTotal;

        // Update Text
        annualCostEl.textContent = `You spend ₹${Math.round(annualCost)} per year on can water.`;
        monthlyCansEl.textContent = `You buy approximately ${Math.round(cansPerYear)} cans a year.`;

        // Update Comparison Label
        if (currentAnnualCostEl) currentAnnualCostEl.textContent = `₹${Math.round(annualCost)}`;

        // Update Savings
        if (savings1yEl) savings1yEl.textContent = `₹${Math.round(savings1y)}`;
        if (savings5yEl) savings5yEl.textContent = `₹${Math.round(savings5y)}`;

        // Update Current Choice Table
        const ccElements = document.querySelectorAll('.cc-price');
        // ccElements[0]..[4] are years 1-5, [5] is total
        ccElements.forEach((el, index) => {
             if (index < 5) {
                 el.textContent = `₹${Math.round(annualCost)}`;
             } else {
                 el.textContent = `₹${Math.round(total5Years)}`;
             }
        });

        // Force High Risk
        riskMeter.className = 'risk-meter-sprite high';
        riskText.textContent = 'High Risk';
        riskText.style.color = '#d9534f';
    }

    function updateFilterRiskMeter() {
        if (!filterPriceInput) return;
        const price = parseInt(filterPriceInput.value) || 0;
        
        // Elements specific to Filter
        const riskMeter = document.querySelector('#risk-display-filter .risk-meter-sprite');
        const riskText = document.getElementById('risk-text-filter');
        const annualCostEl = document.getElementById('annual-cost-filter');
        const maintenanceTextEl = document.getElementById('maintenance-text-filter');
        
        const currentAnnualCostEl = document.getElementById('current-annual-cost-filter');
        const savings1yEl = document.getElementById('savings-1y-val-filter');
        const savings5yEl = document.getElementById('savings-5y-val-filter');
        const ccElements = document.querySelectorAll('.cc-price-filter');

        if (!riskMeter || !riskText || !annualCostEl) return;

        // Logic: Accept up to 5 digits but make changes only after 4th digit (>= 1000)
        if (price < 1000) {
             // Optional: reset or keep 0?
             // If we want to show 0 until valid input:
             annualCostEl.textContent = `You spend ₹0 per year on maintenance.`;
             if (maintenanceTextEl) maintenanceTextEl.textContent = `(Enter purifier cost to see calculation)`;
             if (currentAnnualCostEl) currentAnnualCostEl.textContent = `₹0`;
             if (savings1yEl) savings1yEl.textContent = `₹0`;
             if (savings5yEl) savings5yEl.textContent = `₹0`;
             ccElements.forEach(el => el.textContent = `₹0`);
             return; 
        }

        let annualCost = 0;
        if (price < 7000) {
            annualCost = (price / 5) + 3500;
        } else {
            annualCost = (price / 5) + 4500;
        }
        
        const total5Years = annualCost * 5;

        // Care Zero Costs (Fixed)
        const czAnnual = 2796;
        const czTotal = 13980;
        
        // Savings
        const savings1y = annualCost - czAnnual;
        const savings5y = total5Years - czTotal;

        // Update Text
        annualCostEl.textContent = `You spend ₹${Math.round(annualCost)} per year on maintenance & depreciation.`;
        if (maintenanceTextEl) maintenanceTextEl.textContent = `Based on your initial purifier cost of ₹${price}`;

        // Update Comparison Label
        if (currentAnnualCostEl) currentAnnualCostEl.textContent = `₹${Math.round(annualCost)}`;

        // Update Savings
        if (savings1yEl) savings1yEl.textContent = `₹${Math.round(savings1y)}`;
        if (savings5yEl) savings5yEl.textContent = `₹${Math.round(savings5y)}`;

        // Update Current Choice Table
        ccElements.forEach((el, index) => {
             if (index < 5) {
                 el.textContent = `₹${Math.round(annualCost)}`;
             } else {
                 el.textContent = `₹${Math.round(total5Years)}`;
             }
        });

        // Set Medium Risk
        riskMeter.className = 'risk-meter-sprite medium';
        riskText.textContent = 'Medium Risk';
        riskText.style.color = '#ff9800';
    }

    radios.forEach(r => r.addEventListener('change', updatePriceVisibility));
    if (canPriceInput) {
        canPriceInput.addEventListener('input', updateRiskMeter);
    }
    if (filterPriceInput) {
        filterPriceInput.addEventListener('input', () => {
             // Limit to 5 digits
             if (filterPriceInput.value.length > 5) {
                 filterPriceInput.value = filterPriceInput.value.slice(0, 5);
             }
             updateFilterRiskMeter();
        });
    }

    // Initialize on load
    updatePriceVisibility();
});
/* Extracted from index.php - Header Scroll */
  document.addEventListener("DOMContentLoaded", function () {
    const header = document.querySelector("header"); // Select the header
    let lastScrollTop = 0;
    const scrollThreshold = 100; // Threshold to hide/show the header

    window.addEventListener("scroll", function () {
      let currentScroll = window.pageYOffset || document.documentElement.scrollTop;

      if (currentScroll > lastScrollTop && currentScroll > scrollThreshold) {
        // Scrolling down past the threshold, hide the header
        header.classList.add("hidden-header");
      } else if (currentScroll < lastScrollTop) {
        // Scrolling up, show the header
        header.classList.remove("hidden-header");
      }

      lastScrollTop = currentScroll <= 0 ? 0 : currentScroll; // Prevent negative values
    });
  });

/* Extracted from index.php - Two Col Sticky */
  (function () {
    function init() {
      const section = document.querySelector('.ro-two-col');
      const rightCol = document.querySelector('.ro-two-col .ro-right-col');
      const sticky = document.querySelector('.ro-two-col .ro-right-sticky');

      if (!section || !rightCol || !sticky) return;

      const stickyTop = 88;
      const mobileMax = 781;

      function reset() {
        sticky.classList.remove('is-fixed', 'is-bottom');
        sticky.style.left = '';
        sticky.style.width = '';
        sticky.style.top = '';
        rightCol.style.minHeight = '';
      }

      function update() {
        if (window.innerWidth <= mobileMax) {
          reset();
          return;
        }

        const sectionRect = section.getBoundingClientRect();
        const sectionTop = sectionRect.top + window.scrollY;
        const sectionHeight = section.offsetHeight;
        const stickyHeight = sticky.offsetHeight;

        rightCol.style.minHeight = stickyHeight + 'px';

        const startY = sectionTop - stickyTop;
        const endY = sectionTop + sectionHeight - stickyTop - stickyHeight;
        const y = window.scrollY;

        if (y < startY) {
          reset();
          return;
        }

        if (y >= endY) {
          sticky.classList.remove('is-fixed');
          sticky.classList.add('is-bottom');
          sticky.style.left = '';
          sticky.style.width = '';
          sticky.style.top = '';
          return;
        }

        const rightRect = rightCol.getBoundingClientRect();
        sticky.classList.remove('is-bottom');
        sticky.classList.add('is-fixed');
        sticky.style.top = stickyTop + 'px';
        sticky.style.left = rightRect.left + 'px';
        sticky.style.width = rightRect.width + 'px';
      }

      update();
      window.addEventListener('scroll', update, { passive: true });
      window.addEventListener('resize', update);
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }
  })();

/* Extracted from index.php - Plan UI Update */
  (function () {
    function updatePlanUI(form) {
      if (!form) return;
      const checked = form.querySelector('input[name="ro_plan_term"]:checked');
      if (!checked) return;

      const termLabel = (checked.dataset.termLabel || '').trim() || (checked.value ? (checked.value + ' Years') : '');
      const total = (checked.dataset.total || '').trim();
      const monthly = (checked.dataset.monthly || '').trim();

      const summary = form.querySelector('[data-plan-summary]');
      if (summary) {
        const termEl = summary.querySelector('.ro-plan-summary-term');
        const priceEl = summary.querySelector('.ro-plan-summary-price');
        if (termEl) termEl.textContent = termLabel;
        if (priceEl) priceEl.textContent = total ? ('₹ ' + total) : '';
      }

      const hiddenTotal = form.querySelector('input[name="ro_plan_total"][data-plan-total]');
      if (hiddenTotal) hiddenTotal.value = total;
      const hiddenMonthly = form.querySelector('input[name="ro_plan_monthly"][data-plan-monthly]');
      if (hiddenMonthly) hiddenMonthly.value = monthly;
    }

    document.addEventListener('change', function (e) {
      const t = e.target;
      if (!t || t.tagName !== 'INPUT') return;
      if (t.getAttribute('name') !== 'ro_plan_term') return;
      const form = t.closest('form');
      updatePlanUI(form);
    });

    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('form#cctv-requirement-form').forEach(updatePlanUI);
    });
  })();

/* Extracted from index.php - Care Zero Modal */
      // Get the modal
      var modal = document.getElementById("careZeroModal");

      // Get the button that opens the modal
      var btn = document.querySelector(".care-zero-modal-button");

      // Get the <span> element that closes the modal
      var span = document.getElementsByClassName("close-button")[0];

      if (btn && modal && span) {
        // When the user clicks the button, open the modal 
        btn.onclick = function() {
          modal.style.display = "flex"; // Use flex to center content
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
          modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
          if (event.target == modal) {
            modal.style.display = "none";
          }
        }
      }

/* Extracted from index.php - Visitor Tracking */
        // Initialize visitor tracking
        let scrollDepth = 0;
        let startTime = Date.now();

        // Capture scroll depth
        window.addEventListener('scroll', () => {
            const scrolled = Math.ceil((window.scrollY / document.body.scrollHeight) * 100);
            scrollDepth = Math.max(scrollDepth, scrolled);

        });


        document.addEventListener("DOMContentLoaded", function () {
    const goToTopBtn = document.getElementById("goToTop");

    window.addEventListener("scroll", function () {
        const scrollPosition = window.scrollY;
        const quotationSection = document.getElementById("quotation");

        
        if (scrollPosition > 100) {
            goToTopBtn.style.display = "block";
        } else {
            goToTopBtn.style.display = "none";
        }

        // Hide the button when inside the #quotation section
        if (quotationSection) {
            const quotationRect = quotationSection.getBoundingClientRect();
            if (quotationRect.top < window.innerHeight && quotationRect.bottom > 1500) {
                goToTopBtn.classList.add("hide-button");
            } else {
                goToTopBtn.classList.remove("hide-button");
            }
        }
    });

    // Scroll to Top Action
    goToTopBtn.addEventListener("click", function () {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });
});


        // Send data to the server
        function sendData(data) {
            fetch('https://smartronic.online/admin/track.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });
        }

        // Send user activity on unload
        window.addEventListener('beforeunload', () => {
            const timeOnPage = Math.ceil((Date.now() - startTime) / 1000);
            sendData({
                scrollDepth: scrollDepth,
                timeOnPage: timeOnPage,
            });

        });

        // Tracking pixel for capturing visitor info
        const img = document.getElementById('tracking-image');
        if (img) {
            img.src = `https://smartronic.online/admin/capture.php?timestamp=${Date.now()}`;
        }

/* Extracted from index.php - Quotation Overlay */
   document.addEventListener("DOMContentLoaded", function () {

    
  const overlay = document.getElementById("quotation-overlay");
  const closeBtn = document.getElementById("close-overlay");
  const quoteSection = document.querySelector("#quotation")?.innerHTML;

  if (!overlay || !closeBtn || !quoteSection) {
    // console.error("One or more required elements are missing.");
    return;
  }

  function showQuotationOverlay() {
    if (window.location.hash === "#show-quotation") {
      overlay.style.display = "block";
      document.body.classList.add("overlay-active"); // Disable body scroll
      document.querySelector("#quotation-overlay-form").innerHTML += quoteSection;
	  document.querySelector("#quotation").innerHTML = '';
    }
  }

  showQuotationOverlay();

  window.addEventListener("hashchange", showQuotationOverlay);

  closeBtn.addEventListener("click", function () {
	// Hide the overlay and re-enable body scrolling
    overlay.style.display = "none";
	document.body.classList.remove("overlay-active");
	document.querySelector("#quotation-overlay-form").innerHTML = '';

    // Restore content back to the original section
    document.querySelector("#quotation").innerHTML = quoteSection;
	window.location.hash = ""; // Remove #show-quotation from URL
	// handleCCTVFormSubmission(); // Undefined function? commented out to be safe
  });
});

(function () {
  function initPremiumHeroV3Slider() {
    var slider = document.querySelector('.premium-hero-v3-slider');
    if (!slider) return;
    var slides = Array.prototype.slice.call(slider.querySelectorAll('.phv2-v3-slide'));
    if (!slides.length) return;
    var toggle = slider.querySelector('.phv2-toggle');
    var currentIndex = slides.findIndex(function (slide) {
      return slide.classList.contains('is-active');
    });
    if (currentIndex < 0) currentIndex = 0;
    var isPaused = false;
    var timerId = null;

    function getDelay(index) {
      var delay = slides[index].getAttribute('data-delay');
      return delay ? parseInt(delay, 10) : 5200;
    }

    function setActive(index) {
      slides.forEach(function (slide, i) {
        if (i === index) {
          slide.classList.add('is-active');
        } else {
          slide.classList.remove('is-active');
        }
      });
      currentIndex = index;
    }

    function scheduleNext() {
      if (isPaused) return;
      clearTimeout(timerId);
      timerId = setTimeout(function () {
        var nextIndex = (currentIndex + 1) % slides.length;
        setActive(nextIndex);
        scheduleNext();
      }, getDelay(currentIndex));
    }

    function updateToggle() {
      if (!toggle) return;
      toggle.setAttribute('aria-pressed', String(isPaused));
      toggle.setAttribute('aria-label', isPaused ? 'Play' : 'Pause');
      toggle.setAttribute('title', isPaused ? 'Play' : 'Pause');
      toggle.innerHTML = isPaused ? '<i class="fa-solid fa-play"></i>' : '<i class="fa-solid fa-pause"></i>';
    }

    if (toggle) {
      toggle.addEventListener('click', function () {
        isPaused = !isPaused;
        updateToggle();
        if (!isPaused) scheduleNext();
      });
    }

    setActive(currentIndex);
    updateToggle();
    scheduleNext();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPremiumHeroV3Slider);
  } else {
    initPremiumHeroV3Slider();
  }
})();

(function () {
  function initPremiumHeroV3MosaicEffects() {
    var slider = document.querySelector('.premium-hero-v3-slider');
    if (!slider) return;
    var tiles = Array.prototype.slice.call(slider.querySelectorAll('.phv2-v3-mosaic .phv2-tile'));
    if (!tiles.length) return;
    var currentIndex = -1;

    function pickNextIndex() {
      if (tiles.length === 1) return 0;
      var nextIndex = Math.floor(Math.random() * tiles.length);
      while (nextIndex === currentIndex) {
        nextIndex = Math.floor(Math.random() * tiles.length);
      }
      return nextIndex;
    }

    function triggerEffect() {
      if (currentIndex >= 0) {
        tiles[currentIndex].classList.remove('is-animating');
      }
      currentIndex = pickNextIndex();
      tiles[currentIndex].classList.add('is-animating');
      setTimeout(function () {
        tiles[currentIndex].classList.remove('is-animating');
      }, 1800);
    }

    function schedule() {
      triggerEffect();
      var delay = 4000 + Math.floor(Math.random() * 1001);
      setTimeout(schedule, delay);
    }

    schedule();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPremiumHeroV3MosaicEffects);
  } else {
    initPremiumHeroV3MosaicEffects();
  }
})();


/* Extracted from comparison-animations.js */
document.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('.comparison-cards .card');

  const cardObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-animated');
        observer.unobserve(entry.target); // Stop observing once animated
      }
    });
  }, {
    threshold: 0.1, // Trigger when 10% of the card is visible
    rootMargin: '0px 0px -50px 0px' // Adjust when the animation starts
  });

  cards.forEach(card => {
    cardObserver.observe(card);
  });

  const flowObservers = new Map();

  cards.forEach(card => {
    const flow = card.querySelector('.flow');
    if (flow) {
      const flowObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            flow.classList.add('is-animated');
            observer.unobserve(entry.target); // Stop observing once animated
          }
        });
      }, {
        threshold: 0.5, // Trigger when 50% of the flow is visible
        rootMargin: '0px 0px -50px 0px'
      });
      flowObserver.observe(flow);
      flowObservers.set(flow, flowObserver);
    }
  });
});
