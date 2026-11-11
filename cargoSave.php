<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$user = require_role(['admin', 'staff']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();

$customerId = filter_var($_POST['customer_id'] ?? null, FILTER_VALIDATE_INT);
$description = trim(post_string('description'));
$sender = trim(post_string('sender_name'));
$recipient = trim(post_string('recipient_name'));
$origin = trim(post_string('origin'));
$destination = trim(post_string('destination'));
$expectedDelivery = trim(post_string('expected_delivery'));
$lengths = [
    'Cargo description' => [$description, 250],
    'Sender name' => [$sender, 120],
    'Recipient name' => [$recipient, 120],
    'Origin' => [$origin, 160],
    'Destination' => [$destination, 160],
];
foreach ($lengths as $label => [$value, $limit]) {
    if ($value === '' || mb_strlen($value) > $limit) {
        set_flash($label . ' is required and must be no longer than ' . $limit . ' characters.', 'error');
        redirect('cargoCreate.php');
    }
}
if (!$customerId) {
    set_flash('Select a customer account.', 'error');
    redirect('cargoCreate.php');
}
$customerStatement = db()->prepare("SELECT id FROM users WHERE id = ? AND role = 'customer' AND active = 1");
$customerStatement->execute([$customerId]);
if (!$customerStatement->fetch()) {
    set_flash('The selected customer account is not available.', 'error');
    redirect('cargoCreate.php');
}
if ($expectedDelivery !== '') {
    $date = DateTime::createFromFormat('!Y-m-d', $expectedDelivery);
    if (!$date || $date->format('Y-m-d') !== $expectedDelivery) {
        set_flash('Enter a valid expected delivery date.', 'error');
        redirect('cargoCreate.php');
    }
} else {
    $expectedDelivery = null;
}

$pdo = db();
$pdo->beginTransaction();
try {
    $trackingNumber = 'CTK-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    $statement = $pdo->prepare(
        "INSERT INTO cargo (tracking_number, customer_id, created_by, description, sender_name, recipient_name, origin, destination, expected_delivery)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $statement->execute([$trackingNumber, $customerId, $user['id'], $description, $sender, $recipient, $origin, $destination, $expectedDelivery]);
    $cargoId = (int)$pdo->lastInsertId();
    $event = $pdo->prepare(
        "INSERT INTO cargo_events (cargo_id, updated_by, status, location, notes) VALUES (?, ?, 'registered', ?, ?)"
    );
    $event->execute([$cargoId, $user['id'], $origin, 'Shipment registered.']);
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $error;
}

set_flash('Shipment ' . $trackingNumber . ' was registered.');
redirect('cargoView.php?id=' . $cargoId);
