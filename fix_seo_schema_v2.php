<?php
require_once __DIR__ . '/config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Schema Fixer (SEO)</h1>";

try {
    $db = getDB();
    echo "<p>Connected to database successfully.</p>";

    // 1. Add indexed_pages to daily_stats
    echo "<p>Checking 'daily_stats' table for 'indexed_pages'...</p>";
    $stmt = $db->query("SHOW COLUMNS FROM daily_stats LIKE 'indexed_pages'");
    if ($stmt->fetch()) {
        echo "<p style='color:green'>Column 'indexed_pages' already exists.</p>";
    } else {
        echo "<p>Adding column 'indexed_pages'...</p>";
        // Safely add column
        $db->exec("ALTER TABLE daily_stats ADD COLUMN indexed_pages INT DEFAULT 0 AFTER leads");
        echo "<p style='color:green'>SUCCESS: Column 'indexed_pages' added.</p>";
    }

    // 2. Create site_notes table
    echo "<p>Checking 'site_notes' table...</p>";
    $db->exec("CREATE TABLE IF NOT EXISTS `site_notes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `site_id` INT NOT NULL,
        `content` TEXT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`site_id`) REFERENCES `sites`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "<p style='color:green'>SUCCESS: Table 'site_notes' (checked/created).</p>";

    echo "<hr><p style='font-size:1.2rem; color:blue;'><strong>ALL DONE!</strong> You can now use the SEO features.</p>";
    echo "<p>Please delete this file after use.</p>";

} catch (PDOException $e) {
    echo "<p style='color:red'><strong>DATABASE ERROR:</strong> " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'><strong>GENERAL ERROR:</strong> " . $e->getMessage() . "</p>";
}
?>