<?php
require_once __DIR__ . '/../../../config.php';
require_method('GET');
require_admin();

$page     = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page = max(1, sanitize_int($_GET['per_page'] ?? 20));
$search   = sanitize_string($_GET['search'] ?? '');
$status   = sanitize_string($_GET['status'] ?? '');

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[] = "(cs.class_name LIKE ? OR cs.location LIKE ?)";
    $like = "%$search%"; $params[] = $like; $params[] = $like;
}
if ($status !== '') { $where[] = "cs.status = ?"; $params[] = $status; }

$where_sql = 'WHERE ' . implode(' AND ', $where);
$pdo = db();

$total     = (int)$pdo->query("SELECT COUNT(*) FROM class_schedules")->fetchColumn();
$today     = (int)$pdo->query("SELECT COUNT(*) FROM class_schedules WHERE DATE(scheduled_at) = CURDATE() AND status='active'")->fetchColumn();
$upcoming  = (int)$pdo->query("SELECT COUNT(*) FROM class_schedules WHERE scheduled_at >= NOW() AND status='active'")->fetchColumn();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM class_schedules cs $where_sql");
$countStmt->execute($params);
$filtered = (int)$countStmt->fetchColumn();
$pag = get_pagination($filtered, $page, $per_page);

$stmt = $pdo->prepare("
    SELECT cs.id, cs.class_name, cs.scheduled_at, cs.duration_minutes,
           cs.max_participants, cs.current_participants, cs.location, cs.status, cs.created_at,
           CONCAT(t.first_name,' ',t.last_name) AS trainer_name, t.id AS trainer_id,
           t.specialty AS trainer_specialty
    FROM class_schedules cs
    LEFT JOIN trainers t ON t.id = cs.trainer_id
    $where_sql
    ORDER BY cs.scheduled_at ASC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
");
$stmt->execute($params);
$classes = $stmt->fetchAll();

success('Classes retrieved.', [
    'classes'    => $classes,
    'pagination' => $pag,
    'stats'      => ['scheduled' => $total, 'today' => $today, 'upcoming' => $upcoming],
]);
