<?php
ob_start();
require_once '../auth.php'; // Assuming we create auth.php in admin folder
?>
 
<?php

if ($_SERVER['HTTP_HOST'] === 'smartronic.online') {
    $servername = '127.0.0.1:3306';
    $dbusername = 'u398852039_smartronic';
    $password = 'Chennai@40!';
    $database = 'u398852039_smartronic';
} else {
    $servername = "localhost";
    $dbusername = "root";
    $password = "root";
    $database = "smarthome";
}

$conn = new mysqli($servername, $dbusername, $password, $database);

// Save form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitInstall'])) {
    $idno = $_POST['idno'];
    $cableLength = (int)$_POST['cableLength'];
    $cameraCount = (int)$_POST['cameraCount'];
    $bncCount = (int)$_POST['bncCount'];
    $tvInstalled = isset($_POST['tvInstalled']) ? 1 : 0;
    $rackInstalled = isset($_POST['rackInstalled']) ? 1 : 0;
    $kmRange = (int)$_POST['kmRange'];
    $extraLabel = $_POST['extraLabel'];
    $extraValue = (float)$_POST['extraValue'];
    $totalCostInput = (float)$_POST['totalCostInput'];
    $name = $_POST['name'];
    $area = $_POST['area'];
    $date = $_POST['date']; // Assuming YYYY-MM-DD format from input

    // Update the query and bindings to include `date`
    $stmt = $conn->prepare("REPLACE INTO install (
        idno, name, area, cable_length, camera_count, bnc_count, tv_installed, rack_installed, km_range,
        extra_label, extra_value, total_cost, date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssiiiiissdds", $idno, $name, $area, $cableLength, $cameraCount, $bncCount,
        $tvInstalled, $rackInstalled, $kmRange, $extraLabel, $extraValue, $totalCostInput, $date);

    $stmt->execute();
}


// Fetch order info
$idno = $_POST['idno'] ?? $_GET['idno'] ?? '';
$data = ['name' => '', 'area' => '', 'quantity' => 1];
if ($idno) {
    $result = $conn->query("SELECT * FROM orders WHERE idno = '$idno'");
    $data = $result->fetch_assoc();
}

$cameraCount = $data['quantity'] ?? 1;


?>
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calculation Form</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <script>
  function generateCode(length = 5) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = '';
    for (let i = 0; i < length; i++) {
      code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
      return code;
  }
function calculateTotal() {
  const cameras = parseInt(document.getElementById('cameraCount').value) || 0;
  const cable = parseFloat(document.getElementById('cableLength').value) || 0;
  const bncCount =  parseFloat(document.getElementById('bncCount').value) || 0;
  const kmRange = document.getElementById('kmRange').value;
  const tv = document.getElementById('tvInstalled').checked;
  const rack = document.getElementById('rackInstalled').checked;
  const extra = parseFloat(document.getElementById('extraValue').value) || 0;

  const bnc = bncCount;
  document.getElementById('bncCount').value = bnc;

  let total = 0;
  
  const threshold = (cable/cameras) > 25  ;

  if (threshold || kmRange === '1' || kmRange === '2') {
    total += 100 * cameras;
  }
  total += cameras * 500;
  total += bnc * 25;
  if (tv) total += 300;
  if (rack) total += 300;
  total += extra;

  document.getElementById('totalCost').innerText = total.toFixed(2);
  document.getElementById('totalCostInput').value = total.toFixed(2);



// Example usage
const randomCode = generateCode();
  
  if (!document.getElementById('idno').value) {
    document.getElementById('idno').value = randomCode;
  }
}
window.onload = calculateTotal; 
  </script>
