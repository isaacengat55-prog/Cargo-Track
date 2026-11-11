<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

date_default_timezone_set('Africa/Nairobi');

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'httponly' => true,
    'secure' => $isHttps,
    'samesite' => 'Lax',
]);
session_start();

function db(): PDO
{
    static $connection;
    if (!$connection instanceof PDO) {
        $connection = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    return $connection;
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

function query_string(string $key): string
{
    $value = $_GET[$key] ?? '';
    return is_string($value) ? $value : '';
}

function app_url(string $path = ''): string
{
    return APP_BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . app_url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(403);
        exit('The form expired or could not be verified. Please go back and try again.');
    }
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function current_user(): ?array
{
    static $checked = false;
    static $user = null;
    if ($checked) {
        return $user;
    }
    $checked = true;

    $id = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        return null;
    }

    $statement = db()->prepare('SELECT id, name, email, role, active FROM users WHERE id = ?');
    $statement->execute([$id]);
    $record = $statement->fetch();
    if (!$record || !(bool)$record['active']) {
        $_SESSION = [];
        session_regenerate_id(true);
        return null;
    }

    $user = $record;
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        set_flash('Please sign in to continue.', 'error');
        redirect('index.php');
    }

    return $user;
}

function require_role(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }

    return $user;
}

function format_status(string $status): string
{
    return str_replace('_', ' ', $status);
}
