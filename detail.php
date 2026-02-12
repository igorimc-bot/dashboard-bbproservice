<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM sites WHERE id = ?");
$stmt->execute([$id]);
$site = $stmt->fetch();

if (!$site || (!isSuperAdmin() && $site['owner_id'] != $_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Fetch stats for the last 30 days
$stmt = $db->prepare("
    SELECT * 
    FROM daily_stats 
    WHERE site_id = ? 
    AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
    ORDER BY date ASC
");
$stmt->execute([$id]);
$stats = $stmt->fetchAll();

// Calculate Totals
$totalVisits = 0;
$totalPageViews = 0;
$totalLeads = 0;
$chartDates = [];
$chartVisits = [];
$chartLeads = [];

foreach ($stats as $row) {
    $totalVisits += $row['visits'];
    $totalLeads += $row['leads'];
    // Handle potential missing column gracefully
    $totalPageViews += $row['page_views'] ?? 0;

    $chartDates[] = date('d/m', strtotime($row['date']));
    $chartVisits[] = $row['visits'];
    $chartLeads[] = $row['leads'];
}

// Calculate Conversion Rate
$conversionRate = $totalVisits > 0 ? round(($totalLeads / $totalVisits) * 100, 2) : 0;

// Determine trend (dummy logic for now, comparing first half vs second half could be better but keep simple)
$status = 'active'; // Default
if (count($stats) > 0 && strtotime($stats[count($stats) - 1]['date']) < strtotime('-7 days')) {
    $status = 'inactive';
}

?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics: <?php echo htmlspecialchars($site['name']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="dashboard.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <li><a href="index.php">
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
                    <?php if (isSuperAdmin()): ?>
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
                    <li><a href="#" class="active">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                                <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                            </svg>
                            Analytics
                        </a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div class="search-bar">
                    <!-- Placeholder -->
                </div>
                <div class="topbar-right">
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=random"
                            alt="User">
                        <div class="user-info">
                            <span class="name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <span class="role"><?php echo isSuperAdmin() ? 'Super Admin' : 'User'; ?></span>
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

            <div class="content-wrapper">

                <!-- Detail Header -->
                <div class="detail-header">
                    <div class="detail-title-group">
                        <div style="display:flex; align-items:center; gap: 1rem; margin-bottom: 0.5rem;">
                            <a href="index.php" style="color: #94a3b8; display:flex; align-items:center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <line x1="19" y1="12" x2="5" y2="12"></line>
                                    <polyline points="12 19 5 12 12 5"></polyline>
                                </svg>
                            </a>
                            <h1><?php echo htmlspecialchars($site['name']); ?></h1>
                            <?php if ($status === 'active'): ?>
                                <span class="badge badge-success"
                                    style="background:#dcfce7; color:#166534; padding:0.25rem 0.75rem; border-radius:1rem; font-size:0.75rem; font-weight:600;">Active</span>
                            <?php else: ?>
                                <span class="badge"
                                    style="background:#f1f5f9; color:#64748b; padding:0.25rem 0.75rem; border-radius:1rem; font-size:0.75rem; font-weight:600;">Inactive</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo htmlspecialchars($site['url']); ?>" target="_blank" class="detail-url">
                            <?php echo htmlspecialchars($site['url']); ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <line x1="10" y1="14" x2="21" y2="3"></line>
                            </svg>
                        </a>
                    </div>
                    <div class="detail-actions">
                        <button class="btn btn-primary" onclick="window.print()">Export Report</button>
                    </div>
                </div>

                <!-- KPI Grid -->
                <div class="kpi-grid">
                    <!-- Visits -->
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-title">Visite Totali (30gg)</div>
                            <div class="kpi-value"><?php echo number_format($totalVisits); ?></div>
                        </div>
                        <div class="kpi-trend neutral">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <span>Ultimi 30 giorni</span>
                        </div>
                    </div>

                    <!-- Page Views -->
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-title">Pagine Viste</div>
                            <div class="kpi-value"><?php echo number_format($totalPageViews); ?></div>
                        </div>
                        <div class="kpi-trend up">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                <polyline points="17 6 23 6 23 12"></polyline>
                            </svg>
                            <span>Trend (simulato)</span>
                        </div>
                    </div>

                    <!-- Leads -->
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-title">Lead Generati</div>
                            <div class="kpi-value"><?php echo number_format($totalLeads); ?></div>
                        </div>
                        <div class="kpi-trend up">
                            <span>Ottimo lavoro!</span>
                        </div>
                    </div>

                    <!-- Conversion Rate -->
                    <div class="kpi-card">
                        <div>
                            <div class="kpi-title">Tasso di Conversione</div>
                            <div class="kpi-value"><?php echo $conversionRate; ?>%</div>
                        </div>
                        <div class="kpi-trend <?php echo $conversionRate > 2 ? 'up' : 'neutral'; ?>">
                            <span>Target: > 2%</span>
                        </div>
                    </div>
                </div>

                <!-- Main Chart -->
                <div class="chart-section">
                    <div class="section-header">
                        <h3 class="section-title">Andamento Traffico & Lead</h3>
                        <span class="date-range-badge">Ultimi 30 Giorni</span>
                    </div>
                    <div style="height: 300px; width: 100%;">
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>

                <!-- Recent Activity Table -->
                <div class="content-card">
                    <h3>Dettaglio Giornaliero</h3>
                    <div class="table-responsive">
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid #f1f5f9; text-align: left;">
                                    <th style="padding: 1rem;">Data</th>
                                    <th style="padding: 1rem;">Visite</th>
                                    <th style="padding: 1rem;">Pagine Viste</th>
                                    <th style="padding: 1rem;">Lead</th>
                                    <th style="padding: 1rem;">CR %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($stats)): ?>
                                    <tr>
                                        <td colspan="5" style="padding: 2rem; text-align: center; color: #94a3b8;">
                                            Nessun dato disponibile per questo periodo.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (array_reverse($stats) as $day): ?>
                                        <tr style="border-bottom: 1px solid #f8fafc;">
                                            <td style="padding: 1rem; color: #334155; font-weight:500;">
                                                <?php echo date('d M Y', strtotime($day['date'])); ?>
                                            </td>
                                            <td style="padding: 1rem; color: #64748b;">
                                                <?php echo number_format($day['visits']); ?>
                                            </td>
                                            <td style="padding: 1rem; color: #64748b;">
                                                <?php echo number_format($day['page_views'] ?? 0); ?>
                                            </td>
                                            <td style="padding: 1rem;">
                                                <?php if ($day['leads'] > 0): ?>
                                                    <span
                                                        style="background: #dcfce7; color: #166534; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">
                                                        <?php echo $day['leads']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #cbd5e1;">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 1rem; color: #64748b;">
                                                <?php
                                                $cr = $day['visits'] > 0 ? round(($day['leads'] / $day['visits']) * 100, 2) : 0;
                                                echo $cr . '%';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        const ctx = document.getElementById('trafficChart').getContext('2d');
        const trafficChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartDates); ?>,
                datasets: [
                    {
                        label: 'Visite',
                        data: <?php echo json_encode($chartVisits); ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Lead',
                        data: <?php echo json_encode($chartLeads); ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false,
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>