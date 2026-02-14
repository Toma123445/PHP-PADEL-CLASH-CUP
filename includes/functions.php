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

function redirect(string $path = ''): void
{
    if (!defined('APP_URL')) {
        require_once __DIR__ . '/../config.php';
    }
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