</head>
<body>
  <button onclick="newShowForm()" id="addButton"> <i class="fas fa-plus"></i></button>


  <div id="formOverlay" style="display:none;">

    <div style="background: white; margin: 50px auto; padding: 20px; padding-bottom:0; border-radius: 8px; width: 95%; max-width: 600px; position: relative; height: fit-content">
      <button onclick="hideForm()" class="formCloseBtn" ><i class="fas fa-times"></i></button>
      <form id="calcForm" onsubmit="return submitFormAjax(event)" >
        
        <input type="hidden" name="submitInstall" value="1" />

        <p>
          <label><strong>Name:</strong></label>
          <input type="text" name="name" id="name" value="<?= htmlspecialchars($data['name']) ?>" required />
          <div style="display:flex; gap: 20px">
            <div  style="width:50%">
              <label><strong>Area:</strong> </label>
              <input type="text" name="area" id="area" value="<?= htmlspecialchars($data['area']) ?>" required />
            </div>
            <div  style="width:50%">
              <label><strong>Date:</strong> </label>
              <input type="date" name="date" id="date" value="<?= htmlspecialchars($data['date']) ?>" required />
            </div>
          </div>

          <div style="display:none">
            <label><strong>IDNO:</strong> </label>
            <input type="text" name="idno" id="idno" value="<?= htmlspecialchars($idno) ?>">
          </div>
          <input type="text" name="username" value="<?= $username ?>" />
          <label>Total Cable Used (in meters 3+1 & CAT6):</label>
          <input type="number" name="cableLength" id="cableLength" value="90" required oninput="calculateTotal()" />
          
          <div style="display:flex; gap: 20px">
            <div  style="width:50%">
              <label>Cameras:</label>
              <select name="cameraCount" id="cameraCount" required onchange="calculateTotal()">
                <?php for ($i = 0; $i <= 32; $i++): ?>
                  <option value="<?= $i ?>" <?= $i == $cameraCount ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div style="width:50%">
              <label>Onw BNC Used:</label>
              <input type="number" name="bncCount" id="bncCount" />
            </div>
          </div>
          <div style="display:flex; gap: 20px">
            <div  style="width:50%">
              <label><input type="checkbox" name="tvInstalled" id="tvInstalled" onclick="calculateTotal()" /> TV Installed</label><br/>
            </div>
            <div  style="width:50%">
              <label><input type="checkbox" name="rackInstalled" id="rackInstalled" onclick="calculateTotal()" /> DVR Rack Installed</label><br/>
            </div>
          </div>
          <label>KMs:</label>
          <select name="kmRange" id="kmRange" onchange="calculateTotal()">
            <option value="0">0–19KM</option>
            <option value="1">20–25KM</option>
            <option value="2">Above 25KM</option>
          </select>

          <label>Extra:</label>
          <input type="text" name="extraLabel" id="extraLabel" placeholder="Label" />
          <input type="number" name="extraValue" id="extraValue" placeholder="Value" oninput="calculateTotal()" />

          <input type="hidden" name="totalCostInput" id="totalCostInput" />
          <h3>Total Cost: ₹<span id="totalCost">0</span></h3>
          <div style="text-align: right">
              <button type="submit" name="submitInstall" class="formSubmitBtn"><i class="fas fa-paper-plane"></i></button>
          </div>
          
      </form>
  </div>
  </div>
<div class="header">
  <h2>Installation Summary</h2>
  Welcome <strong><?= $nameAssign ?></strong>
  <?php if ($role !== 'tech') { ?>
  <select id="userFilter" onchange="fetchInstallations(this.value)">
  <option value="all">All</option>
  <?php } ?>
</select>
</div>
<div id="totals-summary" style="display: flex; gap: 1rem; padding: 10px; font-weight: bold;">
    <div><i class="fas fa-check-circle"></i><br /> Submitted <br /><span id="total-submitted">₹ 0</span></div>
    <div><i class="fas fa-thumbs-up"></i><br /> Approved <br /><span id="total-approved">₹ 0</span></div>
    <div><i class="fas fa-money-bill-wave"></i><br /> Payment <br /><span id="total-paid">₹ 0</span></div>
</div>
  <div id="installList"></div>

  <script>
function fetchInstallations(username = 'all') {
  const url = username && username !== 'all'
    ? `api.php?abc=sadf&user=${encodeURIComponent(username)}`
    : `api.php`;

  fetch(url)
    .then(res => res.json())
    .then(data => {
      loadInstallations(data); // ✅ Only called when data is ready
    })
    .catch(error => console.error("Fetch failed:", error));
}

  function newShowForm() {
    const overlay = document.getElementById('formOverlay');
    const form = document.getElementById('calcForm');

    // Reset form
    form.reset();

    // Clear all fields except idno
    document.getElementById('name').value = '';
    document.getElementById('area').value = '';
    document.getElementById('date').value = '';
    document.getElementById('cableLength').value = '';
    document.getElementById('cameraCount').value = '1';
    document.getElementById('bncCount').value = '';
    document.getElementById('tvInstalled').checked = false;
    document.getElementById('rackInstalled').checked = false;
    document.getElementById('kmRange').value = '0';
    document.getElementById('extraLabel').value = '';
    document.getElementById('extraValue').value = '';
    document.getElementById('totalCost').innerText = '0';
    document.getElementById('totalCostInput').value = '';

    // Set random IDNO
    const randomCode2 = generateCode();
    document.getElementById('idno').value = randomCode2;

    overlay.style.display = 'flex';

    // Disable body scroll
    document.body.style.overflow = 'hidden';
  }

  function showForm() {
    document.getElementById('formOverlay').style.display = 'block';
  }

