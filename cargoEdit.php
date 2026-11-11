<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_role(['admin', 'staff']);
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit('Shipment not found.');
}
$statement = db()->prepare('SELECT * FROM cargo WHERE id = ?');
$statement->execute([$id]);
$shipment = $statement->fetch();
if (!$shipment) {
    http_response_code(404);
    exit('Shipment not found.');
}

$pageTitle = 'Edit ' . $shipment['tracking_number'];
require __DIR__ . '/header.php';
?>
<div class="page-heading">
    <div><div class="eyebrow">Shipment record</div><h1>Edit details</h1><p class="muted">Tracking number <?= h($shipment['tracking_number']) ?> stays the same.</p></div>
</div>
<form class="card form-card" method="post" action="<?= h(app_url('cargoUpdateInfo.php')) ?>">
    <h2>Cargo information</h2>
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="cargo_id" value="<?= (int)$shipment['id'] ?>">
    <div class="form-grid">
        <div class="field field-wide"><label for="description">Cargo description</label><input id="description" name="description" maxlength="250" required value="<?= h($shipment['description']) ?>"></div>
        <div class="field"><label for="sender_name">Sender name</label><input id="sender_name" name="sender_name" maxlength="120" required value="<?= h($shipment['sender_name']) ?>"></div>
        <div class="field"><label for="recipient_name">Recipient name</label><input id="recipient_name" name="recipient_name" maxlength="120" required value="<?= h($shipment['recipient_name']) ?>"></div>
        <div class="field"><label for="origin">Origin</label><input id="origin" name="origin" maxlength="160" required value="<?= h($shipment['origin']) ?>"></div>
        <div class="field"><label for="destination">Destination</label><input id="destination" name="destination" maxlength="160" required value="<?= h($shipment['destination']) ?>"></div>
        <div class="field field-wide"><label for="expected_delivery">Expected delivery date <span class="muted">(optional)</span></label><input id="expected_delivery" name="expected_delivery" type="date" value="<?= h($shipment['expected_delivery']) ?>"></div>
    </div>
    <div class="form-actions">
        <a class="button button-secondary" href="<?= h(app_url('cargoView.php?id=' . (int)$shipment['id'])) ?>">Cancel</a>
        <button class="button" type="submit">Save changes</button>
    </div>
</form>
<?php require __DIR__ . '/footer.php'; ?>
