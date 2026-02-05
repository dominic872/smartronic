<?php
// delete.php
require 'db.php';

if (isset($_GET['audio_id'])) {
    $id = intval($_GET['audio_id']);
    // get filename to remove
    $stmt = $pdo->prepare("SELECT filename FROM audios WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        @unlink(__DIR__ . '/uploads/' . $row['filename']);
        $pdo->prepare("DELETE FROM audios WHERE id = ?")->execute([$id]);
    }
    header('Location: admin.php'); exit;
}

if (isset($_GET['transcript_id'])) {
    $id = intval($_GET['transcript_id']);
    $pdo->prepare("DELETE FROM transcripts WHERE id = ?")->execute([$id]);
    header('Location: admin.php'); exit;
}
header('Location: admin.php'); exit;
?>
