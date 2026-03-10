<?php
/**
 * GET /api/user/events/list.php
 *
 * Returns upcoming public events.
 * Members-only events are only visible to authenticated members.
 *
 * Query params (GET):
 *   type        string  optional  "fitness_challenge"|"workshop"|"competition" etc.
 *   registered  string  optional  "1" — show only events the member is registered for
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "events": [
 *       {
 *         id, name, type, event_date, event_time, location,
 *         fee, max_attendees, current_attendees, spots_left,
 *         is_members_only, is_registered, organizer_name
 *       }
 *     ]
 *   }
 *
 * DB tables used (when connected):
 *   events           (id, name, type, event_date, event_time, location, fee,
 *                     max_attendees, current_attendees, is_members_only,
 *                     organizer_id, description, status)
 *   event_registrations (id, event_id, member_id, registered_at, status)
 *   trainers         (id, name)
 */

require_once __DIR__ . '/../../config.php';
require_method('GET');

$current_member_id = is_logged_in() ? (int)$_SESSION['member_id'] : null;

$type_filter      = sanitize_string($_GET['type']       ?? '');
$registered_only  = ($_GET['registered'] ?? '') === '1';

// ─── TODO: replace stub with real DB logic ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $conditions = ["e.status = 'active'", 'e.event_date >= CURDATE()'];
    $params     = [];

    // Hide members-only events from guests
    if (!$current_member_id) {
        $conditions[] = 'e.is_members_only = 0';
    }

    if ($type_filter) {
        $conditions[] = 'e.type = ?';
        $params[]     = $type_filter;
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    $stmt = $pdo->prepare("
        SELECT e.id, e.name, e.type,
               e.event_date, e.event_time, e.location,
               e.fee, e.max_attendees, e.current_attendees,
               (e.max_attendees - e.current_attendees) AS spots_left,
               e.is_members_only, e.description,
               CONCAT(t.first_name, ' ', t.last_name) AS organizer_name
        FROM events e
        LEFT JOIN trainers t ON t.id = e.organizer_id
        $where
        ORDER BY e.event_date ASC, e.event_time ASC
    ");
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark registration status for logged-in members
    if ($current_member_id && count($events)) {
        $event_ids    = array_column($events, 'id');
        $placeholders = implode(',', array_fill(0, count($event_ids), '?'));
        $reg_stmt     = $pdo->prepare("
            SELECT event_id FROM event_registrations
            WHERE member_id = ? AND event_id IN ($placeholders) AND status = 'registered'
        ");
        $reg_stmt->execute(array_merge([$current_member_id], $event_ids));
        $registered_event_ids = $reg_stmt->fetchAll(PDO::FETCH_COLUMN);
        $registered_set       = array_flip($registered_event_ids);

        foreach ($events as &$ev) {
            $ev['is_registered'] = isset($registered_set[$ev['id']]);
        }
        unset($ev);

        if ($registered_only) {
            $events = array_values(array_filter($events, fn($e) => $e['is_registered']));
        }
    } else {
        foreach ($events as &$ev) { $ev['is_registered'] = false; }
        unset($ev);
    }

    success('OK', ['events' => $events]);
*/

// ─── STUB response ────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
