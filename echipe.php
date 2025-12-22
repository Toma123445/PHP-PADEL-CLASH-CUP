<?php



declare(strict_types=1);



require_once __DIR__ . '/includes/db_connect.php';

require_once __DIR__ . '/includes/layout.php';



$pdo = get_pdo();



require_admin();



// Fetch divisions for dropdowns.

$divisionsStmt = $pdo->query('SELECT id_divizie, nume_divizie FROM divizii ORDER BY valoare_banda ASC');

$divisions = $divisionsStmt->fetchAll();



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {

        flash('danger', 'Token CSRF invalid. Reincarca pagina si incearca din nou.');

        redirect('echipe.php');

    }



    $payload = sanitize($_POST);

    $action = $payload['action'] ?? null;



    if ($action === 'create') {

        $errors = validate_required($payload, [

            'nume_echipa' => 'Nume echipa',

            'divizie_id'  => 'Divizie',

        ]);



        if ($errors) {

            flash('danger', implode('<br>', $errors));

            redirect('echipe.php');

        }



        $stmt = $pdo->prepare('INSERT INTO echipe (nume_echipa, capitan, email_capitan, telefon_capitan, divizie_id, data_inscriere) VALUES (:nume, :capitan, :email, :telefon, :divizie, NOW())');

        $stmt->execute([

            ':nume'    => $payload['nume_echipa'],

            ':capitan' => 'Fără capitan',

            ':email'   => null,

            ':telefon' => null,

            ':divizie' => (int)$payload['divizie_id'],

        ]);



        flash('success', 'Echipa a fost adaugata.');

        redirect('echipe.php');

    }



    if ($action === 'update') {

        $errors = validate_required($payload, [

            'team_id'     => 'ID echipa',

            'nume_echipa' => 'Nume echipa',

            'divizie_id'  => 'Divizie',

        ]);



        if ($errors) {

            flash('danger', implode('<br>', $errors));

            redirect('echipe.php?edit=' . (int)$payload['team_id']);

        }

        $stmt = $pdo->prepare('UPDATE echipe SET nume_echipa = :nume, divizie_id = :divizie WHERE id_echipa = :id');

        $stmt->execute([

            ':nume'    => $payload['nume_echipa'],

            ':divizie' => (int)$payload['divizie_id'],

            ':id'      => (int)$payload['team_id'],

        ]);



        flash('success', 'Echipa a fost actualizata.');

        redirect('echipe.php');

    }



    if ($action === 'delete') {

        $teamId = (int)($payload['team_id'] ?? 0);



        if (!$teamId) {

            flash('danger', 'Echipa nu a putut fi identificata.');

            redirect('echipe.php');

        }



        $stmt = $pdo->prepare('DELETE FROM echipe WHERE id_echipa = :id');

        $stmt->execute([':id' => $teamId]);



        flash('success', 'Echipa a fost stearsa.');

        redirect('echipe.php');

    }

}



$teamsStmt = $pdo->query('

    SELECT e.id_echipa,

           e.nume_echipa,

           e.capitan,

           e.data_inscriere,

           d.nume_divizie

    FROM echipe e

    LEFT JOIN divizii d ON d.id_divizie = e.divizie_id

    ORDER BY e.data_inscriere DESC

');

$teams = $teamsStmt->fetchAll();



$teamToEdit = null;

if (isset($_GET['edit'])) {

    $teamId = (int)$_GET['edit'];

    $stmt = $pdo->prepare('SELECT * FROM echipe WHERE id_echipa = :id');

    $stmt->execute([':id' => $teamId]);

    $teamToEdit = $stmt->fetch();



    if (!$teamToEdit) {

        flash('danger', 'Echipa selectata nu exista.');

        redirect('echipe.php');

    }

}



?>

<!DOCTYPE html>

<html lang="ro">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrare echipe - Smash Cup 5x5</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<?php render_nav('echipe'); ?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="h3 mb-0">Administrare echipe</h1>

            <p class="text-muted mb-0">Adauga, modifica si sterge echipele inscrise in competitie.</p>

        </div>

    </div>



    <?php display_flashes(); ?>



    <div class="row g-4">

        <div class="col-md-5">

            <div class="card shadow-sm">

                <div class="card-header bg-white border-0">

                    <h2 class="h5 mb-0"><?= $teamToEdit ? 'Editeaza echipa' : 'Adauga echipa' ?></h2>

                </div>

                <div class="card-body">

                    <form method="POST" action="echipe.php">

                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <input type="hidden" name="action" value="<?= $teamToEdit ? 'update' : 'create' ?>">

                        <?php if ($teamToEdit): ?>

                            <input type="hidden" name="team_id" value="<?= (int)$teamToEdit['id_echipa'] ?>">

                        <?php endif; ?>

                        <div class="mb-3">

                            <label for="nume_echipa" class="form-label">Nume echipa</label>

                            <input type="text" class="form-control" id="nume_echipa" name="nume_echipa" value="<?= htmlspecialchars($teamToEdit['nume_echipa'] ?? '') ?>" required>

                        </div>


                        <div class="mb-3">

                            <label for="divizie_id" class="form-label">Divizie</label>

                            <select class="form-select" id="divizie_id" name="divizie_id" required>

                                <option value="">Alege divizia</option>

                                <?php foreach ($divisions as $division): ?>

                                    <option value="<?= (int)$division['id_divizie'] ?>" <?= isset($teamToEdit['divizie_id']) && (int)$teamToEdit['divizie_id'] === (int)$division['id_divizie'] ? 'selected' : '' ?>>

                                        <?= htmlspecialchars($division['nume_divizie']) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="d-flex gap-2">

                            <button type="submit" class="btn btn-primary"><?= $teamToEdit ? 'Actualizeaza' : 'Adauga' ?></button>

                            <?php if ($teamToEdit): ?>

                                <a href="echipe.php" class="btn btn-outline-secondary">Anuleaza</a>

                            <?php endif; ?>

                        </div>

                    </form>

                </div>

            </div>

        </div>

        <div class="col-md-7">

            <div class="card shadow-sm">

                <div class="card-header bg-white border-0">

                    <h2 class="h5 mb-0">Echipe inscrise</h2>

                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table mb-0 align-middle">

                            <thead class="table-light">

                            <tr>

                                <th>Nume echipa</th>

                                <th>Capitan</th>

                                <th>Divizie</th>

                                <th>Inscrisa la</th>

                                <th class="text-end">Actiuni</th>

                            </tr>

                            </thead>

                            <tbody>

                            <?php if (!$teams): ?>

                                <tr>

                                    <td colspan="5" class="text-center text-muted py-4">Nu exista echipe inregistrate.</td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($teams as $team): ?>

                                    <tr>

                                        <td><?= htmlspecialchars($team['nume_echipa']) ?></td>

                                        <td><?= htmlspecialchars($team['capitan']) ?></td>

                                        <td><?= htmlspecialchars($team['nume_divizie'] ?? 'N/A') ?></td>

                                        <td><?= htmlspecialchars($team['data_inscriere']) ?></td>

                                        <td class="text-end">

                                            <a href="echipe.php?edit=<?= (int)$team['id_echipa'] ?>" class="btn btn-sm btn-outline-primary">Editeaza</a>

                                            <form method="POST" action="echipe.php" class="d-inline-block" onsubmit="return confirm('Esti sigur ca vrei sa stergi aceasta echipa?');">

                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                                                <input type="hidden" name="action" value="delete">

                                                <input type="hidden" name="team_id" value="<?= (int)$team['id_echipa'] ?>">

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



