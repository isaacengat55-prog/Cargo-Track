<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$user = require_login();
$isStaff = in_array($user['role'], ['admin', 'staff'], true);
$query = trim(query_string('q'));
$query = mb_substr($query, 0, 100);

$sql = 'SELECT c.id, c.tracking_number, c.description, c.destination, c.status, c.expected_delivery, c.updated_at, u.name AS customer_name
    FROM cargo c JOIN users u ON u.id = c.customer_id';
$conditions = [];
$parameters = [];
if (!$isStaff) {
    $conditions[] = 'c.customer_id = ?';
    $parameters[] = (int)$user['id'];
}
if ($query !== '') {
    $conditions[] = '(c.tracking_number LIKE ? OR c.description LIKE ? OR c.destination LIKE ?)';
    $pattern = '%' . $query . '%';
    array_push($parameters, $pattern, $pattern, $pattern);
}
if ($conditions) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY c.updated_at DESC LIMIT 200';
$statement = db()->prepare($sql);
$statement->execute($parameters);
$shipments = $statement->fetchAll();

$pageTitle = 'Shipments';
require __DIR__ . '/header.php';
?>
<div class="page-heading">
    <div><div class="eyebrow">Cargo register</div><h1>Shipments</h1><p class="muted"><?= $isStaff ? 'Search and manage shipment records across your operations.' : 'Search and follow shipments assigned to your account.' ?></p></div>
    <?php if ($isStaff): ?><a class="button" href="<?= h(app_url('cargoCreate.php')) ?>">+ Register cargo</a><?php endif; ?>
</div>
<section class="card">
    <div class="toolbar">
        <form class="search-form" method="get">
            <input aria-label="Search shipments" name="q" placeholder="Search tracking number, cargo, destination" value="<?= h($query) ?>">
            <button class="button" type="submit">Search</button>
        </form>
        <a class="button button-secondary" href="<?= h(app_url('reports.php')) ?>">Download report</a>
    </div>
    <?php if (!$shipments): ?>
        <div class="empty-state"><strong>No matching shipments</strong><?= $query !== '' ? 'Try a different tracking number or keyword.' : ($isStaff ? 'Register the first cargo record to get started.' : 'Shipments assigned to your account will appear here.') ?></div>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Tracking number</th><th>Cargo</th><?php if ($isStaff): ?><th>Customer</th><?php endif; ?><th>Destination</th><th>Expected</th><th>Status</th><th>Updated</th></tr></thead>
            <tbody><?php foreach ($shipments as $shipment): ?>
                <tr>
                    <td><a class="tracking-link" href="<?= h(app_url('cargoView.php?id=' . (int)$shipment['id'])) ?>"><?= h($shipment['tracking_number']) ?></a></td>
                    <td><?= h($shipment['description']) ?></td>
                    <?php if ($isStaff): ?><td><?= h($shipment['customer_name']) ?></td><?php endif; ?>
                    <td><?= h($shipment['destination']) ?></td>
                    <td><?= $shipment['expected_delivery'] ? h(date('M j, Y', strtotime($shipment['expected_delivery']))) : '—' ?></td>
                    <td><span class="status-pill status-<?= h($shipment['status']) ?>"><?= h(ucwords(format_status($shipment['status']))) ?></span></td>
                    <td><?= h(date('M j, Y H:i', strtotime($shipment['updated_at']))) ?></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <?php if (count($shipments) === 200): ?><p class="hint">Showing the latest 200 matches. Refine your search to find older records.</p><?php endif; ?>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/footer.php'; ?>
