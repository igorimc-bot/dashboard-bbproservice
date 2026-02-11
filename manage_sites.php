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
        <h1>Gestione Siti</h1>

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
            <div class="card"
                style="background: var(--card-bg); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--border-color);">
                <h2>Aggiungi Nuovo Sito</h2>
                <form method="POST">
                    <input type="hidden" name="add_site" value="1">
                    <div class="form-group">
                        <label>Nome Sito</label>
                        <input type="text" name="name" placeholder="es. Ohana Lab" required>
                    </div>
                    <div class="form-group">
                        <label>URL Sito</label>
                        <input type="url" name="url" placeholder="https://..." required>
                    </div>
                    <div class="form-group">
                        <label>Assegna a Utente</label>
                        <select name="owner_id"
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; background: rgba(15, 23, 42, 0.6); color: white; border: 1px solid var(--border-color);">
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $user['id'] == $_SESSION['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Aggiungi Sito</button>
                </form>
            </div>

            <div class="card"
                style="background: var(--card-bg); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--border-color);">
                <h2>Siti Esistenti</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                            <th style="padding: 1rem;">Nome</th>
                            <th style="padding: 1rem;">Utente</th>
                            <th style="padding: 1rem;">ID Tracciamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sites as $site): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 1rem;">
                                    <?php echo htmlspecialchars($site['name']); ?>
                                </td>
                                <td style="padding: 1rem;">
                                    <?php echo htmlspecialchars($site['owner_name']); ?>
                                </td>
                                <td style="padding: 1rem;"><code
                                        style="background: rgba(0,0,0,0.3); padding: 0.2rem 0.5rem; border-radius: 0.3rem;">site_id=<?php echo $site['id']; ?></code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card"
            style="margin-top: 2rem; background: rgba(59, 130, 246, 0.05); padding: 1.5rem; border-radius: 0.75rem; border: 1px dashed var(--primary-color);">
            <h3>Come tracciare i siti</h3>
            <p style="margin-top: 0.5rem; color: var(--text-secondary);">Inserisci questo codice nei siti remoti:</p>
            <pre
                style="background: #000; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem; overflow-x: auto; color: #a5b4fc;">
// Tracciamento Visita (caricamento pagina)
fetch('<?php echo BASE_URL; ?>api.php?site_id=ID_SITO&type=visit');

// Tracciamento Lead (al submit del form)
// Aggiungi questo nella funzione di successo del tuo form
fetch('<?php echo BASE_URL; ?>api.php?site_id=ID_SITO&type=lead');
            </pre>
        </div>
    </div>
</body>

</html>