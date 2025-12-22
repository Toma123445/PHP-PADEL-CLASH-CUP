<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout.php';

$pdo = get_pdo();

// Obține clasamentul pentru competiția activă
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

$standings = [];
if ($competition) {
    $stmt = $pdo->prepare('
        SELECT 
            e.id_echipa,
            e.nume_echipa,
            c.meciuri_jucate,
            c.puncte,
            c.gameuri_plus,
            c.gameuri_minus,
            (c.gameuri_plus - c.gameuri_minus) as diferenta_gameuri
        FROM clasament c
        INNER JOIN echipe e ON e.id_echipa = c.id_echipa
        WHERE c.id_competitie = :comp_id
        ORDER BY c.puncte DESC, diferenta_gameuri DESC, c.gameuri_plus DESC
    ');
    
    $stmt->execute([':comp_id' => (int)$competition['id_competitie']]);
    $standings = $stmt->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clasament - Smash Cup 5x5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php render_nav('clasament'); ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Clasament</h1>
            <?php if ($competition): ?>
                <p class="text-muted mb-0"><?= escape_html($competition['nume']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php display_flashes(); ?>
    
    <?php if (empty($standings)): ?>
        <div class="alert alert-info">
            Nu există date de clasament disponibile.
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Poz.</th>
                                <th>Echipă</th>
                                <th class="text-center">Meciuri</th>
                                <th class="text-center">Puncte</th>
                                <th class="text-center">Gameuri +</th>
                                <th class="text-center">Gameuri -</th>
                                <th class="text-center">Diferență</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $position = 1; foreach ($standings as $standing): ?>
                                <tr>
                                    <td><strong><?= $position++ ?></strong></td>
                                    <td><?= escape_html($standing['nume_echipa']) ?></td>
                                    <td class="text-center"><?= (int)$standing['meciuri_jucate'] ?></td>
                                    <td class="text-center"><strong><?= (int)$standing['puncte'] ?></strong></td>
                                    <td class="text-center"><?= (int)$standing['gameuri_plus'] ?></td>
                                    <td class="text-center"><?= (int)$standing['gameuri_minus'] ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $standing['diferenta_gameuri'] >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $standing['diferenta_gameuri'] >= 0 ? '+' : '' ?><?= (int)$standing['diferenta_gameuri'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <h5 class="h6">Criterii de departajare:</h5>
            <ol class="small text-muted">
                <li>Puncte (1 punct per set câștigat)</li>
                <li>Diferența de game-uri</li>
                <li>Total game-uri câștigate</li>
            </ol>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

