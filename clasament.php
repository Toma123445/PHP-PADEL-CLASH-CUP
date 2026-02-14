<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/config.php';

define('APP_URL', APP_URL);

$pdo = get_db();

// Get standings
$clasament = [];
try {
    $stmt = $pdo->query("
        SELECT c.*,
               e.nume_echipa,
               (c.gameuri_plus - c.gameuri_minus) as diferenta_gameuri
        FROM clasament c
        JOIN echipe e ON c.id_echipa = e.id_echipa
        ORDER BY c.puncte DESC, diferenta_gameuri DESC, c.gameuri_plus DESC
    ");
    $clasament = $stmt->fetchAll();
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
    <title>Clasament - <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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

        <h1 class="mb-4"><i class="bi bi-trophy"></i> Clasament</h1>

        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Poziție</th>
                                        <th>Echipă</th>
                                        <th class="text-center">Puncte</th>
                                        <th class="text-center">Meciuri</th>
                                        <th class="text-center">Gameuri +</th>
                                        <th class="text-center">Gameuri -</th>
                                        <th class="text-center">Diferență</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($clasament)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-5">
                                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                                Clasamentul este gol. Așteptați rezultatele primelor meciuri.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($clasament as $index => $echipa): ?>
                                        <tr>
                                            <td>
                                                <strong>
                                                    <?php if ($index < 3): ?>
                                                        <span class="badge bg-<?= $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'danger') ?>">
                                                            <?= $index + 1 ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <?= $index + 1 ?>
                                                    <?php endif; ?>
                                                </strong>
                                            </td>
                                            <td><strong><?= htmlspecialchars($echipa['nume_echipa']) ?></strong></td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?= $echipa['puncte'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?= ($echipa['meciuri_castigate'] ?? 0) + ($echipa['meciuri_pierdute'] ?? 0) ?>
                                            </td>
                                            <td class="text-center text-success">+<?= $echipa['gameuri_plus'] ?></td>
                                            <td class="text-center text-danger">-<?= $echipa['gameuri_minus'] ?></td>
                                            <td class="text-center">
                                                <strong class="<?= $echipa['diferenta_gameuri'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                    <?= $echipa['diferenta_gameuri'] >= 0 ? '+' : '' ?><?= $echipa['diferenta_gameuri'] ?>
                                                </strong>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bar-chart-fill"></i> Grafic Puncte</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="pointsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Grafic puncte
        const ctx = document.getElementById('pointsChart');
        if (ctx) {
            const teams = <?= json_encode(array_column($clasament, 'nume_echipa')) ?>;
            const points = <?= json_encode(array_column($clasament, 'puncte')) ?>;
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: teams,
                    datasets: [{
                        label: 'Puncte',
                        data: points,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>

