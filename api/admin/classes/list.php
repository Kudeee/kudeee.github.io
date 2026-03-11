<?php
// FIXED: field aliases match admin-js.js expectations (scheduled_at, max_participants, current_participants)
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('GET');
require_admin();

$page     = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page = max(1, sanitize_int($_GET['per_page'] ?? 20));
$search   = sanitize_string($_GET['search'] ?? '');
$status   = sanitize_string($_GET['status'] ?? '');
$trainer  = sanitize_string($_GET['trainer_id'] ?? '');

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = "(cs.class_name LIKE ? OR cs.description LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
}
if ($status !== '') {
    $where[]  = "cs.status = ?";
    $params[] = $status;
}
if ($trainer !== '') {
    $where[]  = "cs.trainer_id = ?";
    $params[] = $trainer;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$pdo = db();

// Summary stats
$totalStmt   = $pdo->query("SELECT COUNT(*) FROM class_schedules");
$total        = (int)$totalStmt->fetchColumn();

$activeStmt  = $pdo->query("SELECT COUNT(*) FROM class_schedules WHERE status = 'active'");
$active       = (int)$activeStmt->fetchColumn();

$todayStmt   = $pdo->query("SELECT COUNT(*) FROM class_schedules WHERE DATE(schedule_date) = CURDATE()");
$today_count  = (int)$todayStmt->fetchColumn();

// Count with filters
$count_sql = "SELECT COUNT(*) FROM class_schedules cs $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$filtered_total = (int)$stmt->fetchColumn();

$pag = get_pagination($filtered_total, $page, $per_page);

// JS reads: scheduled_at, class_name, trainer_name, duration_minutes,
//           location, current_participants, max_participants
$sql = "
    SELECT cs.id, cs.class_name, cs.description,
           CONCAT(cs.schedule_date, 'T', IFNULL(cs.start_time, '00:00:00')) AS scheduled_at,
           cs.schedule_date, cs.start_time, cs.end_time,
           cs.capacity       AS max_participants,
           cs.enrolled_count AS current_participants,
           IFNULL(TIMESTAMPDIFF(MINUTE,
               CONCAT(cs.schedule_date, ' ', cs.start_time),
               CONCAT(cs.schedule_date, ' ', cs.end_time)
           ), 60)            AS duration_minutes,
           cs.status, cs.location, cs.created_at,
           CONCAT(t.first_name, ' ', t.last_name) AS trainer_name,
           t.id AS trainer_id
    FROM class_schedules cs
    LEFT JOIN trainers t ON t.id = cs.trainer_id
    $where_sql
    ORDER BY cs.schedule_date ASC, cs.start_time ASC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$classes = $stmt->fetchAll();

success('Classes retrieved.', [
    'classes'    => $classes,
    'pagination' => $pag,
    'stats'      => [
        'scheduled' => $total,
        'active'    => $active,
        'today'     => $today_count,
    ],
]);