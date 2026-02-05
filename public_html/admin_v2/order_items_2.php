<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>CCTV Equipment Form</title>
  <style>
  /* Base styles (mobile-first) */
#material-sandbox {
  font-family: Arial, sans-serif;
  margin: 10px;
  padding: 0;
  font-size: 16px; /* Improved font size for mobile */
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.label-input label {
  display: flex;
  flex-direction: column;
  font-weight: bold;
  margin-bottom: 10px;
}

input[type="number"],
input[type="text"],
select {
  padding: 10px;
  font-size: 16px;
  width: 100%;
  box-sizing: border-box; 
}

button {
  padding: 12px 16px;
  font-size: 16px;
  cursor: pointer;
  margin-top: 10px;
  width: 100%;
  box-sizing: border-box;
}

#equipment-form .tables-wrapper {
  display: flex;
  flex-direction: column;
  gap: 20px;
  height: 70vh;
  overflow: auto;
}

#equipment-form table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
  font-size: 16px;
}

#equipment-form th, #equipment-form td {
  border: 1px solid #999;
  padding: 5px;
  text-align: left;
  white-space: break-spaces;
}

#equipment-form th {
  background-color: #f2f2f2;
}

#copy {
  background: #f9f9f9;
  padding: 15px;
  border: 1px solid #ddd;
  border-radius: 4px;
  width: 100%;
  box-sizing: border-box;
}

#user-details {
  font-weight: bold;
  margin-bottom: 10px;
}

/* Make tables scrollable on very narrow screens */
#equipment-form table {
  display: block;
  overflow-x: auto;
  white-space: nowrap;
}

/* Larger screen styles */
@media (min-width: 768px) {
  .form-section {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  .label-input label {
    flex: 1;
    margin-right: 20px;
  }

  .tables-wrapper {
    flex-direction: row;
    flex-wrap: wrap;
  }

  #equipment-form table {
    width: 48%;
    display: table;
    white-space: normal;
  }

  button {
    width: auto;
  }

  #copy {
    width: 45%;
  }
}


    div#copy table {
    width: unset;
    }
    div#copy {
     display: block;
    background: #f1f1f1;
    width: 90%;
    align-content: center;
    margin: auto;
    padding: 20px;
    }
    tr:nth-child(even) {
            background-color: #fff;
        }
    #mobile-summary-toggle {
        display: none;
    }
        @media (max-width: 767px) {
  #mobile-summary-toggle {
    display: inline;
    position: fixed;
    top: 0;
    right: 10px;
    z-index: 9999;
    background: #333;
    color: #fff;
    border: none;
    padding: 10px 12px;
    font-size: 14px;
    border-radius: 6px;
    cursor: pointer;
    width: 70px;
  }

  #mobile-summary-container {
    position: fixed;
    top: -10px;
    left: 0;
    right: 0;
    background: #f9f9f9;
    padding: 10px;
    z-index: 9998;
    display: none;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
  }

  #mobile-summary-container.show {
    display: block;
  }
}
.hybrid-selected {
  background-color: red !important;
  color: white !important;
}
.hybrid-selected select, .hybrid-selected input {
    color: black !important;
    background-color: white !important;
}
  </style>
</head>
<body>

  <div id="material-sandbox">

  <h2>Material Form</h2>

  <form id="equipment-form">
    <label style="display: block; margin: 10px 0;">
        <input type="checkbox" id="auto-update-toggle" checked>
        Auto update results
    </label>
    <div class="form-section label-input">
      <label>
        Name: <input type="text" id="user-name" required>
      </label>
      <label>
        ID No: <input type="text" id="user-id" required>
      </label>
    </div>

    <div class="tables-wrapper">
      <table>
        <thead>
          <tr>
            <th>Item Name</th>
            <th>Options</th>
            <th>Quantity</th>
          </tr>
        </thead>
        <tbody id="form-body"></tbody>
      </table>
        <!-- Toggle Button for Mobile -->
