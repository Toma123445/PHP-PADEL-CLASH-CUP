<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config.php';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-trophy"></i> <?= htmlspecialchars(APP_NAME) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="bi bi-house"></i> Acasă</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="inscriere.php"><i class="bi bi-pencil-square"></i> Înscriere</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="echipe.php"><i class="bi bi-people"></i> Echipe</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="meciuri.php"><i class="bi bi-calendar3"></i> Meciuri</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="clasament.php"><i class="bi bi-trophy"></i> Clasament</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contact.php"><i class="bi bi-envelope"></i> Contact</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="exportDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-download"></i> Export
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="export.php?format=excel"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</a></li>
                        <li><a class="dropdown-item" href="export.php?format=pdf"><i class="bi bi-file-pdf"></i> PDF</a></li>
                        <li><a class="dropdown-item" href="export.php?format=doc"><i class="bi bi-file-word"></i> DOC</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

