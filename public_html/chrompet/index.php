<?php
// index.php
require 'db.php';
// Start session for access logging
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// fetch audios ordered by audio_order then id
$stmt = $pdo->query("SELECT * FROM audios ORDER BY audio_order ASC, id ASC");
$audios = $stmt->fetchAll();

// fetch transcripts grouped by audio
$transStmt = $pdo->prepare("SELECT * FROM transcripts WHERE audio_id = ? ORDER BY transcript_order ASC, id ASC");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Audio Player — User</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <!-- Materialize CSS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
  <!-- Font Awesome for icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    /* friendly for 50+ years: larger sizes, high contrast */
    
    body { font-size: 1.15rem; color:#000; background:#f7f7f7; font-family: 'Poppins', system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
    .card { margin: 0.8rem auto; max-width:900px; }
    .audio-controls { display:flex; gap:0.5rem; align-items:center; }
    .big-btn { font-size:1.05rem; padding: 10px 18px; border-radius:12px; }
    .transcript { font-size:1rem; line-height:1.6; padding:0.7rem; background:white; border-radius:8px; margin-top:0.5rem; }
    .audio-title { font-weight:600; font-size:1.1rem; }

    /* Enlarge native audio controls (best effort across browsers) */
    audio { width:100%; outline:none; height:72px; }
    /* Safari/Chrome */
    audio::-webkit-media-controls-panel { transform: scale(1.35); transform-origin: left center; }
    audio::-webkit-media-controls-enclosure { padding: 8px 10px; }

    .card { margin-bottom: 20px; overflow: visible; }
    .card .card-content { overflow: visible; }

    button, .btn, .card-title { font-size: 1.2em !important; }

    p { font-size: 2.5em; line-height: 1.6; }

    /* Highlight current playing items in green */
    .card.playing { background: #e6ffed; box-shadow: 0 6px 16px rgba(34,197,94,0.15); border-left: 6px solid #22c55e; }
    .audio-player.playing { background: #e6ffed; border-radius: 8px; }

    /* External control bar */
    #ext-controls { position: fixed; left: 0; right: 0; bottom: 0; z-index: 2000; background: #000; border-top: 2px solid #1f2937; padding: 8px 12px; display: flex; flex-direction: column; gap: 8px; box-shadow: 0 -6px 16px rgba(0,0,0,0.06); }
    #ext-controls.playing { border-top-color: #22c55e; }
    .ext-row { display:flex; align-items:center; gap:10px; }
    .ext-icons { display:flex; align-items:center; gap:12px; }
    .icon-btn { appearance:none; border:none; background:transparent; color:#fff; width:56px; height:56px; border-radius:12px; display:flex; align-items:center; justify-content:center; cursor:pointer; padding-top: 25px}
    .icon-btn i { font-size:28px; line-height:1; }
    .icon-btn:hover { background:#111827; }
    .icon-btn.active { background:#064e3b; }
    #ext-title { font-weight: 600; font-size: 1.0rem; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #ext-status { margin-left:auto; font-weight:600; color:#9ca3af; font-size: .95rem; }
    /* Seek row */
    #ext-seek-row { display:flex; align-items:center; gap:10px; }
    #ext-seek { appearance: none; width: 100%; height: 6px; background: #374151; border-radius: 4px; outline: none; }
    #ext-seek::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 14px; height: 14px; border-radius: 50%; background: #22c55e; cursor: pointer; }
    #ext-time { color:#9ca3af; font-weight:600; min-width: 110px; text-align:right; }

    /* Global status just above ext-controls (bottom) */
    #global-audio-status { position: fixed; left: 0; right: 0; bottom: 140px; z-index: 2050; background: red; color:rgb(245, 239, 239); font-weight: 700; text-align: center; padding: 6px 10px; display: none; line-height: 20px }

    /* Access overlay */
    #access-overlay { position: fixed; inset: 0; background: radial-gradient(1200px 800px at 20% 10%, #0b1220, #05070d); color: #fff; display: none; align-items: center; justify-content: center; z-index: 4000; padding: 24px; }
    #access-overlay.visible { display: flex; }
    .access-panel { width: 100%; max-width: 720px; text-align: center; backdrop-filter: blur(6px); background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 32px 28px; box-shadow: 0 20px 60px rgba(0,0,0,0.35); }
    .access-title { font-family: 'Playfair Display', serif; font-weight: 700; font-size: clamp(1.4rem, 2.2vw + 1rem, 2.4rem); letter-spacing: .3px; margin: 0 0 18px; }
    .access-sub { font-size: 1.05rem; color: #cbd5e1; margin-bottom: 22px; }
    .access-row { display: flex; gap: 10px; justify-content: center; }
    .access-input { flex: 1 1 auto; max-width: 380px; background: #0e1729; color:#fff; border: 1px solid #334155; border-radius: 12px; padding: 14px 16px; font-size: 1rem; outline: none; }
    .access-input::placeholder { color: #94a3b8; }
    .access-btn { background: linear-gradient(135deg, #22c55e, #16a34a); color:#fff; border: none; border-radius: 12px; padding: 14px 18px; font-weight: 700; cursor: pointer; letter-spacing: .3px; }
    .access-btn:hover { filter: brightness(1.05); }

    /* Card number badge */
    .card-badge { 
        display: inline-flex
;
    min-width: 34px;
    text-align: center;
    margin-right: 8px;
    padding: 6px 10px;
    border-radius: 999px;
    background: #f1f1f1;
    color: #000;
    font-weight: 700;
    font-size: .95rem;
    border: 1px solid #999;
    align-items: center;
    justify-content: center;
    line-height: 20px;
    height: 37px;
    margin-top: 23px;}
    .card-badge.sticky { position: sticky; top: 25px; z-index: 2; }
    .card-badge.fixed { 
        position: fixed;
    top: 25px;
    z-index: 3;
    background: red;
    width: 50px;
    height: 50px;
    color: white;
    font-size: 1.5em;
}
    }
    .audio-row { display:flex; align-items:stretch; gap:12px; }
    .audio-row .badge-col { flex: 0 0 48px; display:flex; justify-content:center; align-self: stretch; position: relative; }
    .audio-row .audio-player { flex: 1 1 auto; }

    @media (max-width: 600px) {
      .card-content { padding: 1em !important; }
      .card-title { font-size: 1.4em !important; }
      /* Mobile adjustments remain simple */
    }
    .nav-wrapper strong {
        font-size: 1.5em;
    }

  </style>
</head>
<body>
  <!-- Access overlay -->
  <div id="access-overlay" aria-modal="true" role="dialog" class="<?= empty($_SESSION['access_log_id']) ? 'visible' : '' ?>">
    <div class="access-panel">
      <div class="access-title">Dominic Sagayaraj | Alone, Yet Standing</div>
      <div class="access-sub">Please enter your name to continue</div>
      <div class="access-row">
        <input id="access-name" class="access-input" type="text" placeholder="Your name" autocomplete="name" />
        <button id="access-enter" class="access-btn">Enter</button>
      </div>
      <div id="access-error" style="margin-top:10px;color:#fca5a5;display:none;">Please enter your name</div>
    </div>
  </div>
  <nav class="blue darken-2">
    <div class="nav-wrapper container">
        <strong>Dominic Sagayaraj</strong> | Alone, Yet Standing
    </div>

  <!-- Global Audio Status (above controls) -->
  <div id="global-audio-status" role="status" aria-live="polite">No audio selected</div>

  <!-- External Controls (icons only) -->
  <div id="ext-controls" aria-live="polite">
    <div class="ext-row">
      <div class="ext-icons">
        <button id="ext-prev" class="icon-btn" title="Previous" aria-label="Previous"><i class="fa-solid fa-backward-step"></i></button>
        <button id="ext-toggle" class="icon-btn" title="Play/Pause" aria-label="Play/Pause"><i id="ext-icon" class="fa-solid fa-play"></i></button>
        <button id="ext-unmute" class="icon-btn" title="Unmute" aria-label="Unmute" style="display:none"><i class="fa-solid fa-volume-xmark"></i></button>
        <button id="ext-stop" class="icon-btn" title="Stop" aria-label="Stop"><i class="fa-solid fa-stop"></i></button>
        <button id="ext-next" class="icon-btn" title="Next" aria-label="Next"><i class="fa-solid fa-forward-step"></i></button>
      </div>
      <div id="ext-title"></div>
      <div id="ext-status"></div>
    </div>
    <div id="ext-seek-row">
      <input id="ext-seek" type="range" min="0" max="100" step="0.1" value="0" />
      <div id="ext-time">00:00 / 00:00</div>
    </div>
  </div>
  </nav>

  <main class="container" role="main" style="padding:1rem">
    <h4 style="font-size:1.4rem;">Audio List</h4>

    <?php
// make sure you included DB connection earlier:
// require 'db.php';

// fetch audios
$stmt = $pdo->query("SELECT * FROM audios ORDER BY audio_order ASC, id ASC");
$audios = $stmt->fetchAll();

// prepare transcript statement once
$transStmt = $pdo->prepare("SELECT * FROM transcripts WHERE audio_id = ? ORDER BY transcript_order ASC, id ASC");
?>

<!-- audio list -->
<div id="audio-list">
<?php foreach ($audios as $i => $a): ?>
  <div class="card white lighten-5 z-depth-2">
    <div class="card-content">
      <span class="card-title" style="font-size: 1.3em; display:flex; align-items:center; gap:8px;">
        <span><?= htmlspecialchars($a['title'] ?? 'Audio '.$a['id']); ?></span>
      </span>

      <div class="audio-row">
        <div class="badge-col">
          <span class="card-badge sticky"><?= ($i+1) ?></span>
        </div>
        <audio class="audio-player" controls preload="metadata" style="width:100%;" <?= ($i===0 && !empty($_SESSION['access_log_id']) ? 'autoplay muted' : '') ?> playsinline>
        <?php
          $filename = htmlspecialchars($a['filename']);
          $base = pathinfo($filename, PATHINFO_FILENAME);
        ?>
        <source src="uploads/<?= $filename ?>" type="audio/mp4">
        <source src="uploads/<?= $base ?>.mp3" type="audio/mpeg">
        Your browser does not support audio playback.
        </audio>
      </div>

      <!-- Single transcript -->
      <p style="font-size:1.4em;margin-top:1em;">
        <?= nl2br(htmlspecialchars($a['transcript'] ?? '')); ?>
      </p>
    </div>
  </div>
<?php endforeach; ?>
</div>

  </main>

  <!-- Materialize & JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
  <script>
  // main.js behavior embedded (auto-next)
  document.addEventListener('DOMContentLoaded', function(){
    // Access overlay handlers
    const overlay = document.getElementById('access-overlay');
    const nameInput = document.getElementById('access-name');
    const enterBtn = document.getElementById('access-enter');
    const err = document.getElementById('access-error');

    async function startAccess() {
      const name = (nameInput?.value || '').trim();
      if (!name) { if (err) err.style.display = 'block'; return; }
      if (err) err.style.display = 'none';
      try {
        const res = await fetch('access_start.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name })
        });
        const data = await res.json();
        if (data.success) {
          overlay?.classList.remove('visible');
        } else {
          if (err) { err.textContent = data.message || 'Unable to start access'; err.style.display = 'block'; }
        }
      } catch (e) {
        if (err) { err.textContent = 'Network error'; err.style.display = 'block'; }
      }
    }
    enterBtn?.addEventListener('click', startAccess);
    nameInput?.addEventListener('keydown', (ev)=>{ if (ev.key === 'Enter') startAccess(); });

    // End access on unload using Beacon
    function endAccess() {
      try {
        const blob = new Blob([JSON.stringify({ goodbye: true })], { type: 'application/json' });
        navigator.sendBeacon && navigator.sendBeacon('access_end.php', blob);
      } catch (e) {}
    }
    window.addEventListener('pagehide', endAccess);
    window.addEventListener('beforeunload', endAccess);
    const players = Array.from(document.querySelectorAll('.audio-player'));

    // Play next when current audio ends
    players.forEach((player, idx) => {
      player.addEventListener('ended', () => {
        const next = players[idx + 1];
        if(next){
          // give a slight delay to allow user to notice end
          next.play().catch(()=>{ /* autoplay blocked — user gesture required */ });
          // Scroll next into view for better UX
          next.scrollIntoView({behavior:'smooth', block:'center'});
        } else {
          // optionally loop or stop; here we stop.
        }
      });
    });

    // Large touch targets: increase controls size on mobile if needed (CSS does most of this)
  });
  </script>
  <div class="preloader-wrapper small active" id="loading" style="display:none;">
  <div class="spinner-layer spinner-blue-only">
    <div class="gap-patch"><div class="circle"></div></div>
    <div class="circle-clipper right"><div class="circle"></div></div>
  </div>
</div>

  <script>
(() => {
  const loading = document.getElementById('loading');
  const cards = Array.from(document.querySelectorAll('#audio-list .card'));
  const players = Array.from(document.querySelectorAll('.audio-player'));
  let userInteracted = false; // enable auto-switch only after a user-initiated play
  let currentAudio = null;
  const totalCards = cards.length;
  let autoplayBlocked = false;
  const overlayEl = document.getElementById('access-overlay');
  const accessGranted = <?= !empty($_SESSION['access_log_id']) ? 'true' : 'false' ?>;

  // External control refs
  const extBar = document.getElementById('ext-controls');
  const extBtn = document.getElementById('ext-toggle');
  const extTitle = document.getElementById('ext-title');
  const extStatus = document.getElementById('ext-status');
  const extIcon = document.getElementById('ext-icon');
  const extSeek = document.getElementById('ext-seek');
  const extTime = document.getElementById('ext-time');
  const extPrev = document.getElementById('ext-prev');
  const extNext = document.getElementById('ext-next');
  const extStop = document.getElementById('ext-stop');
  const extUnmute = document.getElementById('ext-unmute');
  const globalStatus = document.getElementById('global-audio-status');

  // Helpers
  const getCardFromAudio = (audio) => audio.closest('.card');
  const getAudioFromCard = (card) => card ? card.querySelector('.audio-player') : null;
  const setPlayingCard = (card) => {
    cards.forEach(c => c.classList.toggle('playing', c === card));
  };
  const setPlayingAudio = (audioEl) => {
    players.forEach(p => p.classList.toggle('playing', p === audioEl));
  };

  const getTitleFromAudio = (audio) => {
    const card = getCardFromAudio(audio);
    const titleEl = card ? card.querySelector('.card-title') : null;
    return titleEl ? titleEl.textContent.trim() : 'Audio';
  };

  const refreshExternalControls = () => {
    const isPlaying = currentAudio && !currentAudio.paused;
    // Update icon state
    if (extIcon) extIcon.className = 'fa-solid ' + (isPlaying ? 'fa-pause' : 'fa-play');
    if (extBtn) extBtn.classList.toggle('active', !!isPlaying);
    if (extBar) extBar.classList.toggle('playing', !!isPlaying);
    if (extTitle) {
      extTitle.textContent = currentAudio ? getTitleFromAudio(currentAudio) : '';
    }
    if (extStatus) {
      if (!currentAudio) {
        extStatus.textContent = `0 of ${totalCards}`;
      } else {
        const idx = players.indexOf(currentAudio);
        const n = idx >= 0 ? idx + 1 : 0;
        extStatus.textContent = `${isPlaying ? 'Playing' : 'Paused'} ${n} of ${totalCards}`;
      }
    }
    // Global status visibility just above controls
    if (globalStatus) {
      if (autoplayBlocked) {
        globalStatus.textContent = currentAudio && !currentAudio.muted ? 'Tap Play to start audio' : 'Tap Unmute to hear audio';
        globalStatus.style.display = 'block';
      } else if (!currentAudio) {
        globalStatus.textContent = 'No audio selected';
        globalStatus.style.display = 'block';
      } else {
        globalStatus.style.display = 'none';
      }
    }
    // Unmute button visibility
    if (extUnmute) {
      const shouldShowUnmute = autoplayBlocked || (currentAudio && currentAudio.muted);
      extUnmute.style.display = shouldShowUnmute ? 'inline-flex' : 'none';
    }
    // Progress/time
    const dur = (currentAudio && isFinite(currentAudio.duration)) ? currentAudio.duration : 0;
    const cur = (currentAudio && isFinite(currentAudio.currentTime)) ? currentAudio.currentTime : 0;
    if (extSeek) {
      extSeek.max = dur || 0;
      extSeek.value = cur || 0;
    }
    if (extTime) {
      const fmt = (s)=>{
        s = Math.max(0, Math.floor(s||0));
        const m = Math.floor(s/60), ss = s%60; return `${String(m).padStart(2,'0')}:${String(ss).padStart(2,'0')}`;
      };
      extTime.textContent = `${fmt(cur)} / ${fmt(dur)}`;
    }
  };

  // Determine the top-most visible card in viewport
  const getTopVisibleCard = () => {
    let topCandidate = null;
    let minTop = Number.POSITIVE_INFINITY;
    let fallback = null;
    let maxTop = Number.NEGATIVE_INFINITY;
    for (const c of cards) {
      const r = c.getBoundingClientRect();
      // store fallback: the one closest to top even if above viewport
      if (r.top > maxTop) { maxTop = r.top; fallback = c; }
      // choose the smallest non-negative top (visible or at top)
      if (r.top >= 0 && r.top < minTop) { minTop = r.top; topCandidate = c; }
    }
    return topCandidate || fallback || cards[0] || null;
  };

  // Loading indicator
  players.forEach(audio => {
    audio.addEventListener('waiting', () => loading && (loading.style.display = 'block'));
    audio.addEventListener('playing', () => loading && (loading.style.display = 'none'));
    audio.addEventListener('timeupdate', () => { if (audio === currentAudio) refreshExternalControls(); });
    audio.addEventListener('loadedmetadata', () => { if (audio === currentAudio) refreshExternalControls(); });
  });

  // Ensure only one plays at a time and mark sticky card
  players.forEach((player, i) => {
    player.addEventListener('play', () => {
      userInteracted = true;
      currentAudio = player;
      // Pause all others
      players.forEach((p, j) => { if (j !== i && !p.paused) p.pause(); });
      // Mark playing card as sticky
      setPlayingCard(getCardFromAudio(player));
      setPlayingAudio(player);
      refreshExternalControls();
    });

    player.addEventListener('pause', () => {
      // If paused manually and it's the current one, remove sticky class
      if (currentAudio === player) {
        setPlayingCard(null);
        setPlayingAudio(null);
      }
      refreshExternalControls();
    });

    player.addEventListener('ended', () => {
      // Auto-advance to next
      const next = players[i + 1];
      if (next) {
        // Scroll next into view, then play
        const nextCard = getCardFromAudio(next);
        if (nextCard && nextCard.scrollIntoView) {
          nextCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        setTimeout(() => { next.play().catch(() => {}); }, 250);
        setPlayingCard(getCardFromAudio(next));
        setPlayingAudio(next);
        currentAudio = next;
      } else {
        setPlayingCard(null);
        setPlayingAudio(null);
        currentAudio = null;
      }
      refreshExternalControls();
    });
  });

  // External toggle behavior
  if (extBtn) {
    extBtn.addEventListener('click', () => {
      // Block play while overlay is visible
      if (overlayEl && overlayEl.classList.contains('visible')) {
        const nameInput = document.getElementById('access-name');
        nameInput && nameInput.focus();
        return;
      }
      // Always target the top visible card's audio on Play
      const topCard = getTopVisibleCard();
      const targetAudio = getAudioFromCard(topCard);
      if (targetAudio) {
        if (currentAudio && currentAudio !== targetAudio && !currentAudio.paused) {
          currentAudio.pause();
        }
        currentAudio = targetAudio;
        if (currentAudio.paused) currentAudio.play().catch(() => {});
        else currentAudio.pause(); // if playing, toggle to pause
        setPlayingCard(topCard);
        setPlayingAudio(currentAudio);
      }
      refreshExternalControls();
    });
  }

  // Stop all
  if (extStop) {
    extStop.addEventListener('click', () => {
      players.forEach(p => { p.pause(); p.currentTime = 0; });
      setPlayingCard(null);
      setPlayingAudio(null);
      currentAudio = null;
      refreshExternalControls();
    });
  }

  // Go to previous
  if (extPrev) {
    extPrev.addEventListener('click', () => {
      let idx = currentAudio ? players.indexOf(currentAudio) : -1;
      if (idx <= 0) idx = 0; else idx = idx - 1;
      const prev = players[idx];
      if (prev) {
        const card = getCardFromAudio(prev);
        if (card && card.scrollIntoView) card.scrollIntoView({ behavior:'smooth', block:'center' });
        if (currentAudio && currentAudio !== prev) currentAudio.pause();
        currentAudio = prev;
        currentAudio.play().catch(()=>{});
        setPlayingCard(card);
        setPlayingAudio(currentAudio);
        refreshExternalControls();
      }
    });
  }

  // Go to next
  if (extNext) {
    extNext.addEventListener('click', () => {
      let idx = currentAudio ? players.indexOf(currentAudio) : -1;
      if (idx < players.length - 1) idx = idx + 1; else idx = players.length - 1;
      const next = players[idx];
      if (next) {
        const card = getCardFromAudio(next);
        if (card && card.scrollIntoView) card.scrollIntoView({ behavior:'smooth', block:'center' });
        if (currentAudio && currentAudio !== next) currentAudio.pause();
        currentAudio = next;
        currentAudio.play().catch(()=>{});
        setPlayingCard(card);
        setPlayingAudio(currentAudio);
        refreshExternalControls();
      }
    });
  }

  // Seeking behavior
  if (extSeek) {
    let seeking = false;
    extSeek.addEventListener('input', () => {
      seeking = true;
      if (currentAudio && isFinite(currentAudio.duration)) {
        currentAudio.currentTime = Number(extSeek.value || 0);
        refreshExternalControls();
      }
    });
    extSeek.addEventListener('change', () => {
      seeking = false;
    });
  }

  // Try to autoplay the first audio on load (unmuted first, fallback to muted)
  (function tryAutoplayFirst() {
    const first = players[0];
    if (!first) return;
    // Do not attempt autoplay unless access is granted and overlay is hidden
    if (!accessGranted || (overlayEl && overlayEl.classList.contains('visible'))) return;
    try { first.muted = false; } catch(e) {}
    first.play().then(() => {
      currentAudio = first;
      setPlayingCard(getCardFromAudio(first));
      setPlayingAudio(first);
      refreshExternalControls();
    }).catch(() => {
      // Retry with muted autoplay
      try { first.muted = true; } catch(e) {}
      first.play().then(() => {
        currentAudio = first;
        autoplayBlocked = true; // sound is blocked; show prompt/unmute option
        setPlayingCard(getCardFromAudio(first));
        setPlayingAudio(first);
        refreshExternalControls();
      }).catch(() => {
        // Still blocked entirely
        autoplayBlocked = true;
        refreshExternalControls();
      });
    });
  })();

  // On first user interaction, unmute and ensure playback continues
  const enableSoundOnce = () => {
    const first = players[0];
    if (first) {
      try { first.muted = false; } catch(e) {}
      if (first.paused) { first.play().catch(()=>{}); }
    }
    autoplayBlocked = false;
    refreshExternalControls();
    window.removeEventListener('click', enableSoundOnce, { capture: true });
    window.removeEventListener('touchstart', enableSoundOnce, { capture: true });
  };
  window.addEventListener('click', enableSoundOnce, { capture: true, once: true });
  window.addEventListener('touchstart', enableSoundOnce, { capture: true, once: true });
  window.addEventListener('wheel', enableSoundOnce, { capture: true, once: true, passive: true });
  window.addEventListener('scroll', enableSoundOnce, { capture: true, once: true, passive: true });
  window.addEventListener('mousemove', enableSoundOnce, { capture: true, once: true });
  window.addEventListener('keydown', enableSoundOnce, { capture: true, once: true });

  // Explicit Unmute button
  if (extUnmute) {
    extUnmute.addEventListener('click', () => {
      const target = currentAudio || players[0];
      if (target) {
        try { target.muted = false; } catch(e) {}
        if (target.paused) target.play().catch(()=>{});
      }
      autoplayBlocked = false;
      refreshExternalControls();
    });
  }

  // Initialize bar state on load
  refreshExternalControls();

  // Removed scroll-based auto switch per request. Only manual play changes current.
})();
</script>

<script>
// Fallback pinning for card number badge to ensure it sticks until card end (cross-browser)
(function() {
  const rows = Array.from(document.querySelectorAll('.audio-row'));
  const badges = rows.map(r => r.querySelector('.card-badge'));
  const TOP = 25; // same as CSS top

  function update() {
    rows.forEach((row, i) => {
      const badge = badges[i];
      if (!badge) return;
      // Reset before measure
      badge.classList.remove('fixed');
      badge.style.left = '';

      const card = row.closest('.card');
      if (!card) return;
      const cr = card.getBoundingClientRect();
      const br = badge.getBoundingClientRect();

      const withinTop = cr.top <= TOP;
      const withinBottom = cr.bottom >= (TOP + br.height + 8);
      if (withinTop && withinBottom) {
        const col = row.querySelector('.badge-col');
        const lr = (col ? col.getBoundingClientRect() : br);
        badge.classList.add('fixed');
        badge.style.left = `${lr.left + (lr.width - br.width) / 2}px`;
      }
    });
  }

  ['scroll', 'resize'].forEach(evt => window.addEventListener(evt, update, { passive: true }));
  update();
})();
</script>

</body>
</html>