<button id="mobile-summary-toggle" onclick="toggleMobileSummary()">Show</button>

      <div style="flex: 1;" id="mobile-summary-container">
        <h3 id="result-heading" style="display:none;">Selected Items</h3>
        <div id="copy">
            <div id="user-details" style="display:none;"></div>
            <table id="output-table" style="display:none;">
            <thead>
                <tr>
                <th>Item Name</th>
                <th>Selected Option</th>
                <th>Quantity</th>
                </tr>
            </thead>
            <tbody id="output-body"></tbody>
            </table>
        </div>   
      </div>
    </div>

    <button type="submit">Generate Table</button>
    <button type="button" id="copy-share-btn" style="display:none;">Copy & Share on WhatsApp</button>
  </form>

  <!-- Load html2canvas for screenshot -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

  <script>
    const items = [
      { name: "HIKVISON BULLET", options: ["5 MP Cam Normal with Mic", "5 MP Cam HYBRID with Mic", "2 MP with Mic", "2 MP HYBRID with Mic"], quantityRange: 20, id: "resolution-bullet" },
      { name: "HIKVISON DOME", options: ["5 MP Cam Normal with Mic", "5 MP Cam HYBRID with Mic", "2 MP with Mic", "2 MP HYBRID with Mic"], quantityRange: 20, id: "resolution-dome" },
      { name: "HIKVISON DVR", options: ["2 MP 4 CH", "2 MP 8 CH", "2 MP 16 CH", "2 MP 32 CH", "5 MP 4 CH", "5 MP 8 CH", "5 MP 16 CH", "5 MP 32 CH"], quantityRange: 1, id: "type" },
      { name: "Hard Disk Consistian", options: ["500 GB", "1 TB Seagate (3yrs)", "2 TB Toshiba (3yrs)", "3 TB", "4 TB", "6 TB"], quantityRange: 1, id: "hdd" },
      { name: "SMPS", options: ["4 CH FYBRE", "8 CH FYBRE", "16 CH FYBRE", "32 CH FYBRE"], quantityRange: 1 },
      { name: "DC PIN", options: [], quantityRange: 40 },
      { name: "Back Box", options: [], quantityRange: 40 },
      { name: "BNC NO", options: [], quantityRange: 40 },
      { name: "C Pin bundle", options: [], quantityRange: 5 },
      { name: "Dlink Cable 3+1", options: [], quantityRange: 5 },
      { name: "Dlink Cable Cat 6", options: ["90 mtrs 3+1"], quantityRange: 5 },
      { name: "LED TV", options: ["15 Inches", "19 Inches", "22 Inches", "24 Inches"], quantityRange: 1, id:"monitor" },
      { name: "LED TV Clamp", options: [], quantityRange: 1, id: "clamp" },
      { name: "Dvr rack", options: [], quantityRange: 1, id:"rack" },
      { name: "Sim Router", options: [], quantityRange: 1, id: "sim-router" },
      { name: "Wifi Extender", options: [], quantityRange: 1, id: "wifi-extender" },
      { name: "HDMI Cable", options: [], quantityRange: 1, id: "hdmi" }
    ];

    const formBody = document.getElementById("form-body");

    items.forEach((item, index) => {
      const row = document.createElement("tr");

      const nameCell = document.createElement("td");
      nameCell.textContent = item.name;
      row.appendChild(nameCell);

      const optionCell = document.createElement("td");
      if (item.options.length > 0) {
        const select = document.createElement("select");
        select.name = `option-${index}`;
        select.id = item.id;
        item.options.forEach(opt => {
          const option = document.createElement("option");
          option.value = opt;
          option.textContent = opt;
          select.appendChild(option);
        });
        optionCell.appendChild(select);
      } else {
        const input = document.createElement("input");
        input.type = "text";
        input.id = item.id;
        input.name = `option-${index}`;
        input.placeholder = "N/A";
        optionCell.appendChild(input);
      }
      row.appendChild(optionCell);

      const quantityCell = document.createElement("td");
      const quantityInput = document.createElement("input");
      quantityInput.type = "number";
      quantityInput.min = 0;
      quantityInput.max = item.quantityRange;
      quantityInput.value = 0;
      quantityInput.name = `qty-${index}`;
      quantityInput.id = `${item.id}-qty`;
      
      quantityCell.appendChild(quantityInput);
      row.appendChild(quantityCell);

      formBody.appendChild(row);
    });
    function generateTable(e) {
  e.preventDefault();

  const name = document.getElementById("user-name").value.trim();
  const idNo = document.getElementById("user-id").value.trim();

  const outputBody = document.getElementById("output-body");
  const outputTable = document.getElementById("output-table");
  const userDetails = document.getElementById("user-details");
  
  const shareBtn = document.getElementById("copy-share-btn");

  outputBody.innerHTML = "";
  let hasItems = false;

  items.forEach((item, index) => {
    const qty = parseInt(document.querySelector(`[name=qty-${index}]`).value, 10);
    const optionInput = document.querySelector(`[name=option-${index}]`);
    const option = optionInput.tagName === "SELECT" ? optionInput.value : optionInput.value.trim();

    if (qty > 0) {
      hasItems = true;
      const row = document.createElement("tr");
      row.innerHTML = `<td>${item.name}</td><td>${option || "N/A"}</td><td>${qty}</td>`;
      if (option && option.includes('HYBRID')) {
          row.classList.add('hybrid-selected');
      }
      outputBody.appendChild(row);
    }
  }); // ← Missing closing bracket was here

  if (hasItems) {
    userDetails.textContent = `Name: ${name} | ID No: ${idNo}`;
    userDetails.style.display = "block";

    outputTable.style.display = "table";
    shareBtn.style.display = "inline-block";
  } else {
    userDetails.style.display = "none";

    outputTable.style.display = "none";
    shareBtn.style.display = "none";
  }
}

