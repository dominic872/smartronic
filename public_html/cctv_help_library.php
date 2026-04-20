<?php
// Standalone index page with mobile-first layout.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Smartronic | CCTV Help Library</title>
  <meta name="description" content="Hikvision CCTV customer help video library and quick WhatsApp share.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/includes/blocks/navigation/style.min.css">
  <link rel="stylesheet" href="/includes/css/dist/block-library/common.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css">
  <style>
    :root{
      --wp--preset--color--base-2:#ffffff;
      --wp--preset--color--contrast:#111111;
      --wp--preset--spacing--60: 2.5rem;
    }
    .has-base-2-color{color:var(--wp--preset--color--base-2)}
    .has-text-color{color:inherit}
    .has-link-color a{color:inherit}
    .has-background{background-color:transparent}
    :root{
      --bg:#ffffff;
      --fg:#111827;
      --muted:#6b7280;
      --primary:#0ea5e9;
      --primary-600:#0284c7;
      --neutral-100:#f3f4f6;
      --neutral-200:#e5e7eb;
      --radius:10px;
      --space-1:6px;
      --space-2:10px;
      --space-3:14px;
      --space-4:18px;
      --space-5:24px;
    }
    *{box-sizing:border-box}
    html,body{margin:0;padding:0;background:var(--bg);color:var(--fg);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;line-height:1.5}
    a{color:var(--primary);text-decoration:none}
    a:hover{text-decoration:underline}

    /* Header */
    .site-header{
      position:sticky;top:0;z-index:10;background:#fff;border-bottom:1px solid var(--neutral-200)
    }
    .header-wrap{
      max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;padding:var(--space-3) var(--space-4);
    }
    .brand{display:flex;align-items:center;gap:var(--space-2)}
    .brand img{height:36px;width:auto}
    .brand h1{font-size:16px;font-weight:700;margin:0;display:none}
    .section-nav{position:sticky;top:56px;z-index:5;background:#fff;border-bottom:1px solid var(--neutral-200)}
    .section-nav .nav-inner{max-width:980px;margin:0 auto;display:flex;gap:8px;overflow-x:auto;padding:10px var(--space-4)}
    .section-nav .nav-link{padding:8px 10px;border-radius:9999px;white-space:nowrap;background:var(--neutral-100);color:#111;text-decoration:none;font-size:13px}
    .section-nav .nav-link.is-active{background:#e9f6fd;color:#0b4a6f}

    /* Main */
    .container{max-width:980px;margin:0 auto;padding:var(--space-4) var(--space-4)}
    .hero{
      background:linear-gradient(180deg, #e6f6ff 0%, #ffffff 100%);border:1px solid var(--neutral-200);border-radius:var(--radius);padding:var(--space-5)
    }
    .hero h2{margin:0 0 var(--space-2);font-size:20px}
    .hero p{margin:0;color:var(--muted);font-size:14px}

    .sections{margin-top:var(--space-5);display:grid;gap:var(--space-4)}
    .card{
      border:1px solid var(--neutral-200);border-radius:var(--radius);padding:var(--space-4)
    }
    .card h3{margin:0 0 var(--space-2);font-size:16px}
    .link-list{margin:0;padding-left:20px}
    .link-list li{margin:6px 0}
    .tagline{font-size:12px;color:var(--muted);margin-bottom:var(--space-2)}

    /* Footer */
    .site-footer{border-top:1px solid var(--neutral-200);padding:var(--space-4) var(--space-4);font-size:12px;color:var(--muted)}

    /* Responsive */
    @media (min-width: 720px){
      .hero h2{font-size:22px}
      .card-grid{display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4)}
    }
    /* Standard floating buttons (from main site) */
    .go-to-top{
      position:fixed;bottom:20px;right:20px;background:#fc0000!important;color:#fff;border:none;padding:10px 15px;font-size:16px;cursor:pointer;border-radius:5px;display:none;transition:opacity .3s ease-in-out;z-index:10;box-shadow:0 0 10px rgba(0,0,0,.3)
    }
    .wa-fab{position:fixed;bottom:80px;right:20px;width:72px;height:72px;background:#25d366;border:none;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 18px rgba(0,0,0,.25);z-index:12;cursor:pointer}
    .wa-fab i{font-size:36px;line-height:1}
    .wa-popup{position:fixed;bottom:144px;right:20px;width:280px;background:#fff;border:1px solid var(--neutral-200);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);padding:12px;z-index:12;display:none}
    .wa-popup .row{display:flex;gap:8px;align-items:center}
    .wa-popup input{flex:1;min-width:0;padding:10px;border:1px solid var(--neutral-200);border-radius:8px;font-size:14px}
    .wa-popup .send{background:#25D366;color:#fff;border:0;border-radius:8px;padding:10px 12px;font-weight:600;cursor:pointer}
    .wa-popup .close{background:#eee;border:0;border-radius:8px;padding:10px 12px;cursor:pointer}
  </style>
</head>
<body>
  <header class="site-header" role="banner">
    <div class="header-wrap">
      <a class="brand" href="/" aria-label="Smartronic Home">
        <img src="/content/uploads/2025/01/smarthome-black2.svg" alt="Smartronic">
        <h1>Smartronic</h1>
      </a>
    </div>
  </header>

  <main class="container">
    <section class="hero" aria-labelledby="hero-title">
      <h2 id="hero-title">Hikvision CCTV Customer Help Library</h2>
      <p>Step‑by‑step videos for setup, viewing, sharing, motion alerts, playback, and more.</p>
    </section>
    <div class="section-nav" aria-label="Categories">
      <div class="nav-inner">
        <a class="nav-link" href="#first-time">First Time Setup</a>
        <a class="nav-link" href="#viewing">Viewing on Mobile</a>
        <a class="nav-link" href="#sharing">Sharing Access</a>
        <a class="nav-link" href="#motion">Motion Alerts</a>
        <a class="nav-link" href="#playback">Playback</a>
        <a class="nav-link" href="#forgot">Forgot Password</a>
        <a class="nav-link" href="#problems">Common Problems</a>
        <a class="nav-link" href="#offline">Camera Offline</a>
        <a class="nav-link" href="#wifi">Wi‑Fi Camera</a>
        <a class="nav-link" href="#hdd">HDD & Recording</a>
        <a class="nav-link" href="#basic">Basic Settings</a>
        <a class="nav-link" href="#maint">Maintenance</a>
      </div>
    </div>

    <section class="sections">
      <div class="card" id="first-time">
        <h3>1. First Time Setup</h3>
        <ul class="link-list">
          <li><a href="https://www.youtube.com/watch?v=lhlxJMO_GJY" target="_blank" rel="noopener">How to Connect Hikvision DVR to Mobile</a></li>
          <li><a href="https://www.youtube.com/watch?v=8LiiVAV4K40" target="_blank" rel="noopener">Hik‑Connect App Setup Full Tutorial</a></li>
          <li><a href="https://www.youtube.com/watch?v=UNXIXFt5cMc" target="_blank" rel="noopener">Add Device Using QR Code</a></li>
          <li><a href="https://www.youtube.com/watch?v=cz1aXCxZO5A" target="_blank" rel="noopener">Connect Hikvision Camera to Mobile</a></li>
        </ul>
      </div>

      <div class="card" id="viewing">
        <h3>2. Viewing Cameras on Mobile</h3>
        <ul class="link-list">
          <li><a href="https://www.youtube.com/watch?v=llYcPj9R9Wg" target="_blank" rel="noopener">Watch Live Camera on Phone</a></li>
          <li><a href="https://www.youtube.com/watch?v=wG4NxpriS9Q" target="_blank" rel="noopener">Complete Hik‑Connect Tutorial</a></li>
          <li><a href="https://www.youtube.com/watch?v=LtXKmtoEza0" target="_blank" rel="noopener">Setup Hik‑Connect Phone App</a></li>
        </ul>
      </div>

      <div class="card" id="sharing">
        <h3>3. Sharing Cameras With Family / Staff</h3>
        <ul class="link-list">
          <li><a href="https://www.youtube.com/watch?v=0U9yQxW1oHs" target="_blank" rel="noopener">Share Hik‑Connect Device</a></li>
          <li><a href="https://www.youtube.com/watch?v=3n6BDnSx5c4" target="_blank" rel="noopener">Share CCTV Access to Another Phone</a></li>
          <li><a href="https://www.youtube.com/watch?v=Y0vI0YyH5j8" target="_blank" rel="noopener">Remove Shared User</a></li>
        </ul>
      </div>

      <div class="card" id="motion">
        <h3>4. Motion Detection Alerts</h3>
        <ul class="link-list">
          <li><a href="https://www.youtube.com/watch?v=P0_Yq0H7r9g" target="_blank" rel="noopener">Enable Motion Detection</a></li>
          <li><a href="https://www.youtube.com/watch?v=jjqaK29wynY" target="_blank" rel="noopener">Motion Detection Setup</a></li>
          <li><a href="https://www.youtube.com/watch?v=2xJHKSP9yFk" target="_blank" rel="noopener">Enable Motion Push Notifications</a></li>
          <li><a href="https://www.youtube.com/watch?v=AvR-4oXrWGo" target="_blank" rel="noopener">Record Only on Motion</a></li>
          <li><a href="https://www.youtube.com/watch?v=xdlYWDjjWBc" target="_blank" rel="noopener">Motion Recording Setup</a></li>
        </ul>
      </div>

      <div class="card" id="playback">
        <h3>5. Playback & Recording</h3>
        <ul class="link-list">
          <li><a href="https://www.youtube.com/watch?v=QvP9zpnM4eM" target="_blank" rel="noopener">Watch Recorded Video</a></li>
          <li><a href="https://www.youtube.com/watch?v=KTx7uGQmVdA" target="_blank" rel="noopener">Playback on Hik‑Connect App</a></li>
          <li><a href="https://www.youtube.com/watch?v=1qNItxqM7Kk" target="_blank" rel="noopener">Backup CCTV Footage to USB</a></li>
          <li><a href="https://www.youtube.com/watch?v=2V2Jg4SAVY8" target="_blank" rel="noopener">Download CCTV Video</a></li>
        </ul>
      </div>

      <div class="card" id="forgot">
        <h3>6. Forgot Password (Very Common Problem)</h3>
        <ul class="link-list">
          <li><a href="https://www.youtube.com/watch?v=iD43OUm7xek" target="_blank" rel="noopener">Reset Hikvision DVR Password</a></li>
          <li><a href="https://www.youtube.com/watch?v=aijCEAmjTYE" target="_blank" rel="noopener">Reset Hik‑Connect Password</a></li>
          <li><a href="https://www.youtube.com/watch?v=ocuRHHd1MXs" target="_blank" rel="noopener">Reset Using SADP Tool</a></li>
          <li><a href="https://www.youtube.com/watch?v=1C8YQ4F06C8" target="_blank" rel="noopener">Factory Reset Hikvision DVR</a></li>
        </ul>
      </div>

      <div class="card" id="problems">
        <h3>7. Fix Common Problems</h3>
        <ul class="link-list">
          <li><a href="https://www.youtube.com/watch?v=khU4o2RAv7g" target="_blank" rel="noopener">Camera Showing No Video</a></li>
          <li><a href="https://www.youtube.com/watch?v=Da3F2_9pj0g" target="_blank" rel="noopener">Fix No Video Signal Error</a></li>
          <li><a href="https://www.youtube.com/watch?v=rA3s2a1lXrU" target="_blank" rel="noopener">Blurry Camera Image Fix</a></li>
          <li><a href="https://www.youtube.com/watch?v=YFLv2Yl4A7g" target="_blank" rel="noopener">Night Vision Not Working Fix</a></li>
        </ul>
      </div>

      <div class="card" id="offline">
        <h3>8. Camera Offline Problem</h3>
        <ul class="link-list">
          <li><a href="https://www.youtube.com/watch?v=6rR4gXqJqk0" target="_blank" rel="noopener">Fix Device Offline in Hik‑Connect</a></li>
          <li><a href="https://www.youtube.com/watch?v=6s9qfZkJxBo" target="_blank" rel="noopener">Reconnect DVR to Internet</a></li>
          <li><a href="https://www.youtube.com/watch?v=QfY9X7L0mHE" target="_blank" rel="noopener">Fix Network Configuration</a></li>
        </ul>
      </div>

      <div class="card" id="wifi">
        <h3>9. Wi‑Fi Camera Setup</h3>
        <ul class="link-list">
          <li><a href="https://www.youtube.com/watch?v=b7x6QyXW3Ho" target="_blank" rel="noopener">Setup Hikvision Wi‑Fi Camera</a></li>
          <li><a href="https://www.youtube.com/watch?v=bb5JpVYkHZo" target="_blank" rel="noopener">Connect Wi‑Fi Camera to Router</a></li>
          <li><a href="https://www.youtube.com/watch?v=lgwN9Q1y4y4" target="_blank" rel="noopener">Reset Wi‑Fi Camera</a></li>
          <li><a href="https://www.youtube.com/watch?v=9p7c0aQb9Q8" target="_blank" rel="noopener">Fix Wi‑Fi Camera Disconnection</a></li>
        </ul>
      </div>

      <div class="card" id="hdd">
        <h3>10. Hard Disk & Recording Problems</h3>
        <ul class="link-list">
          <li><a href="https://www.youtube.com/watch?v=4m0L0TqX2oY" target="_blank" rel="noopener">Install HDD in DVR</a></li>
          <li><a href="https://www.youtube.com/watch?v=2J8Pp7y2rLQ" target="_blank" rel="noopener">Format Hard Disk</a></li>
          <li><a href="https://www.youtube.com/watch?v=F1QKc7V8Z0A" target="_blank" rel="noopener">Recording Not Working Fix</a></li>
          <li><a href="https://www.youtube.com/watch?v=9g3J4k8v6p0" target="_blank" rel="noopener">Increase Recording Storage</a></li>
        </ul>
      </div>

      <div class="card" id="basic">
        <h3>11. Basic Camera Settings</h3>
        <ul class="link-list">
          <li><a href="https://www.youtube.com/watch?v=ZNGaytWSpUE" target="_blank" rel="noopener">Change DVR Password</a></li>
          <li><a href="https://www.youtube.com/watch?v=2iG4xvV1h1E" target="_blank" rel="noopener">Set Date and Time</a></li>
          <li><a href="https://www.youtube.com/watch?v=H9m6lS3aGQk" target="_blank" rel="noopener">Change Camera Name</a></li>
        </ul>
      </div>

      <div class="card" id="maint">
        <h3>12. Maintenance & Safety</h3>
        <ul class="link-list">
          <li><a href="https://www.youtube.com/watch?v=Zs1OqF3rj4Q" target="_blank" rel="noopener">Clean CCTV Camera Lens</a></li>
          <li><a href="https://www.youtube.com/watch?v=GX7J5p2VfVA" target="_blank" rel="noopener">Restart DVR Properly</a></li>
          <li><a href="https://www.youtube.com/watch?v=8RrKqA3Z2fU" target="_blank" rel="noopener">Firmware Update Guide</a></li>
          <li><a href="https://www.youtube.com/watch?v=bM9uXr9K2w0" target="_blank" rel="noopener">Secure CCTV System From Hackers</a></li>
        </ul>
      </div>
    </section>
  </main>

  <footer class="site-footer" role="contentinfo">
    <div class="container" style="padding:0">
      © <?php echo date('Y'); ?> Smartronic •
      <a href="https://smartronic.online/" target="_blank" rel="noopener">smartronic.online</a> •
      <a href="https://smartronic.online/invoice/terms.php" target="_blank" rel="noopener">Terms</a>
    </div>
  </footer>
  <button id="goToTop" class="go-to-top" aria-label="Go to top">↑ Top</button>
  <button id="shareWAFab" class="wa-fab" aria-label="Share via WhatsApp"><i class="fab fa-whatsapp" aria-hidden="true"></i></button>
  <div id="waSharePopup" class="wa-popup" role="dialog" aria-modal="true" aria-labelledby="waPopupTitle">
    <div style="font-size:13px;margin-bottom:8px" id="waPopupTitle">Share this page via WhatsApp</div>
    <div class="row">
      <input id="waNumber" inputmode="tel" autocomplete="tel" placeholder="+91 98765 43210" aria-label="Phone number">
      <button class="send" id="waSendBtn" type="button">Send</button>
      <button class="close" id="waCloseBtn" type="button" aria-label="Close">×</button>
    </div>
  </div>

  <script>
    (function(){
      function normalizePhone(raw) {
        let p = String(raw || '').trim();
        p = p.replace(/\s+/g, '').replace(/[-().]/g, '');
        if (p.startsWith('+')) return p;
        if (p.startsWith('91')) return '+' + p;
        p = p.replace(/^0+/, '');
        if (p.length === 10) return '+91' + p;
        return '+' + p;
      }
      const input = document.getElementById('waNumber');
      const btn = document.getElementById('waSendBtn');
      const openFab = document.getElementById('shareWAFab');
      const popup = document.getElementById('waSharePopup');
      const closeBtn = document.getElementById('waCloseBtn');
      function openPopup(){ if (popup){ popup.style.display='block'; setTimeout(()=>{ if(input) input.focus(); }, 0);} }
      function closePopup(){ if (popup){ popup.style.display='none'; if(openFab) openFab.focus(); } }
      if (openFab){ openFab.addEventListener('click', openPopup); }
      if (closeBtn){ closeBtn.addEventListener('click', closePopup); }
      document.addEventListener('keydown', function(e){ if(e.key==='Escape') closePopup(); });
      document.addEventListener('click', function(e){ if(popup && popup.style.display==='block' && !popup.contains(e.target) && e.target !== openFab){ closePopup(); }});
      if (btn && input) { btn.addEventListener('click', function(){ const phone = normalizePhone(input.value); const text = 'Hikvision CCTV Help Library: ' + window.location.href; const url = 'https://api.whatsapp.com/send?phone=' + encodeURIComponent(phone) + '&text=' + encodeURIComponent(text); window.open(url, '_blank', 'noopener'); closePopup(); }); }
      // Standard footer behavior: go-to-top toggle
      const goTop = document.getElementById('goToTop');
      window.addEventListener('scroll', function () {
        const y = window.scrollY || window.pageYOffset || 0;
        if (goTop) goTop.style.display = y > 100 ? 'block' : 'none';
      });
      if (goTop) {
        goTop.addEventListener('click', function(){
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
      }
      const links = Array.from(document.querySelectorAll('.section-nav .nav-link'));
      const ids = links.map(a => a.getAttribute('href')).filter(Boolean).map(h => h.slice(1));
      const sections = ids.map(id => document.getElementById(id)).filter(Boolean);
      function setActive(id){ links.forEach(a => { a.classList.toggle('is-active', a.getAttribute('href') === '#' + id); }); }
      if ('IntersectionObserver' in window){
        const io = new IntersectionObserver(entries => {
          entries.forEach(ent => { if (ent.isIntersecting){ setActive(ent.target.id); } });
        }, { rootMargin: '-40% 0px -55% 0px', threshold: 0.01 });
        sections.forEach(sec => io.observe(sec));
      }
      links.forEach(a => { a.addEventListener('click', e => { e.preventDefault(); const id = a.getAttribute('href').slice(1); const el = document.getElementById(id); if(el){ el.scrollIntoView({ behavior:'smooth', block:'start' }); setActive(id); } }); });
    })();
  </script>
</body>
</html>