function hideForm() {
  document.getElementById('formOverlay').style.display = 'none';
  document.body.style.overflow = 'auto';
}

  function submitFormAjax(event) {
      event.preventDefault();
      const form = document.getElementById('calcForm');
      const formData = new FormData(form);

      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        alert("Submitted successfully!");
        
        location.reload(); // reload to see updates
      })
      .catch(error => {
        console.error('Error:', error);
        alert("Submission failed.");
      });

      return false;
    }

    // Keep total cost calculation working
window.onload = function () {
  calculateTotal();


  if (role === 'tech') {
    fetchInstallations(username); // ✅ fetches and loads
  } else {
    fetchInstallations('all');    // ✅ fetches and loads
    loadUsernames();              // ✅ loads user dropdown
  }

  if (document.getElementById("name").value) {
    showForm();
  }
};

    if (document.getElementById("name").value) {
      showForm()
    }

  function updateTotals(data) {
    let totalSubmitted = 0;
    let totalApproved = 0;
    let totalPaid = 0;

    data.forEach(row => {
        const cost = parseFloat(row.total_cost || 0);
        if (row.Submitted === 'Yes') totalSubmitted += cost;
        if (row.Approved === 'Yes') totalApproved += cost;
        if (row.Payment === 'Yes') totalPaid += cost;
    });

    // Update the HTML values
    document.getElementById("total-submitted").innerText = `₹ ${totalSubmitted.toLocaleString()}`;
    document.getElementById("total-approved").innerText = `₹ ${totalApproved.toLocaleString()}`;
    document.getElementById("total-paid").innerText = `₹ ${totalPaid.toLocaleString()}`;
}

  </script>
</body>
</html>
<script>
const username = "<?= $username ?>"; // 👈 Now available to all functions
const role = "<?= $role ?>";
function loadInstallations(data) {
  console.log("Loading installations:", data);  // ✅ this now works

  const container = document.getElementById('installList');
   const distanceLabels = ['0–19KM', '20–25KM', 'Above 25KM'];
    
  container.innerHTML = '';

  if (!Array.isArray(data)) {
    console.warn("Invalid data received:", data);
    return;
  }

  updateTotals(data);

      container.innerHTML = '';
      data.forEach(row => {
        const div = document.createElement('div');
        div.className = 'card';
        div.innerHTML = `
          <div style="display: flex; justify-content: space-between;">
            <span style="font-size: 16px; color: #666;"> ID: ${row.idno}</span>
            <span style="font-size: 16px; color: #666;"> | ${row.username}</span>
            <span style="font-size: 16px; color: #666;"> Date: ${row.date}</span>
            ${row.Submitted !== 'Yes' ? `
              <button class="deleteBtn" onclick="deleteRecord('${row.idno}')" title="Delete">
                <i class="fas fa-trash-alt" style="color:black;"></i>
              </button>` : ''}
          </div>

          <h2 style="margin: 8px 0; font-size: 20px; color: #222;">
            <i class="fas fa-user"></i> ${row.name}
            <small style="font-size: 14px; color: #666;"> | ${row.area}</small>
          </h2>

          <ul style="list-style-type: none; padding: 0; margin: 10px 0; font-size: 15px; line-height: 1.6;">
            <li><strong> Cameras:</strong> ${row.camera_count}</li>
            <li><strong>Cable:</strong> ${row.cable_length} m</li>
            <li><strong> BNC:</strong> ${row.bnc_count}</li>
            <li>
              <strong>TV:</strong> ${row.tv_installed === '1' ? 'Yes' : 'No'} |
              <strong>Rack:</strong> ${row.rack_installed === '1' ? 'Yes' : 'No'}
            </li>
            <li><strong>Extra:</strong> ₹${row.extra_label} - ₹${row.extra_value}</li>
            <li><hr style="border-top: 1px solid #e0e0e0;" /><strong>Distance:</strong> ${distanceLabels[row.km_range]}</li>
            <li style="font-size: 18px; color: #111;"><strong>Total:</strong> ₹${row.total_cost}</li>
          </ul>
          <hr style="border-top: 1px solid #e0e0e0;" />
          ${(row.Submitted !== 'Yes' &&  role !== 'admin') ? `
            <div class="controls">
              <button class="editBtn" onclick="editRecord('${row.idno}')" title="Edit">
                <i class="fas fa-pen"></i>
              </button>
              <button class="submitBtn" onclick="updateCard('${row.idno}', 'submit')" title="Submit">
                <i class="fas fa-paper-plane"></i>
              </button>
            </div>
          ` : `
            <p><strong><i class="fas fa-check-circle ${row.Submitted}"></i> Submitted:</strong> ${row.Submitted}</p>
            <p><strong><i class="fas fa-thumbs-up ${row.Approved}"></i> Approved:</strong> ${row.Approved} |
            <strong><i class="fas fa-money-bill-wave ${row.Payment}"></i> Payment:</strong> ${row.Payment}</p>
          `}

           ${(row.Submitted === 'Yes' &&  role === 'admin') ? `
            <div class="controls">
              ${row.Approved !== 'Yes' ? `
              <button class="approveBtn" onclick="updateCard('${row.idno}', 'approve')" title="Approve">
                <i class="fas fa-thumbs-up"></i>
              </button> `: ``}
               ${(row.Approved === 'Yes' && row.Payment !== 'Yes') ? `
              <button class="payBtn" onclick="updateCard('${row.idno}', 'pay')" title="Pay">
                <i class="fas fa-credit-card"></i>
               </button> `: ``}
            </div>
          ` : ``}


        `;
        container.appendChild(div);
      });
    };



