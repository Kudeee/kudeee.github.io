<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');
require_admin();

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$id   = sanitize_int($body['event_id'] ?? $body['id'] ?? 0);
if (!$id) error('Event ID required.');

db()->prepare("UPDATE events SET status='cancelled' WHERE id=?")->execute([$id]);
success('Event cancelled.');
