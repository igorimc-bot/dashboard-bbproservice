<?php
require_once __DIR__ . '/auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

$siteId = $_POST['site_id'] ?? null;
$content = $_POST['note_content'] ?? '';

if (!$siteId || empty(trim($content))) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

// Security Check
$db = getDB();
$stmt = $db->prepare("SELECT owner_id FROM sites WHERE id = ?");
$stmt->execute([$siteId]);
$site = $stmt->fetch();

if (!$site || (!isSuperAdmin() && $site['owner_id'] != $_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $db->prepare("INSERT INTO site_notes (site_id, content) VALUES (?, ?)");
    $stmt->execute([$siteId, trim($content)]);

    $fileId = $db->lastInsertId();
    $timestamp = date('d M Y H:i'); // Return formatted date for immediate UI update

    echo json_encode([
        'status' => 'success',
        'id' => $fileId,
        'content' => nl2br(htmlspecialchars(trim($content))), // Prepare for HTML display
        'created_at' => $timestamp
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>