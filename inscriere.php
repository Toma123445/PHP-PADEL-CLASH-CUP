<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/layout.php';

require_login();

$pdo = get_pdo();
$user = current_user();

// Verifică dacă utilizatorul are deja o echipă
$stmt = $pdo->prepare('
    SELECT e.* FROM echipe e
    INNER JOIN jucatori j ON j.id_echipa = e.id_echipa
    WHERE j.user_id = :user_id
    LIMIT 1
');
$stmt->execute([':user_id' => $user['id']]);
$existingTeam = $stmt->fetch();

if ($existingTeam) {
    flash('info', 'Ai deja o echipă înregistrată: ' . escape_html($existingTeam['nume_echipa']));
    redirect('index.php');
}

$divisions = $pdo->query('SELECT id_divizie, nume_divizie FROM divizii ORDER BY valoare_banda')->fetchAll();

// Obține jucători disponibili (fără echipă sau utilizatorul curent)
$availablePlayers = $pdo->query('
    SELECT id_jucator, nume, prenume, id_divizie 
    FROM jucatori 
    WHERE id_echipa IS NULL 
    ORDER BY nume, prenume
')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Token CSRF invalid.');
        redirect('inscriere.php');
    }

    // Verificare rate limiting
    if (!check_rate_limit('team_registration_' . $user['id'], 3, 3600)) {
        flash('danger', 'Prea multe încercări. Te rugăm să aștepți o oră.');
        redirect('inscriere.php');
    }

    // Verificare reCAPTCHA pentru formularele publice
    if (!verify_recaptcha($_POST['g-recaptcha-response'] ?? null)) {
        flash('danger', 'Verificarea reCAPTCHA a eșuat. Te rugăm să încerci din nou.');
        redirect('inscriere.php');
    }

    $payload = sanitize($_POST);
    
    $errors = validate_required($payload, [
        'nume_echipa' => 'Nume echipa',
        'divizie_id' => 'Divizie',
        'jucatori' => 'Jucatori'
    ]);

    // Validare nume echipă
    if (isset($payload['nume_echipa']) && strlen($payload['nume_echipa']) < 3) {
        $errors['nume_echipa'] = 'Numele echipei trebuie să aibă minim 3 caractere.';
    }

    // Validare jucători (trebuie exact 5)
    $selectedPlayers = $payload['jucatori'] ?? [];
    if (!is_array($selectedPlayers) || count($selectedPlayers) !== 5) {
        $errors['jucatori'] = 'Trebuie să selectezi exact 5 jucători.';
    }

    if ($errors) {
        flash('danger', implode('<br>', $errors));
        redirect('inscriere.php');
    }

    // Verifică dacă jucătorii sunt disponibili
    $placeholders = implode(',', array_fill(0, count($selectedPlayers), '?'));
    $stmt = $pdo->prepare("
        SELECT id_jucator FROM jucatori 
        WHERE id_jucator IN ($placeholders) AND id_echipa IS NULL
    ");
    $stmt->execute($selectedPlayers);
    
    if ($stmt->rowCount() !== 5) {
        flash('danger', 'Unul sau mai mulți jucători selectați nu sunt disponibili.');
        redirect('inscriere.php');
    }

    $pdo->beginTransaction();
    
    try {
        // Creează echipa
        $stmt = $pdo->prepare('
            INSERT INTO echipe (nume_echipa, capitan, email_capitan, telefon_capitan, divizie_id, data_inscriere)
            VALUES (:nume, :capitan, :email, :telefon, :divizie, NOW())
        ');
        
        $stmt->execute([
            ':nume' => $payload['nume_echipa'],
            ':capitan' => $user['nume'] . ' ' . $user['prenume'],
            ':email' => $user['email'],
            ':telefon' => $payload['telefon_capitan'] ?? null,
            ':divizie' => (int)$payload['divizie_id']
        ]);
        
        $teamId = (int)$pdo->lastInsertId();
        
        // Asociază jucătorii cu echipa
        $stmt = $pdo->prepare('UPDATE jucatori SET id_echipa = :team_id WHERE id_jucator = :player_id');
        foreach ($selectedPlayers as $playerId) {
            $stmt->execute([
                ':team_id' => $teamId,
                ':player_id' => (int)$playerId
            ]);
        }
        
        $pdo->commit();
        
        flash('success', 'Echipa a fost înregistrată cu succes! Așteaptă validarea administratorului.');
        redirect('index.php');
        
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('danger', 'A apărut o eroare la înregistrare. Te rugăm să încerci din nou.');
        redirect('inscriere.php');
    }
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Înscriere echipă - Smash Cup 5x5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="bg-light">
<?php render_nav('inscriere'); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h4 mb-3">Înscriere echipă</h1>
                    <?php display_flashes(); ?>
                    
                    <form method="POST" action="inscriere.php" id="teamRegistrationForm">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        
                        <div class="mb-3">
                            <label for="nume_echipa" class="form-label">Nume echipă *</label>
                            <input type="text" class="form-control" id="nume_echipa" name="nume_echipa" 
                                   required minlength="3" maxlength="150" 
                                   value="<?= escape_html($_POST['nume_echipa'] ?? '') ?>">
                            <small class="text-muted">Minim 3 caractere</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="divizie_id" class="form-label">Divizie *</label>
                            <select class="form-select" id="divizie_id" name="divizie_id" required>
                                <option value="">Selectează divizia</option>
                                <?php foreach ($divisions as $division): ?>
                                    <option value="<?= (int)$division['id_divizie'] ?>">
                                        <?= escape_html($division['nume_divizie']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="telefon_capitan" class="form-label">Telefon capitan</label>
                            <input type="text" class="form-control" id="telefon_capitan" name="telefon_capitan" 
                                   maxlength="30" value="<?= escape_html($_POST['telefon_capitan'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Selectează 5 jucători *</label>
                            <small class="text-muted d-block mb-2">Trebuie să selectezi exact 5 jucători disponibili</small>
                            
                            <?php if (empty($availablePlayers)): ?>
                                <div class="alert alert-warning">
                                    Nu există jucători disponibili. Contactează administratorul.
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($availablePlayers as $player): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="jucatori[]" 
                                                       value="<?= (int)$player['id_jucator'] ?>" 
                                                       id="player_<?= (int)$player['id_jucator'] ?>"
                                                       onchange="checkPlayerCount()">
                                                <label class="form-check-label" for="player_<?= (int)$player['id_jucator'] ?>">
                                                    <?= escape_html($player['nume'] . ' ' . $player['prenume']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <small id="playerCount" class="text-muted">0 jucători selectați</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                            Înregistrează echipa
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function checkPlayerCount() {
    const checkboxes = document.querySelectorAll('input[name="jucatori[]"]:checked');
    const count = checkboxes.length;
    document.getElementById('playerCount').textContent = count + ' jucători selectați';
    document.getElementById('submitBtn').disabled = count !== 5;
}
</script>
</body>
</html>

