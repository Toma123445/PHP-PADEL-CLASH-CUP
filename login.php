<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout.php';

if (is_logged_in()) {
    redirect('index.php');
}

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Token CSRF invalid.');
        redirect('login.php');
    }

    $payload = sanitize($_POST);
    $errors = validate_required($payload, [
        'email'    => 'Email',
        'password' => 'Parola',
    ]);

    if ($errors) {
        flash('danger', implode('<br>', $errors));
        redirect('login.php');
    }

    $stmt = $pdo->prepare('SELECT * FROM utilizatori WHERE email = :email');
    $stmt->execute([':email' => $payload['email']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($payload['password'], $user['parola_hash'])) {
        flash('danger', 'Credentiale invalide.');
        redirect('login.php');
    }

    login_user($user);
    flash('success', 'Autentificare reusita.');
    redirect('index.php');
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smash Cup 5x5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php render_nav(); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h4 mb-3 text-center">Autentificare</h1>
                    <?php display_flashes(); ?>
                    <form method="POST" action="login.php" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Parola</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Login</button>
                    </form>
                    <p class="text-center mt-3 mb-0">
                        Nu ai cont?
                        <a href="register.php">Creeaza unul</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

