<?php
ob_start();
require_once __DIR__ . '/../auth.php';
date_default_timezone_set('Asia/Kolkata');

$today = date('Y-m-d');
$displayName = trim((string)($nameAssign ?? $authUser ?? 'Engineer'));
$authUsername = trim((string)($authUser ?? ''));
if (!function_exists('isElevatedRole')) {
  function isElevatedRole(string $role): bool {
    $role = strtolower(trim($role));
    return in_array($role, ['admin', 'manager'], true);
  }
}
$isAdmin = isElevatedRole((string)($role ?? ''));
$logoSmall = 'https://smartronic.online/content/uploads/2025/01/smartronic_small_logo.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>SM Issues | Today</title>
  <link rel="icon" type="image/png" sizes="32x32" href="/content/uploads/2025/01/cropped-Site-Icon-32x32.png">
  <link rel="apple-touch-icon" href="/content/uploads/2025/01/cropped-Site-Icon-180x180.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --bg: #f3f4f6;
      --card: #ffffff;
      --muted: #6b7280;
      --text: #111827;
      --primary: #2563eb;
      --danger: #dc2626;
      --ok: #16a34a;
      --border: #e5e7eb;
      --issue: #dc2626;
      --inspection: #f97316;
      --general: #2563eb;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Arial, Helvetica, sans-serif;
      background: var(--bg);
      color: var(--text);
    }
    .wrap {
      max-width: 760px;
      margin: 0 auto;
      padding: 12px 12px 88px;
    }
    .topbar {
      position: sticky;
      top: 0;
      z-index: 20;
      background: rgba(243, 244, 246, 0.95);
      backdrop-filter: blur(6px);
      padding: 8px 0 10px;
    }
    .title-card {
      position: relative;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 10px 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      overflow: hidden;
    }
    .title-card.map-loading {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), 0 0 0 3px rgba(37, 99, 235, 0.15);
    }
    .title-card.map-loading::after {
      content: "";
      position: absolute;
      inset: 0;
      padding: 5px;
      border-radius: 12px;
      background: linear-gradient(90deg, #2563eb 0%, #60a5fa 25%, #22c55e 50%, #60a5fa 75%, #2563eb 100%);
      background-size: 220% 100%;
      animation: mapLoadBorder 1.1s linear infinite;
      -webkit-mask:
        linear-gradient(#000 0 0) content-box,
        linear-gradient(#000 0 0);
      -webkit-mask-composite: xor;
      mask-composite: exclude;
      pointer-events: none;
    }
    @keyframes mapLoadBorder {
      0% { background-position: 0% 50%; }
      100% { background-position: 220% 50%; }
    }
    .title-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }
    .title-left {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
    }
    .title-logo {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      object-fit: cover;
      flex: 0 0 auto;
      border: 1px solid #d1d5db;
    }
    .title-row h1 {
      margin: 0;
      font-size: 18px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .top-actions {
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .map-open-btn {
      border: 1px solid var(--border);
      background: #fff;
      color: #111827;
      border-radius: 4px;
      font-size: 14px;
      font-weight: 700;
      padding: 8px 10px;
      cursor: pointer;
      min-width: 40px;
    }
    .meta {
      margin-top: 4px;
      font-size: 12px;
      color: var(--muted);
    }
    .date-nav {
      margin-top: 8px;
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      align-items: center;
    }
    .date-nav-btn,
    .date-nav-select {
      border: 1px solid var(--border);
      background: #fff;
      color: #374151;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 700;
      padding: 5px 8px;
      min-height: 30px;
    }
    .date-nav-btn {
      cursor: pointer;
    }
    .date-nav-select {
      min-width: 130px;
      flex: 0 1 160px;
    }
    .top-priority-btn {
      width: 30px;
      height: 30px;
      flex: 0 0 30px;
      border-radius: 999px;
      border: 1px solid #a16207;
      background: #ca8a04;
      cursor: pointer;
      padding: 0;
      box-shadow: 0 0 0 3px rgba(202, 138, 4, 0.18);
      transition: transform 0.16s ease, box-shadow 0.18s ease, background 0.18s ease, border-color 0.18s ease;
    }
    .top-priority-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.16);
    }
    .top-priority-btn.is-active {
      border-color: #dc2626;
      background: #dc2626;
      box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.16);
    }
    .top-closed-toggle-btn,
    .top-direction-btn {
      width: 40px;
      height: 40px;
      flex: 0 0 40px;
      border-radius: 999px;
      border: 1px solid var(--border);
      background: #fff;
      color: #111827;
      cursor: pointer;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      transition: transform 0.16s ease, box-shadow 0.18s ease, background 0.18s ease, border-color 0.18s ease, color 0.18s ease;
    }
    .top-closed-toggle-btn:hover,
    .top-direction-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.16);
    }
    .top-closed-toggle-btn.is-active {
      border-color: #4f46e5;
      background: #4f46e5;
      color: #fff;
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.16);
    }
    .top-direction-btn.is-active {
      border-color: #0f766e;
      background: #0f766e;
      color: #fff;
      box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.16);
    }
    .filters {
      display: grid;
      grid-template-columns: repeat(6, minmax(0, 1fr));
      gap: 8px;
      margin-top: 10px;
    }
    .filter-btn {
      border: 1px solid var(--border);
      background: #fff;
      color: #374151;
      border-radius: 4px;
      font-size: 12px;
      padding: 8px 10px;
      cursor: pointer;
      text-align: center;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
      position: relative;
      overflow: visible;
    }
    .filter-btn[aria-pressed="true"] {
      color: #fff;
      border-color: transparent;
      background: #111827;
    }
    .filter-count-badge {
      width: 22px;
      min-width: 22px;
      height: 22px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #dc2626;
      color: #fff;
      font-size: 11px;
      font-weight: 800;
      line-height: 1;
      position: absolute;
      top: -8px;
      right: -8px;
      box-shadow: 0 3px 10px rgba(220, 38, 38, 0.28);
      border: 2px solid #fff;
    }
    .filter-btn[aria-pressed="true"] .filter-count-badge {
      background: #dc2626;
      color: #fff;
    }
    .sort-btn {
      border: 1px solid var(--border);
      background: #fff;
      color: #374151;
      border-radius: 4px;
      font-size: 14px;
      padding: 8px 0;
      cursor: pointer;
      text-align: center;
      font-weight: 700;
    }
    .sort-btn.active {
      color: #fff;
      border-color: transparent;
      background: #1d4ed8;
    }
    .create-card {
      margin-top: 10px;
      border: 1px solid var(--border);
      background: #fff;
      border-radius: 12px;
      padding: 10px;
      display: none;
      position: relative;
      z-index: 25;
    }
    .create-card.open {
      display: block;
    }
    .create-grid {
      display: grid;
      gap: 8px;
    }
    .create-grid input,
    .create-grid textarea,
    .create-grid select {
      width: 100%;
      border: 1px solid #d1d5db;
      border-radius: 10px;
      padding: 10px;
      font-size: 14px;
      background: #fff;
      font-family: inherit;
    }
    .create-grid textarea {
      min-height: 74px;
      resize: vertical;
    }
    .note-icon-picker {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 2px;
    }
    .note-icon-chip {
      border: 1px solid #d1d5db;
      background: #fff;
      color: #374151;
      border-radius: 999px;
      min-height: 34px;
      padding: 6px 10px;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      cursor: pointer;
      font-size: 12px;
      font-weight: 700;
      transition: transform 0.16s ease, box-shadow 0.18s ease, border-color 0.18s ease, background 0.18s ease, color 0.18s ease;
    }
    .note-icon-chip:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
    }
    .note-icon-chip.is-active {
      border-color: #111827;
      background: #111827;
      color: #fff;
      box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.12);
    }
    .note-icon-chip i {
      font-size: 13px;
    }
    .lookup-wrap {
      position: relative;
    }
    .lookup-wrap.lookup-priority input {
      border: 2px solid #f59e0b;
      background: #fff7d6;
      box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
      font-weight: 700;
    }
    .lookup-wrap.lookup-priority input::placeholder {
      color: #92400e;
      opacity: 1;
    }
    .lookup-hint {
      font-size: 11px;
      font-weight: 700;
      color: #b45309;
      margin-bottom: 4px;
    }
    .lookup-suggest {
      position: absolute;
      top: calc(100% + 6px);
      left: 0;
      right: 0;
      background: #fff;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
      max-height: 240px;
      overflow-y: auto;
      z-index: 50;
      display: none;
    }
    .lookup-suggest-item {
      padding: 10px 12px;
      border-bottom: 1px solid #e5e7eb;
      cursor: pointer;
    }
    .lookup-suggest-item:last-child {
      border-bottom: none;
    }
    .lookup-suggest-item:hover {
      background: #f8fafc;
    }
    .lookup-s-line1 {
      font-size: 13px;
      color: #111827;
    }
    .lookup-s-line2 {
      margin-top: 3px;
      font-size: 11px;
      color: #6b7280;
    }
    .form-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(17, 24, 39, 0.28);
      z-index: 15;
      display: none;
    }
    .form-backdrop.open {
      display: block;
    }
    .create-actions {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }
    .create-meta {
      font-size: 12px;
      color: #6b7280;
      font-weight: 700;
    }
    .create-btn {
      border: none;
      border-radius: 10px;
      background: #111827;
      color: #fff;
      font-size: 13px;
      font-weight: 700;
      padding: 10px 12px;
      cursor: pointer;
    }
    .list {
      margin-top: 12px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .age-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .age-separator {
      position: sticky;
      top: 112px;
      z-index: 5;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      align-self: flex-start;
      margin-top: 6px;
      padding: 5px 10px;
      border-radius: 4px;
      background: #111827;
      color: #fff;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.02em;
      text-transform: uppercase;
    }
    .expired-notes-region {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin: 2px 0 8px;
    }
    .expired-notes-panel {
      border: 1px solid #d1d5db;
      border-radius: 10px;
      background: #fff;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
    }
    .expired-notes-panel summary {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      padding: 10px 12px;
      cursor: pointer;
      user-select: none;
      font-size: 13px;
      font-weight: 800;
      color: #111827;
      list-style: none;
    }
    .expired-notes-panel summary::-webkit-details-marker {
      display: none;
    }
    .expired-notes-panel summary::after {
      content: "\f078";
      font-family: "Font Awesome 6 Free";
      font-weight: 900;
      color: #64748b;
      transition: transform 0.18s ease;
    }
    .expired-notes-panel[open] summary::after {
      transform: rotate(180deg);
    }
    .expired-notes-panel.issue summary { border-left: 6px solid var(--issue); }
    .expired-notes-panel.inspection summary { border-left: 6px solid var(--inspection); }
    .expired-notes-panel.general summary { border-left: 6px solid var(--general); }
    .expired-notes-count {
      margin-left: auto;
      border-radius: 999px;
      background: #f1f5f9;
      color: #334155;
      padding: 3px 8px;
      font-size: 11px;
      font-weight: 800;
    }
    .expired-notes-body {
      display: flex;
      flex-direction: column;
      gap: 8px;
      padding: 8px;
      border-top: 1px solid #e5e7eb;
      background: #f8fafc;
    }
    .expired-notes-body .note-card {
      box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
    }
    .note-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-left: 6px solid var(--general);
      border-radius: 12px;
      padding: 14px 14px 16px;
      box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
    }
    .note-card.issue { border-left-color: var(--issue); }
    .note-card.inspection { border-left-color: var(--inspection); }
    .note-card.general { border-left-color: var(--general); }
    .note-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 4px;
    }
    .note-head-main {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      flex: 1 1 320px;
      min-width: 0;
    }
    .note-head-meta {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 6px;
      flex-wrap: wrap;
      margin-left: auto;
      position: relative;
      flex: 1 1 220px;
    }
    .note-chip {
      display: inline-flex;
      align-items: center;
      min-height: 28px;
      padding: 4px 9px;
      border-radius: 999px;
      border: 1px solid #d1d5db;
      background: #ffffff;
      color: #2e2e2e;
      font-size: 12px;
      line-height: 1.2;
      white-space: normal;
    }
    .note-chip.duplicate {
      border-color: #fecaca;
      background: #fee2e2;
      color: #991b1b;
      font-weight: 800;
    }
    .note-card.is-duplicate {
      border-color: #fca5a5;
      box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.12), 0 6px 18px rgba(15, 23, 42, 0.08);
    }
    .note-card.is-duplicate:not(.is-done) {
      background: #fff7f7;
    }
    .note-age-text {
      display: block;
      margin: 8px 0 6px;
      font-size: 12px;
      font-weight: 600;
      color: #64748b;
    }
    .note-serial {
      min-width: 32px;
      text-align: center;
      padding: 4px 8px;
      border-radius: 4px;
      background: #111827;
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      line-height: 1;
    }
    .note-contact {
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .contact-link {
      width: 28px;
      height: 28px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      font-size: 13px;
      border: 1px solid #e5e7eb;
      background: #fff;
    }
    .contact-link.call { color: #0f766e; }
    .contact-link.wa { color: #16a34a; }
    .note-type {
      font-size: 11px;
      font-weight: 700;
      border-radius: 4px;
      padding: 3px 8px;
      color: #fff;
      background: var(--general);
      text-transform: uppercase;
    }
    .note-type.issue { background: var(--issue); }
    .note-type.inspection { background: var(--inspection); }
    .note-type.general { background: var(--general); }
    .note-customer {
      margin: 0;
      font-size: 20px;
      font-weight: bold;
      line-height: 1.25;
      color: #0f172a;
      word-break: break-word;
      text-transform: capitalize;
      flex: 1 1 auto;
      min-width: 0;
    }
    .note-customer-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 0 0 6px;
    }
    .note-title-block {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      flex: 1 1 auto;
      min-width: 0;
    }
    .note-asset-icons {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }
    .note-asset-icon {
      width: 28px;
      height: 28px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #eff6ff;
      color: #1d4ed8;
      border: 1px solid #bfdbfe;
      font-size: 13px;
    }
    .note-priority-toggle {
      width: 24px;
      height: 24px;
      flex: 0 0 24px;
      border-radius: 999px;
      border: 1px solid #cbd5e1;
      background: #e5e7eb;
      color: transparent;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      padding: 0;
      transition: transform 0.16s ease, box-shadow 0.18s ease, border-color 0.18s ease, background 0.18s ease, color 0.18s ease;
    }
    .note-priority-toggle:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.16);
    }
    .note-priority-toggle.is-important {
      border-color: #a16207;
      background: #ca8a04;
      box-shadow: 0 0 0 3px rgba(202, 138, 4, 0.18);
    }
    .note-priority-toggle.is-very-important {
      border-color: #dc2626;
      background: #dc2626;
      box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.18);
    }
    .note-customer.empty {
      display: none;
    }
    .note-title {
      margin: 0;
      font-size: 14px;
      font-weight: 400;
      line-height: 1.55;
      word-break: break-word;
      color: #334155;
    }
    .note-place {
      font-weight: 800;
      color: #0f172a;
    }
    .note-title-meta {
      color: #334155;
    }
    .note-issue-inline {
      color: #dc2626;
      font-weight: 600;
    }
    .note-geo-plot {
      width: 100%;
      max-width: 100%;
      position: relative;
    }
    .note-geo-map {
      display: block;
      width: 100%;
      max-width: 100%;
      height: 240px;
      border-radius: 12px;
      border: 1px solid #dbe3ef;
      object-fit: cover;
      background: #e5e7eb;
    }
    .note-geo-overlay {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 240px;
      pointer-events: none;
      overflow: visible;
    }
    .note-geo-meta {
      position: absolute;
      top: 12px;
      left: 12px;
      z-index: 2;
    }
    .note-geo-pill {
      display: inline-block;
      color: #0f172a;
      font-weight: 900;
      font-size: 22px;
      line-height: 1.05;
      text-shadow: 0 1px 2px rgba(255,255,255,0.95);
    }
    .note-geo-meta-text {
      display: none;
    }
    .note-desc {
      display: none;
    }
    .note-foot {
      margin-top: 14px;
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      padding-top: 12px;
      border-top: 1px solid #eef2f7;
    }
    .note-by {
      font-size: 12px;
      color: var(--muted);
      line-height: 1.45;
    }
    .note-meta-line {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      flex: 1 1 280px;
      min-width: 0;
    }
    .note-map-inline-toggle {
      width: 22px;
      height: 22px;
      border: 1px solid #cbd5e1;
      border-radius: 999px;
      background: #fff;
      color: #475569;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      padding: 0;
    }
    .note-map-inline-toggle i {
      font-size: 12px;
      transition: transform 0.16s ease;
    }
    .note-card.map-inline-open .note-map-inline-toggle {
      background: #111827;
      color: #fff;
      border-color: #111827;
    }
    .note-card.map-inline-open .note-map-inline-toggle i {
      transform: rotate(180deg);
    }
    .note-inline-map-wrap {
      display: none;
      margin-top: 10px;
      padding-top: 10px;
      border-top: 1px dashed #cbd5e1;
    }
    .note-card.map-inline-open .note-inline-map-wrap {
      display: block;
    }
    .note-assignee-tag {
      display: inline-flex;
      align-items: center;
      padding: 2px 7px;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 700;
      color: #fff;
      line-height: 1.2;
    }
    .note-assignee-history {
      font-size: 11px;
      color: #6b7280;
      line-height: 1.4;
    }
    .note-assignee-select {
      border: none;
      border-radius: 999px;
      padding: 5px 24px 5px 10px;
      font-size: 11px;
      font-weight: 700;
      line-height: 1.2;
      appearance: none;
      -webkit-appearance: none;
      background-image: linear-gradient(45deg, transparent 50%, currentColor 50%), linear-gradient(135deg, currentColor 50%, transparent 50%);
      background-position: calc(100% - 12px) calc(50% - 1px), calc(100% - 7px) calc(50% - 1px);
      background-size: 5px 5px, 5px 5px;
      background-repeat: no-repeat;
      cursor: pointer;
    }
    .done-btn {
      border: none;
      border-radius: 999px;
      background: var(--ok);
      color: #fff;
      font-weight: 700;
      font-size: 14px;
      width: 34px;
      height: 34px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0;
    }
    .edit-btn {
      border: 1px solid #d1d5db;
      border-radius: 999px;
      background: #fff;
      color: #374151;
      font-size: 14px;
      width: 34px;
      height: 34px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0;
    }
    .reopen-btn {
      border: 1px solid #d1d5db;
      border-radius: 999px;
      background: #fff;
      color: #1d4ed8;
      font-size: 14px;
      width: 34px;
      height: 34px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0;
    }
    .note-actions-inline {
      display: inline-flex;
      gap: 8px;
      align-items: center;
      justify-content: flex-end;
      flex: 0 0 auto;
    }
    .note-card.is-done {
      opacity: 0.8;
      background: #f9fafb;
      border-left-color: #16a34a;
    }
    .note-card.is-done .note-title,
    .note-card.is-done .note-customer {
      text-decoration: line-through;
      color: #6b7280;
    }
    .done-state {
      font-size: 12px;
      color: #15803d;
      font-weight: 700;
    }
    .done-by {
      font-size: 12px;
      color: #166534;
      margin-top: 10px;
      font-weight: 700;
      line-height: 1.45;
    }
    @media (min-width: 761px) {
      .note-card {
        padding: 16px 18px 18px;
      }
      .note-head {
        gap: 14px;
      }
      .note-head-main {
        flex-basis: 360px;
      }
      .note-head-meta {
        max-width: 44%;
      }
      .note-customer {
        font-size: 22px;
      }
      .note-title {
        font-size: 15px;
      }
    }
    .empty, .error {
      margin-top: 12px;
      background: #fff;
      border: 1px dashed var(--border);
      border-radius: 12px;
      padding: 16px;
      font-size: 14px;
      color: #4b5563;
      text-align: center;
    }
    .error {
      border-color: #fecaca;
      color: #991b1b;
      background: #fef2f2;
    }
    .refresh {
      border: 1px solid var(--border);
      background: #fff;
      color: #111827;
      border-radius: 4px;
      font-size: 14px;
      font-weight: 700;
      padding: 8px 0;
      cursor: pointer;
      min-width: 40px;
    }
    .refresh.is-loading i {
      animation: spinRefresh 0.9s linear infinite;
    }
    @keyframes spinRefresh {
      to { transform: rotate(360deg); }
    }
    .fab-add {
      position: fixed;
      right: 16px;
      bottom: 16px;
      z-index: 30;
      width: 56px;
      height: 56px;
      border: none;
      border-radius: 999px;
      background: #111827;
      color: #fff;
      font-size: 26px;
      font-weight: 700;
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.28);
      cursor: pointer;
    }
    .fab-add.open {
      background: #dc2626;
    }
    .issues-map-popup {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.85);
      z-index: 99999;
      display: flex;
      flex-direction: column;
      padding: 20px;
    }
    .issues-map-close {
      position: absolute;
      top: 15px;
      right: 15px;
      background: #fff;
      border: none;
      border-radius: 999px;
      width: 40px;
      height: 40px;
      font-size: 18px;
      cursor: pointer;
      z-index: 100001;
      box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    .issues-map-canvas {
      flex: 1;
      min-height: 300px;
      border-radius: 12px;
      overflow: hidden;
      margin-bottom: 15px;
      background: #111827;
    }
    .issues-map-cards {
      display: flex;
      gap: 15px;
      overflow-x: auto;
      padding: 10px 0;
      scroll-snap-type: x mandatory;
      -webkit-overflow-scrolling: touch;
    }
    .issues-map-card {
      flex: 0 0 280px;
      background: #1e1e1e;
      color: #ffffff;
      border-radius: 6px;
      padding: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.5);
      scroll-snap-align: start;
      position: relative;
      border: 1px solid #333;
      cursor: pointer;
    }
    .issues-map-card.no-map {
      opacity: 0.72;
      cursor: default;
    }
    .issues-map-card.issue { border-left: 6px solid var(--issue); }
    .issues-map-card.inspection { border-left: 6px solid var(--inspection); }
    .issues-map-card.general { border-left: 6px solid var(--general); }
    .issues-map-card-badge {
      position: absolute;
      top: 4px;
      left: 9px;
      background: linear-gradient(135deg, rgb(229, 57, 53) 0%, rgb(198, 40, 40) 100%);
      color: #fff;
      min-width: 24px;
      height: 24px;
      border-radius: 4px;
      padding: 0 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 11px;
      box-shadow: rgba(229, 57, 53, 0.5) 0 3px 8px;
      border: 2px solid rgb(30, 30, 30);
      z-index: 2;
    }
    .issues-map-card-head {
      padding-top: 22px;
      font-size: 15px;
      font-weight: 700;
      line-height: 1.35;
    }
    .issues-map-card-type {
      display: inline-flex;
      align-items: center;
      border-radius: 4px;
      padding: 3px 8px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      color: #fff;
      margin-bottom: 8px;
    }
    .issues-map-card-type.issue { background: var(--issue); }
    .issues-map-card-type.inspection { background: var(--inspection); }
    .issues-map-card-type.general { background: var(--general); }
    .issues-map-card-desc {
      font-size: 13px;
      line-height: 1.45;
      color: #d1d5db;
      white-space: pre-wrap;
      word-break: break-word;
    }
    .issues-map-card-meta {
      font-size: 11px;
      color: #aaa;
      margin-top: 8px;
      border-top: 1px solid #444;
      padding-top: 5px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .planning-card {
      margin-top: 14px;
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 14px;
      box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
    }
    .planning-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 8px;
    }
    .planning-title {
      margin: 0;
      font-size: 13px;
      font-weight: 800;
      color: #111827;
      letter-spacing: 0.01em;
      text-transform: uppercase;
    }
    .planning-open-btn {
      border: 1px solid #d1d5db;
      background: #fff;
      color: #111827;
      border-radius: 999px;
      min-height: 34px;
      padding: 7px 12px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      white-space: nowrap;
    }
    .planning-hint {
      margin: 0 0 10px;
      font-size: 12px;
      color: #6b7280;
      line-height: 1.45;
    }
    .planning-textarea {
      width: 100%;
      min-height: 280px;
      border: 1px solid #d1d5db;
      border-radius: 10px;
      padding: 12px;
      font: 13px/1.5 "Courier New", Courier, monospace;
      color: #111827;
      background: #f9fafb;
      resize: vertical;
    }
    @media (max-width: 560px) {
      .filters { grid-template-columns: repeat(6, minmax(0, 1fr)); }
      .filter-btn { font-size: 11px; padding: 8px 4px; }
      .title-row h1 { font-size: 16px; }
      .note-card {
        padding: 12px 12px 14px;
        border-radius: 10px;
      }
      .note-head {
        gap: 10px;
      }
      .note-head-main,
      .note-head-meta,
      .note-meta-line,
      .note-actions-inline {
        width: 100%;
      }
      .note-head-meta {
        justify-content: flex-start;
      }
      .note-customer {
        font-size: 18px;
      }
      .note-customer-row {
        gap: 8px;
        align-items: center;
      }
      .note-title {
        font-size: 13px;
        line-height: 1.5;
      }
      .note-foot {
        gap: 10px;
        margin-top: 12px;
        padding-top: 10px;
      }
      .note-meta-line {
        gap: 7px;
      }
      .note-actions-inline {
        justify-content: flex-start;
      }
      .done-btn,
      .edit-btn,
      .reopen-btn {
        width: 32px;
        height: 32px;
      }
      .planning-card {
        padding: 12px;
      }
      .planning-head {
        align-items: flex-start;
        flex-wrap: wrap;
      }
      .planning-open-btn {
        width: 100%;
        justify-content: center;
      }
      .planning-textarea {
        min-height: 240px;
        font-size: 12px;
      }
      .note-priority-toggle {
        width: 22px;
        height: 22px;
        flex-basis: 22px;
      }
    }
  </style>
