'use strict';

(function (w) {
  const GST_RATE = 0.18;
  let pricingDataPromise = null;

  function parsePrice(value) {
    const n = Number.parseFloat(String(value ?? '').replace(/[^0-9.-]/g, ''));
    return Number.isFinite(n) ? n : 0;
  }

  function norm(value) {
    return String(value ?? '').trim().toUpperCase().replace(/\s+/g, '');
  }

  function normBrand(value) {
    const text = String(value || '').trim().toUpperCase();
    if (text === 'HIKVISION') return 'HIKVISION';
    if (text === 'CPPLUS' || text === 'CP PLUS') return 'CP PLUS';
    if (text === 'PRAMA') return 'PRAMA';
    if (text === 'SECUREYE') return 'SECUREYE';
    return text || 'PRAMA';
  }

  function normResolution(value) {
    const text = norm(value);
    const match = text.match(/(\d+)\s*(MP|K)/i) || text.match(/^(\d+)/);
    if (!match) return text || '2MP';
    return `${match[1]}${match[2] ? match[2].toUpperCase() : 'MP'}`;
  }

  function normSystemType(value) {
    const text = norm(value);
    if (text.includes('NVR')) return 'NVR';
    if (text.includes('WIFI')) return 'WIFI';
    if (text.includes('WIRELESS')) return 'Wireless';
    return 'DVR';
  }

  function normCamType(value) {
    const text = String(value || '').trim().toLowerCase();
    if (text.includes('hybrid') || text.includes('hybid')) return 'hybrid';
    if (text.includes('full') || text.includes('colour') || text.includes('color')) return 'full';
    return 'normal';
  }

  function getChannel(cameraCount) {
    const count = Number.parseInt(cameraCount || 0, 10) || 0;
    if (count <= 4) return 4;
    if (count <= 8) return 8;
    if (count <= 16) return 16;
    return 32;
  }

  function firstPricedOption(options) {
    const list = Array.isArray(options) ? options : [];
    return list.find(opt => parsePrice(opt && opt.value) > 0) || null;
  }

  function pickCameraOption(options, camType) {
    const list = Array.isArray(options) ? options : [];
    const wanted = normCamType(camType);

    const score = opt => {
      const label = String(opt && opt.label || '').toLowerCase();
      if (wanted === 'hybrid') return label.includes('hybrid') || label.includes('hybid') ? 10 : 0;
      if (wanted === 'full') return label.includes('full') || label.includes('colour') || label.includes('color') ? 10 : 0;
      if (label.includes('hybrid') || label.includes('hybid') || label.includes('full') || label.includes('colour') || label.includes('color')) return 0;
      if (label.includes('mic')) return 9;
      if (label.includes('normal') || label.includes('nv') || label.includes('night')) return 8;
      return 1;
    };

    return list
      .filter(opt => parsePrice(opt && opt.value) > 0)
      .sort((a, b) => score(b) - score(a))[0] || firstPricedOption(list);
  }

  function findBrandNode(data, systemType, brand) {
    const typeNode = data && data.Type && data.Type[systemType];
    if (!typeNode || typeof typeNode !== 'object') return null;
    if (typeNode[brand]) return typeNode[brand];
    const key = Object.keys(typeNode).find(k => normBrand(k) === brand);
    return key ? typeNode[key] : null;
  }

  function pickRecorder(brandNode, systemType, resolution, channel) {
    const channelKey = `${channel}CH`;
    let recorderNode = null;
    if (systemType === 'DVR') {
      recorderNode = brandNode && brandNode[resolution] && brandNode[resolution].Recorder;
    } else {
      recorderNode = brandNode && brandNode.Recorder;
    }
    if (!recorderNode) return null;
    const key = Object.keys(recorderNode).find(k => norm(k) === channelKey) || Object.keys(recorderNode)[0];
    return key ? firstPricedOption(recorderNode[key]) : null;
  }

  function pickCamera(data, brandNode, systemType, brand, resolution, camType) {
    if (!brandNode) return null;

    if (systemType === 'DVR') {
      return pickCameraOption(brandNode[resolution] && brandNode[resolution].Camera, camType);
    }

    if (brandNode.Camera) {
      if (Array.isArray(brandNode.Camera)) return pickCameraOption(brandNode.Camera, camType);
      const resolutionOptions = brandNode.Camera[resolution] || brandNode.Camera[Object.keys(brandNode.Camera)[0]];
      return pickCameraOption(resolutionOptions, camType);
    }

    const wifiCameraNode = data && data.Type && data.Type.WIFI && data.Type.WIFI.Camera;
    const byResolution = wifiCameraNode && (wifiCameraNode[resolution] || wifiCameraNode[Object.keys(wifiCameraNode)[0]]);
    const byBrand = byResolution && (byResolution[brand] || byResolution[Object.keys(byResolution)[0]]);
    return pickCameraOption(byBrand, camType);
  }

  function pickHdd(data, hdd) {
    const wanted = norm(hdd);
    if (!wanted) return { option: null, price: 0 };
    const groups = data && data.HDD ? Object.values(data.HDD).flat() : [];
    const exact = groups.find(opt => norm(opt.capacity) === wanted || norm(opt.label).includes(wanted));
    const preferred = groups.find(opt => norm(opt.capacity) === wanted && String(opt.preferred || '').toLowerCase() === 'true');
    const option = preferred || exact || null;
    return { option, price: parsePrice(option && option.value) };
  }

  function itemPrice(data, key, fallback = 0) {
    const items = data && data.items ? data.items : {};
    const price = parsePrice(items[key] && items[key].value);
    return price || fallback;
  }

  function getCustomerPayableFromDom() {
    const payEl = document.querySelector('#invoice-content #payable, #payable');
    if (!payEl) return NaN;
    const baseAttr = payEl.getAttribute('data-base-value');
    const fromAttr = baseAttr ? parsePrice(baseAttr) : NaN;
    const fromText = parsePrice(payEl.textContent || '');
    return Number.isFinite(fromAttr) && fromAttr > 0 ? fromAttr : fromText;
  }

  async function loadPricingData() {
    if (!pricingDataPromise) {
      pricingDataPromise = fetch('/admin_v2/smart/data.json', { cache: 'no-store' }).then(res => {
        if (!res.ok) throw new Error(`data.json HTTP ${res.status}`);
        return res.json();
      });
    }
    return pricingDataPromise;
  }

  async function calculateInstallPricing(input = {}) {
    const data = await loadPricingData();
    const cams = Number.parseInt(input.cams || input.total || input.quantity || 0, 10)
      || (Number.parseInt(input.bullets || 0, 10) || 0) + (Number.parseInt(input.dome || 0, 10) || 0);
    const systemType = normSystemType(input.type || input.product || 'DVR');
    const brand = normBrand(input.brand || 'PRAMA');
    const resolution = normResolution(input.resolution || input.camera_resolution || '2MP');
    const camType = input.cam_type || input.camType || '';
    const hdd = input.hdd || input.storage || input.hdd_size || '';
    const channel = getChannel(cams);
    const missing = [];

    const brandNode = findBrandNode(data, systemType, brand);
    if (!brandNode) missing.push(`${systemType} ${brand}`);

    const cameraOption = pickCamera(data, brandNode, systemType, brand, resolution, camType);
    const recorderOption = pickRecorder(brandNode, systemType, resolution, channel);
    const hddResult = pickHdd(data, hdd);

    const cameraUnitPrice = parsePrice(cameraOption && cameraOption.value);
    const recorderPrice = parsePrice(recorderOption && recorderOption.value);
    const hddPrice = hddResult.price;

    if (!cameraUnitPrice) missing.push(`${brand} ${resolution} ${camType || 'camera'}`);
    if (!recorderPrice && systemType !== 'WIFI' && systemType !== 'Wireless') missing.push(`${brand} ${channel}CH recorder`);
    if (hdd && !hddPrice) missing.push(`${hdd} HDD`);

    const smpsPrice = cams <= 4 ? itemPrice(data, 'SMPS 4', 500) : itemPrice(data, 'SMPS 8', 800);
    const poePrice = cams <= 4 ? itemPrice(data, 'POE 4', 1500) : cams <= 8 ? itemPrice(data, 'POE 8', 2500) : itemPrice(data, 'POE 16', 4500);
    const bncPrice = itemPrice(data, 'BNC WIRED', 20);
    const dcPrice = itemPrice(data, 'DC', 20);
    const backBoxPrice = itemPrice(data, 'BACK BOX', 20);
    const dlinkPrice = itemPrice(data, 'Dlink', 875);
    const cat6Price = itemPrice(data, 'Dlink Cat 6', dlinkPrice || 975);

    const accessories = systemType === 'NVR'
      ? poePrice + (backBoxPrice * cams) + (cat6Price * Math.ceil(cams / 4))
      : smpsPrice + (bncPrice * cams * 2) + (dcPrice * cams) + (backBoxPrice * cams) + (dlinkPrice * Math.ceil(cams / 4));

    const cameraTotal = cameraUnitPrice * cams;
    const subtotal = cameraTotal + recorderPrice + hddPrice + accessories;
    const gst = subtotal * GST_RATE;
    const materialCost = subtotal + gst;
    const installCharge = cams * 600;
    const profitFlat = parsePrice(data.additionalItems && data.additionalItems.profit && data.additionalItems.profit.value);
    const profitPercentage = parsePrice(data.additionalItems && data.additionalItems.profitPercentage && data.additionalItems.profitPercentage.value);
    const limitedTotal = materialCost + installCharge + profitFlat;
    const finalTotal = limitedTotal + (limitedTotal * (profitPercentage / 100));
    const customerPayable = Number.isFinite(input.customerPayable) ? input.customerPayable : getCustomerPayableFromDom();
    const actualProfit = Number.isFinite(customerPayable) && customerPayable > 0 ? customerPayable - materialCost : NaN;

    return {
      source: 'data.json',
      systemType,
      brand,
      resolution,
      camType: camType || '',
      cams,
      channel,
      cameraLabel: cameraOption && cameraOption.label || '',
      cameraUnitPrice,
      cameraTotal,
      recorderLabel: recorderOption && recorderOption.label || '',
      recorderPrice,
      hddLabel: hddResult.option && hddResult.option.label || '',
      hddPrice,
      accessories,
      subtotal,
      gst,
      materialCost,
      installCharge,
      profitFlat,
      profitPercentage,
      limitedTotal,
      finalTotal,
      customerPayable,
      actualProfit,
      missing
    };
  }

  w.calculateInstallPricing = calculateInstallPricing;
  w.getInstallCustomerPayableFromDom = getCustomerPayableFromDom;
})(window);
