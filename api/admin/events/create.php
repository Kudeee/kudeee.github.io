<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');
require_admin();

$body         = json_decode(file_get_contents('php://input'), true) ?? [];
$name         = sanitize_string($body['event_name']    ?? $body['name'] ?? '');
$type         = sanitize_string($body['event_type']    ?? $body['type'] ?? 'general');
$event_date   = sanitize_string($body['event_date']    ?? '');
$event_time   = sanitize_string($body['event_time']    ?? '00:00');
$location     = sanitize_string($body['event_location'] ?? $body['location'] ?? '');
$fee          = (float)($body['event_fee'] ?? $body['fee'] ?? 0);
$max          = sanitize_int($body['max_attendees'] ?? 50);
$members_only = sanitize_int($body['is_members_only'] ?? 0);
$organizer_id = sanitize_int($body['organizer_id'] ?? 0);
$description  = sanitize_string($body['event_description'] ?? $body['description'] ?? '');

if (!$name || !$event_date || !$location) error('Name, date, and location are required.');

$stmt = db()->prepare("
    INSERT INTO events (name, type, event_date, event_time, location, fee, max_attendees, current_attendees, is_members_only, organizer_id, description, status, created_at)
    VALUES (?,?,?,?,?,?,?,0,?,?,'','active',NOW())
");
$stmt->execute([$name, $type, $event_date, $event_time, $location, $fee, $max, $members_only, $organizer_id ?: null]);
success('Event created.', ['event_id' => db()->lastInsertId()]);
