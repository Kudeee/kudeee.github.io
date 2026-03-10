<?php
/**
 * GET /api/admin/payments/list.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

$search    = sanitize_string($_GET['search']    ?? '');
$type      = sanitize_string($_GET['type']      ?? '');
$status    = sanitize_string($_GET['status']    ?? '');
$method    = sanitize_string($_GET['method']    ?? '');
$member_id = isset($_GET['member_id']) ? sanitize_int($_GET['member_id']) : null;
$sort      = in_array($_GET['sort'] ?? '', ['date','amount','member','type']) ? $_GET['sort'] : 'date';
$order     = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$date      = get_date_range();
[$offset, $per_page, $page] = get_pagination(25);

try {
    $pdo = db();

    $where  = ['p.created_at BETWEEN ? AND ?'];
    $params = [$date['from'] . ' 00:00:00', $date['to'] . ' 23:59:59'];

    if ($search) {
        $where[]  = "(CONCAT(m.first_name,' ',m.last_name) LIKE ? OR m.email LIKE ? OR p.transaction_id LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like; $params[] = $like; $params[] = $like;
    }
    if ($type)      { $where[] = 'p.type = ?';   $params[] = $type;      }
    if ($status)    { $where[] = 'p.status = ?'; $params[] = $status;    }
    if ($method)    { $where[] = 'p.method = ?'; $params[] = $method;    }
    if ($member_id) { $where[] = 'p.member_id = ?'; $params[] = $member_id; }

    $whereSQL = implode(' AND ', $where);

    $sort_map = [
        'date'   => 'p.created_at',
        'amount' => 'p.amount',
        'member' => 'm.first_name',
        'type'   => 'p.type',
    ];
    $orderSQL = ($sort_map[$sort] ?? 'p.created_at') . ' ' . $order;

    $count = $pdo->prepare("
        SELECT COUNT(*) FROM payments p
        JOIN members m ON m.id = p.member_id
        WHERE $whereSQL
    ");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT p.id, CONCAT(m.first_name,' ',m.last_name) AS member_name,
               m.email AS member_email, p.amount, p.type, p.method,
               p.transaction_id, p.status, p.description, p.created_at
        FROM payments p
        JOIN members m ON m.id = p.member_id
        WHERE $whereSQL
        ORDER BY $orderSQL
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $payments = $stmt->fetchAll();

    // Revenue totals for the filtered period
    $totals_stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN p.status='completed' AND p.amount>0 THEN p.amount ELSE 0 END), 0) AS gross_revenue,
            COALESCE(SUM(CASE WHEN p.status='refunded' THEN p.amount ELSE 0 END), 0) AS refunds,
            COALESCE(SUM(CASE WHEN p.status='completed' AND p.amount>0 THEN p.amount ELSE 0 END), 0)
              - COALESCE(SUM(CASE WHEN p.status='refunded' THEN p.amount ELSE 0 END), 0) AS net_revenue,
            COUNT(CASE WHEN p.status='completed' AND p.amount>0 THEN 1 END) AS total_transactions,
            COUNT(CASE WHEN p.status='failed' THEN 1 END) AS failed_count,
            COUNT(CASE WHEN p.status='pending' THEN 1 END) AS pending_count
        FROM payments p
        JOIN members m ON m.id = p.member_id
        WHERE $whereSQL
    ");
    $totals_stmt->execute($params);
    $totals = $totals_stmt->fetch();

    // By type
    $by_type_stmt = $pdo->prepare("
        SELECT p.type, COALESCE(SUM(p.amount), 0) AS total, COUNT(*) AS count
        FROM payments p JOIN members m ON m.id=p.member_id
        WHERE $whereSQL AND p.status='completed' AND p.amount>0
        GROUP BY p.type
    ");
    $by_type_stmt->execute($params);
    $totals['by_type'] = $by_type_stmt->fetchAll();

    success('Payments retrieved.', [
        'payments'   => $payments,
        'pagination' => [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil($total / max($per_page, 1)),
        ],
        'totals' => $totals,
    ]);
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}