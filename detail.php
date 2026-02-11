<?php
require_once 'auth.php';
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
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettaglio
        <?php echo htmlspecialchars($site['name']); ?> -
        <?php echo SITE_NAME; ?>
    </title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <nav class="navbar">
        <a href="index.php" class="navbar-brand">Dashboard Stats</a>
        <div class="navbar-user">
            <a href="index.php" class="btn btn-sm" style="display:inline; margin-right: 10px;">Torna alla Dashboard</a>
            <a href="logout.php" class="btn btn-sm" style="display:inline; background: rgba(255,255,255,0.1)">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h1>Dettaglio Sito:
            <?php echo htmlspecialchars($site['name']); ?>
        </h1>

        <div class="alert"
            style="background: rgba(59, 130, 246, 0.1); border: 1px solid var(--primary-color); padding: 2rem; text-align: center;">
            <h2 style="margin-bottom: 1rem;">Work in Progress - Fase 2</h2>
            <p>La pagina di dettaglio con grafici avanzati e analisi dei lead sarà sviluppata nel prossimo step.</p>
            <p style="margin-top: 1rem; color: var(--text-secondary);">Vai alla <a href="index.php"
                    style="color: var(--primary-color)">Dashboard principale</a> per vedere le statistiche attuali.</p>
        </div>
    </div>
</body>

</html>