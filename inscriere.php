<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/email.php';
require_once __DIR__ . '/config.php';

define('APP_URL', APP_URL);

$pdo = get_db();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Token CSRF invalid. Te rugam sa reincarci pagina.';
    } else {
        $data = sanitize($_POST);
        
        $required = [
            'nume_echipa' => 'Nume echipă',
            'capitan' => 'Nume capitan',
            'email' => 'Email',
            'telefon' => 'Telefon'
        ];
        
        $errors = validate_required($data, $required);
        
        if (empty($errors)) {
            try {
                // Check if team name already exists
                $stmt = $pdo->prepare("SELECT id_echipa FROM echipe WHERE nume_echipa = ?");
                $stmt->execute([$data['nume_echipa']]);
                if ($stmt->fetch()) {
                    $errors['nume_echipa'] = 'Această echipă există deja.';
                } else {
                    // Get current competition
                    $stmt = $pdo->query("SELECT id_competitie FROM competitii ORDER BY id_competitie DESC LIMIT 1");
                    $competitie = $stmt->fetch();
                    $id_competitie = $competitie['id_competitie'] ?? null;
                    
                    // Insert team
                    $stmt = $pdo->prepare("
                        INSERT INTO echipe (nume_echipa, capitan, email_capitan, telefon, id_competitie, status)
                        VALUES (?, ?, ?, ?, ?, 'pending')
                    ");
                    $stmt->execute([
                        $data['nume_echipa'],
                        $data['capitan'],
                        $data['email'],
                        $data['telefon'],
                        $id_competitie
                    ]);
                    
                    $id_echipa = $pdo->lastInsertId();
                    
                    // Add players
                    for ($i = 1; $i <= 5; $i++) {
                        if (!empty($data["jucator_nume_$i"]) && !empty($data["jucator_prenume_$i"])) {
                            $id_divizie = $data["jucator_divizie_$i"] ?? 1;
                            $stmt = $pdo->prepare("
                                INSERT INTO jucatori (nume, prenume, id_echipa, id_divizie)
                                VALUES (?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $data["jucator_nume_$i"],
                                $data["jucator_prenume_$i"],
                                $id_echipa,
                                $id_divizie
                            ]);
                        }
                    }
                    
                    // Send confirmation email
                    send_registration_email($data['email'], $data['nume_echipa']);
                    
                    flash('success', 'Înscrierea a fost trimisă cu succes! Vei primi un email de confirmare.');
                    redirect('inscriere.php');
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors['general'] = 'A apărut o eroare. Te rugăm să încerci din nou.';
            }
        }
    }
}

// Get divisions
$divizii = [];
try {
    $stmt = $pdo->query("SELECT * FROM divizii ORDER BY valoare_banda");
    $divizii = $stmt->fetchAll();
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
    <title>Înscriere Echipă - <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
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

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0"><i class="bi bi-pencil-square"></i> Formular Înscriere Echipă</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                            <h5 class="mb-3">Date Echipă</h5>
                            
                            <div class="mb-3">
                                <label for="nume_echipa" class="form-label">Nume Echipă *</label>
                                <input type="text" class="form-control <?= isset($errors['nume_echipa']) ? 'is-invalid' : '' ?>" 
                                       id="nume_echipa" name="nume_echipa" 
                                       value="<?= htmlspecialchars($_POST['nume_echipa'] ?? '') ?>" required>
                                <?php if (isset($errors['nume_echipa'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['nume_echipa']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="capitan" class="form-label">Nume Capitan *</label>
                                    <input type="text" class="form-control <?= isset($errors['capitan']) ? 'is-invalid' : '' ?>" 
                                           id="capitan" name="capitan" 
                                           value="<?= htmlspecialchars($_POST['capitan'] ?? '') ?>" required>
                                    <?php if (isset($errors['capitan'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['capitan']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                           id="email" name="email" 
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="telefon" class="form-label">Telefon *</label>
                                <input type="tel" class="form-control <?= isset($errors['telefon']) ? 'is-invalid' : '' ?>" 
                                       id="telefon" name="telefon" 
                                       value="<?= htmlspecialchars($_POST['telefon'] ?? '') ?>" required>
                                <?php if (isset($errors['telefon'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['telefon']) ?></div>
                                <?php endif; ?>
                            </div>

                            <hr class="my-4">

                            <h5 class="mb-3">Jucători (minim 5)</h5>

                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Jucător <?= $i ?></h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Nume</label>
                                            <input type="text" class="form-control" 
                                                   name="jucator_nume_<?= $i ?>" 
                                                   value="<?= htmlspecialchars($_POST["jucator_nume_$i"] ?? '') ?>">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Prenume</label>
                                            <input type="text" class="form-control" 
                                                   name="jucator_prenume_<?= $i ?>" 
                                                   value="<?= htmlspecialchars($_POST["jucator_prenume_$i"] ?? '') ?>">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Divizie</label>
                                            <select class="form-select" name="jucator_divizie_<?= $i ?>">
                                                <?php foreach ($divizii as $divizie): ?>
                                                    <option value="<?= $divizie['id_divizie'] ?>" 
                                                            <?= (($_POST["jucator_divizie_$i"] ?? 1) == $divizie['id_divizie']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($divizie['nume_divizie']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endfor; ?>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle"></i> Trimite Înscrierea
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

