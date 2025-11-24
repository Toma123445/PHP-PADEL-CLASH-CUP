<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf($_POST['csrf_token'] ?? null)) {
        logout_user();
        flash('success', 'Ai fost delogat.');
    } else {
        flash('danger', 'Token CSRF invalid.');
    }
}

redirect('login.php');

