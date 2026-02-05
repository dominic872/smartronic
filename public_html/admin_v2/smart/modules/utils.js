/**
 * Utilities Module - Common utility functions
 */
(function(window) {
  function formatINR(n) {
    if (n === null || n === undefined || isNaN(n)) return '—';
    return '₹' + Math.round(n).toLocaleString('en-IN');
  }

  function parsePrice(v) {
    if (v === null || v === undefined) return null;
    const num = parseFloat((v + '').replace(/[^\d\.]/g, ''));
    return isNaN(num) ? null : num;
  }

  function sortByPriceLowToHigh(items) {
    if (!Array.isArray(items)) return items;
    return items.sort((a, b) => {
      const priceA = parsePrice(a.value) || 0;
      const priceB = parsePrice(b.value) || 0;
      return priceA - priceB;
    });
  }

  function sortHddByPriceLowToHigh(brandData) {
    if (!brandData || !Array.isArray(brandData)) return brandData;
    return brandData.sort((a, b) => {
      const priceA = parsePrice(a.value) || 0;
      const priceB = parsePrice(b.value) || 0;
      return priceA - priceB;
    });
  }

  function safeGetTypeRoot() {
    return (typeof data === "object" && data && data.Type) ? data.Type : {};
  }

  function updateCameraButtonText(btn, qty) {
    const originalText = btn.dataset.originalLabel || btn.textContent.replace(/\s*\(\d+\)$/, '');
    if (!btn.dataset.originalLabel) btn.dataset.originalLabel = originalText;
    btn.textContent = `${originalText} (${qty})`;
  }

  // Export functions
  window.SmartronicUtils = {
    formatINR,
    parsePrice,
    sortByPriceLowToHigh,
    sortHddByPriceLowToHigh,
    safeGetTypeRoot,
    updateCameraButtonText
  };
})(window);
