/**
 * Core Module - Session Management and Data Persistence
 */
(function(window) {
  const SESSION_KEY = 'smartronic_dvr_selections';

  function saveSessionData(category, brand, mp, cameraCount, selectedItems) {
    const contextKey = `${category}::${brand}::${mp}`;
    
    const sessionData = {
      category,
      brand,
      mp,
      cameraCount,
      selectedItems: selectedItems.map(item => {
        const baseItem = {
          id: item.id,
          label: item.label,
          price: item.price,
          qty: item.qty,
          meta: { ...item.meta }
        };

        if (item.meta?.component === "Camera" && item.meta.raw) {
          baseItem.meta.category = category;
          baseItem.meta.brand = brand;
          baseItem.meta.mp = mp;
          baseItem.meta.cameraLabel = item.meta.raw.label;
          baseItem.meta.cameraValue = item.meta.raw.value;
          baseItem.meta.raw = item.meta.raw;
        } else if (item.meta?.component === "Recorder" && item.meta.raw) {
          baseItem.meta.category = category;
          baseItem.meta.brand = brand;
          baseItem.meta.mp = mp;
          baseItem.meta.recorderLabel = item.meta.raw.label;
          baseItem.meta.recorderValue = item.meta.raw.value;
          baseItem.meta.channel = item.meta.channel;
          baseItem.meta.raw = item.meta.raw;
        } else if (item.meta?.component === "HDD") {
          baseItem.meta.brand = item.meta.brand;
          baseItem.meta.capacity = item.meta.capacity;
        } else if (item.meta?.type === "accessory") {
          baseItem.meta.type = "accessory";
        }

        return baseItem;
      }),
      timestamp: Date.now()
    };

    let allSessionData = {};
    try {
      const existing = localStorage.getItem(SESSION_KEY);
      if (existing) allSessionData = JSON.parse(existing);
    } catch (e) {
      console.warn('Error loading existing session data:', e);
    }

    allSessionData[contextKey] = sessionData;
    console.log('Saving session data for', contextKey, sessionData);
    localStorage.setItem(SESSION_KEY, JSON.stringify(allSessionData));
  }

  function clearSessionData() {
    localStorage.removeItem(SESSION_KEY);
  }

  function loadSessionData(category, brand, mp) {
    try {
      const saved = localStorage.getItem(SESSION_KEY);
      if (saved) {
        const allData = JSON.parse(saved);
        const contextKey = `${category}::${brand}::${mp}`;
        const data = allData[contextKey];
        if (data) {
          if (Date.now() - data.timestamp < 60 * 60 * 1000) {
            console.log('Loaded session data for', contextKey, data);
            return data;
          } else {
            delete allData[contextKey];
            localStorage.setItem(SESSION_KEY, JSON.stringify(allData));
          }
        }
      }
    } catch (e) {
      console.warn('Failed to load session data:', e);
    }
    return null;
  }

  // Export functions
  window.SmartronicCore = {
    saveSessionData,
    clearSessionData,
    loadSessionData
  };
})(window);
