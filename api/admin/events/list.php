<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('GET');
require_admin();

$page     = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page = max(1, sanitize_int($_GET['per_page'] ?? 20));
$search   = sanitize_string($_GET['search'] ?? '');
$status   = sanitize_string($_GET['status'] ?? '');

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = "(e.title LIKE ? OR e.description LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
}
if ($status !== '') {
    $where[]  = "e.status = ?";
    $params[] = $status;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$pdo = db();

// Summary stats
$totalStmt    = $pdo->query("SELECT COUNT(*) FROM events");
$total         = (int)$totalStmt->fetchColumn();

$upcomingStmt = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE() AND status != 'cancelled'");
$upcoming      = (int)$upcomingStmt->fetchColumn();

$thisMonthStmt = $pdo->query("SELECT COUNT(*) FROM events WHERE MONTH(event_date)=MONTH(NOW()) AND YEAR(event_date)=YEAR(NOW())");
$this_month    = (int)$thisMonthStmt->fetchColumn();

// Count with filters
$count_sql = "SELECT COUNT(*) FROM events e $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$filtered_total = (int)$stmt->fetchColumn();

$pag = get_pagination($filtered_total, $page, $per_page);

// JS reads: name, type, event_date, event_time, location, fee,
//           is_members_only, current_attendees, max_attendees
$sql = "
    SELECT e.id,
           e.title            AS name,
           e.description,
           e.event_date,
           e.start_time       AS event_time,
           e.end_time,
           e.location,
           e.capacity         AS max_attendees,
           e.registered_count AS current_attendees,
           e.status,
           e.price            AS fee,
           IFNULL(e.is_members_only, 0) AS is_members_only,
           e.created_at,
           au.username        AS created_by
    FROM events e
    LEFT JOIN admin_users au ON au.id = e.created_by
    $where_sql
    ORDER BY e.event_date ASC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

success('Events retrieved.', [
    'events'     => $events,
    'pagination' => $pag,
    'stats'      => [
        'upcoming'             => $upcoming,
        'total_registrations'  => $pdo->query("SELECT COALESCE(SUM(registered_count),0) FROM events")->fetchColumn(),
        'this_month'           => $this_month,
    ],
]);