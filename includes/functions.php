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

