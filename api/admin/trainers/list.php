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
    $where[]  = "(t.first_name LIKE ? OR t.last_name LIKE ? OR t.email LIKE ? OR t.specialization LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($status !== '') {
    $where[]  = "t.status = ?";
    $params[] = $status;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$pdo = db();

// Summary stats
$totalStmt  = $pdo->query("SELECT COUNT(*) FROM trainers");
$total       = (int)$totalStmt->fetchColumn();

$activeStmt = $pdo->query("SELECT COUNT(*) FROM trainers WHERE status = 'active'");
$active      = (int)$activeStmt->fetchColumn();

// Count with filters
$count_sql = "SELECT COUNT(*) FROM trainers t $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$filtered_total = (int)$stmt->fetchColumn();

$pag = get_pagination($filtered_total, $page, $per_page);

// JS reads: first_name, last_name, specialty, rating, session_rate,
//           total_sessions, upcoming_sessions, status
$sql = "
    SELECT t.id, t.first_name, t.last_name, t.email, t.phone,
           t.specialization AS specialty,
           t.bio, t.status, t.hire_date, t.created_at,
           IFNULL(t.session_rate, 0)  AS session_rate,
           IFNULL(t.rating, 0)        AS rating,
           COUNT(DISTINCT cs.id)      AS total_sessions,
           COUNT(DISTINCT CASE
               WHEN cs.status = 'active' AND cs.schedule_date >= CURDATE()
               THEN cs.id END)        AS upcoming_sessions,
           COUNT(DISTINCT CASE WHEN cs.status = 'active' THEN cs.id END) AS active_classes
    FROM trainers t
    LEFT JOIN class_schedules cs ON cs.trainer_id = t.id
    $where_sql
    GROUP BY t.id
    ORDER BY t.created_at DESC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trainers = $stmt->fetchAll();

success('Trainers retrieved.', [
    'trainers'   => $trainers,
    'pagination' => $pag,
    'summary'    => [
        'total'  => $total,
        'active' => $active,
    ],
]);