document.body.style.background = "linear-gradient(35deg, #00d1a4, #070080)";
   var videoRecoder = "dvr";
function updateDVRSelection() {
  console.log("###");
  let cameraElement = document.getElementById("camera");
  let numCameras = parseInt(document.getElementById("numCameras").value);
  let dvrElement = document.getElementById("dvr");
  let smpsElement = document.getElementById("smps");
  let poeElement = document.getElementById("poe");
  let manual = document.getElementById("manual").checked;

  let cameraType =
    cameraElement.options[cameraElement.selectedIndex].getAttribute(
      "data-type"
    );
 
   poeElement.style.display = "none";
   smpsElement.style.display = "block"
  videoRecoder = "DVR";


  console.log("manual", manual);
  let dvrOptions = "";
  if (cameraType === "5 mp" && manual === false) {
    if (numCameras <= 4)
      dvrOptions += '<option value="2780">DVR 5 MP (4 CH) - ₹2780</option>';
    if (numCameras > 4 && numCameras <= 8)
      dvrOptions += '<option value="4500">DVR 5 MP (8 CH) - ₹4500</option>';
    if (numCameras > 8 && numCameras <= 16)
      dvrOptions += '<option value="10000">DVR 5 MP (16 CH) - ₹10000</option>';
    if (numCameras > 16 && numCameras <= 32)
      dvrOptions += '<option value="21500">DVR 5 MP (32 CH) - ₹21500</option>';
  }
   else if (cameraType === "5 mp" && manual === true) {
   
        dvrOptions += '<option value="2780">DVR 5 MP (4 CH) - ₹2780</option>';
 
        dvrOptions += '<option value="4500">DVR 5 MP (8 CH) - ₹4500</option>';

        dvrOptions += '<option value="10000">DVR 5 MP (16 CH) - ₹10000</option>';

        dvrOptions += '<option value="21500">DVR 5 MP (32 CH) - ₹21500</option>';
  } else if (cameraType === "2 mp" && manual === false)  {
    if (numCameras <= 4 )
      dvrOptions += '<option value="1950">DVR 2 MP (4 CH) - ₹1950</option>';
    if (numCameras > 4 && numCameras <= 8)
      dvrOptions += '<option value="2850">DVR 2 MP (8 CH) - ₹2850</option>';
    if (numCameras > 8 && numCameras <= 16)
      dvrOptions += '<option value="4950">DVR 2 MP (16 CH) - ₹4950</option>';
    if (numCameras > 16 && numCameras <= 32)
      dvrOptions += '<option value="17750">DVR 2 MP (32 CH) - ₹17750</option>';
  }   else if (cameraType === "2 mp" && manual === true) {

    
      dvrOptions += '<option value="1950">DVR 2 MP (4 CH) - ₹1950</option>';
   
      dvrOptions += '<option value="2850">DVR 2 MP (8 CH) - ₹2850</option>';
  
      dvrOptions += '<option value="4950">DVR 2 MP (16 CH) - ₹4950</option>';
   
      dvrOptions += '<option value="17750">DVR 2 MP (32 CH) - ₹17750</option>';
      document.body.style.background = "linear-gradient(35deg, #00d1a4, #070080)";

  }

     else if ((cameraType === "2 mp NVR" || cameraType === "4 mp NVR" || cameraType === "6 mp NVR") && manual === true) {
      videoRecoder = "NVR";
    
      dvrOptions += '<option value="2850">NVR 4 CH - ₹2850</option>';
   
      dvrOptions += '<option value="3390">NVR 8 CH - ₹3390</option>';
  
      dvrOptions += '<option value="4950">NVR 16 CH - ₹4950</option>';
   
      dvrOptions += '<option value="11050">NVR 32 CH - ₹11050</option>';

      


      poeElement.style.display = "block";
      smpsElement.style.display = "none"

      poeElement.value = calculatePoEsWithPrice(numCameras).totalPrice;
      

  }

  dvrElement.innerHTML =
    dvrOptions || '<option value="0">No valid DVR available</option>';

  if (numCameras > 8) {
    smpsElement.value = "750";
  } else {
    smpsElement.value = "400";
  }

  calculateTotal(); // Ensure calculation updates after DVR selection changes
}

