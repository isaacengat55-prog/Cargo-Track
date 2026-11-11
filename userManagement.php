<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$admin = require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post_string('action');

    if ($action === 'create') {
        $name = trim(post_string('name'));
        $email = strtolower(trim(post_string('email')));
        $phone = trim(post_string('phone'));
        $role = post_string('role');
        $password = post_string('password');
        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) {
            $errors[] = 'Enter a name of up to 120 characters.';
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
        if (!in_array($role, ['staff', 'customer'], true)) {
            $errors[] = 'Choose a valid account role.';
        }
        if (strlen($password) < 10) {
            $errors[] = 'Use a password with at least 10 characters.';
        }
        if ($errors) {
            set_flash(implode(' ', $errors), 'error');
        } else {
            $insert = db()->prepare('INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)');
            $insert->execute([$name, $email, $phone !== '' ? $phone : null, password_hash($password, PASSWORD_DEFAULT), $role]);
            set_flash(ucfirst($role) . ' account created.');
        }
    } elseif ($action === 'toggle') {
        $targetId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$targetId || (int)$targetId === (int)$admin['id']) {
            set_flash('You cannot change your own account status here.', 'error');
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $targetQuery = $pdo->prepare('SELECT id, role, active FROM users WHERE id = ? FOR UPDATE');
                $targetQuery->execute([$targetId]);
                $target = $targetQuery->fetch();
                if (!$target) {
                    $pdo->rollBack();
                    set_flash('User account not found.', 'error');
                } elseif ((bool)$target['active'] && $target['role'] === 'admin') {
                    $activeAdmins = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND active = 1 FOR UPDATE")->fetchAll();
                    if (count($activeAdmins) <= 1) {
                        $pdo->rollBack();
                        set_flash('The last active administrator cannot be deactivated.', 'error');
                    } else {
                        $disable = $pdo->prepare('UPDATE users SET active = 0 WHERE id = ?');
                        $disable->execute([$targetId]);
                        $pdo->commit();
                        set_flash('User account deactivated.');
                    }
                } else {
                    $toggle = $pdo->prepare('UPDATE users SET active = ? WHERE id = ?');
                    $toggle->execute([(int)!((bool)$target['active']), $targetId]);
                    $pdo->commit();
                    set_flash((bool)$target['active'] ? 'User account deactivated.' : 'User account reactivated.');
                }
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $error;
            }
        }
    } else {
        set_flash('That user action is not supported.', 'error');
    }
    redirect('userManagement.php');
}

$users = db()->query('SELECT id, name, email, phone, role, active, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$pageTitle = 'User management';
require __DIR__ . '/header.php';
?>
<div class="page-heading">
    <div><div class="eyebrow">Administration</div><h1>User accounts</h1><p class="muted">Create staff or customer accounts and manage access.</p></div>
</div>
<div class="detail-grid">
    <section class="card">
        <h2>Create an account</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="create">
            <div class="field"><label for="name">Full name</label><input id="name" name="name" maxlength="120" required></div>
            <div class="field" style="margin-top:13px"><label for="email">Email address</label><input id="email" name="email" type="email" maxlength="190" required></div>
            <div class="field" style="margin-top:13px"><label for="phone">Phone number <span class="muted">(optional)</span></label><input id="phone" name="phone" maxlength="32" type="tel"></div>
            <div class="field" style="margin-top:13px"><label for="role">Account role</label><select id="role" name="role" required><option value="customer">Customer</option><option value="staff">Staff</option></select></div>
            <div class="field" style="margin-top:13px"><label for="password">Temporary password</label><input id="password" name="password" type="password" minlength="10" required><p class="hint">Share this with the account holder securely. They should use a unique password.</p></div>
            <button class="button" type="submit" style="width:100%;margin-top:20px">Create account</button>
        </form>
    </section>
    <section class="card">
        <h2>Account access</h2>
        <p class="muted">Deactivated accounts cannot sign in. Existing shipment records are retained.</p>
        <div class="table-wrap"><table>
            <thead><tr><th>User</th><th>Role</th><th>Access</th><th>Action</th></tr></thead>
            <tbody><?php foreach ($users as $account): ?>
                <tr>
                    <td><strong><?= h($account['name']) ?></strong><br><span class="muted"><?= h($account['email']) ?></span></td>
                    <td><?= h(ucfirst($account['role'])) ?></td>
                    <td><span class="status-pill <?= $account['active'] ? '' : 'status-cancelled' ?>"><?= $account['active'] ? 'Active' : 'Disabled' ?></span></td>
                    <td>
                        <?php if ((int)$account['id'] !== (int)$admin['id']): ?>
                            <form class="inline-form" method="post">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="user_id" value="<?= (int)$account['id'] ?>">
                                <button class="button button-small <?= $account['active'] ? 'button-danger' : 'button-secondary' ?>" type="submit"><?= $account['active'] ? 'Deactivate' : 'Reactivate' ?></button>
                            </form>
                        <?php else: ?><span class="muted">You</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </section>
</div>
<?php require __DIR__ . '/footer.php'; ?>
