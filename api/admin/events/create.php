<?php
/**
 * POST /api/admin/events/create.php
 *
 * Creates a new gym event.
 *
 * Request (POST, form-data):
 *   csrf_token     string   required
 *   name           string   required
 *   type           string   required  (e.g. Workshop, Competition, Social, etc.)
 *   description    string   optional
 *   event_date     date     required, YYYY-MM-DD, not in the past
 *   start_time     time     required, HH:MM
 *   end_time       time     required, HH:MM, after start_time
 *   location       string   required
 *   fee            float    optional  default 0.00
 *   max_attendees  int      required
 *   members_only   bool     optional  1|0  default 0
 *   status         string   optional: draft | active  default: active
 *
 * Response 201:
 *   { "success": true, "message": "Event created.", "event_id": <int> }
 *
 * DB tables used:
 *   events, admin_logs
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$name          = sanitize_string($_POST['name']          ?? '');
$type          = sanitize_string($_POST['type']          ?? '');
$description   = sanitize_string($_POST['description']   ?? '');
$event_date    = sanitize_string($_POST['event_date']    ?? '');
$start_time    = sanitize_string($_POST['start_time']    ?? '');
$end_time      = sanitize_string($_POST['end_time']      ?? '');
$location      = sanitize_string($_POST['location']      ?? '');
$fee           = filter_var($_POST['fee'] ?? 0, FILTER_VALIDATE_FLOAT);
$max_attendees = sanitize_int($_POST['max_attendees']    ?? 0);
$members_only  = (int) ($_POST['members_only']           ?? 0) === 1 ? 1 : 0;
$status        = sanitize_string($_POST['status']        ?? 'active');

if (!$name)                                                     error('Event name is required.');
if (!$type)                                                     error('Event type is required.');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $event_date))         error('Event date must be YYYY-MM-DD.');
if ($event_date < date('Y-m-d'))                                error('Event date cannot be in the past.');
if (!preg_match('/^\d{2}:\d{2}$/', $start_time))               error('Start time must be HH:MM.');
if (!preg_match('/^\d{2}:\d{2}$/', $end_time))                 error('End time must be HH:MM.');
if ($end_time <= $start_time)                                   error('End time must be after start time.');
if (!$location)                                                 error('Location is required.');
if ($fee === false || $fee < 0)                                 error('Fee must be a valid non-negative number.');
if (!$max_attendees || $max_attendees < 1)                      error('Max attendees must be at least 1.');
if (!in_array($status, ['draft','active'], true))               error('Status must be draft or active.');

// ─── TODO: replace stub with real DB insert ───────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare("
        INSERT INTO events
            (name, type, description, event_date, start_time, end_time,
             location, fee, max_attendees, current_attendees,
             members_only, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, NOW())
    ");
    $stmt->execute([
        $name, $type, $description, $event_date, $start_time,
        $end_time, $location, $fee, $max_attendees, $members_only, $status,
    ]);
    $event_id = (int) $pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, target_type, target_id, created_at)
        VALUES (?, 'create_event', 'event', ?, NOW())
    ")->execute([$admin['admin_id'], $event_id]);

    success('Event created successfully.', ['event_id' => $event_id], 201);
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
