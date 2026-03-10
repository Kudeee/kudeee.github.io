<?php
/**
 * GET /api/admin/events/list.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

$search = sanitize_string($_GET['search'] ?? '');
$type   = sanitize_string($_GET['type']   ?? '');
$status = sanitize_string($_GET['status'] ?? '');
$date   = get_date_range();
[$offset, $per_page, $page] = get_pagination();

try {
    $pdo = db();

    $where  = ['e.event_date BETWEEN ? AND ?'];
    $params = [$date['from'], $date['to']];

    if ($search) { $where[] = 'e.name LIKE ?'; $params[] = '%' . $search . '%'; }
    if ($type)   { $where[] = 'e.type = ?';    $params[] = $type;               }
    if ($status) { $where[] = 'e.status = ?';  $params[] = $status;             }

    $whereSQL = implode(' AND ', $where);

    $count = $pdo->prepare("SELECT COUNT(*) FROM events e WHERE $whereSQL");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT e.*,
               (e.max_attendees - e.current_attendees) AS spots_left,
               CONCAT(t.first_name,' ',t.last_name) AS organizer_name
        FROM events e
        LEFT JOIN trainers t ON t.id = e.organizer_id
        WHERE $whereSQL
        ORDER BY e.event_date ASC, e.event_time ASC
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $events = $stmt->fetchAll();

    // Stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE() AND status='active'");
    $stmt->execute();
    $upcoming = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(current_attendees), 0) FROM events WHERE event_date BETWEEN ? AND ?");
    $stmt->execute([$date['from'], $date['to']]);
    $total_registrations = (int) $stmt->fetchColumn();

    success('Events retrieved.', [
        'events'     => $events,
        'pagination' => [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil($total / max($per_page, 1)),
        ],
        'stats' => [
            'upcoming'            => $upcoming,
            'total_registrations' => $total_registrations,
        ],
    ]);
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}