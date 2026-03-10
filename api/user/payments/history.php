<?php
/**
 * GET /api/user/payments/history.php
 *
 * Returns the authenticated member's payment history with optional filters.
 *
 * Query params (GET):
 *   type        string  optional  "membership" | "class" | "trainer"
 *   status      string  optional  "completed" | "pending" | "refunded"
 *   range       string  optional  "month" | "3months" | "6months" | "year"
 *   page        int     optional  default 1
 *   per_page    int     optional  default 10 (max 50)
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "payments": [ { id, transaction_id, type, amount, method, status, created_at, description } ],
 *     "summary": { "total_spent": 3198, "total_transactions": 4, "this_month": 899 },
 *     "pagination": { "page": 1, "per_page": 10, "total": 4, "total_pages": 1 }
 *   }
 *
 * DB tables used (when connected):
 *   payments  (id, member_id, transaction_id, type, amount, method, status, created_at)
 */

require_once __DIR__ . '/../../config.php';
require_method('GET');

$member = require_member();

// ─── Filters ─────────────────────────────────────────────────────────────────

$type     = sanitize_string($_GET['type']  ?? '');
$status   = sanitize_string($_GET['status'] ?? '');
$range    = sanitize_string($_GET['range']  ?? '');
$page     = max(1, (int)($_GET['page']     ?? 1));
$per_page = min(50, max(1, (int)($_GET['per_page'] ?? 10)));
$offset   = ($page - 1) * $per_page;

// Date range calculation
$date_from = null;
switch ($range) {
    case 'month':   $date_from = date('Y-m-d', strtotime('-1 month')); break;
    case '3months': $date_from = date('Y-m-d', strtotime('-3 months')); break;
    case '6months': $date_from = date('Y-m-d', strtotime('-6 months')); break;
    case 'year':    $date_from = date('Y-m-d', strtotime('-1 year')); break;
}

// ─── TODO: replace stub with real DB logic ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Build WHERE conditions dynamically
    $conditions = ['p.member_id = ?'];
    $params     = [$member['member_id']];

    if ($type && in_array($type, ['membership', 'class', 'trainer'], true)) {
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

    // Summary (totals, unaffected by pagination)
    $summary_stmt = $pdo->prepare("
        SELECT
            SUM(amount) AS total_spent,
            COUNT(*)    AS total_transactions,
            SUM(CASE WHEN MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())
                     THEN amount ELSE 0 END) AS this_month
        FROM payments p $where
    ");
    $summary_stmt->execute($params);
    $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

    // Count for pagination
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM payments p $where");
    $count_stmt->execute($params);
    $total = (int) $count_stmt->fetchColumn();

    // Paginated results
    $params_paged = array_merge($params, [$per_page, $offset]);
    $stmt = $pdo->prepare("
        SELECT p.id, p.transaction_id, p.type, p.amount, p.method, p.status,
               p.created_at, p.description
        FROM payments p
        $where
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($params_paged);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    success('OK', [
        'payments'   => $payments,
        'summary'    => [
            'total_spent'         => (float)($summary['total_spent'] ?? 0),
            'total_transactions'  => (int)($summary['total_transactions'] ?? 0),
            'this_month'          => (float)($summary['this_month'] ?? 0),
        ],
        'pagination' => [
            'page'        => $page,
            'per_page'    => $per_page,
            'total'       => $total,
            'total_pages' => (int) ceil($total / $per_page),
        ],
    ]);
*/

// ─── STUB response ────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
