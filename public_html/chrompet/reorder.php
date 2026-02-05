<?php
require 'db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['audios'])) {
    echo json_encode(['success' => false, 'message' => 'No data sent']);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE audios SET audio_order = ? WHERE id = ?");
    foreach ($input['audios'] as $a) {
        $stmt->execute([(int)$a['audio_order'], (int)$a['id']]);
    }
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
