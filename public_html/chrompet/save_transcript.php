<?php
// save_transcript.php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

$transcript_id = $_POST['transcript_id'] ?? null;
$audio_id = $_POST['audio_id'] ?? null;
$content = trim($_POST['content'] ?? '');
$language = trim($_POST['language'] ?? 'unknown');
$order = intval($_POST['transcript_order'] ?? 0);

if ($transcript_id) {
    // update
    $stmt = $pdo->prepare("UPDATE transcripts SET content = ?, language = ?, transcript_order = ? WHERE id = ?");
    $stmt->execute([$content, $language, $order, $transcript_id]);
} elseif ($audio_id) {
    // insert
    $stmt = $pdo->prepare("INSERT INTO transcripts (audio_id, content, transcript_order, language) VALUES (?, ?, ?, ?)");
    $stmt->execute([$audio_id, $content, $order, $language]);
}

header('Location: admin.php');
exit;
?>
