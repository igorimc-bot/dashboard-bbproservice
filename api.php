<?php
require_once 'config.php';

/**
 * API Endpoint for receiving stats from remote sites
 * 
 * Parameters (POST or GET for simplicity in tracking pixels):
 * - site_id: The ID of the site in the dashboard
 * - type: 'visit' or 'lead'
 * - secret: A simple security token (to be implemented more robustly later)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow from any site

$siteId = $_REQUEST['site_id'] ?? null;
$type = $_REQUEST['type'] ?? 'visit'; // Default to visit

if (!$siteId) {
    echo json_encode(['status' => 'error', 'message' => 'Missing site_id']);
    exit;
}

$db = getDB();
$today = date('Y-m-d');

try {
    // Check if record exists for today
    $stmt = $db->prepare("SELECT id FROM daily_stats WHERE site_id = ? AND date = ?");
    $stmt->execute([$siteId, $today]);
    $record = $stmt->fetch();

    if ($record) {
        // Update existing record
        $field = ($type === 'lead') ? 'leads' : 'visits';
        $stmt = $db->prepare("UPDATE daily_stats SET $field = $field + 1 WHERE id = ?");
        $stmt->execute([$record['id']]);
    } else {
        // Create new record for today
        $visits = ($type === 'visit') ? 1 : 0;
        $leads = ($type === 'lead') ? 1 : 0;
        $stmt = $db->prepare("INSERT INTO daily_stats (site_id, date, visits, leads) VALUES (?, ?, ?, ?)");
        $stmt->execute([$siteId, $today, $visits, $leads]);
    }

    echo json_encode(['status' => 'success', 'type' => $type]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>