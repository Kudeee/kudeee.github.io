<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');
require_admin();

$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$class_name = sanitize_string($body['class_name'] ?? $body['class_type'] ?? '');
$trainer_id = sanitize_int($body['trainer_id'] ?? 0);
$scheduled_at = sanitize_string($body['class_datetime'] ?? $body['scheduled_at'] ?? '');
$duration   = sanitize_int($body['duration_minutes'] ?? 60);
$max_p      = sanitize_int($body['max_participants'] ?? 20);
$location   = sanitize_string($body['location'] ?? 'Main Studio');

if (!$class_name || !$trainer_id || !$scheduled_at) error('Class name, trainer, and date/time are required.');

$stmt = db()->prepare("
    INSERT INTO class_schedules (class_name, trainer_id, scheduled_at, duration_minutes, max_participants, current_participants, location, status, created_at)
    VALUES (?,?,?,?,?,0,?,'active',NOW())
");
$stmt->execute([$class_name, $trainer_id, $scheduled_at, $duration, $max_p, $location]);
success('Class scheduled.', ['class_id' => db()->lastInsertId()]);