function deleteRecord(idno) {
  fetch('api.php', {
    method: 'DELETE',
    body: new URLSearchParams({ idno })
  })
  .then(() => fetchInstallations(username)) // 🔄 fetch data first, then load
}

function submitCard(idno) {
  fetch('api.php', {
    method: 'PATCH',
    body: new URLSearchParams({ idno })
  })
  .then(() => fetchInstallations(username)) // 🔄 same here
}

function updateCard(idno, action) {
  fetch('api.php', {
    method: 'PATCH',
    body: new URLSearchParams({ idno, action })
  })
  .then(() => fetchInstallations(username)) // 🔄 get fresh data
}


function editRecord(idno) {
  fetch(`api.php?idno=${idno}`)
    .then(res => res.json())
    .then(data => {
      const record = data.find(r => r.idno === idno);
      if (record) {
        showForm();
        document.getElementById('idno').value = record.idno;
        document.getElementById('name').value = record.name;
        document.getElementById('area').value = record.area;
        document.getElementById('date').value = record.date;
        document.getElementById('cableLength').value = record.cable_length;
        document.getElementById('cameraCount').value = record.camera_count;
        document.getElementById('bncCount').value = record.bnc_count;
        document.getElementById('tvInstalled').checked = record.tv_installed == 1;
        document.getElementById('rackInstalled').checked = record.rack_installed == 1;
        document.getElementById('kmRange').value = record.km_range;
        document.getElementById('extraLabel').value = record.extra_label;
        document.getElementById('extraValue').value = record.extra_value;
        document.getElementById('totalCostInput').value = record.total_cost;
        document.getElementById('totalCost').innerText = record.total_cost;
      }
    });
}

function submitFormAjax(event) {
  event.preventDefault();
  const form = document.getElementById('calcForm');
  const formData = new FormData(form);

  fetch('api.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(res => {
    if (res.success) {
      alert('Saved successfully!');
      hideForm();
     fetchInstallations(username);// ✅ instead of loadInstallations()
    }
  });

  return false;
}
function loadUsernames() {
  fetch('api.php?action=user')
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById('userFilter');

      // Add "All" as the first option
      const allOpt = document.createElement('option');
      allOpt.value = '';
      select.appendChild(allOpt);

      data.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.username;       // Use correct key from object
        opt.textContent = item.username;
        select.appendChild(opt);
      });
    });
}




</script>

