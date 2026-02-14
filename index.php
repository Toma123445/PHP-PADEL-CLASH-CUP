<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/external_data.php';
require_once __DIR__ . '/config.php';

define('APP_URL', APP_URL);

$pdo = get_db();

// Get statistics
$stats = [
    'total_teams' => 0,
    'total_matches' => 0,
    'completed_matches' => 0,
    'upcoming_matches' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM echipe WHERE status = 'validat'");
    $stats['total_teams'] = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM meciuri");
    $stats['total_matches'] = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM meciuri WHERE status = 'finalizat'");
    $stats['completed_matches'] = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM meciuri WHERE status = 'programat'");
    $stats['upcoming_matches'] = $stmt->fetch()['total'] ?? 0;
} catch (PDOException $e) {
    error_log($e->getMessage());
}

// Get external data
$weather = parse_external_data('weather', get_weather_data());
$news = parse_external_data('news', get_sports_news(3));

$flashes = get_flashes();

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> - Acasă</title>
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

        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-4 text-center mb-4">Bun venit la <?= htmlspecialchars(APP_NAME) ?></h1>
                <p class="lead text-center">Sistem de management pentru turneul de padel 5x5</p>
            </div>
        </div>

        <!-- Statistici -->
        <div class="row mb-5">
            <div class="col-md-3 mb-3">
                <div class="card text-center border-primary">
                    <div class="card-body">
                        <i class="bi bi-people-fill fs-1 text-primary"></i>
                        <h3 class="mt-2"><?= $stats['total_teams'] ?></h3>
                        <p class="text-muted mb-0">Echipe</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <i class="bi bi-trophy-fill fs-1 text-success"></i>
                        <h3 class="mt-2"><?= $stats['total_matches'] ?></h3>
                        <p class="text-muted mb-0">Meciuri</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center border-info">
                    <div class="card-body">
                        <i class="bi bi-check-circle-fill fs-1 text-info"></i>
                        <h3 class="mt-2"><?= $stats['completed_matches'] ?></h3>
                        <p class="text-muted mb-0">Finalizate</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <i class="bi bi-calendar-event-fill fs-1 text-warning"></i>
                        <h3 class="mt-2"><?= $stats['upcoming_matches'] ?></h3>
                        <p class="text-muted mb-0">Programate</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafic statistici -->
        <div class="row mb-5">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bar-chart-fill"></i> Statistici Competiție</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statsChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-cloud-sun-fill"></i> Vremea</h5>
                    </div>
                    <div class="card-body">
                        <h3><?= htmlspecialchars($weather['temperature'] ?? 0) ?>°C</h3>
                        <p class="text-muted"><?= htmlspecialchars($weather['condition'] ?? 'Necunoscut') ?></p>
                        <p><small>Umiditate: <?= htmlspecialchars($weather['humidity'] ?? 0) ?>%</small></p>
                        <p><small>Vânt: <?= htmlspecialchars($weather['wind_speed'] ?? 0) ?> km/h</small></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stiri externe -->
        <?php if (!empty($news)): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-newspaper"></i> Știri Sportive</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($news as $item): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title"><?= htmlspecialchars($item['title']) ?></h6>
                                        <p class="card-text text-muted small"><?= htmlspecialchars($item['description']) ?></p>
                                        <small class="text-muted"><?= htmlspecialchars($item['date']) ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Linkuri rapide -->
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-pencil-square fs-1 text-primary"></i>
                        <h5 class="mt-3">Înscriere Echipă</h5>
                        <p class="text-muted">Înscrie echipa ta în competiție</p>
                        <a href="inscriere.php" class="btn btn-primary">Înscrie-te</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-list-ol fs-1 text-success"></i>
                        <h5 class="mt-3">Clasament</h5>
                        <p class="text-muted">Vezi clasamentul actual</p>
                        <a href="clasament.php" class="btn btn-success">Vezi Clasament</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar3 fs-1 text-info"></i>
                        <h5 class="mt-3">Meciuri</h5>
                        <p class="text-muted">Program și rezultate</p>
                        <a href="meciuri.php" class="btn btn-info">Vezi Meciuri</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Grafic statistici
        const ctx = document.getElementById('statsChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Echipe', 'Meciuri Total', 'Finalizate', 'Programate'],
                    datasets: [{
                        label: 'Statistici',
                        data: [
                            <?= $stats['total_teams'] ?>,
                            <?= $stats['total_matches'] ?>,
                            <?= $stats['completed_matches'] ?>,
                            <?= $stats['upcoming_matches'] ?>
                        ],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(153, 102, 255, 0.6)',
                            'rgba(255, 159, 64, 0.6)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>

