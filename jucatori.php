<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout.php';

$pdo = get_pdo();

$divisions = $pdo->query('SELECT id_divizie, nume_divizie FROM divizii ORDER BY valoare_banda')->fetchAll();
$teams = $pdo->query('SELECT id_echipa, nume_echipa FROM echipe ORDER BY nume_echipa')->fetchAll();

require_admin();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Token CSRF invalid.');
        redirect('jucatori.php');
    }

    $payload = sanitize($_POST);
    $action = $payload['action'] ?? null;

    if ($action === 'create') {
        $errors = validate_required($payload, [
            'nume'      => 'Nume',
            'prenume'   => 'Prenume',
            'id_echipa' => 'Echipa',
            'id_divizie'=> 'Divizie',
        ]);

        if ($errors) {
            flash('danger', implode('<br>', $errors));
            redirect('jucatori.php');
        }

        $stmt = $pdo->prepare('
            INSERT INTO jucatori (nume, prenume, email, telefon, id_echipa, id_divizie)
            VALUES (:nume, :prenume, :email, :telefon, :echipa, :divizie)
        ');
        $stmt->execute([
            ':nume'    => $payload['nume'],
            ':prenume' => $payload['prenume'],
            ':email'   => $payload['email'] ?? null,
            ':telefon' => $payload['telefon'] ?? null,
            ':echipa'  => (int)$payload['id_echipa'],
            ':divizie' => (int)$payload['id_divizie'],
        ]);

        flash('success', 'Jucator adaugat cu succes.');
        redirect('jucatori.php');
    }

    if ($action === 'update') {
        $errors = validate_required($payload, [
            'player_id' => 'ID jucator',
            'nume'      => 'Nume',
            'prenume'   => 'Prenume',
            'id_echipa' => 'Echipa',
            'id_divizie'=> 'Divizie',
        ]);

        if ($errors) {
            flash('danger', implode('<br>', $errors));
            redirect('jucatori.php?edit=' . (int)$payload['player_id']);
        }

        $stmt = $pdo->prepare('
            UPDATE jucatori
            SET nume = :nume,
                prenume = :prenume,
                email = :email,
                telefon = :telefon,
                id_echipa = :echipa,
                id_divizie = :divizie
            WHERE id_jucator = :id
        ');
        $stmt->execute([
            ':nume'    => $payload['nume'],
            ':prenume' => $payload['prenume'],
            ':email'   => $payload['email'] ?? null,
            ':telefon' => $payload['telefon'] ?? null,
            ':echipa'  => (int)$payload['id_echipa'],
            ':divizie' => (int)$payload['id_divizie'],
            ':id'      => (int)$payload['player_id'],
        ]);

        flash('success', 'Datele jucatorului au fost actualizate.');
        redirect('jucatori.php');
    }

    if ($action === 'delete') {
        $playerId = (int)($payload['player_id'] ?? 0);
        if (!$playerId) {
            flash('danger', 'Jucatorul nu a putut fi identificat.');
            redirect('jucatori.php');
        }

        $stmt = $pdo->prepare('DELETE FROM jucatori WHERE id_jucator = :id');
        $stmt->execute([':id' => $playerId]);

        flash('success', 'Jucatorul a fost sters.');
        redirect('jucatori.php');
    }
}

$playersStmt = $pdo->query('
    SELECT j.id_jucator,
           j.nume,
           j.prenume,
           j.email,
           j.telefon,
           e.nume_echipa,
           d.nume_divizie
    FROM jucatori j
    LEFT JOIN echipe e ON e.id_echipa = j.id_echipa
    LEFT JOIN divizii d ON d.id_divizie = j.id_divizie
    ORDER BY e.nume_echipa, j.nume
');
$players = $playersStmt->fetchAll();

$playerToEdit = null;
if (isset($_GET['edit'])) {
    $playerId = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM jucatori WHERE id_jucator = :id');
    $stmt->execute([':id' => $playerId]);
    $playerToEdit = $stmt->fetch();

    if (!$playerToEdit) {
        flash('danger', 'Jucatorul selectat nu exista.');
        redirect('jucatori.php');
    }
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrare jucatori - Smash Cup 5x5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php render_nav('jucatori'); ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Administrare jucatori</h1>
            <p class="text-muted mb-0">Gestioneaza roster-ul fiecarei echipe.</p>
        </div>
    </div>

    <?php display_flashes(); ?>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-0">
                    <h2 class="h5 mb-0"><?= $playerToEdit ? 'Editeaza jucator' : 'Adauga jucator' ?></h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="jucatori.php">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="<?= $playerToEdit ? 'update' : 'create' ?>">
                        <?php if ($playerToEdit): ?>
                            <input type="hidden" name="player_id" value="<?= (int)$playerToEdit['id_jucator'] ?>">
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nume" class="form-label">Nume</label>
                                <input type="text" class="form-control" id="nume" name="nume" value="<?= htmlspecialchars($playerToEdit['nume'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prenume" class="form-label">Prenume</label>
                                <input type="text" class="form-control" id="prenume" name="prenume" value="<?= htmlspecialchars($playerToEdit['prenume'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($playerToEdit['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="telefon" class="form-label">Telefon</label>
                            <input type="text" class="form-control" id="telefon" name="telefon" value="<?= htmlspecialchars($playerToEdit['telefon'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="id_echipa" class="form-label">Echipa</label>
                            <select class="form-select" id="id_echipa" name="id_echipa" required>
                                <option value="">Selecteaza echipa</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= (int)$team['id_echipa'] ?>" <?= isset($playerToEdit['id_echipa']) && (int)$playerToEdit['id_echipa'] === (int)$team['id_echipa'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($team['nume_echipa']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_divizie" class="form-label">Divizie</label>
                            <select class="form-select" id="id_divizie" name="id_divizie" required>
                                <option value="">Selecteaza divizia</option>
                                <?php foreach ($divisions as $division): ?>
                                    <option value="<?= (int)$division['id_divizie'] ?>" <?= isset($playerToEdit['id_divizie']) && (int)$playerToEdit['id_divizie'] === (int)$division['id_divizie'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($division['nume_divizie']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><?= $playerToEdit ? 'Actualizeaza' : 'Adauga' ?></button>
                            <?php if ($playerToEdit): ?>
                                <a href="jucatori.php" class="btn btn-outline-secondary">Anuleaza</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Lista jucatori</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>Nume</th>
                                <th>Echipa</th>
                                <th>Divizie</th>
                                <th>Contact</th>
                                <th class="text-end">Actiuni</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!$players): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Nu exista jucatori inregistrati.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($players as $player): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($player['nume'] . ' ' . $player['prenume']) ?></td>
                                        <td><?= htmlspecialchars($player['nume_echipa'] ?? 'Fara echipa') ?></td>
                                        <td><?= htmlspecialchars($player['nume_divizie'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php if ($player['email']): ?>
                                                <div><?= htmlspecialchars($player['email']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($player['telefon']): ?>
                                                <small class="text-muted"><?= htmlspecialchars($player['telefon']) ?></small>
                                            <?php endif; ?>
                                            <?php if (!$player['email'] && !$player['telefon']): ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="jucatori.php?edit=<?= (int)$player['id_jucator'] ?>" class="btn btn-sm btn-outline-primary">Editeaza</a>
                                            <form method="POST" action="jucatori.php" class="d-inline-block" onsubmit="return confirm('Stergi acest jucator?');">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="player_id" value="<?= (int)$player['id_jucator'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Sterge</button>
                                            </form>
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
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

