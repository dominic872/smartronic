<?php
ob_start();
require_once '../auth.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../config.php';
requireSmartPageAccess('lead', $role, $authPages);

if (!function_exists('isElevatedRole')) {
    function isElevatedRole(string $role): bool {
        $role = strtolower(trim($role));
        return in_array($role, ['admin', 'manager'], true);
    }
}

// Set timezone to India
date_default_timezone_set('Asia/Kolkata');

$isAdmin = isElevatedRole($role);
$canDeleteLead = strtolower(trim((string)$role)) === 'admin';
$allowedSmartPages = getAllowedSmartPages($role, $authPages);
$authName = $_COOKIE['auth_name'] ?? '';
$authUser = $_COOKIE['auth_user'] ?? '';
$codeSource = $authName !== '' ? $authName : $authUser;
$letters = preg_replace('/[^a-zA-Z]/', '', $codeSource);
$letters = strtoupper($letters);
$userCode = $letters !== '' ? substr($letters, 0, 3) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
  <title>SM Leads | Enhanced</title>
  <link rel="icon" type="image/png" sizes="32x32" href="/content/uploads/2025/01/cropped-Site-Icon-32x32.png">
  <link rel="apple-touch-icon" href="/content/uploads/2025/01/cropped-Site-Icon-180x180.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="lead_list_styles_enhanced.css?v=2">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
  <style>
    @media (max-width: 768px) {
      #main-logo {
        content: url('https://smartronic.online/content/uploads/2025/01/smartronic_small_logo.png');
        width: 40px;
        height: 40px;
      }
    }
    /* Spec styles moved to lead_list_styles_enhanced.css */
    .lead-comments {
      font-size: 14px;
      color: #000;
      margin-top: 5px;
      padding: 5px;
      background-color: #f9f9f9;
      border-radius: 4px;
      border: 1px solid #ccc;
      width: 100%;
    }
    .qr-placeholder {
        display: none !important;
    }
    .lead-qr-fallback {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 96px;
        min-height: 96px;
        padding: 10px;
        border-radius: 10px;
        background: #eef6ff;
        color: #0f172a;
        border: 1px solid #bfdbfe;
        font-family: monospace;
        font-size: 14px;
        font-weight: 700;
        text-align: center;
        word-break: break-word;
    }
    .assign-badge {
        border: 1px solid #ccc;

        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        background-color: transparent;
        color: #555;
    }
    .assign-badge.is-sur {
        background-color: #f28c28;
        border-color: #f28c28;
        color: #fff;
    }
    .lead-avatar-section h3 {
        margin: 0;
        padding: 0;
    }
    .idno-badge {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        background-color: #f8f9fa;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin: 0 auto;
    }
    .id-letter {
        font-size: 24px;
        font-weight: 800;
        color: #4F46E5;
        line-height: 1;
        margin-bottom: 2px;
    }
    .id-number {
        font-size: 16px;
        font-weight: 700;
        color: #374151;
        line-height: 1;
    }
    .prominent {
        background-color: #fffacd !important;
        border: 1px solid #f0e68c !important;
    }
    .status-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-badge.status-not-interested, .status-badge.status-wrong-number, .status-badge.status-dnc {
        background-color: #ffcdd2 !important;
    }
    .status-badge.status-follow-up {
        background-color: #fff9c4 !important;
    }
    .status-badge.status-busy, .status-badge.status-not-answering, .status-badge.status-cut-the-call {
        background-color: #eeeeee !important;
    }
    .close-edit-btn {
        position: absolute;
        top: -15px;
        right: 5px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: white;
        border: 1px solid #ccc;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 10;
        transition: all 0.2s;
        font-size: 18px;
        line-height: 1;
        padding-bottom: 2px; /* Visual center adjustment */
    }
    .close-edit-btn:hover {
        background: #f0f0f0;
        transform: scale(1.1);
        color: #d32f2f;
        border-color: #d32f2f;
    }

    /* --- FIXES START --- */
    /* Fix horizontal scroll in calendar */
    .datepicker-modal { max-width: 95vw; min-width: 280px; overflow-x: hidden; }
    .datepicker-container { padding: 0 !important; }
    .datepicker-content { padding: 0 !important; }
    .datepicker-date-display { overflow: hidden; }
    .datepicker-calendar-container { overflow: hidden; }

    /* Fix month font size */
    .datepicker-controls .month-display { font-size: 1.2rem !important; font-weight: 600; }

    /* Remove background from dropdown trigger and set font color to black */
    .select-dropdown.dropdown-trigger {
        background-color: transparent !important;
        color: #000 !important;
        border-bottom: 1px solid #9e9e9e !important;
    }
    
    /* Ensure input text is black */
    .input-field input[type=text]:not(.browser-default) {
        color: #000;
    }

    /* Fix flickering on hover by removing transform */
    .card.lead-card:hover { transform: none !important; }
    /* --- FIXES END --- */

    .area-input-group { position: relative; }
    .area-suggest {
      position: absolute;
      left: 0;
      right: 0;
      top: calc(100% + 6px);
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      box-shadow: 0 14px 40px rgba(0,0,0,0.14);
      max-height: 240px;
      overflow: auto;
      z-index: 9999;
      padding: 6px;
    }
    .area-suggest[hidden] { display: none !important; }
    .area-suggest-item {
      width: 100%;
      text-align: left;
      border: 0;
      background: transparent;
      padding: 8px 10px;
      border-radius: 8px;
      color: #0f172a;
      cursor: pointer;
      font: inherit;
      line-height: 1.2;
    }
    .area-suggest-item:hover,
    .area-suggest-item:focus-visible {
      background: #f1f5f9;
      outline: none;
    }

    .date-range-filter {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-left: 14px;
      flex-wrap: wrap;
    }
    .date-range-label {
      font-size: 12px;
      color: #64748b;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }
    .date-range-select {
      height: 34px;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 0 10px;
      background: #fff;
      color: #0f172a;
      font: inherit;
      max-width: 210px;
    }
    .custom-date-wrap {
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .date-input-wrap {
      display: inline-flex;
      align-items: center;
    }
    .date-input-wrap input[type="date"] {
      cursor: pointer;
    }
    .custom-date-wrap input[type="date"] {
      height: 34px;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 0 10px;
      background: #fff;
      color: #0f172a;
      font: inherit;
    }
	    .filter-counts-bar {
	      position: fixed;
	      top: 160px;
	      left: 16px;
	      z-index: 260;
	      display: flex;
	      flex-direction: column;
	      align-items: flex-start;
	      gap: 8px;
	      padding: 0;
	      color: #475569;
	      font-size: 13px;
	    }
    .filter-count-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255,255,255,0.92);
      border: 1px solid #e2e8f0;
      border-radius: 999px;
      padding: 6px 10px;
      box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
    }
    .filter-count-toggle {
      border: 1px solid #d7deea;
      cursor: pointer;
      font: inherit;
      color: inherit;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }
    .filter-count-toggle:hover {
      border-color: #94a3b8;
      box-shadow: 0 8px 18px rgba(15, 23, 42, 0.1);
    }
    .filter-count-toggle i {
      font-size: 11px;
      color: #64748b;
      transition: transform 0.2s ease;
    }
    .filter-count-toggle[aria-expanded="true"] i {
      transform: rotate(180deg);
    }
    .filter-count-chip strong {
      color: #0f172a;
      font-weight: 800;
    }
    .filter-counts-bar.is-loading .filter-count-chip strong {
      opacity: 0.6;
    }
	    .filter-counts-panel {
	      display: none;
	      flex-wrap: wrap;
	      justify-content: normal;
	      gap: 8px;
	      max-width: min(420px, calc(100vw - 32px));
	    }
	    .filter-counts-panel.is-open {
	      display: flex;
	    }
    .filter-count-detail {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255,255,255,0.94);
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 6px 10px;
      box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
    }
	    .filter-count-detail strong {
	      color: #0f172a;
	      font-weight: 800;
	    }
	    @media (max-width: 768px) {
	      .filter-counts-bar {
	        top: 118px;
	        right: 12px;
	        max-width: calc(100vw - 24px);
	      }
	      .filter-counts-panel {
	        max-width: calc(100vw - 24px);
	      }
	    }

	    .qlm-backdrop {
      position: fixed;
      inset: 0;
      display: none;
      align-items: flex-end;
      justify-content: flex-end;
      padding: 90px 20px 160px 20px;
      z-index: 10050;
      background: rgba(15, 23, 42, 0.15);
    }
    .qlm-backdrop.is-open { display: flex; }
    .qlm-card {
      position: relative;
      width: min(420px, 92vw);
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 18px 60px rgba(0,0,0,0.22);
      overflow: hidden;
    }
    .qlm-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 12px;
      background: #111827;
      color: #fff;
      font-weight: 700;
      font-size: 13px;
      letter-spacing: 0.2px;
    }
    .qlm-close {
      border: none;
      background: transparent;
      color: #fff;
      cursor: pointer;
      padding: 6px 8px;
      border-radius: 10px;
    }
    .qlm-close:hover { background: rgba(255,255,255,0.12); }
    .qlm-body { padding: 12px; }
    .qlm-grid { display: grid; gap: 10px; }
    .qlm-field label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      color: #334155;
      margin-bottom: 6px;
    }
    .qlm-row { display: flex; align-items: center; gap: 10px; }
    .qlm-row input[type="range"] { flex: 1; }
    .qlm-input {
      width: 100%;
      height: 36px;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 0 10px;
      font: inherit;
      color: #0f172a;
      background: #fff;
    }
    .qlm-radio-group { display: flex; flex-wrap: wrap; gap: 8px; }
    .qlm-radio {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 1px solid #e5e7eb;
      border-radius: 9999px;
      padding: 7px 10px;
      cursor: pointer;
      background: #fff;
      user-select: none;
      font-size: 13px;
      color: #111827;
    }
    .qlm-radio input { margin: 0; }
    .qlm-radio.is-selected {
      border-color: #4f46e5;
      background: #eef2ff;
      color: #111827;
    }
    .qlm-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
    .qlm-btn {
      height: 36px;
      border-radius: 10px;
      border: 1px solid transparent;
      padding: 0 12px;
      cursor: pointer;
      font: inherit;
    }
    .qlm-cancel { background: #f3f4f6; color: #111827; border-color: #e5e7eb; }
    .qlm-save { background: #16a34a; color: #fff; }
    .qlm-save:disabled { opacity: 0.65; cursor: not-allowed; }
    .qlm-save.is-loading { position: relative; padding-right: 34px; }
    .qlm-save.is-loading::after {
      content: "";
      position: absolute;
      right: 12px;
      top: 50%;
      width: 14px;
      height: 14px;
      margin-top: -7px;
      border: 2px solid rgba(255, 255, 255, 0.55);
      border-top-color: #fff;
      border-radius: 50%;
      animation: qlmSpin 0.8s linear infinite;
    }
    @keyframes qlmSpin { to { transform: rotate(360deg); } }
    @media (max-width: 768px) {
      .qlm-backdrop { align-items: center; justify-content: center; padding: 20px; }
    }

    .qlm-dupe-overlay {
      position: absolute;
      inset: 0;
      background: rgba(255, 255, 255, 0.95);
      display: none;
      flex-direction: column;
      z-index: 2;
    }
    .qlm-dupe-overlay.is-open { display: flex; }
    .qlm-dupe-title {
      padding: 12px;
      font-weight: 800;
      color: #111827;
      border-bottom: 1px solid #e5e7eb;
    }
    .qlm-dupe-list {
      padding: 10px 12px;
      overflow: auto;
      max-height: 55vh;
    }
    .qlm-dupe-item {
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 10px;
      margin-bottom: 10px;
      background: #fff;
      cursor: pointer;
    }
    .qlm-dupe-item:hover { background: #f8fafc; }
    .qlm-dupe-line1 { font-weight: 800; color: #111827; font-size: 13px; }
    .qlm-dupe-line2 { font-size: 12px; color: #475569; margin-top: 4px; }
    .qlm-dupe-actions {
      padding: 12px;
      border-top: 1px solid #e5e7eb;
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      background: #fff;
    }
    .qlm-btn.qlm-proceed { background: #16a34a; color: #fff; }
    .qlm-btn.qlm-proceed:disabled { opacity: 0.65; cursor: not-allowed; }

    .lead-card.is-dup-highlight {
      outline: 3px solid #f59e0b;
      outline-offset: 3px;
      box-shadow: 0 0 0 6px rgba(245, 158, 11, 0.18), 0 18px 60px rgba(0,0,0,0.22);
    }

    .paym-backdrop {
      position: fixed;
      inset: 0;
      display: none;
      align-items: flex-start;
      justify-content: flex-end;
      padding: 20px;
      z-index: 10060;
      background: rgba(15, 23, 42, 0.18);
    }
    .paym-backdrop.is-open { display: flex; }
    .paym-card {
      width: min(400px, 96vw);
      height: 300px;
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 18px 60px rgba(0,0,0,0.22);
      overflow: hidden;
      position: relative;
      margin: 0;
    }
    .paym-close {
      position: absolute;
      top: 12px;
      right: 12px;
      border: none;
      background: rgba(0,0,0,0.6);
      color: #fff;
      cursor: pointer;
      padding: 6px 8px;
      border-radius: 50%;
      z-index: 10;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .paym-close:hover { background: rgba(0,0,0,0.8); }
    .paym-body { 
      padding: 0;
      height: 100%;
    }
    .paym-iframe {
      width: 100%;
      height: 100%;
      border: 0;
      display: block;
    }

    .lead-delete-btn {
      width: 28px;
      height: 28px;
      border: 0;
      border-radius: 7px;
      background: #fee2e2;
      color: #b91c1c;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background 0.15s ease, color 0.15s ease, opacity 0.15s ease;
    }
    .lead-delete-btn:hover {
      background: #dc2626;
      color: #fff;
    }
    .lead-delete-btn:disabled {
      cursor: wait;
      opacity: 0.6;
    }
    
    /* Mobile responsiveness */
    @media (max-width: 768px) {
      .paym-backdrop {
        align-items: center;
        justify-content: center;
        padding: 10px;
      }
      .paym-card {
        width: min(360px, 96vw);
        height: 280px;
      }
    }
    
    /* Payment Creation Modal Styles */
    .paycreate-backdrop {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
      z-index: 10070;
      background: rgba(15, 23, 42, 0.18);
    }
    .paycreate-backdrop.is-open { display: flex; }
    .paycreate-card {
      width: min(500px, 96vw);
      max-height: 90vh;
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 18px 60px rgba(0,0,0,0.22);
      overflow: hidden;
      position: relative;
      margin: 0;
    }
    .paycreate-close {
      position: absolute;
      top: 12px;
      right: 12px;
      border: none;
      background: rgba(0,0,0,0.6);
      color: #fff;
      cursor: pointer;
      padding: 6px 8px;
      border-radius: 50%;
      z-index: 10;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .paycreate-close:hover { background: rgba(0,0,0,0.8); }
    .paycreate-body { 
      padding: 24px;
      overflow-y: auto;
    }
    .paycreate-title {
      font-size: 18px;
      font-weight: 700;
      color: #111827;
      margin-bottom: 20px;
      text-align: center;
    }
    .paycreate-form .input-field {
      margin-bottom: 20px;
    }
    .paycreate-actions {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-top: 24px;
    }
    #paycreateStatus {
      color: #6b7280;
      font-weight: 600;
    }
  </style>
</head>
<body class="grey lighten-4">
  <!-- Header with Logo -->
  <div class="page-header" id="pageHeader">
    <div class="logo-section">
      <a href="/admin_v2/smart/" class="custom-logo-link" rel="home" aria-current="page">
        <img id="main-logo" width="200" height="40" src="https://smartronic.online/content/uploads/2025/01/smarthome-black2.svg" class="custom-logo" alt="Smartronic | CCTV with Free Installation | Smart Home Automation" decoding="async">
      </a>
      <?php echo "<h2>Hello, $nameAssign!</h2>";?>
    </div>
    <div class="header-right">
      <div id="activeUsersBar" class="active-users-bar"></div>
    </div>
  </div>

  <div class="page-bg-text" id="pageBgText">Smartronic Lead List</div>

  <!-- Floating date indicator -->
  <div class="floating-date visible" id="floatingDate"></div>
  
  <div id="newLeadsFab" class="new-leads-fab">
    New <big>0</big> Lead
  </div>

  <!-- Search Bar -->
  <div id="searchBar">
    <div class="search-left">
      <img class="search-logo" src="https://smartronic.online/content/uploads/2025/01/smartronic_small_logo.png" alt="Smartronic" decoding="async">
      <?php if ($isAdmin) { ?>
        <div class="mine-links">
          <a href="#" class="mine-filter-link active" data-mine="">All</a>
          <span class="mine-sep">|</span>
          <a href="#" class="mine-filter-link" data-mine="var">Varsha</a>
          <span class="mine-sep">|</span>
          <a href="#" class="mine-filter-link" data-mine="amr">Amreen</a>
          <span class="mine-sep">|</span>
          <a href="#" class="mine-filter-link" data-mine="sur">Surya</a>
        </div>
      <?php } ?>
      <div class="follow-links">
        <span class="follow-label">Follow:</span>
        <a href="#" class="follow-filter-link" data-follow="yesterday">Yesterday</a>
        <span class="follow-sep">|</span>
        <a href="#" class="follow-filter-link" data-follow="today">Today</a>
        <span class="follow-sep">|</span>
        <a href="#" class="follow-filter-link" data-follow="tomorrow">Tomorrow</a>
      </div>
      <div class="date-range-filter">
        <span class="date-range-label">Jump to</span>
        <select id="dateRangeSelect" class="date-range-select">
          <option value="latest">Latest</option>
          <option value="this_month">This month</option>
          <option value="last_month">Last month</option>
          <option value="month_minus_2">Current Month - 2</option>
          <option value="last_two_months">Last two months</option>
          <option value="custom">Custom date</option>
        </select>
        <div id="customDateWrap" class="custom-date-wrap" hidden>
          <span class="date-input-wrap"><input type="date" id="customStartDate" aria-label="Start date"></span>
        </div>
      </div>
    </div>
    <div class="search-field">
      <input type="text" id="searchInput" list="searchSuggestions" autocomplete="off" placeholder="Search name, whatsapp, MID, date, area, comments" />
      <button type="button" id="searchSubmit" class="search-submit" aria-label="Search">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
    </div>
    <button type="button" id="clearBtn" class="search-clear-btn" aria-label="Clear">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <datalist id="searchSuggestions"></datalist>
  </div>
  <div id="filterCountsBar" class="filter-counts-bar">
    <button type="button" id="filterCountsToggle" class="filter-count-chip filter-count-toggle" aria-expanded="false" aria-controls="filterCountsPanel">
      <span id="countThisMonthLabel">Current month</span>
      <strong id="countThisMonth">-</strong>
      <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
    </button>
    <div id="filterCountsPanel" class="filter-counts-panel"></div>
  </div>

  <section class="crf-form-wrapper">
    <div class="container">
      <div class="crf-container">
        <div class="crf-left-col">

          <div id="container">
            <div id="top-sentinel"></div>
            <!-- rows go here -->
            <div id="bottom-sentinel"></div>
          </div>

          <div id="status">Loading...</div>

          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Fullscreen Quote Modal -->
<div id="quoteModal" class="quote-modal">
  <div class="quote-modal-content">
    <button id="closeQuoteModal" class="close-quote-btn"><i class="fa fa-times"></i></button>
    <iframe id="quoteIframe" src="" frameborder="0"></iframe>
  </div>
</div>

<div id="quickLeadModal" class="qlm-backdrop" aria-hidden="true">
  <div class="qlm-card" role="dialog" aria-modal="true" aria-label="New Lead">
    <div class="qlm-head">
      <div>New Lead</div>
      <button type="button" id="quickLeadClose" class="qlm-close" aria-label="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="qlm-body">
      <form id="quickLeadForm" class="qlm-grid">
        <div class="qlm-field">
          <label for="qlmCams">Cameras You Need?</label>
          <input type="number" id="qlmCams" class="qlm-input" min="1" max="32" value="6" inputmode="numeric">
        </div>
        <div class="qlm-field">
          <label>Type of DVR Needed</label>
          <div class="qlm-radio-group">
            <label class="qlm-radio"><input type="radio" name="qlmDvr" value="DVR"> DVR</label>
            <label class="qlm-radio"><input type="radio" name="qlmDvr" value="NVR"> NVR</label>
            <label class="qlm-radio"><input type="radio" name="qlmDvr" value="dont-know" checked> Don't Know</label>
          </div>
        </div>
        <div class="qlm-field">
          <label>Hard Disk Size</label>
          <div class="qlm-radio-group">
            <label class="qlm-radio"><input type="radio" name="qlmHdd" value="500GB"> 500 GB</label>
            <label class="qlm-radio"><input type="radio" name="qlmHdd" value="1TB"> 1 TB</label>
            <label class="qlm-radio"><input type="radio" name="qlmHdd" value="2TB"> 2 TB</label>
            <label class="qlm-radio"><input type="radio" name="qlmHdd" value="3TB"> 3 TB</label>
            <label class="qlm-radio"><input type="radio" name="qlmHdd" value="4TB"> 4 TB</label>
            <label class="qlm-radio"><input type="radio" name="qlmHdd" value="dont-know" checked> Don't know</label>
          </div>
        </div>
        <div class="qlm-field">
          <label>Camera Resolution</label>
          <div class="qlm-radio-group">
            <label class="qlm-radio"><input type="radio" name="qlmRes" value="2 MP"> 2 MP</label>
            <label class="qlm-radio"><input type="radio" name="qlmRes" value="5 MP"> 5 MP</label>
            <label class="qlm-radio"><input type="radio" name="qlmRes" value="dont-know" checked> Don't Know</label>
          </div>
        </div>
        <div class="qlm-field">
          <label for="qlmWa">WhatsApp number to receive the quotation</label>
          <input type="tel" id="qlmWa" class="qlm-input" placeholder="WhatsApp number" inputmode="tel" autocomplete="off">
        </div>
        <div class="qlm-actions">
          <button type="button" id="quickLeadCancel" class="qlm-btn qlm-cancel">Cancel</button>
          <button type="submit" id="quickLeadSubmit" class="qlm-btn qlm-save" disabled>Create</button>
        </div>
      </form>
    </div>
    <div id="qlmDupOverlay" class="qlm-dupe-overlay" aria-hidden="true">
      <div class="qlm-dupe-title">WhatsApp already exists</div>
      <div id="qlmDupList" class="qlm-dupe-list"></div>
      <div class="qlm-dupe-actions">
        <button type="button" id="qlmDupCancel" class="qlm-btn qlm-cancel">Cancel</button>
        <button type="button" id="qlmDupProceed" class="qlm-btn qlm-proceed">Add new row</button>
      </div>
    </div>
  </div>
</div>

<div id="paymentsModal" class="paym-backdrop" aria-hidden="true">
  <div class="paym-card" role="dialog" aria-modal="true" aria-label="Payments">
    <button type="button" id="paymentsClose" class="paym-close" aria-label="Close">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div class="paym-body">
      <iframe id="paymentsIframe" class="paym-iframe" src="https://smartronic.online/payments/" referrerpolicy="no-referrer" loading="eager"></iframe>
    </div>
  </div>
</div>


  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
  <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB7BKkBQEI0WpbFFjn8K4VWKRaYeIs3GhU&libraries=places&loading=async"></script>
  <script src="../js/floating_icon_menu.js"></script>

<!-- Payment Creation Modal -->
<div id="paymentCreateModal" class="paycreate-backdrop" aria-hidden="true">
  <div class="paycreate-card" role="dialog" aria-modal="true" aria-label="Create Payment Link">
    <button type="button" id="paymentCreateClose" class="paycreate-close" aria-label="Close">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div class="paycreate-body">
      <div class="paycreate-title">Create Payment Link</div>
      <form id="paymentCreateForm" class="paycreate-form">
        <div class="input-field">
          <i class="fa-solid fa-user prefix"></i>
          <input id="paycreateName" name="name" type="text" required>
          <label for="paycreateName">Customer Name</label>
        </div>
        <div class="input-field">
          <i class="fa-solid fa-phone prefix"></i>
          <input id="paycreatePhone" name="phone" type="tel" inputmode="numeric" required>
          <label for="paycreatePhone">Customer Phone (10 digit)</label>
        </div>
        <div class="input-field">
          <i class="fa-solid fa-indian-rupee-sign prefix"></i>
          <input id="paycreateAmount" name="amount" type="number" value="500" min="1" step="1" required>
          <label for="paycreateAmount">Amount (INR)</label>
        </div>
        <div class="input-field">
          <i class="fa-solid fa-pen-to-square prefix"></i>
          <input id="paycreateDescription" name="description" type="text" value="Smartronic CCTV Installation Booking" required>
          <label for="paycreateDescription">Description</label>
        </div>
        <div class="paycreate-actions">
          <button type="submit" class="btn waves-effect waves-light" id="paycreateSubmit">
            Create Link <i class="fa-solid fa-arrow-right right"></i>
          </button>
          <span id="paycreateStatus"></span>
        </div>
      </form>
    </div>
  </div>
</div>

  <script>
    const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    const CAN_DELETE_LEAD = <?php echo $canDeleteLead ? 'true' : 'false'; ?>;
    const CURRENT_USER_CODE = <?php echo json_encode($userCode); ?>;
    let lastScrollDirection = 'down';
    let offsetStart = 0;
    let offsetEnd = 0;
    let isLoading = false;
    let searchQuery = '';
    let mineQuery = '';
    let followQuery = '';
    let statusQuery = '';
    let col1Query = '';
    let col2Query = '';
    let dateMode = 'latest';
    let customStartDate = '';
    const INITIAL_PAGE_SIZE = 50;
    const PAGE_SIZE = 100;
    const MAX_ROWS = 200;
    let editedCards = new Map(); // Track edited cards
    let lastScrollCardsCount = 0; // Track card count for scroll detection
    let lastScrollPosition = 0; // Track scroll position
    const SCROLL_THRESHOLD = 500; // Scroll 1000px before auto-save
    let floatingDateVisible = false;
    let currentFloatingDate = '';
    const allLeadsMap = new Map(); // Store minimal data for all fetched leads to track stats accurately
    let favoritesViewActive = false;
    let historyViewActive = false;
    const FAVORITES_KEY = 'smartronic_favorites_mids';
    const HISTORY_KEY = 'smartronic_history_mids';
    let viewGeneration = 0;
    let listFetchController = null;
    let midsFetchController = null;

    function bumpViewGeneration() {
      viewGeneration += 1;
      if (listFetchController) {
        try { listFetchController.abort(); } catch (e) {}
        listFetchController = null;
      }
      if (midsFetchController) {
        try { midsFetchController.abort(); } catch (e) {}
        midsFetchController = null;
      }
    }

    function parseCommaList(value) {
      return String(value || '')
        .split(',')
        .map(v => v.trim())
        .filter(Boolean);
    }

    function readStoredList(key) {
      try {
        return parseCommaList(localStorage.getItem(key));
      } catch (e) {
        return [];
      }
    }

    function writeStoredList(key, values) {
      try {
        localStorage.setItem(key, values.join(','));
      } catch (e) {}
    }

    function normalizeMid(value) {
      const v = String(value || '').trim();
      return v;
    }

    function isFavoritedMid(mid) {
      const v = normalizeMid(mid);
      if (!v) return false;
      return readStoredList(FAVORITES_KEY).includes(v);
    }

    function toggleFavoriteMid(mid) {
      const v = normalizeMid(mid);
      if (!v) return { changed: false, isFav: false, reason: 'empty' };
      const list = readStoredList(FAVORITES_KEY);
      const idx = list.indexOf(v);
      if (idx >= 0) {
        list.splice(idx, 1);
        writeStoredList(FAVORITES_KEY, list);
        return { changed: true, isFav: false };
      }
      if (list.length >= 50) return { changed: false, isFav: false, reason: 'limit' };
      list.unshift(v);
      const next = Array.from(new Set(list)).slice(0, 50);
      writeStoredList(FAVORITES_KEY, next);
      return { changed: true, isFav: true };
    }

    function addToHistory(mid) {
      const v = normalizeMid(mid);
      if (!v) return;
      const list = readStoredList(HISTORY_KEY);
      const next = [v, ...list.filter(x => x !== v)].slice(0, 20);
      writeStoredList(HISTORY_KEY, next);
    }

    function setFavButtonState(btn, isFav) {
      if (!btn) return;
      btn.classList.toggle('is-fav', !!isFav);
      const icon = btn.querySelector('i');
      if (!icon) return;
      icon.className = isFav ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
    }

    function clearLeadListDom() {
      if (!container) return;
      container.querySelectorAll('.day-separator').forEach(el => el.remove());
      container.querySelectorAll('.col.s12').forEach(el => el.remove());
    }

    function loadMidsView(mode, mids) {
      const list = Array.isArray(mids) ? mids.map(normalizeMid).filter(Boolean) : [];
      const capped = Array.from(new Set(list)).slice(0, mode === 'favorites' ? 50 : 20);
      if (capped.length === 0) {
        showAutoSaveNotification(mode === 'favorites' ? 'No favourites saved yet' : 'No history yet', null, 'error');
        return;
      }

      bumpViewGeneration();
      const gen = viewGeneration;
      favoritesViewActive = mode === 'favorites';
      historyViewActive = mode === 'history';
      isLoading = false;
      lastScrollDirection = 'down';
      offsetStart = 0;
      offsetEnd = 0;
      editedCards.clear();
      allLeadsMap.clear();
      clearLeadListDom();

      status.innerText = 'Loading...';
      const midsCsv = capped.join(',');
      midsFetchController = new AbortController();
      fetch(`lead_fetch.php?mids=${encodeURIComponent(midsCsv)}`, { signal: midsFetchController.signal })
        .then(res => {
          if (gen !== viewGeneration) return null;
          if (!res.ok) throw new Error('Server error');
          return res.json();
        })
        .then(data => {
          if (gen !== viewGeneration) return;
          if (!Array.isArray(data) || data.length === 0) {
            status.innerText = 'No records.';
            return;
          }
          clearLeadListDom();
          data.forEach(row => {
            if (row.id && row.created_at) allLeadsMap.set(parseInt(row.id), row.created_at);
            const el = createRow(row);
            container.insertBefore(el, bottomSentinel);
          });
          rebuildDaySeparators();
          status.innerText = '';
          window.scrollTo({ top: 0, behavior: 'smooth' });
        })
        .catch(err => {
          if (gen !== viewGeneration) return;
          if (err && err.name === 'AbortError') return;
          console.error(err);
          status.innerText = 'Error loading records.';
        });
    }

    function exitMidsView() {
      favoritesViewActive = false;
      historyViewActive = false;
      resetStateAndReload();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function parseMysqlDateTime(value) {
      if (!value || value === '-') return null;
      const str = String(value).trim();
      const normalized = str
        .replace(/(\.\d{1,6})(?=(Z|[+-]\d{2}:?\d{2})?$)/, '')
        .replace(' ', 'T');
      const d = new Date(normalized);
      if (!isNaN(d.getTime())) return d;

      const m = str.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{1,6}))?)?)?(?:Z|([+-])(\d{2}):?(\d{2}))?$/);
      if (!m) return null;
      const year = parseInt(m[1], 10);
      const monthIndex = parseInt(m[2], 10) - 1;
      const day = parseInt(m[3], 10);
      const hour = m[4] ? parseInt(m[4], 10) : 0;
      const minute = m[5] ? parseInt(m[5], 10) : 0;
      const second = m[6] ? parseInt(m[6], 10) : 0;
      const sign = m[8];
      const tzHour = m[9] ? parseInt(m[9], 10) : null;
      const tzMin = m[10] ? parseInt(m[10], 10) : null;

      if (sign && tzHour !== null && tzMin !== null) {
        const offsetMinutes = (tzHour * 60 + tzMin) * (sign === '-' ? -1 : 1);
        const utcMs = Date.UTC(year, monthIndex, day, hour, minute, second) - offsetMinutes * 60 * 1000;
        return new Date(utcMs);
      }

      return new Date(year, monthIndex, day, hour, minute, second);
    }

    // Function to get relative time string
    function getRelativeTime(dateString) {
      if (!dateString || dateString === '-') return '';
      
      const date = parseMysqlDateTime(dateString);
      if (!date) return '';
      const now = new Date();
      const diffMs = now - date;
      const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
      
      if (diffDays === 0) return 'Today';
      if (diffDays === 1) return '1 day ago';
      if (diffDays === 2) return '2 days ago';
      if (diffDays === 3) return '3 days ago';
      if (diffDays <= 7) return 'This week';
      if (diffDays <= 14) return 'Last week';
      if (diffDays <= 21) return '2 weeks ago';
      if (diffDays <= 30) return '3 weeks ago';
      if (diffDays <= 60) return 'Last month';
      
      const months = Math.floor(diffDays / 30);
      if (months < 12) return `${months} months ago`;
      
      const years = Math.floor(months / 12);
      return `${years} year${years > 1 ? 's' : ''} ago`;
    }
    
    // Function to get HDD color class
    function getHddColorClass(hdd) {
      if (!hdd || hdd.toLowerCase() === 'dont-know') return '';
      const size = hdd.toLowerCase().replace(/\s/g, '');
      if (size.includes('500gb')) return 'hdd-500gb';
      if (size.includes('1tb')) return 'hdd-1tb';
      if (size.includes('2tb')) return 'hdd-2tb';
      if (size.includes('3tb')) return 'hdd-3tb';
      if (size.includes('4tb')) return 'hdd-4tb';
      if (size.includes('6tb')) return 'hdd-6tb';
      return '';
    }
    
    // Function to get DVR color class
    function getDvrColorClass(dvr) {
      if (!dvr || dvr.toLowerCase() === 'dont-know') return '';
      const type = dvr.toLowerCase();
      if (type === 'dvr') return 'dvr-dvr';
      if (type === 'nvr') return 'dvr-nvr';
      if (type === 'wifi') return 'dvr-wifi';
      return '';
    }
    function getCallStatusClass(status) {
        if (!status) return '';
        const s = status.toLowerCase();
        if (s.includes('ordered')) {
            return 'status-ordered';
        }
        if (s.includes('not interested') || s.includes('wrong number') || s.includes('dnc')) {
            return 'status-not-interested';
        }
        if (s.includes('follow up')) {
            return 'status-follow-up';
        }
        if (s.includes('busy') || s.includes('not answering') || s.includes('cut the call')) {
            return 'status-busy';
        }
        return '';
    }

    function getCallStatusIcon(status) {
        if (!status) return '';
        const s = status.toLowerCase();
        if (s.includes('ordered')) return '✅';
        if (s.includes('not answering')) return '📵';
        if (s.includes('busy')) return '⏳';
        if (s.includes('cut the call')) return '✂️';
        if (s.includes('not interested')) return '👎';
        if (s.includes('wrong number')) return '❌';
        if (s.includes('dnc')) return '🚫';
        if (s.includes('follow up')) return '📅';
        return '';
    }

    function formatDisplayId(id) {
      if (!id) return { letter: '', number: '' };
      // Handle L-123 format
      if (id.includes('-')) {
        const parts = id.split('-');
        return { letter: parts[0], number: parts[1] };
      }
      // Handle L123 format or just numbers
      if (id.length > 1 && isNaN(id[0])) {
         // Assuming first character is letter
         return { letter: id[0], number: id.substring(1) };
      }
      // Just numbers or other format
      return { letter: '', number: id };
    }

    // Generate unique color pattern from phone number
    function generateAvatarPattern(phone) {
      if (!phone) return { bg: '#f9f9f9', pattern: '#ccc', shape: '#999', shapeType: 'circle', bgPattern: 'stripes' };
      
      // Simple hash function
      let hash = 0;
      for (let i = 0; i < phone.length; i++) {
        hash = phone.charCodeAt(i) + ((hash << 5) - hash);
      }
      
      // Generate hue from hash (0-360)
      const hue = Math.abs(hash % 360);
      const saturation = 25 + (Math.abs(hash) % 15); // 25-40% for neutral colors
      const lightness = 80 + (Math.abs(hash >> 8) % 10); // 80-90% for light background
      
      const bgColor = `hsl(${hue}, ${saturation}%, ${lightness}%)`;
      const patternColor = `hsl(${hue}, ${saturation + 15}%, ${lightness - 15}%)`; // Slightly darker for pattern
      
      // Generate different hue for shape (offset by 120-240 degrees for contrast)
      const shapeHueOffset = 120 + (Math.abs(hash >> 4) % 120);
      const shapeHue = (hue + shapeHueOffset) % 360;
      const shapeColor = `hsl(${shapeHue}, 55%, 55%)`; // More saturated, medium lightness
      
      // Determine shape type based on hash
      const shapes = ['circle', 'square', 'diamond', 'hexagon'];
      const shapeType = shapes[Math.abs(hash >> 2) % shapes.length];
      
      // Determine background pattern type
      const patterns = ['stripes', 'dots', 'grid', 'zigzag'];
      const bgPattern = patterns[Math.abs(hash >> 6) % patterns.length];
      
      return { bg: bgColor, pattern: patternColor, shape: shapeColor, shapeType: shapeType, bgPattern: bgPattern };
    }

    // Pick a Font Awesome icon deterministically from a seed (phone/id)
    function getUniqueIconClass(seed) {
      const ICONS = [
        'fa-user', 'fa-camera', 'fa-video', 'fa-shield', 'fa-bolt',
        'fa-home', 'fa-map-marker-alt', 'fa-wrench', 'fa-cogs', 'fa-bell',
        'fa-key', 'fa-lock', 'fa-phone', 'fa-microchip', 'fa-wifi',
        'fa-satellite-dish', 'fa-robot', 'fa-store', 'fa-truck', 'fa-lightbulb'
      ];
      const str = String(seed || 'seed');
      let hash = 0;
      for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
      }
      const idx = Math.abs(hash) % ICONS.length;
      return ICONS[idx];
    }

    function updateCallStatusHighlight(selectElement) {
        const wrapper = selectElement.parentElement; // This should be the .select-wrapper from materialize
        if (!wrapper) return;

        // Clear existing status classes
        wrapper.classList.remove('status-not-interested', 'status-wrong-number', 'status-dnc', 'status-follow-up', 'status-busy', 'status-not-answering', 'status-cut-the-call');

        const selectedValue = selectElement.value;
        if (selectedValue === 'Not Interested' || selectedValue === 'Wrong Number' || selectedValue === 'DNC') {
            wrapper.classList.add('status-not-interested');
        } else if (selectedValue === 'Follow Up') {
            wrapper.classList.add('status-follow-up');
        } else if (selectedValue === 'Busy' || selectedValue === 'Not answering/ Switch Off' || selectedValue === 'Cut the call') {
            wrapper.classList.add('status-busy');
        }
    }

    function escapeHtml(str) {
      return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function escapeAttr(str) {
      return escapeHtml(str).replace(/`/g, '&#96;');
    }

    function getCol2BadgeInnerHtml(value) {
      const raw = String(value || '').trim();
      if (!raw) return '';

      const normalized = raw
        .toLowerCase()
        .replace(/[_-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

      const iconMap = {
        'main mobile': {
          label: 'Main Mobile',
          icons: ['fa-house', 'fa-mobile-screen-button']
        },
        'main desk': {
          label: 'Main Desktop',
          icons: ['fa-house', 'fa-desktop']
        },
        'main desktop': {
          label: 'Main Desktop',
          icons: ['fa-house', 'fa-desktop']
        },
        'pop mobile': {
          label: 'Popup Mobile',
          icons: ['fa-window-restore', 'fa-mobile-screen-button']
        },
        'popup mobile': {
          label: 'Popup Mobile',
          icons: ['fa-window-restore', 'fa-mobile-screen-button']
        },
        'pop desktop': {
          label: 'Popup Desktop',
          icons: ['fa-window-restore', 'fa-desktop']
        },
        'pop desk': {
          label: 'Popup Desktop',
          icons: ['fa-window-restore', 'fa-desktop']
        },
        'popup desktop': {
          label: 'Popup Desktop',
          icons: ['fa-window-restore', 'fa-desktop']
        }
      };

      const match = iconMap[normalized];
      if (!match) return escapeHtml(raw);

      return `
        <span class="col2-badge-icons" aria-hidden="true">
          <i class="fa-solid ${match.icons[0]}"></i>
          <i class="fa-solid ${match.icons[1]}"></i>
        </span>
        <span class="sr-only">${escapeHtml(match.label)}</span>
      `;
    }

    const areaSuggestState = new WeakMap();

    function googlePlacesReady() {
      return !!(window.google && google.maps && google.maps.places && google.maps.places.AutocompleteService);
    }

    function getBangaloreBounds() {
      return new google.maps.LatLngBounds(
        new google.maps.LatLng(12.80, 77.40),
        new google.maps.LatLng(13.20, 77.80)
      );
    }

    function getAreaSuggestBox(input) {
      const group = input ? input.closest('.area-input-group') : null;
      return group ? group.querySelector('.area-suggest') : null;
    }

    function hideAreaSuggest(input) {
      const box = getAreaSuggestBox(input);
      if (!box) return;
      box.hidden = true;
      box.innerHTML = '';
    }

    function formatPredictionValue(pred) {
      const sf = pred && pred.structured_formatting ? pred.structured_formatting : null;
      const main = (sf && sf.main_text) ? sf.main_text : (pred && pred.description ? pred.description : '');
      return String(main || '').trim();
    }

    function formatPredictionLabel(pred) {
      const sf = pred && pred.structured_formatting ? pred.structured_formatting : null;
      const main = (sf && sf.main_text) ? sf.main_text : (pred && pred.description ? pred.description : '');
      const secondary = (sf && sf.secondary_text) ? sf.secondary_text : '';
      const m = String(main || '').trim();
      const s = String(secondary || '').trim();
      return s ? `${m} • ${s}` : m;
    }

    function getAreaState(input) {
      let st = areaSuggestState.get(input);
      if (!st) {
        st = { timer: null, seq: 0, svc: null, token: null };
        areaSuggestState.set(input, st);
      }
      if (googlePlacesReady()) {
        if (!st.svc) st.svc = new google.maps.places.AutocompleteService();
        if (!st.token && google.maps.places.AutocompleteSessionToken) st.token = new google.maps.places.AutocompleteSessionToken();
      }
      return st;
    }

    function renderAreaSuggest(input, predictions) {
      const box = getAreaSuggestBox(input);
      if (!box) return;

      const items = Array.isArray(predictions) ? predictions.slice(0, 8) : [];
      if (items.length === 0) {
        hideAreaSuggest(input);
        return;
      }

      box.innerHTML = items.map((p) => {
        const val = formatPredictionValue(p);
        const label = formatPredictionLabel(p);
        return `<button type="button" class="area-suggest-item" data-value="${escapeAttr(val)}">${escapeHtml(label)}</button>`;
      }).join('');

      box.hidden = false;
    }

    function requestAreaPredictions(input, q, seq) {
      const st = getAreaState(input);
      if (!st.svc) return renderAreaSuggest(input, []);

      const req = {
        input: q,
        bounds: getBangaloreBounds(),
        componentRestrictions: { country: 'in' },
        types: ['geocode']
      };
      if (st.token) req.sessionToken = st.token;

      st.svc.getPlacePredictions(req, (predictions, status) => {
        const current = areaSuggestState.get(input);
        if (!current || current.seq !== seq) return;
        if (status !== google.maps.places.PlacesServiceStatus.OK || !predictions) {
          renderAreaSuggest(input, []);
          return;
        }
        const filtered = predictions.filter(p => /bengaluru|bangalore/i.test(p.description || ''));
        renderAreaSuggest(input, filtered.length ? filtered : predictions);
      });
    }

    function buildTraceCalloutHtml(raw) {
      const trace = String(raw || '').trim();
      if (!trace) return '<div class="trace-empty">No history</div>';
      const parts = trace
        .split('>')
        .map(s => s.trim())
        .filter(Boolean);
      return `<div class="trace-list">${parts.map(p => `<div class="trace-item">${escapeHtml(p)}</div>`).join('')}</div>`;
    }

    let tracePopoverEl = null;
    let tracePopoverAnchorId = '';

    function ensureTracePopoverEl() {
      if (tracePopoverEl) return tracePopoverEl;
      const el = document.createElement('div');
      el.id = 'trace-popover';
      el.className = 'trace-popover';
      el.style.display = 'none';
      el.addEventListener('click', (e) => {
        e.stopPropagation();
      });
      document.body.appendChild(el);
      tracePopoverEl = el;
      return el;
    }

    function hideTracePopover() {
      if (!tracePopoverEl) return;
      tracePopoverEl.style.display = 'none';
      tracePopoverEl.style.visibility = '';
      tracePopoverEl.style.top = '';
      tracePopoverEl.style.left = '';
      tracePopoverAnchorId = '';
    }

    function countQuoteLinks(raw) {
      return String(raw || '')
        .split(',')
        .map(s => s.trim())
        .filter(Boolean)
        .length;
    }

    function base64DecodeUnicode(b64) {
      const s = String(b64 ?? '');
      return decodeURIComponent(Array.prototype.map.call(atob(s), (ch) => '%' + ('00' + ch.charCodeAt(0).toString(16)).slice(-2)).join(''));
    }

    function getQuoteMinMaxFromUrl(url) {
      try {
        const u = new URL(url, window.location.origin);
        const qstate = u.searchParams.get('qstate');
        if (!qstate) return { minQuote: 0, maxQuote: 0 };
        const state = JSON.parse(base64DecodeUnicode(qstate));
        return {
          minQuote: parseInt(state.minQuote, 10) || 0,
          maxQuote: parseInt(state.maxQuote, 10) || 0
        };
      } catch (_) {
        return { minQuote: 0, maxQuote: 0 };
      }
    }

    function getQuoteShortLabelFromUrl(url, opts) {
      try {
        const u = new URL(url, window.location.origin);
        const qstate = u.searchParams.get('qstate');
        if (qstate) {
          const state = JSON.parse(base64DecodeUnicode(qstate));
          const parts = [];
          const includeAmounts = !(opts && opts.includeAmounts === false);
          const category = (state.category || '').toString().trim();
          const brand = (state.brand || '').toString().trim();
          const mp = (state.mp || '').toString().trim();
          const hddSize = (state.hddSize || '').toString().trim().replace(/\s+/g, '');
          const cameraCount = parseInt(state.cameraCount, 10) || 0;
          const pct = parseInt(state.additionalPercentage, 10) || 0;
          const disc = parseInt(state.additionalDiscount, 10) || 0;
          const minQuote = parseInt(state.minQuote, 10) || 0;
          const maxQuote = parseInt(state.maxQuote, 10) || 0;

          if (category) parts.push(category);
          if (brand) parts.push(brand);
          if (mp && category !== 'NVR') parts.push(mp);
          if (cameraCount) parts.push(cameraCount + ' CAM');
          if (hddSize) parts.push(hddSize);
          if (pct) parts.push((pct > 0 ? '+' : '') + pct + '%');
          if (disc) parts.push('-₹' + disc.toLocaleString('en-IN'));
          if (includeAmounts && (minQuote || maxQuote)) {
            const minText = minQuote ? ('₹' + minQuote.toLocaleString('en-IN')) : '';
            const maxText = maxQuote ? ('₹' + maxQuote.toLocaleString('en-IN')) : '';
            parts.push([minText, maxText].filter(Boolean).join(' - '));
          }
          return parts.join(' | ');
        }

        const quote = u.searchParams.get('quote');
        if (quote) {
          const decoded = decodeURIComponent(quote);
          const params = decoded.split('|').map(p => p.trim());
          const cams = params[1] || '';
          const dvr = params[2] || '';
          const hdd = params[3] || '';
          const res = params[4] || '';
          const parts = [];
          if (dvr) parts.push(dvr);
          if (res && cams) parts.push(res + ' x ' + cams);
          if (hdd) parts.push(hdd);
          return parts.join(' | ');
        }
      } catch (_) {}
      return '';
    }

    function renderSavedQuotesLinks(quoteLinks) {
      const links = Array.isArray(quoteLinks) ? quoteLinks.filter(Boolean) : [];
      if (!links.length) return `<span class="saved-quotes-empty">No saved quotes</span>`;
      return links.map((u, idx) => `
        <a href="#" class="action-link action-quote open-quote saved-quote-link" data-quote-url="${escapeAttr(u)}">
          <span class="saved-quote-left">
            <i class="fa fa-file-invoice"></i>
            <span class="saved-quote-label">${escapeHtml(getQuoteShortLabelFromUrl(u, { includeAmounts: false }) || ('Quote ' + (links.length - idx)))}</span>
          </span>
          ${(() => {
            const mm = getQuoteMinMaxFromUrl(u);
            const minText = mm.minQuote ? ('₹' + mm.minQuote.toLocaleString('en-IN')) : '';
            const maxText = mm.maxQuote ? ('₹' + mm.maxQuote.toLocaleString('en-IN')) : '';
            const parts = [];
            if (minText) parts.push('MIN ' + minText);
            if (maxText) parts.push('MAX ' + maxText);
            const mmText = parts.join(' | ');
            return mmText ? `<span class="saved-quote-minmax">${escapeHtml(mmText)}</span>` : '';
          })()}
        </a>
      `).join('');
    }

    const ORDER_DRAFT_PREFIX = 'orderDraft:';

    function readOrderDraft(leadId) {
      if (!leadId) return null;
      try {
        const raw = localStorage.getItem(ORDER_DRAFT_PREFIX + String(leadId));
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== 'object') return null;
        return parsed;
      } catch (_) {
        return null;
      }
    }

    function writeOrderDraft(leadId, patch) {
      if (!leadId) return;
      try {
        const current = readOrderDraft(leadId) || {};
        const next = Object.assign({}, current, patch || {});
        localStorage.setItem(ORDER_DRAFT_PREFIX + String(leadId), JSON.stringify(next));
      } catch (_) {}
    }

    const LEAD_MARKERS_KEY = 'smartronic_lead_markers_v1';
    const MARKER_CONFIG = {
      1: { days: 1, color: '#94a3b8' },
      2: { days: 3, color: '#f59e0b' },
      3: { days: 7, color: '#10b981' }
    };
    let leadMarkersCache = null;

    function readLeadMarkers() {
      if (leadMarkersCache) return leadMarkersCache;
      try {
        const raw = localStorage.getItem(LEAD_MARKERS_KEY);
        if (!raw) return (leadMarkersCache = {});
        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) return (leadMarkersCache = {});
        leadMarkersCache = parsed;
        return leadMarkersCache;
      } catch (_) {
        return (leadMarkersCache = {});
      }
    }

    function writeLeadMarkers(next) {
      leadMarkersCache = next && typeof next === 'object' && !Array.isArray(next) ? next : {};
      try {
        localStorage.setItem(LEAD_MARKERS_KEY, JSON.stringify(leadMarkersCache));
      } catch (_) {}
    }

    function startOfDayMs(ts) {
      const d = new Date(ts);
      d.setHours(0, 0, 0, 0);
      return d.getTime();
    }

    function markerExpiryMs(setAtMs, days) {
      const d = startOfDayMs(setAtMs);
      return d + (Number(days) || 0) * 24 * 60 * 60 * 1000;
    }

    function isMarkerExpired(entry, nowMs) {
      if (!entry || typeof entry !== 'object') return true;
      const marker = Number(entry.marker);
      const cfg = MARKER_CONFIG[marker];
      if (!cfg) return true;
      const setAt = Number(entry.setAt);
      if (!setAt || !isFinite(setAt)) return true;
      const expiry = markerExpiryMs(setAt, cfg.days);
      return (Number(nowMs) || Date.now()) >= expiry;
    }

    function getLeadMarker(leadId) {
      const id = String(leadId || '').trim();
      if (!id) return null;
      const map = readLeadMarkers();
      const entry = map[id];
      if (!entry) return null;
      if (isMarkerExpired(entry, Date.now())) {
        const next = Object.assign({}, map);
        delete next[id];
        writeLeadMarkers(next);
        return null;
      }
      const marker = Number(entry.marker);
      return MARKER_CONFIG[marker] ? marker : null;
    }

    function setLeadMarker(leadId, markerId) {
      const id = String(leadId || '').trim();
      const marker = Number(markerId);
      if (!id) return null;
      const cfg = MARKER_CONFIG[marker];
      const map = readLeadMarkers();
      const next = Object.assign({}, map);
      const current = next[id];
      const currentMarker = current ? Number(current.marker) : null;
      if (!cfg) {
        delete next[id];
        writeLeadMarkers(next);
        return null;
      }
      if (currentMarker === marker && !isMarkerExpired(current, Date.now())) {
        delete next[id];
        writeLeadMarkers(next);
        return null;
      }
      next[id] = { marker, setAt: Date.now() };
      writeLeadMarkers(next);
      return marker;
    }

    function renderMarkerButtonsHtml(leadId) {
      const selected = getLeadMarker(leadId);
      return `
        <button type="button" class="lead-marker-btn marker-1${selected === 1 ? ' is-selected' : ''}" data-marker="1" aria-label="Marker 1" title="Resets at 12:00 AM (daily)"></button>
        <button type="button" class="lead-marker-btn marker-2${selected === 2 ? ' is-selected' : ''}" data-marker="2" aria-label="Marker 2" title="Resets at 12:00 AM (after 3 days)"></button>
        <button type="button" class="lead-marker-btn marker-3${selected === 3 ? ' is-selected' : ''}" data-marker="3" aria-label="Marker 3" title="Resets at 12:00 AM (after 7 days)"></button>
      `;
    }

    function applyMarkerDom(cardElement, markerId) {
      if (!cardElement) return;
      cardElement.classList.remove('marker-1', 'marker-2', 'marker-3');
      const marker = Number(markerId);
      if (MARKER_CONFIG[marker]) cardElement.classList.add(`marker-${marker}`);
      const buttons = cardElement.querySelectorAll('.lead-marker-btn');
      buttons.forEach((btn) => {
        const v = Number(btn.getAttribute('data-marker'));
        btn.classList.toggle('is-selected', MARKER_CONFIG[marker] && v === marker);
      });
    }

    function refreshAllMarkers() {
      if (!container) return;
      const now = Date.now();
      const map = readLeadMarkers();
      let changed = false;
      const next = Object.assign({}, map);
      Object.keys(next).forEach((leadId) => {
        if (isMarkerExpired(next[leadId], now)) {
          delete next[leadId];
          changed = true;
        }
      });
      if (changed) writeLeadMarkers(next);
      container.querySelectorAll('.lead-card').forEach((card) => {
        const leadId = (card.getAttribute('data-lead-id') || '').trim();
        applyMarkerDom(card, getLeadMarker(leadId));
      });
    }

    function scheduleMarkerSweep() {
      const now = new Date();
      const nextMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 2, 0);
      const delay = Math.max(1000, nextMidnight.getTime() - now.getTime());
      setTimeout(() => {
        refreshAllMarkers();
        scheduleMarkerSweep();
      }, delay);
    }

    function formatIsoToDisplay(iso) {
      const m = String(iso || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
      if (!m) return '';
      const year = parseInt(m[1], 10);
      const monthIndex = parseInt(m[2], 10) - 1;
      const day = parseInt(m[3], 10);
      if (!year || monthIndex < 0 || monthIndex > 11 || !day) return '';
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      return `${String(day).padStart(2, '0')} ${months[monthIndex]} ${String(year).slice(-2)}`;
    }

    function formatWaNumber(phoneRaw) {
      const digits = String(phoneRaw || '').replace(/\D/g, '');
      if (!digits) return '';
      if (digits.length === 10) return '91' + digits;
      if (digits.length === 12 && digits.startsWith('91')) return digits;
      if (digits.length > 10) return digits;
      return '';
    }

    function formatSentAtDisplay(mysqlDateTime) {
      const d = parseMysqlDateTime(mysqlDateTime);
      if (!d || isNaN(d.getTime())) return String(mysqlDateTime || '');
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      const dd = String(d.getDate()).padStart(2, '0');
      const mm = months[d.getMonth()];
      const yy = String(d.getFullYear()).slice(-2);
      const hh = String(d.getHours()).padStart(2, '0');
      const mi = String(d.getMinutes()).padStart(2, '0');
      return `${dd} ${mm} ${yy} ${hh}:${mi}`;
    }

    function initWhatsAppDrafts(cardElement, leadData) {
      const rid = String(leadData && leadData.id ? leadData.id : '').trim();
      if (!rid) return;
      const wrap = cardElement.querySelector(`.wa-drafts-wrap[data-lead-id="${CSS.escape(rid)}"]`);
      if (!wrap) return;
      if (wrap.getAttribute('data-initialized') === '1') return;
      wrap.setAttribute('data-initialized', '1');
      const isAdmin = (typeof IS_ADMIN !== 'undefined') ? !!IS_ADMIN : (wrap.getAttribute('data-is-admin') === '1');

      const textarea = wrap.querySelector('.wa-draft-text');
      const listEl = wrap.querySelector('.wa-drafts-list');
      const addBtn = wrap.querySelector('.wa-add-template');
      const sendBtn = wrap.querySelector('.wa-send-message');
      const statusEl = wrap.querySelector('.wa-drafts-status');
      const phoneInput = cardElement.querySelector('input[name="whatsapp_number"]');
      const leadName = String(leadData && leadData.Name ? leadData.Name : '').trim();

      const setStatus = (t) => {
        if (!statusEl) return;
        statusEl.textContent = t || '';
      };

      const applyTemplateVars = (messageText) => {
        const raw = String(messageText || '');
        if (!raw) return '';
        const name = leadName;
        return raw.replace(/\[Customer Name\]/gi, name ? name : '');
      };

      const fetchTemplates = () => {
        return fetch('lead_fetch.php?wa_templates=1', { credentials: 'same-origin' })
          .then(r => r.json())
          .then(rows => Array.isArray(rows) ? rows : []);
      };

      const fetchHistory = () => {
        return fetch(`lead_fetch.php?wa_history=1&lead_id=${encodeURIComponent(rid)}`, { credentials: 'same-origin' })
          .then(r => r.json())
          .then(rows => Array.isArray(rows) ? rows : []);
      };

      const addTemplate = (messageText) => {
        const fd = new FormData();
        fd.append('message_text', messageText);
        return fetch('lead_fetch.php?wa_template_add=1', { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(r => r.json());
      };

      const deleteTemplate = (templateId) => {
        const fd = new FormData();
        fd.append('id', String(templateId || ''));
        return fetch('lead_fetch.php?wa_template_delete=1', { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(r => r.json());
      };

      const logSend = (messageText) => {
        const fd = new FormData();
        fd.append('lead_id', rid);
        fd.append('message_text', messageText);
        return fetch('lead_fetch.php?wa_send=1', { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(r => r.json());
      };

      const render = (templates, historyGroups) => {
        if (!listEl) return;
        const templateMap = new Map();
        (templates || []).forEach(t => {
          const msg = String(t && t.message_text ? t.message_text : '');
          const id = (t && t.id !== undefined && t.id !== null) ? String(t.id) : '';
          if (!msg || !id) return;
          templateMap.set(msg, id);
        });

        const sentMap = new Map();
        (historyGroups || []).forEach(g => {
          const key = String(g && g.message_text ? g.message_text : '');
          if (!key) return;
          sentMap.set(key, Array.isArray(g.sent_times) ? g.sent_times : []);
        });

        const sentItems = [];
        sentMap.forEach((times, messageText) => {
          sentItems.push({ message_text: messageText, sent_times: times });
        });
        sentItems.sort((a, b) => {
          const at = (a.sent_times && a.sent_times[0]) ? String(a.sent_times[0]) : '';
          const bt = (b.sent_times && b.sent_times[0]) ? String(b.sent_times[0]) : '';
          return bt.localeCompare(at);
        });

        const templateItems = Array.from(templateMap.keys());
        const unseenTemplates = templateItems.filter(t => !sentMap.has(t));

        const buildItemHtml = (messageText, sentTimes) => {
          const templateId = templateMap.has(messageText) ? templateMap.get(messageText) : '';
          const trimmed = messageText.length > 180 ? (messageText.slice(0, 180) + '…') : messageText;
          const safeTrimmed = escapeHtml(trimmed);
          const dates = (sentTimes || []).map(dt => `<span class="wa-draft-date">${escapeHtml(formatSentAtDisplay(dt))}</span>`).join('');
          const meta = dates ? `<div class="wa-draft-meta"><div class="wa-draft-dates">${dates}</div></div>` : '';
          const removeBtn = (isAdmin && templateId) ? `
            <button type="button" class="wa-draft-remove" data-template-id="${escapeAttr(templateId)}" aria-label="Remove message" title="Remove">
              <i class="fa fa-times"></i>
            </button>
          ` : '';
          return `
            <div class="wa-draft-item" role="button" tabindex="0" data-message="${escapeAttr(messageText)}">
              <div class="wa-draft-text-preview">${safeTrimmed}</div>
              ${meta}
              ${removeBtn}
            </div>
          `;
        };

        const htmlParts = [];
        sentItems.forEach(item => {
          htmlParts.push(buildItemHtml(item.message_text, item.sent_times));
        });
        unseenTemplates.forEach(messageText => {
          htmlParts.push(buildItemHtml(messageText, []));
        });
        if (htmlParts.length === 0) {
          listEl.innerHTML = `<span class="saved-quotes-empty">No saved messages</span>`;
          return;
        }
        listEl.innerHTML = htmlParts.join('');
      };

      const refresh = () => {
        setStatus('Loading...');
        return Promise.all([fetchTemplates(), fetchHistory()])
          .then(([templates, history]) => {
            render(templates, history);
            setStatus('');
          })
          .catch(() => setStatus('Error'));
      };

      if (listEl) {
        listEl.addEventListener('click', (e) => {
          const removeBtn = e.target && e.target.closest ? e.target.closest('.wa-draft-remove') : null;
          if (removeBtn) {
            e.preventDefault();
            if (!isAdmin) return;
            const templateId = removeBtn.getAttribute('data-template-id') || '';
            if (!templateId) return;
            setStatus('Removing...');
            deleteTemplate(templateId)
              .then(res => {
                if (res && res.success) {
                  setStatus('Removed');
                  refresh();
                } else {
                  setStatus((res && res.error) ? res.error : 'Error');
                }
              })
              .catch(() => setStatus('Error'));
            return;
          }

          const item = e.target && e.target.closest ? e.target.closest('.wa-draft-item') : null;
          if (!item) return;
          e.preventDefault();
          const msg = item.getAttribute('data-message') || '';
          if (textarea) {
            textarea.value = applyTemplateVars(msg);
            M.textareaAutoResize(textarea);
            textarea.focus();
          }
        });
      }

      if (addBtn) {
        addBtn.addEventListener('click', (e) => {
          e.preventDefault();
          if (!isAdmin) return;
          const msg = textarea ? String(textarea.value || '').trim() : '';
          if (!msg) {
            setStatus('Type a message first');
            return;
          }
          setStatus('Saving...');
          addTemplate(msg)
            .then(res => {
              if (res && res.success) {
                setStatus('Saved');
                refresh();
              } else {
                setStatus((res && res.error) ? res.error : 'Error');
              }
            })
            .catch(() => setStatus('Error'));
        });
      }

      if (sendBtn) {
        sendBtn.addEventListener('click', (e) => {
          e.preventDefault();
          const msg = textarea ? String(textarea.value || '').trim() : '';
          if (!msg) {
            setStatus('Type a message first');
            return;
          }
          const resolvedMsg = applyTemplateVars(msg);
          const waNum = formatWaNumber(phoneInput ? phoneInput.value : (leadData.whatsapp_number || ''));
          if (!waNum) {
            setStatus('Missing WhatsApp number');
            return;
          }
          const url = `https://wa.me/${waNum}?text=${encodeURIComponent(resolvedMsg)}`;
          window.open(url, '_blank', 'noopener');
          setStatus('Sending...');
          logSend(resolvedMsg)
            .then(res => {
              if (res && res.success) {
                setStatus('Sent');
                refresh();
              } else {
                setStatus((res && res.error) ? res.error : 'Error');
              }
            })
            .catch(() => setStatus('Error'));
        });
      }

      if (textarea) {
        textarea.addEventListener('input', () => setStatus(''));
      }

      refresh();
    }

    // Function to create inline edit form
    function createInlineEditForm(leadData) {
      const rid = leadData.id || '';
      const cleanFieldValue = (value) => {
        const text = String(value ?? '').trim();
        return text === '-' || text.toLowerCase() === 'na' ? '' : text;
      };
      const selectedAssign = cleanFieldValue(leadData.Assign) || 'Open';
      const selectedArea = cleanFieldValue(leadData.Area);
      const code = `L-${rid}`;
      const qPhone = leadData.whatsapp_number || '';
      const qCams = leadData.num_cameras || '';
      const qDvr = leadData.dvr_type || '';
      const qHdd = leadData.hdd_size || '';
      const qRes = leadData.camera_resolution || '';
      const qName = leadData.Name || '';
      const quoteParam = encodeURIComponent(`${qPhone}|${qCams}|${qDvr}|${qHdd}|${qRes}|${qName}|${code}`);
      const quoteUrl = `quote.php?quote=${quoteParam}&lead_id=${encodeURIComponent(rid)}&mid=${encodeURIComponent(leadData.MID || leadData.mid || '')}`;
      const quoteLinksRaw = leadData.quote_links || '';
      const quoteLinks = quoteLinksRaw.split(',').map(s => s.trim()).filter(Boolean).slice().reverse();
      const draft = readOrderDraft(rid);
      const draftQuote = draft && draft.quote !== undefined && String(draft.quote).trim() !== '' ? String(draft.quote) : '';
      const quoteValue = draftQuote !== '' ? draftQuote : (leadData.quote || '');
      const installationIso = (draft && draft.installation_date) ? String(draft.installation_date) : (leadData.installation_date ? String(leadData.installation_date) : '');
      const installationText = installationIso ? formatIsoToDisplay(installationIso) : 'Installation date';
      const isAlreadyOrdered = String(leadData.call_status || '').toLowerCase().includes('ordered');
      const lockOrderInputs = isAlreadyOrdered && String(quoteValue || '').trim() !== '' && String(installationIso || '').trim() !== '';
      
      return `
        <div class="inline-edit-form">
          <form class="compact-form" data-lead-id="${rid}">
            <input type="hidden" name="leadId" value="${rid}">
            <input type="hidden" name="whatsapp_number" value="${leadData.whatsapp_number || ''}">
            <input type="hidden" name="Message" value="${leadData.Message || ''}">
            <input type="hidden" name="map_link" value="${leadData.map_link || ''}">
            <div class="two-col-grid">
              <div class="grid-left">
                <div class="form-row">
                  <div class="input-group">
                    <label for="leadName-${rid}">Name</label>
                    <input id="leadName-${rid}" type="text" name="Name" value="${leadData.Name || ''}" placeholder="Enter Lead Name">
                  </div>
                  <div class="input-group area-input-group">
                    <label for="leadArea-${rid}">Area</label>
                    <input id="leadArea-${rid}" type="text" name="Area" value="${escapeAttr(selectedArea)}" placeholder="Location" autocomplete="off">
                    <div class="area-suggest" hidden></div>
                  </div>
                </div>

                <div class="form-row tech-specs-row">
                  <div class="input-group">
                    <label for="leadNumCameras-${rid}">Cameras</label>
                    <input id="leadNumCameras-${rid}" type="number" name="num_cameras" value="${leadData.num_cameras || ''}" placeholder="#">
                  </div>
                  <div class="input-group">
                    <label for="leadDvrType-${rid}">DVR Type</label>
                    <select id="leadDvrType-${rid}" name="dvr_type">
                      <option value="">Type</option>
                      <option value="DVR" ${leadData.dvr_type === 'DVR' ? 'selected' : ''}>DVR</option>
                      <option value="NVR" ${leadData.dvr_type === 'NVR' ? 'selected' : ''}>NVR</option>
                      <option value="WiFi" ${leadData.dvr_type === 'WiFi' ? 'selected' : ''}>WiFi</option>
                    </select>
                  </div>
                  <div class="input-group">
                    <label for="leadHdd-${rid}">HDD Size</label>
                    <select id="leadHdd-${rid}" name="hdd_size">
                      <option value="">TB</option>
                      <option value="500GB" ${leadData.hdd_size === '500GB' ? 'selected' : ''}>500GB</option>
                      <option value="1 TB" ${leadData.hdd_size === '1 TB' ? 'selected' : ''}>1 TB</option>
                      <option value="2 TB" ${leadData.hdd_size === '2 TB' ? 'selected' : ''}>2 TB</option>
                    </select>
                  </div>
                  <div class="input-group">
                    <label for="leadCameraResolution-${rid}">Res</label>
                    <select id="leadCameraResolution-${rid}" name="camera_resolution">
                      <option value="">MP</option>
                      <option value="2 MP" ${leadData.camera_resolution === '2 MP' ? 'selected' : ''}>2 MP</option>
                      <option value="5 MP" ${leadData.camera_resolution === '5 MP' ? 'selected' : ''}>5 MP</option>
                    </select>
                  </div>
                </div>

                <div class="form-row details-row">
                  <div class="input-group">
                    <label for="leadAssign-${rid}">Assign To</label>
                    <select id="leadAssign-${rid}" name="Assign">
                      <option value="Open" ${selectedAssign.toLowerCase() === 'open' ? 'selected' : ''}>Open</option>
                      <option value="AMR" ${selectedAssign.toUpperCase() === 'AMR' ? 'selected' : ''}>AMR</option>
                      <option value="VAR" ${selectedAssign.toUpperCase() === 'VAR' ? 'selected' : ''}>VAR</option>
                      <option value="SUR" ${selectedAssign.toUpperCase() === 'SUR' ? 'selected' : ''}>SUR</option>
                    </select>
                  </div>
                </div>

                <div class="form-row">
                  <div class="input-group full-width">
                    <label for="leadComments-${rid}">Notes:</label>
                    <textarea id="leadComments-${rid}" class="materialize-textarea" name="comments" rows="2" placeholder="Add notes..." required>${leadData.comments || ''}</textarea>
                  </div>
                </div>
              </div>

              <div class="grid-right">
                <!-- Moved QR Code here -->
                <div class="lead-qr-code" data-phone="${escapeAttr(leadData.whatsapp_number || '')}" style="margin-bottom: 12px;"></div>
                <a href="tel:${leadData.whatsapp_number || ''}" class="call-icon-mobile">
                  <i class="fas fa-phone"></i>
                </a>

                <div class="input-group">
                  <label for="callStatus-${rid}">Call Status</label>
                  <select id="callStatus-${rid}" name="call_status">
                    <option value="" disabled ${!leadData.call_status ? 'selected' : ''}>Select Status</option>
                    <option value="Not answering/ Switch Off" class="left circle" ${leadData.call_status === 'Not answering/ Switch Off' ? 'selected' : ''}>📵 Not answering/ Switch Off</option>
                    <option value="Busy" class="left circle" ${leadData.call_status === 'Busy' ? 'selected' : ''}>⏳ Busy</option>
                    <option value="Cut the call" class="left circle" ${leadData.call_status === 'Cut the call' ? 'selected' : ''}>✂️ Cut the call</option>
                    <option value="Not Interested" class="left circle" ${leadData.call_status === 'Not Interested' ? 'selected' : ''}>👎 Not Interested</option>
                    <option value="Wrong Number" class="left circle" ${leadData.call_status === 'Wrong Number' ? 'selected' : ''}>❌ Wrong Number</option>
                    <option value="DNC" class="left circle" ${leadData.call_status === 'DNC' ? 'selected' : ''}>🚫 DNC</option>
                    <option value="Follow Up" class="left circle" ${leadData.call_status === 'Follow Up' ? 'selected' : ''}>📅 Follow Up</option>
                    <option value="Follow Up Tomorrow" class="left circle">☀️ Follow Up Tomorrow</option>
                    <option value="Ordered" class="left circle" ${leadData.call_status === 'Ordered' ? 'selected' : ''}>✅ Ordered</option>
                  </select>
                </div>
                <div class="input-group">
                  <label for="leadFollowUp-${rid}">Follow Up</label>
                  <input id="leadFollowUp-${rid}" type="text" name="Follow_up" value="${leadData.Follow_up || ''}" class="follow-up-input datepicker" placeholder="Select Date">
                </div>
              </div>
            </div>

            <div class="inline-edit-actions inline-edit-actions-split">
              <a href="#" class="cancel-edit-btn cancel-edit-link">Cancel</a>
              <button type="button" class="btn-base btn-primary save-lead-btn">
                <i class="fa fa-save"></i> Save Changes
              </button>
            </div>

            <div class="input-group saved-quotes-wrap">
              <div class="saved-quotes-grid">
                <div class="saved-quotes-left">
                  <a href="#" class="action-link action-quote open-quote quote-launch-link" data-quote-url="${quoteUrl}">
                    <i class="fa fa-file-invoice"></i> Open Quotation
                  </a>
                  <label class="place-order-label" for="leadQuote-${rid}">Place order</label>
                  <div class="install-date-row" ${lockOrderInputs ? 'data-locked="1"' : ''}>
                    <span class="install-date-text">${escapeHtml(installationText)}</span>
                    <button type="button" class="install-date-btn" aria-label="Select installation date" title="Select installation date" ${lockOrderInputs ? 'disabled' : ''}>
                      <i class="fa fa-calendar-alt"></i>
                    </button>
                    <input id="installDate-${rid}" type="text" class="installation-date-input datepicker" name="installation_date" value="${escapeAttr(installationIso)}" autocomplete="off" aria-hidden="true" tabindex="-1" ${lockOrderInputs ? 'readonly' : ''}>
                  </div>
                  <div class="quote-amount-wrap">
                    <input id="leadQuote-${rid}" type="text" name="quote" value="${escapeAttr(quoteValue)}" placeholder="Final Amount" ${lockOrderInputs ? 'readonly' : ''}>
                    <button type="button" class="place-order-btn quote-place-order-btn" aria-label="Place order" title="Place order" ${lockOrderInputs ? 'disabled' : ''}>
                      <i class="fa fa-check"></i>
                    </button>
                  </div>
                </div>
                <div class="saved-quotes-right">
                  <div class="saved-quotes-subtitle">Saved Quotes</div>
                  <div class="saved-quotes" data-lead-id="${rid}">
                    ${quoteLinks.length
                      ? quoteLinks.map((u, idx) => {
                          const label = getQuoteShortLabelFromUrl(u, { includeAmounts: false }) || ('Quote ' + (quoteLinks.length - idx));
                          const mm = getQuoteMinMaxFromUrl(u);
                          const minText = mm.minQuote ? ('₹' + mm.minQuote.toLocaleString('en-IN')) : '';
                          const maxText = mm.maxQuote ? ('₹' + mm.maxQuote.toLocaleString('en-IN')) : '';
                          const parts = [];
                          if (minText) parts.push('MIN ' + minText);
                          if (maxText) parts.push('MAX ' + maxText);
                          const mmText = parts.join(' | ');
                          return `
                            <a href="#" class="action-link action-quote open-quote saved-quote-link" data-quote-url="${escapeAttr(u)}">
                              <span class="saved-quote-left">
                                <i class="fa fa-file-invoice"></i>
                                <span class="saved-quote-label">${escapeHtml(label)}</span>
                              </span>
                              ${mmText ? `<span class="saved-quote-minmax">${escapeHtml(mmText)}</span>` : ``}
                            </a>
                          `;
                        }).join('')
                      : `<span class="saved-quotes-empty">No saved quotes</span>`
                    }
                  </div>
                </div>
              </div>
            </div>

            <div class="input-group wa-drafts-wrap" data-lead-id="${rid}">
              <div class="wa-drafts-grid">
                <div class="wa-drafts-left">
                  <label for="waDraftText-${rid}">WhatsApp Message</label>
                  <div class="wa-drafts-textarea-wrap">
                    <textarea id="waDraftText-${rid}" class="materialize-textarea wa-draft-text" rows="3" placeholder="Type or select a message"></textarea>
                    <div class="wa-drafts-buttons">
                      <button type="button" class="wa-draft-btn wa-payment-btn" title="Create Payment Link" aria-label="Create payment link" data-lead-id="${rid}">
                        <i class="fa-solid fa-indian-rupee-sign"></i>
                      </button>
                      ${IS_ADMIN ? `
                        <button type="button" class="wa-draft-btn wa-add-template" title="Add new" aria-label="Add new message">
                          <i class="fa fa-plus"></i>
                        </button>
                      ` : ``}
                      <button type="button" class="wa-draft-btn wa-send-message" title="Send message" aria-label="Send WhatsApp message">
                        <i class="fa-brands fa-whatsapp"></i>
                      </button>
                    </div>
                  </div>
                  <div class="wa-drafts-actions">
                    <span class="wa-drafts-status"></span>
                  </div>
                </div>
                <div class="wa-drafts-right-col">
                  <div class="wa-drafts-subtitle">Saved Messages</div>
                  <div class="wa-drafts-right">
                    <div class="wa-drafts-list" data-lead-id="${rid}"></div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      `;
    }

    // Function to toggle inline edit form
    function toggleInlineEdit(cardElement, leadData) {
      const formSection = cardElement.querySelector('.lead-form-section');
      
      // Check if already in edit mode
      const isEditing = formSection.querySelector('.inline-edit-form');
      
      if (isEditing) {
        // Cancel edit mode - clear the form section
        formSection.innerHTML = '';
        
        // Remove the data attribute indicating editing
        cardElement.removeAttribute('data-editing');
        
        // Remove from edited cards tracking
        const rid = leadData.id || '';
        editedCards.delete(rid);
      } else {
        // Enter edit mode - show inline form in portion 2
        const rid = leadData.id || '';
        formSection.innerHTML = `
          <div style="position: relative;">
            <button class="close-edit-btn">&times;</button>
            ${createInlineEditForm(leadData)}
          </div>
        `;
        
        // Mark this card as editing
        cardElement.setAttribute('data-editing', 'true');
        cardElement.setAttribute('data-lead-id', rid);
        
        // Add to edited cards tracking
        editedCards.set(rid, { element: cardElement, data: leadData });
        
        // Load QR code if not already loaded
        loadQRCode(cardElement);
        
        // Initialize Materialize components
        M.updateTextFields();
        const selectElements = cardElement.querySelectorAll('select');
        M.FormSelect.init(selectElements);

        initWhatsAppDrafts(cardElement, leadData);
        
        // Initialize payment button functionality
        const paymentBtn = cardElement.querySelector('.wa-payment-btn[data-lead-id="' + rid + '"]');
        if (paymentBtn) {
          paymentBtn.addEventListener('click', () => {
            const nameInput = cardElement.querySelector('input[name="Name"]');
            const phoneInput = cardElement.querySelector('input[name="whatsapp_number"]');
            const name = nameInput ? nameInput.value.trim() : '';
            const phone = phoneInput ? phoneInput.value.replace(/\D+/g, '').slice(-10) : '';
            
            if (name && phone.length === 10) {
              openPaymentCreateModal(name, phone, rid);
            } else {
              if (window.M && M.toast) {
                M.toast({html: 'Please fill name and valid 10-digit phone number first'});
              } else {
                alert('Please fill name and valid 10-digit phone number first');
              }
            }
          });
        }

        const callStatusSelect = cardElement.querySelector(`#callStatus-${rid}`);
        if (callStatusSelect) {
            updateCallStatusHighlight(callStatusSelect);
            callStatusSelect.addEventListener('change', (e) => {
              updateCallStatusHighlight(e.target);
              
              const selectedValue = e.target.value;
              const followUpInput = cardElement.querySelector(`#leadFollowUp-${rid}`);
              
              if (selectedValue === 'Follow Up Tomorrow' || 
                  selectedValue === 'Not answering/ Switch Off' || 
                  selectedValue === 'Busy' || 
                  selectedValue === 'Cut the call') {
                
                // Calculate tomorrow's date
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const day = String(tomorrow.getDate()).padStart(2, '0');
                const month = months[tomorrow.getMonth()];
                const year = String(tomorrow.getFullYear()).slice(-2);
                const formattedDate = `${day} ${month} ${year}`;
                
                if (followUpInput) {
                  followUpInput.value = formattedDate;
                  // Trigger Materialize update if needed
                  M.updateTextFields();
                  
                  if (selectedValue === 'Follow Up Tomorrow') {
                    e.target.value = 'Follow Up';
                    // Re-init select to show the change
                    M.FormSelect.init(e.target);
                  }
                }
              } else if (selectedValue === 'Follow Up') {
                // Trigger datepicker
                if (followUpInput) {
                  const instance = M.Datepicker.getInstance(followUpInput);
                  if (instance) {
                    instance.open();
                  }
                }
              } else if (selectedValue === 'Not Interested' || selectedValue === 'Wrong Number' || selectedValue === 'DNC') {
                  // Set follow up to NA
                  if (followUpInput) {
                      followUpInput.value = 'NA';
                      M.updateTextFields();
                  }
              }
            });
        }
        
        // Initialize datepicker for Follow Up field
        const followUpInputs = cardElement.querySelectorAll('.follow-up-input');
        M.Datepicker.init(followUpInputs, {
          format: 'dd mmm yy',
          autoClose: true,
          showClearBtn: true,
          minDate: new Date()
        });

        const installInputs = cardElement.querySelectorAll('.installation-date-input');
        const minInstallDate = new Date();
        minInstallDate.setHours(0, 0, 0, 0);
        M.Datepicker.init(installInputs, {
          format: 'yyyy-mm-dd',
          autoClose: true,
          showClearBtn: true,
          minDate: minInstallDate,
          onSelect: function(date) {
            const input = this && this.el ? this.el : null;
            if (!input) return;
            const row = input.closest('.install-date-row');
            if (row && row.getAttribute('data-locked') === '1') return;
            const label = row ? row.querySelector('.install-date-text') : null;
            const btn = row ? row.querySelector('.install-date-btn') : null;
            const icon = btn ? btn.querySelector('i') : null;
            if (row) row.classList.remove('is-error');
            if (label) label.style.color = '';
            if (btn) btn.style.color = '';
            if (icon) icon.style.color = '';
            if (!label) return;
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const day = String(date.getDate()).padStart(2, '0');
            const month = months[date.getMonth()];
            const year = String(date.getFullYear()).slice(-2);
            const followUpFormatted = `${day} ${month} ${year}`;
            label.textContent = followUpFormatted;
            const leadId = input.closest('form') ? input.closest('form').dataset.leadId : '';
            if (leadId) writeOrderDraft(leadId, { installation_date: input.value });
            const followUpInput = input.closest('.lead-card') ? input.closest('.lead-card').querySelector('input[name="Follow_up"]') : null;
            if (followUpInput) {
              followUpInput.value = followUpFormatted;
              if (typeof M !== 'undefined') M.updateTextFields();
            }
          },
          onClose: function() {
            const input = this && this.el ? this.el : null;
            if (!input) return;
            const row = input.closest('.install-date-row');
            const label = row ? row.querySelector('.install-date-text') : null;
            if (!label) return;
            if (!input.value || !input.value.trim()) label.textContent = 'Installation date';
          }
        });
        for (const input of installInputs) {
          if (!input || !input.value || !input.value.trim()) continue;
          const row = input.closest('.install-date-row');
          const label = row ? row.querySelector('.install-date-text') : null;
          if (label) {
            const display = formatIsoToDisplay(input.value);
            label.textContent = display || 'Installation date';
          }
          const followUpInput = input.closest('.lead-card') ? input.closest('.lead-card').querySelector('input[name="Follow_up"]') : null;
          if (followUpInput) {
            const current = (followUpInput.value || '').trim();
            if (current === '' || current === '-' || current.toLowerCase() === 'na') {
              const display = formatIsoToDisplay(input.value);
              if (display) {
                followUpInput.value = display;
                if (typeof M !== 'undefined') M.updateTextFields();
              }
            }
          }
        }
        // Bind keyup events to update placeholders in real-time
        setupRealtimeFormBindings(cardElement, rid);

        // Scroll the card to the center of the screen
        setTimeout(() => {
            cardElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
      }
    }

    // Function to load QR code when editing
    function loadQRCode(cardElement) {
      const qrContainer = cardElement.querySelector('.lead-qr-code');
      if (!qrContainer) return;
      
      // Check if QR code already loaded
      if (qrContainer.querySelector('img') || qrContainer.querySelector('.lead-qr-fallback')) return;
      
      const phone = qrContainer.getAttribute('data-phone') || '';
      const displayPhone = phone.trim();
      const digits = displayPhone.replace(/\D/g, '');
      if (!displayPhone) return;

      const showPhoneFallback = () => {
        qrContainer.innerHTML = `
          <a class="lead-qr-fallback" href="tel:+91${escapeAttr(digits || displayPhone)}" title="Call ${escapeAttr(displayPhone)}">
            ${escapeHtml(displayPhone)}
          </a>
        `;
      };
      
      // Default to phone call (tel:)
      const callUrl = encodeURIComponent('tel:+91' + (digits || displayPhone));
      qrContainer.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=96x96&data=${callUrl}&bgcolor=3498db&color=fff" alt="QR Code" title="Click to switch to WhatsApp" data-mode="call" data-phone="${escapeAttr(digits || displayPhone)}">`;
      
      // Add click event to toggle between call and WhatsApp
      const qrImg = qrContainer.querySelector('img');
      if (!qrImg) {
        showPhoneFallback();
        return;
      }
      qrImg.addEventListener('error', showPhoneFallback, { once: true });
      setTimeout(() => {
        if (!qrImg.complete || qrImg.naturalWidth === 0) showPhoneFallback();
      }, 2500);
      qrImg.addEventListener('click', function(e) {
        e.preventDefault();
        toggleQRMode(this);
      });
    }
    
    // Function to toggle QR code between call and WhatsApp
    function toggleQRMode(imgElement) {
      const currentMode = imgElement.getAttribute('data-mode');
      const phone = imgElement.getAttribute('data-phone');
      
      if (currentMode === 'call') {
        // Switch to WhatsApp
        const whatsappUrl = encodeURIComponent('https://wa.me/91' + phone);
        imgElement.src = `https://api.qrserver.com/v1/create-qr-code/?size=108x108&data=${whatsappUrl}&bgcolor=3498db&color=fff`;
        imgElement.setAttribute('data-mode', 'whatsapp');
        imgElement.setAttribute('title', 'Click to switch to Call');
        imgElement.classList.add('whatsapp-mode');
      } else {
        // Switch to Call
        const callUrl = encodeURIComponent('tel:+91' + phone);
        imgElement.src = `https://api.qrserver.com/v1/create-qr-code/?size=108x108&data=${callUrl}&bgcolor=3498db&color=fff`;
        imgElement.setAttribute('data-mode', 'call');
        imgElement.setAttribute('title', 'Click to switch to WhatsApp');
        imgElement.classList.remove('whatsapp-mode');
      }
    }

    // Helper to mask phone number
    function maskPhoneNumber(phone) {
      if (!phone) return '';
      // Remove non-digits
      const p = phone.replace(/\D/g, '');
      if (p.length < 5) return phone; // Too short to mask
      
      // Mask 3 digits in the middle
      // For 10 digit number: 9876543210 -> 9876XXX210
      // We keep first 4 and last 3 visible, mask 3 in between?
      // Or Keep first 2, mask 3, keep rest?
      
      // "mask the number 2-3 digits" - interpreting as masking 3 digits
      if (p.length >= 7) {
         const startLen = Math.floor((p.length - 3) / 2);
         const start = p.substring(0, startLen);
         const end = p.substring(startLen + 3);
         return `${start}***${end}`;
      }
      return phone;
    }

    function createRow(row) {
      const el = document.createElement('div');
      el.className = 'col s12';
      const cleanDisplayValue = (value) => {
        const text = String(value ?? '').trim();
        return text === '-' || text.toLowerCase() === 'na' ? '' : text;
      };
      const truncateBadgeText = (value, maxLength = 20) => {
        const text = String(value ?? '').trim();
        return text.length > maxLength ? `${text.slice(0, maxLength)}...` : text;
      };

      // Use display_id field which is either MID or id
      const displayId = row.display_id || row.MID || row.id || '';
      const rid = row.id || '';
      const code = `L-${rid}`;
      const name = row.Name || '';
      const displayName = name || 'NAME';
      const nameClass = name ? 'lead-name' : 'lead-name-placeholder';
      const phone = row.whatsapp_number || '';
      const cams = row.num_cameras || '';
      const dvr = row.dvr_type || '';
      const hdd = row.hdd_size || '';
      const res = row.camera_resolution || '';
      const assign = cleanDisplayValue(row.Assign) || 'Open';
      const area = cleanDisplayValue(row.Area);
      const follow = row.Follow_up || '-';
      const comment = row.comments || '-';
      const created = row.created_at || '-';
      const msg = row.Message || '-';
      const map = row.map_link || '';
      const call_status = row.call_status || '';
      const col1 = cleanDisplayValue(row.Column_1 ?? row.column_1);
      const col2 = cleanDisplayValue(row.Column_2 ?? row.column_2);
      const col1BadgeText = col1 || 'N/A';
      const col1BadgeDisplayText = truncateBadgeText(col1BadgeText, 20);
      const col2BadgeText = col2 || 'N/A';
      const originallyAssigned = (row.originally_assigned || '').trim();
      const editTrace = (row.edit_trace || '').trim();
      
      // Mask phone number
      const maskedPhone = maskPhoneNumber(phone);

      // Format date (dd mmm yy)
      let formattedDate = '-';
      let relativeTime = '';
      if (created && created !== '-') {
        const dateObj = parseMysqlDateTime(created);
        if (dateObj) {
          const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
          const day = String(dateObj.getDate()).padStart(2, '0');
          const month = months[dateObj.getMonth()];
          const year = String(dateObj.getFullYear()).slice(-2);
          formattedDate = `${day} ${month} ${year}`;
          relativeTime = getRelativeTime(created);
        }
      }

      // Format technical specs - replace dont-know with X
      const hddColorClass = getHddColorClass(hdd);
      const dvrColorClass = getDvrColorClass(dvr);
      const hddDisplay = (hdd && hdd.toLowerCase() !== 'dont-know') ? `<span class="spec-value ${hddColorClass}">${hdd}</span>` : '<span class="spec-unknown">X</span>';
      const resDisplay = (res && res.toLowerCase() !== 'dont-know') ? `<span class="spec-value">${res}</span>` : '<span class="spec-unknown">X</span>';
      const camDisplay = (cams && cams.toString().toLowerCase() !== 'dont-know') ? `<span class="spec-value">${cams}</span>` : '<span class="spec-unknown">X</span>';
      const recDisplay = (dvr && dvr.toLowerCase() !== 'dont-know') ? `<span class="spec-value ${dvrColorClass}">${dvr}</span>` : '<span class="spec-unknown">X</span>';

      // Generate unique avatar pattern for this phone number
      const avatarPattern = generateAvatarPattern(phone);
      
      // Generate pattern style based on pattern type
      let patternStyle = '';
      switch(avatarPattern.bgPattern) {
        case 'stripes':
          patternStyle = `background: repeating-linear-gradient(45deg, ${avatarPattern.bg} 0px, ${avatarPattern.bg} 8px, ${avatarPattern.pattern} 8px, ${avatarPattern.pattern} 16px);`;
          break;
        case 'dots':
          patternStyle = `background-color: ${avatarPattern.bg}; background-image: radial-gradient(${avatarPattern.pattern} 2px, transparent 2px); background-size: 10px 10px;`;
          break;
        case 'grid':
          patternStyle = `background-color: ${avatarPattern.bg}; background-image: linear-gradient(${avatarPattern.pattern} 1px, transparent 1px), linear-gradient(90deg, ${avatarPattern.pattern} 1px, transparent 1px); background-size: 8px 8px;`;
          break;
        case 'zigzag':
          patternStyle = `background: linear-gradient(135deg, ${avatarPattern.bg} 25%, transparent 25%) -8px 0, linear-gradient(225deg, ${avatarPattern.bg} 25%, transparent 25%) -8px 0, linear-gradient(315deg, ${avatarPattern.bg} 25%, transparent 25%), linear-gradient(45deg, ${avatarPattern.bg} 25%, transparent 25%); background-size: 16px 16px; background-color: ${avatarPattern.pattern};`;
          break;
      }
      
      const patternId = phone ? phone.slice(-4) : '0000'; // Last 4 digits for pattern recognition
      
      // Compute icon and shape class
      const shapeClass = `avatar-${avatarPattern.shapeType}`;
      const iconClassName = getUniqueIconClass(phone || rid || displayId || patternId);
      
      // Format ID for display
      const idParts = formatDisplayId(displayId);
      const midKey = normalizeMid(row.MID || displayId || rid);
      const isFav = isFavoritedMid(midKey);

      // ✅ Build the quote parameter string
      const quoteParam = encodeURIComponent(`${phone}|${cams}|${dvr}|${hdd}|${res}|${name}|${code}`);
      const quoteUrl = `quote.php?quote=${quoteParam}&lead_id=${encodeURIComponent(rid)}&mid=${encodeURIComponent(row.MID || '')}`;
      const quoteCount = countQuoteLinks(row.quote_links || '');
      const isOpenAssign = (assign || '').trim() === '' || (assign || '').trim().toLowerCase() === 'open';
      const isAssignOpenExact = (assign || '').trim().toLowerCase() === 'open';
      const showWas = isOpenAssign && originallyAssigned && originallyAssigned !== '-' && originallyAssigned.toLowerCase() !== 'na' && originallyAssigned.toLowerCase() !== 'open';
      const hasHistory = !!(editTrace && editTrace.trim() && editTrace.trim() !== '-');
      const markerId = getLeadMarker(rid);
      const markerClass = markerId ? `marker-${markerId}` : '';
      const markerButtonsHtml = renderMarkerButtonsHtml(rid);

      el.innerHTML = `
        <div class="card lead-card ${markerClass}"
             data-created-at="${created}"
             data-lead-id="${rid}"
             data-quote="${escapeAttr(row.quote || '')}"
             data-installation-date="${escapeAttr(row.installation_date || '')}"
             data-quote-links="${escapeAttr(row.quote_links || '')}"
             data-day-total="${row.day_total || ''}"
             data-day-rank="${row.day_rank || ''}"
             data-mid="${row.MID || ''}"
             data-column-1="${escapeAttr(col1)}"
             data-column-2="${escapeAttr(col2)}"
             data-name="${name}"
             data-whatsapp-number="${phone}"
             data-num-cameras="${cams}"
             data-dvr-type="${dvr}"
             data-hdd-size="${hdd}"
             data-camera-resolution="${res}"
             data-assign="${escapeAttr(assign)}"
             data-originally-assigned="${escapeAttr(originallyAssigned)}"
             data-edit-trace="${escapeAttr(editTrace)}"
             data-area="${escapeAttr(area)}"
             data-follow-up="${follow}"
             data-comments="${comment}"
             data-message="${msg}"
             data-map-link="${map}"
             data-call-status="${call_status}">
          
          <!-- Portion 1: Lead Info -->
          <div class="lead-info-section" style="position: relative;">
            ${phone ? `<div class="lead-avatar-section edit-lead-btn" data-code="${code}" data-phone="${phone}" style="cursor: pointer;" title="Click to Edit">
               <div class="qr-placeholder" style="${patternStyle}">
                 <div class="avatar-shape ${shapeClass}" style="background-color: #ffffff; border: 1px solid #999; transform: rotate(-45deg);">
                   <i class="fa ${iconClassName} avatar-icon" style="color: ${avatarPattern.shape}; font-size: 16px;"></i>
                 </div>
               </div>
               <h3>
                 <span class="idno-badge">
                   <span class="id-letter">${idParts.letter}</span>
                   <span class="id-number">${idParts.number}</span>
                 </span>
               </h3>
               <div class="fav-toggle-wrap">
                 <button type="button" class="fav-toggle ${isFav ? 'is-fav' : ''}" data-mid="${midKey}" aria-label="Favourite">
                   <i class="${isFav ? 'fa-solid fa-heart' : 'fa-regular fa-heart'}"></i>
                 </button>
               </div>
               <!-- Mobile call and WhatsApp icons hidden by default, shown on mobile via CSS -->
               <div class="mobile-contact-icons" style="display: none;">
                 <a href="tel:+91${phone}" class="call-icon"><i class="fa fa-phone"></i></a>
                 <a href="https://wa.me/91${phone}" class="whatsapp-icon" target="_blank"><i class="fa fa-whatsapp"></i></a>
               </div>
            </div>` : ''}

            <div class="lead-info-content">
              <div class="lead-specs">
                <div class="spec-tag">
                  <span class="spec-label">HDD</span>
                  ${hddDisplay}
                </div>
                <div class="spec-tag">
                  <span class="spec-label">RES</span>
                  <div class="spec-row">
                    ${resDisplay} <span class="spec-x">x</span> ${camDisplay}
                  </div>
                </div>
                <div class="spec-tag">
                  <span class="spec-label">DVR</span>
                  ${recDisplay}
                </div>
              </div>

              <div class="lead-header" style="flex-direction: column; align-items: flex-start; gap: 2px;">
                <h3 class="${nameClass}" style="margin-bottom: 0;">${displayName}</h3>
                ${maskedPhone ? `<div class="lead-masked-phone" style="font-size: 13px; color: #666; font-family: monospace; letter-spacing: 0.5px; font-weight: 500;">${maskedPhone}</div>` : ''}
              </div>

              ${call_status ? `<div class="lead-status-row"><span class="status-badge ${getCallStatusClass(call_status)}" data-status-value="${escapeAttr(call_status)}">${getCallStatusIcon(call_status)} ${call_status}</span></div>` : ''}
              <div class="lead-marker-strip" data-lead-id="${rid}">
                ${markerButtonsHtml}
              </div>

              <div class="lead-meta-row">
                ${area ? `<span class="lead-phone"><i class="fa fa-map-marker-alt"></i> ${escapeAttr(area)}</span>` : ''}
                ${follow && follow !== '-' ? `<span class="lead-phone"><i class="fa fa-calendar-alt"></i> ${follow}</span>` : ''}
              </div>
              ${comment && comment !== '-' ? `<div class="lead-comments">${comment}</div>` : ''}
            </div>
          </div>
          
          <!-- Portion 2: Expandable Form -->
          <div class="lead-form-section"></div>
          
          <!-- Portion 3: Controls -->
          <div class="lead-controls-section">
            <div class="lead-controls-left">
               ${assign ? `<span class="assign-badge${isAssignOpenExact ? ' is-open open-animate' : ''}${String(assign).trim().toUpperCase() === 'SUR' ? ' is-sur' : ''}">${escapeAttr(assign)}</span>` : ''}
               <span class="col2-badge${col2 ? '' : ' is-missing'}" data-col2-value="${escapeAttr(col2)}" title="${escapeAttr(col2BadgeText)}" aria-label="${escapeAttr(col2BadgeText)}">${getCol2BadgeInnerHtml(col2BadgeText)}</span>
               <span class="col1-badge${col1 ? '' : ' is-missing'}" data-col1-value="${escapeAttr(col1)}" title="${escapeAttr(col1BadgeText)}" aria-label="${escapeAttr(col1BadgeText)}">${escapeAttr(col1BadgeDisplayText)}</span>
               <span class="lead-phone" style="font-size: 12px;">${formattedDate}${relativeTime ? ` • ${relativeTime}` : ''}${showWas ? ` | Was: ${escapeAttr(originallyAssigned)}` : ''}</span>
            </div>
            <div class="lead-actions">
              ${CAN_DELETE_LEAD ? `
              <button type="button" class="lead-delete-btn" aria-label="Delete lead" title="Delete lead" data-lead-id="${escapeAttr(rid)}">
                <i class="fa-solid fa-trash"></i>
              </button>` : ''}
              ${hasHistory ? `
              <button type="button" class="trace-toggle" aria-label="History" title="History">
                <i class="fa-solid fa-clock-rotate-left"></i>
              </button>` : ''}
              <span class="quote-count-indicator ${quoteCount ? '' : 'is-empty'}" title="Quotes sent">
                <i class="fa fa-file-invoice"></i>
                <span class="quote-count-number">${quoteCount}</span>
              </span>
            </div>
          </div>
        </div>
      `;

      return el;
    }

    let daySeparatorObserver = null;

    function ensureDaySeparatorObserver() {
      if (daySeparatorObserver) return;
      daySeparatorObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          const el = entry.target;
          if (entry.isIntersecting) {
            if (el.dataset.inview === '1') return;
            el.dataset.inview = '1';
            el.classList.remove('day-separator-animate');
            void el.offsetWidth;
            el.classList.add('day-separator-animate');
          } else {
            el.dataset.inview = '0';
          }
        });
      }, {
        root: null,
        rootMargin: '-45% 0px -45% 0px',
        threshold: 0.01
      });
    }

    function observeDaySeparators() {
      if (!container) return;
      ensureDaySeparatorObserver();
      daySeparatorObserver.disconnect();
      container.querySelectorAll('.day-separator-inner').forEach((el) => {
        el.dataset.inview = '0';
        daySeparatorObserver.observe(el);
      });
    }

    function rebuildDaySeparators() {
      if (!container || !topSentinel || !bottomSentinel) return;

      container.querySelectorAll('.day-separator').forEach(el => el.remove());

      const cards = Array.from(container.querySelectorAll('.lead-card'));
      if (cards.length === 0) return;

      let lastDateKey = null;
      for (const card of cards) {
        const createdAt = card.getAttribute('data-created-at');
        const d = parseMysqlDateTime(createdAt);
        if (!d || isNaN(d.getTime())) continue;

        const dateKey = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        if (dateKey === lastDateKey) continue;
        lastDateKey = dateKey;

        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const label = `${String(d.getDate()).padStart(2, '0')} ${months[d.getMonth()]} ${String(d.getFullYear()).slice(-2)}`;

        const dayTotal = card.getAttribute('data-day-total');
        const totalText = dayTotal ? `${dayTotal} leads` : '';

        const sepOuter = document.createElement('div');
        sepOuter.className = 'day-separator';
        sepOuter.setAttribute('data-day', dateKey);
        sepOuter.innerHTML = `
          <div class="day-separator-inner">
            <div class="day-separator-date">${label}</div>
            ${totalText ? `<div class="day-separator-count">${totalText}</div>` : ''}
          </div>
        `;

        const wrapper = card.closest('.col');
        if (wrapper) {
          container.insertBefore(sepOuter, wrapper);
        } else {
          container.insertBefore(sepOuter, card);
        }
      }

      observeDaySeparators();
    }

    function fetchRows(direction = 'down') {
      if (favoritesViewActive || historyViewActive) return;
      if (isLoading) return;
      isLoading = true;
      status.innerText = 'Loading...';

      let fetchOffset;
      let fetchLimit = PAGE_SIZE;
      if (direction === 'down') {
        fetchOffset = offsetEnd;
        if (offsetEnd === 0) {
          fetchLimit = INITIAL_PAGE_SIZE;
        }
      } else {
        if (offsetStart === 0) {
          status.innerText = '';
          isLoading = false;
          return;
        }
        fetchOffset = Math.max(0, offsetStart - PAGE_SIZE);
      }

      const gen = viewGeneration;
      listFetchController = new AbortController();
      let resolvedFetchOffset = fetchOffset;
      const isInitialDateJump = direction === 'down'
        && offsetEnd === 0
        && String(dateMode) === 'custom'
        && String(customStartDate || '').trim() !== '';
      const dateParams = (() => {
        const raw = String(searchQuery || '').trim();
        const digits = (raw.match(/\d/g) || []).length;
        const letters = (raw.match(/[A-Za-z]/g) || []).length;
        const isPhoneLike = raw !== '' && letters === 0 && digits >= 7;
        if (isPhoneLike) return '';
        const mode = String(dateMode || '').trim();
        if (!mode || mode === 'latest') return '';
        if (mode === 'custom') {
          const s = String(customStartDate || '').trim();
          if (!s || !isInitialDateJump) return '';
          return `&date_mode=custom&date_start=${encodeURIComponent(s)}`;
        }
        return `&date_mode=${encodeURIComponent(mode)}`;
      })();
      fetch(`lead_fetch.php?offset=${fetchOffset}&limit=${fetchLimit}&search=${encodeURIComponent(searchQuery)}&mine=${encodeURIComponent(mineQuery)}&follow=${encodeURIComponent(followQuery)}&status=${encodeURIComponent(statusQuery)}&col1=${encodeURIComponent(col1Query)}&col2=${encodeURIComponent(col2Query)}${dateParams}`, { signal: listFetchController.signal })
        .then(res => {
          if (gen !== viewGeneration) return null;
          if (!res.ok) throw new Error("Server error");
          const startOffsetHeader = res.headers.get('X-Lead-Start-Offset');
          if (isInitialDateJump && startOffsetHeader !== null) {
            const parsedStartOffset = Number.parseInt(startOffsetHeader, 10);
            if (Number.isFinite(parsedStartOffset) && parsedStartOffset >= 0) {
              resolvedFetchOffset = parsedStartOffset;
            }
          }
          return res.json();
        })
        .then(data => {
          if (gen !== viewGeneration) return;
          if (!Array.isArray(data) || data.length === 0) {
            status.innerHTML = '<img src="https://media0.giphy.com/media/v1.Y2lkPTc5MGI3NjExenNydGE2ZDYweHU2ZjBvNzF2OHN6enBiNTl3aXltanFzMWJ5b3BudSZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9cw/9Poa3NkkYsdbPZNzrK/giphy.gif" alt="No more records" style="display:block;width:120px;max-width:100%;height:auto;margin:12px auto;">';
            isLoading = false;
            return;
          }

          if (direction === 'down') {
            if (isInitialDateJump) {
              offsetStart = resolvedFetchOffset;
              offsetEnd = resolvedFetchOffset;
            }
            data.forEach(row => {
              // Track lead data for stats
              if (row.id && row.created_at) {
                allLeadsMap.set(parseInt(row.id), row.created_at);
              }

              // Prevent duplicates
              if (!container.querySelector(`.lead-card[data-lead-id="${row.id}"]`)) {
                const el = createRow(row);
                container.insertBefore(el, bottomSentinel);
                // offsetEnd increment handled after loop
              }
            });
            // Always advance offsetEnd by the number of fetched items to keep in sync with DB offsets
            offsetEnd += data.length;
          } else {
            const firstRowBefore = topSentinel.nextElementSibling;
            const prevTop = firstRowBefore ? firstRowBefore.getBoundingClientRect().top : 0;

            const frag = document.createDocumentFragment();
            let addedCount = 0;
            
            for (let i = 0; i < data.length; i++) {
              // Track lead data for stats
              if (data[i].id && data[i].created_at) {
                allLeadsMap.set(parseInt(data[i].id), data[i].created_at);
              }

              // Prevent duplicates
              if (!container.querySelector(`.lead-card[data-lead-id="${data[i].id}"]`)) {
                frag.appendChild(createRow(data[i]));
                addedCount++;
              }
            }
            container.insertBefore(frag, topSentinel.nextSibling);

            if (addedCount > 0) {
                requestAnimationFrame(() => {
                  if (firstRowBefore) {
                    const newTop = firstRowBefore.getBoundingClientRect().top;
                    const delta = newTop - prevTop;
                    window.scrollBy(0, delta);
                  }
                });
            }

            // Update offsetStart based on fetched count, not added count, to keep SQL offsets in sync
            offsetStart = Math.max(0, offsetStart - data.length);
          }

          // Check if we've scrolled enough to trigger auto-save
          checkAndAutoSaveOnScroll();
          
          trimExcessRows();
          rebuildDaySeparators();
          if (isInitialDateJump) {
            requestAnimationFrame(() => {
              const jumpDate = String(customStartDate || '').trim();
              const cards = Array.from(container.querySelectorAll('.lead-card'));
              const anchorCard = cards.find(card => {
                const createdAt = String(card.getAttribute('data-created-at') || '');
                return createdAt.slice(0, 10) <= jumpDate;
              });
              if (anchorCard) {
                anchorCard.scrollIntoView({ block: 'center', behavior: 'auto' });
              }
            });
          }
          status.innerText = '';
          isLoading = false;
        })
        .catch(err => {
          if (gen !== viewGeneration) return;
          if (err && err.name === 'AbortError') return;
          console.error(err);
          status.innerText = 'Error loading records.';
          isLoading = false;
        });
    }

    // ✅ Check if edited card has left the viewport and auto-save
    function checkAndAutoSaveOnScroll() {
      // Update floating date as user scrolls
      updateFloatingDate();
      
      if (editedCards.size === 0) return;

      const headerHeight = document.getElementById('pageHeader') ? document.getElementById('pageHeader').offsetHeight : 0;
      const searchHeight = document.getElementById('searchBar') ? document.getElementById('searchBar').offsetHeight : 0;
      const topOffset = headerHeight + searchHeight;
      const windowHeight = window.innerHeight;

      // Check each edited card
      editedCards.forEach((editedCard, leadId) => {
        const card = editedCard.element;
        const rect = card.getBoundingClientRect();

        // Check if card has scrolled completely out of view (top or bottom)
        // Leaving 50px buffer zone
        const isAbove = rect.bottom < topOffset + 50; 
        const isBelow = rect.top > windowHeight - 50;

        if (isAbove || isBelow) {
          console.log(`Card ${leadId} scrolled out of view. Auto-saving...`);
          
          const form = card.querySelector('form');
          if (form) {
            const formData = new FormData(form);
            const leadData = {};
            for (let [key, value] of formData.entries()) {
              leadData[key] = value;
            }
            
            saveLead(leadData, true, leadId);
            
            // Collapse
            const code = `L-${leadId}`;
            fetch(`lead_fetch.php?leadId=${encodeURIComponent(code)}`)
              .then(res => res.json())
              .then(data => {
                if (data && data.id) {
                  toggleInlineEdit(card, data);
                }
              })
              .catch(console.error);
          }
        }
      });
    }

    // Function to update floating date based on current scroll position
    function updateFloatingDate() {
      const floatingDateElement = document.getElementById('floatingDate');
      if (!floatingDateElement) return;

      const getFloatingDateMood = (hour) => {
        if (hour >= 5 && hour < 12) {
          return {
            icon: '🌤️',
            bgColor: 'rgba(173, 216, 230, 0.8)',
            textColor: 'black',
            label: 'Morning'
          };
        }
        if (hour >= 12 && hour < 17) {
          return {
            icon: '☀️',
            bgColor: 'rgba(255, 200, 100, 0.8)',
            textColor: 'black',
            label: 'Noon'
          };
        }
        if (hour >= 17 && hour < 20) {
          return {
            icon: '🌇',
            bgColor: 'rgba(200, 120, 60, 0.8)',
            textColor: 'white',
            label: 'Evening'
          };
        }
        return {
          icon: '🌙',
          bgColor: 'rgb(100 100 100 / 90%)',
          textColor: 'white',
          label: 'Night'
        };
      };
      
      // Get all lead cards
      const cards = document.querySelectorAll('.lead-card');
      if (cards.length === 0) return;
      
      // Always show the floating date
      if (!floatingDateVisible) {
        floatingDateElement.classList.add('visible');
        floatingDateVisible = true;
      }
      
      let closestCard = null;
      let closestDistance = Infinity;
      let closestCardDateObj = null;
      const scrollTop = window.scrollY || window.pageYOffset;
      const viewportCenter = scrollTop + (window.innerHeight / 2);
      
      cards.forEach(card => {
        const rect = card.getBoundingClientRect();
        const cardTop = rect.top + scrollTop;
        const cardCenter = cardTop + (rect.height / 2);
        const distance = Math.abs(viewportCenter - cardCenter);
        
        const createdAt = card.getAttribute('data-created-at');
        const d = parseMysqlDateTime(createdAt);
        if (!d || isNaN(d.getTime())) return;

        if (distance < closestDistance) {
          closestDistance = distance;
          closestCard = card;
          closestCardDateObj = d;
        }
      });
      
      // Display the closest card's data
      if (closestCard && closestCardDateObj) {
            // Format the card's date and time
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const cardDay = String(closestCardDateObj.getDate()).padStart(2, '0');
            const cardMonth = months[closestCardDateObj.getMonth()];
            const cardYear = String(closestCardDateObj.getFullYear()).slice(-2);
            const cardDateText = `${cardDay} ${cardMonth} ${cardYear}`;
            
            const cardHours = String(closestCardDateObj.getHours()).padStart(2, '0');
            const cardMinutes = String(closestCardDateObj.getMinutes()).padStart(2, '0');
            const cardTime = `${cardHours}:${cardMinutes}`;
            
            const closestDateOnly = new Date(closestCardDateObj.getFullYear(), closestCardDateObj.getMonth(), closestCardDateObj.getDate());
            let cardPosition = 0;
            let loadedDayCount = 0;
            cards.forEach(card => {
              const c = card.getAttribute('data-created-at');
              const d = parseMysqlDateTime(c);
              if (!d) return;
              const dateOnly = new Date(d.getFullYear(), d.getMonth(), d.getDate());
              if (dateOnly.getTime() !== closestDateOnly.getTime()) return;
              loadedDayCount++;
              if (card === closestCard) {
                cardPosition = loadedDayCount;
              }
            });

            const totalFromDom = parseInt(closestCard.getAttribute('data-day-total'), 10);
            const rankFromDom = parseInt(closestCard.getAttribute('data-day-rank'), 10);
            const hasTotal = Number.isFinite(totalFromDom) && totalFromDom > 0;
            const hasRank = Number.isFinite(rankFromDom) && rankFromDom > 0;

            const cardDateRecords = hasTotal ? totalFromDom : (loadedDayCount || '?');
            let cardPositionText = '?';
            if (hasTotal && hasRank) {
              cardPositionText = String(totalFromDom - rankFromDom + 1);
            } else if (Number.isFinite(cardDateRecords) && cardPosition > 0) {
              cardPositionText = String(cardDateRecords - cardPosition + 1);
            }
            
            // Update the display
            currentFloatingDate = cardDateText;
            
            // Determine background color based on card's time (hour)
            const cardHour = closestCardDateObj.getHours();
            const floatingMood = getFloatingDateMood(cardHour);
            
            // Display card's date, time, and position within records from same date
            floatingDateElement.innerHTML = `
              <span class="floating-date-icon" aria-hidden="true">${floatingMood.icon}</span>
              <span class="floating-date-text">${cardDateText} ${cardTime} (${cardPositionText}/${cardDateRecords})</span>
            `;
            floatingDateElement.setAttribute('aria-label', `${floatingMood.label}: ${cardDateText} ${cardTime} (${cardPositionText} of ${cardDateRecords})`);
            floatingDateElement.style.background = floatingMood.bgColor;
            floatingDateElement.style.color = floatingMood.textColor;
      }
    }

    function syncFloatingDateOffset() {
      const floatingDateElement = document.getElementById('floatingDate');
      if (!floatingDateElement) return;
      const pageHeader = document.getElementById('pageHeader');
      const searchBar = document.getElementById('searchBar');
      const headerVisible = pageHeader && !pageHeader.classList.contains('hidden');
      const searchVisible = searchBar && !searchBar.classList.contains('hidden');
      const headerHeight = headerVisible ? pageHeader.offsetHeight : 0;
      const searchHeight = searchVisible ? searchBar.offsetHeight : 0;
      const topOffset = Math.max(16, headerHeight + searchHeight + 10);
      floatingDateElement.style.setProperty('--floating-date-top', `${topOffset}px`);
    }

    // ✅ Auto-save all currently edited cards and collapse them
    function autoSaveAllEditedCards() {
      if (editedCards.size === 0) return;
      
      console.log('Auto-saving', editedCards.size, 'edited cards...');
      
      editedCards.forEach((editedCard, leadId) => {
        const card = editedCard.element;
        const form = card.querySelector('form');
        
        if (form && card.parentElement) { // Make sure card is still in DOM
          const formData = new FormData(form);
          const leadData = {};
          for (let [key, value] of formData.entries()) {
            leadData[key] = value;
          }
          
          console.log('Saving lead:', leadId, leadData);
          
          // Save and show notification
          saveLead(leadData, true, leadId);
          
          // Collapse the card after saving by fetching its data and toggling
          const code = `L-${leadId}`;
          fetch(`lead_fetch.php?leadId=${encodeURIComponent(code)}`)
            .then(res => {
              if (!res.ok) throw new Error("Failed to fetch lead details");
              return res.json();
            })
            .then(data => {
              if (data && data.id) {
                // Collapse the card back to view mode
                toggleInlineEdit(card, data);
              }
            })
            .catch(err => {
              console.error("Error fetching lead data for collapse:", err);
            });
        }
      });
      
      // Clear edited cards after saving
      editedCards.clear();
    }

    function trimExcessRows() {
      // Remove any empty row wrappers left behind by previous trims
      container.querySelectorAll('.col.s12').forEach(col => {
        if (!col.querySelector('.lead-card')) col.remove();
      });

      const items = Array.from(container.querySelectorAll('.lead-card'));
      if (items.length <= MAX_ROWS) return;

      const extra = items.length - MAX_ROWS;

      // Removed condition (offsetEnd - offsetStart > MAX_ROWS) to ensure we always trim DOM bloat
      if (lastScrollDirection === 'down') {
          // Check if we need to auto-save edited cards before removing them
          for (let i = 0; i < extra; i++) {
            const card = items[i];
            const leadId = card.getAttribute('data-lead-id');
            const isNew = card.getAttribute('data-is-new') === 'true';
            
            // If this card is being edited, auto-save it
            if (leadId && editedCards.has(leadId)) {
              const editedCard = editedCards.get(leadId);
              const form = card.querySelector('form');
              if (form) {
                const formData = new FormData(form);
                const leadData = {};
                for (let [key, value] of formData.entries()) {
                  leadData[key] = value;
                }
                
                // Save without showing default toast (we'll show auto-save notification with jump link)
                saveLead(leadData, false, leadId);
              }
              // Remove from tracking
              editedCards.delete(leadId);
            }
            
            const wrapper = card.closest('.col.s12');
            if (wrapper && wrapper.parentElement === container) wrapper.remove();
            else card.remove();
            
            // Only increment offset if no other copy of this lead exists (prevent offset inflation from duplicates)
            // Note: We just removed 'card', so we check if any OTHER card with same ID exists
            if (!container.querySelector(`.lead-card[data-lead-id="${leadId}"]`)) {
                // If it's a new lead inserted dynamically, removing it shouldn't affect DB offset
                if (!isNew) {
                    offsetStart++;
                }
            }
          }
        }
        else {
          // Check if we need to auto-save edited cards before removing them
          for (let i = 0; i < extra; i++) {
            const card = items[items.length - 1 - i];
            const leadId = card.getAttribute('data-lead-id');
            const isNew = card.getAttribute('data-is-new') === 'true';
            
            // If this card is being edited, auto-save it
            if (leadId && editedCards.has(leadId)) {
              const editedCard = editedCards.get(leadId);
              const form = card.querySelector('form');
              if (form) {
                const formData = new FormData(form);
                const leadData = {};
                for (let [key, value] of formData.entries()) {
                  leadData[key] = value;
                }
                
                // Save without showing default toast (we'll show auto-save notification with jump link)
                saveLead(leadData, false, leadId);
              }
              // Remove from tracking
              editedCards.delete(leadId);
            }
            
            const wrapper = card.closest('.col.s12');
            if (wrapper && wrapper.parentElement === container) wrapper.remove();
            else card.remove();
            
            // Only decrement offset if no other copy of this lead exists
            if (!container.querySelector(`.lead-card[data-lead-id="${leadId}"]`)) {
                // If it's a new lead inserted dynamically, removing it shouldn't affect DB offset
                if (!isNew) {
                    offsetEnd--;
                }
            }
          }
        }
    }

    function renderFilterCountDetails(monthCounts) {
      if (!filterCountsPanel) return;
      const items = Array.isArray(monthCounts) ? monthCounts : [];
      filterCountsPanel.innerHTML = items.map((item) => {
        const label = item && item.label ? String(item.label) : '-';
        const count = Number(item && item.count);
        const safeCount = Number.isFinite(count) ? String(count) : '-';
        return `<span class="filter-count-detail"><span>${label}</span><strong>${safeCount}</strong></span>`;
      }).join('');
    }

    function setFilterCountsExpanded(expanded) {
      if (!filterCountsToggle || !filterCountsPanel) return;
      const isOpen = !!expanded;
      filterCountsToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      filterCountsPanel.classList.toggle('is-open', isOpen);
    }

    function updateFilterCounts() {
      if (!countThisMonthEl) return;
      if (filterCountsBar) filterCountsBar.classList.add('is-loading');
      const params = new URLSearchParams({
        count_summary: '1',
        search: searchQuery || '',
        mine: mineQuery || '',
        follow: followQuery || '',
        status: statusQuery || '',
        col1: col1Query || '',
        col2: col2Query || ''
      });
      fetch(`lead_fetch.php?${params.toString()}`)
        .then((res) => {
          if (!res.ok) throw new Error('Failed to load counts');
          return res.json();
        })
        .then((data) => {
          const monthCounts = Array.isArray(data && data.month_counts) ? data.month_counts : [];
          const firstMonth = monthCounts[0] || null;
          const thisMonth = Number(firstMonth && firstMonth.count);
          countThisMonthEl.textContent = Number.isFinite(thisMonth) ? String(thisMonth) : '-';
          renderFilterCountDetails(monthCounts);
        })
        .catch(() => {
          countThisMonthEl.textContent = '-';
          renderFilterCountDetails([]);
        })
        .finally(() => {
          if (filterCountsBar) filterCountsBar.classList.remove('is-loading');
        });
    }

    function resetStateAndReload() {
      bumpViewGeneration();
      isLoading = false;
      favoritesViewActive = false;
      historyViewActive = false;
      const favBtn = document.querySelector('.favorites-btn');
      if (favBtn) favBtn.innerHTML = '<i class="fa-regular fa-heart"></i>';
      const histBtn = document.querySelector('.history-btn');
      if (histBtn) histBtn.innerHTML = '<i class="fa-solid fa-clock-rotate-left"></i>';
      lastScrollDirection = 'down';
      offsetStart = 0;
      offsetEnd = 0;
      allLeadsMap.clear(); // Clear stats when reloading
      editedCards.clear();
      container.querySelectorAll('.day-separator').forEach(el => el.remove());
      container.querySelectorAll('.col.s12').forEach(el => el.remove());
      updateFilterCounts();
      fetchRows('down');
    }

    function sanitizeQuoteAmount(value) {
      const raw = value === null || typeof value === 'undefined' ? '' : String(value);
      const cleaned = raw.replace(/[^\d.]/g, '');
      const parts = cleaned.split('.');
      if (parts.length <= 2) return cleaned;
      return parts[0] + '.' + parts.slice(1).join('');
    }
    
    // ✅ Save lead data
    function saveLead(leadData, showNotification = true, leadId = null) {
      if (leadData && typeof leadData === 'object' && Object.prototype.hasOwnProperty.call(leadData, 'quote')) {
        const cleanedQuote = sanitizeQuoteAmount(leadData.quote);
        leadData.quote = cleanedQuote;
      }
      fetch('lead_save.php', {
        method: 'POST',
        body: JSON.stringify(leadData),
        headers: { 'Content-Type': 'application/json' }
      })
      .then(async response => {
        const text = await response.text();
        console.log('Raw response text:', text);

        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          throw new Error('Invalid JSON: ' + text);
        }

        if (data.success) {
          if (showNotification) {
            showAutoSaveNotification(data.message || 'Lead saved successfully', data.id || leadData.leadId);
          }

          const savedId = data.id || leadData.leadId || leadId;
          const savedCard = container ? container.querySelector(`.lead-card[data-lead-id="${savedId}"]`) : null;
          const savedMid = savedCard ? (savedCard.getAttribute('data-mid') || '') : '';
          addToHistory(savedMid || savedId);
          if (typeof data.assign === 'string' && data.assign.trim() !== '') {
            leadData.Assign = data.assign.trim();
          }
          if (typeof data.originally_assigned === 'string') {
            leadData.originally_assigned = data.originally_assigned.trim();
          }
          if (typeof data.edit_trace === 'string' && data.edit_trace.trim() !== '') {
            leadData.edit_trace = data.edit_trace;
          }
          
          // ✅ update that card directly
          updateSingleCard(leadData, savedId);
        } else {
          if (showNotification) {
            showAutoSaveNotification(data.message || 'Failed to save lead', data.id || leadData.leadId, 'error');
          }
        }
      })
      .catch(error => {
        console.error('Fetch error:', error);
        if (showNotification) {
          showAutoSaveNotification('Error saving lead. Please try again.', leadId, 'error');
        }
      });
    }

    // ✅ Show auto-save notification at the top with jump link
    function showAutoSaveNotification(message, leadId = null, type = 'success') {
      // Remove existing notification if any
      const existingNotification = document.getElementById('auto-save-notification');
      if (existingNotification) {
        existingNotification.remove();
      }

      // Set background color based on type
      const backgroundColor = type === 'error' ? '#f44336' : '#4caf50';

      // Create notification element
      const notification = document.createElement('div');
      notification.id = 'auto-save-notification';
      notification.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background-color: ${backgroundColor};
        color: white;
        padding: 10px 0;
        z-index: 9999;
        font-weight: 500;
        animation: slideDown 0.3s ease-in-out;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
      `;
      notification.innerHTML = `
        <span>${message}</span>
        ${leadId ? `<a href="#" class="jump-to-card" data-lead-id="${leadId}" style="color: white; text-decoration: underline; cursor: pointer;">View</a>` : ''}
      `;

      // Add animation style
      if (!document.querySelector('style[data-auto-save]')) {
        const style = document.createElement('style');
        style.setAttribute('data-auto-save', 'true');
        style.textContent = `
          @keyframes slideDown {
            from {
              opacity: 0;
              transform: translateY(-100%);
            }
            to {
              opacity: 1;
              transform: translateY(0);
            }
          }
        `;
        document.head.appendChild(style);
      }

      document.body.appendChild(notification);

      // Auto-remove notification after 5 seconds
      setTimeout(() => {
        notification.style.animation = 'slideDown 0.3s ease-in-out reverse';
        setTimeout(() => {
          notification.remove();
        }, 300);
      }, 5000);

      // Add event listener for jump link
      if (leadId) {
        const jumpLink = notification.querySelector('.jump-to-card');
        if (jumpLink) {
          jumpLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove notification immediately
            notification.remove();
            
            // Try to find the card in current view using the edit button's data-code attribute
            const code = `L-${leadId}`;
            const editBtn = container.querySelector(`.edit-lead-btn[data-code="${code}"]`);
            
            if (editBtn) {
              const card = editBtn.closest('.lead-card');
              if (card) {
                // Scroll to the card
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Add temporary highlight effect
                card.style.transition = 'background-color 0.5s';
                card.style.backgroundColor = '#e8f5e9';
                setTimeout(() => {
                  card.style.backgroundColor = '';
                }, 2000);
              }
            } else {
              // Card not in current view, show message
              showAutoSaveNotification('Card may be out of view. Try scrolling to find it.', leadId, 'error');
            }
          });
        }
      }
    }

    // ✅ Update only the edited card without reloading the page
    function updateSingleCard(updatedData, leadId) {
      const code = `L-${leadId}`;
      const existingEditBtn = container.querySelector(`.edit-lead-btn[data-code="${code}"]`);
      if (!existingEditBtn) {
        console.warn('No matching card found to update.');
        return;
      }
      
      const existingCard = existingEditBtn.closest('.lead-card');
      const currentMid = existingCard ? (existingCard.getAttribute('data-mid') || '') : '';
      const currentCol1 = existingCard ? (existingCard.getAttribute('data-column-1') || '') : '';
      const currentCol2 = existingCard ? (existingCard.getAttribute('data-column-2') || '') : '';
      const currentQuote = existingCard ? (existingCard.getAttribute('data-quote') || '') : '';
      const currentInstallationDate = existingCard ? (existingCard.getAttribute('data-installation-date') || '') : '';
      const currentQuoteLinks = existingCard ? (existingCard.getAttribute('data-quote-links') || '') : '';
      const currentCreatedAt = existingCard ? (existingCard.getAttribute('data-created-at') || '') : '';
      const currentDayTotal = existingCard ? (existingCard.getAttribute('data-day-total') || '') : '';
      const currentDayRank = existingCard ? (existingCard.getAttribute('data-day-rank') || '') : '';
      const currentOriginallyAssigned = existingCard ? (existingCard.getAttribute('data-originally-assigned') || '') : '';
      const currentEditTrace = existingCard ? (existingCard.getAttribute('data-edit-trace') || '') : '';

      const merged = {
        id: leadId,
        MID: currentMid,
        Column_1: currentCol1,
        Column_2: currentCol2,
        created_at: currentCreatedAt,
        day_total: currentDayTotal,
        day_rank: currentDayRank,
        originally_assigned: currentOriginallyAssigned,
        edit_trace: currentEditTrace,
        Name: existingCard ? (existingCard.getAttribute('data-name') || '') : '',
        whatsapp_number: existingCard ? (existingCard.getAttribute('data-whatsapp-number') || '') : '',
        hdd_size: existingCard ? (existingCard.getAttribute('data-hdd-size') || '') : '',
        num_cameras: existingCard ? (existingCard.getAttribute('data-num-cameras') || '') : '',
        camera_resolution: existingCard ? (existingCard.getAttribute('data-camera-resolution') || '') : '',
        dvr_type: existingCard ? (existingCard.getAttribute('data-dvr-type') || '') : '',
        Assign: existingCard ? (existingCard.getAttribute('data-assign') || '') : '',
        Area: existingCard ? (existingCard.getAttribute('data-area') || '') : '',
        Follow_up: existingCard ? (existingCard.getAttribute('data-follow-up') || '') : '',
        comments: existingCard ? (existingCard.getAttribute('data-comments') || '') : '',
        Message: existingCard ? (existingCard.getAttribute('data-message') || '') : '',
        map_link: existingCard ? (existingCard.getAttribute('data-map-link') || '') : '',
        call_status: existingCard ? (existingCard.getAttribute('data-call-status') || '') : '',
        quote: (typeof updatedData.quote !== 'undefined' && updatedData.quote !== null) ? updatedData.quote : currentQuote,
        installation_date: (typeof updatedData.installation_date !== 'undefined' && updatedData.installation_date !== null) ? updatedData.installation_date : currentInstallationDate,
        quote_links: currentQuoteLinks
      };

      Object.keys(updatedData || {}).forEach((k) => {
        if (typeof updatedData[k] !== 'undefined' && updatedData[k] !== null) {
          merged[k] = updatedData[k];
        }
      });

      if (!merged.created_at) merged.created_at = new Date().toISOString();
      if (typeof merged.quote_links === 'undefined' || merged.quote_links === null || merged.quote_links === '') {
        merged.quote_links = currentQuoteLinks;
      }

      // Recreate the updated card using existing function
      const newCard = createRow(merged);

      const cardContainer = existingCard.closest('.col');
      cardContainer.replaceWith(newCard);

      // Apply gradient save effect on the new container
      const newCardEl = newCard.querySelector('.lead-card');
      if (newCardEl) {
          newCardEl.classList.add('save-gradient');
          setTimeout(() => {
            newCardEl.classList.remove('save-gradient');
          }, 4000); // show effect for 4 seconds
      }
    }

    let container, topSentinel, bottomSentinel, searchInput, clearBtn, status, searchSubmit, searchSuggestions, filterCountsBar, countThisMonthEl, countThisMonthLabelEl, filterCountsToggle, filterCountsPanel; // Declare variables here
    
    document.addEventListener('DOMContentLoaded', function() {
      const bgImages = [
        'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1444703686981-a3abbc4d4fe3?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1462331940025-496dfbfc7564?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1470770903676-69b98201ea1c?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1487958449943-2429e8be8625?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1444723121867-7a241cacace9?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1524492412937-b28074a5d7da?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1547153760-18fc86324498?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1426604966848-d7adac402bff?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1528909514045-2fa4ac7a08ba?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1465146344425-f00d5f5c8f07?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1444044205806-38f3ed106c10?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1472214103451-9374bd1c798e?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1471879832106-c7ab9e0cee23?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1474511320723-9a56873867b5?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1503264116251-35a269479413?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1496307042754-b4aa456c4a2d?auto=format&fit=crop&w=2400&q=80',
        'https://images.unsplash.com/photo-1437622368342-7a3d73a34c8f?auto=format&fit=crop&w=2400&q=80'
      ];
      const setBackgroundImage = (baseUrl) => {
        return new Promise((resolve, reject) => {
          const url = `${baseUrl}${baseUrl.includes('?') ? '&' : '?'}sig=${Date.now()}-${Math.floor(Math.random() * 1e9)}`;
          const img = new Image();
          img.referrerPolicy = 'no-referrer';
          img.onload = () => resolve(url);
          img.onerror = () => reject(new Error('Background image failed to load'));
          img.src = url;
        });
      };

      const trySetRandomBackground = async () => {
        const remaining = bgImages.slice();
        for (let attempt = 0; attempt < 8 && remaining.length > 0; attempt++) {
          const idx = Math.floor(Math.random() * remaining.length);
          const baseUrl = remaining.splice(idx, 1)[0];
          try {
            const loadedUrl = await setBackgroundImage(baseUrl);
            document.documentElement.style.setProperty('--page-bg-image', `url("${loadedUrl}")`);
            return;
          } catch (e) {
          }
        }
      };

      trySetRandomBackground();

      // Assign elements inside DOMContentLoaded
      container = document.getElementById('container');
      topSentinel = document.getElementById('top-sentinel');
      bottomSentinel = document.getElementById('bottom-sentinel');
      searchInput = document.getElementById('searchInput');
      clearBtn = document.getElementById('clearBtn');
      searchSubmit = document.getElementById('searchSubmit');
      searchSuggestions = document.getElementById('searchSuggestions');
      status = document.getElementById('status');
      filterCountsBar = document.getElementById('filterCountsBar');
      countThisMonthEl = document.getElementById('countThisMonth');
      countThisMonthLabelEl = document.getElementById('countThisMonthLabel');
      filterCountsToggle = document.getElementById('filterCountsToggle');
      filterCountsPanel = document.getElementById('filterCountsPanel');
      const mineLinks = Array.from(document.querySelectorAll('.mine-filter-link'));
      const followLinks = Array.from(document.querySelectorAll('.follow-filter-link'));
      const dateRangeSelect = document.getElementById('dateRangeSelect');
      const customDateWrap = document.getElementById('customDateWrap');
      const customStartInput = document.getElementById('customStartDate');

      const monthLabel = (d) => d.toLocaleString('default', { month: 'short' });
      const updateDateOptionLabels = () => {
        const selects = [dateRangeSelect].filter(Boolean);
        const now = new Date();
        const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const minus2 = new Date(now.getFullYear(), now.getMonth() - 2, 1);
        if (countThisMonthLabelEl) countThisMonthLabelEl.textContent = `Current month (${monthLabel(now)})`;
        if (!selects.length) return;
        selects.forEach(sel => {
          Array.from(sel.options).forEach(opt => {
            if (opt.value === 'last_month') opt.textContent = `Last month (${monthLabel(lastMonth)})`;
            if (opt.value === 'month_minus_2') opt.textContent = `Current Month - 2 (${monthLabel(minus2)})`;
          });
        });
      };
      updateDateOptionLabels();

      if (filterCountsToggle && filterCountsPanel) {
        setFilterCountsExpanded(false);
        filterCountsToggle.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          const expanded = filterCountsToggle.getAttribute('aria-expanded') === 'true';
          setFilterCountsExpanded(!expanded);
        });
      }

      const syncCustomWrap = () => {
        const isCustom = String(dateMode) === 'custom';
        if (customDateWrap) customDateWrap.hidden = !isCustom;
      };
      const syncDateUi = () => {
        if (dateRangeSelect) dateRangeSelect.value = dateMode;
        if (customStartInput && customStartDate) customStartInput.value = customStartDate;
        syncCustomWrap();
      };
      syncDateUi();

      if (dateRangeSelect) {
        dateRangeSelect.addEventListener('change', () => {
          dateMode = dateRangeSelect.value;
          syncDateUi();
          if (dateMode !== 'custom') {
            customStartDate = '';
            resetStateAndReload();
          }
        });
      }

      const applyCustomStartDate = (startEl) => {
        if (!startEl) return;
        const s = String(startEl.value || '').trim();
        if (!s) return;
        customStartDate = s;
        dateMode = 'custom';
        syncDateUi();
        resetStateAndReload();
      };

      if (customStartInput) {
        customStartInput.addEventListener('change', () => applyCustomStartDate(customStartInput));
      }
      const bindDatePickerOpen = (inputEl) => {
        if (!inputEl) return;
        const wrap = inputEl.closest('.date-input-wrap');
        const open = () => {
          if (typeof inputEl.showPicker === 'function') {
            try { inputEl.showPicker(); return; } catch (e) {}
          }
          try { inputEl.focus(); } catch (e) {}
          try { inputEl.click(); } catch (e) {}
        };
        if (wrap) wrap.addEventListener('click', open);
      };
      bindDatePickerOpen(customStartInput);

      // Initialize lastScrollCardsCount
      lastScrollCardsCount = 0;
      syncFloatingDateOffset();

      // Event delegation for 'Edit Lead' buttons
      if (container) { // Check if container is not null before adding event listener
        container.addEventListener('click', function(event) {
          const statusBadge = event.target.closest('.status-badge');
          if (statusBadge) {
            event.preventDefault();
            event.stopPropagation();
            const val = (statusBadge.getAttribute('data-status-value') || '').trim();
            if (val) {
              if (statusQuery.toLowerCase() === val.toLowerCase()) {
                statusQuery = '';
              } else {
                statusQuery = val;
              }
              resetStateAndReload();
            }
            return;
          }
          const col1Badge = event.target.closest('.col1-badge');
          if (col1Badge) {
            event.preventDefault();
            event.stopPropagation();
            const val = (col1Badge.getAttribute('data-col1-value') || col1Badge.textContent || '').trim();
            if (val) {
              if (col1Query.toLowerCase() === val.toLowerCase()) {
                col1Query = '';
              } else {
                col1Query = val;
              }
              resetStateAndReload();
            }
            return;
          }
          const col2Badge = event.target.closest('.col2-badge');
          if (col2Badge) {
            event.preventDefault();
            event.stopPropagation();
            const val = (col2Badge.getAttribute('data-col2-value') || col2Badge.textContent || '').trim();
            if (val) {
              if (col2Query.toLowerCase() === val.toLowerCase()) {
                col2Query = '';
              } else {
                col2Query = val;
              }
              resetStateAndReload();
            }
            return;
          }
          const favBtn = event.target.closest('.fav-toggle');
          if (favBtn) {
            event.preventDefault();
            event.stopPropagation();
            const mid = favBtn.getAttribute('data-mid') || '';
            const result = toggleFavoriteMid(mid);
            if (result.changed) {
              setFavButtonState(favBtn, result.isFav);
            } else if (result.reason === 'limit') {
              showAutoSaveNotification('Max 50 favourites allowed', null, 'error');
            }
            return;
          }

          const markerBtn = event.target.closest('.lead-marker-btn');
          if (markerBtn) {
            event.preventDefault();
            event.stopPropagation();
            const card = markerBtn.closest('.lead-card');
            const leadId = card ? (card.getAttribute('data-lead-id') || '').trim() : '';
            const marker = markerBtn.getAttribute('data-marker');
            const nextMarker = setLeadMarker(leadId, marker);
            if (card) applyMarkerDom(card, nextMarker);
            return;
          }

          const traceBtn = event.target.closest('.trace-toggle');
          if (traceBtn) {
            event.preventDefault();
            event.stopPropagation();
            const card = traceBtn.closest('.lead-card');
            if (!card) return;
            const leadId = (card.getAttribute('data-lead-id') || '').trim();
            if (tracePopoverAnchorId && tracePopoverAnchorId === leadId) {
              hideTracePopover();
              return;
            }

            const pop = ensureTracePopoverEl();
            pop.innerHTML = buildTraceCalloutHtml(card.getAttribute('data-edit-trace') || '');
            pop.style.display = 'block';
            pop.style.visibility = 'hidden';

            const popRect = pop.getBoundingClientRect();
            const btnRect = traceBtn.getBoundingClientRect();

            let top = btnRect.top - popRect.height - 10;
            if (top < 10) top = btnRect.bottom + 10;

            let left = btnRect.right - popRect.width;
            left = Math.max(10, Math.min(left, window.innerWidth - popRect.width - 10));

            pop.style.top = `${Math.round(top)}px`;
            pop.style.left = `${Math.round(left)}px`;
            pop.style.visibility = 'visible';
            tracePopoverAnchorId = leadId;
            return;
          }

          const deleteBtn = event.target.closest('.lead-delete-btn');
          if (deleteBtn) {
            event.preventDefault();
            event.stopPropagation();
            if (!CAN_DELETE_LEAD) return;

            const card = deleteBtn.closest('.lead-card');
            const leadId = (deleteBtn.getAttribute('data-lead-id') || (card ? card.getAttribute('data-lead-id') : '') || '').trim();
            const leadName = card ? (card.getAttribute('data-name') || '').trim() : '';
            if (!leadId) {
              showAutoSaveNotification('Lead id missing.', null, 'error');
              return;
            }
            const label = leadName ? `${leadName} (${leadId})` : `Lead ${leadId}`;
            if (!confirm(`Delete ${label}? This cannot be undone.`)) return;

            deleteBtn.disabled = true;
            fetch('lead_save.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              credentials: 'same-origin',
              body: JSON.stringify({ deleteLead: true, leadId })
            })
              .then(async (response) => {
                const raw = await response.text();
                let data = null;
                try {
                  data = raw ? JSON.parse(raw) : null;
                } catch (e) {
                  throw new Error(raw || 'Failed to delete lead.');
                }
                if (!response.ok || !data || !data.success) {
                  throw new Error((data && data.message) || 'Failed to delete lead.');
                }
                return data;
              })
              .then(() => {
                if (card) {
                  const wrap = card.closest('.col.s12');
                  const target = wrap || card;
                  target.style.transition = 'opacity 0.18s ease, transform 0.18s ease';
                  target.style.opacity = '0';
                  target.style.transform = 'scale(0.98)';
                  window.setTimeout(() => {
                    target.remove();
                    rebuildDaySeparators();
                  }, 180);
                }
                allLeadsMap.delete(parseInt(leadId, 10));
                showAutoSaveNotification('Lead deleted.', null, 'success');
              })
              .catch((err) => {
                deleteBtn.disabled = false;
                showAutoSaveNotification(err && err.message ? err.message : 'Failed to delete lead.', leadId, 'error');
              });
            return;
          }

          // Handle edit button clicks
          if (event.target.closest('.edit-lead-btn')) {
            event.preventDefault(); // Prevent default link behavior
            const editBtn = event.target.closest('.edit-lead-btn');
            const code = editBtn.dataset.code;
            
            // Check if there's another card in edit mode and collapse it
            const editingCard = container.querySelector('[data-editing="true"]');
            if (editingCard) {
              const editingCode = editingCard.getAttribute('data-lead-id');
              const prefixedCode = `L-${editingCode}`;
              
              // Only collapse if it's a different card
              if (prefixedCode !== code) {
                // Get form data and save before collapsing
                const form = editingCard.querySelector('form');
                if (form) {
                  const formData = new FormData(form);
                  const leadData = {};
                  for (let [key, value] of formData.entries()) {
                    leadData[key] = value;
                  }
                  
                  // Save without showing default toast (we'll show auto-save notification with jump link)
                  saveLead(leadData, false, editingCode);
                }
                
                // Fetch lead data and collapse the card
                fetch(`lead_fetch.php?leadId=${encodeURIComponent(prefixedCode)}`)
                  .then(res => {
                    if (!res.ok) throw new Error("Failed to fetch lead details");
                    return res.json();
                  })
                  .then(data => {
                    if (data && data.id) {
                      toggleInlineEdit(editingCard, data);
                    }
                  })
                  .catch(err => {
                    console.error("Error fetching lead data:", err);
                  });
              }
            }
            
            // Prefer local data attached to the card to avoid backend dependency
            const cardElement = editBtn.closest('.lead-card');
            const ds = cardElement ? cardElement.dataset : {};
            const rid = code && code.startsWith('L-') ? code.slice(2) : code;
            if (cardElement && ds.leadId) {
              const localLeadData = {
                id: ds.leadId || rid,
                MID: ds.mid || '',
                Name: ds.name || '',
                whatsapp_number: ds.whatsappNumber || '',
                num_cameras: ds.numCameras || '',
                dvr_type: ds.dvrType || '',
                hdd_size: ds.hddSize || '',
                camera_resolution: ds.cameraResolution || '',
                Assign: ds.assign || '',
                Area: ds.area || '',
                Follow_up: ds.followUp || '',
                comments: ds.comments || '',
                Message: ds.message || '',
                map_link: ds.mapLink || '',
                call_status: ds.callStatus || '',
                quote: ds.quote || '',
                installation_date: ds.installationDate || '',
                quote_links: ds.quoteLinks || ''
              };
              toggleInlineEdit(cardElement, localLeadData);
            } else {
              // Fallback to server fetch when local data not present
              fetch(`lead_fetch.php?leadId=${encodeURIComponent(code)}`)
                .then(res => {
                  if (!res.ok) throw new Error("Failed to fetch lead details");
                  return res.json();
                })
                .then(data => {
                  if (data && data.error) {
                    showAutoSaveNotification(`Error loading lead details: ${data.error}`, null, 'error');
                    return;
                  }
                  if (!data || !data.id) {
                    showAutoSaveNotification("Lead not found", null, 'error');
                    return;
                  }
                  toggleInlineEdit(cardElement, data);
                })
                .catch(err => {
                  console.error("Error fetching lead data:", err);
                  showAutoSaveNotification("Error loading lead details", null, 'error');
                });
            }
          }
          // Handle save button clicks in inline forms
          else if (event.target.closest('.save-lead-btn')) {
            event.preventDefault();
            const saveBtn = event.target.closest('.save-lead-btn');
            const form = saveBtn.closest('form');
            const cardElement = saveBtn.closest('.lead-card');
            const leadId = cardElement.getAttribute('data-lead-id');
            const formData = new FormData(form);
            const leadData = {};
            for (let [key, value] of formData.entries()) {
              leadData[key] = value;
            }
            
            // --- VALIDATION START ---
            if (!leadData.call_status || leadData.call_status.trim() === '') {
                showAutoSaveNotification('Please select a Call Status', null, 'error');
                // Find the specific Call Status dropdown wrapper
                const callStatusSelect = form.querySelector('select[name="call_status"]');
                if (callStatusSelect) {
                    const wrapper = callStatusSelect.closest('.select-wrapper');
                    if (wrapper) {
                        const trigger = wrapper.querySelector('input.select-dropdown');
                        if (trigger) trigger.click();
                    }
                }
                return;
            }
            
            if (!leadData.Follow_up || leadData.Follow_up.trim() === '') {
                showAutoSaveNotification('Please enter Follow Up date', null, 'error');
                const followUpInput = form.querySelector('input[name="Follow_up"]');
                if (followUpInput) {
                     // Try to open datepicker if instance exists
                     if (typeof M !== 'undefined' && M.Datepicker) {
                        const instance = M.Datepicker.getInstance(followUpInput);
                        if (instance) instance.open();
                        else followUpInput.focus();
                     } else {
                        followUpInput.focus();
                     }
                }
                return;
            }

            // Date validation (Present or Future)
            if (leadData.Follow_up !== 'NA' && leadData.Follow_up !== '-') {
                 const parts = leadData.Follow_up.split(' ');
                 if (parts.length === 3) {
                     const day = parseInt(parts[0]);
                     const monthStr = parts[1];
                     const year = 2000 + parseInt(parts[2]);
                     const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                     const month = months.indexOf(monthStr);
                     
                     if (month !== -1) {
                         const followDate = new Date(year, month, day);
                         const today = new Date();
                         today.setHours(0,0,0,0);
                         
                         if (followDate < today) {
                             showAutoSaveNotification('Follow Up date must be today or future', null, 'error');
                             return;
                         }
                     }
                 }
            }
            // --- VALIDATION END ---

            // Save the lead
            saveLead(leadData, true, leadId);
            
            // Collapse the card after saving
            const code = `L-${leadId}`;
            fetch(`lead_fetch.php?leadId=${encodeURIComponent(code)}`)
              .then(res => {
                if (!res.ok) throw new Error("Failed to fetch lead details");
                return res.json();
              })
              .then(data => {
                if (data && data.error) {
                  showAutoSaveNotification(`Error loading lead details: ${data.error}`, null, 'error');
                  return;
                }
                if (data && data.id) {
                  // Collapse the card back to view mode
                  toggleInlineEdit(cardElement, data);
                }
              })
              .catch(err => {
                console.error("Error fetching lead data for collapse:", err);
              });
          }
          // Handle Installation Date button clicks
          else if (event.target.closest('.install-date-btn')) {
            event.preventDefault();
            const btn = event.target.closest('.install-date-btn');
            if (btn.disabled) return;
            const row = btn.closest('.install-date-row');
            const input = row ? row.querySelector('input[name="installation_date"]') : null;
            if (input && typeof M !== 'undefined' && M.Datepicker) {
              const instance = M.Datepicker.getInstance(input);
              if (instance) instance.open();
            }
          }
          // Handle Place Order button clicks
          else if (event.target.closest('.place-order-btn')) {
            event.preventDefault();
            const btn = event.target.closest('.place-order-btn');
            if (btn.disabled) return;
            const form = btn.closest('form');
            const leadId = form.dataset.leadId;
            const cardElement = btn.closest('.lead-card');
            const quoteInput = form.querySelector('input[name="quote"]');
            
            if (!quoteInput || !quoteInput.value.trim()) {
                showAutoSaveNotification("Quote amount is mandatory to place order!", leadId, 'error');
                if(quoteInput) quoteInput.focus();
                return;
            }

            const installationInput = form.querySelector('input[name="installation_date"]');
            const installationDate = installationInput ? installationInput.value.trim() : '';
            if (!installationDate) {
                showAutoSaveNotification("Installation date is mandatory to place order!", leadId, 'error');
                const row = form.querySelector('.install-date-row') || (installationInput ? installationInput.closest('.install-date-row') : null);
                const label = row ? row.querySelector('.install-date-text') : null;
                const rowBtn = row ? row.querySelector('.install-date-btn') : null;
                const rowIcon = rowBtn ? rowBtn.querySelector('i') : null;
                if (row) row.classList.add('is-error');
                if (label) label.style.color = '#ef4444';
                if (rowBtn) rowBtn.style.color = '#ef4444';
                if (rowIcon) rowIcon.style.color = '#ef4444';
                if (installationInput && typeof M !== 'undefined' && M.Datepicker) {
                  const instance = M.Datepicker.getInstance(installationInput);
                  if (instance) instance.open();
                  else installationInput.focus();
                }
                return;
            }
            const okRow = installationInput ? installationInput.closest('.install-date-row') : null;
            if (okRow) {
              okRow.classList.remove('is-error');
              const label = okRow.querySelector('.install-date-text');
              const okBtn = okRow.querySelector('.install-date-btn');
              const okIcon = okBtn ? okBtn.querySelector('i') : null;
              if (label) label.style.color = '';
              if (okBtn) okBtn.style.color = '';
              if (okIcon) okIcon.style.color = '';
            }
            
            const formData = new FormData(form);
            const leadData = {};
            for (let [key, value] of formData.entries()) {
              leadData[key] = value;
            }
            leadData.leadId = leadId;
            const cleanedQuote = sanitizeQuoteAmount(quoteInput.value);
            if (!cleanedQuote) {
              showAutoSaveNotification("Invalid quote amount.", leadId, 'error');
              quoteInput.focus();
              return;
            }
            quoteInput.value = cleanedQuote;
            leadData.quote = cleanedQuote;
            leadData.installation_date = installationDate;
            leadData.call_status = 'Ordered';
            leadData.place_order = 1;

            fetch('lead_save.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(leadData)
            })
              .then(res => res.json())
              .then(data => {
                if (data.success) {
                  showAutoSaveNotification(data.message || 'Order placed.', leadId);
                  writeOrderDraft(leadId, { quote: leadData.quote, installation_date: leadData.installation_date });
                  if (data.assign) leadData.Assign = data.assign;
                  if (typeof data.originally_assigned === 'string') {
                    leadData.originally_assigned = data.originally_assigned.trim();
                  }
                  if (data.quote_links !== undefined) leadData.quote_links = data.quote_links;
                  if (data.edit_trace !== undefined) leadData.edit_trace = data.edit_trace;
                  updateSingleCard(leadData, leadId);

                  const code = `L-${leadId}`;
                  fetch(`lead_fetch.php?leadId=${encodeURIComponent(code)}`)
                    .then(res => {
                      if (!res.ok) throw new Error("Failed to fetch lead details");
                      return res.json();
                    })
                    .then(ld => {
                      if (ld && ld.error) {
                        showAutoSaveNotification(`Error loading lead details: ${ld.error}`, null, 'error');
                        return;
                      }
                      if (ld && ld.id) {
                        toggleInlineEdit(cardElement, ld);
                      }
                    })
                    .catch(err => {
                      console.error("Error fetching lead data for collapse:", err);
                    });
                } else {
                  showAutoSaveNotification(data.message || "Failed to place order.", leadId, 'error');
                }
              })
              .catch(err => {
                console.error("Place order error:", err);
                showAutoSaveNotification("Failed to place order.", leadId, 'error');
              });
          }
          // Handle cancel button clicks in inline forms
          else if (event.target.closest('.cancel-edit-btn')) {
            event.preventDefault();
            const cancelBtn = event.target.closest('.cancel-edit-btn');
            const cardElement = cancelBtn.closest('.lead-card');
            const leadId = cardElement.getAttribute('data-lead-id');
            const code = `L-${leadId}`;
            
            // Cancel edit mode locally without backend fetch
            toggleInlineEdit(cardElement, { id: leadId });
          }
          // Handle close (x) button clicks in inline forms
          else if (event.target.closest('.close-edit-btn')) {
            event.preventDefault();
            const closeBtn = event.target.closest('.close-edit-btn');
            const cardElement = closeBtn.closest('.lead-card');
            const leadId = cardElement.getAttribute('data-lead-id');
            const code = `L-${leadId}`;
            
            // Close edit mode locally without backend fetch
            toggleInlineEdit(cardElement, { id: leadId });
          }
        });

        const closeAllAreaSuggests = () => {
          const boxes = container.querySelectorAll('.area-suggest:not([hidden])');
          for (const box of boxes) {
            box.hidden = true;
            box.innerHTML = '';
          }
        };

        container.addEventListener('click', (event) => {
          const item = event.target.closest('.area-suggest-item');
          if (!item) return;
          const group = item.closest('.area-input-group');
          const input = group ? group.querySelector('input[name="Area"]') : null;
          const val = item.getAttribute('data-value') || '';
          if (input) input.value = val;
          if (input) hideAreaSuggest(input);
          event.preventDefault();
          event.stopPropagation();
        }, true);

        container.addEventListener('keydown', (event) => {
          const input = event.target && event.target.matches && event.target.matches('input[name="Area"]') ? event.target : null;
          if (!input) return;
          if (event.key === 'Escape') {
            hideAreaSuggest(input);
          }
        });

        container.addEventListener('input', (event) => {
          const input = event.target && event.target.matches && event.target.matches('input[name="Area"]') ? event.target : null;
          if (!input) return;
          const q = input.value.trim();
          if (q.length < 3) {
            hideAreaSuggest(input);
            return;
          }
          const st = getAreaState(input);
          st.seq += 1;
          const seq = st.seq;
          clearTimeout(st.timer);
          st.timer = setTimeout(() => {
            const currentQ = input.value.trim();
            if (currentQ.length < 3) {
              hideAreaSuggest(input);
              return;
            }
            requestAreaPredictions(input, currentQ, seq);
          }, 180);
        });

        container.addEventListener('focusin', (event) => {
          const input = event.target && event.target.matches && event.target.matches('input[name="Area"]') ? event.target : null;
          if (!input) return;
          const q = input.value.trim();
          if (q.length < 3) return;
          const st = getAreaState(input);
          st.seq += 1;
          requestAreaPredictions(input, q, st.seq);
        });

        document.addEventListener('click', (event) => {
          if (event.target && event.target.closest && event.target.closest('.area-input-group')) return;
          closeAllAreaSuggests();
        }, true);

        console.log('Click event listener set up on container.');
      } else {
        console.error('Container element not found, cannot set up click listener.');
      }

      function submitSearch() {
        const q = (searchInput ? searchInput.value : '').trim();
        if (/^\d+$/.test(q) && q.length > 0 && q.length < 3) {
          if (status) {
            status.innerText = 'Enter at least 3 digits';
            setTimeout(() => {
              if (status && status.innerText === 'Enter at least 3 digits') status.innerText = '';
            }, 1200);
          }
          return;
        }
        searchQuery = q;
        resetStateAndReload();
      }

      function updateSearchSuggestions() {
        if (!searchSuggestions || !container || !searchInput) return;
        const q = (searchInput.value || '').trim().toLowerCase();
        searchSuggestions.innerHTML = '';
        const isDigitsOnly = /^\d+$/.test(q);
        if ((isDigitsOnly && q.length < 3) || (!isDigitsOnly && q.length < 2)) return;

        const seen = new Set();
        const options = [];
        const cards = container.querySelectorAll('.lead-card');
        for (const card of cards) {
          const createdAt = (card.getAttribute('data-created-at') || '').trim();
          const dateOnly = createdAt ? createdAt.slice(0, 10) : '';
          const values = [
            card.getAttribute('data-whatsapp-number') || '',
            card.getAttribute('data-name') || '',
            card.getAttribute('data-mid') || '',
            card.getAttribute('data-area') || '',
            dateOnly,
            (card.getAttribute('data-comments') || '').slice(0, 48)
          ];

          for (const raw of values) {
            const v = (raw || '').trim();
            if (!v || v === '-') continue;
            if (!v.toLowerCase().includes(q)) continue;
            if (seen.has(v)) continue;
            seen.add(v);
            options.push(v);
            if (options.length >= 12) break;
          }
          if (options.length >= 12) break;
        }

        for (const v of options) {
          const opt = document.createElement('option');
          opt.value = v;
          searchSuggestions.appendChild(opt);
        }
      }

      let suggestTimer = null;

      if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') {
            e.preventDefault();
            submitSearch();
          }
        });
        searchInput.addEventListener('input', () => {
          clearTimeout(suggestTimer);
          suggestTimer = setTimeout(updateSearchSuggestions, 120);
        });
      }

      if (searchSubmit) {
        searchSubmit.addEventListener('click', () => {
          submitSearch();
        });
      }

      if (clearBtn) {
        clearBtn.addEventListener('click', () => {
          if (searchInput) searchInput.value = '';
          searchQuery = '';
          if (searchSuggestions) searchSuggestions.innerHTML = '';
          resetStateAndReload();
        });
      }

      if (mineLinks.length > 0) {
        const syncMineLinks = () => {
          mineLinks.forEach((link) => {
            const code = (link.getAttribute('data-mine') || '').trim();
            link.classList.toggle('active', code === mineQuery);
          });
        };

        mineLinks.forEach((link) => {
          link.addEventListener('click', (e) => {
            e.preventDefault();
            const code = (link.getAttribute('data-mine') || '').trim();
            if (code === mineQuery) mineQuery = '';
            else mineQuery = code;
            syncMineLinks();
            resetStateAndReload();
          });
        });

        syncMineLinks();
      }

      if (followLinks.length > 0) {
        const syncFollowLinks = () => {
          followLinks.forEach((link) => {
            const code = (link.getAttribute('data-follow') || '').trim();
            link.classList.toggle('active', code === followQuery);
          });
        };

        followLinks.forEach((link) => {
          link.addEventListener('click', (e) => {
            e.preventDefault();
            const code = (link.getAttribute('data-follow') || '').trim();
            if (code === followQuery) followQuery = '';
            else followQuery = code;
            syncFollowLinks();
            resetStateAndReload();
          });
        });

        syncFollowLinks();
      }

      document.addEventListener('click', (e) => {
        if (e.target.closest('.trace-toggle')) return;
        if (e.target.closest('#trace-popover')) return;
        hideTracePopover();
      });

      window.addEventListener('scroll', hideTracePopover, { passive: true });
      window.addEventListener('resize', hideTracePopover);

      // Initialize Materialize components that need it
      M.updateTextFields(); // For input labels

      updateFilterCounts();

      // Load initial leads
      fetchRows('down');
      
      // Initialize scroll position after initial load
      setTimeout(() => {
        lastScrollCardsCount = container.querySelectorAll('.lead-card').length;
        lastScrollPosition = window.scrollY || window.pageYOffset;
        console.log('Initial state - Cards:', lastScrollCardsCount, 'Scroll position:', lastScrollPosition);
      }, 500);

      refreshAllMarkers();
      scheduleMarkerSweep();
      
      // Header and Search scroll behavior
      const pageHeader = document.getElementById('pageHeader');
      const searchBar = document.getElementById('searchBar');
      const headerSearchIcon = document.getElementById('headerSearchIcon');
      // searchInput already declared above
      let lastScroll = 0;
      
      if (pageHeader && searchBar) {
        // Toggle Search Bar
        if (headerSearchIcon) {
            headerSearchIcon.addEventListener('click', () => {
                pageHeader.classList.add('hidden');
                searchBar.classList.remove('hidden');
                if (searchInput) searchInput.focus();
            });
        }

        window.addEventListener('scroll', () => {
          const currentScroll = window.pageYOffset;
          
          if (currentScroll <= 0) {
            // At top: Show Header, keep Search visible
            pageHeader.classList.remove('hidden');
            searchBar.classList.remove('hidden');
          } else if (currentScroll > lastScroll && currentScroll > 50) {
            // Scrolling down: Hide Header, Show Search
            pageHeader.classList.add('hidden');
            searchBar.classList.remove('hidden');
          } else if (currentScroll < lastScroll) {
            // Scrolling up: Show Header, keep Search visible
            pageHeader.classList.remove('hidden');
            searchBar.classList.remove('hidden');
          }
          
          lastScroll = currentScroll;
          syncFloatingDateOffset();
        });
        window.addEventListener('resize', syncFloatingDateOffset);
      }
    });

    // Intersection Observer setup (remains outside DOMContentLoaded as it uses globally declared variables)
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          if (entry.target.id === 'bottom-sentinel') {
            lastScrollDirection = 'down';
            fetchRows('down');
          }
          if (entry.target.id === 'top-sentinel') {
            lastScrollDirection = 'up';
            fetchRows('up');
          }
        }
      });
    }, {
      root: null,
      rootMargin: '200px 0px',
      threshold: 0.01
    });
    
    // Observe sentinels after they are assigned in DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
      if (topSentinel) observer.observe(topSentinel);
      if (bottomSentinel) observer.observe(bottomSentinel);
      
      // Add scroll event listener to detect scrolling continuously
      let scrollTimeout;
      window.addEventListener('scroll', () => {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
          console.log('Scroll event triggered');
          syncFloatingDateOffset();
          checkAndAutoSaveOnScroll();
        }, 100); // More frequent check for floating date
      });
      // Quote Modal logic
const quoteModal = document.getElementById('quoteModal');
const quoteIframe = document.getElementById('quoteIframe');
const closeQuoteModal = document.getElementById('closeQuoteModal');

// Event delegation: detect click on Quote link
container.addEventListener('click', (e) => {
  if (e.target.closest('.open-quote')) {
    e.preventDefault();
    const quoteUrl = e.target.closest('.open-quote').dataset.quoteUrl;
    openQuoteModal(quoteUrl);
  }
});

// Function to open the quote modal
let scrollPosition = 0;

function openQuoteModal(url) {
  scrollPosition = window.scrollY;
  quoteIframe.src = url;
  quoteModal.classList.add('active');
  
  document.body.style.top = `-${scrollPosition}px`;
  document.body.style.position = 'fixed';
  document.body.style.width = '100%';
  document.body.style.overflowY = 'scroll'; // Prevent layout shift
}

function refreshSavedQuotesForLead(leadId) {
  const lid = parseInt(leadId, 10);
  if (!lid) return;
  fetch(`lead_fetch.php?leadId=${encodeURIComponent('L-' + lid)}`)
    .then(res => res.json())
    .then(data => {
      if (!data || data.error) return;
      const card = container.querySelector(`.lead-card[data-lead-id="${lid}"]`);
      if (!card) return;
      const saved = card.querySelector(`.saved-quotes[data-lead-id="${lid}"]`);
      if (!saved) return;
      const raw = data.quote_links || '';
      card.setAttribute('data-quote-links', raw);
      const quoteCount = countQuoteLinks(raw);
      const indicator = card.querySelector('.quote-count-indicator');
      if (indicator) {
        indicator.classList.toggle('is-empty', quoteCount === 0);
        const num = indicator.querySelector('.quote-count-number');
        if (num) num.textContent = String(quoteCount);
      }
      const links = raw.split(',').map(s => s.trim()).filter(Boolean).slice().reverse();
      saved.innerHTML = renderSavedQuotesLinks(links);
      if (editedCards && editedCards.has(lid)) {
        const entry = editedCards.get(lid);
        if (entry && entry.data) entry.data.quote_links = raw;
      }
    })
    .catch(() => {});
}

window.addEventListener('message', (event) => {
  if (event.origin !== window.location.origin) return;
  const payload = event.data || {};
  if (payload.type === 'quoteSaved') {
    const lid = payload.leadId;
    refreshSavedQuotesForLead(lid);
    if (typeof showAutoSaveNotification === 'function') {
      showAutoSaveNotification('Quote saved.', lid);
    }
  }
});

// Close button event
closeQuoteModal.addEventListener('click', () => {
  quoteModal.classList.remove('active');
  quoteIframe.src = '';
  
  document.body.style.position = '';
  document.body.style.top = '';
  document.body.style.width = '';
  document.body.style.overflowY = '';
  
  window.scrollTo(0, scrollPosition);
});

// Close when clicking outside the modal content
quoteModal.addEventListener('click', (e) => {
  if (e.target === quoteModal) {
    quoteModal.classList.remove('active');
    quoteIframe.src = '';
    
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.width = '';
    document.body.style.overflowY = '';
    
    window.scrollTo(0, scrollPosition);
  }
});

    // --- Realtime UI update helpers (name, specs, assign) ---
    // Helper to build spec span based on value
    function buildSpecSpan(value) {
      const v = (value || '').trim();
      const isUnknown = v.length === 0 || v.toLowerCase() === 'dont-know';
      const safeText = isUnknown ? 'X' : v;
      const cls = isUnknown ? 'spec-unknown' : 'spec-value';
      return `<span class="${cls}">${safeText}</span>`;
    }

    function updateNameFromForm(cardElement, rid) {
      const nameInput = cardElement.querySelector(`#leadName-${rid}`);
      // Updated selector: .lead-info-content is the new wrapper
      const displayEl = cardElement.querySelector('.lead-info-content .lead-name, .lead-info-content .lead-name-placeholder');
      if (!displayEl || !nameInput) return;
      const val = (nameInput.value || '').trim();
      if (val) {
        displayEl.textContent = val;
        displayEl.classList.add('lead-name');
        displayEl.classList.remove('lead-name-placeholder');
      } else {
        displayEl.textContent = 'NAME';
        displayEl.classList.add('lead-name-placeholder');
        displayEl.classList.remove('lead-name');
      }
    }

    // --- Realtime UI update helpers moved to separate script block ---


    });
    

  </script>
    <script>
    // --- Realtime UI update helpers (name, specs, assign) ---
    console.log('Lead List Enhanced: Helper functions loaded (Global)');

    function normalizeAssignValue(v) {
      return String(v || '').trim().toLowerCase();
    }

    function syncAssignBadgeOpenState(assignTag, assignVal) {
      const next = normalizeAssignValue(assignVal);
      const was = normalizeAssignValue(assignTag.dataset.assignValue || '');
      assignTag.dataset.assignValue = String(assignVal || '').trim();
      const isOpen = next === 'open';
      const isSur = next === 'sur';
      assignTag.classList.toggle('is-open', isOpen);
      assignTag.classList.toggle('is-sur', isSur);
      if (isOpen && was !== 'open') {
        assignTag.classList.remove('open-animate');
        void assignTag.offsetWidth;
        assignTag.classList.add('open-animate');
      }
    }

    // Ensure global availability explicitly
    window.updateAssignFromForm = function(cardElement, rid) {
      const assignInput = cardElement.querySelector(`#leadAssign-${rid}`);
      const assignVal = assignInput ? assignInput.value.trim() : '';
      const leftSection = cardElement.querySelector('.lead-controls-section .lead-controls-left');
      if (!leftSection) return;
      let assignTag = leftSection.querySelector('.assign-badge');
      if (assignVal) {
        if (!assignTag) {
          assignTag = document.createElement('span');
          assignTag.className = 'assign-badge';
          leftSection.insertBefore(assignTag, leftSection.firstChild);
        }
        assignTag.textContent = assignVal;
        syncAssignBadgeOpenState(assignTag, assignVal);
      } else if (assignTag) {
        assignTag.remove();
      }
    };
    // Also define as regular function for backward compatibility/local scope preference if needed
    function updateAssignFromForm(cardElement, rid) {
        return window.updateAssignFromForm(cardElement, rid);
    }

    // Helper to build spec span based on value
    function buildSpecSpan(value) {
      const v = (value || '').trim();
      const isUnknown = v.length === 0 || v.toLowerCase() === 'dont-know';
      const safeText = isUnknown ? 'X' : v;
      const cls = isUnknown ? 'spec-unknown' : 'spec-value';
      return `<span class="${cls}">${safeText}</span>`;
    }

    function updateNameFromForm(cardElement, rid) {
      const nameInput = cardElement.querySelector(`#leadName-${rid}`);
      // Updated selector: .lead-info-content is the new wrapper
      const displayEl = cardElement.querySelector('.lead-info-content .lead-name, .lead-info-content .lead-name-placeholder');
      if (!displayEl || !nameInput) return;
      const val = (nameInput.value || '').trim();
      if (val) {
        displayEl.textContent = val;
        displayEl.classList.add('lead-name');
        displayEl.classList.remove('lead-name-placeholder');
      } else {
        displayEl.textContent = 'NAME';
        displayEl.classList.add('lead-name-placeholder');
        displayEl.classList.remove('lead-name');
      }
    }

    function updateSpecsFromForm(cardElement, rid) {
      const hddInput = cardElement.querySelector(`#leadHdd-${rid}`);
      const resInput = cardElement.querySelector(`#leadCameraResolution-${rid}`);
      const camsInput = cardElement.querySelector(`#leadNumCameras-${rid}`);
      const dvrInput = cardElement.querySelector(`#leadDvrType-${rid}`);
      
      // Updated selector: .lead-specs .spec-tag
      const tags = cardElement.querySelectorAll('.lead-specs .spec-tag');
      if (!tags || tags.length < 3) return;

      // HDD
      const hddTag = tags[0];
      const hddVal = hddInput ? hddInput.value : '';
      const hddSpan = hddTag.querySelector('.spec-value, .spec-unknown');
      
      const isHddUnknown = !hddVal || hddVal.trim().toLowerCase() === 'dont-know';
      const hddText = isHddUnknown ? 'X' : hddVal.trim();
      const hddClass = isHddUnknown ? 'spec-unknown' : `spec-value ${getHddColorClass(hddVal)}`;

      if (hddSpan) {
        hddSpan.textContent = hddText;
        hddSpan.className = hddClass;
      } else {
        // If span doesn't exist but label might
        if (hddTag.querySelector('.spec-label')) {
             // Remove any text nodes that might be there and append span
             // Actually safest is to just rebuild
             hddTag.innerHTML = `<span class="spec-label">HDD</span> <span class="${hddClass}">${hddText}</span>`;
        } else {
             hddTag.innerHTML = `<span class="spec-label">HDD</span> <span class="${hddClass}">${hddText}</span>`;
        }
      }

      // RES
      const resTag = tags[1];
      const resVal = resInput ? resInput.value : '';
      const camsVal = camsInput ? camsInput.value : '';
      
      // We expect a .spec-row container now
      let specRow = resTag.querySelector('.spec-row');
      if (!specRow) {
          resTag.innerHTML = `<span class="spec-label">RES</span> <div class="spec-row">${buildSpecSpan(resVal)} <span class="spec-x">x</span> ${buildSpecSpan(camsVal)}</div>`;
      } else {
          specRow.innerHTML = `${buildSpecSpan(resVal)} <span class="spec-x">x</span> ${buildSpecSpan(camsVal)}`;
      }

      // DVR
      const recTag = tags[2];
      const dvrVal = dvrInput ? dvrInput.value : '';
      const recSpan = recTag.querySelector('.spec-value, .spec-unknown');
      
      const isDvrUnknown = !dvrVal || dvrVal.trim().toLowerCase() === 'dont-know';
      const dvrText = isDvrUnknown ? 'X' : dvrVal.trim();
      const dvrClass = isDvrUnknown ? 'spec-unknown' : `spec-value ${getDvrColorClass(dvrVal)}`;

      if (recSpan) {
        recSpan.textContent = dvrText;
        recSpan.className = dvrClass;
      } else {
        if (recTag.querySelector('.spec-label')) {
             recTag.innerHTML = `<span class="spec-label">DVR</span> <span class="${dvrClass}">${dvrText}</span>`;
        } else {
             recTag.innerHTML = `<span class="spec-label">DVR</span> <span class="${dvrClass}">${dvrText}</span>`;
        }
      }
    }

    

    function setupRealtimeFormBindings(cardElement, rid) {
      const nameInput = cardElement.querySelector(`#leadName-${rid}`);
      const hddInput = cardElement.querySelector(`#leadHdd-${rid}`);
      const resInput = cardElement.querySelector(`#leadCameraResolution-${rid}`);
      const camsInput = cardElement.querySelector(`#leadNumCameras-${rid}`);
      const dvrInput = cardElement.querySelector(`#leadDvrType-${rid}`);
      const assignInput = cardElement.querySelector(`#leadAssign-${rid}`);
      const callStatusInput = cardElement.querySelector(`#callStatus-${rid}`);
      const quoteInput = cardElement.querySelector(`#leadQuote-${rid}`);
      const installInput = cardElement.querySelector(`#installDate-${rid}`);

      if (nameInput) {
        nameInput.addEventListener('keyup', () => updateNameFromForm(cardElement, rid));
        updateNameFromForm(cardElement, rid);
      }
      [hddInput, resInput, camsInput, dvrInput].forEach(inp => {
        if (inp) inp.addEventListener('keyup', () => updateSpecsFromForm(cardElement, rid));
      });
      updateSpecsFromForm(cardElement, rid);

      if (assignInput) {
        assignInput.addEventListener('change', () => updateAssignFromForm(cardElement, rid));
        updateAssignFromForm(cardElement, rid);
      }

      if (callStatusInput && assignInput) {
        callStatusInput.addEventListener('change', () => {
          if (IS_ADMIN) return;
          const nextStatus = String(callStatusInput.value || '').trim();
          if (!nextStatus) return;
          const currentAssign = String(assignInput.value || '').trim();
          const currentAssignNorm = currentAssign.toLowerCase();
          const isOpen = currentAssignNorm === '' || currentAssignNorm === 'open' || currentAssignNorm === '-' || currentAssignNorm === 'na';
          const code = String(CURRENT_USER_CODE || '').trim();
          if (!isOpen || !code) return;
          assignInput.value = code;
          updateAssignFromForm(cardElement, rid);
        });
      }

      if (quoteInput) {
        if (quoteInput.readOnly || quoteInput.disabled) return;
        const saveQuoteDraft = () => writeOrderDraft(rid, { quote: quoteInput.value });
        quoteInput.addEventListener('input', saveQuoteDraft);
        saveQuoteDraft();
      }

      if (installInput) {
        if (installInput.readOnly || installInput.disabled) return;
        const saveInstallDraft = () => writeOrderDraft(rid, { installation_date: installInput.value });
        installInput.addEventListener('change', saveInstallDraft);
        saveInstallDraft();
      }
    }
    // Scroll to Top Button Logic
    const scrollToTopBtn = document.createElement('button');
    scrollToTopBtn.className = 'scroll-to-top-btn';
    scrollToTopBtn.innerHTML = '<i class="fa fa-arrow-up"></i>';
    scrollToTopBtn.title = 'Go to top';
    document.body.appendChild(scrollToTopBtn);

    // Refresh / Go to Latest Button Logic
    const refreshBtn = document.createElement('button');
    refreshBtn.className = 'refresh-btn';
    // Top arrow with a line on the top
    refreshBtn.innerHTML = '<i class="fa fa-arrow-up" style="position: relative;"><span style="position: absolute; top: -3px; left: 50%; transform: translateX(-50%); width: 10px; height: 2px; background-color: currentColor;"></span></i>';
    refreshBtn.title = 'Go to Latest';
    refreshBtn.style.cssText = `
        position: fixed;
        bottom: 80px;
        right: 20px;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background-color: #4CAF50;
        color: white;
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        display: none;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        z-index: 1000;
        transition: transform 0.2s, background-color 0.2s;
    `;
    // Add hover effect
    refreshBtn.onmouseover = () => refreshBtn.style.backgroundColor = '#45a049';
    refreshBtn.onmouseout = () => refreshBtn.style.backgroundColor = '#4CAF50';
    
    document.body.appendChild(refreshBtn);

    const newLeadsFab = document.getElementById('newLeadsFab');

    const quickLeadModal = document.getElementById('quickLeadModal');
    const quickLeadForm = document.getElementById('quickLeadForm');
    const quickLeadClose = document.getElementById('quickLeadClose');
    const quickLeadCancel = document.getElementById('quickLeadCancel');
    const quickLeadSubmit = document.getElementById('quickLeadSubmit');
    const qlmCams = document.getElementById('qlmCams');
    const qlmWa = document.getElementById('qlmWa');
    const qlmDupOverlay = document.getElementById('qlmDupOverlay');
    const qlmDupList = document.getElementById('qlmDupList');
    const qlmDupProceed = document.getElementById('qlmDupProceed');
    const qlmDupCancel = document.getElementById('qlmDupCancel');
    let pendingQuickLeadPayload = null;
    const paymentsModal = document.getElementById('paymentsModal');
    const paymentsClose = document.getElementById('paymentsClose');
    const paymentsIframe = document.getElementById('paymentsIframe');

    const normalizeWaDigits = (raw) => {
      let s = String(raw || '').trim();
      if (s.startsWith('+')) s = s.slice(1);
      let digits = s.replace(/\D/g, '');
      if (digits.startsWith('91') && digits.length > 10) digits = digits.slice(-10);
      if (digits.length > 10) digits = digits.slice(-10);
      return digits;
    };

    const syncQuickLeadSubmit = () => {
      if (!quickLeadSubmit) return;
      const digits = normalizeWaDigits(qlmWa ? qlmWa.value : '');
      quickLeadSubmit.disabled = digits.length < 10 || quickLeadSubmit.classList.contains('is-loading');
    };

    const openQuickLeadModal = () => {
      if (!quickLeadModal) return;
      quickLeadModal.classList.add('is-open');
      quickLeadModal.setAttribute('aria-hidden', 'false');
      if (qlmCams) qlmCams.value = qlmCams.value || '6';
      if (qlmWa) qlmWa.value = '';
      if (qlmDupOverlay) {
        qlmDupOverlay.classList.remove('is-open');
        qlmDupOverlay.setAttribute('aria-hidden', 'true');
      }
      pendingQuickLeadPayload = null;
      syncQuickLeadSubmit();
      setTimeout(() => { if (qlmWa) qlmWa.focus(); }, 0);
    };
    const closeQuickLeadModal = () => {
      if (!quickLeadModal) return;
      quickLeadModal.classList.remove('is-open');
      quickLeadModal.setAttribute('aria-hidden', 'true');
      if (qlmDupOverlay) {
        qlmDupOverlay.classList.remove('is-open');
        qlmDupOverlay.setAttribute('aria-hidden', 'true');
      }
      pendingQuickLeadPayload = null;
      if (quickLeadSubmit) {
        quickLeadSubmit.classList.remove('is-loading');
        quickLeadSubmit.disabled = true;
      }
    };
    window.openQuickLeadModal = openQuickLeadModal;

    if (quickLeadClose) quickLeadClose.addEventListener('click', closeQuickLeadModal);
    if (quickLeadCancel) quickLeadCancel.addEventListener('click', closeQuickLeadModal);
    if (quickLeadModal) {
      quickLeadModal.addEventListener('click', (e) => {
        if (e.target === quickLeadModal) closeQuickLeadModal();
      });
    }
    const openPaymentsModal = () => {
      if (!paymentsModal) return;
      if (paymentsIframe && paymentsIframe.src) {
        try { paymentsIframe.contentWindow && paymentsIframe.contentWindow.postMessage({ type: 'ping' }, '*'); } catch(e) {}
      }
      paymentsModal.classList.add('is-open');
      paymentsModal.setAttribute('aria-hidden', 'false');
    };
    const closePaymentsModal = () => {
      if (!paymentsModal) return;
      paymentsModal.classList.remove('is-open');
      paymentsModal.setAttribute('aria-hidden', 'true');
    };
    if (paymentsClose) paymentsClose.addEventListener('click', closePaymentsModal);
    if (paymentsModal) paymentsModal.addEventListener('click', (e) => { if (e.target === paymentsModal) closePaymentsModal(); });
    
    // Payment Creation Modal Functions
    const paymentCreateModal = document.getElementById('paymentCreateModal');
    const paymentCreateClose = document.getElementById('paymentCreateClose');
    const paymentCreateForm = document.getElementById('paymentCreateForm');
    const paycreateName = document.getElementById('paycreateName');
    const paycreatePhone = document.getElementById('paycreatePhone');
    const paycreateAmount = document.getElementById('paycreateAmount');
    const paycreateDescription = document.getElementById('paycreateDescription');
    const paycreateSubmit = document.getElementById('paycreateSubmit');
    const paycreateStatus = document.getElementById('paycreateStatus');
    
    const openPaymentCreateModal = (name, phone, leadId) => {
      if (!paymentCreateModal) return;
      if (paycreateName) paycreateName.value = name || '';
      if (paycreatePhone) paycreatePhone.value = phone || '';
      if (paycreateAmount) paycreateAmount.value = '500';
      if (paycreateDescription) paycreateDescription.value = 'Smartronic CCTV Installation Booking';
      
      // Store lead ID for later use
      paymentCreateModal.setAttribute('data-lead-id', leadId || '');
      
      paymentCreateModal.classList.add('is-open');
      paymentCreateModal.setAttribute('aria-hidden', 'false');
      
      // Initialize Materialize fields
      if (window.M && M.updateTextFields) M.updateTextFields();
    };
    
    const closePaymentCreateModal = () => {
      if (!paymentCreateModal) return;
      paymentCreateModal.classList.remove('is-open');
      paymentCreateModal.setAttribute('aria-hidden', 'true');
      
      // Clear form status
      if (paycreateStatus) paycreateStatus.textContent = '';
      if (paycreateSubmit) paycreateSubmit.disabled = false;
    };
    
    if (paymentCreateClose) paymentCreateClose.addEventListener('click', closePaymentCreateModal);
    if (paymentCreateModal) paymentCreateModal.addEventListener('click', (e) => { 
      if (e.target === paymentCreateModal) closePaymentCreateModal(); 
    });
    
    // Payment form submission
    if (paymentCreateForm) {
      paymentCreateForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const name = paycreateName ? paycreateName.value.trim() : '';
        const phone = paycreatePhone ? paycreatePhone.value.replace(/\D+/g, '').slice(-10) : '';
        const amount = paycreateAmount ? parseFloat(paycreateAmount.value) : 0;
        const description = paycreateDescription ? paycreateDescription.value.trim() : '';
        const leadId = paymentCreateModal.getAttribute('data-lead-id') || '';
        
        if (!name || phone.length !== 10 || amount <= 0 || !description) {
          if (window.M && M.toast) {
            M.toast({html: 'Please fill all fields correctly'});
          }
          return;
        }
        
        if (paycreateSubmit) paycreateSubmit.disabled = true;
        if (paycreateStatus) paycreateStatus.textContent = 'Creating payment link...';
        
        try {
          const response = await fetch('/payments/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, phone, amount, description })
          });
          
          const data = await response.json();
          
          if (data.success && data.link) {
            // Add payment link to WhatsApp textarea
            if (leadId) {
              const textarea = document.querySelector(`#waDraftText-${leadId}`);
              if (textarea) {
                const currentText = textarea.value.trim();
                const customerName = paycreateName ? paycreateName.value.trim() : '';
                const paymentMessage = `Dear ${customerName},

To confirm your booking and quickly schedule installation, please send ₹500 to 8884831000.

Or please use the RazorPay link below:
${data.link}

This will help us reserve your preferred slot and immediately assign a technician.

Once assigned, you'll receive a notification with the exact installation time for you to have a look at.

The best part — the remaining amount can be paid online only after the installation is completed to your satisfaction.

Looking forward to getting this set up for you soon!`;
                textarea.value = currentText ? `${currentText}\n\n${paymentMessage}` : paymentMessage;
                
                // Trigger Materialize update
                if (window.M && M.textareaAutoResize) M.textareaAutoResize(textarea);
              }
            }
            
            if (window.M && M.toast) {
              M.toast({html: 'Payment link created and added to WhatsApp message!'});
            }
            
            closePaymentCreateModal();
          } else {
            throw new Error(data.error || 'Failed to create payment link');
          }
        } catch (error) {
          if (paycreateStatus) paycreateStatus.textContent = 'Error: ' + error.message;
          if (window.M && M.toast) {
            M.toast({html: 'Error creating payment link: ' + error.message});
          }
        } finally {
          if (paycreateSubmit) paycreateSubmit.disabled = false;
        }
      });
    }
    
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeQuickLeadModal();
        closePaymentsModal();
      }
    });
    const clampCams = () => {
      if (!qlmCams) return;
      const raw = String(qlmCams.value || '').trim();
      const num = parseInt(raw, 10);
      if (!isFinite(num) || isNaN(num)) return;
      const clamped = Math.max(1, Math.min(32, num));
      if (String(clamped) !== raw) qlmCams.value = String(clamped);
    };
    if (qlmCams) qlmCams.addEventListener('input', () => setTimeout(clampCams, 0));
    if (qlmWa) qlmWa.addEventListener('input', () => setTimeout(syncQuickLeadSubmit, 0));
    const syncRadioSelected = (name) => {
      if (!quickLeadForm) return;
      const inputs = Array.from(quickLeadForm.querySelectorAll(`input[name="${CSS.escape(name)}"]`));
      inputs.forEach((inp) => {
        const label = inp.closest('.qlm-radio');
        if (!label) return;
        label.classList.toggle('is-selected', !!inp.checked);
      });
    };
    ['qlmDvr', 'qlmHdd', 'qlmRes'].forEach((n) => {
      if (!quickLeadForm) return;
      quickLeadForm.addEventListener('change', (e) => {
        const t = e.target;
        if (!(t instanceof HTMLInputElement)) return;
        if (t.name === n) syncRadioSelected(n);
      });
      syncRadioSelected(n);
    });

    const highlightLeadById = (leadId) => {
      const targetId = leadId ? String(leadId) : '';
      if (!targetId) return;
      const startedAt = Date.now();
      const tick = () => {
        const card = document.querySelector(`.lead-card[data-lead-id="${CSS.escape(targetId)}"]`);
        if (card) {
          card.classList.add('is-dup-highlight');
          card.scrollIntoView({ behavior: 'smooth', block: 'center' });
          setTimeout(() => { card.classList.remove('is-dup-highlight'); }, 4000);
          return;
        }
        if (Date.now() - startedAt < 6000) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };

    const showDupOverlay = (payload, matches) => {
      if (!qlmDupOverlay || !qlmDupList) return;
      pendingQuickLeadPayload = payload;
      console.log('[QuickLead] Showing duplicate overlay', { payload, matchesCount: Array.isArray(matches) ? matches.length : 0 });
      qlmDupList.innerHTML = '';
      const list = Array.isArray(matches) ? matches : [];
      if (list.length === 0) {
        qlmDupList.innerHTML = '<div class="hint">No matches returned.</div>';
      } else {
        qlmDupList.innerHTML = list.map((m) => {
          const lid = String(m.lead_id || '');
          const mid = String(m.mid || '');
          const wa = String(m.whatsapp_number || '');
          return `
            <div class="qlm-dupe-item" data-lead-id="${escapeAttr(lid)}">
              <div class="qlm-dupe-line1">${mid ? escapeHtml(mid) : 'Lead'}${lid ? ` · #${escapeHtml(lid)}` : ''}</div>
              <div class="qlm-dupe-line2">${wa ? escapeHtml(wa) : ''}</div>
            </div>
          `;
        }).join('');
      }
      qlmDupOverlay.classList.add('is-open');
      qlmDupOverlay.setAttribute('aria-hidden', 'false');

      qlmDupOverlay.querySelectorAll('.qlm-dupe-item').forEach((el) => {
        el.addEventListener('click', () => {
          const leadId = el.getAttribute('data-lead-id') || '';
          const digits = normalizeWaDigits(payload && payload.whatsapp_number ? payload.whatsapp_number : '');
          const searchEl = document.getElementById('searchInput');
          searchQuery = digits;
          if (searchEl) searchEl.value = digits;
          mineQuery = '';
          followQuery = '';
          statusQuery = '';
          col1Query = '';
          col2Query = '';
          resetStateAndReload();
          window.scrollTo({ top: 0, behavior: 'smooth' });
          highlightLeadById(leadId);
        });
      });
    };

    const hideDupOverlay = () => {
      if (!qlmDupOverlay) return;
      qlmDupOverlay.classList.remove('is-open');
      qlmDupOverlay.setAttribute('aria-hidden', 'true');
    };

    if (qlmDupCancel) qlmDupCancel.addEventListener('click', () => { closeQuickLeadModal(); });
    if (qlmDupProceed) {
      qlmDupProceed.addEventListener('click', async () => {
        if (!pendingQuickLeadPayload) return;
        console.log('[QuickLead] Duplicate overlay: proceed clicked', pendingQuickLeadPayload);
        qlmDupProceed.disabled = true;
        try {
          const res = await fetch('lead_quick_add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(Object.assign({}, pendingQuickLeadPayload, { force_create: 1, debug: 1 }))
          });
          const raw = await res.text();
          console.log('[QuickLead] Force-create HTTP', res.status, raw);
          const data = (() => { try { return JSON.parse(raw); } catch (e) { return null; } })();
          if (!data) throw new Error('Invalid response');
          console.log('[QuickLead] Force-create parsed', data);
          if (data.success) {
            hideDupOverlay();
            closeQuickLeadModal();
            if (typeof showAutoSaveNotification === 'function') {
              const msg = data.mid ? `Lead created: ${data.mid}` : 'Lead created';
              showAutoSaveNotification(msg, data.id || null, 'success');
            }
            resetStateAndReload();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
          }
          if (typeof showAutoSaveNotification === 'function') showAutoSaveNotification(data.message || 'Failed to create lead', null, 'error');
        } catch (e) {
          console.error('[QuickLead] Force-create error', e);
          if (typeof showAutoSaveNotification === 'function') showAutoSaveNotification('Error creating lead', null, 'error');
        } finally {
          qlmDupProceed.disabled = false;
        }
      });
    }

    if (quickLeadForm) {
      quickLeadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const camsVal = qlmCams ? String(qlmCams.value || '').trim() : '';
        const dvrVal = (quickLeadForm.querySelector('input[name="qlmDvr"]:checked') || {}).value || '';
        const hddVal = (quickLeadForm.querySelector('input[name="qlmHdd"]:checked') || {}).value || '';
        const resVal = (quickLeadForm.querySelector('input[name="qlmRes"]:checked') || {}).value || '';
        const waDigits = normalizeWaDigits(qlmWa ? qlmWa.value : '');
        console.log('[QuickLead] Submit clicked', { camsVal, dvrVal, hddVal, resVal, waDigits });
        if (!waDigits || waDigits.length < 10) {
          if (typeof showAutoSaveNotification === 'function') showAutoSaveNotification('Enter a valid WhatsApp number', null, 'error');
          syncQuickLeadSubmit();
          return;
        }
        const camsNum = parseInt(camsVal, 10);
        if (!isFinite(camsNum) || isNaN(camsNum) || camsNum < 1) {
          if (typeof showAutoSaveNotification === 'function') showAutoSaveNotification('Enter cameras (1 to 32)', null, 'error');
          syncQuickLeadSubmit();
          return;
        }
        if (!dvrVal || !hddVal || !resVal) {
          if (typeof showAutoSaveNotification === 'function') showAutoSaveNotification('Fill all fields', null, 'error');
          syncQuickLeadSubmit();
          return;
        }
        quickLeadSubmit.classList.add('is-loading');
        quickLeadSubmit.disabled = true;
        try {
          const payload = {
            num_cameras: String(Math.max(1, Math.min(32, camsNum))),
            dvr_type: dvrVal,
            hdd_size: hddVal,
            camera_resolution: resVal,
            whatsapp_number: waDigits,
            debug: 1
          };
          console.log('[QuickLead] POST lead_quick_add payload', payload);
          const res = await fetch('lead_quick_add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });
          const raw = await res.text();
          console.log('[QuickLead] Create HTTP', res.status, raw);
          const data = (() => { try { return JSON.parse(raw); } catch (e) { return null; } })();
          if (!data) throw new Error('Invalid response');
          console.log('[QuickLead] Create parsed', data);

          if (data.success) {
            closeQuickLeadModal();
            if (typeof showAutoSaveNotification === 'function') {
              const msg = data.mid ? `Lead created: ${data.mid}` : 'Lead created';
              showAutoSaveNotification(msg, data.id || null, 'success');
            }
            resetStateAndReload();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
          }

          if (data.duplicate) {
            console.log('[QuickLead] Duplicate detected', data.matches_count, data.matches);
            if (typeof showAutoSaveNotification === 'function') {
              const countText = data.matches_count ? ` (${data.matches_count})` : '';
              const msg = data.mid ? `WhatsApp already exists${countText}: ${data.mid}` : `WhatsApp already exists${countText}`;
              showAutoSaveNotification(msg, data.lead_id || null, 'error');
            }
            showDupOverlay(payload, data.matches || []);
            return;
          }

          if (typeof showAutoSaveNotification === 'function') showAutoSaveNotification(data.message || 'Failed to create lead', null, 'error');
        } catch (err) {
          console.error('[QuickLead] Create error', err);
          if (typeof showAutoSaveNotification === 'function') showAutoSaveNotification('Error creating lead', null, 'error');
        } finally {
          if (quickLeadSubmit) quickLeadSubmit.classList.remove('is-loading');
          syncQuickLeadSubmit();
        }
      });
    }

    function mountLeadFloatingMenu() {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('floating_menu') === '0') return;
      if (!window.SmartFloatingMenu || typeof window.SmartFloatingMenu.mount !== 'function') return;
      const allowedPages = <?php echo json_encode($allowedSmartPages); ?>;
      const links = [
        {
          label: 'Favourites',
          icon: favoritesViewActive ? 'fa-solid fa-xmark' : 'fa-regular fa-heart',
          color: '#e11d48',
          action: () => {
            if (favoritesViewActive) {
              exitMidsView();
            } else {
              historyViewActive = false;
              loadMidsView('favorites', readStoredList(FAVORITES_KEY));
            }
            mountLeadFloatingMenu();
          }
        },
        {
          label: 'History',
          icon: historyViewActive ? 'fa-solid fa-xmark' : 'fa-solid fa-clock-rotate-left',
          color: '#2563eb',
          action: () => {
            if (historyViewActive) {
              exitMidsView();
            } else {
              favoritesViewActive = false;
              loadMidsView('history', readStoredList(HISTORY_KEY));
            }
            mountLeadFloatingMenu();
          }
        }
      ];
      if (allowedPages.includes('install')) {
        links.push({
          label: 'Installs',
          icon: 'fa-solid fa-screwdriver-wrench',
          color: '#2563eb',
          url: `${window.location.origin}/admin_v2/smart/installs.php`
        });
      }
      if (allowedPages.includes('quote')) {
        links.push({
          label: 'Quote Tool',
          icon: 'fas fa-calculator',
          color: '#6f42c1',
          url: `${window.location.origin}/admin_v2/smart/quote.php`
        });
      }
      if (allowedPages.includes('gads_stats')) {
        links.push({
          label: 'Google Ads Command Center',
          icon: 'fas fa-chart-line',
          color: '#dc2626',
          url: `${window.location.origin}/admin_v2/smart/gads_conversion.php`
        });
      }
      links.push(
        {
          label: 'Payments',
          icon: 'fa-solid fa-indian-rupee-sign',
          color: '#059669',
          action: () => {
            openPaymentsModal();
          }
        },
        {
          label: 'New Lead',
          icon: 'fa-solid fa-plus',
          color: '#16a34a',
          action: () => {
            if (typeof window.openQuickLeadModal === 'function') window.openQuickLeadModal();
          }
        }
      );
      window.SmartFloatingMenu.mount({
        links,
        baseBottom: 140,
        step: 60
      });
    }

    mountLeadFloatingMenu();

    window.addEventListener('scroll', () => {
      if (window.scrollY > 300) {
        scrollToTopBtn.style.display = 'flex';
        refreshBtn.style.display = 'flex';
      } else {
        scrollToTopBtn.style.display = 'none';
        refreshBtn.style.display = 'none';
      }
    });

    scrollToTopBtn.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
    
    refreshBtn.addEventListener('click', () => {
      resetStateAndReload();
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });

    // ==========================================
    // Real-time Auto Update Feature
    // ==========================================
    let newLeadsCount = 0;

    if (newLeadsFab) {
      newLeadsFab.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        newLeadsFab.classList.remove('visible');
        newLeadsCount = 0;
        newLeadsFab.innerHTML = 'New <big>0</big> Lead';
      });
    }

    let lastSeenLeadId = 0;
    
    // Function to find the highest lead ID currently in the list
    function updateLastSeenLeadId() {
        const cards = document.querySelectorAll('.lead-card');
        cards.forEach(card => {
            const leadId = parseInt(card.getAttribute('data-lead-id') || 0);
            if (leadId > lastSeenLeadId) {
                lastSeenLeadId = leadId;
            }
        });
        console.log('Current highest Lead ID:', lastSeenLeadId);
    }

    // Call this initially to set baseline
    // We'll call it again after initial load is complete
    
    function playNotificationSound() {
        const audio = new Audio('/content/uploads/2025/01/mixkit-happy-bells-notification-937.wav');
        audio.play().catch(e => console.error('Audio playback failed', e));
    }

    const LEAD_NOTIFICATION_KEY = 'smartronic_notified_lead_ids_v1';
    function readNotifiedLeadIds() {
        try {
            return new Set(JSON.parse(localStorage.getItem(LEAD_NOTIFICATION_KEY) || '[]').map(String));
        } catch (e) {
            return new Set();
        }
    }

    function writeNotifiedLeadIds(ids) {
        try {
            localStorage.setItem(LEAD_NOTIFICATION_KEY, JSON.stringify(Array.from(ids).slice(-300)));
        } catch (e) {}
    }

    function markLeadIdsNotified(ids) {
        const notified = readNotifiedLeadIds();
        ids.map(String).filter(Boolean).forEach(id => notified.add(id));
        writeNotifiedLeadIds(notified);
    }

    function getUnnotifiedLeadIds(rows) {
        const notified = readNotifiedLeadIds();
        return (Array.isArray(rows) ? rows : [])
            .map(row => String(row && row.id || '').trim())
            .filter(id => id && !notified.has(id));
    }

    let lastPresenceActivityAt = Date.now();

    function touchPresenceActivity() {
        const now = Date.now();
        if (now - lastPresenceActivityAt < 5000) return;
        lastPresenceActivityAt = now;
    }

    ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach((evt) => {
        window.addEventListener(evt, touchPresenceActivity, { passive: true });
    });

    function renderActiveUsersBar(users) {
        const bar = document.getElementById('activeUsersBar');
        if (!bar) return;
        if (!Array.isArray(users)) return;
        bar.innerHTML = '';
        const nameMap = {
            amr: 'Amreen',
            var: 'Varsha',
            sur: 'Surya'
        };
        const normalizePresenceCode = (user) => {
            const codeRaw = user && user.code ? String(user.code) : '';
            const nameRaw = user && user.name ? String(user.name) : '';
            const candidates = [codeRaw, nameRaw];
            for (const candidate of candidates) {
                const value = candidate.toLowerCase().replace(/[^a-z]/g, '');
                if (!value) continue;
                if (value.startsWith('amreen') || value.startsWith('amr')) return 'amr';
                if (value.startsWith('varsha') || value.startsWith('var')) return 'var';
                if (value.startsWith('zoya') || value.startsWith('zoy')) return 'zoy';
                if (value.startsWith('surya') || value.startsWith('sur')) return 'sur';
                return value.slice(0, 3);
            }
            return '';
        };
        const byCode = new Map();
        users.forEach((u) => {
            const key = normalizePresenceCode(u);
            if (!key || byCode.has(key)) return;
            byCode.set(key, u);
        });

        Object.keys(nameMap).forEach((key) => {
            const u = byCode.get(key) || { active: false };

            const chip = document.createElement('span');
            chip.className = 'active-user-chip';

            const dot = document.createElement('span');
            dot.className = 'presence-dot' + (u && u.active ? ' active' : '');

            const label = document.createElement('span');
            label.textContent = nameMap[key];

            chip.appendChild(dot);
            chip.appendChild(label);
            bar.appendChild(chip);
        });
    }

    function pollPresence() {
        fetch(`lead_check_updates.php?presence_last_active=${encodeURIComponent(Math.floor(lastPresenceActivityAt / 1000))}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.success && Array.isArray(data.users)) {
                    renderActiveUsersBar(data.users);
                }
            })
            .catch(() => {});
    }

    pollPresence();
    setInterval(pollPresence, 20000);

    function checkForNewLeads() {
        if (favoritesViewActive || historyViewActive) return;
        if (lastSeenLeadId === 0) {
            updateLastSeenLeadId();
            if (lastSeenLeadId === 0) return; // Still 0, maybe list empty or loading
        }

        console.log('Checking for new leads since ID:', lastSeenLeadId);

        fetch(`lead_check_updates.php?last_id=${lastSeenLeadId}&mine=${encodeURIComponent(mineQuery)}&follow=${encodeURIComponent(followQuery)}&presence_last_active=${encodeURIComponent(Math.floor(lastPresenceActivityAt / 1000))}`)
            .then(res => res.json())
            .then(data => {
                if (data && data.success && Array.isArray(data.users)) {
                    renderActiveUsersBar(data.users);
                }
                if (data.success && data.new_leads && data.new_leads.length > 0) {
                    const newLeads = data.new_leads;
                    console.log(`Found ${newLeads.length} new leads!`);
                    const visibleNewLeads = newLeads.filter(row => {
                        const id = String(row && row.id || '').trim();
                        return id && !document.querySelector(`.lead-card[data-lead-id="${id}"]`);
                    });
                    const unnotifiedIds = getUnnotifiedLeadIds(visibleNewLeads);
                    
                    // Update FAB
                    newLeadsCount += visibleNewLeads.length;
                    if (newLeadsFab && visibleNewLeads.length > 0) {
                        newLeadsFab.classList.add('visible');
                        newLeadsFab.innerHTML = `New <big>${newLeadsCount}</big> Lead${newLeadsCount === 1 ? '' : 's'}`;
                    }

                    // Play Notification Sound only once per truly new lead id.
                    if (unnotifiedIds.length > 0) {
                        playNotificationSound();
                        markLeadIdsNotified(unnotifiedIds);
                    }

                    // showAutoSaveNotification(`Found ${newLeads.length} new lead(s)! Adding to list...`, null, 'success');
                    
                    // Process new leads
                    // We need to add them in reverse order (newest first) to the top, 
                    // but the API returns them ASC (oldest to newest).
                    // So we iterate normally and prepend each one, which effectively reverses them if we use prepend?
                    // Wait, prepend adds as first child. 
                    // If we have [101, 102, 103] (ASC)
                    // prepend(101) -> [101, ...]
                    // prepend(102) -> [102, 101, ...]
                    // prepend(103) -> [103, 102, 101, ...]
                    // Yes, this results in DESC order at the top. Correct.

                    visibleNewLeads.forEach(row => {
                        const newCard = createRow(row);
                        
                        // Add highlight effect
                        const cardInner = newCard.querySelector('.lead-card');
                        if (cardInner) {
                            // Mark as new lead for offset handling
                            cardInner.setAttribute('data-is-new', 'true');
                            
                            cardInner.classList.add('new-lead-highlight');
                            cardInner.classList.add('new-lead-highlight');
                            // Remove highlight after some time
                            setTimeout(() => {
                                cardInner.classList.remove('new-lead-highlight');
                            }, 10000);
                        }

                        // Prepend to container
                        if (container && topSentinel) {
                            // Insert after topSentinel
                            container.insertBefore(newCard, topSentinel.nextSibling);
                        } else if (container) {
                            container.prepend(newCard);
                        }

                        // Update lastSeenLeadId
                        const newId = parseInt(row.id);
                        if (newId > lastSeenLeadId) {
                            lastSeenLeadId = newId;
                        }
                    });
                    newLeads.forEach(row => {
                        const newId = parseInt(row && row.id);
                        if (newId > lastSeenLeadId) lastSeenLeadId = newId;
                    });

                    rebuildDaySeparators();
                    
                    // Update offsetStart to account for added items?
                    // Actually, if we add items to top, our existing scroll logic might get confused 
                    // if it relies on strict index counts, but here we use sentinels.
                    // However, we should increment offsetEnd so we don't reload these if we scroll down and up?
                    // The fetchRows logic uses offset based on SQL LIMIT. 
                    // If we inject rows, the existing DOM has more items.
                    // This is fine for infinite scroll as long as we don't duplicate.
                    
                } else {
                    console.log('No new leads found.');
                }
            })
            .catch(err => console.error('Error checking for updates:', err));
    }

    // Start polling every 60 seconds
    setInterval(checkForNewLeads, 60000);

    // Initial update of ID after a short delay to ensure list is populated
    setTimeout(updateLastSeenLeadId, 2000);

    </script>
</body>
</html>
