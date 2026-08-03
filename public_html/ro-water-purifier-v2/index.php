<?php
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$currentUrl = 'https://smartronic.online/ro-water-purifier-v2/' . ($queryString !== '' ? '?' . $queryString : '');
$phone = '8884831000';
$waText = rawurlencode('Hi Smartronic, I want details for Care Zero RO Water Purifier installation/service.');
$whatsAppUrl = 'https://wa.me/91' . $phone . '?text=' . $waText;
$plans = [
    ['term' => '2 Years', 'price' => '7990', 'monthly' => '189', 'label' => 'Starter protection'],
    ['term' => '3 Years', 'price' => '9990', 'monthly' => '169', 'label' => 'Popular choice'],
    ['term' => '5 Years', 'price' => '13990', 'monthly' => '149', 'label' => 'Best long-term value'],
];
$faqs = [
    ['q' => 'Is installation included with Care Zero RO?', 'a' => 'Yes. The current Care Zero offer includes professional installation support and an installation kit. The team confirms fitment and plan details before booking.'],
    ['q' => 'What purification technology does it use?', 'a' => 'The existing Care Zero page describes RO + UV + UF + Copper filtration with TDS control for safe and better-tasting drinking water.'],
    ['q' => 'Does the plan include filter replacement?', 'a' => 'The offer highlights complete warranty support and filter replacement coverage for selected plans. Exact inclusions are confirmed before purchase.'],
    ['q' => 'Is it suitable for tanker, borewell, or municipal water?', 'a' => 'Yes. The product is positioned for borewell, tanker, tap, and municipal water sources. A water/source check helps choose the right setup.'],
];
?>
<!doctype html>
<html lang="en-IN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="index,follow,max-image-preview:large">
  <title>Care Zero RO Water Purifier Bangalore | Free Installation & Warranty Support</title>
  <meta name="description" content="Book Smartronic Care Zero RO water purifier in Bangalore with RO+UV+UF+Copper filtration, TDS control, professional installation, warranty support, and quick WhatsApp assistance.">
  <link rel="canonical" href="https://smartronic.online/ro-water-purifier-v2/">
  <meta property="og:title" content="Care Zero RO Water Purifier by Smartronic">
  <meta property="og:description" content="Premium RO water purifier sales and service with professional installation, TDS control, and Care Zero support plans.">
  <meta property="og:image" content="https://smartronic.online/ro-water-purifier-v2/images/hero-water-1200.jpg">
  <meta property="og:url" content="<?php echo htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:type" content="website">
  <meta name="theme-color" content="#f5f5f7">
  <link rel="preload" href="/ro-water-purifier-v2/images/hero-water-1200.jpg" as="image" fetchpriority="high">
  <link rel="stylesheet" href="/ro-water-purifier-v2/css/styles.css">
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-8WDFCD4S8D"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-8WDFCD4S8D');
  </script>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "LocalBusiness",
        "@id": "https://smartronic.online/#business",
        "name": "Smartronic",
        "url": "https://smartronic.online/",
        "telephone": "+918884831000",
        "areaServed": "Bangalore",
        "makesOffer": {
          "@type": "Offer",
          "itemOffered": {
            "@type": "Product",
            "name": "Care Zero RO Water Purifier",
            "brand": {"@type": "Brand", "name": "Care Zero"},
            "description": "RO + UV + UF + Copper water purifier with TDS control and professional installation support."
          },
          "priceCurrency": "INR",
          "price": "7990"
        }
      },
      {
        "@type": "FAQPage",
        "mainEntity": [
          <?php foreach ($faqs as $index => $faq): ?>{
            "@type": "Question",
            "name": <?php echo json_encode($faq['q']); ?>,
            "acceptedAnswer": {"@type": "Answer", "text": <?php echo json_encode($faq['a']); ?>}
          }<?php echo $index < count($faqs) - 1 ? ',' : ''; ?>
          <?php endforeach; ?>
        ]
      }
    ]
  }
  </script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>

  <header class="site-header">
    <a class="brand" href="#top" aria-label="Smartronic Care Zero">
      <img src="/ro-water-purifier/image/carezero.png" width="44" height="44" alt="Care Zero logo">
      <span><b>Care Zero</b> by Smartronic</span>
    </a>
    <nav class="nav-actions" aria-label="Quick actions">
      <a href="#plans">Plans</a>
      <a href="#faq">FAQ</a>
      <a class="nav-call" href="tel:<?php echo $phone; ?>">Call <?php echo $phone; ?></a>
    </nav>
  </header>

  <main id="main">
    <section class="hero" id="top">
      <div class="hero-copy">
        <p class="eyebrow">Care Zero RO for Bangalore homes</p>
        <h1>Pure water. Simply cared for.</h1>
        <p class="hero-lede">A premium RO purifier experience with RO + UV + UF + Copper purification, TDS control, professional installation, and warranty-led service support.</p>
        <div class="hero-actions">
          <a class="btn btn-primary" href="#enquiry">Get Quote</a>
          <a class="btn btn-call" href="tel:<?php echo $phone; ?>" data-track="call">Call Now</a>
          <a class="btn btn-wa" href="<?php echo htmlspecialchars($whatsAppUrl, ENT_QUOTES, 'UTF-8'); ?>" data-track="whatsapp">WhatsApp</a>
        </div>
        <div class="trust-row" aria-label="Trust highlights">
          <span>Professional installation</span>
          <span>Warranty-led support</span>
          <span>Filter replacement plans</span>
          <span>Quick WhatsApp response</span>
        </div>
      </div>

      <aside class="hero-card" aria-label="Quick enquiry form">
        <picture>
          <source media="(max-width: 720px)" srcset="/ro-water-purifier-v2/images/hero-water-720.jpg">
          <img src="/ro-water-purifier-v2/images/hero-water-1200.jpg" width="1200" height="800" alt="Care Zero RO water purifier for safe drinking water" loading="eager" decoding="async" fetchpriority="high">
        </picture>
        <div class="enquiry-card" id="enquiry">
          <div class="form-head">
            <p class="eyebrow">Quick booking</p>
            <h2>Get expert guidance.</h2>
            <p>Share your number for price, plan, installation, or service support.</p>
          </div>
          <form id="ro-lead-form" novalidate>
            <input type="hidden" name="action" value="crf_save_form_data">
            <input type="hidden" name="num_cameras" value="1">
            <input type="hidden" name="dvr_type" value="RO Water Purifier">
            <input type="hidden" name="hdd_size" id="selected-plan" value="3 Years - Care Zero RO">
            <input type="hidden" name="camera_resolution" id="selected-service" value="Get Quote">
            <input type="hidden" name="city" value="Bangalore">
            <input type="hidden" name="form_device" value="main-mobile">
            <input class="honeypot" type="text" name="company" tabindex="-1" autocomplete="off" aria-hidden="true">

            <div class="field">
              <label for="customer-name">Name</label>
              <input id="customer-name" name="customer_name" type="text" autocomplete="name" placeholder="Your name" required aria-describedby="name-error">
              <small class="error" id="name-error" aria-live="polite"></small>
            </div>
            <div class="field">
              <label for="whatsapp-number">WhatsApp number</label>
              <input id="whatsapp-number" name="whatsapp_number" type="tel" inputmode="numeric" autocomplete="tel" placeholder="10-digit mobile number" required aria-describedby="phone-error">
              <small class="error" id="phone-error" aria-live="polite"></small>
            </div>
            <div class="field two">
              <label for="service-type">Need</label>
              <select id="service-type" name="service_type">
                <option>Get Quote</option>
                <option>Book Installation</option>
                <option>Book Service</option>
                <option>Water Quality Check</option>
              </select>
            </div>
            <div class="field two">
              <label for="plan-choice">Plan</label>
              <select id="plan-choice" name="plan_choice">
                <option>3 Years - Care Zero RO</option>
                <option>2 Years - Care Zero RO</option>
                <option>5 Years - Care Zero RO</option>
                <option>Not sure, advise me</option>
              </select>
            </div>
            <label class="consent">
              <input type="checkbox" id="consent" checked required>
              <span>I agree to receive a call or WhatsApp update from Smartronic.</span>
            </label>
            <button class="btn btn-primary full" type="submit">Submit Enquiry</button>
            <p class="form-note" id="form-status" role="status" aria-live="polite">No long form. Just enough details for a quick response.</p>
          </form>
        </div>
      </aside>
    </section>

    <section class="metrics" aria-label="Key product highlights">
      <div><strong>7-stage</strong><span>RO + UV + UF + Copper filtration</span></div>
      <div><strong>8 litre</strong><span>storage for daily family use</span></div>
      <div><strong>15 LPH</strong><span>purification capacity</span></div>
      <div><strong>Local</strong><span>Bangalore install and service support</span></div>
    </section>

    <section class="section split">
      <div class="section-copy">
        <p class="eyebrow">Why Care Zero</p>
        <h2>Designed for families who want safe water and predictable care.</h2>
        <p>Can water delivery and ordinary purifier maintenance can become repetitive. Care Zero focuses on purity, service clarity, and simple ownership.</p>
        <div class="mini-cta">
          <a class="btn btn-primary" href="#enquiry">Book a Call Back</a>
          <a class="text-link" href="<?php echo htmlspecialchars($whatsAppUrl, ENT_QUOTES, 'UTF-8'); ?>">Ask on WhatsApp</a>
        </div>
      </div>
      <div class="benefit-grid">
        <article><span>01</span><h3>Better water control</h3><p>TDS control helps balance taste while reducing excess dissolved salts.</p></article>
        <article><span>02</span><h3>Multi-source ready</h3><p>Positioned for borewell, tanker, tap, and municipal water supply.</p></article>
        <article><span>03</span><h3>Service-backed purchase</h3><p>Installation, support, warranty, and filter replacement details are handled by one team.</p></article>
        <article><span>04</span><h3>Cleaner kitchen experience</h3><p>Professional setup helps keep the purifier positioned, tested, and ready for daily use.</p></article>
      </div>
    </section>

    <section class="section media-section">
      <div class="media-frame">
        <img src="/ro-water-purifier-v2/images/7stage-900.jpg" width="900" height="506" alt="Seven stage RO water filtration process" loading="lazy" decoding="async">
      </div>
      <div class="section-copy">
        <p class="eyebrow">Purification</p>
        <h2>Advanced purification. Balanced taste.</h2>
        <ul class="check-list">
          <li>RO filtration for dissolved impurities</li>
          <li>UV and UF layers for microbiological protection</li>
          <li>Copper filtration positioning from current Care Zero page</li>
          <li>TDS control for better taste and mineral balance</li>
        </ul>
      </div>
    </section>

    <section class="section" id="plans">
      <div class="section-head">
        <p class="eyebrow">Pricing and offer</p>
        <h2>Clear Care Zero plan options.</h2>
        <p>These plan values come from the current Care Zero page. Final recommendation is confirmed after your usage and water source are understood.</p>
      </div>
      <div class="plans">
        <?php foreach ($plans as $index => $plan): ?>
          <article class="plan <?php echo $index === 1 ? 'is-featured' : ''; ?>">
            <p><?php echo htmlspecialchars($plan['label'], ENT_QUOTES, 'UTF-8'); ?></p>
            <h3><?php echo htmlspecialchars($plan['term'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <strong>&#8377;<?php echo htmlspecialchars($plan['price'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <span>Approx. &#8377;<?php echo htmlspecialchars($plan['monthly'], ENT_QUOTES, 'UTF-8'); ?>/mo</span>
            <ul>
              <li>Professional installation support</li>
              <li>Warranty-led service assurance</li>
              <li>Filter replacement support as per plan</li>
            </ul>
            <a class="btn btn-plan" href="#enquiry" data-plan="<?php echo htmlspecialchars($plan['term'], ENT_QUOTES, 'UTF-8'); ?>">Select Plan</a>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="section process">
      <div class="section-head">
        <p class="eyebrow">Easy booking</p>
        <h2>Four simple steps to better water.</h2>
      </div>
      <ol class="process-grid">
        <li><strong>Share enquiry</strong><span>Call, WhatsApp, or submit the short form.</span></li>
        <li><strong>Confirm needs</strong><span>Discuss water source, family use, and preferred plan.</span></li>
        <li><strong>Schedule visit</strong><span>Choose an installation or service slot.</span></li>
        <li><strong>Install and test</strong><span>Technician installs, checks water flow, and explains usage.</span></li>
      </ol>
    </section>

    <section class="section visual-grid">
      <article>
        <img src="/ro-water-purifier-v2/images/tdscontrol-900.jpg" width="900" height="506" alt="TDS control system for RO purifier" loading="lazy" decoding="async">
        <h3>Balanced taste control</h3>
        <p>TDS control helps tune water taste based on the source.</p>
      </article>
      <article>
        <img src="/ro-water-purifier-v2/images/support-900.jpg" width="900" height="506" alt="Smartronic service support for purifier customers" loading="lazy" decoding="async">
        <h3>Service assurance</h3>
        <p>Support is part of the purchase journey, not an afterthought.</p>
      </article>
      <article>
        <img src="/ro-water-purifier-v2/images/everyday-900.jpg" width="900" height="506" alt="Everyday family drinking water use" loading="lazy" decoding="async">
        <h3>Everyday family use</h3>
        <p>8-litre storage and 15 LPH capacity are positioned for normal home needs.</p>
      </article>
    </section>

    <section class="section review-section">
      <div class="section-copy">
        <p class="eyebrow">Trust and confidence</p>
        <h2>Made for a confident decision.</h2>
        <p>Instead of pushing a confusing catalogue, this page keeps the decision clear: purification quality, installation support, warranty/service assurance, and quick local contact.</p>
      </div>
      <div class="review-cards" aria-label="Customer decision factors">
        <article><strong>Clear pricing</strong><span>Plan values shown before the enquiry.</span></article>
        <article><strong>Local response</strong><span>Call and WhatsApp are always visible.</span></article>
        <article><strong>Service clarity</strong><span>Warranty and filter support are highlighted upfront.</span></article>
      </div>
    </section>

    <section class="section faq" id="faq">
      <div class="section-head">
        <p class="eyebrow">Questions</p>
        <h2>RO purifier FAQ.</h2>
      </div>
      <?php foreach ($faqs as $faq): ?>
        <details>
          <summary><?php echo htmlspecialchars($faq['q'], ENT_QUOTES, 'UTF-8'); ?></summary>
          <p><?php echo htmlspecialchars($faq['a'], ENT_QUOTES, 'UTF-8'); ?></p>
        </details>
      <?php endforeach; ?>
    </section>

    <section class="final-cta">
      <div>
        <p class="eyebrow">Ready to choose safer water?</p>
        <h2>Talk to Smartronic before you buy.</h2>
        <p>Get plan guidance, installation details, and service clarity in one quick conversation.</p>
      </div>
      <div class="hero-actions">
        <a class="btn btn-primary" href="#enquiry">Get Quote</a>
        <a class="btn btn-wa" href="<?php echo htmlspecialchars($whatsAppUrl, ENT_QUOTES, 'UTF-8'); ?>" data-track="whatsapp">WhatsApp</a>
      </div>
    </section>
  </main>

  <div class="mobile-cta" aria-label="Sticky mobile contact actions">
    <a href="tel:<?php echo $phone; ?>" data-track="call">Call</a>
    <a href="<?php echo htmlspecialchars($whatsAppUrl, ENT_QUOTES, 'UTF-8'); ?>" data-track="whatsapp">WhatsApp</a>
  </div>

  <script src="/ro-water-purifier-v2/js/app.js" defer></script>
</body>
</html>
