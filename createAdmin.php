<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/bootstrap.php';

$existing = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
if ($existing > 0) {
    fwrite(STDERR, "An administrator already exists. Create additional accounts from the Users page.\n");
    exit(1);
}

$name = trim((string)readline('Administrator name: '));
$email = strtolower(trim((string)readline('Administrator email: ')));
$password = (string)readline('Password (at least 10 characters): ');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
    fwrite(STDERR, "Enter a name, a valid email, and a password of at least 10 characters.\n");
    exit(1);
}

$statement = db()->prepare(
    "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')"
);
$statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
fwrite(STDOUT, "Administrator account created. Sign in at " . app_url('index.php') . "\n");
