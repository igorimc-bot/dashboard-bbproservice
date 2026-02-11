<?php
require_once __DIR__ . '/auth.php';
requireLogin();

if (!isSuperAdmin()) {
    header("Location: index.php");
    exit;
}

$db = getDB();
$error = '';
$success = '';

// Handle Add Site
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_site'])) {
    $name = $_POST['name'] ?? '';
    $url = $_POST['url'] ?? '';
    $ownerId = $_POST['owner_id'] ?? $_SESSION['user_id'];

    if ($name && $url) {
        try {
            $stmt = $db->prepare("INSERT INTO sites (name, url, owner_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $url, $ownerId]);
            $success = 'Sito aggiunto con successo!';
        } catch (PDOException $e) {
            $error = 'Errore durante l\'aggiunta: ' . $e->getMessage();
        }
    } else {
        $error = 'Nome e URL sono obbligatori.';
    }
}

// Fetch users for the owner selection
$users = $db->query("SELECT id, username FROM users")->fetchAll();

// Fetch sites for management
$sites = $db->query("SELECT s.*, u.username as owner_name FROM sites s JOIN users u ON s.owner_id = u.id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Siti -
        <?php echo SITE_NAME; ?>
    </title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
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
                    <li><a href="manage_sites.php" class="active">
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
                            <span class="role">Super Admin</span>
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

            <!-- Page Content -->
            <div class="content-wrapper">
                <div class="page-title-area">
                    <div class="title-left">
                        <h2>Gestione Siti</h2>
                        <p>Manage users and sites configuration.</p>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="alert"
                        style="background: rgba(34, 197, 94, 0.1); border: 1px solid var(--success); color: var(--success);">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
                    <!-- Add Site Card -->
                    <div class="stat-card" style="padding: 1.5rem; height: auto;">
                        <h3 style="margin-bottom: 1rem;">Aggiungi Nuovo Sito</h3>
                        <form method="POST">
                            <input type="hidden" name="add_site" value="1">
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label style="display:block; margin-bottom: 0.5rem; font-weight:600;">Nome Sito</label>
                                <input type="text" name="name" placeholder="es. Ohana Lab" required
                                    style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; outline: none;">
                            </div>
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label style="display:block; margin-bottom: 0.5rem; font-weight:600;">URL Sito</label>
                                <input type="url" name="url" placeholder="https://..." required
                                    style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; outline: none;">
                            </div>
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label style="display:block; margin-bottom: 0.5rem; font-weight:600;">Assegna a
                                    Utente</label>
                                <select name="owner_id"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; outline: none;">
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" <?php echo $user['id'] == $_SESSION['user_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%;">Aggiungi Sito</button>
                        </form>
                    </div>

                    <!-- Existing Sites Card -->
                    <div class="stat-card" style="padding: 1.5rem; height: auto;">
                        <h3 style="margin-bottom: 1rem;">Siti Esistenti</h3>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid #e2e8f0; text-align: left;">
                                    <th style="padding: 0.75rem;">Nome</th>
                                    <th style="padding: 0.75rem;">Utente</th>
                                    <th style="padding: 0.75rem;">ID Tracciamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sites as $site): ?>
                                    <tr style="border-bottom: 1px solid #f8f9fa;">
                                        <td style="padding: 0.75rem; font-weight: 500;">
                                            <?php echo htmlspecialchars($site['name']); ?>
                                            <div style="font-size: 0.8rem; color: #888; font-weight: 400;">
                                                <?php echo htmlspecialchars($site['url']); ?></div>
                                        </td>
                                        <td style="padding: 0.75rem;">
                                            <span
                                                style="background: #e0f3ff; color: #3f6ad8; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">
                                                <?php echo htmlspecialchars($site['owner_name']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem;">
                                            <code
                                                style="background: #f1f4f6; color: #d63384; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.9rem;">site_id=<?php echo $site['id']; ?></code>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="stat-card"
                    style="margin-top: 2rem; padding: 1.5rem; border: 1px dashed #3f6ad8; background: #f8fbff;">
                    <div
                        style="display:flex; align-items:center; gap: 0.5rem; margin-bottom: 0.5rem; color: #3f6ad8; font-weight: 600;">
                        <span>🔧</span> Come tracciare i siti
                    </div>
                    <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1rem;">Inserisci questo codice
                        JavaScript nei siti remoti:</p>
                    <pre
                        style="background: #1e293b; color: #e2e8f0; padding: 1.5rem; border-radius: 0.5rem; overflow-x: auto; font-size: 0.9rem;">
// Tracciamento Visita (caricamento pagina)
fetch('<?php echo BASE_URL; ?>api.php?site_id=ID_SITO&type=visit');

// Tracciamento Lead (al submit del form)
// Aggiungi questo nella funzione di successo del tuo form
fetch('<?php echo BASE_URL; ?>api.php?site_id=ID_SITO&type=lead');</pre>
                </div>
            </div>
        </main>
    </div>
</body>

</html>