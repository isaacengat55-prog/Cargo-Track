<?php
$signedInUser = current_user();
$pageTitle = $pageTitle ?? APP_NAME;
$flashMessage = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?> | <?= h(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= h(app_url('style.css')) ?>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= h(app_url($signedInUser ? 'dashboard.php' : 'index.php')) ?>">
        <span class="brand-mark">CT</span>
        <span>CargoTrack <small>KENYA</small></span>
    </a>
    <?php if ($signedInUser): ?>
        <nav class="main-nav" aria-label="Main navigation">
            <a href="<?= h(app_url('dashboard.php')) ?>">Dashboard</a>
            <a href="<?= h(app_url('cargo.php')) ?>">Shipments</a>
            <a href="<?= h(app_url('reports.php')) ?>">Reports</a>
            <?php if ($signedInUser['role'] === 'admin'): ?>
                <a href="<?= h(app_url('userManagement.php')) ?>">Users</a>
            <?php endif; ?>
        </nav>
        <div class="account">
            <span><?= h($signedInUser['name']) ?> <small><?= h(ucfirst($signedInUser['role'])) ?></small></span>
            <form method="post" action="<?= h(app_url('logout.php')) ?>">
                <?= csrf_field() ?>
                <button class="button button-quiet" type="submit">Sign out</button>
            </form>
        </div>
    <?php else: ?>
        <a class="button button-outline" href="<?= h(app_url('register.php')) ?>">Create account</a>
    <?php endif; ?>
</header>
<main class="page-shell">
    <?php if ($flashMessage): ?>
        <div class="alert alert-<?= h($flashMessage['type']) ?>" role="status"><?= h($flashMessage['message']) ?></div>
    <?php endif; ?>
