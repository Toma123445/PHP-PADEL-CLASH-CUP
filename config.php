<?php

declare(strict_types=1);

// Configurare aplicatie
define('APP_NAME', 'Smash Cup 5x5');
define('APP_URL', 'http://localhost/PHP-PADEL-CLASH-CUP/');
define('APP_EMAIL', 'noreply@smashcup.ro');

// Configurare baza de date
define('DB_HOST', 'localhost');
define('DB_NAME', 'padel_tournament');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurare email (pentru PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com'); // Modifica cu adresa ta
define('SMTP_PASS', 'your-app-password'); // Modifica cu parola aplicatiei
define('SMTP_FROM_EMAIL', 'noreply@smashcup.ro');
define('SMTP_FROM_NAME', 'Smash Cup 5x5');

// Timezone
date_default_timezone_set('Europe/Bucharest');

