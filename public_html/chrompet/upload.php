<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['audio']) && $_FILES['audio']['error'] === 0) {
        $file = $_FILES['audio'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['mp3', 'm4a', 'wav', 'ogg'];
        $allowedTypes = ['audio/mpeg', 'audio/mp4', 'audio/x-m4a', 'audio/m4a', 'audio/ogg', 'audio/wav'];

        if (in_array($ext, $allowed) && in_array($file['type'], $allowedTypes)) {
            $targetDir = 'uploads/';
            $fileName = time() . '_' . basename($file['name']);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                $sql = "INSERT INTO audios (filename, transcript) VALUES (?, '')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$fileName]);
                echo "<p>✅ File uploaded successfully: $fileName</p>";
                echo '<a href="admin.php" class="btn blue">Go back</a>';
            } else {
                echo "<p>❌ Error moving uploaded file.</p>";
            }
        } else {
            echo "<p>❌ Invalid file type. Please upload .mp3, .m4a, .wav, or .ogg.</p>";
        }
    } else {
        echo "<p>❌ No file uploaded or upload error.</p>";
    }
}
?>
