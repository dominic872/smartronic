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
    const CAMERA_RESOLUTION_OPTIONS = ["2 MP", "5 MP"];
    const CAMERA_TYPE_OPTIONS = ["Normal with mic", "Hybrid", "Full colour"];
    let activeMaterialBrand = '';

    function normalizeCamType(value) {
      const normalized = String(value || '').trim().toLowerCase();
      if (!normalized) return '';
      if (normalized === 'hybrid' || normalized.includes('hybrid')) return 'Hybrid';
      if (normalized === 'full colour' || normalized === 'full color' || normalized.includes('full colour') || normalized.includes('full color')) return 'Full colour';
      return 'Normal with mic';
    }

    function getCameraOptionLabel(resolutionValue, camTypeValue) {
      const resolution = String(resolutionValue || '').trim();
      const camType = normalizeCamType(camTypeValue);
      if (!resolution && !camType) return 'N/A';
      if (!resolution) return camType || 'N/A';
      if (!camType) return resolution;
      return `${resolution} ${camType}`;
    }

    function getCameraFieldIds(item) {
      return {
        resolution: item.id,
        camType: `${item.id}-cam-type`
      };
    }

    function getCameraSelection(item) {
      const fieldIds = getCameraFieldIds(item);
      const resolutionField = document.getElementById(fieldIds.resolution);
      const camTypeField = document.getElementById(fieldIds.camType);
      return {
        resolution: resolutionField ? resolutionField.value : '',
        camType: camTypeField ? camTypeField.value : ''
      };
    }

    function setCameraSelection(item, resolutionValue, camTypeValue) {
      const fieldIds = getCameraFieldIds(item);
      const resolutionField = document.getElementById(fieldIds.resolution);
      const camTypeField = document.getElementById(fieldIds.camType);
      if (resolutionField && resolutionValue) {
        resolutionField.value = resolutionValue;
      }
      if (camTypeField && camTypeValue) {
        camTypeField.value = normalizeCamType(camTypeValue);
      }
    }

    function formatMaterialINR(value) {
      const amount = Number(value);
      if (!Number.isFinite(amount)) return 'n/a';
      return `₹${Math.round(amount).toLocaleString('en-IN')}`;
    }

    function getInstallPricingInputFromForm() {
      const form = document.getElementById('installForm');
      const getVal = id => {
        const el = form ? form.querySelector(`#${id}`) : null;
        return el ? (el.value || '') : '';
      };
      return {
        id: getVal('id'),
        name: getVal('name'),
        cams: getVal('cams'),
        bullets: getVal('bullets'),
        dome: getVal('dome'),
        hdd: getVal('hdd'),
        type: getVal('type'),
        resolution: getVal('resolution'),
        brand: getVal('brand'),
        cam_type: getVal('cam_type'),
        monitor: getVal('monitor'),
        rack: getVal('rack')
      };
    }

    async function updateMaterialCostRowFromDataJson() {
      const copyBox = document.getElementById('copy');
      const outputTable = document.getElementById('output-table');
      if (!copyBox || !outputTable || typeof window.calculateInstallPricing !== 'function') return;

      let row = document.getElementById('material-cost-row');
      if (!row) {
        row = document.createElement('div');
        row.id = 'material-cost-row';
        row.style.cssText = 'margin-top:10px;padding-top:8px;border-top:1px solid #ddd;display:none;justify-content:space-between;gap:12px;font-weight:700;';
        row.innerHTML = `
          <span>Material Cost</span>
          <span id="material-cost-amount">Calculating...</span>
        `;
        outputTable.insertAdjacentElement('afterend', row);
      }

      if (outputTable.style.display === 'none') {
        row.style.display = 'none';
        return;
      }

      row.style.display = 'flex';
      const amountEl = row.querySelector('#material-cost-amount');
      if (amountEl) amountEl.textContent = 'Calculating...';

      try {
        const pricing = await window.calculateInstallPricing(getInstallPricingInputFromForm());
        row.dataset.materialCost = String(pricing.materialCost || 0);
        row.dataset.priceSource = pricing.source || 'data.json';
        if (amountEl) amountEl.textContent = formatMaterialINR(pricing.materialCost);

        const profitContent = document.getElementById('profit-content');
        if (profitContent) {
          profitContent.dataset.materialCost = String(pricing.materialCost || 0);
          profitContent.dataset.priceSource = pricing.source || 'data.json';
        }
      } catch (err) {
        console.warn('Unable to update material-cost-row from data.json:', err);
        row.dataset.materialCost = '';
        if (amountEl) amountEl.textContent = 'n/a';
      }
    }

    const brandLabels = {
      'CP PLUS': 'CP PLUS',
      'Hikvision': 'HIKVISON',
      'PRAMA': 'PRAMA',
      'SECUREYE': 'SECUREYE'
    };
    const recorderOptionsByType = {
      DVR: ["2 MP 4 CH", "2 MP 8 CH", "2 MP 16 CH", "2 MP 32 CH", "5 MP 4 CH", "5 MP 8 CH", "5 MP 16 CH", "5 MP 32 CH"],
      NVR: ["4 CH", "8 CH", "16 CH", "32 CH"]
    };

    function getMaterialSystemType() {
      const params = new URLSearchParams(window.location.search);
      const paramType = String(params.get('type') || '').trim().toUpperCase();
      if (paramType.includes('NVR')) return 'NVR';
      const typeField = document.getElementById('type');
      const value = String(typeField ? typeField.value : '').trim().toUpperCase();
      return value.includes('NVR') ? 'NVR' : 'DVR';
    }

    function buildBrandItems(brand, systemType) {
      const label = brandLabels[brand] || 'SECUREYE';
      const recorderType = systemType === 'NVR' ? 'NVR' : 'DVR';
      return [
        { name: `${label} BULLET`, options: [], quantityRange: 20, id: "resolution-bullet", kind: "camera" },
        { name: `${label} DOME`, options: [], quantityRange: 20, id: "resolution-dome", kind: "camera" },
        { name: `${label} ${recorderType}`, options: recorderOptionsByType[recorderType], quantityRange: 1, id: "type" }
      ];
    }

    function getCommonItems(systemType) {
      const recorderRackName = systemType === 'NVR' ? 'Nvr rack' : 'Dvr rack';
      const transportItems = systemType === 'NVR'
        ? [
            { name: "POE", options: ["4 CH POE", "8 CH POE", "16 CH POE", "32 CH POE"], quantityRange: 1 },
            { name: "Back Box", options: [], quantityRange: 40 },
            { name: "Dlink Cable Cat 6", options: ["90 mtrs Cat 6"], quantityRange: 5 }
          ]
        : [
            { name: "SMPS", options: ["4 CH FYBRE", "8 CH FYBRE", "16 CH FYBRE", "32 CH FYBRE"], quantityRange: 1 },
            { name: "DC PIN", options: [], quantityRange: 40 },
            { name: "Back Box", options: [], quantityRange: 40 },
            { name: "BNC NO", options: [], quantityRange: 40 },
            { name: "C Pin bundle", options: [], quantityRange: 5 },
            { name: "Dlink Cable 3+1", options: [], quantityRange: 5 }
          ];
      return [
        { name: "Hard Disk Consistian", options: ["500 GB", "1 TB Seagate (3yrs)", "1 TB Consistent (2yrs)", "2 TB Toshiba (3yrs)", "2 TB Consistent (2yrs)", "3 TB", "4 TB", "6 TB"], quantityRange: 1, id: "hdd" },
        ...transportItems,
        { name: "LED TV", options: ["15 Inches", "19 Inches", "22 Inches", "24 Inches"], quantityRange: 1, id:"monitor" },
        { name: "LED TV Clamp", options: [], quantityRange: 1, id: "clamp" },
        { name: recorderRackName, options: [], quantityRange: 1, id:"rack" },
        { name: "Sim Router", options: [], quantityRange: 1, id: "sim-router" },
        { name: "Wifi Extender", options: [], quantityRange: 1, id: "wifi-extender" },
        { name: "HDMI Cable", options: [], quantityRange: 1, id: "hdmi" }
      ];
    }

    let items = [];

    function setMaterialBrand(brand) {
      activeMaterialBrand = brand && brandLabels[brand] ? brand : '';
      updateItems();
    }

    // Function to update items based on brand selection
    function updateItems() {
      const brandField = document.getElementById('brand');
      const brand = activeMaterialBrand || (brandField ? brandField.value : '');
      const systemType = getMaterialSystemType();
      
      // Get values from requirements tab
      const reqBrand = document.getElementById('brand') ? document.getElementById('brand').value : '';
      const reqCamType = document.getElementById('cam_type') ? document.getElementById('cam_type').value : '';
      
      // Set defaults if no brand or cam_type selected in requirements
      const selectedBrand = brand || reqBrand || 'SECUREYE';
      const selectedCamType = reqCamType || 'Normal with mic';
      
      items = [];
      
      if (selectedBrand && brandLabels[selectedBrand]) {
        items = [...buildBrandItems(selectedBrand, systemType), ...getCommonItems(systemType)];
      } else {
        items = getCommonItems(systemType);
      }
      
      regenerateForm();
      
      // Set default camera type selections after form regeneration
      setTimeout(() => {
        const bulletCamType = document.getElementById('resolution-bullet-cam-type');
        const domeCamType = document.getElementById('resolution-dome-cam-type');
        
        if (bulletCamType) {
          selectOptionByText(bulletCamType, selectedCamType);
        }
        if (domeCamType) {
          selectOptionByText(domeCamType, selectedCamType);
        }
      }, 100);
    }

    // Helper function to select option by text
    function selectOptionByText(selectElement, text) {
      if (!selectElement) return;
      for (let i = 0; i < selectElement.options.length; i++) {
        if (selectElement.options[i].textContent === text || selectElement.options[i].value === text) {
          selectElement.selectedIndex = i;
          break;
        }
      }
    }

    // Function to regenerate the form
    function regenerateForm() {
      const formBody = document.getElementById("form-body");
      formBody.innerHTML = ''; // Clear existing content

      items.forEach((item, index) => {
      const row = document.createElement("tr");

      const nameCell = document.createElement("td");
      nameCell.textContent = item.name;
      row.appendChild(nameCell);

      const optionCell = document.createElement("td");
      if (item.kind === "camera") {
        const cameraSection = document.createElement("div");
        cameraSection.style.display = "flex";
        cameraSection.style.flexDirection = "column";

        // Create container for side-by-side resolution and camera type
        const selectsContainer = document.createElement("div");
        selectsContainer.style.display = "flex";
        selectsContainer.style.gap = "8px";
        selectsContainer.style.marginBottom = "8px";
        
        // Resolution section
        const resolutionSection = document.createElement("div");
        resolutionSection.style.flex = "0 0 25%";
        
        const resolutionLabel = document.createElement("div");
        resolutionLabel.style.fontSize = "12px";
        resolutionLabel.style.marginBottom = "4px";
        resolutionLabel.textContent = "Resolution:";
        resolutionSection.appendChild(resolutionLabel);
        
        const resolutionSelect = document.createElement("select");
        resolutionSelect.style.width = "100%";
        const fieldIds = getCameraFieldIds(item);
        resolutionSelect.name = `option-${index}`;
        resolutionSelect.id = fieldIds.resolution;
        CAMERA_RESOLUTION_OPTIONS.forEach(opt => {
          const option = document.createElement("option");
          option.value = opt;
          option.textContent = opt;
          resolutionSelect.appendChild(option);
        });
        resolutionSection.appendChild(resolutionSelect);
        
        // Camera type section
        const camTypeSection = document.createElement("div");
        camTypeSection.style.flex = "0 0 75%";
        
        const camTypeLabel = document.createElement("div");
        camTypeLabel.style.fontSize = "12px";
        camTypeLabel.style.marginBottom = "4px";
        camTypeLabel.textContent = "Camera Type:";
        camTypeSection.appendChild(camTypeLabel);
        
        const camTypeSelect = document.createElement("select");
        camTypeSelect.style.width = "100%";
        camTypeSelect.name = `cam-type-${index}`;
        camTypeSelect.id = fieldIds.camType;
        CAMERA_TYPE_OPTIONS.forEach(opt => {
          const option = document.createElement("option");
          option.value = opt;
          option.textContent = opt;
          camTypeSelect.appendChild(option);
        });
        camTypeSection.appendChild(camTypeSelect);
        
        selectsContainer.appendChild(resolutionSection);
        selectsContainer.appendChild(camTypeSection);
        cameraSection.appendChild(selectsContainer);
        optionCell.appendChild(cameraSection);
      } else if (item.options.length > 0) {
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
    }

    // Add event listener for brand change when that field is present
    const brandField = document.getElementById('brand');
    if (brandField) {
      brandField.addEventListener('change', updateItems);
    }

    // Initialize with common items
    updateItems();
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
		    const qtyField = document.querySelector(`[name=qty-${index}]`);
		    const qty = parseInt((qtyField && qtyField.value) || "0", 10);
		    let option = "N/A";
		    if (item.kind === "camera") {
		      const selection = getCameraSelection(item);
		      option = getCameraOptionLabel(selection.resolution, selection.camType);
		    } else {
	      const optionInput = document.querySelector(`[name=option-${index}]`);
	      option = optionInput
	        ? (optionInput.tagName === "SELECT" ? optionInput.value : optionInput.value.trim())
	        : "N/A";
	    }

	    if (qty > 0) {
	      hasItems = true;
      const row = document.createElement("tr");
      row.innerHTML = `<td>${item.name}</td><td>${option || "N/A"}</td><td>${qty}</td>`;
	      if (option && option.toLowerCase().includes('hybrid')) {
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

  updateMaterialCostRowFromDataJson();
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
  const systemType = getMaterialSystemType();
  const isNvr = systemType === 'NVR';
  const getChannels = (value) => {
    const match = String(value || '').match(/(\d+)\s*CH/i);
    return match ? parseInt(match[1], 10) || 0 : 0;
  };

	  items.forEach((item, index) => {
	    if (item.name.includes("BULLET") || item.name.includes("DOME")) {
		      const qtyField = document.querySelector(`[name=qty-${index}]`);
		      const qty = parseInt((qtyField && qtyField.value) || "0", 10);
		      const selection = getCameraSelection(item);
		      const opt = selection.resolution;
		      totalCameras += qty;

	      if (qty > 0 && opt.includes("5 MP")) {
        cameraType = "5 MP";
      } else if (qty > 0 && opt.includes("2 MP")) {
        cameraType = "2 MP";
      }
    }
  });

  // Update recorder
	  const dvrIndex = items.findIndex(i => i.name.includes("DVR") || i.name.includes("NVR"));
	  const dvrSelect = document.querySelector(`[name=option-${dvrIndex}]`);
	  const dvrQty = document.querySelector(`[name=qty-${dvrIndex}]`);
	  if ((cameraType || isNvr) && dvrSelect && dvrQty) {
	    const choices = Array.from(dvrSelect.options).map(o => o.value);
	    const suitable = choices.find(opt => (isNvr || opt.startsWith(cameraType)) && getChannels(opt) >= totalCameras);
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
	  if (hddQty) hddQty.value = 1;

	  // SMPS/POE = same channel size as recorder
	  const smpsIndex = items.findIndex(i => i.name === "SMPS");
	  const smpsSelect = document.querySelector(`[name=option-${smpsIndex}]`);
	  const smpsQty = document.querySelector(`[name=qty-${smpsIndex}]`);
	  if (dvrSelect && smpsSelect && smpsQty && dvrSelect.value) {
	    const ch = String(getChannels(dvrSelect.value)); // get CH number
	    const smpsMatch = Array.from(smpsSelect.options).find(o => o.value.includes(ch));
	    if (smpsMatch) {
      smpsSelect.value = smpsMatch.value;
      smpsQty.value = 1;
    }
  }
	  const poeIndex = items.findIndex(i => i.name === "POE");
	  const poeSelect = document.querySelector(`[name=option-${poeIndex}]`);
	  const poeQty = document.querySelector(`[name=qty-${poeIndex}]`);
	  if (dvrSelect && poeSelect && poeQty && dvrSelect.value) {
	    const ch = String(getChannels(dvrSelect.value));
	    const poeMatch = Array.from(poeSelect.options).find(o => o.value.includes(ch));
	    if (poeMatch) {
      poeSelect.value = poeMatch.value;
      poeQty.value = 1;
    }
  }

	  // Accessory values
	  const setQty = (label, val) => {
	    const i = items.findIndex(x => x.name === label);
	    const field = i >= 0 ? document.querySelector(`[name=qty-${i}]`) : null;
	    if (field) field.value = val;
	  };
  setQty("DC PIN", totalCameras);
  setQty("Back Box", totalCameras);
  setQty("BNC NO", totalCameras * 2);
  setQty("C Pin bundle", 1);

	  // Dlink Cable
	  const cable3Index = items.findIndex(x => x.name === "Dlink Cable 3+1");
	  if (cable3Index >= 0) {
	    const cable3Qty = document.querySelector(`[name=qty-${cable3Index}]`);
	    if (cable3Qty) cable3Qty.value = totalCameras <= 4 ? 1 : 2;
	  }
	  const cable6Index = items.findIndex(x => x.name === "Dlink Cable Cat 6");
	  if (cable6Index >= 0) {
	    const cable6Qty = document.querySelector(`[name=qty-${cable6Index}]`);
	    const cable6Select = document.querySelector(`[name=option-${cable6Index}]`);
	    if (cable6Select && cable6Select.options.length) cable6Select.selectedIndex = 0;
	    if (cable6Qty) cable6Qty.value = totalCameras <= 4 ? 1 : 2;
	  }

	    const ledTVIndex = items.findIndex(i => i.name === "LED TV");
	    const ledTVSelect = document.querySelector(`[name=option-${ledTVIndex}]`);
	    const ledTVQty = document.querySelector(`[name=qty-${ledTVIndex}]`);
	    const clampIndex = items.findIndex(i => i.name === "LED TV Clamp");
	    const clampQty = document.querySelector(`[name=qty-${clampIndex}]`);

	    // Only set clamp = 1 if user selected a value AND quantity > 0 for LED TV
	    if (clampQty) {
	      if (ledTVSelect && ledTVQty && ledTVSelect.value && parseInt(ledTVQty.value || "0", 10) > 0) {
	        clampQty.value = 1;
	      } else {
	        clampQty.value = 0;
	      }
	    }
    
}
	document.getElementById("equipment-form").addEventListener("change", (event) => {
	  if (!event.target.matches("input, select")) return;
	  const autoUpdate = document.getElementById("auto-update-toggle").checked;
	  if (autoUpdate) {
	    updateDependentFields();
	  }
	  generateTable({ preventDefault: () => {} });
	});

	window.setMaterialBrand = setMaterialBrand;
  </script>
 
</div>
</body>
</html>
 <script>
  window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    
    const resolution = params.get('resolution'); // e.g., "2 MP", "5 MP", "4K"
    const camType = params.get('cam_type');
    const brand = params.get('brand');

    const bullets = parseInt(params.get('bullets') || "0", 10);
    const domes = parseInt(params.get('dome') || "0", 10);

    const monitor = params.get('monitor');
    const rack = params.get('rack');
    const name = params.get('name');
    const id = params.get('id');
    const location = params.get('location');

    if (brand) {
        setMaterialBrand(brand);
    }

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
      setCameraSelection({ id: 'resolution-bullet' }, resolution, camType);
      setCameraSelection({ id: 'resolution-dome' }, resolution, camType);

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
