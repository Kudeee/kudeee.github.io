<?php
/**
 * GET /api/admin/classes/list.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

$search  = sanitize_string($_GET['search']  ?? '');
$status  = sanitize_string($_GET['status']  ?? '');
$date    = get_date_range();
[$offset, $per_page, $page] = get_pagination();

try {
    $pdo = db();

    $where  = ["DATE(cs.scheduled_at) BETWEEN ? AND ?"];
    $params = [$date['from'], $date['to']];

    if ($search) {
        $where[]  = "(cs.class_name LIKE ? OR CONCAT(t.first_name,' ',t.last_name) LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like; $params[] = $like;
    }
    if ($status) {
        $where[]  = 'cs.status = ?';
        $params[] = $status;
    }

    $whereSQL = implode(' AND ', $where);

    $count = $pdo->prepare("
        SELECT COUNT(*) FROM class_schedules cs
        LEFT JOIN trainers t ON t.id = cs.trainer_id
        WHERE $whereSQL
    ");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT cs.id, cs.class_name, cs.trainer_id,
               CONCAT(t.first_name,' ',t.last_name) AS trainer_name,
               cs.scheduled_at, cs.duration_minutes, cs.max_participants,
               cs.current_participants,
               (cs.max_participants - cs.current_participants) AS spots_left,
               cs.location, cs.status, cs.created_at
        FROM class_schedules cs
        LEFT JOIN trainers t ON t.id = cs.trainer_id
        WHERE $whereSQL
        ORDER BY cs.scheduled_at ASC
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $classes = $stmt->fetchAll();

    // Stats for current range
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM class_schedules WHERE DATE(scheduled_at) BETWEEN ? AND ? AND status='active'");
    $stmt->execute([$date['from'], $date['to']]);
    $scheduled_count = (int) $stmt->fetchColumn();

    success('Classes retrieved.', [
        'classes'    => $classes,
        'pagination' => [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil($total / max($per_page, 1)),
        ],
        'stats' => ['scheduled' => $scheduled_count],
    ]);
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}