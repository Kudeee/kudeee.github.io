<?php
require_once __DIR__ . '/../../config.php';

$member = require_member();
$pdo    = db();

$type   = sanitize_string($_GET['type']   ?? '');
$status = sanitize_string($_GET['status'] ?? '');

$where  = ['member_id = ?'];
$params = [$member['id']];

if ($type && $type !== 'all') {
    $where[]  = 'type = ?';
    $params[] = $type;
}
if ($status && $status !== 'all') {
    $where[]  = 'status = ?';
    $params[] = $status;
}

// Date range filter
$range = sanitize_string($_GET['range'] ?? '');
if ($range === 'month') {
    $where[]  = 'created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
} elseif ($range === '3months') {
    $where[]  = 'created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)';
} elseif ($range === '6months') {
    $where[]  = 'created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)';
} elseif ($range === 'year') {
    $where[]  = 'created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
}

$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT * FROM payments
    WHERE $whereSQL
    ORDER BY created_at DESC
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Summary totals
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) AS total_spent,
        COUNT(*) AS total_transactions,
        SUM(CASE WHEN status = 'completed' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) THEN amount ELSE 0 END) AS this_month
    FROM payments
    WHERE member_id = ?
");
$stmt->execute([$member['id']]);
$summary = $stmt->fetch();

success('OK', [
    'payments' => $payments,
    'summary'  => $summary,
]);