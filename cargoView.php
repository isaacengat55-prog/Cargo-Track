<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$user = require_login();
$isStaff = in_array($user['role'], ['admin', 'staff'], true);
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit('Shipment not found.');
}
$sql = 'SELECT c.*, u.name AS customer_name, u.email AS customer_email, creator.name AS creator_name
    FROM cargo c JOIN users u ON u.id = c.customer_id JOIN users creator ON creator.id = c.created_by
    WHERE c.id = ?';
$statement = db()->prepare($sql);
$statement->execute([$id]);
$shipment = $statement->fetch();
if (!$shipment || (!$isStaff && (int)$shipment['customer_id'] !== (int)$user['id'])) {
    http_response_code(404);
    exit('Shipment not found.');
}
$eventStatement = db()->prepare(
    'SELECT e.*, u.name AS updated_by_name FROM cargo_events e JOIN users u ON u.id = e.updated_by
     WHERE e.cargo_id = ? ORDER BY e.created_at DESC, e.id DESC'
);
$eventStatement->execute([$id]);
$events = $eventStatement->fetchAll();
$statuses = ['picked_up', 'in_transit', 'at_customs', 'out_for_delivery', 'delayed', 'delivered', 'cancelled'];
$terminal = in_array($shipment['status'], ['delivered', 'cancelled'], true);

$pageTitle = 'Shipment ' . $shipment['tracking_number'];
require __DIR__ . '/header.php';
?>
<div class="page-heading">
    <div><div class="eyebrow">Shipment details</div><h1><?= h($shipment['tracking_number']) ?></h1><p class="muted"><?= h($shipment['description']) ?></p></div>
    <div class="inline-form">
        <?php if ($isStaff): ?><a class="button button-secondary" href="<?= h(app_url('cargoEdit.php?id=' . (int)$shipment['id'])) ?>">Edit details</a><?php endif; ?>
        <span class="status-pill status-<?= h($shipment['status']) ?>"><?= h(ucwords(format_status($shipment['status']))) ?></span>
    </div>
