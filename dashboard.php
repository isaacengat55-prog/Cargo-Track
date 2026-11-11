<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$user = require_login();
$isStaff = in_array($user['role'], ['admin', 'staff'], true);

$countSql = 'SELECT status, COUNT(*) AS total FROM cargo';
$recentSql = 'SELECT c.id, c.tracking_number, c.description, c.destination, c.status, c.updated_at, u.name AS customer_name
    FROM cargo c JOIN users u ON u.id = c.customer_id';
$parameters = [];
if (!$isStaff) {
    $countSql .= ' WHERE customer_id = ?';
    $recentSql .= ' WHERE c.customer_id = ?';
    $parameters[] = (int)$user['id'];
}
$countSql .= ' GROUP BY status';
$countStatement = db()->prepare($countSql);
$countStatement->execute($parameters);
$statusCounts = array_fill_keys(['registered', 'picked_up', 'in_transit', 'at_customs', 'out_for_delivery', 'delayed', 'delivered', 'cancelled'], 0);
foreach ($countStatement->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int)$row['total'];
}
$total = array_sum($statusCounts);
$recentSql .= ' ORDER BY c.updated_at DESC LIMIT 6';
$recentStatement = db()->prepare($recentSql);
$recentStatement->execute($parameters);
$recentShipments = $recentStatement->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/header.php';
?>
<div class="page-heading">
    <div><div class="eyebrow"><?= $isStaff ? 'Operations overview' : 'Your cargo overview' ?></div><h1>Good to see you, <?= h((string)$user['name']) ?>.</h1><p class="muted"><?= $isStaff ? 'A quick view of shipment activity across your operations.' : 'A quick view of the shipments linked to your account.' ?></p></div>
    <?php if ($isStaff): ?><a class="button" href="<?= h(app_url('cargoCreate.php')) ?>">+ Register cargo</a><?php endif; ?>
</div>
<section class="stats-grid" aria-label="Shipment summary">
    <div class="stat-card"><span><i class="stat-dot"></i>Total shipments</span><strong><?= $total ?></strong></div>
    <div class="stat-card"><span><i class="stat-dot" style="background:#ef9b4a"></i>In transit</span><strong><?= $statusCounts['in_transit'] + $statusCounts['picked_up'] + $statusCounts['out_for_delivery'] ?></strong></div>
    <div class="stat-card"><span><i class="stat-dot" style="background:#5b9b5f"></i>Delivered</span><strong><?= $statusCounts['delivered'] ?></strong></div>
    <div class="stat-card"><span><i class="stat-dot" style="background:#c87458"></i>Needs attention</span><strong><?= $statusCounts['delayed'] ?></strong></div>
</section>
<section class="card">
    <div class="toolbar"><div><h2>Recently updated</h2><p class="hint">Latest shipment activity</p></div><a href="<?= h(app_url('cargo.php')) ?>">View all shipments &rarr;</a></div>
    <?php if (!$recentShipments): ?>
        <div class="empty-state"><strong>No shipments yet</strong><?= $isStaff ? 'Register a shipment to start tracking its journey.' : 'Shipments assigned to your account will appear here.' ?></div>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Tracking number</th><th>Cargo</th><?php if ($isStaff): ?><th>Customer</th><?php endif; ?><th>Destination</th><th>Status</th><th>Last updated</th></tr></thead>
            <tbody><?php foreach ($recentShipments as $shipment): ?>
                <tr>
                    <td><a class="tracking-link" href="<?= h(app_url('cargoView.php?id=' . (int)$shipment['id'])) ?>"><?= h($shipment['tracking_number']) ?></a></td>
                    <td><?= h($shipment['description']) ?></td>
                    <?php if ($isStaff): ?><td><?= h($shipment['customer_name']) ?></td><?php endif; ?>
                    <td><?= h($shipment['destination']) ?></td>
                    <td><span class="status-pill status-<?= h($shipment['status']) ?>"><?= h(ucwords(format_status($shipment['status']))) ?></span></td>
                    <td><?= h(date('M j, Y H:i', strtotime($shipment['updated_at']))) ?></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/footer.php'; ?>
