<?php
/**
 * GET /api/user/events/list.php
 * Returns upcoming events. Members-only events hidden from guests.
 */

require_once __DIR__ . '/../../config.php';
require_method('GET');

$current_member_id = is_logged_in() ? (int)$_SESSION['member_id'] : null;

$type_filter     = sanitize_string($_GET['type']       ?? '');
$registered_only = ($_GET['registered'] ?? '') === '1';

try {
    $pdo = db();

    $conditions = ["e.status = 'active'", 'e.event_date >= CURDATE()'];
    $params     = [];

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
               CONCAT(t.first_name, ' ', t.last_name)  AS organizer_name
        FROM events e
        LEFT JOIN trainers t ON t.id = e.organizer_id
        $where
        ORDER BY e.event_date ASC, e.event_time ASC
    ");
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    foreach ($events as &$ev) {
        $ev['is_registered']   = false;
        $ev['is_members_only'] = (bool)$ev['is_members_only'];
    }
    unset($ev);

    if ($current_member_id && count($events)) {
        $event_ids    = array_column($events, 'id');
        $placeholders = implode(',', array_fill(0, count($event_ids), '?'));
        $reg_stmt     = $pdo->prepare("
            SELECT event_id FROM event_registrations
            WHERE member_id = ? AND event_id IN ($placeholders) AND status = 'registered'
        ");
        $reg_stmt->execute(array_merge([$current_member_id], $event_ids));
        $registered_set = array_flip($reg_stmt->fetchAll(PDO::FETCH_COLUMN));

        foreach ($events as &$ev) {
            $ev['is_registered'] = isset($registered_set[$ev['id']]);
        }
        unset($ev);

        if ($registered_only) {
            $events = array_values(array_filter($events, fn($e) => $e['is_registered']));
        }
    } elseif ($registered_only) {
        $events = [];
    }

    success('OK', ['events' => $events]);

} catch (PDOException $e) {
    error('A database error occurred. Please try again.', 500);
}