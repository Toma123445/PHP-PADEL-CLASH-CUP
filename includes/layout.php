<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function render_nav(string $active = ''): void
{
    $user = current_user();
    $isAdmin = is_admin();
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Smash Cup 5x5</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                    aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if ($user): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $active === 'dashboard' ? 'active' : '' ?>" href="index.php">Acasa</a>
                        </li>
                        <?php if ($isAdmin): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $active === 'echipe' ? 'active' : '' ?>" href="echipe.php">Echipe</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $active === 'jucatori' ? 'active' : '' ?>" href="jucatori.php">Jucatori</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $active === 'profil' ? 'active' : '' ?>" href="profil.php">Profil</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($user): ?>
                        <span class="text-white small"><?= htmlspecialchars($user['nume'] . ' ' . $user['prenume']) ?> (<?= htmlspecialchars($user['role']) ?>)</span>
                        <form method="POST" action="logout.php" class="m-0">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <button class="btn btn-outline-light btn-sm">Logout</button>
                        </form>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light btn-sm">Login</a>
                        <a href="register.php" class="btn btn-primary btn-sm">Creeaza cont</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php
}

