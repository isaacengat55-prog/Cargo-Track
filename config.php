<?php
declare(strict_types=1);

define('APP_NAME', 'CargoTrack Kenya');

$localConfigPath = __DIR__ . '/config.local.php';
$localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
if (!is_array($localConfig)) {
    throw new RuntimeException('config.local.php must return an array.');
}

foreach ($localConfig as $key => $value) {
    if (!in_array($key, ['app_base_url', 'db_host', 'db_name', 'db_user', 'db_pass'], true) || !is_string($value)) {
        throw new RuntimeException('config.local.php contains an invalid setting.');
    }
}

$basePath = $localConfig['app_base_url'] ?? getenv('APP_BASE_URL');
define('APP_BASE_URL', rtrim($basePath === false || $basePath === null ? '/isaac' : $basePath, '/'));

define('DB_HOST', $localConfig['db_host'] ?? (getenv('DB_HOST') ?: '127.0.0.1'));
define('DB_NAME', $localConfig['db_name'] ?? (getenv('DB_NAME') ?: 'cargo_track'));
define('DB_USER', $localConfig['db_user'] ?? (getenv('DB_USER') ?: 'root'));
define('DB_PASS', $localConfig['db_pass'] ?? (getenv('DB_PASS') === false ? '' : getenv('DB_PASS')));
