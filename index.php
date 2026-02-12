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
    $stmt = $db->prepare("SELECT visits, leads, page_views FROM daily_stats WHERE site_id = ? AND date = ?");
    $stmt->execute([$site['id'], $dateSelection]);
    $todayStats = $stmt->fetch() ?: ['visits' => 0, 'leads' => 0, 'page_views' => 0];

    // Yesterday's stats for comparison
    $stmt = $db->prepare("SELECT visits, leads, page_views FROM daily_stats WHERE site_id = ? AND date = ?");
    $stmt->execute([$site['id'], $yesterday]);
    $yesterdayStats = $stmt->fetch() ?: ['visits' => 0, 'leads' => 0, 'page_views' => 0];

    $site['today'] = $todayStats;
    $site['yesterday'] = $yesterdayStats;

    // Calculate deltas
    $site['visit_delta'] = $todayStats['visits'] - $yesterdayStats['visits'];
    $site['lead_delta'] = $todayStats['leads'] - $yesterdayStats['leads'];
    $site['page_view_delta'] = ($todayStats['page_views'] ?? 0) - ($yesterdayStats['page_views'] ?? 0);
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
    <link rel="stylesheet" href="dashboard.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                    <li><a href="index.php" class="active">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                            Dashboards
                        </a></li>
                    <?php if ($isSuperAdmin): ?>
                        <li><a href="manage_sites.php">
                                <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path
                                        d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z">
                                    </path>
                                </svg>
                                Manage Sites
                            </a></li>
                    <?php endif; ?>
                    <li><a href="#">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                            </svg>
                            Analytics
                        </a></li>
                    <li><a href="#">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z">
                                </path>
                            </svg>
                            Projects
                        </a></li>
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
                    <a href="logout.php" class="btn-logout" title="Logout">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </a>
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

                <!-- Summary Cards Section -->
                <div class="summary-cards">
                    <div class="summary-card blue">
                        <div class="summary-icon" style="background-color: rgba(255,255,255,0.6); color: #1e88e5;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                <polyline points="17 6 23 6 23 12"></polyline>
                            </svg>
                        </div>
                        <div class="summary-content">
                            <span class="summary-value" id="total-visits">0</span>
                            <span class="summary-label">Totale Visite</span>
                        </div>
                    </div>
                    <div class="summary-card purple">
                        <div class="summary-icon" style="background-color: rgba(255,255,255,0.6); color: #7b1fa2;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="2" y1="12" x2="22" y2="12"></line>
                                <path
                                    d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z">
                                </path>
                            </svg>
                        </div>
                        <div class="summary-content">
                            <span class="summary-value" id="total-page-views">0</span>
                            <span class="summary-label">Pagine Viste</span>
                        </div>
                    </div>
                    <div class="summary-card green">
                        <div class="summary-icon" style="background-color: rgba(255,255,255,0.6); color: #2e7d32;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="summary-content">
                            <span class="summary-value" id="total-leads">0</span>
                            <span class="summary-label">Totale Lead</span>
                        </div>
                    </div>
                </div>

                <div class="stats-grid">
                    <?php if (empty($sites)): ?>
                        <div class="alert alert-info" style="grid-column: 1/-1;">
                            Nessun sito trovato.
                        </div>
                    <?php else: ?>
                        <?php foreach ($sites as $site): ?>
                            <div class="stat-card" data-visits="<?php echo $site['today']['visits']; ?>"
                                data-leads="<?php echo $site['today']['leads']; ?>"
                                data-page-views="<?php echo $site['today']['page_views'] ?? 0; ?>">
                                <!-- Checkbox for selection -->
                                <div class="card-checkbox">
                                    <input type="checkbox" class="site-checkbox" checked onchange="calculateTotals()">
                                </div>
                                <!-- 1. Identity -->
                                <div class="card-header">
                                    <div class="site-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="2" y1="12" x2="22" y2="12"></line>
                                            <path
                                                d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div class="site-meta">
                                        <h3><?php echo htmlspecialchars($site['name']); ?></h3>
                                        <a href="<?php echo htmlspecialchars($site['url']); ?>"
                                            target="_blank"><?php echo htmlspecialchars($site['url']); ?></a>
                                    </div>
                                </div>

                                <!-- 2. Metrics -->
                                <div class="card-body">
                                    <div class="metric">
                                        <span class="value"><?php echo number_format($site['today']['visits']); ?></span>
                                        <span class="label">Visite Oggi</span>
                                        <div class="trend <?php echo $site['visit_delta'] >= 0 ? 'positive' : 'negative'; ?>">
                                            <?php if ($site['visit_delta'] >= 0): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <polyline points="18 15 12 9 6 15"></polyline>
                                                </svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <polyline points="6 9 12 15 18 9"></polyline>
                                                </svg>
                                            <?php endif; ?>
                                            <?php echo abs($site['visit_delta']); ?>
                                        </div>
                                    </div>

                                    <div class="metric">
                                        <span class="value"><?php echo number_format($site['today']['page_views'] ?? 0); ?></span>
                                        <span class="label">Pagine Viste</span>
                                        <div
                                            class="trend <?php echo ($site['page_view_delta'] ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                                            <?php if (($site['page_view_delta'] ?? 0) >= 0): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <polyline points="18 15 12 9 6 15"></polyline>
                                                </svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <polyline points="6 9 12 15 18 9"></polyline>
                                                </svg>
                                            <?php endif; ?>
                                            <?php echo abs($site['page_view_delta'] ?? 0); ?>
                                        </div>
                                    </div>

                                    <div class="metric">
                                        <span class="value"><?php echo number_format($site['today']['leads']); ?></span>
                                        <span class="label">Lead Oggi</span>
                                        <div class="trend <?php echo $site['lead_delta'] >= 0 ? 'positive' : 'negative'; ?>">
                                            <?php if ($site['lead_delta'] >= 0): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <polyline points="18 15 12 9 6 15"></polyline>
                                                </svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <polyline points="6 9 12 15 18 9"></polyline>
                                                </svg>
                                            <?php endif; ?>
                                            <?php echo abs($site['lead_delta']); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. Actions -->
                                <div class="card-footer">
                                    <div class="card-actions-icons" style="border-left: none; padding-left: 0;">
                                        <a href="detail.php?id=<?php echo $site['id']; ?>" class="btn-icon-action"
                                            title="Vedi Statistiche" style="color: #3f6ad8; background: #e0f3ff;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <line x1="18" y1="20" x2="18" y2="10"></line>
                                                <line x1="12" y1="20" x2="12" y2="4"></line>
                                                <line x1="6" y1="20" x2="6" y2="14"></line>
                                            </svg>
                                        </a>
                                        <?php if ($isSuperAdmin): ?>
                                            <div style="width: 1px; height: 24px; background: #e9ecef; margin: 0 0.5rem;"></div>
                                            <a href="edit_site.php?id=<?php echo $site['id']; ?>" class="btn-icon-action"
                                                title="Modifica">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </a>
                                            <a href="delete_site.php?id=<?php echo $site['id']; ?>" class="btn-icon-action"
                                                title="Elimina">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path
                                                        d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                    </path>
                                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                                </svg>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function calculateTotals() {
            let totalVisits = 0;
            let totalLeads = 0;
            let totalPageViews = 0;
            let totalSites = 0;

            const checkboxes = document.querySelectorAll('.site-checkbox');

            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    const card = checkbox.closest('.stat-card');
                    const visits = parseInt(card.dataset.visits) || 0;
                    const leads = parseInt(card.dataset.leads) || 0;
                    const pageViews = parseInt(card.dataset.pageViews) || 0;

                    totalVisits += visits;
                    totalLeads += leads;
                    totalPageViews += pageViews;
                    totalSites++;
                }
            });

            // Update DOM 
            document.getElementById('total-visits').textContent = new Intl.NumberFormat().format(totalVisits);
            document.getElementById('total-leads').textContent = new Intl.NumberFormat().format(totalLeads);
            document.getElementById('total-page-views').textContent = new Intl.NumberFormat().format(totalPageViews);
        }

        // Run calculation on load
        document.addEventListener('DOMContentLoaded', calculateTotals);
    </script>
</body>

</html>