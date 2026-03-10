<?php
/**
 * POST /api/admin/classes/create.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

$class_name       = sanitize_string($_POST['class_type']        ?? '');  // form uses class_type
$trainer_id       = sanitize_int($_POST['trainer_id']           ?? 0);
$class_datetime   = sanitize_string($_POST['class_datetime']    ?? '');
$duration_minutes = sanitize_int($_POST['duration_minutes']     ?? 50);
$max_participants = sanitize_int($_POST['max_participants']      ?? 20);
$location         = sanitize_string($_POST['location']          ?? '');
$description      = sanitize_string($_POST['class_description'] ?? '');

// Map display values to proper names
$class_name_map = [
    'yoga_flow'    => 'Yoga Flow',
    'hiit_training'=> 'HIIT Training',
    'zumba'        => 'Zumba',
    'crossfit'     => 'CrossFit',
    'boxing'       => 'Boxing',
    'pilates'      => 'Pilates',
    'spin_class'   => 'Spin Class',
];
$class_display = $class_name_map[$class_name] ?? $class_name;

$location_map = [
    'studio_a'     => 'Studio A',
    'studio_b'     => 'Studio B',
    'main_gym'     => 'Main Gym',
    'outdoor_area' => 'Outdoor Area',
    'boxing_ring'  => 'Boxing Ring',
];
$location_display = $location_map[$location] ?? $location;

if (!$class_name)                                  error('Class type is required.');
if (!$trainer_id || $trainer_id < 1)               error('A valid trainer ID is required.');
if (!$class_datetime)                              error('Date and time are required.');
if (!$duration_minutes || $duration_minutes < 1)   error('Duration must be at least 1 minute.');
if (!$max_participants || $max_participants < 1)    error('Max participants must be at least 1.');

try {
    $scheduled_at = date('Y-m-d H:i:s', strtotime($class_datetime));
    if (!$scheduled_at || $scheduled_at < date('Y-m-d H:i:s')) {
        error('Scheduled date/time cannot be in the past.');
    }

    $pdo = db();

    $stmt = $pdo->prepare("SELECT id FROM trainers WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$trainer_id]);
    if (!$stmt->fetch()) error('Trainer not found or is inactive.', 404);

    $stmt = $pdo->prepare("
        INSERT INTO class_schedules
            (class_name, trainer_id, scheduled_at, duration_minutes, max_participants,
             current_participants, location, status, created_at)
        VALUES (?, ?, ?, ?, ?, 0, ?, 'active', NOW())
    ");
    $stmt->execute([$class_display, $trainer_id, $scheduled_at, $duration_minutes, $max_participants, $location_display]);
    $class_id = (int) $pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, 'class_created', 'class', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $class_id, json_encode(['class' => $class_display, 'trainer_id' => $trainer_id]), $_SERVER['REMOTE_ADDR'] ?? '']);

    success('Class scheduled successfully.', ['class_id' => $class_id, 'slots_created' => 1], 201);
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}