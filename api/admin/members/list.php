<?php
/**
 * GET /api/admin/members/list.php
 * Returns paginated, filterable list of all members with subscription info.
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

$search  = sanitize_string($_GET['search']  ?? '');
$plan    = sanitize_string($_GET['plan']    ?? '');
$status  = sanitize_string($_GET['status']  ?? '');
$sort    = in_array($_GET['sort'] ?? '', ['name','plan','status','joined','expiry']) ? $_GET['sort'] : 'joined';
$order   = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

[$offset, $per_page, $page] = get_pagination();

try {
    $pdo = db();

    $where  = ['1=1'];
    $params = [];

    if ($search) {
        $where[]  = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like; $params[] = $like; $params[] = $like;
    }
    if ($plan) {
        $where[]  = 'm.plan = ?';
        $params[] = $plan;
    }
    if ($status) {
        $where[]  = 'm.status = ?';
        $params[] = $status;
    }

    $whereSQL = implode(' AND ', $where);

    $sort_map = [
        'name'   => 'm.first_name',
        'plan'   => 'm.plan',
        'status' => 'm.status',
        'joined' => 'm.created_at',
        'expiry' => 's.expiry_date',
    ];
    $orderSQL = ($sort_map[$sort] ?? 'm.created_at') . ' ' . $order;

    // Count
    $count = $pdo->prepare("
        SELECT COUNT(*) FROM members m
        LEFT JOIN subscriptions s ON s.member_id = m.id AND s.status = 'active'
        WHERE $whereSQL
    ");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    // Fetch page
    $stmt = $pdo->prepare("
        SELECT m.id, m.first_name, m.last_name, m.email, m.phone,
               m.plan, m.status, m.join_date, m.created_at AS joined_at,
               s.billing_cycle, s.expiry_date,
               (s.paused_at IS NOT NULL AND s.resumed_at IS NULL) AS is_paused
        FROM members m
        LEFT JOIN subscriptions s ON s.member_id = m.id AND s.status = 'active'
        WHERE $whereSQL
        ORDER BY $orderSQL
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $members = $stmt->fetchAll();

    // Summary counts
    $summary_stmt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM members GROUP BY status");
    $summary = ['total_members' => 0, 'active' => 0, 'expired' => 0, 'paused' => 0, 'suspended' => 0];
    foreach ($summary_stmt->fetchAll() as $r) {
        if (isset($summary[$r['status']])) $summary[$r['status']] = (int) $r['cnt'];
        $summary['total_members'] += (int) $r['cnt'];
    }

    // New this month
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
    $stmt->execute();
    $summary['new_this_month'] = (int) $stmt->fetchColumn();

    // Expiring this month
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE expiry_date BETWEEN CURDATE() AND LAST_DAY(CURDATE()) AND status='active'");
    $stmt->execute();
    $summary['expiring_this_month'] = (int) $stmt->fetchColumn();

    success('Members retrieved.', [
        'members'    => $members,
        'pagination' => [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil($total / max($per_page, 1)),
        ],
        'summary' => $summary,
    ]);
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}