function calculateTotal() {

  let cameraElement = document.getElementById("camera");
  let cameraType = cameraElement.options[cameraElement.selectedIndex]
    .getAttribute("data-type")
    .toUpperCase();
  let hardDisk = parseInt(document.getElementById("hardDisk").value);
  let camera = parseInt(document.getElementById("camera").value);
  let numCameras = parseInt(document.getElementById("numCameras").value);
  let dvr = parseInt(document.getElementById("dvr").value) || 0;
  let smps = parseInt(document.getElementById("smps").value);
  let poe = parseInt(document.getElementById("poe").value);
  let cable = parseInt(document.getElementById("cable").value);
  let profitMargin =
    parseFloat(document.getElementById("profitMargin").value) / 100;
  if(videoRecoder === 'DVR'){
    poe = 0;
  } else {
    smps = 0
  }


  let cameraTotal = camera * numCameras;
  let bncBoxDC = (numCameras * 20 * 2) + (numCameras * 20) + (numCameras * 20) + cable;
  let subtotal = hardDisk + cameraTotal + dvr + smps + bncBoxDC + poe;
  let installationCost = 3000; //numCameras <= 3 ? 1800 : numCameras  * 600;
  let gst = subtotal * 0.18;
  let subtotalWithGST = subtotal + gst;
  let totalWithInstallation = subtotalWithGST + 3000;
  let profit = totalWithInstallation * profitMargin;
  let totalBeforeDiscount = totalWithInstallation + profit;
  let discount = totalBeforeDiscount * 0.2;
  let finalTotal = totalBeforeDiscount - discount;
  const perCamValue = Number(perCam.value) || 0; // Ensure it's a number
  const profitValue = Number(limitedProfit.value) || 0; // Ensure it's a number
  const fixCamValue = Number((numCameras * perCamValue <= 1499)? 1500 : numCameras * perCamValue);
  const finalLimitedProfit =
    Number(subtotalWithGST) + fixCamValue + profitValue;

  document.getElementById("breakdown").innerHTML = `
        <h3>Breakdown:</h3>
        <p>Hard Disk: ₹${fic(hardDisk)}</p>
        <p>Camera (${numCameras} pcs): ₹${fic(cameraTotal)}</p>
        <p>DVR: ₹${fic(dvr)}</p>
        <p>SMPS: ₹${fic(smps)}</p>
        <p>BNC x ${numCameras} + Box x ${numCameras} +  ${numCameras} DC: ₹${bncBoxDC} + 1 90 mtr Cable: ₹${cable}</p>
        <p><strong>Subtotal: ₹${fic(subtotal)}</strong></p>
        <p>GST (18%): ₹${fic(gst.toFixed(2))}</p>
        <p class="border-box"><strong>Pay for CCTV + GST: ₹${fic(
          subtotalWithGST.toFixed(0)
        )}.00</strong> </p>
        <p>Installation: ₹${fic(
          installationCost.toFixed(0)
        )}</p>
        <p><strong>Approx. Expense with Install: ₹${fic(
          totalWithInstallation.toFixed(0)
        )}.00</strong></p>
        <p>Profit (${fic((profitMargin * 100).toFixed(0))}%): ₹${fic(
    profit.toFixed(0)
  )}.00</p>
        <p class="from-cust"><strong>Total Before Discount: ₹${fic(
          totalBeforeDiscount.toFixed(0)
        )}.00</strong></p>
        <p>Discount (20%): -₹${fic(discount.toFixed(0))}.00</p>
        <h3 class="from-cust-disc">Final Total Cost: ₹${fic(
          finalTotal.toFixed(0) 
        )}.00 / ₹${fic(finalLimitedProfit.toFixed(0))}.00 <span style="color: green">(₹${fic((finalLimitedProfit+finalLimitedProfit*0.15).toFixed(0))}.00 Diff: ₹${fic(finalLimitedProfit*0.15 )}.00) </span></h3>

        ${fic(finalTotal.toFixed(0) - finalLimitedProfit.toFixed(0))}.00
        

        <div class="proffit border-box smart"> 
          <strong>Final Profit: ₹ ${fic(finalTotal.toFixed(0) - subtotalWithGST.toFixed(0) - fixCamValue.toFixed(0))}.00</strong> 
          <br />
          <span class="proffit-breakup">
          ₹ ${fic(
            finalTotal.toFixed(0) - subtotalWithGST.toFixed(0)
          )}.00 - ${fic(fixCamValue.toFixed(0))}.00 </span>
        </div>
        
        <div class="proffit border-box">
            <strong>Base Limited Profit: ₹ ${fic(finalLimitedProfit.toFixed(0))}.00</strong>
              <div class="proffit-breakup">  ₹${fic(subtotalWithGST.toFixed(0))}.00 + installation <span class="${(fixCamValue <= 1499)? 'strick' :'0'}">${numCameras} x ${perCamValue.toFixed(0)}.00</span> (${fixCamValue}) + ${fic(profitValue.toFixed(0))}.00</div>
        `;

    const getChannel = n => {
      const ch = [4, 8, 16, 32].find(c => n <= c);
      if (!ch) throw new Error("Number of cameras exceeds supported channels");
      return ch;
    };
  let idno = document.getElementById("idno").value;
  let profitMargin2 = document.getElementById("profitMargin").value;
 
  let message =
    `*HIKVISION ${videoRecoder} Full HD ${getChannel(numCameras)}-Channel* | ${cameraType} X ${numCameras} CAM | *After 20% Discount: ₹${fic(
      finalTotal.toFixed(0)
    )}.00*\n\n` +
    `- ${cameraType} Outdoor/ Indoor Cameras (${numCameras} units) – Auto Night Vision, Motion Detection\n` +
    `- TOSHIBA/ Seagate/ WD/ Consistent Hard Disk ${rac(
      document.getElementById("hardDisk").options[
        document.getElementById("hardDisk").selectedIndex
      ].text,
      "-"
    )} - Included\n` +
    `- Cabling & Accessories (BNC, DC, CAM Box, POE/SMPS) – Included\n` +
    `- Installation Services – ₹4,500 (Now Free!)\n` +
    `- Up to 90 meters of cable is provided at no additional cost. Any extra cable required will be charged at ₹20 per meter + labour ₹20 per meter. \n` +
    `- Casing or piping is not included in the standard scope. However, if needed, it can be provided at ₹20 per meter, or ₹40 per meter including materials.\n` +
    `- Kindly provide a ladder for the technician or bear the rental cost if one is required.\n\n` +
    `Total Cost: ₹${fic(totalBeforeDiscount.toFixed(0))}.00\n` +
    `——————————————\n` +
    `*After 20% Discount: ₹${fic(finalTotal.toFixed(0))}.00*\n\n\n\n` +
    `REF#${idno}|SPM${profitMargin2}P`;
  let name = document.getElementById("name").value;

  let premessage = "";
  if (name) {
    premessage = `Dear ${name}\n\n Thank you for considering Smartronic.online for your CCTV security needs. Please find the quotation below:\n\n`;
  }

  document.getElementById("whatsappMessage").innerText = premessage + message;

  let message_1 =
    `- All prices include 18% GST and a 20% offer price applied.\n` +
    `- All delivered goods are 100% genuine/ original, with no duplicates or copy.\n` +
    `- A 2-year warranty is provided on all HikVision products from the brand 1 year and from Smartronic 1 year.\n\n`;

  document.getElementById("mess-1").innerText = message_1;
 
  let message_2 =
    `*Special Offers & Benefits*\n` +
    `- ⁠Free Installation & Materials\n` +
    `- ⁠2-Year Warranty (Brand + Smartronic.online Extended)\n` +
    `- ⁠Optional 5-Year Replacement Warranty with AMC\n` +
    `- ⁠Additional Cabling: Rs. 30/meter (extra labor if required)\n\n` +
    `*Why Choose Smartronic.online?*\n` +
    `- ⁠AI-Powered Security – High-performance cameras with smart night vision\n` +
    `- ⁠Affordable & Transparent Pricing – No hidden charges\n` +
    `- ⁠Hassle-Free Installation – Full setup & configuration at no extra cost\n` +
    `- ⁠Reliable service & quick support`;

  document.getElementById("mess-2").innerText = message_2;
}

