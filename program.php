<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout.php';

$pdo = get_pdo();

// Obține competiția activă
$competition = $pdo->query('
    SELECT id_competitie, nume FROM competitii 
    WHERE status = "in_desfasurare" 
    ORDER BY id_competitie DESC 
    LIMIT 1
')->fetch();

if (!$competition) {
    $competition = $pdo->query('
        SELECT id_competitie, nume FROM competitii 
        ORDER BY id_competitie DESC 
        LIMIT 1
    ')->fetch();
}

$matches = [];
if ($competition) {
    $stmt = $pdo->prepare('
        SELECT 
            m.id_meci,
            m.data_meci,
            m.locatie,
            m.faza,
            m.status,
            ea.nume_echipa as echipa_a,
            eb.nume_echipa as echipa_b,
            g.nume as nume_grupa
        FROM meciuri m
        INNER JOIN echipe ea ON ea.id_echipa = m.id_echipa_a
        INNER JOIN echipe eb ON eb.id_echipa = m.id_echipa_b
        LEFT JOIN grupe g ON g.id_grupa = m.id_grupa
        WHERE m.id_competitie = :comp_id
        ORDER BY m.data_meci DESC, m.id_meci DESC
    ');
    
    $stmt->execute([':comp_id' => (int)$competition['id_competitie']]);
    $matches = $stmt->fetchAll();
}

// Grupează meciurile după status
$programmed = array_filter($matches, fn($m) => $m['status'] === 'programat');
$inProgress = array_filter($matches, fn($m) => $m['status'] === 'in_desfasurare');
$finished = array_filter($matches, fn($m) => $m['status'] === 'finalizat');

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program și Rezultate - Smash Cup 5x5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php render_nav('program'); ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Program și Rezultate</h1>
            <?php if ($competition): ?>
                <p class="text-muted mb-0"><?= escape_html($competition['nume']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php display_flashes(); ?>
    
    <?php if (empty($matches)): ?>
        <div class="alert alert-info">
            Nu există meciuri programate.
        </div>
    <?php else: ?>
        <!-- Meciuri în desfășurare -->
        <?php if (!empty($inProgress)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Meciuri în desfășurare</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($inProgress as $match): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= escape_html($match['echipa_a']) ?> vs <?= escape_html($match['echipa_b']) ?></h6>
                                    <small class="text-muted">
                                        <?= $match['nume_grupa'] ? escape_html($match['nume_grupa']) . ' - ' : '' ?>
                                        <?= $match['faza'] ? escape_html($match['faza']) : 'Grupa' ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <?php if ($match['data_meci']): ?>
                                        <small class="text-muted d-block"><?= date('d.m.Y H:i', strtotime($match['data_meci'])) ?></small>
                                    <?php endif; ?>
                                    <?php if ($match['locatie']): ?>
                                        <small class="text-muted"><?= escape_html($match['locatie']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Meciuri programate -->
        <?php if (!empty($programmed)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Meciuri programate</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($programmed as $match): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= escape_html($match['echipa_a']) ?> vs <?= escape_html($match['echipa_b']) ?></h6>
                                    <small class="text-muted">
                                        <?= $match['nume_grupa'] ? escape_html($match['nume_grupa']) . ' - ' : '' ?>
                                        <?= $match['faza'] ? escape_html($match['faza']) : 'Grupa' ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <?php if ($match['data_meci']): ?>
                                        <small class="text-muted d-block"><?= date('d.m.Y H:i', strtotime($match['data_meci'])) ?></small>
                                    <?php endif; ?>
                                    <?php if ($match['locatie']): ?>
                                        <small class="text-muted"><?= escape_html($match['locatie']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Meciuri finalizate -->
        <?php if (!empty($finished)): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Rezultate</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($finished as $match): ?>
                        <?php
                        // Obține seturile pentru meci
                        $setsStmt = $pdo->prepare('
                            SELECT numar_set, gameuri_a, gameuri_b
                            FROM seturi
                            WHERE id_meci = :meci_id
                            ORDER BY numar_set
                        ');
                        $setsStmt->execute([':meci_id' => (int)$match['id_meci']]);
                        $sets = $setsStmt->fetchAll();
                        
                        $pointsA = 0;
                        $pointsB = 0;
                        foreach ($sets as $set) {
                            if ((int)$set['gameuri_a'] > (int)$set['gameuri_b']) {
                                $pointsA++;
                            } elseif ((int)$set['gameuri_b'] > (int)$set['gameuri_a']) {
                                $pointsB++;
                            }
                        }
                        ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <h6 class="mb-1"><?= escape_html($match['echipa_a']) ?></h6>
                                    <small class="text-muted">
                                        <?= $match['nume_grupa'] ? escape_html($match['nume_grupa']) . ' - ' : '' ?>
                                        <?= $match['faza'] ? escape_html($match['faza']) : 'Grupa' ?>
                                    </small>
                                </div>
                                <div class="col-md-4 text-center">
                                    <h5 class="mb-0">
                                        <span class="<?= $pointsA > $pointsB ? 'fw-bold text-success' : '' ?>"><?= $pointsA ?></span>
                                        -
                                        <span class="<?= $pointsB > $pointsA ? 'fw-bold text-success' : '' ?>"><?= $pointsB ?></span>
                                    </h5>
                                    <?php if (!empty($sets)): ?>
                                        <small class="text-muted">
                                            <?php foreach ($sets as $set): ?>
                                                <?= (int)$set['gameuri_a'] ?>-<?= (int)$set['gameuri_b'] ?>
                                                <?= $set !== end($sets) ? ', ' : '' ?>
                                            <?php endforeach; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 text-end">
                                    <h6 class="mb-1"><?= escape_html($match['echipa_b']) ?></h6>
                                    <?php if ($match['data_meci']): ?>
                                        <small class="text-muted"><?= date('d.m.Y', strtotime($match['data_meci'])) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

