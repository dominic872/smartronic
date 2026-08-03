jQuery(document).ready(function($) {
    const submitButton = $('#cctv-requirement-form button[type="submit"]');
    const whatsappInput = $('#num-whatsapp');

    // Disable submit button initially
    submitButton.prop('disabled', true);

    // Enable submit button only when a valid 10-digit mobile number is entered
    whatsappInput.on('input', function () {
        const mobileNumber = $(this).val();
        const isValidMobile = (mobileNumber.match(/\d/g) || []).length >= 10; // Check if there are 10 or more digits
        submitButton.prop('disabled', !isValidMobile); // Enable or disable button based on validity
    });
    
    function handleCCTVFormSubmission() {
        $('#cctv-requirement-form').off('submit').on('submit', function (e) {
            gtagSendEvent('https://smartronic.online'); // Google Analytics Event
            e.preventDefault(); // Prevent default form submission
            const currentForm = this;
            const submitButton = document.getElementById("next-button");
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = "Submitting..."; // Optional UX improvement
            }
            function getQueryParam(name) {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(name);
            }

            var gadsParam = getQueryParam("gads") || getQueryParam("Gads"); // e.g. "GetLeads"
            var gadCampaignIdParam = getQueryParam("gad_campaignid"); // e.g. "GetLeads"
            var dryRunParam = getQueryParam("dry_run") || getQueryParam("no_db");
            const customerNameInput = currentForm.querySelector('#customer-name, #gads-name, input[name="customer-name"], input[name="name"]');
            const customerName = customerNameInput ? customerNameInput.value.trim() : '';
            const cityInput = currentForm.querySelector('input[name="city"]');
            const cityValue = cityInput ? cityInput.value.trim() : '';
            const pageQuery = window.location.search.replace(/^\?/, '');
    
            const formData = {
                action: 'crf_save_form_data',
                num_cameras: $('#num-cameras').val(),
                dvr_type: $('input[name="dvr-type"]:checked').val(),
                hdd_size: $('input[name="hdd-size"]:checked').val(),
                camera_resolution: $('input[name="camera-resolution"]:checked').val(),
                whatsapp_number: $('#num-whatsapp').val(),
                gads: gadsParam,  
                gad_campaignid: gadCampaignIdParam,
                page_query: pageQuery,
                page_url: window.location.href,
                form_device: window.innerWidth <= 767 ? 'main-mobile' : 'main-desk'
            };

            if (customerName) {
                formData.customer_name = customerName;
            }

            if (cityValue) {
                formData.city = cityValue;
            }

            if (dryRunParam && dryRunParam !== '0') {
                formData.dry_run = dryRunParam;
            }

            console.log(formData, gadsParam);
    
            $.post(crf_ajax_object.ajax_url, formData, function (response) {
                console.log(response);
                let responseObj = {};
                if (typeof response !== 'object') {
                    console.log(response, "not obj");
                    try {
                        responseObj = JSON.parse(response);
                        console.log('Parsed Object:', responseObj, responseObj.success);
                    } catch (error) {
                        console.error('Invalid JSON string:', error);
                    }
                } else {
                    console.log('Response is already an object:', response);
                    responseObj = response;
                }
    
                const headerHeight = $('header').outerHeight(); 
                if (responseObj.success) {
                    if (typeof window.showLeadSuccessState === 'function') {
                        window.showLeadSuccessState();
                    } else {
                        $('.form-holder').css('display', 'none');
                        $('.form-success-message').css('display', 'block');
                        $('#cctv-requirement-form').replaceWith('<p class="success-message">Form data saved successfully!</p>');
                    }
                    try {
                        window.sessionStorage.setItem('smartronicPromoPopupState', 'closed');
                    } catch (e) {}
                    window.dispatchEvent(new CustomEvent('smartronic:leadSubmitted', { detail: { source: 'main-form' } }));
                    $('html, body').animate({
                        scrollTop: $('.form-success-message').offset().top - headerHeight - 20,
                    }, 600);
                } else {
                    // Display error message
                    $('#cctv-requirement-form').prepend('<p class="error-message">Error: ' + responseObj.data + '</p>');
                    $('html, body').animate({
                        scrollTop: $('#cctv-requirement-form').offset().top - headerHeight - 20,
                    }, 600); 
                }
            }).fail(function () {
                // Handle AJAX failure
                $('#cctv-requirement-form').replaceWith('<p class="error-message">AJAX call failed. Please check your network connection.</p>');
            });
        });
    }

    window.handleCCTVFormSubmission = handleCCTVFormSubmission;
    
    handleCCTVFormSubmission();
    const numCamerasInput = $('#num-cameras');
    const numCamerasNumberInput = $('#num-cameras-input');
    
    // Sync the range slider and number input for cameras
    numCamerasInput.on('input', function() {
        numCamerasNumberInput.val(this.value);
    });

    numCamerasNumberInput.on('input', function() {
        numCamerasInput.val(this.value);
    });

    const callNowBtn = document.getElementById('call-now-btn');
    const qrPopup = document.getElementById('qr-popup');
    const overlay = document.createElement('div'); // Create overlay for popup

    overlay.className = 'qr-popup-overlay';
    document.body.appendChild(overlay);

    // Detect if the user is on a mobile device
    const isMobile = window.innerWidth <= 768;
    
    // Event listener for the call button
    callNowBtn.addEventListener('click', function (e) {
        e.preventDefault();

        if (isMobile) {
            // Directly make the call on mobile
            window.location.href = 'tel:8884831000';
        } else {
            // Show the QR code popup on desktops/tablets
            qrPopup.style.display = 'block';
            overlay.style.display = 'block';
        }
    });

    // Close the popup when the overlay is clicked
    overlay.addEventListener('click', function () {
        qrPopup.style.display = 'none';
        overlay.style.display = 'none';
    });
});
