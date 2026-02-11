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
    <nav class="navbar">
        <a href="index.php" class="navbar-brand">Dashboard Stats</a>
        <div class="navbar-user">
            <span>Ciao, <strong>
                    <?php echo $_SESSION['username']; ?>
                </strong></span>
            <?php if ($isSuperAdmin): ?>
                <a href="manage_sites.php" class="btn btn-sm" style="display:inline; margin-left: 10px;">Gestisci Siti</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-sm"
                style="display:inline; margin-left:10px; background: rgba(255,255,255,0.1)">Logout</a>
        </div>
    </nav>

    <div class="container">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1>Panoramica Siti</h1>
            <form id="dateFilter">
                <input type="date" name="date" value="<?php echo $dateSelection; ?>" onchange="this.form.submit()"
                    style="padding: 0.5rem; border-radius: 0.5rem; background: var(--card-bg); color: white; border: 1px solid var(--border-color);">
            </form>
        </header>

        <div class="site-list">
            <?php if (empty($sites)): ?>
                <div class="alert"
                    style="background: rgba(255,255,255,0.05); text-align: center; border: 1px dashed var(--border-color);">
                    Nessun sito trovato.
                    <?php if ($isSuperAdmin): ?><a href="manage_sites.php">Aggiungine uno ora.</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($sites as $site): ?>
                    <div class="site-card">
                        <div class="site-info">
                            <div class="site-name">
                                <?php echo htmlspecialchars($site['name']); ?>
                            </div>
                            <div class="site-url">
                                <?php echo htmlspecialchars($site['url']); ?>
                            </div>
                            <?php if ($isSuperAdmin): ?>
                                <small style="color: var(--primary-color)">Proprietario:
                                    <?php echo htmlspecialchars($site['owner_name']); ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="site-stat">
                            <span class="stat-label">Visite Oggi</span>
                            <div class="stat-value">
                                <?php echo number_format($site['today']['visits']); ?>
                            </div>
                            <div class="delta <?php echo $site['visit_delta'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($site['visit_delta'] >= 0 ? '+' : '') . $site['visit_delta']; ?> rispetto a ieri
                            </div>
                        </div>

                        <div class="site-stat">
                            <span class="stat-label">Lead Oggi</span>
                            <div class="stat-value">
                                <?php echo number_format($site['today']['leads']); ?>
                            </div>
                            <div class="delta <?php echo $site['lead_delta'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($site['lead_delta'] >= 0 ? '+' : '') . $site['lead_delta']; ?> rispetto a ieri
                            </div>
                        </div>

                        <div class="site-actions">
                            <a href="detail.php?id=<?php echo $site['id']; ?>" class="btn btn-primary"
                                style="padding: 0.5rem 1rem; font-size: 0.8rem;">Dettagli</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>