// Add this outside of the generateTable function
document.getElementById("equipment-form").addEventListener("submit", generateTable);



    document.getElementById("copy-share-btn").addEventListener("click", () => {
  const table = document.querySelector("#copy");

  html2canvas(table).then(canvas => {
    canvas.toBlob(blob => {
      if (navigator.clipboard && navigator.clipboard.write) {
        const item = new ClipboardItem({ 'image/png': blob });
        navigator.clipboard.write([item]).then(() => {
          alert("Image copied to clipboard! Now opening WhatsApp...");
          const waUrl = `https://wa.me/917795953403?text=Please%20find%20the%20CCTV%20equipment%20list%20attached.`;
          window.open(waUrl, '_blank');
        }).catch(err => {
          console.error("Clipboard error:", err);
          alert("Failed to copy image. Try using Chrome over HTTPS.");
        });
      } else {
        alert("Clipboard image copy not supported in this browser.");
      }
    });
  });
});
function updateDependentFields() {
  let totalCameras = 0;
  let cameraType = null;

  items.forEach((item, index) => {
    if (item.name === "HIKVISON BULLET" || item.name === "HIKVISON DOME") {
      const qty = parseInt(document.querySelector(`[name=qty-${index}]`).value || "0", 10);
      const opt = document.querySelector(`[name=option-${index}]`).value;
      totalCameras += qty;

      if (qty > 0 && opt.includes("5 MP")) {
        cameraType = "5 MP";
      } else if (qty > 0 && opt.includes("2 MP")) {
        cameraType = "2 MP";
      }
    }
  });

  // Update DVR
  const dvrIndex = items.findIndex(i => i.name === "HIKVISON DVR");
  const dvrSelect = document.querySelector(`[name=option-${dvrIndex}]`);
  const dvrQty = document.querySelector(`[name=qty-${dvrIndex}]`);
  if (cameraType) {
    const choices = Array.from(dvrSelect.options).map(o => o.value);
    const suitable = choices.find(opt => opt.startsWith(cameraType) && parseInt(opt.split(" ")[2]) >= totalCameras);
    if (suitable) {
      dvrSelect.value = suitable;
      dvrQty.value = 1;
    }
  }

  // Hard Disk
  const hddIndex = items.findIndex(i => i.name.startsWith("Hard Disk"));
  const hddSelect = document.querySelector(`[name=option-${hddIndex}]`);
  const hddQty = document.querySelector(`[name=qty-${hddIndex}]`);
//   if (totalCameras <= 4) hddSelect.value = "500 GB";
//   else if (totalCameras <= 8) hddSelect.value = "1 TB";
//   else if (totalCameras <= 16) hddSelect.value = "2 TB";
//   else if (totalCameras <= 24) hddSelect.value = "3 TB";
//   else hddSelect.value = "4 TB";
  hddQty.value = 1;

  // SMPS = same as DVR
  const smpsIndex = items.findIndex(i => i.name === "SMPS");
  const smpsSelect = document.querySelector(`[name=option-${smpsIndex}]`);
  const smpsQty = document.querySelector(`[name=qty-${smpsIndex}]`);
  if (dvrSelect.value) {
    const ch = dvrSelect.value.split(" ")[2]; // get CH number
    const smpsMatch = Array.from(smpsSelect.options).find(o => o.value.includes(ch));
    if (smpsMatch) {
      smpsSelect.value = smpsMatch.value;
      smpsQty.value = 1;
    }
  }

  // Accessory values
  const setQty = (label, val) => {
    const i = items.findIndex(x => x.name === label);
    if (i >= 0) document.querySelector(`[name=qty-${i}]`).value = val;
  };
  setQty("DC PIN", totalCameras);
  setQty("Back Box", totalCameras);
  setQty("BNC NO", totalCameras * 2);
  setQty("C Pin bundle", 1);

  // Dlink Cable
  const cable3Index = items.findIndex(x => x.name === "Dlink Cable 3+1");
  if (cable3Index >= 0)
    document.querySelector(`[name=qty-${cable3Index}]`).value = totalCameras <= 4 ? 1 : 2;

    const ledTVIndex = items.findIndex(i => i.name === "LED TV");
    const ledTVSelect = document.querySelector(`[name=option-${ledTVIndex}]`);
    const ledTVQty = document.querySelector(`[name=qty-${ledTVIndex}]`);
    const clampIndex = items.findIndex(i => i.name === "LED TV Clamp");
    const clampQty = document.querySelector(`[name=qty-${clampIndex}]`);

    // Only set clamp = 1 if user selected a value AND quantity > 0 for LED TV
    if (ledTVSelect.value && parseInt(ledTVQty.value) > 0) {
    clampQty.value = 1;
    } else {
    clampQty.value = 0;
    }
    
}
document.querySelectorAll("input, select").forEach(el => {
  el.addEventListener("change", () => {
    const autoUpdate = document.getElementById("auto-update-toggle").checked;
    if (autoUpdate) {
      updateDependentFields();
     
    }
    generateTable({ preventDefault: () => {} });
  });
});
  </script>
 
