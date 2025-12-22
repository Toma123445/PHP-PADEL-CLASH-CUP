<?php



declare(strict_types=1);



require_once __DIR__ . '/includes/config.php';

require_once __DIR__ . '/includes/db_connect.php';

require_once __DIR__ . '/includes/layout.php';



if (is_logged_in()) {

    redirect('index.php');

}



$pdo = get_pdo();

$divisions = $pdo->query('SELECT id_divizie, nume_divizie FROM divizii ORDER BY valoare_banda')->fetchAll();



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {

        flash('danger', 'Token CSRF invalid.');

        redirect('register.php');

    }

    // Verificare rate limiting
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!check_rate_limit('register_' . $ip, 3, 3600)) {

        flash('danger', 'Prea multe încercări de înregistrare. Te rugăm să aștepți o oră.');

        redirect('register.php');

    }

    // Verificare reCAPTCHA pentru formularele publice
    if (!verify_recaptcha($_POST['g-recaptcha-response'] ?? null)) {

        flash('danger', 'Verificarea reCAPTCHA a eșuat. Te rugăm să încerci din nou.');

        redirect('register.php');

    }

    $payload = sanitize($_POST);

    $errors = validate_required($payload, [

        'nume'      => 'Nume',

        'prenume'   => 'Prenume',

        'email'     => 'Email',

        'telefon'   => 'Telefon',

        'divizie_id'=> 'Divizie',

        'password'  => 'Parola',

    ]);

    // Validare email
    if (isset($payload['email']) && !validate_email($payload['email'])) {

        $errors['email'] = 'Adresa de email nu este validă.';

    }

    // Validare parola mai strictă
    if (isset($payload['password'])) {

        $passwordErrors = validate_password($payload['password']);

        if (!empty($passwordErrors)) {

            $errors = array_merge($errors, $passwordErrors);

        }

    }



    if ($errors) {

        flash('danger', implode('<br>', $errors));

        redirect('register.php');

    }



    $stmt = $pdo->prepare('SELECT id_utilizator FROM utilizatori WHERE email = :email');

    $stmt->execute([':email' => $payload['email']]);

    if ($stmt->fetch()) {

        flash('danger', 'Exista deja un cont cu acest email.');

        redirect('register.php');

    }



    $pdo->beginTransaction();

    try {

        $stmt = $pdo->prepare('

            INSERT INTO utilizatori (email, parola_hash, rol, nume, prenume, telefon, divizie_id)

            VALUES (:email, :parola, "player", :nume, :prenume, :telefon, :divizie)

        ');

        $stmt->execute([

            ':email'   => $payload['email'],

            ':parola'  => password_hash($payload['password'], PASSWORD_BCRYPT),

            ':nume'    => $payload['nume'],

            ':prenume' => $payload['prenume'],

            ':telefon' => $payload['telefon'],

            ':divizie' => (int)$payload['divizie_id'],

        ]);



        $userId = (int)$pdo->lastInsertId();



        $stmt = $pdo->prepare('

            INSERT INTO jucatori (nume, prenume, email, telefon, id_echipa, id_divizie, user_id)

            VALUES (:nume, :prenume, :email, :telefon, NULL, :divizie, :user_id)

        ');

        $stmt->execute([

            ':nume'    => $payload['nume'],

            ':prenume' => $payload['prenume'],

            ':email'   => $payload['email'],

            ':telefon' => $payload['telefon'],

            ':divizie' => (int)$payload['divizie_id'],

            ':user_id' => $userId,

        ]);



        $pdo->commit();

    } catch (Throwable $throwable) {

        $pdo->rollBack();

        flash('danger', 'A aparut o eroare la inregistrare. Incearca din nou.');

        redirect('register.php');

    }



    flash('success', 'Cont creat. Te poti autentifica acum.');

    redirect('login.php');

}



?>

<!DOCTYPE html>

<html lang="ro">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Inregistrare jucator - Smash Cup 5x5</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

</head>

<body class="bg-light">

<?php render_nav(); ?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-6">

            <div class="card shadow-sm">

                <div class="card-body">

                    <h1 class="h4 mb-3 text-center">Creeaza cont de jucator</h1>

                    <?php display_flashes(); ?>

                    <form method="POST" action="register.php">

                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label for="nume" class="form-label">Nume</label>

                                <input type="text" class="form-control" id="nume" name="nume" required>

                            </div>

                            <div class="col-md-6 mb-3">

                                <label for="prenume" class="form-label">Prenume</label>

                                <input type="text" class="form-control" id="prenume" name="prenume" required>

                            </div>

                        </div>

                        <div class="mb-3">

                            <label for="email" class="form-label">Email</label>

                            <input type="email" class="form-control" id="email" name="email" required>

                        </div>

                        <div class="mb-3">

                            <label for="telefon" class="form-label">Telefon</label>

                            <input type="text" class="form-control" id="telefon" name="telefon" required>

                        </div>

                        <div class="mb-3">

                            <label for="divizie_id" class="form-label">Divizie</label>

                            <select class="form-select" id="divizie_id" name="divizie_id" required>

                                <option value="">Selecteaza divizia</option>

                                <?php foreach ($divisions as $division): ?>

                                    <option value="<?= (int)$division['id_divizie'] ?>"><?= htmlspecialchars($division['nume_divizie']) ?></option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="mb-3">

                            <label for="password" class="form-label">Parola</label>

                            <input type="password" class="form-control" id="password" name="password" required minlength="8">

                            <small class="text-muted">Minim 8 caractere, cel puțin o literă mare, o literă mică și o cifră</small>

                        </div>

                        <div class="mb-3">

                            <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>

                        </div>

                        <button class="btn btn-primary w-100" type="submit">Creeaza cont</button>

                    </form>

                    <p class="text-center mt-3 mb-0">

                        Ai deja cont?

                        <a href="login.php">Autentifica-te</a>

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>



