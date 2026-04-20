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
    

    $('#cctv-requirement-form').on('submit', function (e) {
        e.preventDefault(); // Prevent default form submission
        const submitButton = document.getElementById("next-button");
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = "Submitting..."; // Optional UX improvement
            }
        

        const formData = {
            action: 'crf_save_form_data',
            num_cameras: $('#num-cameras').val(),
            dvr_type: $('input[name="dvr-type"]:checked').val(),
            hdd_size: $('input[name="hdd-size"]:checked').val(),
            camera_resolution: $('input[name="camera-resolution"]:checked').val(),
            whatsapp_number: $('#num-whatsapp').val(),
        };
        const urlParams = new URLSearchParams(window.location.search);
        const dryRunParam = urlParams.get('dry_run') || urlParams.get('no_db');
        if (dryRunParam && dryRunParam !== '0') {
            formData.dry_run = dryRunParam;
        }

        console.log(formData, "hello");

        $.post(crf_ajax_object.ajax_url, formData, function (response) {
            if (response.success) {
                // Remove the form and display success message
                $('.form-holder').css('display', 'none');
                $('.form-success-message').css('display', 'block');
                $('#cctv-requirement-form').replaceWith('<p class="success-message">Form data saved successfully!</p>');
            } else {
                // Display error message
                $('#cctv-requirement-form').replaceWith('<p class="error-message">Error: ' + response.data + '</p>');
            }
        }).fail(function () {
            // Handle AJAX failure
            $('#cctv-requirement-form').replaceWith('<p class="error-message">AJAX call failed. Please check your network connection.</p>');
        });
    });

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
            window.location.href = 'tel:888483100';
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
