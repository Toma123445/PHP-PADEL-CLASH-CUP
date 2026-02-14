<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/config.php';

define('APP_URL', APP_URL);

$pdo = get_db();

// Get matches
$meciuri = [];
try {
    $stmt = $pdo->query("
        SELECT m.*,
               ea.nume_echipa as echipa_a_nume,
               eb.nume_echipa as echipa_b_nume,
               g.nume as grupa_nume
        FROM meciuri m
        LEFT JOIN echipe ea ON m.id_echipa_a = ea.id_echipa
        LEFT JOIN echipe eb ON m.id_echipa_b = eb.id_echipa
        LEFT JOIN grupe g ON m.id_grupa = g.id_grupa
        ORDER BY m.data_meci DESC, m.id_meci DESC
    ");
    $meciuri = $stmt->fetchAll();
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
    <title>Meciuri - <?= htmlspecialchars(APP_NAME) ?></title>
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

        <h1 class="mb-4"><i class="bi bi-calendar3"></i> Meciuri</h1>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Echipă A</th>
                                <th>vs</th>
                                <th>Echipă B</th>
                                <th>Fază</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($meciuri)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Nu există meciuri programate.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($meciuri as $meci): ?>
                                <tr>
                                    <td>
                                        <?php if ($meci['data_meci']): ?>
                                            <?= date('d.m.Y H:i', strtotime($meci['data_meci'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($meci['echipa_a_nume'] ?? 'N/A') ?></strong></td>
                                    <td class="text-center">vs</td>
                                    <td><strong><?= htmlspecialchars($meci['echipa_b_nume'] ?? 'N/A') ?></strong></td>
                                    <td>
                                        <?php if ($meci['grupa_nume']): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($meci['grupa_nume']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-info"><?= htmlspecialchars($meci['faza']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($meci['status']) {
                                            'finalizat' => 'bg-success',
                                            'in_desfasurare' => 'bg-warning text-dark',
                                            default => 'bg-secondary'
                                        };
                                        $statusText = match($meci['status']) {
                                            'finalizat' => 'Finalizat',
                                            'in_desfasurare' => 'În desfășurare',
                                            default => 'Programat'
                                        };
                                        ?>
                                        <span class="badge <?= $statusClass ?>">
                                            <?= $statusText ?>
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

