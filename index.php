<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$isSuperAdmin = isSuperAdmin();

// Date range (default: today)
$dateSelection = $_GET['date'] ?? date('Y-m-d');
$yesterday = date('Y-m-d', strtotime($dateSelection . ' -1 day'));

// Fetch sites
if ($isSuperAdmin) {
    $stmt = $db->prepare("SELECT s.*, u.username as owner_name FROM sites s JOIN users u ON s.owner_id = u.id");
    $stmt->execute();
} else {
    $stmt = $db->prepare("SELECT * FROM sites WHERE owner_id = ?");
    $stmt->execute([$userId]);
}
$sites = $stmt->fetchAll();

// Fetch stats for each site
foreach ($sites as &$site) {
    // Today's stats
    $stmt = $db->prepare("SELECT visits, leads FROM daily_stats WHERE site_id = ? AND date = ?");
    $stmt->execute([$site['id'], $dateSelection]);
    $todayStats = $stmt->fetch() ?: ['visits' => 0, 'leads' => 0];

    // Yesterday's stats for comparison
    $stmt = $db->prepare("SELECT visits, leads FROM daily_stats WHERE site_id = ? AND date = ?");
    $stmt->execute([$site['id'], $yesterday]);
    $yesterdayStats = $stmt->fetch() ?: ['visits' => 0, 'leads' => 0];

    $site['today'] = $todayStats;
    $site['yesterday'] = $yesterdayStats;

    // Calculate deltas
    $site['visit_delta'] = $todayStats['visits'] - $yesterdayStats['visits'];
    $site['lead_delta'] = $todayStats['leads'] - $yesterdayStats['leads'];
}
unset($site);
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard -
        <?php echo SITE_NAME; ?>
    </title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <span class="logo-text">Architect</span>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">Main Navigation</div>
                <ul>
                    <li><a href="index.php" class="active"><span class="icon">📊</span> Dashboards</a></li>
                    <?php if ($isSuperAdmin): ?>
                        <li><a href="manage_sites.php"><span class="icon">⚙️</span> Manage Sites</a></li>
                    <?php endif; ?>
                    <li><a href="#"><span class="icon">📈</span> Analytics</a></li>
                    <li><a href="#"><span class="icon">📁</span> Projects</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div class="search-bar">
                    <input type="text" placeholder="Search...">
                </div>
                <div class="topbar-right">
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random"
                            alt="User">
                        <div class="user-info">
                            <span class="name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <span class="role"><?php echo $isSuperAdmin ? 'Super Admin' : 'User'; ?></span>
                        </div>
                    </div>
                    <a href="logout.php" class="btn-logout" title="Logout">🚪</a>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="content-wrapper">
                <div class="page-title-area">
                    <div class="title-left">
                        <h2>Analytics Dashboard</h2>
                        <p>This is an example dashboard created using build-in elements and components.</p>
                    </div>
                    <div class="title-right">
                        <?php if ($isSuperAdmin): ?>
                            <a href="manage_sites.php" class="btn btn-success">+ Add New Site</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Date Filter (Kept from original) -->
                <div class="filter-bar">
                    <form id="dateFilter" style="display:inline-block;">
                        <label style="margin-right:0.5rem; color: #6c757d;">Data:</label>
                        <input type="date" name="date" value="<?php echo $dateSelection; ?>"
                            onchange="this.form.submit()" class="form-control">
                    </form>
                </div>

                <div class="stats-grid">
                    <?php if (empty($sites)): ?>
                        <div class="alert alert-info" style="grid-column: 1/-1;">
                            Nessun sito trovato.
                        </div>
                    <?php else: ?>
                        <?php foreach ($sites as $site): ?>
                            <div class="stat-card">
                                <div class="card-header">
                                    <div class="site-icon">🌐</div>
                                    <div class="site-meta">
                                        <h3><?php echo htmlspecialchars($site['name']); ?></h3>
                                        <a href="<?php echo htmlspecialchars($site['url']); ?>"
                                            target="_blank"><?php echo htmlspecialchars($site['url']); ?></a>
                                    </div>
                                    <div class="card-actions">
                                        <a href="detail.php?id=<?php echo $site['id']; ?>" class="btn-icon">⋮</a>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <div class="metric">
                                        <span class="value"><?php echo number_format($site['today']['visits']); ?></span>
                                        <span class="label">Visits</span>
                                        <div class="trend <?php echo $site['visit_delta'] >= 0 ? 'positive' : 'negative'; ?>">
                                            <?php echo ($site['visit_delta'] >= 0 ? '↑' : '↓') . abs($site['visit_delta']); ?>
                                            vs yesterday
                                        </div>
                                    </div>

                                    <div class="metric">
                                        <span class="value"><?php echo number_format($site['today']['leads']); ?></span>
                                        <span class="label">Leads</span>
                                        <div class="trend <?php echo $site['lead_delta'] >= 0 ? 'positive' : 'negative'; ?>">
                                            <?php echo ($site['lead_delta'] >= 0 ? '↑' : '↓') . abs($site['lead_delta']); ?> vs
                                            yesterday
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <a href="detail.php?id=<?php echo $site['id']; ?>" class="btn-view-report">View Complete
                                        Report</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>