function sendWhatsAppMessage() {
  let name = document.getElementById("name").value;
  let pnumber = document.getElementById("pnumber").value;

  let message = encodeURIComponent(
    document.getElementById("whatsappMessage").innerText
  );
  console.log(pnumber, name);
  let url = `https://api.whatsapp.com/send?text=${message}`;
  let url2 = url;
  if (pnumber) url2 = `${url}&phone=91${pnumber}`;

  window.open(url2, "_blank");
}

function copyToClipboard(copyid) {
  const text = document.getElementById(copyid).innerText;

  // Create a temporary textarea to copy text
  const tempTextArea = document.createElement("textarea");
  tempTextArea.value = text;
  document.body.appendChild(tempTextArea);
  tempTextArea.select();
  document.execCommand("copy");
  document.body.removeChild(tempTextArea);

  // Find the clicked button
  const button = document.querySelector(
    `[onclick="copyToClipboard('${copyid}')"]`
  );

  // Create the copied message element
  let copiedMessage = document.createElement("span");
  copiedMessage.innerText = "Copied!";
  copiedMessage.classList.add("copied-message");

  // Position the message next to the button
  // button.appendChild(copiedMessage);

  //Animate & remove after delay
  setTimeout(() => {
    copiedMessage.style.opacity = "0";
    setTimeout(() => copiedMessage.remove(), 300); // Remove element after fade out
  }, 1000);
}

