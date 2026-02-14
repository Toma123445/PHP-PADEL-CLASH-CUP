<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/config.php';

define('APP_URL', APP_URL);

$pdo = get_db();

// Get teams
$echipe = [];
try {
    $stmt = $pdo->query("
        SELECT e.*, 
               COUNT(j.id_jucator) as numar_jucatori,
               d.nume_divizie
        FROM echipe e
        LEFT JOIN jucatori j ON e.id_echipa = j.id_echipa
        LEFT JOIN divizii d ON e.divizie_principala = d.id_divizie
        GROUP BY e.id_echipa
        ORDER BY e.nume_echipa
    ");
    $echipe = $stmt->fetchAll();
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
    <title>Echipe - <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container my-5">
        <?php foreach ($flashes as $flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-people"></i> Echipe</h1>
            <a href="inscriere.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Înscrie Echipă
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nume Echipă</th>
                                <th>Capitan</th>
                                <th>Email</th>
                                <th>Jucători</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($echipe)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Nu există echipe înscrise.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($echipe as $index => $echipa): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($echipa['nume_echipa']) ?></strong></td>
                                    <td><?= htmlspecialchars($echipa['capitan']) ?></td>
                                    <td><?= htmlspecialchars($echipa['email_capitan']) ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= $echipa['numar_jucatori'] ?> jucători</span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($echipa['status']) {
                                            'validat' => 'bg-success',
                                            'respins' => 'bg-danger',
                                            default => 'bg-warning text-dark'
                                        };
                                        ?>
                                        <span class="badge <?= $statusClass ?>">
                                            <?= ucfirst($echipa['status']) ?>
                                        </span>
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

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

