<?php
ob_start();
require_once '../auth.php'; // Assuming we create auth.php in admin folder
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../config.php'; // contains $mysqli = new mysqli(...);
$error = '';

$user_id = $_COOKIE['auth_user'];
$roll = $_COOKIE['auth_role'] ?? '';
$fullname = $_COOKIE['auth_name'] ?? '';

// Always work with padded month for correct date strings below
$month = date('m');
$year = date('Y');
if (isset($_GET['month']) && isset($_GET['year'])) {
    $month = str_pad(intval($_GET['month']), 2, "0", STR_PAD_LEFT);
    $year = intval($_GET['year']);
} else {
    $month = date('m');
    $year = date('Y');
}

// For navigation links
$current_month = intval($month);
$current_year = intval($year);

$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }

$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) { $next_month = 1; $next_year++; }

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$firstDay = date('w', strtotime("$year-$month-01"));

// Fetch leaves for the displayed month including user_id for delete buttons
$stmt = $conn->prepare(
    "SELECT leaves.leave_date, users.id AS user_id, users.fullname, leaves.reason
     FROM leaves
     JOIN users ON users.id = leaves.user_id
     WHERE MONTH(leave_date) = ? AND YEAR(leave_date) = ?"
);

$stmt->bind_param('ii', $current_month, $current_year);
$stmt->execute();
$result = $stmt->get_result();
$leaves = [];
while ($row = $result->fetch_assoc()) {
    $leaves[$row['leave_date']][] = [
        'user_id'  => $row['user_id'],
        'fullname' => $row['fullname'],
        'reason'   => $row['reason']
    ];
}

// Fetch leave summary data needed:
$today = date('Y-m-d');

// 1. Who is on leave today
$stmtToday = $conn->prepare(
    "SELECT users.fullname FROM leaves JOIN users ON users.id = leaves.user_id WHERE leave_date = ?"
);
$stmtToday->bind_param('s', $today);
$stmtToday->execute();
$resultToday = $stmtToday->get_result();
$todayLeaves = [];
while ($row = $resultToday->fetch_assoc()) {
    $todayLeaves[] = $row['fullname'];
}

// 2. Number of leaves taken by logged-in user in current month
$stmtCountMonth = $conn->prepare(
    "SELECT COUNT(*) as count_leaves FROM leaves WHERE user_id = ? AND MONTH(leave_date) = ? AND YEAR(leave_date) = ?"
);
$stmtCountMonth->bind_param('iii', $user_id, $current_month, $current_year);
$stmtCountMonth->execute();
$countMonth = 0;
if ($res = $stmtCountMonth->get_result()->fetch_assoc()) {
    $countMonth = $res['count_leaves'];
}

// 3. Number of leaves taken by logged-in user in current year
$stmtCountYear = $conn->prepare(
    "SELECT COUNT(*) as count_leaves FROM leaves WHERE user_id = ? AND YEAR(leave_date) = ?"
);
$stmtCountYear->bind_param('ii', $user_id, $current_year);
$stmtCountYear->execute();
$countYear = 0;
if ($res = $stmtCountYear->get_result()->fetch_assoc()) {
    $countYear = $res['count_leaves'];
}

