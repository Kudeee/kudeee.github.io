<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('POST');
require_admin();

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$id     = sanitize_int($body['id']     ?? 0);
$status = sanitize_string($body['status'] ?? '');

if (!$id || !$status) error('id and status are required.');

$allowed = ['pending', 'completed', 'failed', 'refunded'];
if (!in_array($status, $allowed)) error('Invalid status.');

$stmt = db()->prepare("UPDATE payments SET status = ? WHERE id = ?");
$stmt->execute([$status, $id]);

if ($stmt->rowCount() === 0) error('Payment not found.', 404);

success('Payment status updated.');