</head>
<body>
  <main class="wrap">
    <section class="topbar">
      <div class="title-card">
        <div class="title-row">
          <div class="title-left">
            <img class="title-logo" src="<?php echo htmlspecialchars($logoSmall, ENT_QUOTES, 'UTF-8'); ?>" alt="Smartronic">
            <h1><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Today Service Notes</h1>
          </div>
          <div class="top-actions">
            <button id="showClosedToggleBtn" class="top-closed-toggle-btn" type="button" title="Showing open issues only" aria-label="Showing open issues only" aria-pressed="false">
              <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            </button>
            <button id="directionGroupBtn" class="top-direction-btn" type="button" title="Group by direction" aria-label="Group by direction" aria-pressed="false">
              <i class="fa-regular fa-compass" aria-hidden="true"></i>
            </button>
            <button id="mapViewBtn" class="map-open-btn" type="button" title="Open map view" aria-label="Open map view">
              <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            </button>
          </div>
        </div>
        <div class="meta">
          <span id="todayLabel"></span> | Logged in as <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php if ($isAdmin): ?>
          <div class="date-nav" aria-label="Closed issue date navigation">
            <button id="yesterdayBtn" class="date-nav-btn" type="button">Yesterday</button>
            <button id="twoDaysBtn" class="date-nav-btn" type="button">2 Days</button>
            <input id="dateSelect" class="date-nav-select" type="date" value="<?php echo htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Select note date">
            <button id="priorityGroupBtn" class="top-priority-btn" type="button" title="Group by priority" aria-label="Group by priority" aria-pressed="false"></button>
          </div>
        <?php endif; ?>
        <div class="filters" role="group" aria-label="Filter note type">
          <button class="filter-btn" data-filter="all" data-label="ALL" aria-pressed="true" type="button">ALL <span class="filter-count-badge" aria-label="0 open">0</span></button>
          <button class="filter-btn" data-filter="issue" data-label="ISS" aria-pressed="false" type="button">ISS <span class="filter-count-badge" aria-label="0 open">0</span></button>
          <button class="filter-btn" data-filter="inspection" data-label="INSP" aria-pressed="false" type="button">INSP <span class="filter-count-badge" aria-label="0 open">0</span></button>
          <button class="filter-btn" data-filter="general" data-label="GEN" aria-pressed="false" type="button">GEN <span class="filter-count-badge" aria-label="0 open">0</span></button>
          <button class="sort-btn active" id="sortToggleBtn" type="button" title="Toggle day sorting" aria-label="Toggle day sorting">
            <i class="fa-solid fa-arrow-up-wide-short"></i>
          </button>
          <button id="refreshBtn" class="refresh" type="button" title="Refresh" aria-label="Refresh">
            <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
          </button>
        </div>
        <form id="createForm" class="create-card" aria-label="Create a new service note">
          <div class="create-grid">
            <input id="editId" type="hidden">
            <div id="createMeta" class="create-meta">Create new note</div>
            <div class="lookup-wrap lookup-priority">
              <div class="lookup-hint">Search here first</div>
              <input id="newLookup" type="search" placeholder="Search phone or name first">
              <div id="lookupSuggestions" class="lookup-suggest" aria-label="Matching orders"></div>
            </div>
            <select id="newType" required>
              <option value="Issue">Issue</option>
              <option value="Inspection">Inspection</option>
              <option value="General">General</option>
            </select>
            <input id="newTitle" type="text" placeholder="Title (required)" maxlength="255" required>
            <textarea id="newDesc" placeholder="Description"></textarea>
            <div id="noteIconPicker" class="note-icon-picker" aria-label="Issue asset icons"></div>
            <div class="create-actions">
              <button class="create-btn" id="createBtn" type="submit">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Create
              </button>
            </div>
          </div>
        </form>
      </div>
    </section>

    <section id="issuesList" class="list" aria-live="polite"></section>
    <section id="stateBox"></section>
    <?php if ($isAdmin): ?>
      <section id="planningCard" class="planning-card">
        <div class="planning-head">
          <h2 class="planning-title">Priority Planning Notes</h2>
          <button id="planningOpenBtn" class="planning-open-btn" type="button">
            <i class="fa-regular fa-copy" aria-hidden="true"></i>
            Copy + Open
          </button>
        </div>
        <p class="planning-hint">Copy this into your planning prompt. It follows priority order first, then the current day sorting inside each priority.</p>
        <textarea id="planningTextarea" class="planning-textarea" readonly placeholder="Priority summary will appear here..."></textarea>
      </section>
    <?php endif; ?>
  </main>
  <div id="formBackdrop" class="form-backdrop" aria-hidden="true"></div>
  <button id="fabAddBtn" class="fab-add" type="button" aria-label="Add new note" title="Add new note">+</button>
  <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB7BKkBQEI0WpbFFjn8K4VWKRaYeIs3GhU&libraries=geometry&loading=async"></script>

  <script>
    (function () {
      const NOTES_API = 'notes_api.php';
      const TODAY = '<?php echo $today; ?>';
      const listEl = document.getElementById('issuesList');
      const stateEl = document.getElementById('stateBox');
      const planningTextarea = document.getElementById('planningTextarea');
      const planningOpenBtn = document.getElementById('planningOpenBtn');
      const refreshBtn = document.getElementById('refreshBtn');
      const yesterdayBtn = document.getElementById('yesterdayBtn');
      const twoDaysBtn = document.getElementById('twoDaysBtn');
      const dateSelectEl = document.getElementById('dateSelect');
      const priorityGroupBtn = document.getElementById('priorityGroupBtn');
      const filterButtons = Array.from(document.querySelectorAll('.filter-btn'));
      const todayLabel = document.getElementById('todayLabel');
      const sortToggleBtn = document.getElementById('sortToggleBtn');
      const createForm = document.getElementById('createForm');
      const createBtn = document.getElementById('createBtn');
      const newTypeEl = document.getElementById('newType');
      const newLookupEl = document.getElementById('newLookup');
      const lookupSuggestionsEl = document.getElementById('lookupSuggestions');
      const newTitleEl = document.getElementById('newTitle');
      const newDescEl = document.getElementById('newDesc');
      const noteIconPickerEl = document.getElementById('noteIconPicker');
      const editIdEl = document.getElementById('editId');
      const createMetaEl = document.getElementById('createMeta');
      const fabAddBtn = document.getElementById('fabAddBtn');
      const formBackdropEl = document.getElementById('formBackdrop');
      const showClosedToggleBtn = document.getElementById('showClosedToggleBtn');
      const directionGroupBtn = document.getElementById('directionGroupBtn');
      const mapViewBtn = document.getElementById('mapViewBtn');
      const titleCardEl = document.querySelector('.title-card');
      const AUTH_USERNAME = '<?php echo htmlspecialchars($authUsername, ENT_QUOTES, 'UTF-8'); ?>';
      const AUTH_DISPLAY = '<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>';
      const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
      const GOOGLE_MAPS_API_KEY = 'AIzaSyB7BKkBQEI0WpbFFjn8K4VWKRaYeIs3GhU';
      const PLANNING_UTILITY_URL = 'https://chatgpt.com/g/g-p-6790a9d6db9081918e85682b3a9a67fd-utility/c/6a0a9ac0-56d8-8322-beb4-8dcd0602b2d3';
      const NOTE_ICON_OPTIONS = [
        { key: 'camera', label: 'Camera', icon: 'fa-solid fa-camera' },
        { key: 'addcamera', label: 'Add Camera', icon: 'fa-solid fa-camera-retro' },
        { key: 'hdd', label: 'HDD', icon: 'fa-solid fa-hard-drive' },
        { key: 'bnc', label: 'BNC', icon: 'fa-solid fa-plug-circle-bolt' },
        { key: 'cable', label: 'Cable', icon: 'fa-solid fa-ethernet' },
        { key: 'offline', label: 'Offline', icon: 'fa-solid fa-power-off' },
        { key: 'mobile', label: 'Mobile', icon: 'fa-solid fa-mobile-screen-button' },
        { key: 'monitor', label: 'Monitor', icon: 'fa-solid fa-desktop' },
        { key: 'adapter', label: 'Adapter', icon: 'fa-solid fa-plug' },
        { key: 'smps', label: 'SMPS', icon: 'fa-solid fa-bolt' },
        { key: 'relocation', label: 'Relocation', icon: 'fa-solid fa-right-left' }
      ];

      let currentFilter = 'all';
      let currentSort = 'asc';
      let priorityGroupActive = false;
      let directionGroupActive = false;
      let showClosedNotes = false;
      let selectedDate = TODAY;
      let notes = [];
      let latestLookupResults = [];
      const geoCache = new Map();
      const openInlineMapIds = new Set();
      const DIRECTION_BASE_COORDS = { lat: 12.9116, lng: 77.6474 };
      const ASSIGNEE_OPTIONS = ['SYED', 'KARTHIK', 'GOWTHAM', 'AKIB', 'ZAIN', 'SIRENJIVI'];
      const NOTE_ICON_MAP = new Map(NOTE_ICON_OPTIONS.map((item) => [item.key, item]));

      function normalizeNoteIconKey(value) {
        const key = String(value || '').trim().toLowerCase().replace(/[^a-z]/g, '');
        return NOTE_ICON_MAP.has(key) ? key : '';
      }

      function extractNoteIcons(text) {
        const raw = extractLabeledValue(text, 'Icons');
        if (!raw) return [];
        const seen = new Set();
        return raw
          .split(',')
          .map((part) => normalizeNoteIconKey(part))
          .filter((key) => {
            if (!key || seen.has(key)) return false;
            seen.add(key);
            return true;
          });
      }

      function stripIconsMeta(text) {
        const source = String(text || '');
        if (!source) return '';
        return source
          .replace(/(?:^|\|)\s*Icons\s*:\s*[^|]*/gi, '')
          .replace(/\s*\|\s*\|\s*/g, ' | ')
          .replace(/^\s*\|\s*|\s*\|\s*$/g, '')
          .replace(/\s{2,}/g, ' ')
          .trim();
      }

      function buildDescriptionWithIcons(baseText, icons) {
        const cleaned = stripIconsMeta(baseText);
        const normalizedIcons = Array.isArray(icons)
          ? icons.map((item) => normalizeNoteIconKey(item)).filter(Boolean)
          : [];
        if (!normalizedIcons.length) return cleaned;
        const iconText = `Icons: ${normalizedIcons.join(', ')}`;
        return cleaned ? `${cleaned} | ${iconText}` : iconText;
      }

      function getSelectedNoteIcons() {
        return Array.from(noteIconPickerEl ? noteIconPickerEl.querySelectorAll('.note-icon-chip.is-active') : [])
          .map((btn) => normalizeNoteIconKey(btn.getAttribute('data-icon-key')))
          .filter(Boolean);
      }

      function syncIconPickerFromDescription() {
        if (!noteIconPickerEl || !newDescEl) return;
        const activeIcons = new Set(extractNoteIcons(newDescEl.value));
        noteIconPickerEl.querySelectorAll('.note-icon-chip').forEach((btn) => {
          const key = normalizeNoteIconKey(btn.getAttribute('data-icon-key'));
          const isActive = !!key && activeIcons.has(key);
          btn.classList.toggle('is-active', isActive);
          btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
      }

      function updateDescriptionIcons(nextIcons) {
        if (!newDescEl) return;
        newDescEl.value = buildDescriptionWithIcons(newDescEl.value, nextIcons);
        syncIconPickerFromDescription();
      }

      function renderNoteIconPicker() {
        if (!noteIconPickerEl) return;
        noteIconPickerEl.innerHTML = NOTE_ICON_OPTIONS.map((item) => `
          <button class="note-icon-chip" type="button" data-icon-key="${escapeHtml(item.key)}" aria-pressed="false" title="${escapeHtml(item.label)}">
            <i class="${escapeHtml(item.icon)}" aria-hidden="true"></i>
            <span>${escapeHtml(item.label)}</span>
          </button>
        `).join('');
        syncIconPickerFromDescription();
      }

      function buildNoteIconHtml(iconKeys) {
        const html = (Array.isArray(iconKeys) ? iconKeys : [])
          .map((key) => NOTE_ICON_MAP.get(key))
          .filter(Boolean)
          .map((item) => `<span class="note-asset-icon" title="${escapeHtml(item.label)}" aria-label="${escapeHtml(item.label)}"><i class="${escapeHtml(item.icon)}" aria-hidden="true"></i></span>`)
          .join('');
        return html ? `<span class="note-asset-icons">${html}</span>` : '';
      }

      function updateFilterCounts() {
        const counters = { all: 0, issue: 0, inspection: 0, general: 0 };
        (Array.isArray(notes) ? notes : []).forEach((note) => {
          if (isDoneNote(note)) return;
          const type = normalizeType(note.type);
          counters.all += 1;
          if (Object.prototype.hasOwnProperty.call(counters, type)) counters[type] += 1;
        });
        filterButtons.forEach((btn) => {
          const filter = btn.getAttribute('data-filter') || 'all';
          const count = counters[filter] || 0;
          let badge = btn.querySelector('.filter-count-badge');
          if (!badge) {
            badge = document.createElement('span');
            badge.className = 'filter-count-badge';
            btn.appendChild(badge);
          }
          badge.textContent = String(count);
          badge.setAttribute('aria-label', `${count} open`);
          btn.setAttribute('aria-label', `${btn.getAttribute('data-label') || filter}: ${count} open`);
        });
      }

      function resetCreateForm() {
        editIdEl.value = '';
        createMetaEl.textContent = 'Create new note';
        createBtn.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i> Create';
        newTypeEl.value = 'Issue';
        if (newLookupEl) newLookupEl.value = '';
        if (lookupSuggestionsEl) {
          lookupSuggestionsEl.innerHTML = '';
          lookupSuggestionsEl.style.display = 'none';
        }
        newTitleEl.value = '';
        newDescEl.value = '';
        syncIconPickerFromDescription();
      }

      function openCreateForm() {
        createForm.classList.add('open');
        if (formBackdropEl) formBackdropEl.classList.add('open');
        fabAddBtn.classList.add('open');
        fabAddBtn.textContent = 'x';
        fabAddBtn.setAttribute('aria-label', 'Close note form');
        setTimeout(() => {
          if (newLookupEl) newLookupEl.focus();
        }, 0);
      }

      function closeCreateForm() {
        createForm.classList.remove('open');
        if (formBackdropEl) formBackdropEl.classList.remove('open');
        fabAddBtn.classList.remove('open');
        fabAddBtn.textContent = '+';
        fabAddBtn.setAttribute('aria-label', 'Add new note');
      }

      function escapeHtml(value) {
        return String(value || '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }

      function parseDate(value) {
        const text = String(value || '').trim();
        const m = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!m) return null;
        return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
      }

      function formatDateLabel(value) {
        const parsed = parseDate(value);
        if (!parsed) return String(value || '');
        return parsed.toLocaleDateString('en-IN', {
          weekday: 'short',
          day: '2-digit',
          month: 'short',
          year: 'numeric'
        });
      }

      function shiftDate(baseValue, days) {
        const parsed = parseDate(baseValue);
        if (!parsed) return String(baseValue || TODAY);
        parsed.setDate(parsed.getDate() + days);
        const yyyy = parsed.getFullYear();
        const mm = String(parsed.getMonth() + 1).padStart(2, '0');
        const dd = String(parsed.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
      }

      function updateHeaderDate() {
        const isTodayView = selectedDate === TODAY;
        todayLabel.textContent = isTodayView
          ? `Today: ${formatDateLabel(selectedDate)}`
          : `Viewing: ${formatDateLabel(selectedDate)}`;
      }

      function refreshDateOptions() {
        if (!dateSelectEl) return;
        dateSelectEl.value = selectedDate;
        updateHeaderDate();
      }

      function formatShortDate(value) {
        const parsed = parseDate(value);
        if (!parsed) return '';
        return parsed.toLocaleDateString('en-IN', {
          day: '2-digit',
          month: 'short',
          year: '2-digit'
        });
      }

      function extractLabeledValue(text, label) {
        const source = String(text || '');
        if (!source) return '';
        const escapedLabel = label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const rx = new RegExp(`(?:^|\\|)\\s*${escapedLabel}\\s*:\\s*([^|]+)`, 'i');
        const match = source.match(rx);
        return match ? String(match[1] || '').trim() : '';
      }

      function stripKnownPreviewFields(text) {
        return String(text || '')
          .split('|')
          .map((part) => part.trim())
          .filter(Boolean)
          .filter((part) => !/^(?:id|name|phone|date|location|type|cams|resolution|hdd|map|owner|technician|icons)\s*:/i.test(part))
          .join(' | ');
      }

      function extractNoteDisplayParts(note) {
        const title = String(note.title || '').trim();
        const noteDesc = String(note.description || '').trim();
        const rawBits = title.split('|').map((part) => part.trim());
        const bits = rawBits.filter(Boolean);
        const mergeUniqueParts = (...values) => {
          const seen = new Set();
          const out = [];
          values.forEach((value) => {
            String(value || '')
              .split('|')
              .map((part) => part.trim())
              .filter(Boolean)
              .forEach((part) => {
                const key = part.toLowerCase();
                if (seen.has(key)) return;
                seen.add(key);
                out.push(part);
              });
          });
          return out.join(' | ');
        };
        const descName = extractLabeledValue(noteDesc, 'Name');
        const descPhone = extractLabeledValue(noteDesc, 'Phone');
        const descLocation = extractLabeledValue(noteDesc, 'Location');
        const descResolution = extractLabeledValue(noteDesc, 'Resolution');
        const descHdd = extractLabeledValue(noteDesc, 'HDD');
        const descDate = extractLabeledValue(noteDesc, 'Date');
        const descCams = extractLabeledValue(noteDesc, 'Cams');
        const descOwner = extractLabeledValue(noteDesc, 'Owner');
        const descIcons = extractNoteIcons(noteDesc);
        const previewTail = stripKnownPreviewFields(noteDesc);
        const parsePhoneAndIssue = (value) => {
          const text = String(value || '').trim();
          if (!text) return { phone: '', issueDescription: '' };
          const match = text.match(/^(?:(?:\+|00)91[\s-]?)?(?:0)?([6-9](?:[\s-]?\d){9})(.*)$/);
          if (!match) {
            return { phone: text, issueDescription: '' };
          }
          return {
            phone: match[1].replace(/\D/g, '').slice(-10),
            issueDescription: String(match[2] || '').trim().replace(/^[|\-:,.\s]+/, '')
          };
        };
        const fromDesc = {
          apiPlace: String(note.location || note.place || descLocation).trim(),
          dbPlace: String(bits[0] || '').trim(),
          customerName: String(descName || '').trim(),
          phone: String(descPhone || '').trim(),
          cams: String(note.cams || descCams).trim(),
          resolution: String(note.resolution || descResolution).trim(),
          hdd: String(note.hdd || note.storage || descHdd).trim(),
          owner: String(note.owner || descOwner).trim(),
          sourceDate: String(descDate || note.order_date || note.date || '').trim()
        };
        if (bits.length >= 4) {
          return {
            place: fromDesc.dbPlace || fromDesc.apiPlace || '',
            apiPlace: fromDesc.apiPlace,
            customerName: fromDesc.customerName || bits[1] || '',
            phone: fromDesc.phone || bits[2] || '',
            issueDescription: mergeUniqueParts(bits.slice(3).join(' | '), previewTail),
            cams: fromDesc.cams,
            resolution: fromDesc.resolution,
            hdd: fromDesc.hdd,
            owner: fromDesc.owner,
            icons: descIcons,
            sourceDate: fromDesc.sourceDate,
            shortTitle: bits.join(' | ')
          };
        }
        if (bits.length === 3) {
          const parsedTail = parsePhoneAndIssue(bits[2]);
          const hasStructuredPhone = parsedTail.phone.replace(/\D/g, '').length >= 10;
          return {
            place: fromDesc.dbPlace || fromDesc.apiPlace || bits[0] || '',
            apiPlace: fromDesc.apiPlace,
            customerName: fromDesc.customerName || bits[1] || '',
            phone: fromDesc.phone || (hasStructuredPhone ? parsedTail.phone : String(note.phone || '').trim()),
            issueDescription: mergeUniqueParts(parsedTail.issueDescription, previewTail),
            cams: fromDesc.cams,
            resolution: fromDesc.resolution,
            hdd: fromDesc.hdd,
            owner: fromDesc.owner,
            icons: descIcons,
            sourceDate: fromDesc.sourceDate,
            shortTitle: bits.join(' | ')
          };
        }
        if (bits.length >= 2) {
          const first = bits[0] || '';
          const second = bits[1] || '';
          const secondDigits = second.replace(/\D/g, '');
          if (second && secondDigits.length < 8) {
            return {
              place: fromDesc.dbPlace || first || fromDesc.apiPlace || '',
              apiPlace: fromDesc.apiPlace,
              customerName: fromDesc.customerName || second,
              phone: fromDesc.phone || String(note.phone || '').trim(),
              issueDescription: mergeUniqueParts(previewTail),
              cams: fromDesc.cams,
              resolution: fromDesc.resolution,
              hdd: fromDesc.hdd,
              owner: fromDesc.owner,
              icons: descIcons,
              sourceDate: fromDesc.sourceDate,
              shortTitle: first || title
            };
          }
        }
        return {
          place: fromDesc.dbPlace || fromDesc.apiPlace,
          apiPlace: fromDesc.apiPlace,
          customerName: fromDesc.customerName,
          phone: fromDesc.phone || String(note.phone || '').trim(),
          issueDescription: mergeUniqueParts(previewTail),
          cams: fromDesc.cams,
          resolution: fromDesc.resolution,
          hdd: fromDesc.hdd,
          owner: fromDesc.owner,
          icons: descIcons,
          sourceDate: fromDesc.sourceDate,
          shortTitle: title
        };
      }

      function getAgeLabel(age) {
        const n = Number(age) || 0;
        if (n <= 0) return 'Today';
        if (n === 1) return 'Yesterday';
        return `${n} days`;
      }

      function getAgeChipLabel(age) {
        const n = Number(age) || 0;
        if (n <= 0) return 'today';
        if (n < 7) return `${n} day${n === 1 ? '' : 's'} old`;
        if (n < 30) {
          const weeks = Math.floor(n / 7);
          return weeks <= 1 ? 'a week old' : `${weeks} weeks old`;
        }
        if (n < 365) {
          const months = Math.floor(n / 30);
          return months <= 1 ? 'a month old' : `${months} months old`;
        }
        const years = Math.floor(n / 365);
        return years <= 1 ? 'a year old' : `${years} years old`;
      }

      function isExpiredNote(note) {
        const createdAge = ageDays(note);
        return createdAge >= 15 && !isDoneNote(note);
      }

      function ageDays(note) {
        const created = parseDate(note && note.created_at ? note.created_at : '');
        const today = parseDate(TODAY);
        if (!created || !today) return 0;
        const dayCreated = new Date(created.getFullYear(), created.getMonth(), created.getDate());
        return Math.max(0, Math.floor((today - dayCreated) / 86400000));
      }

      function ageDaysFromValue(value) {
        const created = parseDate(value);
        const today = parseDate(TODAY);
        if (!created || !today) return 0;
        const dayCreated = new Date(created.getFullYear(), created.getMonth(), created.getDate());
        return Math.max(0, Math.floor((today - dayCreated) / 86400000));
      }

      function normalizeType(type) {
        const t = String(type || 'General').toLowerCase();
        if (t === 'issue') return 'issue';
        if (t === 'inspection') return 'inspection';
        return 'general';
      }

      function normalizePriorityLevel(value) {
        const level = Number(value);
        if (level === 2) return 2;
        if (level === 1) return 1;
        return 0;
      }

      function getPriorityMeta(level) {
        const normalized = normalizePriorityLevel(level);
        if (normalized === 2) {
          return { level: 2, className: 'is-very-important', label: 'Very important' };
        }
        if (normalized === 1) {
          return { level: 1, className: 'is-important', label: 'Important' };
        }
        return { level: 0, className: '', label: 'Not marked' };
      }

      function getPriorityText(level) {
        const normalized = normalizePriorityLevel(level);
        if (normalized === 2) return 'Red';
        if (normalized === 1) return 'Dark Yellow';
        return 'Grey';
      }

      function isPriorityProtected(note) {
        return normalizePriorityLevel(note && note.priority_level) > 0;
      }

      function syncPriorityGroupButton() {
        if (!priorityGroupBtn) return;
        priorityGroupBtn.classList.toggle('is-active', priorityGroupActive);
        priorityGroupBtn.setAttribute('aria-pressed', priorityGroupActive ? 'true' : 'false');
        priorityGroupBtn.setAttribute('title', priorityGroupActive ? 'Priority grouping on' : 'Group by priority');
        priorityGroupBtn.setAttribute('aria-label', priorityGroupActive ? 'Priority grouping on' : 'Group by priority');
      }

      function syncDirectionGroupButton() {
        if (!directionGroupBtn) return;
        directionGroupBtn.classList.toggle('is-active', directionGroupActive);
        directionGroupBtn.setAttribute('aria-pressed', directionGroupActive ? 'true' : 'false');
        directionGroupBtn.setAttribute('title', directionGroupActive ? 'Direction grouping on' : 'Group by direction');
        directionGroupBtn.setAttribute('aria-label', directionGroupActive ? 'Direction grouping on' : 'Group by direction');
      }

      function syncShowClosedToggleButton() {
        if (!showClosedToggleBtn) return;
        showClosedToggleBtn.classList.toggle('is-active', showClosedNotes);
        showClosedToggleBtn.setAttribute('aria-pressed', showClosedNotes ? 'true' : 'false');
        showClosedToggleBtn.setAttribute('title', showClosedNotes ? 'Showing open and closed issues' : 'Showing open issues only');
        showClosedToggleBtn.setAttribute('aria-label', showClosedNotes ? 'Showing open and closed issues' : 'Showing open issues only');
        const icon = showClosedToggleBtn.querySelector('i');
        if (icon) {
          icon.className = showClosedNotes ? 'fa-solid fa-layer-group' : 'fa-solid fa-circle-check';
        }
      }

      function detectPhone(note) {
        const pool = [note.phone, note.title, note.description].filter(Boolean).join(' | ');

        const explicitMatch = pool.match(/(?:phone|ph|mobile|mob|contact)\s*[:#-]?\s*(\+?[\d\s-]{10,15})/i);
        if (explicitMatch) {
          const digits = explicitMatch[1].replace(/\D/g, '');
          if (digits.length >= 10) return digits.slice(-10);
        }

        const patternMatch = pool.match(/(?:(?:\+|00)91[\s-]?)?(?:0)?([6-9](?:[\s-]?\d){9})/);
        if (patternMatch) {
          const digits = patternMatch[0].replace(/\D/g, '');
          if (digits.length >= 10) return digits.slice(-10);
        }

        const fallback = pool.match(/(?:^|[^\d])(\d{10})(?:[^\d]|$)/);
        if (fallback) return fallback[1];

        const parts = pool.split('|');
        for (let p of parts) {
          const partDigits = p.replace(/\D/g, '');
          if (partDigits.length === 10) return partDigits;
          if (partDigits.length === 11 && partDigits.startsWith('0')) return partDigits.slice(-10);
          if (partDigits.length === 12 && partDigits.startsWith('91')) return partDigits.slice(-10);
        }

        return '';
      }

      function detectPhoneFromDraft(title, description, explicitPhone = '') {
        return detectPhone({
          phone: explicitPhone,
          title: String(title || '').trim(),
          description: String(description || '').trim()
        });
      }

      function findRecentPhoneDuplicate(phone, excludeId = 0) {
        const normalized = String(phone || '').replace(/\D/g, '').slice(-10);
        if (normalized.length !== 10) return null;
        let bestMatch = null;
        notes.forEach((note) => {
          if (excludeId && Number(note.id || 0) === Number(excludeId || 0)) return;
          const notePhone = detectPhone(note);
          if (notePhone !== normalized) return;
          const noteAge = ageDays(note);
          if (noteAge > 15) return;
          if (!bestMatch) {
            bestMatch = note;
            return;
          }
          const currentTime = new Date(note.created_at || note.date || 0).getTime() || 0;
          const bestTime = new Date(bestMatch.created_at || bestMatch.date || 0).getTime() || 0;
          if (currentTime > bestTime || (currentTime === bestTime && Number(note.id || 0) > Number(bestMatch.id || 0))) {
            bestMatch = note;
          }
        });
        return bestMatch;
      }

      function getNoteSerialLabel(note, noteList = notes) {
        if (!note) return '';
        const labelsById = buildSequenceLabels(noteList);
        return labelsById[String(note.id || '')] || '';
      }

      function buildDuplicatePhoneMessage(duplicateNote, fallbackPhone = '') {
        const serial = getNoteSerialLabel(duplicateNote);
        const phone = fallbackPhone || (duplicateNote ? detectPhone(duplicateNote) : '');
        const dateLabel = duplicateNote && duplicateNote.date ? formatDateLabel(duplicateNote.date) : '';
        const serialText = serial
          ? `Note ${serial}`
          : (duplicateNote && duplicateNote.id ? `Note ID ${duplicateNote.id}` : 'An existing note');
        return `${serialText} already has phone ${phone || 'number'} within 15 days${dateLabel ? ` (${dateLabel})` : ''}.`;
      }

      function buildWhatsAppLink(phone) {
        const msg = `I am ${AUTH_DISPLAY || AUTH_USERNAME || 'Team'}, from Smartronic regarding the CCTV concern you have raised.`;
        return `https://wa.me/91${encodeURIComponent(phone)}?text=${encodeURIComponent(msg)}`;
      }

      function getAssigneePalette(name) {
        const palette = {
          SYED: { bg: '#0f766e', fg: '#ffffff', soft: '#ccfbf1', border: '#14b8a6' },
          KARTHIK: { bg: '#1d4ed8', fg: '#ffffff', soft: '#dbeafe', border: '#3b82f6' },
          GOWTHAM: { bg: '#7c3aed', fg: '#ffffff', soft: '#ede9fe', border: '#8b5cf6' },
          AKIB: { bg: '#c2410c', fg: '#ffffff', soft: '#ffedd5', border: '#f97316' },
          ZAIN: { bg: '#be123c', fg: '#ffffff', soft: '#ffe4e6', border: '#f43f5e' },
          SIRENJIVI: { bg: '#475569', fg: '#ffffff', soft: '#e2e8f0', border: '#64748b' }
        };
        return palette[String(name || '').toUpperCase()] || { bg: '#6b7280', fg: '#ffffff', soft: '#f3f4f6', border: '#9ca3af' };
      }

      function debounce(fn, wait) {
        let timer = null;
        return function debounced(...args) {
          if (timer) clearTimeout(timer);
          timer = setTimeout(() => fn.apply(this, args), wait);
        };
      }

      function orderToPreview(order) {
        const parts = [];
        if (order.id) parts.push(`ID: ${order.id}`);
        if (order.name) parts.push(`Name: ${order.name}`);
        if (order.phone) parts.push(`Phone: ${order.phone}`);
        if (order.date) parts.push(`Date: ${order.date}`);
        if (order.location) parts.push(`Location: ${order.location}`);
        if (order.type) parts.push(`Type: ${order.type}`);
        if (order.cams) parts.push(`Cams: ${order.cams}`);
        if (order.resolution) parts.push(`Resolution: ${order.resolution}`);
        if (order.hdd) parts.push(`HDD: ${order.hdd}`);
        if (order.map) parts.push(`Map: ${order.map}`);
        return parts.join(' | ');
      }

      function normalizePhoneQuery(raw) {
        let value = String(raw || '').trim().replace(/\s+/g, '');
        if (value.startsWith('+')) value = value.slice(1);
        let digits = value.replace(/\D/g, '');
        if (digits.startsWith('91') && digits.length > 10) digits = digits.slice(-10);
        return digits;
      }

      function renderLookupSuggestions(list) {
        if (!lookupSuggestionsEl) return;
        if (!list || !list.length) {
          lookupSuggestionsEl.innerHTML = '';
          lookupSuggestionsEl.style.display = 'none';
          return;
        }
        lookupSuggestionsEl.innerHTML = list.map((order) => `
          <div class="lookup-suggest-item" data-id="${escapeHtml(order.id || '')}">
            <div class="lookup-s-line1"><strong>${escapeHtml(order.name || 'Unknown')}</strong> ${order.phone ? `&middot; ${escapeHtml(order.phone)}` : ''}</div>
            <div class="lookup-s-line2">${escapeHtml(order.date || '')} ${order.location ? `&middot; ${escapeHtml(order.location)}` : ''} ${order.cams ? `&middot; ${escapeHtml(String(order.cams))} cams` : ''}</div>
          </div>
        `).join('');
        lookupSuggestionsEl.style.display = 'block';

        lookupSuggestionsEl.querySelectorAll('.lookup-suggest-item').forEach((el) => {
          el.addEventListener('mouseenter', () => {
            const id = el.getAttribute('data-id');
            const hit = latestLookupResults.find((row) => String(row.id || '') === String(id || ''));
            if (hit) newDescEl.value = orderToPreview(hit);
          });
          el.addEventListener('click', async (evt) => {
            evt.stopPropagation();
            const id = el.getAttribute('data-id');
            const hit = latestLookupResults.find((row) => String(row.id || '') === String(id || ''));
            if (!hit) return;

            if (newLookupEl) newLookupEl.value = hit.phone || hit.name || '';
            createBtn.disabled = true;
            try {
              let areaName = hit.location || '';
              let locationToGeocode = hit.location || '';
              const desc = orderToPreview(hit);
              const mapsRegex = /https?:\/\/[^\s]*(?:maps|goo\.gl)[^\s]*/i;

              if (!locationToGeocode.match(mapsRegex) && desc.match(mapsRegex)) {
                locationToGeocode = desc.match(mapsRegex)[0];
              }

              if (locationToGeocode && typeof google !== 'undefined' && google.maps && google.maps.Geocoder) {
                try {
                  if (locationToGeocode.match(mapsRegex)) {
                    const resolveRes = await fetch(`resolve_map_url.php?url=${encodeURIComponent(locationToGeocode)}`);
                    const resolveData = await resolveRes.json();
                    if (resolveData.success && resolveData.coords) {
                      locationToGeocode = `${resolveData.coords.lat},${resolveData.coords.lng}`;
                    }
                  }

                  const geocoder = new google.maps.Geocoder();
                  const geoRes = await new Promise((resolve, reject) => {
                    const request = locationToGeocode.includes(',') && !isNaN(parseFloat(locationToGeocode))
                      ? { location: { lat: parseFloat(locationToGeocode.split(',')[0]), lng: parseFloat(locationToGeocode.split(',')[1]) } }
                      : { address: locationToGeocode };
                    geocoder.geocode(request, (results, status) => {
                      if (status === 'OK' && results[0]) resolve(results[0]);
                      else reject(status);
                    });
                  });
                  const areaFromGeo = getAreaNameFromAddressComponents(geoRes.address_components || []);
                  if (areaFromGeo) areaName = areaFromGeo;
                } catch (err) {
                }
              }

              newTitleEl.value = [areaName, hit.name, hit.phone].filter(Boolean).join(' | ');
              newDescEl.value = desc;
            } finally {
              createBtn.disabled = false;
            }

            lookupSuggestionsEl.innerHTML = '';
            lookupSuggestionsEl.style.display = 'none';
          });
        });
      }

      const searchOrders = debounce(async (rawInput) => {
        const raw = String(rawInput || '').trim();
        const digits = (raw.match(/\d/g) || []).length;
        const letters = (raw.match(/[A-Za-z]/g) || []).length;
        if (!raw || (digits < 3 && letters < 3)) {
          latestLookupResults = [];
          renderLookupSuggestions([]);
          return;
        }
        const isPhoneQuery = letters === 0 && digits > 0;
        const query = isPhoneQuery ? normalizePhoneQuery(raw) : raw;
        if (!query) {
          latestLookupResults = [];
          renderLookupSuggestions([]);
          return;
        }
        try {
          const res = await fetch(`orders_search.php?q=${encodeURIComponent(query)}`, { cache: 'no-store' });
          const data = await res.json();
          latestLookupResults = Array.isArray(data.results) ? data.results : [];
          renderLookupSuggestions(latestLookupResults);
        } catch (err) {
          latestLookupResults = [];
          renderLookupSuggestions([]);
        }
      }, 300);

      function buildGoogleMapsLink(raw) {
        const text = String(raw || '').trim();
        if (!text) return '';
        const coordMatch = text.match(/(-?\d{1,2}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/);
        if (coordMatch) {
          return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(coordMatch[1] + ',' + coordMatch[2])}`;
        }
        if (/^https?:\/\//i.test(text)) {
          try {
            const url = new URL(text);
            const query = url.searchParams.get('q') || url.searchParams.get('query') || url.searchParams.get('destination');
            if (query) {
              return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`;
            }
          } catch (err) {
          }
          return text;
        }
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(text)}`;
      }

      function getAreaNameFromAddressComponents(components) {
        const addr = Array.isArray(components) ? components : [];
        const subLoc = addr.find((c) => c.types.includes('sublocality_level_1'))
          || addr.find((c) => c.types.includes('sublocality'))
          || addr.find((c) => c.types.includes('neighborhood'))
          || addr.find((c) => c.types.includes('locality'))
          || addr.find((c) => c.types.includes('administrative_area_level_2'));
        return subLoc ? String(subLoc.long_name || '').trim() : '';
      }

      function getMapQueryText(raw) {
        const text = String(raw || '').trim();
        if (!text || !/^https?:\/\//i.test(text)) return '';
        try {
          const url = new URL(text);
          return String(url.searchParams.get('q') || url.searchParams.get('query') || url.searchParams.get('destination') || '').trim();
        } catch (err) {
          return '';
        }
      }

      function getDisplayLocationText(raw) {
        const text = String(raw || '').trim();
        if (!text) return '';
        const queryText = getMapQueryText(text);
        if (queryText) return queryText;
        if (/^https?:\/\//i.test(text)) return '';
        if (/^-?\d{1,2}\.\d+\s*,\s*-?\d{1,3}\.\d+$/.test(text)) return '';
        return text;
      }

      function getNoteMapSource(note) {
        const noteDesc = String(note && note.description ? note.description : '').trim();
        const descMap = extractLabeledValue(noteDesc, 'Map');
        const descLocation = extractLabeledValue(noteDesc, 'Location');
        const locationText = [note && note.location, note && note.place, descLocation]
          .map((value) => String(value || '').trim())
          .find(Boolean) || '';
        const mapText = [note && note.map, descMap]
          .map((value) => String(value || '').trim())
          .find(Boolean) || '';
        const displayText = getDisplayLocationText(locationText)
          || getDisplayLocationText(mapText)
          || locationText
          || getMapQueryText(mapText)
          || '';
        return { locationText, mapText, displayText };
      }

      function detectMapLink(note) {
        const { mapText, locationText } = getNoteMapSource(note);
        const pool = [mapText, locationText, note.title, note.description].filter(Boolean).join('\n');
        if (!pool) return '';
        const mapUrlMatch = pool.match(/https?:\/\/[^\s<>"']+/i);
        if (mapUrlMatch) return buildGoogleMapsLink(mapUrlMatch[0]);
        const mapLineMatch = pool.match(/(?:^|\n)\s*map\s*:\s*(.+)$/im);
        if (mapLineMatch) return buildGoogleMapsLink(mapLineMatch[1].trim());
        const locationLineMatch = pool.match(/(?:^|\n)\s*location\s*:\s*(.+)$/im);
        if (locationLineMatch) return buildGoogleMapsLink(locationLineMatch[1].trim());
        if (mapText) return buildGoogleMapsLink(mapText);
        if (locationText) return buildGoogleMapsLink(locationText);
        return '';
      }

      async function resolveAreaNameFromInput(input) {
        const raw = String(input || '').trim();
        if (!raw || typeof google === 'undefined' || !google.maps || !google.maps.Geocoder) return '';
        const geocoder = new google.maps.Geocoder();
        let request = null;
        const directCoords = extractCoordsFromText(raw);
        if (directCoords) {
          request = { location: directCoords };
        } else if (/^https?:\/\//i.test(raw)) {
          const queryText = getMapQueryText(raw);
          if (queryText && !extractCoordsFromText(queryText)) {
            request = { address: queryText };
          } else {
            const coords = await resolveCoordsFromMapUrl(raw);
            if (coords) request = { location: coords };
          }
        } else {
          request = { address: raw };
        }
        if (!request) return '';
        try {
          const geoRes = await new Promise((resolve, reject) => {
            geocoder.geocode(request, (results, status) => {
              if (status === 'OK' && results[0]) resolve(results[0]);
              else reject(status);
            });
          });
          return getAreaNameFromAddressComponents(geoRes.address_components || []);
        } catch (err) {
          return '';
        }
      }

      async function resolveCoordsFromInput(input) {
        const raw = String(input || '').trim();
        if (!raw) return null;
        const directCoords = extractCoordsFromText(raw);
        if (directCoords) return directCoords;
        if (/^https?:\/\//i.test(raw)) {
          const mapCoords = await resolveCoordsFromMapUrl(raw);
          if (mapCoords) return mapCoords;
          const queryText = getMapQueryText(raw);
          if (!queryText) return null;
          return resolveCoordsFromInput(queryText);
        }
        if (typeof google === 'undefined' || !google.maps || !google.maps.Geocoder) return null;
        const geocoder = new google.maps.Geocoder();
        try {
          const geoRes = await new Promise((resolve, reject) => {
            geocoder.geocode({ address: raw }, (results, status) => {
              if (status === 'OK' && results[0]) resolve(results[0]);
              else reject(status);
            });
          });
          const loc = geoRes.geometry && geoRes.geometry.location;
          if (!loc) return null;
          return { lat: Number(loc.lat()), lng: Number(loc.lng()) };
        } catch (err) {
          return null;
        }
      }

      function getDirectionLabel(coords, origin = DIRECTION_BASE_COORDS) {
        if (!coords || !Number.isFinite(coords.lat) || !Number.isFinite(coords.lng)) return '';
        const dx = coords.lng - origin.lng;
        const dy = coords.lat - origin.lat;
        if (Math.abs(dx) < 0.005 && Math.abs(dy) < 0.005) return 'CTR';
        const angle = (Math.atan2(dx, dy) * 180 / Math.PI + 360) % 360;
        const sectors = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        return sectors[Math.round(angle / 45) % 8];
      }

      function getDistanceKm(coords, origin = DIRECTION_BASE_COORDS) {
        if (!coords || !Number.isFinite(coords.lat) || !Number.isFinite(coords.lng)) return '';
        const toRad = (deg) => deg * Math.PI / 180;
        const earthRadiusKm = 6371;
        const dLat = toRad(coords.lat - origin.lat);
        const dLng = toRad(coords.lng - origin.lng);
        const a = Math.sin(dLat / 2) ** 2
          + Math.cos(toRad(origin.lat)) * Math.cos(toRad(coords.lat)) * Math.sin(dLng / 2) ** 2;
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        const distance = earthRadiusKm * c;
        if (distance < 1) return `${Math.round(distance * 1000)}m`;
        return `${distance.toFixed(distance < 10 ? 1 : 0)}km`;
      }

      function getDistanceKmValue(coords, origin = DIRECTION_BASE_COORDS) {
        if (!coords || !Number.isFinite(coords.lat) || !Number.isFinite(coords.lng)) return 0;
        const toRad = (deg) => deg * Math.PI / 180;
        const earthRadiusKm = 6371;
        const dLat = toRad(coords.lat - origin.lat);
        const dLng = toRad(coords.lng - origin.lng);
        const a = Math.sin(dLat / 2) ** 2
          + Math.cos(toRad(origin.lat)) * Math.cos(toRad(coords.lat)) * Math.sin(dLng / 2) ** 2;
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return earthRadiusKm * c;
      }

      function latLngToWorldPoint(lat, lng) {
        const tileSize = 256;
        const siny = Math.min(Math.max(Math.sin(lat * Math.PI / 180), -0.9999), 0.9999);
        return {
          x: tileSize * (0.5 + lng / 360),
          y: tileSize * (0.5 - Math.log((1 + siny) / (1 - siny)) / (4 * Math.PI))
        };
      }

      function getMapViewport(originCoords, targetCoords, width, height) {
        const p1 = latLngToWorldPoint(originCoords.lat, originCoords.lng);
        const p2 = latLngToWorldPoint(targetCoords.lat, targetCoords.lng);
        const dx = Math.abs(p1.x - p2.x);
        const dy = Math.abs(p1.y - p2.y);
        const padX = width * 0.18;
        const padY = height * 0.22;
        const usableW = Math.max(80, width - (padX * 2));
        const usableH = Math.max(80, height - (padY * 2));

        const zoomX = dx > 0 ? Math.log2(usableW / (dx * 256 / 256)) : 16;
        const zoomY = dy > 0 ? Math.log2(usableH / (dy * 256 / 256)) : 16;
        let zoom = Math.floor(Math.min(zoomX, zoomY, 16));
        if (!Number.isFinite(zoom)) zoom = 11;
        zoom = Math.max(7, Math.min(16, zoom));

        return {
          center: {
            lat: (originCoords.lat + targetCoords.lat) / 2,
            lng: (originCoords.lng + targetCoords.lng) / 2
          },
          zoom
        };
      }

      function buildStaticBangaloreMapUrl(coords) {
        if (!coords || !Number.isFinite(coords.lat) || !Number.isFinite(coords.lng)) return '';
        const viewport = getMapViewport(DIRECTION_BASE_COORDS, coords, 720, 480);
        const centerLat = viewport.center.lat.toFixed(6);
        const centerLng = viewport.center.lng.toFixed(6);
        const params = new URLSearchParams({
          center: `${centerLat},${centerLng}`,
          zoom: String(viewport.zoom),
          size: '720x480',
          scale: '2',
          maptype: 'roadmap',
          key: GOOGLE_MAPS_API_KEY
        });
        params.append('style', 'feature:poi|visibility:off');
        params.append('style', 'feature:transit|visibility:off');
        params.append('style', 'feature:road|element:geometry|color:0xcbd5e1');
        params.append('style', 'feature:road|element:labels.text.fill|color:0x334155');
        params.append('style', 'feature:water|element:geometry|color:0x93c5fd');
        params.append('style', 'feature:landscape|element:geometry|color:0xf1f5f9');
        params.append('style', 'feature:administrative|element:labels.text.fill|color:0x0f172a');
        params.append('markers', `size:mid|color:blue|label:H|${DIRECTION_BASE_COORDS.lat},${DIRECTION_BASE_COORDS.lng}`);
        params.append('markers', `size:mid|color:red|label:I|${coords.lat},${coords.lng}`);
        return `https://maps.googleapis.com/maps/api/staticmap?${params.toString()}`;
      }

      function projectLatLngToStaticMapPoint(coords, center, zoom, width, height) {
        const tileSize = 256;
        const scale = tileSize * Math.pow(2, zoom);
        const worldPoint = latLngToWorldPoint(coords.lat, coords.lng);
        const centerPoint = latLngToWorldPoint(center.lat, center.lng);
        return {
          x: ((worldPoint.x - centerPoint.x) * (scale / tileSize)) + (width / 2),
          y: ((worldPoint.y - centerPoint.y) * (scale / tileSize)) + (height / 2)
        };
      }

      async function ensureDirectionsForNotes(noteList) {
        const pending = [];
        let queuedCount = 0;
        noteList.forEach((note) => {
          const key = String(note.id || '');
          if (!key || geoCache.has(key)) return;
          const mapLink = detectMapLink(note);
          if (!mapLink) {
            geoCache.set(key, null);
            return;
          }
          queuedCount += 1;
          pending.push((async () => {
            const mapSource = getNoteMapSource(note);
            let coords = await resolveCoordsFromInput(mapLink);
            if (!coords) coords = await resolveCoordsFromInput(mapSource.locationText || mapSource.mapText);
            if (!coords) {
              geoCache.set(key, null);
              return;
            }
            const areaName = await resolveAreaNameFromInput(mapSource.locationText || mapSource.mapText || mapLink);
            geoCache.set(key, {
              direction: getDirectionLabel(coords),
              distance: getDistanceKm(coords),
              distanceKmValue: getDistanceKmValue(coords),
              coords,
              areaName
            });
          })());
        });
        if (!pending.length) return false;
        await Promise.allSettled(pending);
        return queuedCount > 0;
      }

      function extractCoordsFromText(text) {
        const value = String(text || '').trim();
        if (!value) return null;
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
          if (match) return { lat: Number(match[1]), lng: Number(match[2]) };
        }
        const direct = value.match(/(-?\d{1,2}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/);
        return direct ? { lat: Number(direct[1]), lng: Number(direct[2]) } : null;
      }

      function isGoogleMapsLikeUrl(text) {
        return /https?:\/\/[^\s]*(?:google\.[^/\s]+\/maps|google\.com\/maps|maps\.app|goo\.gl)/i.test(String(text || '').trim());
      }

      async function resolveCoordsFromMapUrl(mapUrl) {
        const url = String(mapUrl || '').trim();
        if (!url || !isGoogleMapsLikeUrl(url)) return null;
        const direct = extractCoordsFromText(url);
        if (direct) return direct;
        try {
          const res = await fetch(`resolve_map_url.php?url=${encodeURIComponent(url)}`, { cache: 'force-cache' });
          const data = await res.json();
          return data && data.success && data.coords ? data.coords : null;
        } catch (err) {
          return null;
        }
      }

      function getVisibleNotes() {
        const visible = notes.filter((n) => {
          const type = normalizeType(n.type);
          const expired = isExpiredNote(n);
          if (!showClosedNotes && isDoneNote(n) && !expired) {
            return false;
          }
          if (currentFilter === 'all') return true;
          return type === currentFilter;
        });
        visible.sort((a, b) => {
          if (priorityGroupActive) {
            const priorityDiff = normalizePriorityLevel(b.priority_level) - normalizePriorityLevel(a.priority_level);
            if (priorityDiff !== 0) return priorityDiff;
          }
          if (directionGroupActive) {
            const dirDiff = getDirectionSortRank(a) - getDirectionSortRank(b);
            if (dirDiff !== 0) return dirDiff;
            const distanceDiff = getDirectionDistanceValue(a) - getDirectionDistanceValue(b);
            if (distanceDiff !== 0) return distanceDiff;
          }
          const age = currentSort === 'asc' ? ageDays(a) - ageDays(b) : ageDays(b) - ageDays(a);
          if (age !== 0) return age;
          return (Number(a.sort_order) || 0) - (Number(b.sort_order) || 0);
        });
        return visible;
      }

      function getNoteGeoInfo(note) {
        return geoCache.get(String((note && note.id) || '')) || null;
      }

      function getDirectionLabelForNote(note) {
        const noteGeo = getNoteGeoInfo(note);
        const direction = String((noteGeo && noteGeo.direction) || '').trim().toUpperCase();
        return direction || 'UNMAPPED';
      }

      function getDirectionSortRank(note) {
        const order = {
          CTR: 0,
          N: 1,
          NE: 2,
          E: 3,
          SE: 4,
          S: 5,
          SW: 6,
          W: 7,
          NW: 8,
          UNMAPPED: 99
        };
        const label = getDirectionLabelForNote(note);
        return Object.prototype.hasOwnProperty.call(order, label) ? order[label] : 98;
      }

      function getDirectionDistanceValue(note) {
        const noteGeo = getNoteGeoInfo(note);
        if (noteGeo && Number.isFinite(Number(noteGeo.distanceKmValue))) {
          return Number(noteGeo.distanceKmValue);
        }
        return Number.MAX_SAFE_INTEGER;
      }

      function getDirectionGroupTitle(note) {
        const label = getDirectionLabelForNote(note);
        return label === 'UNMAPPED' ? 'Unmapped' : label;
      }

      function getPriorityOrderedNotes(noteList) {
        return [...(Array.isArray(noteList) ? noteList : [])].sort((a, b) => {
          const priorityDiff = normalizePriorityLevel(b.priority_level) - normalizePriorityLevel(a.priority_level);
          if (priorityDiff !== 0) return priorityDiff;
          const age = currentSort === 'asc' ? ageDays(a) - ageDays(b) : ageDays(b) - ageDays(a);
          if (age !== 0) return age;
          return (Number(a.sort_order) || 0) - (Number(b.sort_order) || 0);
        });
      }

      function buildPlanningSummary(noteList) {
        const openNotes = getPriorityOrderedNotes((noteList || []).filter((note) => !isDoneNote(note)));
        if (!openNotes.length) {
          return '';
        }
        return openNotes.map((note) => {
          const noteParts = extractNoteDisplayParts(note);
          const noteGeo = geoCache.get(String(note.id || '')) || null;
          const mapSource = getNoteMapSource(note);
          const direction = String((noteGeo && noteGeo.direction) || '').trim();
          const distance = String((noteGeo && noteGeo.distance) || '').trim();
          const mapLocation = String(mapSource.displayText || mapSource.mapText || detectMapLink(note) || '').trim();
          const name = String(noteParts.customerName || '').trim();
          const phone = String(noteParts.phone || detectPhone(note) || '').trim();
          const area = String((noteGeo && noteGeo.areaName) || getDisplayLocationText(noteParts.apiPlace) || noteParts.place || '').trim();
          const issue = String(noteParts.issueDescription || note.title || '').trim();
          return [
            `Priority: ${getPriorityText(note.priority_level)}`,
            `Direction: ${direction}`,
            `KM: ${distance}`,
            `Map Location: ${mapLocation}`,
            `Name: ${name}`,
            `Phone: ${phone}`,
            `area: ${area}`,
            `Issue: ${issue}`
          ].join('\n');
        }).join('\n\n');
      }

      function updatePlanningSummary(noteList) {
        if (!planningTextarea) return;
        planningTextarea.value = buildPlanningSummary(noteList);
      }

      async function copyPlanningSummary() {
        if (!planningTextarea) return false;
        const text = String(planningTextarea.value || '').trim();
        if (!text) return false;
        try {
          if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            await navigator.clipboard.writeText(text);
            return true;
          }
        } catch (err) {
        }
        try {
          planningTextarea.focus();
          planningTextarea.select();
          planningTextarea.setSelectionRange(0, planningTextarea.value.length);
          return document.execCommand('copy');
        } catch (err) {
          return false;
        }
      }

      function isDoneNote(note) {
        return !!(note && note.is_done && String(note.is_done) !== '0');
      }

      function buildOpenDuplicatePhoneIndex(noteList) {
        const byPhone = new Map();
        (Array.isArray(noteList) ? noteList : []).forEach((note) => {
          if (isDoneNote(note)) return;
          const phone = detectPhone(note);
          if (!phone) return;
          if (!byPhone.has(phone)) byPhone.set(phone, []);
          byPhone.get(phone).push(note);
        });

        const duplicateById = {};
        byPhone.forEach((items, phone) => {
          if (items.length < 2) return;
          items.forEach((note) => {
            duplicateById[String(note.id || '')] = {
              phone,
              count: items.length
            };
          });
        });
        return duplicateById;
      }

      function buildSequenceLabels(noteList) {
        const counters = { issue: 0, inspection: 0, general: 0 };
        const labelsById = {};
        const orderedForSerial = [...noteList].sort((a, b) => {
          const ageDiff = ageDays(a) - ageDays(b);
          if (ageDiff !== 0) return ageDiff;
          const aTime = new Date(a.created_at || a.date || 0).getTime() || 0;
          const bTime = new Date(b.created_at || b.date || 0).getTime() || 0;
          if (aTime !== bTime) return bTime - aTime;
          const aId = Number(a.id) || 0;
          const bId = Number(b.id) || 0;
          return bId - aId;
        });
        orderedForSerial.forEach((note) => {
          if (isDoneNote(note)) {
            labelsById[String(note.id || '')] = '';
            return;
          }
          const type = normalizeType(note.type);
          counters[type] += 1;
          const serial = counters[type];
          labelsById[String(note.id || '')] = type === 'inspection' ? `#${serial}` : String(serial);
        });
        return labelsById;
      }

      function buildMapSequenceLabels(noteList) {
        const labelsById = {};
        let serial = 0;
        (Array.isArray(noteList) ? noteList : []).forEach((note) => {
          if (isDoneNote(note)) {
            labelsById[String(note.id || '')] = '';
            return;
          }
          serial += 1;
          labelsById[String(note.id || '')] = String(serial);
        });
        return labelsById;
      }

      function buildMapPopupCard(note, label) {
        const type = normalizeType(note.type);
        const niceType = type.charAt(0).toUpperCase() + type.slice(1);
        const noteParts = extractNoteDisplayParts(note);
        const lineBits = [];
        if (noteParts.phone) lineBits.push(noteParts.phone);
        if (noteParts.issueDescription) lineBits.push(noteParts.issueDescription);
        const card = document.createElement('article');
        card.className = `issues-map-card ${type}`;
        card.innerHTML = `
          <span class="issues-map-card-badge">${escapeHtml(label)}</span>
          <div class="issues-map-card-head">
            <span class="issues-map-card-type ${type}">${escapeHtml(niceType)}</span>
            <div>${escapeHtml(noteParts.customerName || note.title || '')}</div>
          </div>
          ${(noteParts.place || lineBits.length) ? `<div class="issues-map-card-desc"><strong>${escapeHtml(noteParts.place || '')}</strong>${lineBits.length ? ` | ${escapeHtml(lineBits.join(' | '))}` : ''}</div>` : ''}
          <div class="issues-map-card-meta"><i class="fas fa-map-pin" style="margin-right:4px;"></i>${escapeHtml((geoCache.get(String(note.id || '')) || {}).areaName || getNoteMapSource(note).displayText || 'Map attached')}</div>
        `;
        return card;
      }

      async function buildMapLocations(noteList, labelsById) {
        const locations = [];
        for (const note of noteList) {
          if (isDoneNote(note)) continue;
          const mapLink = detectMapLink(note);
          if (!mapLink) continue;
          const mapSource = getNoteMapSource(note);
          let coords = mapLink ? await resolveCoordsFromInput(mapLink) : null;
          if (!coords) coords = await resolveCoordsFromInput(mapSource.locationText || mapSource.mapText);
          if (!coords) continue;
          locations.push({
            label: labelsById[String(note.id || '')] || '',
            note,
            type: normalizeType(note.type),
            coords,
            mapLink
          });
        }
        return locations;
      }

      function openMapPopup(noteList, locations, labelsById) {
        const existing = document.getElementById('issues-map-popup');
        if (existing) existing.remove();

        const backdrop = document.createElement('div');
        backdrop.id = 'issues-map-popup';
        backdrop.className = 'issues-map-popup';

        const closeBtn = document.createElement('button');
        closeBtn.className = 'issues-map-close';
        closeBtn.innerHTML = '<i class="fas fa-times"></i>';
        backdrop.appendChild(closeBtn);

        const mapEl = document.createElement('div');
        mapEl.className = 'issues-map-canvas';
        backdrop.appendChild(mapEl);

        const cardsEl = document.createElement('div');
        cardsEl.className = 'issues-map-cards';
        backdrop.appendChild(cardsEl);

        const markerByNoteId = {};
        noteList.forEach((note) => {
          const locationEntry = locations.find((entry) => Number(entry.note.id) === Number(note.id));
          const label = labelsById[String(note.id || '')] || 'NA';
          const card = buildMapPopupCard(note, label);
          if (!locationEntry) card.classList.add('no-map');
          card.onclick = () => {
            const markerMeta = markerByNoteId[String(note.id || '')];
            if (!markerMeta) return;
            Object.values(markerByNoteId).forEach((m) => m.infoWindow.close());
            markerMeta.infoWindow.open(markerMeta.map, markerMeta.marker);
            markerMeta.map.panTo(markerMeta.marker.getPosition());
          };
          cardsEl.appendChild(card);
        });

        const closePopup = () => {
          backdrop.remove();
          document.body.style.overflow = '';
        };
        closeBtn.onclick = closePopup;
        backdrop.onclick = (e) => { if (e.target === backdrop) closePopup(); };
        const escHandler = (e) => {
          if (e.key === 'Escape') {
            closePopup();
            document.removeEventListener('keydown', escHandler);
          }
        };
        document.addEventListener('keydown', escHandler);

        document.body.appendChild(backdrop);
        document.body.style.overflow = 'hidden';

        const defaultCenter = { lat: 12.9716, lng: 77.5946 };
        const map = new google.maps.Map(mapEl, {
          zoom: 11,
          center: defaultCenter,
          mapTypeId: 'roadmap',
          styles: [{ featureType: 'poi', stylers: [{ visibility: 'simplified' }] }]
        });
        const bounds = new google.maps.LatLngBounds();

        locations.forEach((entry) => {
          const marker = new google.maps.Marker({
            position: entry.coords,
            map,
            label: {
              text: entry.label,
              color: '#fff',
              fontWeight: 'bold'
            },
            icon: {
              path: google.maps.SymbolPath.CIRCLE,
              scale: entry.type === 'inspection' ? 16 : 14,
              fillColor: entry.type === 'inspection' ? '#f97316' : '#e53935',
              fillOpacity: 1,
              strokeColor: '#fff',
              strokeWeight: entry.type === 'inspection' ? 3 : 2
            },
            title: entry.note.title || `Location ${entry.label}`
          });
          const infoWindow = new google.maps.InfoWindow({
            content: `
              <div style="padding:12px;min-width:220px;max-width:300px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                  <span style="background:${entry.type === 'inspection' ? '#f97316' : '#e53935'};color:#fff;min-width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:12px;">${escapeHtml(entry.label)}</span>
                  <strong style="font-size:14px;color:#333;">${escapeHtml(entry.note.title || '')}</strong>
                </div>
                <div style="font-size:12px;color:#555;margin-bottom:10px;">${escapeHtml(entry.note.description || '')}</div>
                <a href="${escapeHtml(entry.mapLink)}" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:6px;background:#4285f4;color:#fff;text-decoration:none;padding:8px 12px;border-radius:6px;font-size:13px;">
                  <i class="fas fa-external-link-alt"></i> Open in Google Maps
                </a>
              </div>
            `
          });
          marker.addListener('click', () => {
            Object.values(markerByNoteId).forEach((m) => m.infoWindow.close());
            infoWindow.open(map, marker);
          });
          markerByNoteId[String(entry.note.id || '')] = { marker, infoWindow, map };
          bounds.extend(entry.coords);
        });

        if (!bounds.isEmpty()) map.fitBounds(bounds);
      }

      async function openNotesMapView() {
        if (titleCardEl) titleCardEl.classList.add('map-loading');
        if (mapViewBtn) mapViewBtn.disabled = true;
        try {
        const noteList = getVisibleNotes();
        const openNotes = noteList.filter((note) => !isDoneNote(note));
        const labelsById = buildMapSequenceLabels(openNotes);
        const locations = await buildMapLocations(openNotes, labelsById);
        if (!locations.length) {
          alert('No notes with map locations found.');
          return;
        }
        openMapPopup(openNotes, locations, labelsById);
        } finally {
          if (titleCardEl) titleCardEl.classList.remove('map-loading');
          if (mapViewBtn) mapViewBtn.disabled = false;
        }
      }

      function renderState(message, isError) {
        stateEl.innerHTML = message
          ? `<div class="${isError ? 'error' : 'empty'}">${escapeHtml(message)}</div>`
          : '';
      }

      function renderNotes() {
        const visible = getVisibleNotes();
        const labelsById = buildSequenceLabels(visible);
        const duplicatePhonesById = buildOpenDuplicatePhoneIndex(notes);

        listEl.innerHTML = '';
        updateFilterCounts();
        updatePlanningSummary(visible);
        if (!visible.length) {
          const prefix = showClosedNotes ? 'No notes' : 'No open notes';
          renderState(selectedDate === TODAY ? `${prefix} for today.` : `${prefix} for ${formatDateLabel(selectedDate)}.`);
          return;
        }
        renderState('');

        let currentAgeKey = null;
        let currentDirectionKey = null;
        let groupEl = null;
        const expiredTypeLabels = { issue: 'Expired Issues', inspection: 'Expired Inspections', general: 'Expired General' };
        const expiredGroups = { issue: [], inspection: [], general: [] };
        visible.forEach((note) => {
          if (!isExpiredNote(note)) return;
          const type = normalizeType(note.type);
          if (expiredGroups[type]) expiredGroups[type].push(note);
        });
        const expiredRegion = document.createElement('section');
        expiredRegion.className = 'expired-notes-region';
        const expiredBodies = {};
        ['issue', 'inspection', 'general'].forEach((type) => {
          if (!expiredGroups[type].length) return;
          const panel = document.createElement('details');
          panel.className = `expired-notes-panel ${type}`;
          panel.innerHTML = `
            <summary>
              <span>${escapeHtml(expiredTypeLabels[type])}</span>
              <span class="expired-notes-count">${expiredGroups[type].length}</span>
            </summary>
            <div class="expired-notes-body"></div>
          `;
          expiredBodies[type] = panel.querySelector('.expired-notes-body');
          expiredRegion.appendChild(panel);
        });
        if (expiredRegion.children.length) {
          listEl.appendChild(expiredRegion);
        }

        visible.forEach((note) => {
          const type = normalizeType(note.type);
          const niceType = type.charAt(0).toUpperCase() + type.slice(1);
          const done = !!(note.is_done && String(note.is_done) !== '0');
          const closedByLabel = String(note.done_by || '').trim();
          const phone = detectPhone(note);
          const duplicateMeta = !done ? duplicatePhonesById[String(note.id || '')] : null;
          const mapLink = done ? '' : detectMapLink(note);
          const serialLabel = labelsById[String(note.id || '')] || '';
          const assignedTo = String(note.assigned_to || '').trim().toUpperCase();
          const assignedHistory = String(note.assigned_history || '').trim().replace(/\s*\|\s*/g, ', ');
          const assigneePalette = getAssigneePalette(assignedTo);
          const noteParts = extractNoteDisplayParts(note);
          const noteIconHtml = buildNoteIconHtml(noteParts.icons || []);
          const priorityMeta = getPriorityMeta(note.priority_level);
          const noteAge = ageDays(note);
          const expired = isExpiredNote(note);
          const directionGroupTitle = getDirectionGroupTitle(note);
          const nextGroupKey = directionGroupActive ? directionGroupTitle : String(noteAge);
          if (!expired && ((directionGroupActive && currentDirectionKey !== nextGroupKey) || (!directionGroupActive && currentAgeKey !== noteAge))) {
            currentDirectionKey = directionGroupActive ? nextGroupKey : null;
            currentAgeKey = directionGroupActive ? null : noteAge;
            groupEl = document.createElement('section');
            groupEl.className = 'age-group';
            groupEl.innerHTML = `<div class="age-separator">${escapeHtml(directionGroupActive ? directionGroupTitle : getAgeLabel(noteAge))}</div>`;
            listEl.appendChild(groupEl);
          }
          const assigneeOptionsHtml = ['<option value="">Assign</option>']
            .concat(ASSIGNEE_OPTIONS.map((name) => `<option value="${escapeHtml(name)}"${name === assignedTo ? ' selected' : ''}>${escapeHtml(name)}</option>`))
            .join('');
          const assigneeSelectHtml = `
            <select class="note-assignee-select" data-assign-id="${Number(note.id)}" style="background:${escapeHtml(assigneePalette.bg)};color:${escapeHtml(assigneePalette.fg)};">
              ${assigneeOptionsHtml}
            </select>
          `;
          const assigneeHistoryHtml = assignedHistory
            ? `<span class="note-assignee-history">${escapeHtml(assignedHistory)}</span>`
            : '';
          const contactHtml = phone
            ? `<span class="note-contact">
                 <a class="contact-link call" href="tel:${phone}" title="Call ${phone}" aria-label="Call ${phone}">
                   <i class="fa-solid fa-phone" aria-hidden="true"></i>
                 </a>
                 <a class="contact-link wa" href="${buildWhatsAppLink(phone)}" target="_blank" rel="noopener" title="WhatsApp ${phone}" aria-label="WhatsApp ${phone}">
                   <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                 </a>
                 ${mapLink ? `<a class="contact-link map" href="${escapeHtml(mapLink)}" target="_blank" rel="noopener" title="Open map" aria-label="Open map">
                   <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                 </a>` : ''}
               </span>`
            : (mapLink
              ? `<span class="note-contact">
                   <a class="contact-link map" href="${escapeHtml(mapLink)}" target="_blank" rel="noopener" title="Open map" aria-label="Open map">
                     <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                   </a>
                 </span>`
              : '');
          const div = document.createElement('article');
          const isInlineMapOpen = openInlineMapIds.has(String(note.id || ''));
          div.className = `note-card ${type}${done ? ' is-done' : ''}${duplicateMeta ? ' is-duplicate' : ''}${isInlineMapOpen ? ' map-inline-open' : ''}`;
          if (assignedTo) {
            div.style.background = assigneePalette.soft;
            div.style.borderColor = assigneePalette.border;
            div.style.boxShadow = `0 2px 8px ${assigneePalette.border}22`;
          }
          if (duplicateMeta) {
            div.style.borderColor = '#f87171';
            div.style.boxShadow = '0 0 0 2px rgba(220, 38, 38, 0.12), 0 6px 18px rgba(15, 23, 42, 0.08)';
          }
          const titleLineBits = [];
          if (noteParts.phone) titleLineBits.push(escapeHtml(noteParts.phone));
          const noteGeo = geoCache.get(String(note.id || '')) || null;
          const mapSource = getNoteMapSource(note);
          const apiPlaceText = String(
            (noteGeo && noteGeo.areaName)
            || getDisplayLocationText(noteParts.apiPlace)
            || mapSource.displayText
            || ''
          ).trim();
          const basePlaceText = String(noteParts.place || noteParts.shortTitle || note.title || '').trim();
          const placeHtml = basePlaceText
            ? `<strong class="note-place">${escapeHtml(basePlaceText)}</strong>${apiPlaceText && apiPlaceText.toLowerCase() !== basePlaceText.toLowerCase() ? ` <span class="note-title-meta">(${escapeHtml(apiPlaceText)})</span>` : ''}`
            : (apiPlaceText ? `<span class="note-title-meta">${escapeHtml(apiPlaceText)}</span>` : '');
          const issueHtml = String(noteParts.issueDescription || '').trim()
            ? `<span class="note-issue-inline">${escapeHtml(noteParts.issueDescription)}</span>`
            : '';
          let geoPlotHtml = '';
          if (noteGeo && noteGeo.coords) {
            const staticMapUrl = buildStaticBangaloreMapUrl(noteGeo.coords);
            const viewport = getMapViewport(DIRECTION_BASE_COORDS, noteGeo.coords, 720, 480);
            const hsrPoint = projectLatLngToStaticMapPoint(DIRECTION_BASE_COORDS, viewport.center, viewport.zoom, 720, 480);
            const issuePoint = projectLatLngToStaticMapPoint(noteGeo.coords, viewport.center, viewport.zoom, 720, 480);
            geoPlotHtml = `
              <div class="note-geo-plot" title="HSR Layout to issue location">
                ${staticMapUrl ? `<a href="${escapeHtml(mapLink)}" target="_blank" rel="noopener" title="Open actual map location"><img class="note-geo-map" src="${escapeHtml(staticMapUrl)}" alt="Bangalore map from HSR Layout to issue"></a>` : ''}
                <svg class="note-geo-overlay" viewBox="0 0 720 480" preserveAspectRatio="none" aria-hidden="true">
                  <line x1="${hsrPoint.x.toFixed(1)}" y1="${hsrPoint.y.toFixed(1)}" x2="${issuePoint.x.toFixed(1)}" y2="${issuePoint.y.toFixed(1)}" stroke="#ea580c" stroke-width="2" stroke-dasharray="6 6" stroke-linecap="round"></line>
                  <circle cx="${hsrPoint.x.toFixed(1)}" cy="${hsrPoint.y.toFixed(1)}" r="11" fill="#2563eb" stroke="#ffffff" stroke-width="3"></circle>
                  <circle cx="${issuePoint.x.toFixed(1)}" cy="${issuePoint.y.toFixed(1)}" r="11" fill="#dc2626" stroke="#ffffff" stroke-width="3"></circle>
                </svg>
                <div class="note-geo-meta">
                  <span class="note-geo-pill">${escapeHtml(noteGeo.direction || 'CTR')} | ${escapeHtml(noteGeo.distance || '')}</span>
                  <span class="note-geo-meta-text">HSR Layout to issue</span>
                </div>
              </div>`;
          }
          const headChips = [];
          if (duplicateMeta) {
            headChips.push(`<span class="note-chip duplicate" title="${escapeHtml(`Open duplicate phone: ${duplicateMeta.phone}`)}">Duplicate x${Number(duplicateMeta.count) || 2}</span>`);
          }
          const noteGeoMeta = getNoteGeoInfo(note);
          const directionText = String((noteGeoMeta && noteGeoMeta.direction) || '').trim().toUpperCase();
          const distanceText = String((noteGeoMeta && noteGeoMeta.distance) || '').trim().toUpperCase();
          if (directionText || distanceText) {
            const geoHeadText = [directionText, distanceText].filter(Boolean).join(' | ');
            if (geoHeadText) headChips.push(`<span class="note-chip">${escapeHtml(geoHeadText)}</span>`);
          }
          if (noteParts.hdd) headChips.push(`<span class="note-chip">${escapeHtml(noteParts.hdd)}</span>`);
          if (noteParts.cams || noteParts.resolution) {
            const camsRes = [noteParts.cams, noteParts.resolution].filter(Boolean).join(' x ');
            if (camsRes) headChips.push(`<span class="note-chip">${escapeHtml(camsRes)}</span>`);
          }
          const noteSourceDate = String(noteParts.sourceDate || note.date || '').trim();
          const noteChipDate = formatShortDate(noteSourceDate);
          if (noteChipDate) headChips.push(`<span class="note-chip">${escapeHtml(noteChipDate)}</span>`);
          if (noteParts.owner) headChips.push(`<span class="note-chip">${escapeHtml(noteParts.owner)}</span>`);
          const noteAgeText = formatDateLabel(noteSourceDate) || getAgeChipLabel(noteAge);
          div.innerHTML = `
            <div class="note-head">
              <div class="note-head-main">
                <span class="note-serial">${escapeHtml(serialLabel)}</span>
                <span class="note-type ${type}">${escapeHtml(niceType)}</span>
                ${contactHtml}
              </div>
              ${headChips.length ? `<div class="note-head-meta">${headChips.join('')}</div>` : ''}
            </div>
            <div class="note-age-text">${escapeHtml(noteAgeText)}</div>
            <div class="note-customer-row">
              <button
                class="note-priority-toggle ${priorityMeta.className}"
                type="button"
                data-priority-id="${Number(note.id)}"
                data-priority-level="${priorityMeta.level}"
                title="${escapeHtml(priorityMeta.label)}"
                aria-label="${escapeHtml(priorityMeta.label)}"
              ></button>
              <div class="note-title-block">
                <div class="note-customer${noteParts.customerName ? '' : ' empty'}">${escapeHtml(noteParts.customerName)}</div>
                ${noteIconHtml}
              </div>
            </div>
            <p class="note-title">
              ${placeHtml}${titleLineBits.length ? ` <span class="note-title-meta">| ${titleLineBits.join(' | ')}</span>` : ''}${issueHtml ? ` <span class="note-title-meta">|</span> ${issueHtml}` : ''}
            </p>
            <div class="note-desc"></div>
            <div class="note-foot">
              <span class="note-meta-line">
                ${geoPlotHtml ? `<button class="note-map-inline-toggle" type="button" data-note-map-toggle="${Number(note.id)}" aria-label="Toggle inline map" aria-expanded="${isInlineMapOpen ? 'true' : 'false'}"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></button>` : ''}
                <span class="note-by">${escapeHtml(note.username ? ('By: ' + note.username) : 'By: Unknown')}</span>
                ${assigneeSelectHtml}
                ${assigneeHistoryHtml}
              </span>
              <span class="note-actions-inline">
                ${done
                  ? `<span class="done-state"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Done</span>
                     <button class="reopen-btn" type="button" data-reopen-id="${Number(note.id)}" title="Reopen note" aria-label="Reopen note">
                       <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                     </button>`
                  : `<button class="done-btn" type="button" data-id="${Number(note.id)}" title="Mark done" aria-label="Mark done">
                       <i class="fa-solid fa-check" aria-hidden="true"></i>
                     </button>`}
                <button class="edit-btn" type="button" data-edit-id="${Number(note.id)}" title="Edit note" aria-label="Edit note">
                  <i class="fa-solid fa-pen" aria-hidden="true"></i>
                </button>
              </span>
            </div>
            ${geoPlotHtml ? `<div class="note-inline-map-wrap">${geoPlotHtml}</div>` : ''}
            ${done && closedByLabel ? `<div class="done-by">Closed by: ${escapeHtml(closedByLabel)}</div>` : ''}
            ${!done && note.reopened_by ? `<div class="done-by">Reopened by: ${escapeHtml(note.reopened_by)}${note.reopen_reason ? ` | Why: ${escapeHtml(note.reopen_reason)}` : ''}</div>` : ''}
          `;
          (expired ? (expiredBodies[type] || expiredRegion || listEl) : (groupEl || listEl)).appendChild(div);
        });
        ensureDirectionsForNotes(visible).then((didUpdate) => {
          if (didUpdate) renderNotes();
        });
      }

      async function markDone(id) {
        const res = await fetch(NOTES_API, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: Number(id),
            is_done: 1,
            username: AUTH_USERNAME || AUTH_DISPLAY || 'Unknown'
          })
        });
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to update note');
      }

      async function reopenNote(id, reopenReason) {
        const res = await fetch(NOTES_API, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: Number(id),
            is_done: 0,
            reopen_reason: reopenReason,
            username: AUTH_USERNAME || AUTH_DISPLAY || 'Unknown'
          })
        });
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to reopen note');
      }

      async function createNote() {
        const id = Number(editIdEl.value || 0);
        const type = (newTypeEl.value || 'Issue').trim();
        const title = (newTitleEl.value || '').trim();
        const description = (newDescEl.value || '').trim();
        if (!title) throw new Error('Title is required');
        const draftPhone = detectPhoneFromDraft(title, description, newLookupEl ? newLookupEl.value : '');
        const res = await fetch(NOTES_API, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: id || undefined,
            type,
            title,
            description,
            date: selectedDate,
            phone: draftPhone,
            username: AUTH_USERNAME || AUTH_DISPLAY || 'Unknown'
          })
        });
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to create note');
      }

      async function loadNotes() {
        refreshBtn.disabled = true;
        refreshBtn.classList.add('is-loading');
        try {
          updateHeaderDate();
          const params = new URLSearchParams({
            date: selectedDate,
            t: String(Date.now())
          });
          if (selectedDate === TODAY) {
            params.set('include_expired', '1');
          }
          const res = await fetch(`${NOTES_API}?${params.toString()}`, { cache: 'no-store' });
          const data = await res.json();
          if (!res.ok || !data.success) throw new Error(data.message || 'Failed to fetch notes');

          notes = Array.isArray(data.notes) ? data.notes : [];
          refreshDateOptions();
          renderNotes();
          if (selectedDate === TODAY) {
            const expiredCount = notes.filter((note) => isExpiredNote(note)).length;
            if (expiredCount > 0) {
              renderState(`Showing ${expiredCount} expired record${expiredCount === 1 ? '' : 's'} from older dates.`);
            }
          }
        } catch (err) {
          notes = [];
          listEl.innerHTML = '';
          renderState((err && err.message) ? err.message : 'Unable to load notes.', true);
        } finally {
          refreshBtn.disabled = false;
          refreshBtn.classList.remove('is-loading');
        }
      }

      async function updateAssignedTo(id, assignedTo) {
        const res = await fetch(NOTES_API, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: Number(id),
            assigned_to: assignedTo,
            username: AUTH_USERNAME || AUTH_DISPLAY || 'Unknown'
          })
        });
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to assign note');
      }

      async function updatePriorityLevel(id, priorityLevel) {
        const res = await fetch(NOTES_API, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: Number(id),
            priority_level: normalizePriorityLevel(priorityLevel),
            username: AUTH_USERNAME || AUTH_DISPLAY || 'Unknown'
          })
        });
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to update priority');
      }

      filterButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
          currentFilter = btn.getAttribute('data-filter') || 'all';
          filterButtons.forEach((b) => b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'));
          renderNotes();
        });
      });

      refreshBtn.addEventListener('click', loadNotes);
      if (IS_ADMIN && yesterdayBtn) {
        yesterdayBtn.addEventListener('click', () => {
          selectedDate = shiftDate(TODAY, -1);
          refreshDateOptions();
          loadNotes();
        });
      }
      if (IS_ADMIN && twoDaysBtn) {
        twoDaysBtn.addEventListener('click', () => {
          selectedDate = shiftDate(TODAY, -2);
          refreshDateOptions();
          loadNotes();
        });
      }
      if (IS_ADMIN && dateSelectEl) {
        dateSelectEl.addEventListener('change', () => {
          selectedDate = dateSelectEl.value || TODAY;
          updateHeaderDate();
          loadNotes();
        });
      }
      if (priorityGroupBtn) {
        priorityGroupBtn.addEventListener('click', () => {
          priorityGroupActive = !priorityGroupActive;
          syncPriorityGroupButton();
          renderNotes();
        });
      }
      if (directionGroupBtn) {
        directionGroupBtn.addEventListener('click', async () => {
          directionGroupActive = !directionGroupActive;
          syncDirectionGroupButton();
          if (directionGroupActive) {
            const visible = getVisibleNotes();
            const didUpdate = await ensureDirectionsForNotes(visible);
            if (didUpdate) {
              renderNotes();
              return;
            }
          }
          renderNotes();
        });
      }
      if (IS_ADMIN && planningOpenBtn) {
        planningOpenBtn.addEventListener('click', async () => {
          const copied = await copyPlanningSummary();
          window.open(PLANNING_UTILITY_URL, '_blank', 'noopener');
          if (!copied) {
            alert('Opened the Utility chat. Copy the planning notes manually if clipboard access is blocked.');
          }
        });
      }
      if (mapViewBtn) mapViewBtn.addEventListener('click', openNotesMapView);
      if (showClosedToggleBtn) {
        syncShowClosedToggleButton();
        showClosedToggleBtn.addEventListener('click', () => {
          showClosedNotes = !showClosedNotes;
          syncShowClosedToggleButton();
          renderNotes();
        });
      }
      sortToggleBtn.addEventListener('click', () => {
        currentSort = currentSort === 'desc' ? 'asc' : 'desc';
        sortToggleBtn.classList.add('active');
        sortToggleBtn.innerHTML = currentSort === 'desc'
          ? '<i class="fa-solid fa-arrow-down-wide-short"></i>'
          : '<i class="fa-solid fa-arrow-up-wide-short"></i>';
        renderNotes();
      });
      syncDirectionGroupButton();
      renderNoteIconPicker();
      updateFilterCounts();

      listEl.addEventListener('click', async (e) => {
        const inlineMapToggleBtn = e.target.closest('[data-note-map-toggle]');
        const descToggleBtn = e.target.closest('.note-desc-toggle');
        const priorityBtn = e.target.closest('[data-priority-id]');
        const btn = e.target.closest('.done-btn');
        const reopenBtn = e.target.closest('.reopen-btn');
        const editBtn = e.target.closest('.edit-btn');
        if (inlineMapToggleBtn) {
          const noteId = String(inlineMapToggleBtn.getAttribute('data-note-map-toggle') || '').trim();
          if (!noteId) return;
          if (openInlineMapIds.has(noteId)) openInlineMapIds.delete(noteId);
          else openInlineMapIds.add(noteId);
          renderNotes();
          return;
        }
        if (descToggleBtn) {
          const noteCard = descToggleBtn.closest('.note-card');
          if (!noteCard) return;
          const isOpen = noteCard.classList.toggle('desc-open');
          descToggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
          descToggleBtn.setAttribute('aria-label', isOpen ? 'Hide issue description' : 'Show issue description');
          return;
        }
        if (priorityBtn) {
          const id = Number(priorityBtn.getAttribute('data-priority-id'));
          if (!id) return;
          const currentLevel = normalizePriorityLevel(priorityBtn.getAttribute('data-priority-level'));
          const nextLevel = currentLevel === 0 ? 1 : currentLevel === 1 ? 2 : 0;
          priorityBtn.disabled = true;
          try {
            await updatePriorityLevel(id, nextLevel);
            notes = notes.map((n) => (
              Number(n.id) === id ? {
                ...n,
                priority_level: nextLevel
              } : n
            ));
            renderNotes();
          } catch (err) {
            priorityBtn.disabled = false;
            alert((err && err.message) ? err.message : 'Failed to update priority');
          }
          return;
        }
        if (btn) {
          const id = Number(btn.getAttribute('data-id'));
          if (!id) return;
          btn.disabled = true;
          try {
            await markDone(id);
            notes = notes.map((n) => (
              Number(n.id) === id ? {
                ...n,
                is_done: 1,
                done_by: AUTH_DISPLAY || AUTH_USERNAME || 'Unknown'
              } : n
            ));
            renderNotes();
          } catch (err) {
            btn.disabled = false;
            alert((err && err.message) ? err.message : 'Failed to mark as done');
          }
          return;
        }
        if (reopenBtn) {
          const id = Number(reopenBtn.getAttribute('data-reopen-id'));
          if (!id) return;
          if (!window.confirm('Are you sure you want to reopen this case?')) return;
          const reopenReason = window.prompt('Why reopening?');
          if (reopenReason === null) return;
          if (!String(reopenReason).trim()) {
            alert('Reopen reason is required');
            return;
          }
          reopenBtn.disabled = true;
          try {
            await reopenNote(id, String(reopenReason).trim());
            notes = notes.map((n) => (
              Number(n.id) === id ? {
                ...n,
                is_done: 0,
                reopened_by: AUTH_USERNAME || AUTH_DISPLAY || 'Unknown',
                reopen_reason: String(reopenReason).trim()
              } : n
            ));
            renderNotes();
          } catch (err) {
            reopenBtn.disabled = false;
            alert((err && err.message) ? err.message : 'Failed to reopen note');
          }
          return;
        }
        if (editBtn) {
          const id = Number(editBtn.getAttribute('data-edit-id'));
          const note = notes.find((n) => Number(n.id) === id);
          if (!note) return;
          editIdEl.value = String(id);
          newTypeEl.value = note.type || 'Issue';
          if (newLookupEl) newLookupEl.value = detectPhone(note) || '';
          newTitleEl.value = note.title || '';
          newDescEl.value = note.description || '';
          syncIconPickerFromDescription();
          createMetaEl.textContent = `Editing note #${id}`;
          createBtn.innerHTML = '<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Update';
          openCreateForm();
        }
      });

      if (noteIconPickerEl) {
        noteIconPickerEl.addEventListener('click', (e) => {
          const btn = e.target.closest('.note-icon-chip');
          if (!btn) return;
          const key = normalizeNoteIconKey(btn.getAttribute('data-icon-key'));
          if (!key) return;
          const current = new Set(getSelectedNoteIcons());
          if (current.has(key)) current.delete(key);
          else current.add(key);
          updateDescriptionIcons(Array.from(current));
        });
      }

      if (newDescEl) {
        newDescEl.addEventListener('input', () => {
          syncIconPickerFromDescription();
        });
      }

      listEl.addEventListener('change', async (e) => {
        const select = e.target.closest('.note-assignee-select');
        if (!select) return;
        const id = Number(select.getAttribute('data-assign-id'));
        if (!id) return;
        const assignedTo = String(select.value || '').trim().toUpperCase();
        select.disabled = true;
        try {
          await updateAssignedTo(id, assignedTo);
          const note = notes.find((n) => Number(n.id) === id);
          if (note) {
            const oldAssigned = String(note.assigned_to || '').trim().toUpperCase();
            const currentHistory = String(note.assigned_history || '').trim().replace(/\s*\|\s*/g, ', ');
            let nextHistory = currentHistory;
            if (assignedTo && assignedTo !== oldAssigned) {
              nextHistory = currentHistory ? `${currentHistory}, ${assignedTo}` : assignedTo;
            }
            note.assigned_to = assignedTo;
            note.assigned_history = nextHistory;
          }
          renderNotes();
        } catch (err) {
          alert((err && err.message) ? err.message : 'Failed to assign note');
          renderNotes();
        }
      });

      createForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        createBtn.disabled = true;
        createBtn.textContent = 'Creating...';
        try {
          await createNote();
          resetCreateForm();
          closeCreateForm();
          await loadNotes();
        } catch (err) {
          alert((err && err.message) ? err.message : 'Failed to create note');
        } finally {
          createBtn.disabled = false;
          createBtn.innerHTML = '<i class="fa-solid fa-plus" aria-hidden="true"></i> Create';
        }
      });

      if (newLookupEl) {
        newLookupEl.addEventListener('input', (e) => searchOrders(e.target.value));
        newLookupEl.addEventListener('focus', () => {
          if (latestLookupResults.length && lookupSuggestionsEl) lookupSuggestionsEl.style.display = 'block';
        });
      }

      document.addEventListener('click', (e) => {
        if (!lookupSuggestionsEl || !newLookupEl) return;
        if (!lookupSuggestionsEl.contains(e.target) && e.target !== newLookupEl) {
          lookupSuggestionsEl.style.display = 'none';
        }
      }, { capture: true });

      if (formBackdropEl) {
        formBackdropEl.addEventListener('click', () => {
          closeCreateForm();
          if (!editIdEl.value) resetCreateForm();
        });
      }

      fabAddBtn.addEventListener('click', () => {
        const willOpen = !createForm.classList.contains('open');
        if (willOpen) {
          if (!editIdEl.value) resetCreateForm();
          openCreateForm();
        } else {
          closeCreateForm();
          if (!editIdEl.value) resetCreateForm();
        }
      });

      (function init() {
        refreshDateOptions();
        syncPriorityGroupButton();
        loadNotes();
      })();
    })();
  </script>
</body>
</html>
