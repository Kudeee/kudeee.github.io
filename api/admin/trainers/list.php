<?php
require_once __DIR__ . '/../../../config.php';
require_method('GET');
require_admin();

$page     = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page = max(1, sanitize_int($_GET['per_page'] ?? 50));
$search   = sanitize_string($_GET['search'] ?? '');
$status   = sanitize_string($_GET['status'] ?? '');

$where  = ['1=1']; $params = [];
if ($search !== '') {
    $where[] = "(t.first_name LIKE ? OR t.last_name LIKE ? OR t.specialty LIKE ?)";
    $like = "%$search%"; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($status !== '') { $where[] = "t.status = ?"; $params[] = $status; }
$where_sql = 'WHERE ' . implode(' AND ', $where);

$pdo = db();
$total  = (int)$pdo->query("SELECT COUNT(*) FROM trainers")->fetchColumn();
$active = (int)$pdo->query("SELECT COUNT(*) FROM trainers WHERE status='active'")->fetchColumn();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM trainers t $where_sql");
$countStmt->execute($params);
$filtered = (int)$countStmt->fetchColumn();
$pag = get_pagination($filtered, $page, $per_page);

$stmt = $pdo->prepare("
    SELECT t.id, t.first_name, t.last_name, t.specialty, t.bio, t.image_url,
           t.exp_years, t.client_count, t.session_rate, t.rating,
           t.availability, t.specialty_tags, t.status, t.created_at,
           COUNT(DISTINCT cs.id) AS total_sessions,
           COUNT(DISTINCT CASE WHEN cs.scheduled_at >= NOW() AND cs.status='active' THEN cs.id END) AS upcoming_sessions
    FROM trainers t
    LEFT JOIN class_schedules cs ON cs.trainer_id = t.id
    $where_sql
    GROUP BY t.id
    ORDER BY t.rating DESC, t.first_name ASC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
");
$stmt->execute($params);
$trainers = $stmt->fetchAll();

// Parse specialty_tags JSON
foreach ($trainers as &$tr) {
    if ($tr['specialty_tags']) {
        $tr['specialty_tags'] = json_decode($tr['specialty_tags'], true) ?? [];
    } else {
        $tr['specialty_tags'] = [];
    }
}

success('Trainers retrieved.', [
    'trainers'   => $trainers,
    'pagination' => $pag,
    'summary'    => ['total' => $total, 'active' => $active],
]);
