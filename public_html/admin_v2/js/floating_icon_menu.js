(function () {
  if (window.SmartFloatingMenu) return;

  const STYLE_ID = 'smart-floating-menu-style';
  const SIDE_KEY = 'smart_floating_side';

  function ensureStyle() {
    if (document.getElementById(STYLE_ID)) return;
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
      .smart-floating-link-btn{
        position: fixed;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        color: #fff;
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,.2);
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        z-index: 10000;
        transition: transform .2s, background-color .2s, opacity .2s;
        text-decoration: none;
      }
      .smart-floating-link-btn:hover{ transform: scale(1.06); }
      .smart-floating-link-btn:active{
        box-shadow: 0 2px 4px rgba(0,0,0,.2) !important;
        outline: none !important;
      }
      .smart-floating-menu-btn{
        position: fixed;
        top: 100px;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        color: white;
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #111827;
        z-index: 10001;
        transition: transform .2s, background-color .2s;
        cursor: pointer;
      }
      .smart-floating-menu-btn:hover{
        transform: scale(1.06);
        background-color: #0b1220;
      }
      .smart-floating-side-btn{
        position: fixed;
        top: 154px;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        color: white;
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #374151;
        z-index: 10001;
        transition: transform .2s, background-color .2s;
        cursor: pointer;
      }
      .smart-floating-side-btn:hover{
        transform: scale(1.06);
        background-color: #1f2937;
      }
      body.smart-floating-side-right .smart-floating-link-btn,
      body.smart-floating-side-right .smart-floating-menu-btn,
      body.smart-floating-side-right .smart-floating-side-btn { right: 20px; left: auto; }
      body.smart-floating-side-left .smart-floating-link-btn,
      body.smart-floating-side-left .smart-floating-menu-btn,
      body.smart-floating-side-left .smart-floating-side-btn { left: 20px; right: auto; }
      body.smart-floating-hidden .smart-floating-link-btn{
        opacity: 0;
        pointer-events: none;
        transform: scale(.95);
      }
      body.smart-floating-hidden.smart-floating-side-right .smart-floating-link-btn{
        transform: translateX(16px) scale(.95);
      }
      body.smart-floating-hidden.smart-floating-side-left .smart-floating-link-btn{
        transform: translateX(-16px) scale(.95);
      }
      .smart-floating-modal{
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 10030;
        display: none;
        align-items: center;
        justify-content: center;
        width: 100vw;
        height: 100vh;
        overflow: hidden;
      }
      .smart-floating-modal.is-open{ display: flex; }
      .smart-floating-modal-card{
        position: relative;
        width: 100vw;
        height: 100vh;
        max-width: 100%;
        max-height: 100%;
        background: #fff;
        border-radius: 0;
        overflow: hidden;
        box-shadow: 0 0 24px rgba(0, 0, 0, 0.3);
      }
      .smart-floating-modal-close{
        position: fixed;
        top: 12px;
        right: 12px;
        z-index: 20000;
        background: rgba(255,255,255,0.9);
        border: none;
        border-radius: 50%;
        width: 42px;
        height: 42px;
        font-size: 18px;
        cursor: pointer;
        transition: 0.2s;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
      }
      .smart-floating-modal-close:hover{
        background: #ff4444;
        color: #fff;
      }
.smart-floating-modal-opennew {
    position: absolute;
    top: 12px;
    right: 70px;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: none;
    background: white;
    color: #2563eb;
    font-size: 14px;
    cursor: pointer;
    z-index: 1001;
    transition: 0.2s;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
}
      .smart-floating-modal-opennew:hover{
        background: #1d4ed8;
        color: #fff;
      }
      .smart-floating-modal-frame{
        width: 100%;
        height: 100%;
        border: 0;
        display: block;
        background: #fff;
      }
    `;
    document.head.appendChild(style);
  }

  // Store the last raw URL for the "Open in new window" button
  let lastModalUrl = '';

  function ensureModal() {
    let modal = document.getElementById('smartFloatingModal');
    if (modal) return modal;
    modal = document.createElement('div');
    modal.id = 'smartFloatingModal';
    modal.className = 'smart-floating-modal';
    modal.innerHTML = `
      <div class="smart-floating-modal-card" role="dialog" aria-modal="true" aria-label="Quick Link Window">
       <button id="smartFloatingModalOpenNew" class="smart-floating-modal-opennew" type="button" aria-label="Open in new window">
         <i class="fa fa-external-link"></i>
       </button>
        <button id="smartFloatingModalClose" class="smart-floating-modal-close" type="button" aria-label="Close">
          <i class="fa fa-times"></i>
        </button>
        <iframe id="smartFloatingModalFrame" class="smart-floating-modal-frame" loading="eager"></iframe>
      </div>
    `;
    document.body.appendChild(modal);
    const closeBtn = modal.querySelector('#smartFloatingModalClose');
    const openNewBtn = modal.querySelector('#smartFloatingModalOpenNew');
    const closeModal = () => {
      modal.classList.remove('is-open');
      const frame = modal.querySelector('#smartFloatingModalFrame');
      if (frame) frame.src = '';
    };
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (openNewBtn) {
      openNewBtn.addEventListener('click', () => {
        if (lastModalUrl) {
          window.open(lastModalUrl, '_blank');
        }
      });
    }
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeModal();
    });
    return modal;
  }

  function withModalParams(rawUrl) {
    if (!rawUrl) return '';
    try {
      const u = new URL(rawUrl, window.location.origin);
      u.searchParams.set('floating_menu', '0');
      u.searchParams.set('nav_modal', '1');
      return u.toString();
    } catch (e) {
      return rawUrl;
    }
  }

  function openInModal(url) {
    if (!url) return;
    const modalUrl = withModalParams(url);
    // Store the original URL for the "Open in new window" button
    lastModalUrl = url;
    if (typeof window.openQuoteModal === 'function') {
      window.openQuoteModal(modalUrl);
      return;
    }
    const modal = ensureModal();
    const frame = modal.querySelector('#smartFloatingModalFrame');
    if (frame) frame.src = modalUrl;
    modal.classList.add('is-open');
  }

  function mount(config) {
    const cfg = config || {};
    const links = Array.isArray(cfg.links) ? cfg.links : [];
    if (!links.length) return;
    ensureStyle();

    const existing = document.querySelectorAll('.smart-floating-link-btn, .smart-floating-menu-btn, .smart-floating-side-btn');
    existing.forEach(el => el.remove());

    let hidden = false;
    let side = localStorage.getItem(SIDE_KEY) === 'left' ? 'left' : 'right';
    const baseBottom = typeof cfg.baseBottom === 'number' ? cfg.baseBottom : 140;
    const step = typeof cfg.step === 'number' ? cfg.step : 60;
    const allowSideSwitch = cfg.allowSideSwitch !== false;

    function applySideClass() {
      document.body.classList.remove('smart-floating-side-left', 'smart-floating-side-right');
      document.body.classList.add(side === 'left' ? 'smart-floating-side-left' : 'smart-floating-side-right');
    }
    applySideClass();

    links.forEach((link, idx) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'smart-floating-link-btn';
      btn.setAttribute('aria-label', link.label || 'Quick Link');
      btn.title = link.label || 'Quick Link';
      if (typeof link.top === 'number') {
        btn.style.top = `${link.top}px`;
        btn.style.bottom = 'auto';
      } else {
        btn.style.bottom = `${baseBottom + (idx * step)}px`;
        btn.style.top = 'auto';
      }
      btn.style.backgroundColor = link.color || '#2563eb';
      btn.innerHTML = `<i class="${link.icon || 'fa-solid fa-link'}"></i>`;
      btn.addEventListener('click', () => {
        if (typeof link.action === 'function') {
          link.action();
          return;
        }
        const url = typeof link.url === 'function' ? link.url() : link.url;
        openInModal(url);
      });
      document.body.appendChild(btn);
    });

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'smart-floating-menu-btn';
    toggle.setAttribute('aria-label', 'Toggle floating buttons');
    toggle.innerHTML = '<i class="fa-solid fa-xmark"></i>';
    toggle.addEventListener('click', () => {
      hidden = !hidden;
      document.body.classList.toggle('smart-floating-hidden', hidden);
      toggle.innerHTML = hidden ? '<i class="fa-solid fa-bars"></i>' : '<i class="fa-solid fa-xmark"></i>';
    });
    document.body.appendChild(toggle);

    if (allowSideSwitch) {
      const sideBtn = document.createElement('button');
      sideBtn.type = 'button';
      sideBtn.className = 'smart-floating-side-btn';
      sideBtn.setAttribute('aria-label', 'Switch floating side');
      sideBtn.title = 'Switch left or right';
      sideBtn.innerHTML = '<i class="fa-solid fa-left-right"></i>';
      sideBtn.addEventListener('click', () => {
        side = side === 'left' ? 'right' : 'left';
        localStorage.setItem(SIDE_KEY, side);
        applySideClass();
      });
      document.body.appendChild(sideBtn);
    }
  }

  window.SmartFloatingMenu = { mount };
})();