// Prepare calendar slot data:
$calendar = [];
$wd = 0;
for ($i=0; $i<$firstDay; $i++) {
    $calendar[] = ['day'=>null, 'date_str'=>null, 'leave'=>[]];
    $wd++;
}
// Use always zero-padded month and day for correct date keys!
for ($d=1; $d <= $daysInMonth; $d++, $wd++) {
    $dateStr = sprintf('%04d-%02d-%02d', $year, $current_month, $d);
    $calendar[] = [
        'day' => $d,
        'date_str' => $dateStr,
        'leave' => isset($leaves[$dateStr]) ? $leaves[$dateStr] : []
    ];
}
while ($wd % 7 != 0) {
    $calendar[] = ['day'=>null, 'date_str'=>null, 'leave'=>[]];
    $wd++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Employee Leave Calendar</title>
<link rel="stylesheet" href="vacation_styles.css">
<style>
/* Leave delete button styling */
.remove-leave {
  cursor: pointer;
  color: red;
  border: none;
  background: none;
  font-weight: bold;
  font-size: 16px;
  line-height: 1;
  margin-right: 4px;
      width: unset;
    padding: unset;
    margin: unset;
    
}

.month-nav-link {
    text-decoration: none;
}

/* Position delete button top-right for the day cell */
.calendar-cell {
    position: relative;
}


/* Highlight logged in user's leaves */
.leave-person.mine {
    color: #007bff; /* Blue text for own leaves */
    font-weight: 600;
}

/* Dim other users' leaves */
.leave-person.others {
    opacity: 0.5;
}
</style>
</head>
<body>
<div class="header">
    <img src="https://smartronic.online/content/uploads/2025/01/smarthome-black2.svg" alt="Logo">
    <span><?php echo htmlspecialchars($fullname); ?></span>
</div>
<hr>

<div class="summary-box">
    <h2>Leave Summary</h2>
    <p><strong>Leaves today:</strong>
    <?php if (count($todayLeaves) > 0): ?>
        <span class="leave-names"><?php echo htmlspecialchars(implode(", ", $todayLeaves)); ?></span>
    <?php else: ?>
        <span class="no-leaves">No leaves today</span>
    <?php endif; ?>
    </p>
    <div class="counts">
        <p><strong>Your leaves this month (<?php echo date('F Y', strtotime("$current_year-$current_month-01")); ?>):</strong> <?php echo $countMonth; ?></p>
        <p><strong>Your leaves this year (<?php echo $current_year; ?>):</strong> <?php echo $countYear; ?></p>
    </div>
</div>

<div class="calendar-container">
<div class="month-links" style="display: flex; justify-content: center; gap: 20px; margin-bottom: 16px; align-items:center; text-decoration: none;">
    <a href="?month=<?=sprintf('%02d', $prev_month)?>&year=<?=$prev_year?>" class="month-nav-link">&laquo; </a>
    <span class="month-current" style="font-weight: 600; color: #007bff;"><?=date('F Y', strtotime("$current_year-".sprintf('%02d', $current_month)."-01"))?></span>
    <a href="?month=<?=sprintf('%02d', $next_month)?>&year=<?=$next_year?>" class="month-nav-link"> &raquo;</a>
</div>

<!-- FLEX CALENDAR HEADER -->
<div class='calendar-grid' style="margin-bottom:4px;">
  <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $wd) {
    echo "<div class='calendar-day-label'>$wd</div>";
  } ?>
</div>

<!-- FLEX CALENDAR CELLS -->
<div class='calendar-grid'>
<?php
foreach ($calendar as $i => $cell) {
    $isToday = ($cell['date_str'] === date('Y-m-d'));
    $hasLeave = is_array($cell['leave']) && count($cell['leave']) > 0;
    $classes = "calendar-cell";
    if (!$cell['day']) $classes .= " empty";
    if ($isToday) $classes .= " current-day";
    if ($hasLeave) $classes .= " leave";

    echo "<div class='$classes'>";
    if ($cell['day']) {
        echo "<div class='day-num'>{$cell['day']}</div>";
        echo '<div class="employee-leaves">';

        if ($hasLeave) {
            // For normal user: check if they have leave this day
            $userHasLeaveToday = false;
            foreach ($cell['leave'] as $leaveEntry) {
                if ($leaveEntry['user_id'] === $user_id) {
                    $userHasLeaveToday = true;
                    break;
                }
            }

            // Normal user sees one delete button at day top corner if they have leave


            // Show leave names, colored and styled
            foreach ($cell['leave'] as $leaveEntry) {
                $leaveName = $leaveEntry['fullname'];
                $reason = $leaveEntry['reason'];
                $leaveUserId = $leaveEntry['user_id'];
                $isMine = ($leaveUserId === $user_id);

                echo '<span class="leave-person' . ($isMine ? ' mine' : ' others') . '">';
                // Admin sees "×" next to each leave to delete
                if ($roll === 'admin') {
                    echo '<button class="remove-leave" title="Delete leave" onclick="deleteLeave(\'' . $cell['date_str'] . '\',' . (int)$leaveUserId . ')">&times;</button> ';
                }
                echo htmlspecialchars($leaveName);
                if (!empty($reason)) {
                    echo '<div class="leave-reason">' . htmlspecialchars($reason) . '</div>';
                }
                echo '</span>';
            }
        }

        echo '</div>';

        // **Show "+" button only if user DOES NOT have leave that day**
        $userHasLeaveAlready = false;
        print_r($cell['leave']);
        foreach ($cell['leave'] as $leaveEntry) {
            print_r( $leaveEntry);
           echo '1' . $leaveEntry['user_id'] . $user_id;

            if ($leaveEntry['user_id'] === $user_id) {
                $userHasLeaveAlready = true;
                break;
            }
        }
        echo "$roll .  $userHasLeaveAlready";
       if ($roll !== 'admin' && $userHasLeaveAlready) {
        echo "hi";
    // Normal user has leave, show delete button here instead of +
    echo '<button class="day-delete-btn" title="Delete your leave for this day" onclick="deleteLeave(\'' . $cell['date_str'] . '\',' . (int)$user_id . ')">&times;</button>';
} elseif (!$userHasLeaveAlready) {
    // No leave yet, show "+"
    echo "<button class='log-leave-btn' data-date='{$cell['date_str']}'>+</button>";
}
        
    }
    echo "</div>";
}
?>
</div>
</div>

