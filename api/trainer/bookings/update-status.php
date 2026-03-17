<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../trainer-config.php';
require_method('POST');
$trainer = require_trainer();
$tid     = $trainer['id'];

$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$booking_id = sanitize_int($body['booking_id'] ?? 0);
$status     = sanitize_string($body['status']  ?? '');

if (!$booking_id) error('booking_id is required.');
if (!in_array($status, ['confirmed', 'cancelled', 'completed'])) error('Invalid status.');

$pdo = db();

// Ensure booking belongs to this trainer
$check = $pdo->prepare("SELECT id, status FROM trainer_bookings WHERE id = ? AND trainer_id = ? LIMIT 1");
$check->execute([$booking_id, $tid]);
$booking = $check->fetch();
if (!$booking) error('Booking not found.', 404);

$pdo->prepare("UPDATE trainer_bookings SET status = ? WHERE id = ?")
    ->execute([$status, $booking_id]);

success('Booking status updated.', ['booking_id' => $booking_id, 'status' => $status]);
