<?php
/**
 * POST /api/admin/classes/create.php
 *
 * Creates a new class schedule slot.
 *
 * Request (POST, form-data):
 *   csrf_token        string   required
 *   class_name        string   required
 *   class_type        string   required  (e.g. HIIT, Yoga, Spin, etc.)
 *   trainer_id        int      required
 *   schedule_date     date     required, YYYY-MM-DD, not in the past
 *   start_time        time     required, HH:MM (24h)
 *   end_time          time     required, HH:MM (24h), must be after start_time
 *   max_participants  int      required, min 1
 *   description       string   optional
 *   difficulty        string   optional: beginner | intermediate | advanced
 *   is_recurring      bool     optional  1|0 — if 1, repeat weekly
 *   recurring_weeks   int      optional  1–12 if is_recurring
 *
 * Response 201:
 *   { "success": true, "message": "Class created.", "class_id": <int>,
 *     "slots_created": <int> }
 *
 * DB tables used:
 *   class_schedules, trainers, admin_logs
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$class_name       = sanitize_string($_POST['class_name']        ?? '');
$class_type       = sanitize_string($_POST['class_type']        ?? '');
$trainer_id       = sanitize_int($_POST['trainer_id']           ?? 0);
$schedule_date    = sanitize_string($_POST['schedule_date']     ?? '');
$start_time       = sanitize_string($_POST['start_time']        ?? '');
$end_time         = sanitize_string($_POST['end_time']          ?? '');
$max_participants = sanitize_int($_POST['max_participants']     ?? 0);
$description      = sanitize_string($_POST['description']       ?? '');
$difficulty       = sanitize_string($_POST['difficulty']        ?? 'beginner');
$is_recurring     = (int) ($_POST['is_recurring']              ?? 0) === 1;
$recurring_weeks  = sanitize_int($_POST['recurring_weeks']      ?? 1);

$valid_difficulties = ['beginner','intermediate','advanced'];

if (!$class_name)                                                error('Class name is required.');
if (!$class_type)                                                error('Class type is required.');
if (!$trainer_id || $trainer_id < 1)                             error('A valid trainer ID is required.');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $schedule_date))       error('Schedule date must be YYYY-MM-DD format.');
if ($schedule_date < date('Y-m-d'))                              error('Schedule date cannot be in the past.');
if (!preg_match('/^\d{2}:\d{2}$/', $start_time))                error('Start time must be HH:MM format.');
if (!preg_match('/^\d{2}:\d{2}$/', $end_time))                  error('End time must be HH:MM format.');
if ($end_time <= $start_time)                                    error('End time must be after start time.');
if (!$max_participants || $max_participants < 1)                  error('Max participants must be at least 1.');
if (!in_array($difficulty, $valid_difficulties, true))           error('Invalid difficulty level.');
if ($is_recurring && ($recurring_weeks === false || $recurring_weeks < 1 || $recurring_weeks > 12)) {
    error('Recurring weeks must be between 1 and 12.');
}

// ─── TODO: replace stub with real DB insert ───────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Confirm trainer exists and is active
    $stmt = $pdo->prepare("SELECT id FROM trainers WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$trainer_id]);
    if (!$stmt->fetch()) error('Trainer not found or is inactive.', 404);

    $insert = $pdo->prepare("
        INSERT INTO class_schedules
            (class_name, class_type, trainer_id, schedule_date, start_time,
             end_time, max_participants, current_participants,
             description, difficulty, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 'active', NOW())
    ");

    $slots_created = 0;
    $first_id      = null;
    $weeks         = $is_recurring ? $recurring_weeks : 1;

    $pdo->beginTransaction();
    try {
        for ($i = 0; $i < $weeks; $i++) {
            $slot_date = date('Y-m-d', strtotime($schedule_date . " +{$i} weeks"));
            $insert->execute([
                $class_name, $class_type, $trainer_id,
                $slot_date, $start_time, $end_time,
                $max_participants, $description, $difficulty,
            ]);
            if ($i === 0) $first_id = (int) $pdo->lastInsertId();
            $slots_created++;
        }

        $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_type, target_id, created_at)
            VALUES (?, 'create_class', 'class_schedule', ?, NOW())
        ")->execute([$admin['admin_id'], $first_id]);

        $pdo->commit();
        success('Class schedule created.', [
            'class_id'     => $first_id,
            'slots_created' => $slots_created,
        ], 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        error('Failed to create class schedule. Please try again.', 500);
    }
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
