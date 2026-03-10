<?php
/**
 * POST /api/admin/classes/update.php
 * Also handles cancellation.
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

$class_id         = sanitize_int($_POST['class_id']          ?? 0);
$class_name       = sanitize_string($_POST['class_name']     ?? '');
$trainer_id       = isset($_POST['trainer_id']) ? sanitize_int($_POST['trainer_id']) : null;
$scheduled_at     = sanitize_string($_POST['scheduled_at']   ?? '');
$duration_minutes = isset($_POST['duration_minutes']) ? sanitize_int($_POST['duration_minutes']) : null;
$max_participants = isset($_POST['max_participants']) ? sanitize_int($_POST['max_participants']) : null;
$location         = sanitize_string($_POST['location']       ?? '');
$status           = sanitize_string($_POST['status']         ?? '');
$cancel_reason    = sanitize_string($_POST['cancel_reason']  ?? '');

if (!$class_id || $class_id < 1) error('A valid class ID is required.');
if ($status === 'cancelled' && !$cancel_reason) error('A cancellation reason is required.');

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT * FROM class_schedules WHERE id = ? LIMIT 1');
    $stmt->execute([$class_id]);
    $class = $stmt->fetch();
    if (!$class) error('Class not found.', 404);

    if ($max_participants !== null && $max_participants < (int) $class['current_participants']) {
        error("Cannot set max participants below current booking count ({$class['current_participants']}).");
    }

    $fields = [];
    $params = [];

    if ($class_name)          { $fields[] = 'class_name = ?';        $params[] = $class_name;        }
    if ($trainer_id !== null) { $fields[] = 'trainer_id = ?';        $params[] = $trainer_id;        }
    if ($scheduled_at)        { $fields[] = 'scheduled_at = ?';      $params[] = $scheduled_at;      }
    if ($duration_minutes !== null) { $fields[] = 'duration_minutes = ?'; $params[] = $duration_minutes; }
    if ($max_participants !== null) { $fields[] = 'max_participants = ?'; $params[] = $max_participants; }
    if ($location)            { $fields[] = 'location = ?';          $params[] = $location;          }
    if ($status)              { $fields[] = 'status = ?';            $params[] = $status;            }

    if (empty($fields)) error('No fields provided for update.');

    $params[] = $class_id;

    $pdo->beginTransaction();
    $pdo->prepare('UPDATE class_schedules SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

    $bookings_cancelled = 0;
    if ($status === 'cancelled') {
        $stmt = $pdo->prepare("UPDATE class_bookings SET status = 'cancelled' WHERE class_schedule_id = ? AND status = 'confirmed'");
        $stmt->execute([$class_id]);
        $bookings_cancelled = $stmt->rowCount();
    }

    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, 'class_updated', 'class', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $class_id, json_encode(['status' => $status, 'reason' => $cancel_reason ?: null]), $_SERVER['REMOTE_ADDR'] ?? '']);

    $pdo->commit();
    success('Class updated.', ['bookings_cancelled' => $bookings_cancelled]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error('Database error: ' . $e->getMessage(), 500);
}