<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout.php';

require_login();

$pdo = get_pdo();
$userId = current_user()['id'];

$divisions = $pdo->query('SELECT id_divizie, nume_divizie FROM divizii ORDER BY valoare_banda')->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM utilizatori WHERE id_utilizator = :id');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

$playerStmt = $pdo->prepare('SELECT * FROM jucatori WHERE user_id = :user_id');
$playerStmt->execute([':user_id' => $userId]);
$player = $playerStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Token CSRF invalid.');
        redirect('profil.php');
    }

    $payload = sanitize($_POST);
    $errors = validate_required($payload, [
        'nume'      => 'Nume',
        'prenume'   => 'Prenume',
        'telefon'   => 'Telefon',
        'divizie_id'=> 'Divizie',
    ]);

    if (!empty($payload['new_password']) && strlen($payload['new_password']) < 6) {
        $errors['new_password'] = 'Parola noua trebuie sa aiba minim 6 caractere.';
    }

    if ($errors) {
        flash('danger', implode('<br>', $errors));
        redirect('profil.php');
    }

    $pdo->beginTransaction();
    try {
        $updateFields = [
            ':nume'    => $payload['nume'],
            ':prenume' => $payload['prenume'],
            ':telefon' => $payload['telefon'],
            ':divizie' => (int)$payload['divizie_id'],
            ':id'      => $userId,
        ];

        $passwordSql = '';
        if (!empty($payload['new_password'])) {
            $passwordSql = ', parola_hash = :parola';
            $updateFields[':parola'] = password_hash($payload['new_password'], PASSWORD_BCRYPT);
        }

        $sql = "
            UPDATE utilizatori
            SET nume = :nume,
                prenume = :prenume,
                telefon = :telefon,
                divizie_id = :divizie
                {$passwordSql}
            WHERE id_utilizator = :id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateFields);

        if ($player) {
            $stmt = $pdo->prepare('
                UPDATE jucatori
                SET nume = :nume,
                    prenume = :prenume,
                    telefon = :telefon,
                    id_divizie = :divizie
                WHERE user_id = :user_id
            ');
            $stmt->execute([
                ':nume'    => $payload['nume'],
                ':prenume' => $payload['prenume'],
                ':telefon' => $payload['telefon'],
                ':divizie' => (int)$payload['divizie_id'],
                ':user_id' => $userId,
            ]);
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO jucatori (nume, prenume, email, telefon, id_echipa, id_divizie, user_id)
                VALUES (:nume, :prenume, :email, :telefon, NULL, :divizie, :user_id)
            ');
            $stmt->execute([
                ':nume'    => $payload['nume'],
                ':prenume' => $payload['prenume'],
                ':email'   => $user['email'],
                ':telefon' => $payload['telefon'],
                ':divizie' => (int)$payload['divizie_id'],
                ':user_id' => $userId,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $throwable) {
        $pdo->rollBack();
        flash('danger', 'Actualizarea nu a reusit. Incearca din nou.');
        redirect('profil.php');
    }

    // Refresh in-memory session data
    $stmt = $pdo->prepare('SELECT * FROM utilizatori WHERE id_utilizator = :id');
    $stmt->execute([':id' => $userId]);
    $freshUser = $stmt->fetch();
    login_user($freshUser);

    flash('success', 'Profil actualizat.');
    redirect('profil.php');
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Smash Cup 5x5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php render_nav('profil'); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h4 mb-3">Profil personal</h1>
                    <?php display_flashes(); ?>
                    <form method="POST" action="profil.php">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nume" class="form-label">Nume</label>
                                <input type="text" class="form-control" id="nume" name="nume" value="<?= htmlspecialchars($user['nume']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prenume" class="form-label">Prenume</label>
                                <input type="text" class="form-control" id="prenume" name="prenume" value="<?= htmlspecialchars($user['prenume']) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="telefon" class="form-label">Telefon</label>
                            <input type="text" class="form-control" id="telefon" name="telefon" value="<?= htmlspecialchars($user['telefon'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="divizie_id" class="form-label">Divizie</label>
                            <select class="form-select" id="divizie_id" name="divizie_id" required>
                                <option value="">Selecteaza divizia</option>
                                <?php foreach ($divisions as $division): ?>
                                    <option value="<?= (int)$division['id_divizie'] ?>" <?= (int)($user['divizie_id'] ?? 0) === (int)$division['id_divizie'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($division['nume_divizie']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Parola noua (optional)</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Lasa gol pentru a pastra parola curenta">
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Salveaza</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

