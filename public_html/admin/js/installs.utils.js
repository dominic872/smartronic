// Utilities shared across installs modules
// These are global bindings so that other scripts can use them.

function getMonday(date) {
  const d = new Date(date);
  const day = d.getDay();
  const diff = d.getDate() - day + (day === 0 ? -6 : 1);
  return new Date(d.setDate(diff));
}
window.getMonday = getMonday;

function formatDate(d) {
  // Convert to "local ISO" so we don't lose hours to UTC
  const local = new Date(d.getTime() - d.getTimezoneOffset() * 60000);
  return local.toISOString().split("T")[0]; // YYYY-MM-DD (local)
}
window.formatDate = formatDate;

// Display date like "25 Aug" from YYYY-MM-DD
function formatDateDisplay(iso) {
  const [y, m, day] = String(iso).split('-').map(Number);
  const dt = new Date(y, (m || 1) - 1, day || 1);
  const d = dt.getDate();
  const mon = dt.toLocaleString('default', { month: 'short' });
  return `${d} ${mon}`;
}
window.formatDateDisplay = formatDateDisplay;

function debounce(fn, wait = 250) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
}

// Turn URLs and phone numbers into clickable links
function linkify(text = "") {
  if (!text) return "";
  // URLs
  const urlRegex = /(\bhttps?:\/\/[^\s<]+)/gi;
  // Basic phone (e.g., 9884310000, +91-98843-10000, 09884310000)
  const phoneRegex = /(?:\+?\d[\d\s\-]{8,}\d)/g;

  let html = String(text).replace(urlRegex, (m) => `<a href="${m}" target="_blank" rel="noopener">${m}</a>`);
  html = html.replace(phoneRegex, (m) => {
    const digits = m.replace(/[^\d+]/g, "");
    return `<a href="tel:${digits}">${m}</a>`;
  });
  return html;
}

// Small utility to avoid breaking HTML if names contain special chars
function escapeHtml(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, m => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]
  ));
}
window.escapeHtml = escapeHtml;
