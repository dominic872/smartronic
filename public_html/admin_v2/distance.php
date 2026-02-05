<!DOCTYPE html>
<html>
<head>
  <title>Distance Finder</title>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB7BKkBQEI0WpbFFjn8K4VWKRaYeIs3GhU&libraries=places"></script>
</head>
<body>
  <h2>Find Distance (KM)</h2>
  <input type="text" id="destination" placeholder="Enter destination">
  <button onclick="getDistance()">Get Distance</button>
  <p id="result"></p>

  <script>
    // Fixed origin location
    const origin = "VJXQ+9FX, Bandepalya, Garvebhavi Palya, Bengaluru, Karnataka 560068";

    // Initialize autocomplete with location bias
    function initAutocomplete() {
      const options = {
        types: ['geocode'],
        componentRestrictions: { country: 'in' },
        fields: ['formatted_address'],
        bounds: new google.maps.LatLngBounds(
          new google.maps.LatLng(12.80, 77.40), // SW corner of Bangalore
          new google.maps.LatLng(13.20, 77.80)  // NE corner of Bangalore
        ),
        strictBounds: false
      };

      const input = document.getElementById('destination');
      new google.maps.places.Autocomplete(input, options);
    }

    // Load autocomplete when the page loads
    window.onload = initAutocomplete;

    function getDistance() {
      const destination = document.getElementById('destination').value;

      if (!origin || !destination) {
        alert('Please enter both origin and destination');
        return;
      }

      fetch(`distance_api.php?origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}`)
        .then(res => res.json())
        .then(data => {
          if (data.distance_km !== undefined) {
            document.getElementById('result').textContent = `Distance: ${data.distance_km} km (${data.text})`;
          } else {
            document.getElementById('result').textContent = `Error: ${data.error}`;
          }
        })
        .catch(err => {
          console.error(err);
          document.getElementById('result').textContent = 'Failed to fetch distance';
        });
    }
  </script>
</body>
</html>
