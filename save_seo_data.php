<?php
require_once __DIR__ . '/auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

$siteId = $_POST['site_id'] ?? null;
$indexedPages = $_POST['indexed_pages'] ?? null;

if (!$siteId || $indexedPages === null) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

// Security Check: User must own the site or be admin
$db = getDB();
$stmt = $db->prepare("SELECT owner_id FROM sites WHERE id = ?");
$stmt->execute([$siteId]);
$site = $stmt->fetch();

if (!$site || (!isSuperAdmin() && $site['owner_id'] != $_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $today = date('Y-m-d');

    // Check if record exists for today
    $stmt = $db->prepare("SELECT id FROM daily_stats WHERE site_id = ? AND date = ?");
    $stmt->execute([$siteId, $today]);
    $record = $stmt->fetch();

    if ($record) {
        $stmt = $db->prepare("UPDATE daily_stats SET indexed_pages = ? WHERE id = ?");
        $stmt->execute([$indexedPages, $record['id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO daily_stats (site_id, date, indexed_pages) VALUES (?, ?, ?)");
        $stmt->execute([$siteId, $today, $indexedPages]);
    }

    echo json_encode(['status' => 'success', 'date' => $today, 'indexed_pages' => $indexedPages]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>