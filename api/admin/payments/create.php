<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('POST');
require_admin();

$body         = json_decode(file_get_contents('php://input'), true) ?? [];
$member_id    = sanitize_int($body['member_id']    ?? 0);
$plan_id      = sanitize_int($body['plan_id']      ?? 0);
$amount       = (float) ($body['amount']           ?? 0);
$method       = sanitize_string($body['method']    ?? 'cash');
$status       = sanitize_string($body['status']    ?? 'completed');
$reference_no = sanitize_string($body['reference_no'] ?? '');
$notes        = sanitize_string($body['notes']     ?? '');

if (!$member_id || !$amount) error('member_id and amount are required.');

$stmt = db()->prepare("
    INSERT INTO payments (member_id, plan_id, amount, method, status, reference_no, notes, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");
$stmt->execute([$member_id, $plan_id ?: null, $amount, $method, $status, $reference_no, $notes]);

success('Payment recorded.', ['payment_id' => db()->lastInsertId()]);
