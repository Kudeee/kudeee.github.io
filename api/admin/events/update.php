<?php
/**
 * POST /api/admin/events/update.php
 *
 * Updates or cancels an existing event.
 * Cancelling an event also cancels all registrations.
 *
 * Request (POST, form-data):
 *   csrf_token     string   required
 *   event_id       int      required
 *   name           string   optional
 *   type           string   optional
 *   description    string   optional
 *   event_date     date     optional
 *   start_time     time     optional
 *   end_time       time     optional
 *   location       string   optional
 *   fee            float    optional
 *   max_attendees  int      optional (cannot go below current_attendees)
 *   members_only   bool     optional  1|0
 *   status         string   optional: draft | active | cancelled | completed
 *   cancel_reason  string   required if status = cancelled
 *
 * Response 200:
 *   { "success": true, "message": "Event updated.",
 *     "registrations_cancelled": <int> }
 *
 * DB tables used:
 *   events, event_registrations, admin_logs
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$event_id      = sanitize_int($_POST['event_id']       ?? 0);
$name          = sanitize_string($_POST['name']         ?? '');
$type          = sanitize_string($_POST['type']         ?? '');
$description   = sanitize_string($_POST['description']  ?? '');
$event_date    = sanitize_string($_POST['event_date']   ?? '');
$start_time    = sanitize_string($_POST['start_time']   ?? '');
$end_time      = sanitize_string($_POST['end_time']     ?? '');
$location      = sanitize_string($_POST['location']     ?? '');
$fee           = isset($_POST['fee']) ? filter_var($_POST['fee'], FILTER_VALIDATE_FLOAT) : null;
$max_attendees = isset($_POST['max_attendees']) ? sanitize_int($_POST['max_attendees']) : null;
$members_only  = isset($_POST['members_only']) ? ((int) $_POST['members_only'] === 1 ? 1 : 0) : null;
$status        = sanitize_string($_POST['status']       ?? '');
$cancel_reason = sanitize_string($_POST['cancel_reason']?? '');

$valid_statuses = ['', 'draft', 'active', 'cancelled', 'completed'];

if (!$event_id || $event_id < 1)                               error('A valid event ID is required.');
if (!in_array($status, $valid_statuses, true))                 error('Invalid status.');
if ($status === 'cancelled' && !$cancel_reason)                error('A cancellation reason is required.');
if ($event_date && $event_date < date('Y-m-d'))                error('Event date cannot be in the past.');
if ($start_time && $end_time && $end_time <= $start_time)      error('End time must be after start time.');
if ($fee !== null && ($fee === false || $fee < 0))             error('Fee must be a non-negative number.');
if ($max_attendees !== null && ($max_attendees === false || $max_attendees < 1)) error('Max attendees must be at least 1.');

// ─── TODO: replace stub with real DB update ───────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$event) error('Event not found.', 404);

    if ($max_attendees !== null && $max_attendees < (int) $event['current_attendees']) {
        error("Cannot set max attendees below current registration count ({$event['current_attendees']}).");
    }

    $fields = [];
    $params = [];

    if ($name)                { $fields[] = 'name = ?';           $params[] = $name;           }
    if ($type)                { $fields[] = 'type = ?';           $params[] = $type;           }
    if ($description !== '')  { $fields[] = 'description = ?';    $params[] = $description;    }
    if ($event_date)          { $fields[] = 'event_date = ?';     $params[] = $event_date;     }
    if ($start_time)          { $fields[] = 'start_time = ?';     $params[] = $start_time;     }
    if ($end_time)            { $fields[] = 'end_time = ?';       $params[] = $end_time;       }
    if ($location)            { $fields[] = 'location = ?';       $params[] = $location;       }
    if ($fee !== null)        { $fields[] = 'fee = ?';            $params[] = $fee;            }
    if ($max_attendees !== null) { $fields[] = 'max_attendees = ?'; $params[] = $max_attendees; }
    if ($members_only !== null) { $fields[] = 'members_only = ?'; $params[] = $members_only;  }
    if ($status)              { $fields[] = 'status = ?';         $params[] = $status;         }

    if (empty($fields)) error('No fields provided for update.');

    $fields[]  = 'updated_at = NOW()';
    $params[]  = $event_id;

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE events SET ' . implode(', ', $fields) . ' WHERE id = ?')
            ->execute($params);

        $registrations_cancelled = 0;
        if ($status === 'cancelled') {
            $stmt = $pdo->prepare("
                UPDATE event_registrations SET status = 'cancelled', updated_at = NOW()
                WHERE event_id = ? AND status = 'registered'
            ");
            $stmt->execute([$event_id]);
            $registrations_cancelled = $stmt->rowCount();
            // TODO: trigger refunds for paid registrations
        }

        $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_type, target_id, notes, created_at)
            VALUES (?, 'update_event', 'event', ?, ?, NOW())
        ")->execute([$admin['admin_id'], $event_id, $cancel_reason ?: null]);

        $pdo->commit();
        success('Event updated.', ['registrations_cancelled' => $registrations_cancelled]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error('Failed to update event. Please try again.', 500);
    }
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
