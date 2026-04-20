<?php
// WhatsApp Leads Configuration
// Edit this file to manage available admin WhatsApp numbers and the active selection.

// List of available WhatsApp numbers (digits only)
$whatsappNumberList = [
    "919886735991",
    "918496080849",
    "918884831000",
  //  "917204849198",
    "918884407222",
    "916360274445",
    "917829080536"
];

// Currently active WhatsApp number (one from the list above)
$whatsappNumber = "919886735991";

// Optional labels for UI display (number => label)
// Update these labels to show friendly names next to numbers in the admin UI.
$whatsappNumberLabels = [
    "919886735991" => "Amreen",
    "918496080849" => "Smartronic",
    "918884831000" => "Dominic", 
   //"917204849198" => "Bharath",
    "918884407222" => "Sophy",
    "916360274445" => "Varsha",
    "917829080536" => "Zoya"
];

// Schedule Configuration (Day => Number)
// Keys: Mon, Tue, Wed, Thu, Fri, Sat, Sun
$whatsappSchedule = [
    "Mon" => "919886735991", // Amreen
    "Tue" => "917829080536", // Amreen

    "Wed" => "917829080536", // Varsha
    "Thu" => "916360274445", // Varsha
    "Fri" => "916360274445", // Varsha
    
    "Sat" => "919886735991", // Amreen
    "Sun" => "919886735991"  // Amreen
];
?>

