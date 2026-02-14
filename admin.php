<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/config.php';

define('APP_URL', APP_URL);

$pdo = get_db();

// Simple admin authentication (in production, use proper session-based auth)
$is_admin = false;
if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM utilizatori WHERE username = ? AND rol = 'admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['parola'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['id_utilizator'];
            $is_admin = true;
        } else {
            flash('danger', 'Credențiale invalide.');
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        flash('danger', 'Eroare la autentificare.');
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_user_id']);
    redirect('admin.php');
}

$is_admin = $_SESSION['admin_logged_in'] ?? false;

if (!$is_admin) {
    $flashes = get_flashes();
    ?>
    <!DOCTYPE html>
    <html lang="ro">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - <?= htmlspecialchars(APP_NAME) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="css/style.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="mb-0">Admin Login</h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($flashes as $flash): ?>
                                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
                                    <?= htmlspecialchars($flash['message']) ?>
                                </div>
                            <?php endforeach; ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Parolă</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                            </form>
                            <div class="mt-3 text-center">
                                <a href="index.php">← Înapoi la site</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Admin dashboard
$stats = [];
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM echipe WHERE status = 'pending'");
    $stats['pending_teams'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM mesaje_contact WHERE status = 'nou'");
    $stats['new_messages'] = $stmt->fetch()['total'] ?? 0;
} catch (PDOException $e) {
    error_log($e->getMessage());
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Token CSRF invalid.');
    } else {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'validate_team':
                $id = (int)($_POST['team_id'] ?? 0);
                $stmt = $pdo->prepare("UPDATE echipe SET status = 'validat' WHERE id_echipa = ?");
                $stmt->execute([$id]);
                flash('success', 'Echipa a fost validată.');
                break;
                
            case 'reject_team':
                $id = (int)($_POST['team_id'] ?? 0);
                $stmt = $pdo->prepare("UPDATE echipe SET status = 'respins' WHERE id_echipa = ?");
                $stmt->execute([$id]);
                flash('success', 'Echipa a fost respinsă.');
                break;
        }
    }
    redirect('admin.php');
}

// Get pending teams
$pending_teams = [];
try {
    $stmt = $pdo->query("
        SELECT e.*, COUNT(j.id_jucator) as numar_jucatori
        FROM echipe e
        LEFT JOIN jucatori j ON e.id_echipa = j.id_echipa
        WHERE e.status = 'pending'
        GROUP BY e.id_echipa
        ORDER BY e.created_at DESC
    ");
    $pending_teams = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
}

$flashes = get_flashes();

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">Admin Panel</span>
            <a href="admin.php?logout=1" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>

    <main class="container my-5">
        <?php foreach ($flashes as $flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

        <h1 class="mb-4">Panou Administrare</h1>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5>Echipe în așteptare</h5>
                        <h2 class="text-warning"><?= $stats['pending_teams'] ?? 0 ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5>Mesaje noi</h5>
                        <h2 class="text-info"><?= $stats['new_messages'] ?? 0 ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Echipe în Așteptare Validare</h3>
            </div>
            <div class="card-body">
                <?php if (empty($pending_teams)): ?>
                    <p class="text-muted">Nu există echipe în așteptare.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Echipă</th>
                                    <th>Capitan</th>
                                    <th>Email</th>
                                    <th>Jucători</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_teams as $team): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($team['nume_echipa']) ?></strong></td>
                                    <td><?= htmlspecialchars($team['capitan']) ?></td>
                                    <td><?= htmlspecialchars($team['email_capitan']) ?></td>
                                    <td><?= $team['numar_jucatori'] ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="team_id" value="<?= $team['id_echipa'] ?>">
                                            <button type="submit" name="action" value="validate_team" 
                                                    class="btn btn-sm btn-success">Validează</button>
                                            <button type="submit" name="action" value="reject_team" 
                                                    class="btn btn-sm btn-danger">Respinge</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">← Înapoi la site</a>
            <a href="import.php" class="btn btn-primary">Import Echipe</a>
            <a href="export.php?format=excel&type=echipe" class="btn btn-success">Export Excel</a>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

