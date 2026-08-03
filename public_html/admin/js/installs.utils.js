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

window.HSR_LAYOUT_COORDS = window.HSR_LAYOUT_COORDS || { lat: 12.9121, lng: 77.6446 };

const installDistanceCache = new Map();
const installDistancePending = new Map();
const installResolvedCoordsPending = new Map();

function extractCoordsFromText(text = "") {
  const value = String(text || '').trim();
  if (!value) return null;

  const direct = value.match(/^\s*(-?\d{1,2}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)\s*$/);
  if (direct) {
    return { lat: Number(direct[1]), lng: Number(direct[2]) };
  }

  const patterns = [
    /!3d(-?\d{1,2}\.\d+)!4d(-?\d{1,3}\.\d+)/,
    /@(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)/,
    /[?&]q=(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)/,
    /[?&]query=(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)/,
    /[?&]destination=(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)/,
    /[?&]ll=(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)/,
    /\/place\/(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)/
  ];

  for (const pattern of patterns) {
    const match = value.match(pattern);
    if (match) {
      return { lat: Number(match[1]), lng: Number(match[2]) };
    }
  }

  return null;
}
window.extractCoordsFromText = extractCoordsFromText;

function haversineDistanceKm(a, b) {
  if (!a || !b) return null;
  const toRad = (deg) => deg * Math.PI / 180;
  const earthRadiusKm = 6371;
  const dLat = toRad(b.lat - a.lat);
  const dLng = toRad(b.lng - a.lng);
  const lat1 = toRad(a.lat);
  const lat2 = toRad(b.lat);
  const sinLat = Math.sin(dLat / 2);
  const sinLng = Math.sin(dLng / 2);
  const c = 2 * Math.atan2(
    Math.sqrt(sinLat * sinLat + Math.cos(lat1) * Math.cos(lat2) * sinLng * sinLng),
    Math.sqrt(1 - (sinLat * sinLat + Math.cos(lat1) * Math.cos(lat2) * sinLng * sinLng))
  );
  return earthRadiusKm * c;
}
window.haversineDistanceKm = haversineDistanceKm;

function isGoogleMapsLikeUrl(text = '') {
  const value = String(text || '').trim();
  return /https?:\/\/[^\s]*(?:google\.[^/\s]+\/maps|google\.com\/maps|maps\.app|goo\.gl)/i.test(value);
}
window.isGoogleMapsLikeUrl = isGoogleMapsLikeUrl;

async function resolveCoordsFromMapUrl(mapUrl = '') {
  const url = String(mapUrl || '').trim();
  if (!url || !isGoogleMapsLikeUrl(url)) return null;

  const direct = extractCoordsFromText(url);
  if (direct) return direct;

  if (installResolvedCoordsPending.has(url)) return installResolvedCoordsPending.get(url);

  const req = fetch(`/admin_v2/smart/resolve_map_url.php?url=${encodeURIComponent(url)}`, {
    cache: 'force-cache'
  })
    .then(res => res.json())
    .then(data => (data && data.success && data.coords ? data.coords : null))
    .catch(() => null)
    .finally(() => {
      installResolvedCoordsPending.delete(url);
    });

  installResolvedCoordsPending.set(url, req);
  return req;
}
window.resolveCoordsFromMapUrl = resolveCoordsFromMapUrl;

function getInstallDistanceCacheKey(origin = '', destination = '', mapUrl = '') {
  return ['install-distance', String(origin || '').trim(), String(destination || '').trim(), String(mapUrl || '').trim()].join('|');
}

function loadCachedInstallDistance(key) {
  if (!key) return null;
  if (installDistanceCache.has(key)) return installDistanceCache.get(key);
  try {
    const raw = window.sessionStorage ? sessionStorage.getItem(key) : null;
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (!parsed || typeof parsed.distance_km !== 'number') return null;
    installDistanceCache.set(key, parsed);
    return parsed;
  } catch (err) {
    return null;
  }
}

function storeCachedInstallDistance(key, payload) {
  if (!key || !payload || typeof payload.distance_km !== 'number') return payload;
  installDistanceCache.set(key, payload);
  try {
    if (window.sessionStorage) sessionStorage.setItem(key, JSON.stringify(payload));
  } catch (err) {
  }
  return payload;
}

async function requestInstallDistance(options = {}) {
  const origin = String(options.origin || 'HSR Layout').trim();
  const destination = String(options.destination || '').trim();
  const mapUrl = String(options.mapUrl || '').trim();
  const presetCoords = options.coords && options.coords.lat !== undefined && options.coords.lng !== undefined
    ? { lat: Number(options.coords.lat), lng: Number(options.coords.lng) }
    : null;
  const cacheKey = getInstallDistanceCacheKey(origin, destination, mapUrl);
  const cached = loadCachedInstallDistance(cacheKey);
  if (cached) return Promise.resolve(cached);
  if (installDistancePending.has(cacheKey)) return installDistancePending.get(cacheKey);

  const req = (async () => {
    let coords = presetCoords || extractCoordsFromText(mapUrl) || extractCoordsFromText(destination);
    if (!coords && mapUrl && typeof window.resolveCoordsFromMapUrl === 'function') {
      coords = await window.resolveCoordsFromMapUrl(mapUrl);
    }
    if (coords) {
      const km = haversineDistanceKm(window.HSR_LAYOUT_COORDS, coords);
      if (typeof km === 'number' && !Number.isNaN(km)) {
        return storeCachedInstallDistance(cacheKey, {
          distance_km: Number(km.toFixed(2)),
          text: `${km.toFixed(1)} km`,
          source: 'local'
        });
      }
    }

    return fetch(`../distance_api.php?origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}`, {
      cache: 'force-cache'
    })
      .then(res => res.json())
      .then(data => {
        if (data && typeof data.distance_km === 'number') {
          return storeCachedInstallDistance(cacheKey, data);
        }
        return data;
      });
  })().finally(() => {
    installDistancePending.delete(cacheKey);
  });

  installDistancePending.set(cacheKey, req);
  return req;
}
window.requestInstallDistance = requestInstallDistance;
