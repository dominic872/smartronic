<?php
require_once __DIR__ . '/auth.php';
$greetingName = !empty($_COOKIE['auth_name']) ? $_COOKIE['auth_name'] : ($_COOKIE['auth_user'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Leave Calendar | Smartronic</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4A90E2;
            --primary-hover: #357ABD;
            --bg-color: #f9fbfc;
            --surface: #ffffff;
            --text-main: #2C3E50;
            --text-muted: #7F8C8D;
            --border-color: #E0E6ED;
            
            --leave-planned: #f59e0b;
            --leave-unplanned: #E74C3C;
            --leave-weekly-off: #fb923c;
            --leave-extra: #fdba74;
            --leave-worked: #f97316;
        }

        * {
            box-sizing: border-box; margin: 0; padding: 0;
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body { background-color: var(--bg-color); color: var(--text-main); padding-bottom: 80px; }

        header {
            background: var(--surface); padding: 15px 20px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 100;
        }

        .header-left { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .logo { height: 30px; object-fit: contain; }
        .greeting { font-size: 1rem; font-weight: 600; color: var(--text-main); }
        
        #userSelector {
            display: none; padding: 6px 12px; border-radius: 8px;
            border: 1px solid var(--border-color); font-size: 0.9rem; outline: none;
        }
        
        .btn-logs {
            display: none; background: var(--text-main); color: white;
            padding: 6px 12px; border-radius: 8px; border: none; font-size: 0.8rem; cursor: pointer;
        }

        .container { max-width: 600px; margin: 20px auto; padding: 0 15px; }

        .leave-summary {
            display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-bottom: 20px;
        }

        .summary-box {
            text-align: center; min-width: 0;
            background: var(--surface); padding: 16px 10px; border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        .summary-box .val { font-size: 1.5rem; font-weight: 700; color: var(--primary); }
        .summary-box .label { font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; }

        .calendar-header {
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;
        }
        .calendar-header button {
            background: var(--surface); border: 1px solid var(--border-color); color: var(--text-main);
            width: 40px; height: 40px; border-radius: 50%; cursor: pointer;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02); transition: all 0.2s;
        }
        .calendar-header button:active { transform: scale(0.95); }
        .month-year { font-size: 1.2rem; font-weight: 600; }

        .calendar {
            background: var(--surface); border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); padding: 20px; margin-bottom: 30px;
        }

        .weekdays { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; margin-bottom: 10px; }
        .weekdays div { text-align: center; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); }
        .days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }

        .day {
            aspect-ratio: 1; display: flex; flex-direction: column; align-items: flex-start; justify-content: flex-start;
            border-radius: 4px; font-size: 1rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s; position: relative; background: #f8fafc;
            padding: 6px; line-height: 1; overflow: visible;
            user-select: none; -webkit-user-select: none; -webkit-touch-callout: none;
        }

        .day.empty { background: transparent; cursor: default; }
        .day.sunday { color: #e74c3c; background: #fdf2f0; }
        .day.market-sunday { background: #ffe4e6; color: #be123c; box-shadow: inset 0 0 0 1px #fda4af; }
        .day:active:not(.empty) { transform: scale(0.92); }

        .day[data-type="planned"] {
            background: linear-gradient(180deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            box-shadow: 0 4px 10px rgba(245, 158, 11, 0.30);
        }
        .day[data-type="unplanned"] { background: var(--leave-unplanned); color: white; box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3); }
        .day[data-type="sick"], .day[data-type="extra"] {
            background:
                repeating-linear-gradient(135deg, rgba(255,255,255,0.18) 0 8px, rgba(255,255,255,0.04) 8px 16px),
                linear-gradient(180deg, #fdba74 0%, #fb923c 100%);
            color: #7c2d12;
            box-shadow: 0 4px 10px rgba(251, 146, 60, 0.28);
        }
        .day[data-type="weekly_off"] {
            background:
                repeating-linear-gradient(45deg, rgba(255,255,255,0.16) 0 7px, rgba(255,255,255,0.04) 7px 14px),
                linear-gradient(180deg, #fdba74 0%, #f97316 100%);
            color: white;
            box-shadow: 0 4px 10px rgba(249, 115, 22, 0.26);
        }
        .day[data-type="worked_holiday"] {
            background:
                radial-gradient(circle at top right, rgba(255,255,255,0.26), transparent 42%),
                linear-gradient(180deg, #fb923c 0%, #ea580c 100%);
            color: white;
            box-shadow: 0 4px 10px rgba(234, 88, 12, 0.34);
        }

        .day::after {
            content: attr(data-reason); position: absolute; left: 6px; right: 6px; bottom: 5px;
            font-size: 0.58rem; font-weight: 500; line-height: 1.15; white-space: normal; word-break: break-word; text-align: left; opacity: 0.95;
            max-height: 2.5em; overflow: hidden;
        }
        .day[data-type="worked_holiday"]::after { content: "Working"; }

        .absent-badge {
            position: absolute; top: -6px; right: -6px; background: #0f172a; color: white;
            font-size: 0.62rem; font-weight: 800; width: 20px; height: 20px; line-height: 20px;
            text-align: center; border-radius: 999px; box-shadow: 0 2px 6px rgba(0,0,0,0.24); z-index: 6;
            border: 2px solid #fff;
        }
        
        .custom-tooltip {
            visibility: hidden; opacity: 0; position: absolute; bottom: 120%; left: 50%;
            transform: translateX(-50%); background: #2C3E50; color: white; padding: 5px 10px;
            border-radius: 6px; font-size: 0.75rem; white-space: nowrap; z-index: 10;
            transition: all 0.2s; pointer-events: none;
        }
        .absent-badge:hover .custom-tooltip, .absent-badge:active .custom-tooltip { visibility: visible; opacity: 1; }

        .loader { text-align: center; padding: 20px; color: var(--text-muted); display: none; }

        /* Modified Modal -> Top Sheet */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);
            z-index: 1000; display: none; align-items: flex-start; justify-content: center;
        }
        .modal-overlay.active { display: flex; animation: fadeIn 0.3s; }

        .top-sheet {
            background: var(--surface); width: 100%; max-width: 600px;
            border-radius: 0 0 4px 4px; padding: 24px;
            transform: translateY(-100%); animation: slideDown 0.3s forwards;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }

        /* Logs Bottom Modal */
        .log-sheet {
            max-height: 80vh; display: flex; flex-direction: column;
            border-radius: 4px 4px 0 0; transform: translateY(100%);
            margin-top: auto;
        }
        .modal-overlay.bottom-align { align-items: flex-end; }
        .log-list { overflow-y: auto; margin-top: 15px; padding-right: 5px; }
        .log-item { padding: 12px; border-bottom: 1px solid var(--border-color); font-size: 0.85rem; }
        .log-item:last-child { border-bottom: none; }
        .log-time { color: var(--text-muted); font-size: 0.75rem; margin-bottom: 4px; }
        .log-desc strong { color: var(--text-main); }

        @keyframes slideDown { to { transform: translateY(0); } }
        @keyframes slideUp { to { transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .bs-header { margin-bottom: 20px; }
        .bs-header h3 { font-size: 1.3rem; }
        .bs-header p { font-size: 0.9rem; color: var(--text-muted); margin-top: 4px; }

        .action-buttons { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .opt-btn {
            flex: 1 1 calc(50% - 5px); min-width: 0;
            padding: 12px 14px; border: 2px solid var(--border-color); border-radius: 4px;
            background: var(--surface); color: var(--text-main); font-weight: 700; font-size: 0.95rem;
            cursor: pointer; transition: all 0.2s; text-align: center;
        }
        .opt-main {
            display: block;
            font-weight: 700;
        }
        .opt-sub {
            display: block;
            margin-top: 4px;
            font-size: 0.74rem;
            font-weight: 500;
            opacity: 0.78;
        }
        .opt-btn.hidden { display: none; }
        .opt-btn.selected {
            border-color: #111827;
            background: #111827;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.14);
            transform: translateY(-1px);
        }
        .opt-btn.selected .opt-sub {
            color: rgba(255, 255, 255, 0.82);
            opacity: 1;
        }
        .opt-btn.planned { border-color: #fcd34d; background: #fff7ed; color: #c2410c; }
        .opt-btn.unplanned { border-color: #fca5a5; background: #fef2f2; color: #dc2626; }
        .opt-btn.weekly_off { border-color: #f9a8d4; background: #fff1f2; color: #be185d; }
        .opt-btn.extra, .opt-btn.sick { border-color: #d8b4fe; background: #faf5ff; color: #7e22ce; }
        .opt-btn.worked_holiday { border-color: #fdba74; background: #fff7ed; color: #c2410c; }

        .option-note {
            margin: -8px 0 14px;
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        .input-group { margin-bottom: 20px; }
        .input-group input { width: 100%; padding: 14px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 1rem; outline: none; }

        .btn-submit { width: 100%; padding: 16px; background: var(--primary); color: white; border: none; border-radius: 4px; font-size: 1.1rem; font-weight: 600; cursor: pointer; }
        .btn-cancel { width: 100%; padding: 12px; background: transparent; color: var(--text-muted); border: none; font-size: 1rem; margin-top: 10px; cursor: pointer; }

        .rules-wrapper { background: var(--surface); padding: 20px; border-radius: 4px; margin-bottom: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .rules-header { display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 15px; }
        .lang-icons button { background: #f1f5f9; border: none; border-radius: 8px; padding: 5px 10px; cursor: pointer; }
        .lang-icons button.active { background: var(--primary); color: white; }
        .rules-list { list-style: none; font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; }
        .rules-list li { margin-bottom: 8px; padding-left: 20px; position: relative; }
        .rules-list li::before { content: '•'; position: absolute; left: 0; color: var(--primary); font-weight: bold; }

        @media (max-width: 520px) {
            .leave-summary {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .opt-btn {
                flex-basis: calc(50% - 5px);
                font-size: 0.88rem;
                padding: 11px 10px;
            }
        }
    </style>
</head>
<body>

    <header>
        <div class="header-left">
            <img src="icon.png" alt="Smartronic" class="logo" onerror="this.src='https://via.placeholder.com/150x50?text=Smartronic'">
            <span class="greeting">Hello, <?php echo htmlspecialchars($greetingName); ?></span>
            <select id="userSelector"></select>
            <button id="btnLogs" class="btn-logs">View Logs</button>
        </div>
    </header>

    <div class="container">
        <div class="leave-summary">
            <div class="summary-box">
                <div class="val" id="sumWOff">...</div>
                <div class="label">Weekly Off Available</div>
            </div>
            <div class="summary-box">
                <div class="val" id="sumExtra">...</div>
                <div class="label">Monthly Leaves Available</div>
            </div>
            <div class="summary-box">
                <div class="val" id="sumLop">...</div>
                <div class="label">LOP Leaves</div>
            </div>
        </div>

        <div class="calendar-header">
            <button id="prevBtn">&larr;</button>
            <div class="month-year" id="monthYear"></div>
            <button id="nextBtn">&rarr;</button>
        </div>

        <div class="calendar">
            <div class="loader" id="loader">Syncing...</div>
            <div class="weekdays">
                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div>
                <div>Thu</div><div>Fri</div><div>Sat</div>
            </div>
            <div class="days" id="daysGrid"></div>
        </div>

        <div class="rules-wrapper">
            <div class="rules-header">
                <h3 id="t-rules-title">Leave Rules</h3>
                <div class="lang-icons">
                    <button class="active" onclick="setLang('en')">EN</button>
                    <button onclick="setLang('ta')">TA</button>
                    <button onclick="setLang('kn')">KN</button>
                    <button onclick="setLang('hi')">HI</button>
                </div>
            </div>
            <ul class="rules-list" id="rulesList">
                <li id="t-rule-1">Leave within the next 2 working days becomes LOP and is shown in red.</li>
                <li id="t-rule-2">Weekly Off is available only from worked Sundays and must be used within 14 days.</li>
                <li id="t-rule-3">1 leave is credited to every user on the first day of each month, starting from May 01, 2026, and the balance resets at the end of December. <br />Unused leave carries forward to the following month(s) within the same year.</li>
                <li id="t-rule-4">Market users have Sunday as holiday. For others, only admin can mark Sunday as a working day.</li>
            </ul>
        </div>

        <div class="rules-wrapper">
            <div class="rules-header">
                <h3 id="t-company-rules-title">Company Rules</h3>
            </div>
            <ul class="rules-list">
                <li id="t-company-rule-1">Salary advance can be requested only after the 15th of the month and is limited to a maximum of 50% of the monthly salary.</li>
                <li id="t-company-rule-2">Any request above the approved salary advance limit will not be entertained.</li>
                <li id="t-company-rule-3">Threatening, disturbing, or repeatedly troubling any employee, especially female staff, is strictly prohibited.</li>
                <li id="t-company-rule-4">Proposing, calling multiple times without reason, or using personal expressions such as “love me” and similar words in the office is not allowed.</li>
                <li id="t-company-rule-5">This is a professional workplace, not a personal space. All employees are expected to maintain discipline, respect boundaries, and follow these rules very seriously.</li>
            </ul>
        </div>
    </div>

    <!-- Toggle Leave Modal (Top Sheet) -->
    <div class="modal-overlay" id="leaveModal">
        <div class="top-sheet">
            <div class="bs-header">
                <h3 id="modalTitle">Apply for Leave</h3>
                <p id="modalDateDisplay"></p>
            </div>
            <div class="option-note" id="optionNote"></div>
            <div class="action-buttons" id="actionButtons">
                <button class="opt-btn selected" data-val="planned">Leave</button>
                <!-- Weekly off buttons injected dynamically -->
            </div>
            <div class="input-group">
                <input type="text" id="leaveReason" placeholder="Reason (Optional)">
            </div>
            <button class="btn-submit" id="submitLeaveBtn">Confirm Leave</button>
            <button class="btn-cancel" onclick="closeModal('leaveModal')">Cancel</button>
        </div>
    </div>

    <!-- Logs Modal (Bottom align variant) -->
    <div class="modal-overlay bottom-align" id="logsModal">
        <div class="top-sheet log-sheet" style="border-radius: 4px 4px 0 0; transform: translateY(100%); animation: slideUp 0.3s forwards;">
            <div class="bs-header">
                <h3>Activity Logs</h3>
                <p>Recent calendar modifications</p>
            </div>
            <div class="log-list" id="logsContainer">
                <div class="loader" id="logsLoader" style="display:block;">Loading...</div>
            </div>
            <button class="btn-cancel" onclick="closeModal('logsModal')">Close</button>
        </div>
    </div>

<script>
    

        const i18n = {
            en: {
                title: "Leave Rules",
                companyTitle: "Company Rules",
                r1: "Only available Weekly Off or accumulated Leave can be used.",
                r2: "1 leave is credited to every user on the first day of each month, starting from May 01, 2026, and the balance resets at the end of December.",
                r3: "This leave can be utilized for any public holiday in the respective month or can be carried forward to the following month(s).",
                r4: "If no Leave balance is available, the next leave becomes LOP and is shown in red.",
                r5: "LOP means Loss of Pay, and no salary will be calculated for that day.",
                r6: "Market users have Sunday as holiday. For others, only admin can mark Sunday as a working day.",
                c1: "Salary advance can be requested only after the 15th of the month and is limited to a maximum of 50% of the monthly salary.",
                c2: "Any request above the approved salary advance limit will not be entertained.",
                c3: "Threatening, disturbing, or repeatedly troubling any employee, especially female staff, is strictly prohibited.",
                c4: "Proposing, calling multiple times without reason, or using personal expressions such as “love me” and similar words in the office is not allowed.",
                c5: "This is a professional workplace, not a personal space. All employees are expected to maintain discipline, respect boundaries, and follow these rules very seriously."
            },

            ta: {
                title: "விடுப்பு விதிகள்",
                companyTitle: "நிறுவன விதிகள்",
                r1: "கிடைக்கும் Weekly Off அல்லது சேர்த்து வைத்துள்ள Leave மட்டுமே பயன்படுத்தலாம்.",
                r2: "01 மே 2026 முதல் ஒவ்வொரு மாதமும் 1 கூடுதல் விடுப்பு சேர்க்கப்படும், மேலும் அது டிசம்பர் மாத இறுதியில் ரீசெட் செய்யப்படும்.",
                r3: "இந்த விடுப்பை அந்த மாதத்தின் எந்த பொதுவிடுமுறைக்கும் பயன்படுத்தலாம் அல்லது அடுத்த மாதம்(ங்களுக்கு) மாற்றிக்கொள்ளலாம்.",
                r4: "Leave balance இல்லையெனில் அடுத்த leave LOP ஆக சிவப்பில் காட்டப்படும்.",
                r5: "LOP என்பது Loss of Pay என்பதைக் குறிக்கும்; அந்த நாளுக்கான சம்பளம் கணக்கிடப்படாது.",
                r6: "Marketing பயனர்களுக்கு ஞாயிறு விடுமுறை. மற்றவர்களுக்கு admin மட்டும் ஞாயிற்றுக்கிழமையை வேலை நாளாக குறிக்கலாம்.",
                c1: "சம்பள முன்பணம் ஒவ்வொரு மாதமும் 15ம் தேதிக்குப் பிறகே கோரலாம். அது மாத சம்பளத்தின் அதிகபட்சம் 50% வரை மட்டுமே அனுமதிக்கப்படும்.",
                c2: "அனுமதிக்கப்பட்ட சம்பள முன்பண வரம்பை மீறும் கோரிக்கைகள் ஏற்கப்படமாட்டாது.",
                c3: "ஏதேனும் ஊழியரை, குறிப்பாக பெண் ஊழியர்களை, மிரட்டுவது, தொந்தரவு செய்வது அல்லது மீண்டும் மீண்டும் குறுக்கிடுவது முற்றிலும் தடைசெய்யப்பட்டுள்ளது.",
                c4: "அலுவலகத்தில் காரணமின்றி பலமுறை அழைப்பது, காதல் முன்வைப்பது, அல்லது “love me” போன்ற தனிப்பட்ட சொற்களை பயன்படுத்துவது அனுமதிக்கப்படாது.",
                c5: "இது தனிப்பட்ட இடம் அல்ல; தொழில்முறை பணியிடம். ஒவ்வொருவரும் ஒழுக்கம், மரியாதை, மற்றும் பணியிட வரம்புகளை மிகவும் தீவிரமாகப் பின்பற்ற வேண்டும்."
            },

            kn: {
                title: "ರಜೆ ನಿಯಮಗಳು",
                companyTitle: "ಕಂಪನಿ ನಿಯಮಗಳು",
                r1: "ಲಭ್ಯವಿರುವ Weekly Off ಅಥವಾ ಸಂಗ್ರಹಿತ Leave ಮಾತ್ರ ಬಳಸಬಹುದು.",
                r2: "ಮೇ 01, 2026ರಿಂದ ಪ್ರತಿ ತಿಂಗಳು 1 ಹೆಚ್ಚುವರಿ ರಜೆ ಸೇರಿಸಲಾಗುತ್ತದೆ ಮತ್ತು ಇದು ಡಿಸೆಂಬರ್ ಅಂತ್ಯದಲ್ಲಿ ಮರುಹೊಂದಿಸಲಾಗುತ್ತದೆ.",
                r3: "ಈ ರಜೆಯನ್ನು ಸಂಬಂಧಿತ ತಿಂಗಳ ಯಾವುದೇ ಸಾರ್ವಜನಿಕ ರಜೆಗೆ ಬಳಸಬಹುದು ಅಥವಾ ಮುಂದಿನ ತಿಂಗಳು(ಗಳಿಗೆ) ವರ್ಗಾಯಿಸಬಹುದು.",
                r4: "Leave balance ಇಲ್ಲದಿದ್ದರೆ ಮುಂದಿನ leave LOP ಆಗಿ ಕೆಂಪಿನಲ್ಲಿ ತೋರುತ್ತದೆ.",
                r5: "LOP ಎಂದರೆ Loss of Pay, ಮತ್ತು ಆ ದಿನಕ್ಕೆ ಸಂಬಳ ಲೆಕ್ಕಿಸಲಾಗುವುದಿಲ್ಲ.",
                r6: "Marketing ಬಳಕೆದಾರರಿಗೆ ಭಾನುವಾರ ರಜೆ. ಇತರರಿಗೆ admin ಮಾತ್ರ ಭಾನುವಾರವನ್ನು ಕೆಲಸದ ದಿನವಾಗಿ ಗುರುತಿಸಬಹುದು.",
                c1: "ಸಂಬಳ ಮುಂಗಡವನ್ನು ಪ್ರತಿ ತಿಂಗಳ 15ನೇ ತಾರೀಖಿನ ನಂತರ ಮಾತ್ರ ಕೇಳಬಹುದು ಮತ್ತು ಅದು ಮಾಸಿಕ ಸಂಬಳದ ಗರಿಷ್ಠ 50% ಮೀರಬಾರದು.",
                c2: "ಅನುಮೋದಿತ ಮಿತಿಗಿಂತ ಹೆಚ್ಚಿನ ಸಂಬಳ ಮುಂಗಡ ವಿನಂತಿಗಳನ್ನು ಪರಿಗಣಿಸಲಾಗುವುದಿಲ್ಲ.",
                c3: "ಯಾವುದೇ ಸಿಬ್ಬಂದಿಯನ್ನು, ವಿಶೇಷವಾಗಿ ಮಹಿಳಾ ಸಿಬ್ಬಂದಿಯನ್ನು, ಬೆದರಿಸುವುದು, ಕಿರಿಕಿರಿ ಕೊಡುವುದು ಅಥವಾ ಪುನಃ ಪುನಃ ತೊಂದರೆ ನೀಡುವುದು ಕಟ್ಟುನಿಟ್ಟಾಗಿ ನಿಷೇಧಿಸಲಾಗಿದೆ.",
                c4: "ಕಾರಣವಿಲ್ಲದೆ ಹಲವಾರು ಬಾರಿ ಕರೆ ಮಾಡುವುದು, ಪ್ರೇಮ ವ್ಯಕ್ತಪಡಿಸುವುದು, ಅಥವಾ “love me” ತರಹದ ವೈಯಕ್ತಿಕ ಪದಗಳನ್ನು ಕಚೇರಿಯಲ್ಲಿ ಬಳಸುವುದು ಅನುಮತಿಸಲಾಗುವುದಿಲ್ಲ.",
                c5: "ಇದು ವೈಯಕ್ತಿಕ ಸ್ಥಳವಲ್ಲ; ವೃತ್ತಿಪರ ಕೆಲಸದ ಸ್ಥಳ. ಪ್ರತಿಯೊಬ್ಬರೂ ಶಿಸ್ತು, ಗೌರವ ಮತ್ತು ಕೆಲಸದ ಮೆರಗನ್ನು ತುಂಬಾ ಗಂಭೀರವಾಗಿ ಪಾಲಿಸಬೇಕು."
            },

            hi: {
                title: "छुट्टी के नियम",
                companyTitle: "कंपनी के नियम",
                r1: "केवल उपलब्ध Weekly Off या जमा हुई Leave ही उपयोग की जा सकती है।",
                r2: "01 मई 2026 से हर महीने 1 अतिरिक्त छुट्टी जोड़ी जाएगी, और यह दिसंबर के अंत में रीसेट हो जाएगी।",
                r3: "इस छुट्टी का उपयोग संबंधित महीने के किसी भी सार्वजनिक अवकाश के लिए किया जा सकता है या इसे अगले महीने(महीनों) तक आगे बढ़ाया जा सकता है।",
                r4: "अगर Leave balance नहीं है, तो अगली leave LOP बन जाएगी और लाल रंग में दिखेगी।",
                r5: "LOP का मतलब Loss of Pay है, और उस दिन का वेतन नहीं जोड़ा जाएगा।",
                r6: "Marketing users के लिए रविवार छुट्टी है। बाकी users के लिए सिर्फ admin रविवार को working day mark कर सकता है।",
                c1: "सैलरी एडवांस हर महीने की 15 तारीख के बाद ही लिया जा सकता है, और वह मासिक वेतन के अधिकतम 50% तक ही सीमित रहेगा।",
                c2: "स्वीकृत सीमा से अधिक सैलरी एडवांस का कोई अनुरोध स्वीकार नहीं किया जाएगा।",
                c3: "किसी भी कर्मचारी, विशेष रूप से महिला कर्मचारियों को धमकाना, परेशान करना या बार-बार तंग करना सख्त मना है।",
                c4: "ऑफिस में बिना कारण कई बार कॉल करना, प्रपोज करना, या “love me” जैसे निजी शब्दों का उपयोग करना अनुमति नहीं है।",
                c5: "यह एक पेशेवर कार्यस्थल है, कोई व्यक्तिगत जगह नहीं। सभी कर्मचारियों से अनुशासन, मर्यादा और सीमाओं का गंभीरता से पालन करने की अपेक्षा की जाती है।"
            }
        };


    function setLang(lang) {
        document.querySelectorAll('.lang-icons button').forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');
        document.getElementById('t-rules-title').textContent = i18n[lang].title;
        document.getElementById('t-rule-1').textContent = i18n[lang].r1;
        document.getElementById('t-rule-2').textContent = i18n[lang].r2;
        document.getElementById('t-rule-3').textContent = i18n[lang].r3;
        document.getElementById('t-rule-4').textContent = i18n[lang].r4;
        document.getElementById('t-company-rules-title').textContent = i18n[lang].companyTitle;
        document.getElementById('t-company-rule-1').textContent = i18n[lang].c1;
        document.getElementById('t-company-rule-2').textContent = i18n[lang].c2;
        document.getElementById('t-company-rule-3').textContent = i18n[lang].c3;
        document.getElementById('t-company-rule-4').textContent = i18n[lang].c4;
        document.getElementById('t-company-rule-5').textContent = i18n[lang].c5;
    }

    function formatAppDate(dateInput) {
        const dateObj = (dateInput instanceof Date)
            ? new Date(dateInput.getTime())
            : new Date(String(dateInput).trim() + (String(dateInput).includes('T') ? '' : 'T00:00:00'));
        if (Number.isNaN(dateObj.getTime())) return String(dateInput || '');
        const day = String(dateObj.getDate()).padStart(2, '0');
        const monthRaw = dateObj.toLocaleDateString('en-IN', { month: 'short' });
        const month = monthRaw.charAt(0).toUpperCase() + monthRaw.slice(1).toLowerCase();
        const year = String(dateObj.getFullYear()).slice(-2);
        return `${day} ${month} ${year}`;
    }

    let currentDate = new Date();
    let leavesData = [];
    let globalLeavesData = {};
    let unredeemedSundays = [];
    let weeklyOffSources = [];
    let selectedDateForLeave = null;
    let isAdminUser = false;
    let currentUserRole = '';
    let selectedLeaveType = 'planned';
    let selectedWeeklyOffSource = '';
    
    let currentActionOptions = [];
    let editingExistingLeave = false;

    const monthYearEl = document.getElementById('monthYear');
    const daysGrid = document.getElementById('daysGrid');
    const userSelect = document.getElementById('userSelector');

    function renderCalendar() {
        daysGrid.innerHTML = '';
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        monthYearEl.textContent = `${monthNames[month]} ${year}`;

        for (let i = 0; i < firstDay; i++) {
            let el = document.createElement('div'); el.className = 'day empty'; daysGrid.appendChild(el);
        }

        for (let i = 1; i <= daysInMonth; i++) {
            let el = document.createElement('div'); el.className = 'day'; el.textContent = i;
            
            const loopDate = new Date(year, month, i);
            const dateStr = [ loopDate.getFullYear(), String(loopDate.getMonth()+1).padStart(2,'0'), String(loopDate.getDate()).padStart(2,'0') ].join('-');
            
            el.dataset.date = dateStr;
            if (loopDate.getDay() === 0) el.classList.add('sunday');
            if (loopDate.getDay() === 0 && currentUserRole === 'market') el.classList.add('market-sunday');

            const userLeave = leavesData.find(l => l.leave_date === dateStr);
            if (userLeave) {
                el.dataset.type = userLeave.leave_type;
                if(userLeave.reason) el.dataset.reason = userLeave.reason;
            }

            if (globalLeavesData[dateStr] && globalLeavesData[dateStr].length > 0) {
                const names = globalLeavesData[dateStr];
                const badge = document.createElement('div'); badge.className = 'absent-badge'; badge.textContent = names.length;
                const tip = document.createElement('div'); tip.className = 'custom-tooltip'; tip.textContent = names.join(', ');
                badge.appendChild(tip); el.appendChild(badge);
            }

            el.addEventListener('click', (e) => {
                if (e.target.classList.contains('absent-badge')) return;
                handleDayClick(dateStr, userLeave);
            });
            daysGrid.appendChild(el);
        }
    }

    async function loadAdminUsers() {
        if(document.getElementById('btnLogs').style.display === 'block') return;
        try {
            const res = await fetch(`api_leaves.php?action=get_users`);
            const data = await res.json();
            if (data.success) {
                userSelect.innerHTML = '<option value="">My Profile</option>';
                data.users.forEach(u => {
                    userSelect.innerHTML += `<option value="${u.id}">${u.username}</option>`;
                });
                userSelect.style.display = 'block';
                document.getElementById('btnLogs').style.display = 'block';
            }
        } catch(e) {}
    }

    async function loadLeaves() {
        document.getElementById('loader').style.display = 'block';
        try {
            const y = currentDate.getFullYear();
            const m = currentDate.getMonth() + 1;
            let url = `api_leaves.php?action=get&year=${y}&month=${m}`;
            if(userSelect.value) url += `&target_user_id=${userSelect.value}`;

            const res = await fetch(url);
            const data = await res.json();
            if (data.success) {
                leavesData = data.leaves;
                globalLeavesData = data.globalLeaves || {};
                unredeemedSundays = data.unredeemedSundays || [];
                weeklyOffSources = data.weeklyOffSources || [];
                isAdminUser = data.isAdmin;
                currentUserRole = data.userRole || '';
                
                if (isAdminUser) loadAdminUsers();
                
                document.getElementById('sumWOff').textContent = unredeemedSundays.length;
                document.getElementById('sumExtra').textContent = data.extraBalance;
                document.getElementById('sumLop').textContent = data.lopLeaves || 0;

                renderCalendar();
            }
        } catch (e) {
            console.error('Error fetching leaves', e);
        }
        document.getElementById('loader').style.display = 'none';
    }

    function getDaysDiff(dateStr) {
        const leaveDate = new Date(dateStr);
        const today = new Date();
        leaveDate.setHours(0,0,0,0); today.setHours(0,0,0,0);
        return Math.floor((leaveDate.getTime() - today.getTime()) / (1000 * 3600 * 24));
    }

    function isWithinTwoWorkingDays(dateStr) {
        const leaveDate = new Date(dateStr + 'T00:00:00');
        const cursor = new Date();
        cursor.setHours(0, 0, 0, 0);
        let workingDays = 0;
        while (cursor < leaveDate && workingDays < 2) {
            cursor.setDate(cursor.getDate() + 1);
            if (cursor > leaveDate) break;
            const dow = cursor.getDay();
            const isSundayHoliday = currentUserRole === 'market' && dow === 0;
            if (!isSundayHoliday) workingDays++;
        }
        return workingDays < 2 && leaveDate >= new Date(new Date().setHours(0,0,0,0));
    }

    function createOption(type, label, meta = {}) {
        return { type, label, source: meta.source || '', hint: meta.hint || '', sublabel: meta.sublabel || '' };
    }

    function buildOptions(dateStr) {
        const container = document.getElementById('actionButtons');
        const optionNote = document.getElementById('optionNote');
        const dayOfWeek = new Date(dateStr + 'T00:00:00').getDay();
        const withinTwoWorkingDays = isWithinTwoWorkingDays(dateStr);
        currentActionOptions = [];

        if (dayOfWeek === 0 && isAdminUser && currentUserRole !== 'market') {
            currentActionOptions.push(createOption('worked_holiday', 'Mark As Working Day'));
            optionNote.textContent = 'This Sunday will be marked as a working day for this user.';
        } else if (dayOfWeek === 0 && currentUserRole === 'market') {
            optionNote.textContent = 'Sunday is a holiday for market users and cannot be modified.';
        } else {
            if (withinTwoWorkingDays) {
                currentActionOptions.push(createOption('planned', 'Loss Of Pay', { hint: 'Applied within 2 working days' }));
                optionNote.textContent = 'This leave will be marked as LOP.';
            } else {
                currentActionOptions.push(createOption('planned', 'Leave'));
                optionNote.textContent = 'Use available Leave balance or Weekly Off for this date.';
            }
            weeklyOffSources.slice(0, 2).forEach((entry) => {
                const sundayStr = entry && entry.source_date ? entry.source_date : '';
                const expiryStr = entry && entry.expiry_date ? entry.expiry_date : '';
                if (!sundayStr) return;
                currentActionOptions.push(createOption(
                    'weekly_off',
                    `Weekly Off ${formatAppDate(sundayStr)}`,
                    {
                        source: sundayStr,
                        sublabel: expiryStr ? `Use before ${formatAppDate(expiryStr)}` : ''
                    }
                ));
            });
        }

        if (!currentActionOptions.length) {
            container.innerHTML = '';
            selectedLeaveType = '';
            selectedWeeklyOffSource = '';
            return;
        }

        selectedLeaveType = currentActionOptions[0].type;
        selectedWeeklyOffSource = currentActionOptions[0].source || '';
        container.innerHTML = currentActionOptions.map((opt, idx) => `
            <button class="opt-btn ${opt.type}${idx === 0 ? ' selected' : ''}" data-val="${opt.type}" data-source="${opt.source || ''}">
                <span class="opt-main">${opt.label}</span>
                ${opt.sublabel ? `<span class="opt-sub">${opt.sublabel}</span>` : ''}
            </button>
        `).join('');

        container.querySelectorAll('.opt-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                container.querySelectorAll('.opt-btn').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
                selectedLeaveType = btn.getAttribute('data-val');
                selectedWeeklyOffSource = btn.getAttribute('data-source') || '';
            });
        });
    }

    function handleDayClick(dateStr, existingLeave) {
        const daysDiff = getDaysDiff(dateStr);
        const dayOfWeek = new Date(dateStr + 'T00:00:00').getDay();

        if (existingLeave) {
            if (isAdminUser && !['weekly_off', 'worked_holiday'].includes(existingLeave.leave_type)) {
                selectedDateForLeave = dateStr;
                editingExistingLeave = true;
                selectedLeaveType = existingLeave.leave_type === 'unplanned' ? 'admin_non_lop' : 'admin_lop';
                selectedWeeklyOffSource = '';
                document.getElementById('modalTitle').textContent = 'Change Leave Status';
                document.getElementById('modalDateDisplay').textContent = formatAppDate(dateStr);
                document.getElementById('optionNote').textContent = existingLeave.leave_type === 'unplanned'
                    ? 'This leave is currently LOP.'
                    : 'This leave is currently Non-LOP.';
                document.getElementById('leaveReason').value = existingLeave.reason || '';
                document.getElementById('leaveReason').style.display = 'none';
                document.getElementById('submitLeaveBtn').textContent = 'Update Leave Status';
                const options = document.getElementById('actionButtons');
                options.innerHTML = `
                    <button class="opt-btn unplanned${existingLeave.leave_type !== 'unplanned' ? ' selected' : ''}" data-val="admin_lop">Mark as LOP</button>
                    <button class="opt-btn planned${existingLeave.leave_type === 'unplanned' ? ' selected' : ''}" data-val="admin_non_lop">Mark as Non-LOP</button>
                    <button class="opt-btn" data-val="admin_remove">Remove Leave</button>
                `;
                options.querySelectorAll('.opt-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        options.querySelectorAll('.opt-btn').forEach(item => item.classList.remove('selected'));
                        btn.classList.add('selected');
                        selectedLeaveType = btn.getAttribute('data-val');
                    });
                });
                document.getElementById('leaveModal').classList.add('active');
                return;
            }
            if (daysDiff < 0 && !isAdminUser) {
                alert('Past leaves can only be removed by admin.');
                return;
            }
            if(confirm('Undo this leave tracking?')) toggleLeave(dateStr, null, null);
            return;
        }

        if (currentUserRole === 'market' && dayOfWeek === 0) {
            alert('Sunday is a holiday for market users. No leave can be added.');
            return;
        }

        selectedDateForLeave = dateStr;
        editingExistingLeave = false;
        document.getElementById('leaveReason').style.display = '';
        document.getElementById('submitLeaveBtn').textContent = 'Confirm Leave';
        document.getElementById('modalTitle').textContent = (dayOfWeek === 0 && isAdminUser && currentUserRole !== 'market')
            ? 'Mark Sunday As Working'
            : 'Apply for Leave';
        document.getElementById('modalDateDisplay').textContent = formatAppDate(dateStr);
        document.getElementById('leaveReason').value = '';
        buildOptions(dateStr);
        document.getElementById('leaveModal').classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
        if (id === 'leaveModal') {
            editingExistingLeave = false;
            document.getElementById('leaveReason').style.display = '';
            document.getElementById('submitLeaveBtn').textContent = 'Confirm Leave';
        }
    }

    async function setAdminLopStatus(dateStr, isLop) {
        document.getElementById('loader').style.display = 'block';
        try {
            const bodyPayload = {
                date: dateStr,
                target_user_id: userSelect.value || '',
                is_lop: isLop
            };
            const res = await fetch('api_leaves.php?action=set_lop_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(bodyPayload)
            });
            const data = await res.json();
            if (!data.success) {
                alert(data.error || 'Unable to update LOP status.');
            }
        } catch (e) {
            alert('Unable to update LOP status.');
        }
        document.getElementById('loader').style.display = 'none';
        loadLeaves();
    }

    async function toggleLeave(dateStr, type, reason) {
        document.getElementById('loader').style.display = 'block';
        try {
            const bodyPayload = { date: dateStr };
            if (type) {
                bodyPayload.type = type;
                bodyPayload.reason = reason;
                if (type === 'weekly_off' && selectedWeeklyOffSource) {
                    bodyPayload.weekly_off_source = selectedWeeklyOffSource;
                }
            }
            if (userSelect.value) { bodyPayload.target_user_id = userSelect.value; }

            const res = await fetch(`api_leaves.php?action=toggle`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(bodyPayload) });
            const data = await res.json();
            
            if (data.success) {
                if (data.state === 'added') leavesData.push({ id: data.id, leave_date: dateStr, leave_type: data.final_type, reason: reason });
                else if (data.state === 'removed') leavesData = leavesData.filter(l => l.leave_date !== dateStr);
                renderCalendar();
            } else {
                alert(data.error);
            }
        } catch (e) {
            alert('Unable to update leave right now.');
        }
        document.getElementById('loader').style.display = 'none';
        loadLeaves(); 
    }

    document.getElementById('btnLogs').addEventListener('click', async () => {
        document.getElementById('logsModal').classList.add('active');
        const cont = document.getElementById('logsContainer');
        cont.innerHTML = '<div class="loader" style="display:block">Loading...</div>';
        try {
            const res = await fetch('api_leaves.php?action=get_logs');
            const data = await res.json();
                if (data.success) {
                    if (data.logs.length === 0) cont.innerHTML = '<p style="text-align:center;">No recent activity.</p>';
                else {
                    cont.innerHTML = data.logs.map(log => `
                        <div class="log-item">
                            <div class="log-time">${new Date(log.created_at).toLocaleString('en-IN')}</div>
                            <div class="log-desc"><strong>${log.actor_name}</strong> ${log.action_type.toLowerCase()} a leave for <strong>${log.target_name}</strong> on ${formatAppDate(log.leave_date)}.<br><span style="color:#7F8C8D;font-size:0.75rem">${log.details || ''}</span></div>
                        </div>`).join('');
                }
            } else { cont.innerHTML = `<p style="color:red">${data.error}</p>`; }
        } catch(e) {}
    });

    document.getElementById('submitLeaveBtn').addEventListener('click', () => {
        if (!selectedDateForLeave) return;
        if (editingExistingLeave) {
            if (selectedLeaveType === 'admin_remove') {
                const dateToRemove = selectedDateForLeave;
                closeModal('leaveModal');
                if (confirm('Remove this leave?')) toggleLeave(dateToRemove, null, null);
                return;
            }
            const isLop = selectedLeaveType === 'admin_lop';
            closeModal('leaveModal');
            setAdminLopStatus(selectedDateForLeave, isLop);
            return;
        }
        const reason = document.getElementById('leaveReason').value.trim();
        closeModal('leaveModal');
        toggleLeave(selectedDateForLeave, selectedLeaveType, reason);
    });

    document.getElementById('prevBtn').addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(); loadLeaves(); });
    document.getElementById('nextBtn').addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(); loadLeaves(); });
    userSelect.addEventListener('change', () => loadLeaves());
    
    document.getElementById('leaveModal').addEventListener('click', (e) => { if (e.target === document.getElementById('leaveModal')) closeModal('leaveModal'); });
    document.getElementById('logsModal').addEventListener('click', (e) => { if (e.target === document.getElementById('logsModal')) closeModal('logsModal'); });

    renderCalendar();
    loadLeaves();
</script>
</body>
</html>
