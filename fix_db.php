<?php
require_once __DIR__ . '/config.php';

echo "<h1>Database Fixer</h1>";

try {
    $db = getDB();
    echo "<p>Connected to database.</p>";

    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM daily_stats LIKE 'page_views'");
    if ($stmt->fetch()) {
        echo "<p style='color:green'>Column 'page_views' already exists.</p>";
    } else {
        echo "<p>Column 'page_views' is missing. Attempting to add...</p>";
        $db->exec("ALTER TABLE daily_stats ADD COLUMN page_views INT DEFAULT 0 AFTER leads");
        echo "<p style='color:green'>SUCCESS: Column 'page_views' added.</p>";
    }

    echo "<p>You can now delete this file and reload the dashboard.</p>";

} catch (PDOException $e) {
    echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>";
    echo "<p>If connection failed, make sure your DB credentials in config.php are correct.</p>";
}
?>