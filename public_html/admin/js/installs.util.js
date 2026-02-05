'use strict';
(function(w){
  // Utilities (guarded)
  w.getMonday = w.getMonday || function getMonday(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(d.setDate(diff));
  };

  w.formatDate = w.formatDate || function formatDate(d) {
    const local = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
    return local.toISOString().split('T')[0];
  };

  w.formatDateDisplay = w.formatDateDisplay || function formatDateDisplay(iso) {
    const [y, m, d] = iso.split('-').map(Number);
    const dt = new Date(y, (m || 1) - 1, d || 1);
    const day = dt.getDate();
    const mon = dt.toLocaleString('default', { month: 'short' });
    return `${day} ${mon}`;
  };

  w.debounce = w.debounce || function debounce(fn, wait=250){
    let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
  };

  w.linkify = w.linkify || function linkify(text = '') {
    if (!text) return '';
    const urlRegex = /(\bhttps?:\/\/[^\s<]+)/gi;
    const phoneRegex = /(?:\+?\d[\d\s\-]{8,}\d)/g;
    let html = text.replace(urlRegex, (m) => `<a href="${m}" target="_blank" rel="noopener">${m}</a>`);
    html = html.replace(phoneRegex, (m) => {
      const digits = m.replace(/[^\d+]/g, '');
      return `<a href="tel:${digits}">${m}</a>`;
    });
    return html;
  };

  w.escapeHtml = w.escapeHtml || function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, m => (
      { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;' }[m]
    ));
  };
})(window);
