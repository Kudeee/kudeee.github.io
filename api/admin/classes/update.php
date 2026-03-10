<?php
/**
 * POST /api/admin/classes/update.php
 *
 * Updates a class schedule slot, or cancels it.
 * If the class is cancelled and has booked members, their bookings
 * are also marked cancelled (refund logic to be added when DB connects).
 *
 * Request (POST, form-data):
 *   csrf_token        string   required
 *   class_id          int      required
 *   class_name        string   optional
 *   class_type        string   optional
 *   trainer_id        int      optional
 *   schedule_date     date     optional, YYYY-MM-DD
 *   start_time        time     optional, HH:MM
 *   end_time          time     optional, HH:MM
 *   max_participants  int      optional (cannot be less than current bookings)
 *   description       string   optional
 *   difficulty        string   optional
 *   status            string   optional: active | cancelled | completed
 *   cancel_reason     string   required if status = cancelled
 *
 * Response 200:
 *   { "success": true, "message": "Class updated.",
 *     "bookings_cancelled": <int> }
 *
 * DB tables used:
 *   class_schedules, class_bookings, admin_logs
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$class_id         = sanitize_int($_POST['class_id']          ?? 0);
$class_name       = sanitize_string($_POST['class_name']     ?? '');
$class_type       = sanitize_string($_POST['class_type']     ?? '');
$trainer_id       = isset($_POST['trainer_id']) ? sanitize_int($_POST['trainer_id']) : null;
$schedule_date    = sanitize_string($_POST['schedule_date']  ?? '');
$start_time       = sanitize_string($_POST['start_time']     ?? '');
$end_time         = sanitize_string($_POST['end_time']       ?? '');
$max_participants = isset($_POST['max_participants']) ? sanitize_int($_POST['max_participants']) : null;
$description      = sanitize_string($_POST['description']    ?? '');
$difficulty       = sanitize_string($_POST['difficulty']     ?? '');
$status           = sanitize_string($_POST['status']         ?? '');
$cancel_reason    = sanitize_string($_POST['cancel_reason']  ?? '');

$valid_statuses     = ['', 'active', 'cancelled', 'completed'];
$valid_difficulties = ['', 'beginner', 'intermediate', 'advanced'];

if (!$class_id || $class_id < 1)                                       error('A valid class ID is required.');
if (!in_array($status, $valid_statuses, true))                          error('Invalid status.');
if ($status === 'cancelled' && !$cancel_reason)                         error('A cancellation reason is required.');
if (!in_array($difficulty, $valid_difficulties, true))                  error('Invalid difficulty level.');
if ($schedule_date && $schedule_date < date('Y-m-d'))                   error('Schedule date cannot be in the past.');
if ($start_time && $end_time && $end_time <= $start_time)               error('End time must be after start time.');
if ($max_participants !== null && ($max_participants === false || $max_participants < 1)) {
    error('Max participants must be at least 1.');
}

// ─── TODO: replace stub with real DB update ───────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare('SELECT * FROM class_schedules WHERE id = ? LIMIT 1');
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$class) error('Class not found.', 404);

    // Ensure new max_participants isn't below current bookings
    if ($max_participants !== null && $max_participants < (int) $class['current_participants']) {
        error("Cannot set max participants below the current booking count ({$class['current_participants']}).");
    }

    $fields = [];
    $params = [];

    if ($class_name)          { $fields[] = 'class_name = ?';        $params[] = $class_name;       }
    if ($class_type)          { $fields[] = 'class_type = ?';        $params[] = $class_type;       }
    if ($trainer_id !== null) { $fields[] = 'trainer_id = ?';        $params[] = $trainer_id;       }
    if ($schedule_date)       { $fields[] = 'schedule_date = ?';     $params[] = $schedule_date;    }
    if ($start_time)          { $fields[] = 'start_time = ?';        $params[] = $start_time;       }
    if ($end_time)            { $fields[] = 'end_time = ?';          $params[] = $end_time;         }
    if ($max_participants !== null) { $fields[] = 'max_participants = ?'; $params[] = $max_participants; }
    if ($description !== '')  { $fields[] = 'description = ?';       $params[] = $description;      }
    if ($difficulty)          { $fields[] = 'difficulty = ?';        $params[] = $difficulty;       }
    if ($status)              { $fields[] = 'status = ?';            $params[] = $status;           }
    if (empty($fields))       error('No fields provided for update.');

    $fields[]  = 'updated_at = NOW()';
    $params[]  = $class_id;

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE class_schedules SET ' . implode(', ', $fields) . ' WHERE id = ?')
            ->execute($params);

        $bookings_cancelled = 0;
        if ($status === 'cancelled') {
            $stmt = $pdo->prepare("
                UPDATE class_bookings SET status = 'cancelled', updated_at = NOW()
                WHERE class_schedule_id = ? AND status = 'confirmed'
            ");
            $stmt->execute([$class_id]);
            $bookings_cancelled = $stmt->rowCount();
            // TODO: trigger refund logic for each cancelled booking
        }

        $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_type, target_id, notes, created_at)
            VALUES (?, 'update_class', 'class_schedule', ?, ?, NOW())
        ")->execute([$admin['admin_id'], $class_id, $cancel_reason ?: null]);

        $pdo->commit();
        success('Class updated.', ['bookings_cancelled' => $bookings_cancelled]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error('Failed to update class. Please try again.', 500);
    }
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
