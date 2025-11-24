<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout.php';

$pdo = get_pdo();
$user = current_user();

$teamCount = (int)$pdo->query('SELECT COUNT(*) FROM echipe')->fetchColumn();
$playerCount = (int)$pdo->query('SELECT COUNT(*) FROM jucatori')->fetchColumn();
$latestTeams = $pdo->query('SELECT nume_echipa, data_inscriere FROM echipe ORDER BY data_inscriere DESC LIMIT 5')->fetchAll();

$playerInfo = null;
if ($user) {
    $stmt = $pdo->prepare('
        SELECT j.*, e.nume_echipa, d.nume_divizie
        FROM jucatori j
        LEFT JOIN echipe e ON e.id_echipa = j.id_echipa
        LEFT JOIN divizii d ON d.id_divizie = j.id_divizie
        WHERE j.user_id = :user_id
    ');
    $stmt->execute([':user_id' => $user['id']]);
    $playerInfo = $stmt->fetch();
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smash Cup 5x5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php render_nav('dashboard'); ?>
<div class="container py-5">
    <?php display_flashes(); ?>
    <?php if (!$user): ?>
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <h1 class="display-6 mb-3">Bun venit la Smash Cup 5x5</h1>
                <p class="lead">Gestioneaza echipele si jucatorii turneului de padel intr-o singura aplicatie.</p>
                <div class="d-flex gap-3">
                    <a href="login.php" class="btn btn-primary btn-lg">Login</a>
                    <a href="register.php" class="btn btn-outline-secondary btn-lg">Creeaza cont</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Statistici rapide</h5>
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="text-muted text-uppercase small">Echipe</div>
                                <div class="fs-3 fw-semibold"><?= $teamCount ?></div>
                            </div>
                            <div>
                                <div class="text-muted text-uppercase small">Jucatori</div>
                                <div class="fs-3 fw-semibold"><?= $playerCount ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Total echipe</p>
                        <h2 class="display-6"><?= $teamCount ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Total jucatori</p>
                        <h2 class="display-6"><?= $playerCount ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted mb-1">Contul tau</p>
                        <h2 class="h4 mb-2"><?= htmlspecialchars($user['nume'] . ' ' . $user['prenume']) ?></h2>
                        <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($user['role']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (is_admin()): ?>
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <h5>Echipe</h5>
                            <p class="text-muted flex-grow-1">Creeaza si actualizeaza echipele participante.</p>
                            <a href="echipe.php" class="btn btn-primary">Gestioneaza echipe</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <h5>Jucatori</h5>
                            <p class="text-muted flex-grow-1">Adauga sau atribuie jucatori catre echipe.</p>
                            <a href="jucatori.php" class="btn btn-primary">Gestioneaza jucatori</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">Echipe recente</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Nume</th>
                            <th>Inscrisa la</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$latestTeams): ?>
                            <tr><td colspan="2" class="text-center text-muted py-3">Nicio echipa inregistrata.</td></tr>
                        <?php else: ?>
                            <?php foreach ($latestTeams as $team): ?>
                                <tr>
                                    <td><?= htmlspecialchars($team['nume_echipa']) ?></td>
                                    <td><?= htmlspecialchars($team['data_inscriere']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title">Informatii personale</h5>
                            <?php if ($playerInfo): ?>
                                <ul class="list-unstyled mb-0">
                                    <li><strong>Divizie:</strong> <?= htmlspecialchars($playerInfo['nume_divizie'] ?? '-') ?></li>
                                    <li><strong>Echipa:</strong> <?= htmlspecialchars($playerInfo['nume_echipa'] ?? 'In asteptare') ?></li>
                                    <li><strong>Telefon:</strong> <?= htmlspecialchars($playerInfo['telefon'] ?? '-') ?></li>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted">Nu exista inca un profil de jucator asociat.</p>
                            <?php endif; ?>
                            <a href="profil.php" class="btn btn-outline-primary mt-3">Actualizeaza profilul</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <h5>Notificari</h5>
                            <p class="text-muted flex-grow-1">Asteapta confirmarea administratorului pentru atribuirea la o echipa.</p>
                            <a href="profil.php" class="btn btn-primary">Vezi profil</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

