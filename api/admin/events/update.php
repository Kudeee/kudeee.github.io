<?php
/**
 * POST /api/admin/events/update.php
 * Also handles cancellation via status=cancelled.
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

$event_id      = sanitize_int($_POST['event_id']       ?? 0);
$name          = sanitize_string($_POST['name']         ?? '');
$status        = sanitize_string($_POST['status']       ?? '');
$cancel_reason = sanitize_string($_POST['cancel_reason']?? '');

if (!$event_id || $event_id < 1) error('A valid event ID is required.');
if ($status === 'cancelled' && !$cancel_reason) error('A cancellation reason is required.');

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    if (!$event) error('Event not found.', 404);

    $fields = [];
    $params = [];

    if ($name)   { $fields[] = 'name = ?';   $params[] = $name;   }
    if ($status) { $fields[] = 'status = ?'; $params[] = $status; }

    // Handle other optional fields
    $optional = ['type', 'event_date', 'event_time', 'location', 'description'];
    foreach ($optional as $field) {
        $val = sanitize_string($_POST[$field] ?? '');
        if ($val !== '') { $fields[] = "$field = ?"; $params[] = $val; }
    }

    if (isset($_POST['fee'])) {
        $fee = filter_var($_POST['fee'], FILTER_VALIDATE_FLOAT);
        if ($fee !== false) { $fields[] = 'fee = ?'; $params[] = $fee; }
    }
    if (isset($_POST['max_attendees'])) {
        $max = sanitize_int($_POST['max_attendees']);
        if ($max && $max >= (int) $event['current_attendees']) {
            $fields[] = 'max_attendees = ?'; $params[] = $max;
        }
    }

    if (empty($fields)) error('No fields provided for update.');

    $params[] = $event_id;
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE events SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

    $registrations_cancelled = 0;
    if ($status === 'cancelled') {
        $stmt = $pdo->prepare("UPDATE event_registrations SET status = 'cancelled' WHERE event_id = ? AND status = 'registered'");
        $stmt->execute([$event_id]);
        $registrations_cancelled = $stmt->rowCount();
    }

    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, 'event_updated', 'event', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $event_id, json_encode(['status' => $status, 'reason' => $cancel_reason ?: null]), $_SERVER['REMOTE_ADDR'] ?? '']);

    $pdo->commit();
    success('Event updated.', ['registrations_cancelled' => $registrations_cancelled]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error('Database error: ' . $e->getMessage(), 500);
}