<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('POST');
require_admin();

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$id = sanitize_int($body['id'] ?? 0);
if (!$id) error('Member ID is required.');

$allowed_fields = ['first_name', 'last_name', 'email', 'phone', 'status', 'address', 'birthdate'];
$set    = [];
$params = [];

foreach ($allowed_fields as $field) {
    if (isset($body[$field])) {
        $set[]    = "$field = ?";
        $params[] = sanitize_string((string) $body[$field]);
    }
}

if (!$set) error('No fields to update.');

$params[] = $id;
$sql = "UPDATE members SET " . implode(', ', $set) . ", updated_at = NOW() WHERE id = ?";
$stmt = db()->prepare($sql);
$stmt->execute($params);

if ($stmt->rowCount() === 0) error('Member not found or nothing changed.', 404);

success('Member updated successfully.');
