<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');
require_admin();

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$id   = sanitize_int($body['class_id'] ?? $body['id'] ?? 0);
if (!$id) error('Class ID required.');

$stmt = db()->prepare("UPDATE class_schedules SET status='cancelled' WHERE id=?");
$stmt->execute([$id]);
if ($stmt->rowCount() === 0) error('Class not found.', 404);
success('Class cancelled.');
