<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
require_once 'auth.php'; // Assuming we create auth.php in admin folder

require 'config.php'; // contains $mysqli = new mysqli(...);
$error = '';


// Ensure error reporting won't expose sensitive data in production
error_reporting(0);

// Check if the cookie exists and has the right value
if (
    !isset($_COOKIE['auth_role']) || 
    ($_COOKIE['auth_role'] !== 'admin' && $_COOKIE['auth_role'] !== 'market')
) {
    echo "No access";
    exit; // Stop processing the rest of the page
}

// If the user passes the check, the rest of your page code runs below...

// If cookie is set — user is authenticated

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCTV Quote Calculator</title>
    <link rel="stylesheet" href="css/styles.css">
     <style>
        /* Style for optgroup labels - make them bold and dark */
        select#camera optgroup {
            font-weight: 700;
            color: #1a1a1a;
            background-color: #f0f0f0;
            font-size: 14px;
            padding: 8px 0;
        }
        
        select#camera option {
            font-weight: 400;
            color: #333;
            padding-left: 10px;
        }
    </style>
    
   
</head>
<body class="quote">
    <div class="controls">
        <div class="logo-section">
                <a href="/" class="custom-logo-link" rel="home" aria-current="page">
                    <img width="200" height="40" src="https://smartronic.online/content/uploads/2025/01/smarthome-black2.svg" class="custom-logo" alt="Smartronic | CCTV with Free Installation | Smart Home Automation" decoding="async">
                </a>
            
            </div>
 
        <?php
        echo "<h2>Hello, " . $_COOKIE['auth_name'] . "!</h2>";
        ?>
         
     

        </div>
    </div>
    <div class="container">
        

        
        <label>Manual 2:</label><input type="checkbox" value="on" name="manual" id="manual" onchange="updateDVRSelection(); calculateTotal();"/> 
        <div class="row">
            <div class="column">
                <input type="hidden" name="idno" id="idno" />
                <label>Hard Disk:</label>
                <select id="hardDisk" onchange="calculateTotal()">
                <option value="0">Select</option>
                    <option value="1950" selected class="highlight">Const 500 GB (2Yr) - ₹1950*</option>
                    <option value="6600+" class="highlight">Seagate 1TB (3Yr) - ₹6600*</option>
                    <option value="6850" class="highlight">Toshiba 2TB (3Yr) - ₹6850*</option>
                    <option value="8000" class="highlight">Seagate 4TB (3Yr) - ₹8000*</option>
                    <option value="14000" class="highlight">Seagate 6TB (3Yr) - ₹14000*</option>
                    <option value="17500" class="highlight">Seagate 8TB (3Yr) - ₹17500*</option>
                    <option value="000">--- SELECT ONLY THE ABOVE ---</option>

                    <option value="5500">Const 2TB (2Yr) - ₹5500</option>
                    <option value="6100">Toshiba 2TB (2Yr) - ₹6100</option>
                    <option value="5250">Const 4TB (2Yr) - ₹5250</option>
                    <option value="8500">Consist 6TB (2Yr) - ₹8500</option>
                    <option value="10500">Toshiba 6TB (2Yr) - ₹10500</option>
                    <option value="850">500 GB (1Yr) - ₹850</option>
                    <option value="1950">1TB (1Yr) - ₹1950</option>
                    <option value="3600">1TB (3Yr) - ₹3600</option>
                    <option value="2850">2TB (1Yr) - ₹2800</option>
                    <option value="4050">2TB (3Yr) - ₹4050</option>
                    <option value="3250">3TB (1Yr) - ₹3250</option>
                    <option value="5250">4TB (1Yr) - ₹5250</option>
                    <option value="6650">4TB (3Yr) - ₹6650</option>
                    
                </select>

                <label>Camera Type:</label>
                <select id="camera" onchange="updateDVRSelection(); calculateTotal();">
                    <option value="0">Select Camera Type</option>

                     <optgroup label="HIKVISION - 2MP DVR Cameras">
                        <option value="1800" data-type="2 mp">Color Night Vision - ₹1,800</option>
                        <option value="1250" data-type="2 mp">Hybrid Color Night - ₹1,250</option>
                        <option value="825" data-type="2 mp">Normal Night with Mic - ₹825*</option>
                        <option value="800" data-type="2 mp">Normal Night Vision - ₹800</option>
                    </optgroup>
                   
                    <optgroup label="HIKVISION - 5MP DVR Cameras">
                        <option value="2180" data-type="5 mp">Color Night 3K - ₹2,180</option>
                        <option value="1450" data-type="5 mp">Hybrid Color Night 3K - ₹1,450</option>
                        <option value="1200" data-type="5 mp">Normal Night Vision - ₹1,200*</option>
                    </optgroup>
                    
                   
                    
                    <optgroup label="CP PLUS - 2MP DVR Cameras">
                        <option value="650" data-type="2 mp">2MP Normal Night - ₹650</option>
                        <option value="750" data-type="2 mp">2.4MP Normal Night - ₹750</option>
                        <option value="1050" data-type="2 mp">2MP Full Colour - ₹1,050</option>

                    </optgroup>
                    <optgroup label="CP PLUS - 5MP DVR Cameras">
                        <option value="1100" data-type="5 mp">5MP Normal Night - ₹1,100</option>
                        <option value="1400" data-type="5 mp">5MP Hybrid - ₹1,400</option>
                        <option value="1800" data-type="5 mp">5MP Full Colour - ₹1,800</option>
                    </optgroup>
                    
                    <optgroup label="HIKVISION - NVR IP Cameras">
                        <option value="3650" data-type="2 mp NVR">2MP IP Bullet Camera - ₹3,650</option>
                        <option value="6000" data-type="2 mp NVR">2MP IP Bullet Camera - ₹6,000</option>
                        <option value="3550" data-type="2 mp NVR">4MP IP Bullet Camera - ₹3,550</option>
                        <option value="6000" data-type="2 mp NVR">6MP IP Bullet Camera - ₹6,000</option>
                        <option value="5950" data-type="2 mp NVR">4MP Hybrid IP Bullet Camera (DS-2CD1041G2-LIU)- ₹5,950</option>
                        <option value="4850" data-type="2 mp NVR">6MP IP Camera - ₹4,850</option>
                    </optgroup>
                    
                    <optgroup label="CP PLUS - NVR IP Cameras">
                        <option value="1900" data-type="2 mp NVR">2MP Normal IP Camera - ₹1,900</option>
                        <option value="2300" data-type="2 mp NVR">2MP Full Color IP Camera - ₹2,300</option>
                        <option value="2250" data-type="2 mp NVR">4MP Normal IP Camera - ₹2,250</option>
                        <option value="2890" data-type="2 mp NVR">4MP Full Color IP Camera (CP-UNC-DA41PL3C-D-LQ) - ₹2,890</option>
                    </optgroup>
                </select>

                <label>Number of Cameras:</label>
                <input type="number" id="numCameras" value="1" min="1" onchange="updateDVRSelection(); calculateTotal();">

                <label>DVR Type:</label>
                <select id="dvr" onchange="calculateTotal()">
                </select>

                <label>DVR SMPS / NVR POE:</label>
                <select id="smps" onchange="calculateTotal()"> 
                    <option value="500">SMPS 500 - ₹500</option>
                    <option value="850">SMPS 850 - ₹850</option>
                </select>
                
                <input type="text" id="poe" value="0" />
                <label>Cable 90 Meters:</label>
                <select id="cable" onchange="calculateTotal()">
                    <option value="650" >Local ₹650</option>
                    <option value="1000" selected>DLINK ₹1000</option>
                    <option value="1600">CP Plus Cat6 ₹1600</option>
                </select>
                
                <label>Profit Margin (%):</label>
                <input type="number" id="profitMargin" value="60" min="0" onchange="calculateTotal()">
            </div>
        
            <div class="column">
                <div id="breakdown"></div>
                
            </div>
        </div>
        <h2></h2>
        <div class="row">
            <div class="row-container">
                <div class="column">
                    <label for="perCam">Installation per CAM: <span id="perCamValue">100</span></label>
                    <input type="range" min="400" max="900" step="50" name="perCam" id="perCam" value="600" onchange="calculateTotal()" oninput="updateProfitValue()"/>
                </div>
                <div class="column"> 
                    <label for="limitedProfit">Profit: <span id="limitedProfitValue">100</span></label>
                    <input type="range" min="100" max="10000" step="100" name="profit" id="limitedProfit" value="3000" onchange="calculateTotal()" oninput="updateProfitValue()"/>
                </div>
                
            </div>
        </div>
        <h2>WhatsApp Message</h2>
        <div class="row whatsapp-message-box">
           
        <div class="row-container">
            <div class="column">
                <label>Name:</label>
                <input type="text" name="name" id="name" onchange="calculateTotal()"/>
            </div>
            <div class="column">  
                <label>WhatsApp:</label>
                <input type="text" name="pnumber" id="pnumber" onchange="calculateTotal()"/>
            </div>
            <div class="column">
                <button class="whatsapp-button" onclick="sendWhatsAppMessage()">Send via WhatsApp</button>
            </div>
        </div>


            <div class="tab-container">
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="openTab(event, 'whatsappMessage')">Greeting + 1st Quote</button>
                    <button class="tab-btn" onclick="openTab(event, 'mess-1')">2nd Quote</button>
                    <button class="tab-btn" onclick="openTab(event, 'mess-2')">3rd Quote</button>
                    <button class="tab-btn" onclick="openTab(event, 'mess-3')">Message 3</button>
                    <button class="tab-btn" onclick="openTab(event, 'mess-4')">Message 4</button>
                </div>

                <div class="tab-content active" id="whatsappMessage">
                    <button onclick="copyToClipboard('whatsappMessage')">Copy to Clipboard</button>
                    <div>WhatsApp Message Content Here</div>
                </div>

                <div class="tab-content" id="mess-1">
                    <button onclick="copyToClipboard('mess-1')">Copy to Clipboard</button>
                    <div>Message 1 Content Here</div>
                </div>

                <div class="tab-content" id="mess-2">
                    <button onclick="copyToClipboard('mess-2')">Copy to Clipboard</button>
                    <div>Message 2 Content Here</div>
                </div>

                <div class="tab-content" id="mess-3">
                    <button onclick="copyToClipboard('mess-3')">Copy to Clipboard</button>
                    <div>Message 3 Content Here</div>
                </div>

                <div class="tab-content" id="mess-4">
                    <button onclick="copyToClipboard('mess-4')">Copy to Clipboard</button>
                    <div>Message 4 Content Here</div>
                </div>
            </div>


        </div>
        <?php
            $js_suffix = isset($_GET['js']) ? basename($_GET['js']) : '';
            $js_file = $js_suffix ? "js/scripts.js" : 'js/scripts_may2025';
        ?>
<script src="js/scripts_may2025.js"></script>
</body> 
</html>
