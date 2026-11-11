<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
$user = require_role(['admin', 'staff']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();

$cargoId = filter_var($_POST['cargo_id'] ?? null, FILTER_VALIDATE_INT);
$status = post_string('status');
$location = trim(post_string('location'));
$notes = trim(post_string('notes'));
$deliveredTo = trim(post_string('delivered_to'));
$latitudeText = trim(post_string('latitude'));
$longitudeText = trim(post_string('longitude'));
$allowedStatuses = ['picked_up', 'in_transit', 'at_customs', 'out_for_delivery', 'delayed', 'delivered', 'cancelled'];

if (!$cargoId || !in_array($status, $allowedStatuses, true)) {
    set_flash('The shipment update is invalid.', 'error');
    redirect('cargo.php');
}
if (mb_strlen($location) > 160 || mb_strlen($notes) > 500 || mb_strlen($deliveredTo) > 120) {
    set_flash('The location, notes, or recipient name is too long.', 'error');
    redirect('cargoView.php?id=' . $cargoId);
}
if ($status === 'delivered' && $deliveredTo === '') {
    set_flash('Enter the name of the person who received the shipment.', 'error');
    redirect('cargoView.php?id=' . $cargoId);
}

$latitude = null;
$longitude = null;
if (($latitudeText === '') !== ($longitudeText === '')) {
    set_flash('Provide both GPS coordinates, or leave both blank.', 'error');
    redirect('cargoView.php?id=' . $cargoId);
}
if ($latitudeText !== '') {
    if (!is_numeric($latitudeText) || !is_numeric($longitudeText)
        || (float)$latitudeText < -90 || (float)$latitudeText > 90
        || (float)$longitudeText < -180 || (float)$longitudeText > 180) {
        set_flash('Enter valid GPS coordinates.', 'error');
        redirect('cargoView.php?id=' . $cargoId);
    }
    $latitude = (float)$latitudeText;
    $longitude = (float)$longitudeText;
}

$pdo = db();
$pdo->beginTransaction();
try {
    $lock = $pdo->prepare('SELECT status FROM cargo WHERE id = ? FOR UPDATE');
    $lock->execute([$cargoId]);
    $shipment = $lock->fetch();
    if (!$shipment) {
        $pdo->rollBack();
        http_response_code(404);
        exit('Shipment not found.');
    }
    if (in_array($shipment['status'], ['delivered', 'cancelled'], true)) {
        $pdo->rollBack();
        set_flash('This shipment is closed and cannot be updated.', 'error');
        redirect('cargoView.php?id=' . $cargoId);
    }

    $deliveryUpdate = $status === 'delivered';
    $update = $pdo->prepare(
        'UPDATE cargo SET status = ?, delivered_to = ?, delivered_at = ? WHERE id = ?'
    );
    $update->execute([
        $status,
        $deliveryUpdate ? $deliveredTo : null,
        $deliveryUpdate ? date('Y-m-d H:i:s') : null,
        $cargoId,
    ]);
    $event = $pdo->prepare(
        'INSERT INTO cargo_events (cargo_id, updated_by, status, location, notes, latitude, longitude)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $event->execute([
        $cargoId,
        $user['id'],
        $status,
        $location !== '' ? $location : null,
        $notes !== '' ? $notes : null,
        $latitude,
        $longitude,
    ]);
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $error;
}

set_flash('Shipment status updated.');
redirect('cargoView.php?id=' . $cargoId);
