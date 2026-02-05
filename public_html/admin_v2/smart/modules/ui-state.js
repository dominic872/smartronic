/**
 * UI State Module - Manages current selection state (category, brand, MP)
 */
(function(window) {
  let currentCategory = '';
  let currentBrand = '';
  let currentMP = '';
  let selectedItems = [];

  function getCurrentState() {
    return { currentCategory, currentBrand, currentMP };
  }

  function setCurrentCategory(category) {
    currentCategory = category;
  }

  function setCurrentBrand(brand) {
    currentBrand = brand;
  }

  function setCurrentMP(mp) {
    currentMP = mp;
  }

  function getSelectedItems() {
    return selectedItems;
  }

  function setSelectedItems(items) {
    selectedItems = items;
  }

  function addSelectedItem(item) {
    selectedItems.push(item);
  }

  function removeSelectedItem(id) {
    selectedItems = selectedItems.filter(item => item.id !== id);
  }

  function updateSelectedItem(id, updates) {
    const item = selectedItems.find(i => i.id === id);
    if (item) {
      Object.assign(item, updates);
    }
  }

  function clearSelectedItems() {
    selectedItems = [];
  }

  function filterItemsByContext(items) {
    return items.filter(item => {
      if (item.meta?.component === 'HDD' || item.meta?.type === 'accessory') {
        return true;
      }
      if (item.meta?.component === 'Camera' || item.meta?.component === 'Recorder') {
        return item.meta?.category === currentCategory && 
               item.meta?.brand === currentBrand && 
               item.meta?.mp === currentMP;
      }
      return true;
    });
  }

  // Export functions
  window.SmartronicUIState = {
    getCurrentState,
    setCurrentCategory,
    setCurrentBrand,
    setCurrentMP,
    getSelectedItems,
    setSelectedItems,
    addSelectedItem,
    removeSelectedItem,
    updateSelectedItem,
    clearSelectedItems,
    filterItemsByContext
  };
})(window);
