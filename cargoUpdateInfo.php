<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_role(['admin', 'staff']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();

$id = filter_var($_POST['cargo_id'] ?? null, FILTER_VALIDATE_INT);
$description = trim(post_string('description'));
$sender = trim(post_string('sender_name'));
$recipient = trim(post_string('recipient_name'));
$origin = trim(post_string('origin'));
$destination = trim(post_string('destination'));
$expectedDelivery = trim(post_string('expected_delivery'));
$fields = [
    'Cargo description' => [$description, 250],
    'Sender name' => [$sender, 120],
    'Recipient name' => [$recipient, 120],
    'Origin' => [$origin, 160],
    'Destination' => [$destination, 160],
];
if (!$id) {
    set_flash('The shipment record is invalid.', 'error');
    redirect('cargo.php');
}
foreach ($fields as $label => [$value, $limit]) {
    if ($value === '' || mb_strlen($value) > $limit) {
        set_flash($label . ' is required and must be no longer than ' . $limit . ' characters.', 'error');
        redirect('cargoEdit.php?id=' . $id);
    }
}
if ($expectedDelivery !== '') {
    $date = DateTime::createFromFormat('!Y-m-d', $expectedDelivery);
    if (!$date || $date->format('Y-m-d') !== $expectedDelivery) {
        set_flash('Enter a valid expected delivery date.', 'error');
        redirect('cargoEdit.php?id=' . $id);
    }
} else {
    $expectedDelivery = null;
}

$statement = db()->prepare(
    'UPDATE cargo SET description = ?, sender_name = ?, recipient_name = ?, origin = ?, destination = ?, expected_delivery = ?
     WHERE id = ?'
);
$statement->execute([$description, $sender, $recipient, $origin, $destination, $expectedDelivery, $id]);
if ($statement->rowCount() === 0) {
    $check = db()->prepare('SELECT id FROM cargo WHERE id = ?');
    $check->execute([$id]);
    if (!$check->fetch()) {
        http_response_code(404);
        exit('Shipment not found.');
    }
}
set_flash('Shipment details saved.');
redirect('cargoView.php?id=' . $id);
