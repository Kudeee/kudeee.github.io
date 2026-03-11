<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');
require_admin();

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$id     = sanitize_int($body['id'] ?? $body['payment_id'] ?? 0);
$status = sanitize_string($body['status'] ?? '');

if (!$id || !$status) error('id and status are required.');
if (!in_array($status, ['pending','completed','failed','refunded'])) error('Invalid status.');

db()->prepare("UPDATE payments SET status=? WHERE id=?")->execute([$status, $id]);
success('Payment status updated.');
