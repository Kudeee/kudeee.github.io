<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');
require_admin();

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$id   = sanitize_int($body['id'] ?? 0);
if (!$id) error('Trainer ID required.');

$stmt = db()->prepare("UPDATE trainers SET status = 'inactive' WHERE id = ?");
$stmt->execute([$id]);
if ($stmt->rowCount() === 0) error('Trainer not found.', 404);
success('Trainer deactivated.');