<!-- Log leave modal -->
<div id="leaveModal" role="dialog" aria-modal="true" aria-labelledby="modal-title" aria-hidden="true" style="display:none; align-items:center; justify-content:center; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5);">
    <div style="background:#fff; padding:20px; border-radius:6px; min-width:280px; max-width:320px; position:relative;">
        <button class="close-btn" onclick="closeModal()" aria-label="Close" style="position:absolute; top:8px; right:10px; font-size:20px; background:none; border:none; cursor:pointer;">&times;</button>
        <h3 id="modal-title">Log Leave for <span id="selected-date-formatted"></span></h3>
        
        <form method="post" id="leaveForm">
            <input type="hidden" name="leave_date" id="leaveInput" />
            
            <?php if ($roll === 'admin'): ?>
            <label for="userSelect">Select User:</label>
            <select name="user_id" id="userSelect" required style="margin-bottom:8px; width:100%;">
                <option value="">-- Select User --</option>
                <?php
                $usersResult = $conn->query("SELECT id, fullname FROM users ORDER BY fullname ASC");
                while ($user = $usersResult->fetch_assoc()) {
                    echo '<option value="' . intval($user['id']) . '">' . htmlspecialchars($user['fullname']) . '</option>';
                }
                ?>
            </select>
            <?php endif; ?>
            
            <label for="reasonInput">Reason (optional):</label>
            <input type="text" id="reasonInput" name="reason" autocomplete="off" size="15" style="margin-bottom:12px; width:100%;" />

            <div style="display:flex; justify-content: flex-end; gap:10px;">
                <button type="submit" id="okBtn">OK</button>
                <button type="button" onclick="closeModal()">Cancel</button>
            </div>
        </form>
        <div id="modal-message" role="alert" aria-live="assertive" style="margin-top:12px; color: red;"></div>
    </div>
</div>

<script>
  // Open modal and populate date
  document.querySelectorAll('.log-leave-btn').forEach(function(btn) {
    btn.onclick = function() {
      var d = this.getAttribute('data-date');
      // Format date dd MMM yyyy
      var formatted = new Date(d).toLocaleDateString(undefined, {day: '2-digit', month: 'short', year: 'numeric'});
      document.getElementById('selected-date-formatted').innerText = formatted;
      document.getElementById('leaveInput').value = d;
      document.getElementById('modal-message').innerText = '';

      var userSelect = document.getElementById('userSelect');
      if (userSelect) userSelect.value = '';

      document.getElementById('leaveModal').style.display = 'flex';
      document.getElementById('leaveModal').setAttribute('aria-hidden', 'false');
    }
  });

  function closeModal() {
    document.getElementById('leaveModal').style.display = 'none';
    document.getElementById('leaveModal').setAttribute('aria-hidden', 'true');
  }

  // AJAX form submission for logging leave
  document.getElementById('leaveForm').onsubmit = async function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    let resp = await fetch('log_leave.php', { method: 'POST', body: formData });
    let r = await resp.json();
    document.getElementById('modal-message').innerText = r.message;
    if (r.success) setTimeout(() => window.location.reload(), 1200);
  };

  // Close modal on ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === "Escape" && document.getElementById('leaveModal').style.display === 'flex') {
      closeModal();
    }
  });

  // Delete leave function with user_id to identify the leave owner
  function deleteLeave(dateStr, userId) {
    if (confirm("Are you sure you want to delete this leave?")) {
      fetch('delete_leave.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'leave_date=' + encodeURIComponent(dateStr) + '&user_id=' + encodeURIComponent(userId)
      })
      .then(resp => resp.json())
      .then(data => {
        if (data.success) {
          window.location.reload();
        } else {
          alert(data.message || "Failed to delete leave.");
        }
      });
    }
  }
</script>

</body>
</html>
