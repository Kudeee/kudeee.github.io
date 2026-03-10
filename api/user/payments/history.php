<?php
/**
 * GET /api/user/payments/history.php
 * Returns the authenticated member's payment history with optional filters.
 */

require_once __DIR__ . '/../../config.php';
require_method('GET');

$member = require_member();

$type     = sanitize_string($_GET['type']     ?? '');
$status   = sanitize_string($_GET['status']   ?? '');
$range    = sanitize_string($_GET['range']     ?? '');
$page     = max(1, (int)($_GET['page']         ?? 1));
$per_page = min(50, max(1, (int)($_GET['per_page'] ?? 10)));
$offset   = ($page - 1) * $per_page;

$date_from = null;
switch ($range) {
    case 'month':   $date_from = date('Y-m-d', strtotime('-1 month'));  break;
    case '3months': $date_from = date('Y-m-d', strtotime('-3 months')); break;
    case '6months': $date_from = date('Y-m-d', strtotime('-6 months')); break;
    case 'year':    $date_from = date('Y-m-d', strtotime('-1 year'));   break;
}

try {
    $pdo = db();

    $conditions = ['p.member_id = ?'];
    $params     = [$member['member_id']];

    if ($type && in_array($type, ['membership', 'class', 'trainer', 'event'], true)) {
        $conditions[] = 'p.type = ?';
        $params[]     = $type;
    }
    if ($status && in_array($status, ['completed', 'pending', 'refunded'], true)) {
        $conditions[] = 'p.status = ?';
        $params[]     = $status;
    }
    if ($date_from) {
        $conditions[] = 'DATE(p.created_at) >= ?';
        $params[]     = $date_from;
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    // Summary totals
    $summary_stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(amount), 0)   AS total_spent,
            COUNT(*)                   AS total_transactions,
            COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(NOW())
                              AND  YEAR(created_at)  = YEAR(NOW())
                         THEN amount ELSE 0 END), 0) AS this_month
        FROM payments p $where
    ");
    $summary_stmt->execute($params);
    $summary = $summary_stmt->fetch();

    // Total count for pagination
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM payments p $where");
    $count_stmt->execute($params);
    $total = (int)$count_stmt->fetchColumn();

    // Paginated results — LIMIT/OFFSET cast to int directly (PDO limitation with LIMIT params)
    $stmt = $pdo->prepare("
        SELECT p.id, p.transaction_id, p.type, p.amount, p.method,
               p.status, p.created_at, p.description
        FROM payments p
        $where
        ORDER BY p.created_at DESC
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $payments = $stmt->fetchAll();

    success('OK', [
        'payments'   => $payments,
        'summary'    => [
            'total_spent'        => (float)$summary['total_spent'],
            'total_transactions' => (int)$summary['total_transactions'],
            'this_month'         => (float)$summary['this_month'],
        ],
        'pagination' => [
            'page'        => $page,
            'per_page'    => $per_page,
            'total'       => $total,
            'total_pages' => (int)ceil($total / $per_page),
        ],
    ]);

} catch (PDOException $e) {
    error('A database error occurred. Please try again.', 500);
}