</div>
</body>
</html>
 <script>
  window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    
    const resolution = params.get('resolution'); // e.g., "2 MP", "5 MP", "4K"

    const bullets = parseInt(params.get('bullets') || "0", 10);
    const domes = parseInt(params.get('dome') || "0", 10);

    const monitor = params.get('monitor');
    const rack = params.get('rack');
    const name = params.get('name');
    const id = params.get('id');
    const location = params.get('location');

    if(name)
        document.getElementById('user-name').value = name;
    if(id)
        document.getElementById('user-id').value = `${id} | ${location}`;
    

    // Function to auto-select option that starts with the resolution text
      function selectOptionStartingWith(selectElement, matchText) {
        const options = selectElement?.options;
        if (!options) return;

        for (let i = 0; i < options.length; i++) {
          const optionText = options[i].text.trim();
          if (optionText.startsWith(matchText)) {
            selectElement.selectedIndex = i;
            break;
          }
        }
      }

    if (resolution) {
      const bulletSelect = document.getElementById('resolution-bullet');
      const domeSelect = document.getElementById('resolution-dome');

      selectOptionStartingWith(bulletSelect, resolution);
      selectOptionStartingWith(domeSelect, resolution);

      // Set bullet and dome quantities if present
      const bulletQty = document.getElementById('resolution-bullet-qty');
      const domeQty = document.getElementById('resolution-dome-qty');
      if (bulletQty) bulletQty.value = bullets;
      if (domeQty) domeQty.value = domes;
    }

    if (monitor) {
        const monitorSelect = document.getElementById('monitor');
        const monitorQty = document.getElementById('monitor-qty');
  
         selectOptionStartingWith(monitorSelect, monitor);
         monitorQty.value=1;
         

    }

    if (rack) {
        
        const rackQty = document.getElementById('rack-qty');
        rackQty.value=1;
         
    }
    // Handle 'hdd' param for Hard Disk dropdown
    let hdd = params.get('hdd');
    if (hdd) {
        // Normalize incoming value (remove spaces, lowercase)
        const target = hdd.replace(/\s+/g, '').toLowerCase();

        const hddSelect = document.querySelector('#material-content #hdd') || document.getElementById('hdd');
        if (hddSelect) {
          for (let i = 0; i < hddSelect.options.length; i++) {
            const opt = hddSelect.options[i];
            const optTextNorm = (opt.text || '').replace(/\s+/g, '').toLowerCase();
            const optValNorm = (opt.value || '').replace(/\s+/g, '').toLowerCase();
            if (optTextNorm === target || optValNorm === target || optTextNorm.startsWith(target) || optValNorm.startsWith(target)) {
              hddSelect.selectedIndex = i;
              // Also set HDD quantity to 1 when matched
              const hddQty = document.querySelector('#material-content #hdd-qty') || document.getElementById('hdd-qty');
              if (hddQty) hddQty.value = 1;
              break;
            }
          }
        }
      }
    }

  });
      function toggleMobileSummary() {
  const container = document.getElementById('mobile-summary-container');
  const summaryToggle = document.getElementById('mobile-summary-toggle');
  container.classList.toggle('show');
  summaryToggle.textContent = container.classList.contains('show') ? 'Close' : 'Show';
    generateTable({ preventDefault: () => {} });
 updateDependentFields();
}
</script>