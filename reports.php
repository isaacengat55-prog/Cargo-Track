<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$user = require_login();
$isStaff = in_array($user['role'], ['admin', 'staff'], true);

if (isset($_GET['download'])) {
    $sql = 'SELECT c.tracking_number, c.description, c.sender_name, c.recipient_name, c.origin, c.destination,
                   c.status, c.expected_delivery, c.delivered_to, c.delivered_at, c.created_at, c.updated_at, u.name AS customer_name
            FROM cargo c JOIN users u ON u.id = c.customer_id';
    $parameters = [];
    if (!$isStaff) {
        $sql .= ' WHERE c.customer_id = ?';
        $parameters[] = (int)$user['id'];
    }
    $sql .= ' ORDER BY c.updated_at DESC';
    $statement = db()->prepare($sql);
    $statement->execute($parameters);

    $safeCsv = static function (?string $value): string {
        $value = $value ?? '';
        return preg_match('/^[\s]*[=+\-@]/u', $value) ? "'" . $value : $value;
    };
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cargo-report-' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'wb');
    if ($output === false) {
        throw new RuntimeException('Unable to open the report output stream.');
    }
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Tracking number', 'Cargo', 'Sender', 'Recipient', 'Origin', 'Destination', 'Status', 'Expected delivery', 'Received by', 'Delivered at', 'Customer', 'Registered', 'Last updated'], ',', '"', '\\');
    while ($row = $statement->fetch()) {
        $values = [
            $row['tracking_number'], $row['description'], $row['sender_name'], $row['recipient_name'],
            $row['origin'], $row['destination'], ucwords(format_status($row['status'])),
            $row['expected_delivery'], $row['delivered_to'], $row['delivered_at'],
            $isStaff ? $row['customer_name'] : $user['name'], $row['created_at'], $row['updated_at'],
        ];
        fputcsv($output, array_map($safeCsv, $values), ',', '"', '\\');
    }
    fclose($output);
    exit;
}

$pageTitle = 'Reports';
require __DIR__ . '/header.php';
?>
<div class="page-heading">
    <div><div class="eyebrow">Operations data</div><h1>Shipment reports</h1><p class="muted"><?= $isStaff ? 'Download a CSV report of all shipment records.' : 'Download a CSV report of shipments assigned to your account.' ?></p></div>
</div>
<section class="card">
    <h2>Cargo register export</h2>
    <p class="muted">The report includes shipment details, current status, expected delivery, and delivery confirmation. Your access permissions determine which shipment records are included.</p>
    <a class="button" href="<?= h(app_url('reports.php?download=1')) ?>">Download CSV report</a>
</section>
<?php require __DIR__ . '/footer.php'; ?>