function fic(number) {
  //formatIndianCurrency
  if (isNaN(number)) {
    return "Invalid Number";
  }

  // Convert to string and remove any decimal part
  let numStr = Math.floor(number).toString();

  // Split into two parts: last three digits and the rest
  let lastThree = numStr.slice(-3);
  let rest = numStr.slice(0, -3);

  // Add commas as per Indian number system
  if (rest) {
    rest = rest.replace(/\B(?=(\d{2})+(?!\d))/g, ",");
  }

  return rest ? rest + "," + lastThree : lastThree;
}

//remove After Characters
function rac(str, char) {
  let index = str.indexOf(char);
  return index !== -1 ? str.substring(0, index).trim() : str;
}

document.addEventListener("DOMContentLoaded", function () {
  const urlParams = new URLSearchParams(window.location.search);
  const quote = urlParams.get("quote");

  if (quote) {
    const params = quote.split("|").map((param) => param.trim());
    if (params.length >= 4) {
      document.getElementById("pnumber").value = params[0]; // Phone number
      document.getElementById("name").value = params[5]; // Phone number
      document.getElementById("numCameras").value = params[1]; // Number of cameras
      document.getElementById("idno").value = params[6]; // id no of the row

      // Select Hard Disk
      const hardDiskOptions = document.getElementById("hardDisk").options;

      for (let option of hardDiskOptions) {
        if (option.text.includes(params[3])) {
          option.selected = true;
          break;
        }
      }

      // Select Camera
      const cameraOptions = document.getElementById("camera").options;
      for (let option of cameraOptions) {
        if (option.text.includes(params[4] + " Normal")) {
          option.selected = true;
          break;
        }
      }

      updateDVRSelection();
      calculateTotal();
    }
  }
});
function openTab(event, tabId) {
  // Hide all tab contents
  const tabContents = document.querySelectorAll(".tab-content");
  tabContents.forEach((tab) => tab.classList.remove("active"));

  // Remove active class from all buttons
  const tabButtons = document.querySelectorAll(".tab-btn");
  tabButtons.forEach((btn) => btn.classList.remove("active"));

  // Show the selected tab
  document.getElementById(tabId).classList.add("active");
  event.currentTarget.classList.add("active");
  copyToClipboard(tabId);
}
function updateProfitValue() {
  document.getElementById("limitedProfitValue").textContent =
    document.getElementById("limitedProfit").value;
  document.getElementById("perCamValue").textContent =
    document.getElementById("perCam").value;
}
updateProfitValue();


function calculatePoEsWithPrice(cameraCount) {
  const result = {
    poes: [],      // array of PoE sizes used (e.g., [8, 4])
    totalPrice: 0  // total cost
  };

  const prices = {
    8: 1125,
    4: 825
  };

  let remaining = cameraCount;

  while (remaining > 8) {
    result.poes.push(8);
    result.totalPrice += prices[8];
    remaining -= 8;
  }

  if (remaining > 0) {
    if (remaining === 4 || remaining === 8) {
      result.poes.push(remaining);
      result.totalPrice += prices[remaining];
    } else if (remaining <= 4) {
      result.poes.push(4);
      result.totalPrice += prices[4];
    } else {
      result.poes.push(8);
      result.totalPrice += prices[8];
    }
  }

  return result;
}

