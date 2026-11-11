<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

$errors = [];
$name = '';
$email = '';
$phone = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim(post_string('name'));
    $email = strtolower(trim(post_string('email')));
    $phone = trim(post_string('phone'));
    $password = post_string('password');

    if ($name === '' || mb_strlen($name) > 120) {
        $errors[] = 'Enter your name (up to 120 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    } else {
        $check = db()->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }
    if (strlen($phone) > 32) {
        $errors[] = 'Phone number must be 32 characters or fewer.';
    }
    if (strlen($password) < 10) {
        $errors[] = 'Use a password with at least 10 characters.';
    }

    if (!$errors) {
        $statement = db()->prepare(
            "INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'customer')"
        );
        $statement->execute([$name, $email, $phone !== '' ? $phone : null, password_hash($password, PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)db()->lastInsertId();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        redirect('dashboard.php');
    }
}

$pageTitle = 'Create account';
require __DIR__ . '/header.php';
?>
<div class="page-heading">
    <div><div class="eyebrow">Customer access</div><h1>Create your account</h1><p class="muted">Register to see shipments assigned to you.</p></div>
</div>
<form class="card form-card" method="post">
    <h2>Account details</h2>
    <?php if ($errors): ?><div class="alert alert-error" role="alert"><?= h(implode(' ', $errors)) ?></div><?php endif; ?>
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <div class="form-grid">
        <div class="field field-wide"><label for="name">Full name</label><input id="name" name="name" maxlength="120" required value="<?= h($name) ?>"></div>
        <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" maxlength="190" autocomplete="email" required value="<?= h($email) ?>"></div>
        <div class="field"><label for="phone">Phone number <span class="muted">(optional)</span></label><input id="phone" name="phone" type="tel" maxlength="32" autocomplete="tel" value="<?= h($phone) ?>"></div>
        <div class="field field-wide"><label for="password">Password</label><input id="password" name="password" type="password" minlength="10" autocomplete="new-password" required><p class="hint">Use at least 10 characters.</p></div>
    </div>
    <div class="form-actions"><a class="button button-secondary" href="<?= h(app_url('index.php')) ?>">Back to sign in</a><button class="button" type="submit">Create customer account</button></div>
</form>
<?php require __DIR__ . '/footer.php'; ?>
