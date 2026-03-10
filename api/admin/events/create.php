<?php
/**
 * POST /api/admin/events/create.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

$event_name   = sanitize_string($_POST['event_name']   ?? '');
$event_type   = sanitize_string($_POST['event_type']   ?? '');
$event_date   = sanitize_string($_POST['event_date']   ?? '');
$event_time   = sanitize_string($_POST['event_time']   ?? '');
$location     = sanitize_string($_POST['event_location'] ?? '');
$max_attendees= sanitize_int($_POST['max_attendees']   ?? 50);
$fee          = filter_var($_POST['event_fee'] ?? 0, FILTER_VALIDATE_FLOAT);
$organizer_id = sanitize_int($_POST['organizer_id']    ?? 0);
$description  = sanitize_string($_POST['event_description'] ?? '');
$members_only = isset($_POST['is_members_only']) ? 1 : 0;

if (!$event_name)                        error('Event name is required.');
if (!$event_type)                        error('Event type is required.');
if (!$event_date)                        error('Event date is required.');
if (!$event_time)                        error('Event time is required.');
if (!$location)                          error('Location is required.');
if (!$max_attendees || $max_attendees < 1) error('Max attendees must be at least 1.');
if ($fee === false || $fee < 0)          error('Fee must be a valid non-negative number.');

try {
    $pdo = db();

    $stmt = $pdo->prepare("
        INSERT INTO events (name, type, event_date, event_time, location, fee,
            max_attendees, current_attendees, is_members_only, organizer_id,
            description, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, 'active', NOW())
    ");
    $stmt->execute([
        $event_name, $event_type, $event_date, $event_time, $location, $fee,
        $max_attendees, $members_only,
        ($organizer_id > 0 ? $organizer_id : null),
        $description
    ]);
    $event_id = (int) $pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, 'event_created', 'event', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $event_id, json_encode(['name' => $event_name, 'date' => $event_date]), $_SERVER['REMOTE_ADDR'] ?? '']);

    success('Event created successfully.', ['event_id' => $event_id], 201);
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}