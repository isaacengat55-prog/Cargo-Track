<?php
declare(strict_types=1);

define('APP_NAME', 'CargoTrack Kenya');
$basePath = getenv('APP_BASE_URL');
define('APP_BASE_URL', rtrim($basePath === false ? '/isaac' : $basePath, '/'));

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'cargo_track');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') === false ? '' : getenv('DB_PASS'));
