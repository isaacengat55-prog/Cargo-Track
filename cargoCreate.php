<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$user = require_role(['admin', 'staff']);
$customers = db()->query("SELECT id, name, email FROM users WHERE role = 'customer' AND active = 1 ORDER BY name")->fetchAll();

$pageTitle = 'Register cargo';
require __DIR__ . '/header.php';
?>
<div class="page-heading">
    <div><div class="eyebrow">New shipment</div><h1>Register cargo</h1><p class="muted">Create a shipment record and issue its tracking number.</p></div>
</div>
<form class="card form-card" method="post" action="<?= h(app_url('cargoSave.php')) ?>">
    <h2>Shipment details</h2>
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <div class="form-grid">
        <div class="field field-wide">
            <label for="customer_id">Customer account</label>
            <select id="customer_id" name="customer_id" required>
                <option value="">Select a customer</option>
                <?php foreach ($customers as $customer): ?><option value="<?= (int)$customer['id'] ?>"><?= h($customer['name']) ?> — <?= h($customer['email']) ?></option><?php endforeach; ?>
            </select>
            <?php if (!$customers): ?><p class="hint">Create or activate a customer account before registering cargo.</p><?php endif; ?>
        </div>
        <div class="field field-wide"><label for="description">Cargo description</label><input id="description" name="description" maxlength="250" required placeholder="e.g. Packaged household goods"></div>
        <div class="field"><label for="sender_name">Sender name</label><input id="sender_name" name="sender_name" maxlength="120" required></div>
        <div class="field"><label for="recipient_name">Recipient name</label><input id="recipient_name" name="recipient_name" maxlength="120" required></div>
        <div class="field"><label for="origin">Origin</label><input id="origin" name="origin" maxlength="160" required placeholder="e.g. Mombasa"></div>
        <div class="field"><label for="destination">Destination</label><input id="destination" name="destination" maxlength="160" required placeholder="e.g. Nairobi"></div>
        <div class="field field-wide"><label for="expected_delivery">Expected delivery date <span class="muted">(optional)</span></label><input id="expected_delivery" name="expected_delivery" type="date"></div>
    </div>
    <div class="form-actions"><a class="button button-secondary" href="<?= h(app_url('cargo.php')) ?>">Cancel</a><button class="button" type="submit" <?= !$customers ? 'disabled' : '' ?>>Create shipment</button></div>
</form>
<?php require __DIR__ . '/footer.php'; ?>
