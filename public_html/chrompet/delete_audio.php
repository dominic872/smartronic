<?php
require 'db.php';
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing id']);
        exit;
    }
    $id = (int)$input['id'];

    // Fetch audio to get filename
    $stmt = $pdo->prepare('SELECT id, filename FROM audios WHERE id = ?');
    $stmt->execute([$id]);
    $audio = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$audio) {
        echo json_encode(['success' => false, 'message' => 'Audio not found']);
        exit;
    }

    $pdo->beginTransaction();

    // Delete related transcripts if table exists
    try {
        $pdo->query('SELECT 1 FROM transcripts LIMIT 1');
        $delT = $pdo->prepare('DELETE FROM transcripts WHERE audio_id = ?');
        $delT->execute([$id]);
    } catch (Exception $e) {
        // transcripts table may not exist; ignore
    }

    // Delete audio row
    $delA = $pdo->prepare('DELETE FROM audios WHERE id = ?');
    $delA->execute([$id]);

    // Reindex audio_order to maintain contiguous ordering
    $rows = $pdo->query('SELECT id FROM audios ORDER BY audio_order ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN);
    $upd = $pdo->prepare('UPDATE audios SET audio_order = ? WHERE id = ?');
    $order = 1;
    foreach ($rows as $rowId) {
        $upd->execute([$order++, $rowId]);
    }

    $pdo->commit();

    // Delete files from filesystem after DB commit
    $filename = $audio['filename'];
    $safe = basename($filename);
    $base = pathinfo($safe, PATHINFO_FILENAME);
    $paths = [
        __DIR__ . '/uploads/' . $safe,
        __DIR__ . '/uploads/' . $base . '.mp3'
    ];
    foreach ($paths as $p) {
        if (is_file($p)) {
            @unlink($p);
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
