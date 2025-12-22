<?php





declare(strict_types=1);



if (session_status() === PHP_SESSION_NONE) {

    session_start();

}



/**

 * Cleans input data to avoid accidental whitespace or HTML injection.

 */

function sanitize(array $data): array

{

    return array_map(static function ($value) {

        if (!is_string($value)) {

            return $value;

        }



        return trim(filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS));

    }, $data);

}



/**

 * Validates required fields exist and are not empty.

 *

 * @return array<string, string> Array of field => error message

 */

function validate_required(array $data, array $requiredFields): array

{

    $errors = [];



    foreach ($requiredFields as $field => $label) {

        $key = is_string($field) ? $field : $label;

        $labelText = is_string($field) ? $label : ucfirst((string)$label);



        if (!isset($data[$key]) || $data[$key] === '') {

            $errors[$key] = sprintf('Campul "%s" este obligatoriu.', $labelText);

        }

    }



    return $errors;

}



function flash(string $type, string $message): void

{

    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];

}



/**

 * Returns and clears flash messages.

 */

function get_flashes(): array

{

    $messages = $_SESSION['flash'] ?? [];

    unset($_SESSION['flash']);



    return $messages;

}



function display_flashes(): void

{

    foreach (get_flashes() as $flash) {

        $type = htmlspecialchars($flash['type'], ENT_QUOTES);

        $message = $flash['message'];

        echo "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">{$message}"

            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'

            . '</div>';

    }

}



function redirect(string $path = ''): void

{

    $url = $path ?: APP_URL;

    header("Location: {$url}");

    exit;

}



function csrf_token(): string

{

    if (empty($_SESSION['csrf_token'])) {

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    }



    return $_SESSION['csrf_token'];

}



function verify_csrf(?string $token): bool

{

    return isset($_SESSION['csrf_token'], $token) && hash_equals($_SESSION['csrf_token'], $token);

}



function login_user(array $user): void

{

    $_SESSION['user'] = [

        'id'    => (int)$user['id_utilizator'],

        'email' => $user['email'],

        'role'  => $user['rol'],

        'nume'  => $user['nume'],

        'prenume' => $user['prenume'],

    ];

}



function logout_user(): void

{

    unset($_SESSION['user']);

}



function current_user(): ?array

{

    return $_SESSION['user'] ?? null;

}



function is_logged_in(): bool

{

    return current_user() !== null;

}



function is_admin(): bool

{

    return is_logged_in() && current_user()['role'] === 'admin';

}



function require_login(): void

{

    if (!is_logged_in()) {

        flash('danger', 'Trebuie sa fii autentificat pentru a accesa aceasta zona.');

        redirect('login.php');

    }

}



function require_admin(): void

{

    require_login();

    if (!is_admin()) {

        flash('danger', 'Nu ai permisiunea necesara.');

        redirect('index.php');

    }

}



/**
 * Verifica reCAPTCHA v2 cu Google
 * Returneaza true daca verificarea a reusit, false altfel
 */
function verify_recaptcha(?string $recaptchaResponse): bool

{

    if (empty($recaptchaResponse)) {

        return false;

    }

    require_once __DIR__ . '/config.php';

    $secretKey = RECAPTCHA_SECRET_KEY ?? '';

    if (empty($secretKey)) {

        // Daca nu e configurat, permite (pentru development)

        return true;

    }

    $url = 'https://www.google.com/recaptcha/api/siteverify';

    $data = [

        'secret' => $secretKey,

        'response' => $recaptchaResponse,

        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''

    ];

    $options = [

        'http' => [

            'header' => "Content-type: application/x-www-form-urlencoded\r\n",

            'method' => 'POST',

            'content' => http_build_query($data)

        ]

    ];

    $context = stream_context_create($options);

    $result = @file_get_contents($url, false, $context);

    

    if ($result === false) {

        return false;

    }

    $json = json_decode($result, true);

    return isset($json['success']) && $json['success'] === true;

}



/**
 * Rate limiting simplu pentru a preveni atacuri brute force
 */
function check_rate_limit(string $key, int $maxAttempts = 5, int $timeWindow = 300): bool

{

    if (!isset($_SESSION['rate_limit'])) {

        $_SESSION['rate_limit'] = [];

    }

    $now = time();

    $attempts = $_SESSION['rate_limit'][$key] ?? ['count' => 0, 'reset' => $now + $timeWindow];

    if ($now > $attempts['reset']) {

        $_SESSION['rate_limit'][$key] = ['count' => 0, 'reset' => $now + $timeWindow];

        $attempts = $_SESSION['rate_limit'][$key];

    }

    if ($attempts['count'] >= $maxAttempts) {

        return false; // Limita depasita

    }

    $_SESSION['rate_limit'][$key]['count']++;

    return true;

}



/**
 * Validare email mai strictă
 */
function validate_email(string $email): bool

{

    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && 

           strlen($email) <= 150;

}



/**
 * Validare parola mai strictă
 */
function validate_password(string $password): array

{

    $errors = [];

    

    if (strlen($password) < 8) {

        $errors[] = 'Parola trebuie să aibă minim 8 caractere.';

    }

    

    if (!preg_match('/[A-Z]/', $password)) {

        $errors[] = 'Parola trebuie să conțină cel puțin o literă mare.';

    }

    

    if (!preg_match('/[a-z]/', $password)) {

        $errors[] = 'Parola trebuie să conțină cel puțin o literă mică.';

    }

    

    if (!preg_match('/[0-9]/', $password)) {

        $errors[] = 'Parola trebuie să conțină cel puțin o cifră.';

    }

    

    return $errors;

}



/**
 * Sanitizare mai strictă pentru output HTML
 */
function escape_html(string $string): string

{

    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');

}



/**
 * Verificare dacă request-ul vine de pe același domeniu (HTTP Request Spoofing protection)
 */
function verify_referer(): bool

{

    if (!isset($_SERVER['HTTP_REFERER'])) {

        return false;

    }

    

    $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);

    $host = $_SERVER['HTTP_HOST'] ?? '';

    

    return $referer === $host;

}



/**
 * Verificare dacă utilizatorul este capitan
 */
function is_captain(): bool

{

    if (!is_logged_in()) {

        return false;

    }

    

    require_once __DIR__ . '/db_connect.php';

    $user = current_user();

    $pdo = get_pdo();

    

    $stmt = $pdo->prepare('

        SELECT COUNT(*) FROM echipe 

        WHERE capitan = :nume_complet 

        OR email_capitan = :email

    ');

    

    $stmt->execute([

        ':nume_complet' => $user['nume'] . ' ' . $user['prenume'],

        ':email' => $user['email']

    ]);

    

    return (int)$stmt->fetchColumn() > 0;

}



function require_captain(): void

{

    require_login();

    

    if (!is_captain() && !is_admin()) {

        flash('danger', 'Nu ai permisiunea necesara. Trebuie sa fii capitan de echipa.');

        redirect('index.php');

    }

}



