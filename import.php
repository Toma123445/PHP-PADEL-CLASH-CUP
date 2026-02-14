<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/import.php';
require_once __DIR__ . '/config.php';

define('APP_URL', APP_URL);

$pdo = get_db();
$errors = [];
$success = false;
$importResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token CSRF invalid.';
    } else {
        $file = $_FILES['file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Eroare la încărcarea fișierului.';
        } else {
            $allowed = ['xlsx', 'xls', 'csv'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                $errors[] = 'Format fișier invalid. Folosește Excel (.xlsx, .xls) sau CSV.';
            } else {
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $uploadPath = $uploadDir . uniqid() . '_' . $file['name'];
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    try {
                        $format = in_array($ext, ['xlsx', 'xls']) ? 'excel' : 'csv';
                        $importResult = import_teams_from_file($uploadPath, $format);
                        $success = true;
                        unlink($uploadPath); // Delete file after import
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                        if (file_exists($uploadPath)) {
                            unlink($uploadPath);
                        }
                    }
                } else {
                    $errors[] = 'Nu s-a putut salva fișierul.';
                }
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
    <title>Import Date - <?= htmlspecialchars(APP_NAME) ?></title>
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

        <h1 class="mb-4"><i class="bi bi-upload"></i> Import Date</h1>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Import Echipe din Fișier</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($success && $importResult): ?>
                            <div class="alert alert-success">
                                <h5>Import finalizat!</h5>
                                <p><strong>Total procesate:</strong> <?= $importResult['total'] ?></p>
                                <p><strong>Importate cu succes:</strong> <?= count($importResult['imported']) ?></p>
                                <?php if (!empty($importResult['imported'])): ?>
                                    <p><strong>Echipe importate:</strong></p>
                                    <ul>
                                        <?php foreach ($importResult['imported'] as $team): ?>
                                            <li><?= htmlspecialchars($team) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <?php if (!empty($importResult['errors'])): ?>
                                    <p class="text-danger"><strong>Erori:</strong></p>
                                    <ul>
                                        <?php foreach ($importResult['errors'] as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                            <div class="mb-3">
                                <label for="file" class="form-label">Selectează fișier (Excel sau CSV)</label>
                                <input type="file" class="form-control" id="file" name="file" 
                                       accept=".xlsx,.xls,.csv" required>
                                <small class="form-text text-muted">
                                    Format: Excel (.xlsx, .xls) sau CSV. Prima linie poate conține anteturi.
                                    Coloane: Nume Echipă, Capitan, Email, Telefon
                                </small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-upload"></i> Importă Date
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Instrucțiuni</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Format fișier:</strong></p>
                        <ul>
                            <li>Excel (.xlsx, .xls)</li>
                            <li>CSV (separator: virgulă)</li>
                        </ul>
                        <p><strong>Structură:</strong></p>
                        <ul>
                            <li>Coloana 1: Nume Echipă</li>
                            <li>Coloana 2: Capitan</li>
                            <li>Coloana 3: Email</li>
                            <li>Coloana 4: Telefon (opțional)</li>
                        </ul>
                        <p class="text-muted small">
                            Prima linie poate conține anteturi care vor fi ignorate.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

