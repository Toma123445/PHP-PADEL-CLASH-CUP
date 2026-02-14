<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config.php';
}
?>
<footer class="bg-dark text-light mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5><?= htmlspecialchars(APP_NAME) ?></h5>
                <p class="text-muted">Sistem de management pentru turneul de padel 5x5</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="text-muted mb-0">
                    &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>. Toate drepturile rezervate.
                </p>
                <p class="text-muted small">
                    <a href="contact.php" class="text-light text-decoration-none">Contact</a> |
                    <a href="admin.php" class="text-light text-decoration-none">Admin</a>
                </p>
            </div>
        </div>
    </div>
</footer>

