<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');
require_admin(['super_admin']);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$id   = sanitize_int($body['id'] ?? 0);
if (!$id) error('Member ID required.');

$stmt = db()->prepare("UPDATE members SET status = 'deleted' WHERE id = ?");
$stmt->execute([$id]);
if ($stmt->rowCount() === 0) error('Member not found.', 404);
success('Member deleted.');
