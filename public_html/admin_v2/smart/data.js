var data = {
  "HDD": {
    "Toshiba": [
      { "capacity": "500 GB", "value": null, "label": "500 GB" },
      { "capacity": "1 TB", "value": null, "label": "1 TB" },
      { "capacity": "2 TB", "value": "5451", "label": "2 TB (2Yr) - ₹5451" },
      { "capacity": "4 TB", "value": "6850", "label": "4 TB (2Yr) - ₹6850" },
      { "capacity": "6 TB", "value": "10500", "label": "6 TB (2Yr) - ₹10500" }
    ],
    "Consistent": [
      { "capacity": "500 GB", "value": "1550", "label": "500 GB (2Yr) - ₹1550", "preferred": "true" },
      { "capacity": "1 TB", "value": "3950", "label": "1 TB (2Yr) - ₹3950"},
      { "capacity": "2 TB", "value": "4250", "label": "2 TB (2Yr) - ₹4250" },
      { "capacity": "4 TB", "value": "5250", "label": "4 TB (2Yr) - ₹5250"},
      { "capacity": "6 TB", "value": "8500", "label": "6 TB (2Yr) - ₹8500" }
    ],
    "SEE/WD/TOSH": [
      { "capacity": "500 GB", "value": "850", "label": "500 GB (1Yr) - ₹850" },
      { "capacity": "1 TB", "value": "1950", "label": "1 TB (1Yr) - ₹1950" },
      { "capacity": "2 TB", "value": "2850", "label": "2 TB (1Yr) - ₹2800" },
      { "capacity": "4 TB", "value": "5250", "label": "4 TB (1Yr) - ₹5250" },
      { "capacity": "6 TB", "value": null, "label": "6 TB (1Yr) -" }
    ],
     "Seagate": [
      { "capacity": "1 TB", "value": "5050", "label": "1 TB (3Yr) - Seagate", "preferred": "true" },
      { "capacity": "2 TB", "value": "5950", "label": "2 TB (3Yr) - Seagate", "preferred": "true" }
    ]
  },
  "Type": {
    "DVR": {
      "HIKVISION": {
        "2MP": {
          "Recorder": {
            "4CH": [{ "value": 1950, "label": "4CH", "pdf": "https://www.hikvision.com/en/products/Turbo-HD-Products/DVR/Value-Series/ds-7104hghi-m1-t"}],
            "8CH": [{ "value": 2850, "label": "8 CH DVR", "pdf": "https://www.hikvision.com/en/products/Turbo-HD-Products/DVR/Value-Series/ds-7108hghi-m1-t"}],
            "16CH": [{ "value": 4950, "label": "16 CH", "pdf": "https://www.hikvision.com/en/products/Turbo-HD-Products/DVR/Value-Series/ds-7116hghi-m1-t"}],
            "32CH": [{ "value": 17750, "label": "32CH", "pdf": "https://www.hikvision.com/en/products/Turbo-HD-Products/DVR/Value-Series/ds-7116hghi-m1-t"}]
          },
          "Camera": [
            { "value": "1800", "label": "Color Night - ₹1800", "image": "", "pdf": "palette" },
            { "value": "1250", "label": "HYBID Night- ₹1250" },
            { "value": "825", "label": "Normal Night Mic - ₹825*" },
            { "value": "800", "label": "Normal Night - ₹800" },
            { "value": "1850", "label": "2 Way DOME - ₹1850" },
            { "value": "1850", "label": "2 Way Bullet - ₹1850" }
          ]
        },
        "5MP": {
          "Recorder": {
            "4CH": [{ "value": 2780, "label": "4CH" }],
            "8CH": [{ "value": 4500, "label": "8 CH DVR" }],
            "16CH": [{ "value": 10000, "label": "16 CH" }],
            "32CH": [{ "value": 21500, "label": "32CH" }]
          },
          "Camera": [
            { "value": "2180", "label": "Color Night 3K - ₹2180" },
            { "value": "1450", "label": "HYBID Night 3K - ₹1450" },
            { "value": "1150", "label": "Normal Night - ₹1150*" }
          ]
        }
      },
      "CP PLUS": {
        "2MP": {
          "Recorder": {
            "4CH": [{ "value": "1875", "label": "4CH - ₹1875+" }],
            "8CH": [{ "value": "2825", "label": "8CH - ₹2825" }],
            "16CH": [{ "value": "4750", "label": "16CH" }],
            "32CH": [{ "value": null, "label": "32CH" }]
          },
          "Camera": [
            { "value": "625", "label": "Normal NV - 650" },
            { "value": "1025", "label": "HYBRID - ₹1025" },
            { "value": "725", "label": "2.4 Mic - ₹725" },
            { "value": "1225", "label": "FULL COLOUR - ₹1225", "icon": "palette" }
          ]
        },
        "5MP": {
          "Recorder": {
            "4CH": [{ "value": "2825", "label": "4CH - ₹2825" }],
            "8CH": [{ "value": "4050", "label": "8CH - ₹4050" }],
            "16CH": [{ "value": null, "label": "16CH - 000" }],
            "32CH": [{ "value": null, "label": "32CH - 00" }]
          },
          "Camera": [
            { "value": "1025", "label": "Normal NV - 1075" },
            { "value": "1400", "label": "HYBRID - ₹1400" },
            { "value": "1750", "label": "FULL COLOUR - ₹1750", "icon": "palette" }
          ]
        }
      },
      "DAHUA": {
        "2MP": {
          "Recorder": {
            "4CH": [{ "value": "1975", "label": "4CH - ₹1975" }],
            "8CH": [{ "value": "2875", "label": "8CH - ₹2875" }],
            "16CH": [{ "value": null, "label": "16CH" }],
            "32CH": [{ "value": null, "label": "32CH" }]
          },
          "Camera": [
            { "value": "750", "label": "Normal NV- ₹750+" },
            { "value": "1050", "label": "HYBRID - ₹1050" },
            { "value": "1275", "label": "FULL COLOUR - ₹1275", "icon": "palette" }
          ]
        },
        "5MP": {
          "Recorder": {
            "4CH": [{ "value": "2850", "label": "4CH - ₹2850" }],
            "8CH": [{ "value": "4650", "label": "8CH - ₹4650" }],
            "16CH": [{ "value": null, "label": "16CH" }],
            "32CH": [{ "value": null, "label": "32CH" }]
          },
          "Camera": [
            { "value": "1050", "label": "Normal NV - ₹1050+" },
            { "value": "1350", "label": "HYBRID - ₹1350" },
            { "value": "1650", "label": "FULL COLOUR - ₹1650", "icon": "palette" }
          ]
        }
      }
    },
    "NVR": {
      "HIKVISION": {
        "Recorder": {
          "4CH": [{ "value": "2850", "label": "4CH - ₹2850" }],
          "8CH": [{ "value": "4000", "label": "8 CH - ₹4000" }],
          "16CH": [{ "value": "4950", "label": "16 CH - ₹4950" }],
          "32 CH": [{ "value": "11050", "label": "32CH - ₹11050" }]
        },
        "Camera": [
          { "value": "2700", "label": "2MP Normal NV - ₹2700", "mp": "2MP" },
          { "value": "3450", "label": "4MP Bullet NV- ₹3450", "mp": "4MP" },
          { "value": "4550", "label": "4MP - ₹4550", "mp": "4MP" },
          { "value": "6850", "label": "6MP - ₹4850", "mp": "6MP" }
        ]
      },
      "CP PLUS": {
        "Recorder": {
          "4CH": [{ "value": null, "label": "4CH - 0000" }],
          "8CH": [{ "value": "3350", "label": "8 CH - ₹3350+" }],
          "16CH": [{ "value": null, "label": "16 CH - 0000" }],
          "32 CH": [{ "value": null, "label": "32CH - 0000" }]
        },
        "Camera": [
          { "value": null, "label": "5MP Normal NV - 000", "mp": "5MP" },
          { "value": "3350", "label": "5MP HYBRID - ₹3350+", "mp": "5MP" }
        ]
      },
      "DAHUA": {}
    },
    "WIFI": {
      "Outdoor": [],
      "Indoor": [],
      "Camera": {
        "4MP": {
          "HIKVISION": [{ "value": "2750", "label": "H6C - ₹2750+" }]
        },
        "5MP": {
          "IMOU": [{ "value": "4750", "label": "5MP + 5MP - ₹4750+" }]
        },
        "3MP": {
          "IMOU": [{ "value": "4450", "label": "3MP + 3MP - ₹4450+" }]
        },
        "2MP": {
          "CP PLUS": [
            { "value": "1250", "label": "P23 - ₹1250+" },
            { "value": "1750", "label": "E48A - ₹1750+" }
          ]
        }
      }
    },
    "Wireless": {
      "HIKVISION": {
        "NVR": {
          "Recorder": [{ "value": "4350", "label": "EZVIZ CS-X5S-8W 8 CH - ₹4350+" }],
          "Camera": [
            { "value": "2750", "label": "EZVIZ H6C 4MP - ₹2750+" },
            { "value": "3050", "label": "EZVIZ C3WN - ₹3050+" }
          ]
        }
      },
      "CP PLUS": ["5MP"],
      "DAHUA": ["5MP"]
    }
  },
  "items": {
    "SMPS 4": { "value": "500", "label": "SMPS 4CH - ₹500", "show": false },
    "SMPS 8": { "value": "800", "label": "SMPS 8CH - ₹800", "show": false },
    "BNC WIRED": { "value": "20", "label": "BNC Wired - ₹20", "show": false },
    "DC": { "value": "20", "label": "DC Connector - ₹20", "show": false },
    "BACK BOX": { "value": "20", "label": "Back Box - ₹20", "show": false },
    "Dlink": { "value": "875", "label": "Dlink 3+1 Cable - ₹875", "show": false },
    "Rack 1U": { "value": "500", "label": "Rack 1U - ₹1200", "show": true, "icon": "server" },
    "Rack 2U": { "value": "650", "label": "Rack 2U - ₹1500", "show": true, "icon": "server" },
    "Frontech 16\"": { "value": "1350", "label": "FT 16\" - ₹2000", "show": true, "icon": "desktop" },
    "Frontech 19\"": { "value": "1650", "label": "FT 19\" - ₹2500", "show": true, "icon": "desktop" },
    "Frontech 22\"": { "value": "2300", "label": "FT 22\" - ₹3500", "show": true, "icon": "desktop" }
  },
  "additionalItems": {
    "profit": { "value": 3000 },
    "profitPercentage": { "value": 15 }
  }
};