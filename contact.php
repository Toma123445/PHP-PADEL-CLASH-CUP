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
            'nume' => 'Nume',
            'email' => 'Email',
            'subiect' => 'Subiect',
            'mesaj' => 'Mesaj'
        ];
        
        $errors = validate_required($data, $required);
        
        if (empty($errors)) {
            try {
                // Save to database
                $stmt = $pdo->prepare("
                    INSERT INTO mesaje_contact (nume, email, subiect, mesaj)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['nume'],
                    $data['email'],
                    $data['subiect'],
                    $data['mesaj']
                ]);
                
                // Send email
                send_contact_email($data);
                
                flash('success', 'Mesajul a fost trimis cu succes! Vei primi un răspuns în cel mai scurt timp.');
                redirect('contact.php');
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $errors['general'] = 'A apărut o eroare. Te rugăm să încerci din nou.';
            }
        }
    }
}

$flashes = get_flashes();

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - <?= htmlspecialchars(APP_NAME) ?></title>
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
                <h1 class="mb-4"><i class="bi bi-envelope"></i> Contact</h1>

                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Trimite un mesaj</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nume" class="form-label">Nume *</label>
                                    <input type="text" class="form-control <?= isset($errors['nume']) ? 'is-invalid' : '' ?>" 
                                           id="nume" name="nume" 
                                           value="<?= htmlspecialchars($_POST['nume'] ?? '') ?>" required>
                                    <?php if (isset($errors['nume'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['nume']) ?></div>
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
                                <label for="subiect" class="form-label">Subiect *</label>
                                <input type="text" class="form-control <?= isset($errors['subiect']) ? 'is-invalid' : '' ?>" 
                                       id="subiect" name="subiect" 
                                       value="<?= htmlspecialchars($_POST['subiect'] ?? '') ?>" required>
                                <?php if (isset($errors['subiect'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['subiect']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="mesaj" class="form-label">Mesaj *</label>
                                <textarea class="form-control <?= isset($errors['mesaj']) ? 'is-invalid' : '' ?>" 
                                          id="mesaj" name="mesaj" rows="5" required><?= htmlspecialchars($_POST['mesaj'] ?? '') ?></textarea>
                                <?php if (isset($errors['mesaj'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['mesaj']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send"></i> Trimite Mesaj
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <h5><i class="bi bi-info-circle"></i> Informații Contact</h5>
                        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars(APP_EMAIL) ?></p>
                        <p class="mb-0"><strong>Website:</strong> <a href="<?= htmlspecialchars(APP_URL) ?>"><?= htmlspecialchars(APP_URL) ?></a></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

