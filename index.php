<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identifier = trim(post_string('identifier'));
    $password = post_string('password');
    $account = false;

    if ($identifier !== '') {
        $statement = db()->prepare('SELECT id, password_hash, active FROM users WHERE email = ?');
        $statement->execute([strtolower($identifier)]);
        $account = $statement->fetch();

        if (!$account && !filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $statement = db()->prepare('SELECT id, password_hash, active FROM users WHERE name = ? LIMIT 2');
            $statement->execute([$identifier]);
            $matches = $statement->fetchAll();
            if (count($matches) === 1) {
                $account = $matches[0];
            }
        }
    }

    if ($account && (bool)$account['active'] && password_verify($password, $account['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$account['id'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        redirect('dashboard.php');
    }
    $error = 'Email or password was not recognized.';
}

$pageTitle = 'Sign in';
require __DIR__ . '/header.php';
?>
<section class="login-layout">
    <div class="hero-panel">
        <div class="eyebrow">Cargo visibility, made clear</div>
        <h1>Every shipment.<br><span>Every step.</span></h1>
        <p>A clearer view of your cargo journey, from registration to delivery confirmation. Find shipment updates and history in one place.</p>
        <div class="hero-note">
            <span class="hero-note-mark">↗</span>
            <span><strong>One trusted record</strong><br>Status updates and movement history, together.</span>
        </div>
    </div>
    <form class="card auth-card" method="post">
        <div class="eyebrow">Welcome back</div>
        <h2>Sign in to CargoTrack</h2>
        <p class="muted">Access your shipments and updates.</p>
        <?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= h($error) ?></div><?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <div class="field">
            <label for="identifier">Email address or account name</label>
            <input id="identifier" name="identifier" type="text" autocomplete="username" required value="<?= h(post_string('identifier')) ?>">
            <p class="hint">You can sign in with any active account, including staff and customer accounts.</p>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="button" type="submit">Sign in</button>
        <p class="auth-switch">New to CargoTrack? <a href="<?= h(app_url('register.php')) ?>">Create a customer account</a></p>
    </form>
</section>
<?php require __DIR__ . '/footer.php'; ?>
