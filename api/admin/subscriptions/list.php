<?php
/**
 * GET /api/admin/subscriptions/list.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

[$offset, $per_page, $page] = get_pagination();
$status = sanitize_string($_GET['status'] ?? '');

try {
    $pdo = db();

    $where  = ['1=1'];
    $params = [];
    if ($status) { $where[] = 's.status = ?'; $params[] = $status; }
    $whereSQL = implode(' AND ', $where);

    $count = $pdo->prepare("SELECT COUNT(*) FROM subscriptions s WHERE $whereSQL");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT s.*, CONCAT(m.first_name,' ',m.last_name) AS member_name,
               m.email, DATEDIFF(s.expiry_date, CURDATE()) AS days_remaining
        FROM subscriptions s
        JOIN members m ON m.id = s.member_id
        WHERE $whereSQL
        ORDER BY s.created_at DESC
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $subscriptions = $stmt->fetchAll();

    // Stats
    $stmt = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status='active'");
    $active_count = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status='active' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
    $expiring_soon = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT plan, COUNT(*) AS cnt FROM subscriptions WHERE status='active' GROUP BY plan ORDER BY cnt DESC LIMIT 1");
    $top_plan_row = $stmt->fetch();

    // Plan distribution
    $stmt = $pdo->query("SELECT plan, COUNT(*) AS cnt, SUM(price) AS revenue FROM subscriptions WHERE status='active' GROUP BY plan");
    $plan_distribution = $stmt->fetchAll();

    // Monthly revenue from subscriptions
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(price), 0) AS monthly_revenue
        FROM subscriptions WHERE status='active' AND billing_cycle='monthly'
    ");
    $stmt->execute();
    $monthly_revenue = (float) $stmt->fetchColumn();

    success('Subscriptions retrieved.', [
        'subscriptions' => $subscriptions,
        'pagination'    => [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil($total / max($per_page, 1)),
        ],
        'stats' => [
            'active_count'      => $active_count,
            'expiring_soon'     => $expiring_soon,
            'top_plan'          => $top_plan_row['plan'] ?? 'N/A',
            'monthly_revenue'   => $monthly_revenue,
            'plan_distribution' => $plan_distribution,
        ],
    ]);
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}