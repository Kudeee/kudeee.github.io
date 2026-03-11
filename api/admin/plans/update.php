<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('POST');
require_admin(['super_admin', 'admin']);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$id   = sanitize_int($body['id'] ?? 0);
if (!$id) error('Plan ID is required.');

$allowed = ['name', 'description', 'price', 'duration_days', 'features', 'is_active'];
$set     = [];
$params  = [];

foreach ($allowed as $field) {
    if (isset($body[$field])) {
        $set[]    = "$field = ?";
        $params[] = $field === 'price' ? (float) $body[$field] : sanitize_string((string) $body[$field]);
    }
}

if (!$set) error('No fields to update.');

$params[] = $id;
$stmt = db()->prepare("UPDATE membership_plans SET " . implode(', ', $set) . " WHERE id = ?");
$stmt->execute($params);

if ($stmt->rowCount() === 0) error('Plan not found or nothing changed.', 404);

success('Plan updated successfully.');
