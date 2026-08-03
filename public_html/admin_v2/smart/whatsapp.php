<?php
ob_start();
require_once '../auth.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../config.php';

$authRole = strtolower(trim((string)($_COOKIE['auth_role'] ?? '')));
$isAdminLike = in_array($authRole, ['admin', 'manager'], true);
if (!isset($_COOKIE['auth_role']) || (!$isAdminLike && $authRole !== 'market')) {
    echo "No access";
    exit;
}

date_default_timezone_set('Asia/Kolkata');

// API LOGIC
if (isset($_GET['get_orders'])) {
    header('Content-Type: application/json');
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB connection failed']);
        exit;
    }

    $role = $_COOKIE['auth_role'] ?? '';
    $authName = $_COOKIE['auth_name'] ?? '';
    $mapOwnerKey = function($name) {
        $n = strtolower(trim($name));
        if ($n === 'zoya') return 'zoy';
        if ($n === 'varsha') return 'var';
        if ($n === 'amreen') return 'amr';
        return '';
    };
    $owner = $_GET['owner'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $searchTerm = $_GET['search'] ?? '';

    $sql = "SELECT name, idno, phone, date, price FROM orders WHERE 1=1";
    $params = [];
    $types = "";

    if ($role === 'market') {
        if (!empty($authName)) {
            $ownerKey = $mapOwnerKey($authName);
            if (!empty($ownerKey)) {
                $sql .= " AND (Owner = ? OR Owner = ?)";
                $params[] = $ownerKey;
                $params[] = $authName;
                $types .= "ss";
            } else {
                $sql .= " AND Owner = ?";
                $params[] = $authName;
                $types .= "s";
            }
        }
    } else {
        if (!empty($owner)) {
            $sql .= " AND Owner = ?";
            $params[] = $owner;
            $types .= "s";
        }
    }

    if (!empty($startDate)) {
        $sql .= " AND date >= ?";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $sql .= " AND date <= ?";
        $params[] = $endDate;
        $types .= "s";
    }

    if (!empty($searchTerm)) {
        $sql .= " AND (name LIKE ? OR phone LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }

    $sql .= " ORDER BY date DESC";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    echo json_encode(['success' => true, 'orders' => $orders]);

    $stmt->close();
    $conn->close();
    exit;
}

if (isset($_GET['get_messages']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $messages_file = 'whatsapp_messages.json';

    function get_messages() {
        global $messages_file;
        if (!file_exists($messages_file)) {
            return [];
        }
        $json = file_get_contents($messages_file);
        return json_decode($json, true);
    }

    function save_messages($messages) {
        global $messages_file;
        file_put_contents($messages_file, json_encode($messages, JSON_PRETTY_PRINT));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode(['success' => true, 'messages' => get_messages()]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $role = $_COOKIE['auth_role'] ?? '';
        if (!in_array(strtolower(trim((string)$role)), ['admin', 'manager'], true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Not allowed']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $messages = get_messages();

        if ($data['active']) {
            foreach ($messages as &$msg) {
                $msg['active'] = false;
            }
        }

        $existing_index = -1;
        foreach ($messages as $index => $msg) {
            if ($msg['title'] === $data['title']) {
                $existing_index = $index;
                break;
            }
        }

        if ($existing_index !== -1) {
            $messages[$existing_index] = $data;
        } else {
            $messages[] = $data;
        }

        save_messages($messages);
        echo json_encode(['success' => true, 'messages' => $messages]);
    }
    exit;
}

// PAGE RENDERING LOGIC

// Fetch owners for the filter dropdown
$owners = [];
$owner_query = "SELECT DISTINCT Owner FROM orders WHERE Owner IS NOT NULL AND Owner != '' ORDER BY Owner ASC";
if ($owner_result = $conn->query($owner_query)) {
    while ($owner_row = $owner_result->fetch_assoc()) {
        $owners[] = $owner_row['Owner'];
    }
}

$nameAssign = $_COOKIE['auth_name'] ?? 'User';
$isAdmin = $isAdminLike;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <title>SM WhatsApp | Messenger</title>
  <link rel="icon" type="image/png" sizes="32x32" href="/content/uploads/2025/01/cropped-Site-Icon-32x32.png">
  <link rel="apple-touch-icon" href="/content/uploads/2025/01/cropped-Site-Icon-180x180.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="lead_list_styles_enhanced.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <style>
        /* Page-specific styles */
        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--neutral-800);
            margin-bottom: var(--space-5);
        }
        .filters-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: var(--space-4);
            padding: var(--space-4);
            background: #fff;
            border-radius: var(--radius-md);
            margin-bottom: var(--space-5);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--neutral-200);
        }
        .filters-row {
            display: flex;
            align-items: center;
            gap: var(--space-4);
            width: 100%;
            flex-wrap: wrap;
        }
        .filters-row + .filters-row {
            margin-top: var(--space-3);
        }
        .filters-container select {
            border: 1px solid var(--neutral-300);
            border-radius: var(--radius-sm);
            padding: var(--space-2) var(--space-3);
            font-size: 14px;
            background-color: #fff;
        }
        #apply-filters.header-icon-btn {
            height: 35px;
            padding: 0 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #73a886;
            border-radius: var(--radius-sm);
            margin: 0px;
            margin-top: -8px;
            background: #28d366;
            color: white;
        }
        .filters-container input[type="date"], .filters-container input[type="text"] {
     border: 1px solid var(--neutral-300); 
     border-radius: var(--radius-sm); 
     padding: 8px 12px; 
     font-size: 18px; 
     background-color: #fff; 
     height: 18px; 
     flex-grow: 1; 
     min-width: 120px; 
     max-width: 130px; 
 }
        .filters-container select:focus,
        .filters-container input[type="date"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .table-wrapper {
            background: #fff;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            border: 1px solid var(--neutral-200);
        }
        .lead-table {
            width: 100%;
            border-collapse: collapse;
        }
        .lead-table th, .lead-table td {
            padding: var(--space-3) var(--space-4);
            text-align: left;
            border-bottom: 1px solid var(--neutral-200);
            font-size: 14px;
        }
        .lead-table th {
            background-color: var(--neutral-50);
            font-weight: 600;
            color: var(--neutral-600);
        }
        .lead-table td {
            color: var(--neutral-700);
        }
        .send-btn {
            background-color: #25D366;
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            padding: var(--space-2) var(--space-3);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color var(--transition-fast);
        }
        .send-btn:hover {
            background-color: #1DAE56;
        }
        .btn-cta.btn-floating {
            position: fixed;
            right: 24px;
            bottom: 24px;
            background-color: var(--primary);
            z-index: 999;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100vw; /* Use viewport width */
            height: 100vh !important; /* Use viewport height */
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            max-height: 100%;
        }
        .modal-content {
            background-color: #fafafa;
            margin: 10% auto;
            padding: 16px;
            border: 1px solid #e0e0e0;
            width: 90%;
            max-width: 500px;
            border-radius: 6px;
            box-shadow: var(--shadow-md);
        }
        .date-range-links a {
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--neutral-600);
            font-weight: 500;
            transition: all var(--transition-fast);
        }
        .date-range-links a.active {
            background-color: var(--primary);
            color: white;
            box-shadow: var(--shadow-sm);
        }
        .modal-content h2 {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 12px;
        }
        .modal-content input[type="text"],
        .modal-content select {
            width: 80%;
            padding: 4px 8px;
            margin-bottom: 12px;
            border: 1px solid var(--neutral-300);
            border-radius: var(--radius-sm);
            height: 38px;
            font-size: 16px;
        }
        .modal-content textarea {
            width: 100%;
            padding: 6px 8px;
            margin-bottom: 12px;
            border: 1px solid var(--neutral-300);
            border-radius: var(--radius-sm);
            min-height: 100px;
            font-size: 16px;
            resize: vertical;
        }
        .select-dropdown.dropdown-trigger {
            height: 38px !important;
            line-height: 18px !important;
            font-size: 16px !important;
        }
        .dropdown-content li > span {
            font-size: 16px !important;
        }
        .select-wrapper .caret {
            position: absolute;
            left: 77%;
            top: 0;
            bottom: 0;
            margin: auto 0;
            z-index: 0;
            fill: rgba(0, 0, 0, 0.87);
        }
        .filters-container .select-dropdown.dropdown-trigger {
            height: 20px !important;
            line-height: 20px !important;
        }
        .textarea-wrap {
            position: relative;
        }
        #save-message-btn {
            position: absolute;
            right: 8px;
            bottom: 28px;
            width: 30px;
            height: 30px;
            border-radius: 9999px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content textarea {
            min-height: 120px;
            resize: vertical;
        }
        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body class="grey lighten-4">

    <div class="page-header" id="pageHeader">
        <div class="logo-section">
            <a href="/admin_v2/smart/" class="custom-logo-link" rel="home">
                <img id="main-logo" width="200" height="40" src="https://smartronic.online/content/uploads/2025/01/smarthome-black2.svg" class="custom-logo" alt="Smartronic">
            </a>
            <?php echo "<h2>Hello, $nameAssign!</h2>";?>
        </div>
        <div class="header-right">
            <a class="header-icon-btn" href="/admin_v2/logout.php" aria-label="Logout" title="Logout">
                <i class="fa-solid fa-sign-out-alt"></i>
            </a>
        </div>
    </div>

    <section class="crf-form-wrapper">
        <div class="container">
            <div class="crf-container">
                <div class="crf-left-col">
                    <h5 class="page-title">WhatsApp Messenger</h5>
                    <div id="active-message-banner" style="margin-bottom: var(--space-4); font-size: 13px; color: var(--neutral-700);"></div>
                    <div class="filters-container">
                        <?php if ($isAdmin): ?>
                            <div class="filters-row">
                                <div class="search-field" style="flex-grow: 2;">
                                    <input type="text" id="searchInput" placeholder="Search by name, phone...">
                                </div>
                                <div class="mine-links">
                                    <a href="#" class="mine-filter-link active" data-mine="">All</a>
                                    <span class="mine-sep">|</span>
                                    <a href="#" class="mine-filter-link" data-mine="var">Varsha</a>
                                    <span class="mine-sep">|</span>
                                    <a href="#" class="mine-filter-link" data-mine="amr">Amreen</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="filters-row">
                            <input type="date" id="start-date">
                            <input type="date" id="end-date">
                            <select id="date-range-filter">
                                <option value="">Custom Range</option>
                                <option value="this_week">This Week</option>
                                <option value="last_week">Last Week</option>
                                <option value="last_2_weeks">Last 2 Weeks</option>
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                            </select>
                            <button id="apply-filters" class="header-icon-btn" type="button" aria-label="Apply">
                                <i class="fa-solid fa-check"></i>
                            </button>
                        </div>
                    </div>
                    <div class="table-wrapper">
                        <table class="lead-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>ID</th>
                                    <th>Phone</th>
                                    <th>Date</th>
                                    <?php if ($isAdmin): ?>
                                    <th>Price</th>
                                    <?php endif; ?>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="orders-table-body">
                                <!-- Rows will be inserted here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="btn-cta btn-floating" id="add-message-btn">
        <i class="fas fa-plus"></i>
    </div>

    <div id="message-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <?php if ($isAdmin): ?>
                <h2>Message Templates</h2>
                <select id="message-select">
                    <option value="new">New Message</option>
                </select>
                <input type="text" id="message-title" placeholder="Message Title">
                <div class="textarea-wrap">
                    <textarea id="message-content" placeholder="Message Content"></textarea>
                    <button id="save-message-btn" class="btn" title="Save" aria-label="Save"><i class="fa-solid fa-check"></i></button>
                </div>
                <label>
                    <input type="checkbox" id="message-active" />
                    <span>Active</span>
                </label>
            <?php else: ?>
                <h2>Active Message</h2>
                <div id="active-message-readonly" style="background:#fff;border:1px solid var(--neutral-200);border-radius:var(--radius-sm);padding:12px;">
                    <div id="active-message-title" style="font-weight:600;margin-bottom:8px;"></div>
                    <div id="active-message-content" style="white-space:pre-wrap;"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        M.AutoInit(); // Initialize all Materialize components

        const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        const applyFiltersBtn = document.getElementById('apply-filters');
        const startDateInput = document.getElementById('start-date');
        const endDateInput = document.getElementById('end-date');
        const dateRangeFilter = document.getElementById('date-range-filter');
        const tableBody = document.getElementById('orders-table-body');
        
        const addMessageBtn = document.getElementById('add-message-btn');
        const messageModal = document.getElementById('message-modal');
        const closeBtn = document.querySelector('.close-button');
        const messageSelect = document.getElementById('message-select');
        const messageTitle = document.getElementById('message-title');
        const messageContent = document.getElementById('message-content');
        const messageActive = document.getElementById('message-active');
        const saveMessageBtn = document.getElementById('save-message-btn');
        const activeMessageBanner = document.getElementById('active-message-banner');
        const activeMessageReadonlyTitle = document.getElementById('active-message-title');
        const activeMessageReadonlyContent = document.getElementById('active-message-content');

        let messages = [];

        addMessageBtn.addEventListener('click', () => { messageModal.style.display = 'block'; });
        closeBtn.addEventListener('click', () => { messageModal.style.display = 'none'; });
        window.addEventListener('click', (e) => { if (e.target == messageModal) { messageModal.style.display = 'none'; } });

        if (saveMessageBtn) {
            saveMessageBtn.addEventListener('click', saveMessage);
        }
        if (messageSelect) {
            messageSelect.addEventListener('change', loadMessageToEdit);
        }

        applyFiltersBtn.addEventListener('click', fetchOrders);
        dateRangeFilter.addEventListener('change', () => {
            handleDateRangeChange();
            fetchOrders();
        });

        const mineLinks = document.querySelectorAll('.mine-filter-link');
        mineLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                mineLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                fetchOrders();
            });
        });

        function setDefaultDateRange() {
            dateRangeFilter.value = 'this_week';
            handleDateRangeChange();
        }

        function handleDateRangeChange() {
            const range = dateRangeFilter.value;
            const now = new Date();
            let start = new Date();
            let end = new Date();

            switch (range) {
                case 'this_week':
                    start.setDate(now.getDate() - now.getDay());
                    end.setDate(now.getDate() + (6 - now.getDay()));
                    break;
                case 'last_week':
                    start.setDate(now.getDate() - now.getDay() - 7);
                    end.setDate(now.getDate() - now.getDay() - 1);
                    break;
                case 'last_2_weeks':
                    start.setDate(now.getDate() - now.getDay() - 14);
                    end.setDate(now.getDate() - now.getDay() - 1);
                    break;
                case 'this_month':
                    start = new Date(now.getFullYear(), now.getMonth(), 1);
                    end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                    break;
                case 'last_month':
                    start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                    end = new Date(now.getFullYear(), now.getMonth(), 0);
                    break;
                default:
                    startDateInput.value = '';
                    endDateInput.value = '';
                    return;
            }
            startDateInput.value = start.toISOString().split('T')[0];
            endDateInput.value = end.toISOString().split('T')[0];
        }

        function fetchOrders() {
            let owner = '';
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            const searchTerm = IS_ADMIN ? document.getElementById('searchInput').value : '';

            const activeMineLink = document.querySelector('.mine-filter-link.active');
            if (activeMineLink) {
                owner = activeMineLink.dataset.mine;
            }

            let apiEndpoint = 'whatsapp.php';
            const params = new URLSearchParams();
            params.append('get_orders', 'true');
            if (owner) params.append('owner', owner);
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            if (searchTerm) params.append('search', searchTerm);

            fetch(`${apiEndpoint}?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    tableBody.innerHTML = '';
                    if (data.success && data.orders) {
                        data.orders.forEach(order => {
                            const priceCell = IS_ADMIN ? `<td>${order.price}</td>` : ``;
                            const row = `
                                <tr>
                                    <td>${order.name}</td>
                                    <td>${order.idno}</td>
                                    <td>${order.phone}</td>
                                    <td>${order.date}</td>
                                    ${priceCell}
                                    <td><button class="send-btn" data-phone="${order.phone}" aria-label="Send"><i class="fa-solid fa-arrow-right"></i></button></td>
                                </tr>
                            `;
                            tableBody.innerHTML += row;
                        });
                    }
                });
        }

        async function fetchMessages() {
            try {
                const response = await fetch('whatsapp.php?get_messages=true');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                if (data.success && Array.isArray(data.messages)) {
                    messages = data.messages;
                    updateMessageDropdown();
                    updateActiveMessageUI();
                    console.log('Successfully fetched and loaded messages:', messages);
                } else {
                    console.error('API response was successful, but data is not in the expected format:', data);
                    messages = [];
                    updateMessageDropdown();
                    updateActiveMessageUI();
                }
            } catch (error) {
                console.error('An error occurred while fetching messages:', error);
                messages = [];
                updateMessageDropdown();
                updateActiveMessageUI();
            }
        }

        function updateMessageDropdown() {
            if (!messageSelect) return;
            messageSelect.innerHTML = <?php echo $isAdmin ? "'<option value=\"new\">New Message</option>'" : "''"; ?>;
            messages.forEach(msg => {
                const option = document.createElement('option');
                option.value = msg.title;
                option.textContent = msg.title;
                messageSelect.appendChild(option);
            });
            try {
                const inst = M.FormSelect.getInstance(messageSelect);
                if (inst && typeof inst.destroy === 'function') inst.destroy();
                M.FormSelect.init(messageSelect);
            } catch (_) {}
        }

        function updateActiveMessageUI() {
            const activeMsg = messages.find(m => m.active);
            if (activeMessageBanner) {
                if (activeMsg) {
                    activeMessageBanner.textContent = `Active message: ${activeMsg.title} — ${activeMsg.content}`;
                } else {
                    activeMessageBanner.textContent = 'Active message: None';
                }
            }
            if (activeMessageReadonlyTitle && activeMessageReadonlyContent) {
                if (activeMsg) {
                    activeMessageReadonlyTitle.textContent = activeMsg.title;
                    activeMessageReadonlyContent.textContent = activeMsg.content;
                } else {
                    activeMessageReadonlyTitle.textContent = 'None';
                    activeMessageReadonlyContent.textContent = '';
                }
            }
        }

        function loadMessageToEdit() {
            const selectedTitle = messageSelect.value;
            if (selectedTitle === 'new') {
                messageTitle.value = '';
                messageContent.value = '';
                messageActive.checked = false;
            } else {
                const selectedMessage = messages.find(msg => msg.title === selectedTitle);
                if (selectedMessage) {
                    messageTitle.value = selectedMessage.title;
                    messageContent.value = selectedMessage.content;
                    messageActive.checked = selectedMessage.active;
                }
            }
        }

        function saveMessage() {
            const title = messageTitle.value;
            const content = messageContent.value;
            const active = messageActive.checked;

            if (!title || !content) {
                alert('Title and content are required.');
                return;
            }

            const messageData = { title, content, active };

            fetch('whatsapp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(messageData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messages = data.messages;
                    updateMessageDropdown();
                    messageModal.style.display = 'none';
                }
            });
        }

        tableBody.addEventListener('click', function(e) {
            const btn = e.target.closest('.send-btn');
            if (btn) {
                function normalizePhone(raw) {
                    let p = String(raw).trim();
                    p = p.replace(/\s+/g, '');
                    p = p.replace(/[-().]/g, '');
                    if (p.startsWith('+')) return p;
                    if (p.startsWith('91')) return '+' + p;
                    if (p.startsWith('0')) {
                        p = p.replace(/^0+/, '');
                    }
                    if (p.length === 10) return '+91' + p;
                    return '+' + p;
                }
                const phone = normalizePhone(btn.dataset.phone);
                const activeMessage = messages.find(msg => msg.active);

                if (activeMessage) {
                    const whatsappUrl = `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(activeMessage.content)}`;
                    window.open(whatsappUrl, '_blank');
                } else {
                    alert('No active message set. Please set an active message in the template editor.');
                }
            }
        });

        // Initial fetch
        setDefaultDateRange();
        fetchOrders();
        fetchMessages();
    });
    </script>
</body>
</html>
