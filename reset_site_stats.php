<?php
require_once __DIR__ . '/auth.php';
requireLogin();

// Only Super Admin can reset stats
if (!isSuperAdmin()) {
    die("Unauthorized Access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteId = $_POST['site_id'] ?? null;

    if ($siteId) {
        $db = getDB();
        try {
            // Delete all daily stats for this site
            $stmt = $db->prepare("DELETE FROM daily_stats WHERE site_id = ?");
            $stmt->execute([$siteId]);

            // Redirect with success message
            header('Location: manage_sites.php?success=stats_reset');
            exit;
        } catch (PDOException $e) {
            // Redirect with error message
            header('Location: manage_sites.php?error=' . urlencode('Errore durante il reset: ' . $e->getMessage()));
            exit;
        }
    }
}

// Redirect back if accessed directly or no ID
header('Location: manage_sites.php');
exit;
?>