<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout.php';

require_login();

$pdo = get_pdo();
$user = current_user();

// Doar admin poate gestiona meciuri
if (!is_admin()) {
    flash('danger', 'Nu ai permisiunea necesară.');
    redirect('index.php');
}

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

$teams = $pdo->query('SELECT id_echipa, nume_echipa FROM echipe ORDER BY nume_echipa')->fetchAll();
$groups = [];
if ($competition) {
    $groups = $pdo->prepare('SELECT id_grupa, nume FROM grupe WHERE id_competitie = :comp_id ORDER BY nume');
    $groups->execute([':comp_id' => (int)$competition['id_competitie']]);
    $groups = $groups->fetchAll();
}

// Procesare POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Token CSRF invalid.');
        redirect('meciuri.php');
    }

    $payload = sanitize($_POST);
    $action = $payload['action'] ?? null;

    if ($action === 'create' && is_admin()) {
        $errors = validate_required($payload, [
            'id_echipa_a' => 'Echipa A',
            'id_echipa_b' => 'Echipa B',
            'id_competitie' => 'Competiție'
        ]);

        if ($payload['id_echipa_a'] === $payload['id_echipa_b']) {
            $errors['id_echipa_b'] = 'Echipele trebuie să fie diferite.';
        }

        if ($errors) {
            flash('danger', implode('<br>', $errors));
            redirect('meciuri.php');
        }

        $stmt = $pdo->prepare('
            INSERT INTO meciuri (id_competitie, id_grupa, id_echipa_a, id_echipa_b, data_meci, locatie, faza, status)
            VALUES (:comp, :grupa, :team_a, :team_b, :data, :locatie, :faza, "programat")
        ');

        $stmt->execute([
            ':comp' => (int)$payload['id_competitie'],
            ':grupa' => !empty($payload['id_grupa']) ? (int)$payload['id_grupa'] : null,
            ':team_a' => (int)$payload['id_echipa_a'],
            ':team_b' => (int)$payload['id_echipa_b'],
            ':data' => !empty($payload['data_meci']) ? $payload['data_meci'] : null,
            ':locatie' => $payload['locatie'] ?? null,
            ':faza' => $payload['faza'] ?? 'Grupa'
        ]);

        flash('success', 'Meciul a fost creat.');
        redirect('meciuri.php');
    }

    if ($action === 'update_scores') {
        $meciId = (int)($payload['meci_id'] ?? 0);
        
        if (!$meciId) {
            flash('danger', 'Meciul nu a putut fi identificat.');
            redirect('meciuri.php');
        }

        $pdo->beginTransaction();
        
        try {
            // Șterge seturile existente
            $stmt = $pdo->prepare('DELETE FROM seturi WHERE id_meci = :meci_id');
            $stmt->execute([':meci_id' => $meciId]);

            // Inserează seturile noi
            $stmt = $pdo->prepare('
                INSERT INTO seturi (id_meci, numar_set, jucator_a1, jucator_a2, jucator_b1, jucator_b2, gameuri_a, gameuri_b)
                VALUES (:meci_id, :set_nr, :a1, :a2, :b1, :b2, :games_a, :games_b)
            ');

            for ($i = 1; $i <= 5; $i++) {
                $gamesA = (int)($payload["set_{$i}_a"] ?? 0);
                $gamesB = (int)($payload["set_{$i}_b"] ?? 0);
                
                // Obține jucătorii pentru echipa A
                $playersA = $pdo->prepare('SELECT id_jucator FROM jucatori WHERE id_echipa = :team_id LIMIT 5');
                $playersA->execute([':team_id' => (int)$payload['id_echipa_a']]);
                $teamAPlayers = $playersA->fetchAll(PDO::FETCH_COLUMN);
                
                // Obține jucătorii pentru echipa B
                $playersB = $pdo->prepare('SELECT id_jucator FROM jucatori WHERE id_echipa = :team_id LIMIT 5');
                $playersB->execute([':team_id' => (int)$payload['id_echipa_b']]);
                $teamBPlayers = $playersB->fetchAll(PDO::FETCH_COLUMN);

                // Distribuie jucătorii pentru set (simplificat - rotire circulară)
                $a1 = $teamAPlayers[($i - 1) % count($teamAPlayers)] ?? $teamAPlayers[0] ?? 0;
                $a2 = $teamAPlayers[($i) % count($teamAPlayers)] ?? $teamAPlayers[1] ?? 0;
                $b1 = $teamBPlayers[($i - 1) % count($teamBPlayers)] ?? $teamBPlayers[0] ?? 0;
                $b2 = $teamBPlayers[($i) % count($teamBPlayers)] ?? $teamBPlayers[1] ?? 0;

                $stmt->execute([
                    ':meci_id' => $meciId,
                    ':set_nr' => $i,
                    ':a1' => (int)$a1,
                    ':a2' => (int)$a2,
                    ':b1' => (int)$b1,
                    ':b2' => (int)$b2,
                    ':games_a' => $gamesA,
                    ':games_b' => $gamesB
                ]);
            }

            // Actualizează statusul meciului doar dacă nu este deja finalizat
            $stmt = $pdo->prepare('UPDATE meciuri SET status = "finalizat" WHERE id_meci = :meci_id');
            $stmt->execute([':meci_id' => $meciId]);

            $pdo->commit();
            
            // Recalculează clasamentul pentru competiția respectivă
            try {
                $matchStmt = $pdo->prepare('SELECT id_competitie FROM meciuri WHERE id_meci = :meci_id');
                $matchStmt->execute([':meci_id' => $meciId]);
                $matchData = $matchStmt->fetch();
                
                if ($matchData) {
                    $compId = (int)$matchData['id_competitie'];
                    
                    // Șterge clasamentul vechi pentru această competiție
                    $pdo->prepare('DELETE FROM clasament WHERE id_competitie = :comp')->execute([':comp' => $compId]);
                    
                    // Recalculează clasamentul pentru toate meciurile finalizate
                    $allMatches = $pdo->prepare('
                        SELECT id_meci, id_echipa_a, id_echipa_b 
                        FROM meciuri 
                        WHERE id_competitie = :comp AND status = "finalizat"
                    ');
                    $allMatches->execute([':comp' => $compId]);
                    $finishedMatches = $allMatches->fetchAll();
                    
                    foreach ($finishedMatches as $finishedMatch) {
                        $setsStmt = $pdo->prepare('SELECT gameuri_a, gameuri_b FROM seturi WHERE id_meci = :meci_id');
                        $setsStmt->execute([':meci_id' => (int)$finishedMatch['id_meci']]);
                        $setsData = $setsStmt->fetchAll();
                        
                        $pointsA = 0;
                        $pointsB = 0;
                        $gamesPlusA = 0;
                        $gamesMinusA = 0;
                        $gamesPlusB = 0;
                        $gamesMinusB = 0;
                        
                        foreach ($setsData as $set) {
                            $gamesA = (int)$set['gameuri_a'];
                            $gamesB = (int)$set['gameuri_b'];
                            
                            if ($gamesA > $gamesB) {
                                $pointsA++;
                            } elseif ($gamesB > $gamesA) {
                                $pointsB++;
                            }
                            
                            $gamesPlusA += $gamesA;
                            $gamesMinusA += $gamesB;
                            $gamesPlusB += $gamesB;
                            $gamesMinusB += $gamesA;
                        }
                        
                        // Actualizează clasamentul pentru echipa A
                        $stmt = $pdo->prepare('
                            INSERT INTO clasament (id_competitie, id_echipa, meciuri_jucate, puncte, gameuri_plus, gameuri_minus)
                            VALUES (:comp, :team, 1, :points, :plus, :minus)
                            ON DUPLICATE KEY UPDATE
                                meciuri_jucate = meciuri_jucate + 1,
                                puncte = puncte + :points,
                                gameuri_plus = gameuri_plus + :plus,
                                gameuri_minus = gameuri_minus + :minus
                        ');
                        
                        $stmt->execute([
                            ':comp' => $compId,
                            ':team' => (int)$finishedMatch['id_echipa_a'],
                            ':points' => $pointsA,
                            ':plus' => $gamesPlusA,
                            ':minus' => $gamesMinusA
                        ]);
                        
                        // Pentru echipa B
                        $stmt->execute([
                            ':comp' => $compId,
                            ':team' => (int)$finishedMatch['id_echipa_b'],
                            ':points' => $pointsB,
                            ':plus' => $gamesPlusB,
                            ':minus' => $gamesMinusB
                        ]);
                    }
                }
            } catch (Throwable $e) {
                error_log('Eroare la actualizarea clasamentului: ' . $e->getMessage());
            }
            
            flash('success', 'Scorurile au fost salvate și clasamentul a fost actualizat.');
            redirect('meciuri.php');
            
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('danger', 'A apărut o eroare la salvarea scorurilor.');
            redirect('meciuri.php');
        }
    }
}

// Obține meciurile
$matches = [];
if ($competition) {
    $stmt = $pdo->prepare('
        SELECT 
            m.*,
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

$matchToEdit = null;
if (isset($_GET['edit'])) {
    $matchId = (int)$_GET['edit'];
    $stmt = $pdo->prepare('
        SELECT m.*, 
               ea.nume_echipa as echipa_a,
               eb.nume_echipa as echipa_b
        FROM meciuri m
        INNER JOIN echipe ea ON ea.id_echipa = m.id_echipa_a
        INNER JOIN echipe eb ON eb.id_echipa = m.id_echipa_b
        WHERE m.id_meci = :id
    ');
    $stmt->execute([':id' => $matchId]);
    $matchToEdit = $stmt->fetch();

    if ($matchToEdit) {
        $setsStmt = $pdo->prepare('SELECT * FROM seturi WHERE id_meci = :meci_id ORDER BY numar_set');
        $setsStmt->execute([':meci_id' => $matchId]);
        $matchToEdit['sets'] = $setsStmt->fetchAll();
    }
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionare meciuri - Smash Cup 5x5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php render_nav('meciuri'); ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Gestionare meciuri</h1>
            <p class="text-muted mb-0">Creează meciuri și introdu scoruri după terminarea meciurilor</p>
        </div>
    </div>

    <?php display_flashes(); ?>

    <?php if (is_admin() && !$matchToEdit): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Creează meci nou</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="meciuri.php">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="id_competitie" value="<?= (int)$competition['id_competitie'] ?>">

                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="id_echipa_a" class="form-label">Echipa A *</label>
                            <select class="form-select" id="id_echipa_a" name="id_echipa_a" required>
                                <option value="">Selectează echipa</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= (int)$team['id_echipa'] ?>">
                                        <?= escape_html($team['nume_echipa']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-5 mb-3">
                            <label for="id_echipa_b" class="form-label">Echipa B *</label>
                            <select class="form-select" id="id_echipa_b" name="id_echipa_b" required>
                                <option value="">Selectează echipa</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= (int)$team['id_echipa'] ?>">
                                        <?= escape_html($team['nume_echipa']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label">VS</label>
                            <div class="text-center mt-2">vs</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="id_grupa" class="form-label">Grupă</label>
                            <select class="form-select" id="id_grupa" name="id_grupa">
                                <option value="">Fără grupă</option>
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?= (int)$group['id_grupa'] ?>">
                                        <?= escape_html($group['nume']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="data_meci" class="form-label">Data și ora</label>
                            <input type="datetime-local" class="form-control" id="data_meci" name="data_meci">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="locatie" class="form-label">Locație</label>
                            <input type="text" class="form-control" id="locatie" name="locatie" maxlength="150">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="faza" class="form-label">Fază</label>
                        <select class="form-select" id="faza" name="faza">
                            <option value="Grupa">Grupa</option>
                            <option value="Sferturi">Sferturi</option>
                            <option value="Semifinale">Semifinale</option>
                            <option value="Finala">Finala</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Creează meci</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($matchToEdit): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Introdu scoruri: <?= escape_html($matchToEdit['echipa_a']) ?> vs <?= escape_html($matchToEdit['echipa_b']) ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="meciuri.php">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="update_scores">
                    <input type="hidden" name="meci_id" value="<?= (int)$matchToEdit['id_meci'] ?>">
                    <input type="hidden" name="id_echipa_a" value="<?= (int)$matchToEdit['id_echipa_a'] ?>">
                    <input type="hidden" name="id_echipa_b" value="<?= (int)$matchToEdit['id_echipa_b'] ?>">

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Set</th>
                                    <th class="text-center"><?= escape_html($matchToEdit['echipa_a']) ?></th>
                                    <th class="text-center"><?= escape_html($matchToEdit['echipa_b']) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php 
                                    $set = null;
                                    if (isset($matchToEdit['sets'])) {
                                        foreach ($matchToEdit['sets'] as $s) {
                                            if ((int)$s['numar_set'] === $i) {
                                                $set = $s;
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><strong>Set <?= $i ?></strong></td>
                                        <td class="text-center">
                                            <input type="number" class="form-control text-center" 
                                                   name="set_<?= $i ?>_a" 
                                                   value="<?= $set ? (int)$set['gameuri_a'] : '' ?>" 
                                                   min="0" max="20" required>
                                        </td>
                                        <td class="text-center">
                                            <input type="number" class="form-control text-center" 
                                                   name="set_<?= $i ?>_b" 
                                                   value="<?= $set ? (int)$set['gameuri_b'] : '' ?>" 
                                                   min="0" max="20" required>
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">Salvează scoruri</button>
                        <a href="meciuri.php" class="btn btn-outline-secondary">Anulează</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Lista meciuri</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Echipe</th>
                            <th>Grupă/Fază</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th class="text-end">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($matches)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Nu există meciuri.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($matches as $match): ?>
                                <tr>
                                    <td>
                                        <?= escape_html($match['echipa_a']) ?> vs <?= escape_html($match['echipa_b']) ?>
                                    </td>
                                    <td>
                                        <?= $match['nume_grupa'] ? escape_html($match['nume_grupa']) . ' - ' : '' ?>
                                        <?= escape_html($match['faza']) ?>
                                    </td>
                                    <td>
                                        <?= $match['data_meci'] ? date('d.m.Y H:i', strtotime($match['data_meci'])) : '-' ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = match($match['status']) {
                                            'programat' => 'bg-primary',
                                            'in_desfasurare' => 'bg-warning',
                                            'finalizat' => 'bg-success',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= escape_html($match['status']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="meciuri.php?edit=<?= (int)$match['id_meci'] ?>" class="btn btn-sm btn-outline-primary">
                                            <?= $match['status'] === 'finalizat' ? 'Adaugă/Editează scoruri' : 'Introdu scoruri' ?>
                                        </a>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

