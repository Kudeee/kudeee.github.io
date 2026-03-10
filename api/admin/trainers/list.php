<?php
/**
 * GET /api/admin/trainers/list.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

$search  = sanitize_string($_GET['search'] ?? '');
$status  = sanitize_string($_GET['status'] ?? '');
$sort    = in_array($_GET['sort'] ?? '', ['name','rating','experience','bookings']) ? $_GET['sort'] : 'name';
$order   = strtoupper($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
[$offset, $per_page, $page] = get_pagination();

try {
    $pdo = db();

    $where  = ["t.status != 'deleted'"];
    $params = [];

    if ($search) {
        $where[]  = "(CONCAT(t.first_name,' ',t.last_name) LIKE ? OR t.specialty LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like; $params[] = $like;
    }
    if ($status) {
        $where[]  = 't.status = ?';
        $params[] = $status;
    }

    $whereSQL = implode(' AND ', $where);

    $sort_map = [
        'name'       => 't.first_name',
        'rating'     => 't.rating',
        'experience' => 't.exp_years',
        'bookings'   => 'total_sessions',
    ];
    $orderSQL = ($sort_map[$sort] ?? 't.first_name') . ' ' . $order;

    $count = $pdo->prepare("SELECT COUNT(*) FROM trainers t WHERE $whereSQL");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT t.id, t.first_name, t.last_name, t.specialty, t.image_url,
               t.exp_years, t.client_count, t.session_rate, t.rating,
               t.availability, t.specialty_tags, t.status, t.created_at,
               (SELECT COUNT(*) FROM trainer_bookings tb WHERE tb.trainer_id = t.id) AS total_sessions,
               (SELECT COUNT(*) FROM trainer_bookings tb WHERE tb.trainer_id = t.id
                AND tb.status = 'confirmed' AND tb.booking_date >= CURDATE()) AS upcoming_sessions
        FROM trainers t
        WHERE $whereSQL
        ORDER BY $orderSQL
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $trainers = $stmt->fetchAll();

    // Decode JSON specialty_tags
    foreach ($trainers as &$t) {
        if (isset($t['specialty_tags'])) {
            $decoded = json_decode($t['specialty_tags'], true);
            $t['specialty_tags'] = is_array($decoded) ? $decoded : [];
        }
    }
    unset($t);

    success('Trainers retrieved.', [
        'trainers'   => $trainers,
        'pagination' => [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil($total / max($per_page, 1)),
        ],
    ]);
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}