</div>
<div class="detail-grid">
    <div>
        <section class="card">
            <h2>Shipment information</h2>
            <div class="detail-list">
                <div class="detail-item"><span>Sender</span><strong><?= h($shipment['sender_name']) ?></strong></div>
                <div class="detail-item"><span>Recipient</span><strong><?= h($shipment['recipient_name']) ?></strong></div>
                <div class="detail-item"><span>Route</span><strong><?= h($shipment['origin']) ?> &rarr; <?= h($shipment['destination']) ?></strong></div>
                <div class="detail-item"><span>Expected delivery</span><strong><?= $shipment['expected_delivery'] ? h(date('M j, Y', strtotime($shipment['expected_delivery']))) : 'Not provided' ?></strong></div>
                <?php if ($isStaff): ?><div class="detail-item"><span>Customer</span><strong><?= h($shipment['customer_name']) ?><br><span class="muted"><?= h($shipment['customer_email']) ?></span></strong></div><?php endif; ?>
                <div class="detail-item"><span>Registered</span><strong><?= h(date('M j, Y H:i', strtotime($shipment['created_at']))) ?></strong></div>
                <?php if ($shipment['delivered_at']): ?><div class="detail-item"><span>Delivery confirmed</span><strong><?= h($shipment['delivered_to']) ?> · <?= h(date('M j, Y H:i', strtotime($shipment['delivered_at']))) ?></strong></div><?php endif; ?>
            </div>
        </section>
        <section class="card">
            <h2>Movement history</h2>
            <?php if (!$events): ?><div class="empty-state">No movement updates have been recorded.</div>
            <?php else: ?><ol class="timeline">
                <?php foreach ($events as $event): ?>
                    <li class="timeline-item">
                        <strong><?= h(ucwords(format_status($event['status']))) ?></strong>
                        <?php if ($event['location']): ?><p><?= h($event['location']) ?></p><?php endif; ?>
                        <?php if ($event['notes']): ?><p><?= h($event['notes']) ?></p><?php endif; ?>
                        <?php if ($event['status'] === 'delivered' && $shipment['delivered_to']): ?><p>Received by <?= h($shipment['delivered_to']) ?></p><?php endif; ?>
                        <?php if ($event['latitude'] !== null && $event['longitude'] !== null): ?><p class="coordinates">GPS <?= h((string)$event['latitude']) ?>, <?= h((string)$event['longitude']) ?></p><?php endif; ?>
                        <time><?= h(date('M j, Y · H:i', strtotime($event['created_at']))) ?> · <?= h($event['updated_by_name']) ?></time>
                    </li>
                <?php endforeach; ?>
            </ol><?php endif; ?>
        </section>
    </div>
    <aside>
        <?php if ($isStaff && !$terminal): ?>
        <form class="card" method="post" action="<?= h(app_url('cargoUpdateStatus.php')) ?>">
            <h2>Update shipment</h2>
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="cargo_id" value="<?= (int)$shipment['id'] ?>">
            <div class="field"><label for="status">New status</label><select id="status" name="status" required>
                <?php foreach ($statuses as $status): ?><option value="<?= h($status) ?>" <?= $shipment['status'] === $status ? 'selected' : '' ?>><?= h(ucwords(format_status($status))) ?></option><?php endforeach; ?>
            </select></div>
            <div class="field" style="margin-top:14px"><label for="location">Current location</label><input id="location" name="location" maxlength="160" placeholder="e.g. Mariakani"></div>
            <div class="field" style="margin-top:14px"><label for="notes">Update notes <span class="muted">(optional)</span></label><textarea id="notes" name="notes" maxlength="500" placeholder="Add relevant shipment information"></textarea></div>
            <div id="delivery-field" class="field" style="display:none;margin-top:14px"><label for="delivered_to">Received by</label><input id="delivered_to" name="delivered_to" maxlength="120" placeholder="Recipient's name"></div>
            <div class="field" style="margin-top:14px">
                <label for="latitude">GPS coordinates <span class="muted">(optional)</span></label>
                <div class="form-grid" style="grid-template-columns:1fr 1fr">
                    <input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" aria-label="Latitude" placeholder="Latitude">
                    <input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" aria-label="Longitude" placeholder="Longitude">
                </div>
                <button class="button button-secondary button-small" id="locate-button" type="button" style="margin-top:8px">Use my current location</button>
                <p class="hint" id="location-message">Location is only attached if you choose to share it.</p>
            </div>
            <button class="button" type="submit" style="width:100%;margin-top:18px">Save update</button>
        </form>
        <script>
            const statusSelect = document.getElementById('status');
            const deliveryField = document.getElementById('delivery-field');
            const deliveredTo = document.getElementById('delivered_to');
            const updateDeliveryField = () => {
                const isDelivered = statusSelect.value === 'delivered';
                deliveryField.style.display = isDelivered ? 'flex' : 'none';
                deliveredTo.required = isDelivered;
            };
            statusSelect.addEventListener('change', updateDeliveryField);
            updateDeliveryField();
            document.getElementById('locate-button').addEventListener('click', () => {
                const message = document.getElementById('location-message');
                if (!navigator.geolocation) {
                    message.textContent = 'This browser does not provide location services.';
                    return;
                }
                message.textContent = 'Waiting for location permission…';
                navigator.geolocation.getCurrentPosition(
                    ({coords}) => {
                        document.getElementById('latitude').value = coords.latitude;
                        document.getElementById('longitude').value = coords.longitude;
                        message.textContent = 'Location added to this update.';
                    },
                    () => { message.textContent = 'Location unavailable or permission denied. You can still save the update.'; },
                    {enableHighAccuracy: false, timeout: 10000, maximumAge: 60000}
                );
            });
        </script>
        <?php elseif ($isStaff && $terminal): ?>
            <section class="card"><h2>Shipment closed</h2><p class="muted">This shipment is <?= h(format_status($shipment['status'])) ?>. Its status history is retained for reporting.</p></section>
        <?php else: ?>
            <section class="card"><h2>Need help?</h2><p class="muted">Contact your CargoTrack representative with tracking number <strong><?= h($shipment['tracking_number']) ?></strong> if you need assistance.</p></section>
        <?php endif; ?>
    </aside>
</div>
<?php require __DIR__ . '/footer.php'; ?>
