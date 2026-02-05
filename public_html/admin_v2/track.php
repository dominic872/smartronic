<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Analytics</title>
</head>
<body>
    <h1>Welcome to the Analytics Page</h1>
    <img src="analytics.png" alt="Tracking Image" id="tracking-image" />

    <script>
        // Initialize visitor tracking
        let scrollDepth = 0;
        let startTime = Date.now(); 

        // Capture scroll depth
        window.addEventListener('scroll', () => {
            const scrolled = Math.ceil((window.scrollY / document.body.scrollHeight) * 100);
            scrollDepth = Math.max(scrollDepth, scrolled);
        });

        // Send data to the server
        function sendData(data) {
            fetch('https://smartronic.online/admin/track.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });
        }

        // Send user activity on unload
        window.addEventListener('beforeunload', () => {
            const timeOnPage = Math.ceil((Date.now() - startTime) / 1000);
            sendData({
                scrollDepth: scrollDepth,
                timeOnPage: timeOnPage,
            });
        });

        // Tracking pixel for capturing visitor info
        const img = document.getElementById('tracking-image');
        img.src = `https://smartronic.online/admin/capture.php?timestamp=${Date.now()}`;
    </script>
</body>
</html>
