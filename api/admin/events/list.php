<?php
require_once __DIR__ . '/../../../config.php';
require_method('GET');
require_admin();

$page     = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page = max(1, sanitize_int($_GET['per_page'] ?? 20));
$search   = sanitize_string($_GET['search'] ?? '');
$status   = sanitize_string($_GET['status'] ?? '');

$where  = ['1=1']; $params = [];
if ($search !== '') {
    $where[] = "(e.name LIKE ? OR e.description LIKE ?)";
    $like = "%$search%"; $params[] = $like; $params[] = $like;
}
if ($status !== '') { $where[] = "e.status = ?"; $params[] = $status; }
$where_sql = 'WHERE ' . implode(' AND ', $where);

$pdo = db();
$upcoming  = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE() AND status='active'")->fetchColumn();
$thisWeek  = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE WEEK(event_date)=WEEK(NOW()) AND YEAR(event_date)=YEAR(NOW())")->fetchColumn();
$totalReg  = $pdo->query("SELECT COALESCE(SUM(current_attendees),0) FROM events")->fetchColumn();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM events e $where_sql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pag = get_pagination($total, $page, $per_page);

$stmt = $pdo->prepare("
    SELECT e.id, e.name, e.type, e.event_date, e.event_time, e.location,
           e.fee, e.max_attendees, e.current_attendees, e.is_members_only,
           e.description, e.status, e.created_at,
           CONCAT(t.first_name,' ',t.last_name) AS organizer_name
    FROM events e
    LEFT JOIN trainers t ON t.id = e.organizer_id
    $where_sql
    ORDER BY e.event_date ASC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
");
$stmt->execute($params);
$events = $stmt->fetchAll();

// popular event
$popular = $pdo->query("SELECT name FROM events ORDER BY current_attendees DESC LIMIT 1")->fetchColumn();

success('Events retrieved.', [
    'events'     => $events,
    'pagination' => $pag,
    'stats'      => [
        'upcoming'            => $upcoming,
        'total_registrations' => (int)$totalReg,
        'this_week'           => $thisWeek,
        'popular'             => $popular ?: '—',
    ],
]);
