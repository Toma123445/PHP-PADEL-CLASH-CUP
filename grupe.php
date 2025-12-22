<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout.php';

require_admin();

$pdo = get_pdo();

// Obține competițiile
$competitions = $pdo->query('SELECT id_competitie, nume, sezon FROM competitii ORDER BY id_competitie DESC')->fetchAll();

// Obține echipele
$teams = $pdo->query('SELECT id_echipa, nume_echipa FROM echipe ORDER BY nume_echipa')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Token CSRF invalid.');
        redirect('grupe.php');
    }

    $payload = sanitize($_POST);
    $action = $payload['action'] ?? null;

    if ($action === 'create') {
        $errors = validate_required($payload, [
            'nume' => 'Nume grupă',
            'id_competitie' => 'Competiție'
        ]);

        if ($errors) {
            flash('danger', implode('<br>', $errors));
            redirect('grupe.php');
        }

        $stmt = $pdo->prepare('INSERT INTO grupe (id_competitie, nume) VALUES (:comp, :nume)');
        $stmt->execute([
            ':comp' => (int)$payload['id_competitie'],
            ':nume' => $payload['nume']
        ]);

        flash('success', 'Grupa a fost creată.');
        redirect('grupe.php');
    }

    if ($action === 'add_team') {
        $errors = validate_required($payload, [
            'id_grupa' => 'Grupă',
            'id_echipa' => 'Echipă'
        ]);

        if ($errors) {
            flash('danger', implode('<br>', $errors));
            redirect('grupe.php');
        }

        // Verifică dacă echipa nu este deja în grupă
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM grupe_echipe WHERE id_grupa = :grupa AND id_echipa = :echipa');
        $checkStmt->execute([
            ':grupa' => (int)$payload['id_grupa'],
            ':echipa' => (int)$payload['id_echipa']
        ]);

        if ($checkStmt->fetchColumn() > 0) {
            flash('danger', 'Echipa este deja în această grupă.');
            redirect('grupe.php');
        }

        $stmt = $pdo->prepare('INSERT INTO grupe_echipe (id_grupa, id_echipa) VALUES (:grupa, :echipa)');
        $stmt->execute([
            ':grupa' => (int)$payload['id_grupa'],
            ':echipa' => (int)$payload['id_echipa']
        ]);

        flash('success', 'Echipa a fost adăugată în grupă.');
        redirect('grupe.php');
    }

    if ($action === 'remove_team') {
        $stmt = $pdo->prepare('DELETE FROM grupe_echipe WHERE id_grupa = :grupa AND id_echipa = :echipa');
        $stmt->execute([
            ':grupa' => (int)$payload['id_grupa'],
            ':echipa' => (int)$payload['id_echipa']
        ]);

        flash('success', 'Echipa a fost eliminată din grupă.');
        redirect('grupe.php');
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM grupe WHERE id_grupa = :id');
        $stmt->execute([':id' => (int)$payload['id_grupa']]);

        flash('success', 'Grupa a fost ștearsă.');
        redirect('grupe.php');
    }
}

// Obține toate grupele cu echipele
$groupsStmt = $pdo->query('
    SELECT g.id_grupa, g.nume, c.nume as nume_competitie, c.id_competitie
    FROM grupe g
    INNER JOIN competitii c ON c.id_competitie = g.id_competitie
    ORDER BY c.id_competitie DESC, g.nume
');
$allGroups = $groupsStmt->fetchAll();

// Pentru fiecare grupă, obține echipele
foreach ($allGroups as &$group) {
    $teamsStmt = $pdo->prepare('
        SELECT e.id_echipa, e.nume_echipa
        FROM grupe_echipe ge
        INNER JOIN echipe e ON e.id_echipa = ge.id_echipa
        WHERE ge.id_grupa = :grupa
        ORDER BY e.nume_echipa
    ');
    $teamsStmt->execute([':grupa' => (int)$group['id_grupa']]);
    $group['echipe'] = $teamsStmt->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionare grupe - Smash Cup 5x5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php render_nav('grupe'); ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Gestionare grupe</h1>
            <p class="text-muted mb-0">Creează grupe și adaugă echipe în grupe</p>
        </div>
    </div>

    <?php display_flashes(); ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Creează grupă nouă</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="grupe.php">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="create">

                        <div class="mb-3">
                            <label for="nume" class="form-label">Nume grupă *</label>
                            <input type="text" class="form-control" id="nume" name="nume" required maxlength="50">
                        </div>

                        <div class="mb-3">
                            <label for="id_competitie" class="form-label">Competiție *</label>
                            <select class="form-select" id="id_competitie" name="id_competitie" required>
                                <option value="">Selectează competiția</option>
                                <?php foreach ($competitions as $comp): ?>
                                    <option value="<?= (int)$comp['id_competitie'] ?>">
                                        <?= escape_html($comp['nume']) ?> - <?= escape_html($comp['sezon']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Creează grupă</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <?php if (empty($allGroups)): ?>
                <div class="alert alert-info">
                    Nu există grupe create. Creează prima grupă.
                </div>
            <?php else: ?>
                <?php foreach ($allGroups as $group): ?>
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0"><?= escape_html($group['nume']) ?></h5>
                                <small class="text-muted"><?= escape_html($group['nume_competitie']) ?></small>
                            </div>
                            <form method="POST" action="grupe.php" class="d-inline" onsubmit="return confirm('Ești sigur că vrei să ștergi această grupă?');">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_grupa" value="<?= (int)$group['id_grupa'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Șterge grupă</button>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <form method="POST" action="grupe.php" class="row g-2">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="add_team">
                                    <input type="hidden" name="id_grupa" value="<?= (int)$group['id_grupa'] ?>">
                                    <div class="col-md-8">
                                        <select class="form-select form-select-sm" name="id_echipa" required>
                                            <option value="">Selectează echipă</option>
                                            <?php foreach ($teams as $team): ?>
                                                <?php
                                                $isInGroup = false;
                                                foreach ($group['echipe'] as $teamInGroup) {
                                                    if ((int)$teamInGroup['id_echipa'] === (int)$team['id_echipa']) {
                                                        $isInGroup = true;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <?php if (!$isInGroup): ?>
                                                    <option value="<?= (int)$team['id_echipa'] ?>">
                                                        <?= escape_html($team['nume_echipa']) ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-sm btn-primary w-100">Adaugă echipă</button>
                                    </div>
                                </form>
                            </div>

                            <?php if (empty($group['echipe'])): ?>
                                <p class="text-muted mb-0">Nu există echipe în această grupă.</p>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($group['echipe'] as $team): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><?= escape_html($team['nume_echipa']) ?></span>
                                            <form method="POST" action="grupe.php" class="d-inline" onsubmit="return confirm('Ești sigur că vrei să elimini această echipă din grupă?');">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="remove_team">
                                                <input type="hidden" name="id_grupa" value="<?= (int)$group['id_grupa'] ?>">
                                                <input type="hidden" name="id_echipa" value="<?= (int)$team['id_echipa'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Elimină</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

