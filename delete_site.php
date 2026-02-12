<?php
require_once __DIR__ . '/auth.php';
requireLogin();

// Only Super Admin can delete sites
if (!isSuperAdmin()) {
    die("Unauthorized Access");
}

$siteId = $_GET['id'] ?? null;

if ($siteId) {
    $db = getDB();
    try {
        // Delete site (cascading delete will handle stats if FK is set up correctly, 
        // otherwise we should delete stats first)

        // Ensure cascading is in schema, but to be safe:
        $db->beginTransaction();

        // 1. Delete daily stats
        $stmt = $db->prepare("DELETE FROM daily_stats WHERE site_id = ?");
        $stmt->execute([$siteId]);

        // 2. Delete site
        $stmt = $db->prepare("DELETE FROM sites WHERE id = ?");
        $stmt->execute([$siteId]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        die("Error deleting site: " . $e->getMessage());
    }
}

// Redirect back to dashboard
header('Location: index.php');
exit;
?>