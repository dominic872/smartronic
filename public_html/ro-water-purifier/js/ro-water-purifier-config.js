/* Global Configuration & Analytics */

// Variable definitions for Plugins
var crf_ajax_object = {"ajax_url":"/admin_v2/admin-ajax.php"};

// Google Tag Manager
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'G-8WDFCD4S8D');

function gtagSendEvent(url) {
    var callback = function () {
      if (typeof url === 'string') {
        console.log('Quotation registered in GA');
      }
    };
    gtag('event', 'conversion_event_request_quote', {
      'event_callback': callback,
      'event_timeout': 2000,
    });
    return false;
}
