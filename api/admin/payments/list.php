<?php
/**
 * GET /api/admin/payments/list.php
 *
 * Returns all payment records with filters and totals.
 *
 * Query params:
 *   search      string   Member name or email or reference number
 *   type        string   membership | class | trainer | event
 *   status      string   completed | pending | refunded | failed
 *   method      string   cash | card | gcash
 *   date_from   date     YYYY-MM-DD  (defaults to start of current month)
 *   date_to     date     YYYY-MM-DD  (defaults to end of current month)
 *   member_id   int      Filter by specific member
 *   sort        string   date | amount | member | type  (default: date)
 *   order       string   asc | desc  (default: desc)
 *   page        int
 *   per_page    int      default 25
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "payments": [ { id, member_name, member_email, amount, type,
 *                     payment_method, reference_number, status, created_at } ],
 *     "pagination": { total, page, per_page, total_pages },
 *     "totals": { gross_revenue, refunds, net_revenue, by_type, by_method }
 *   }
 *
 * DB tables used:
 *   payments, members
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$search    = sanitize_string($_GET['search']    ?? '');
$type      = sanitize_string($_GET['type']      ?? '');
$status    = sanitize_string($_GET['status']    ?? '');
$method    = sanitize_string($_GET['method']    ?? '');
$member_id = isset($_GET['member_id']) ? sanitize_int($_GET['member_id']) : null;
$sort      = in_array($_GET['sort'] ?? '', ['date','amount','member','type']) ? $_GET['sort'] : 'date';
$order     = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$date      = get_date_range();
[$offset, $per_page, $page] = get_pagination(25);

$valid_types   = ['', 'membership', 'class', 'trainer', 'event'];
$valid_statuses = ['', 'completed', 'pending', 'refunded', 'failed'];
$valid_methods  = ['', 'cash', 'card', 'gcash'];

if (!in_array($type, $valid_types, true))     error('Invalid payment type filter.');
if (!in_array($status, $valid_statuses, true)) error('Invalid status filter.');
if (!in_array($method, $valid_methods, true))  error('Invalid payment method filter.');

// ─── TODO: replace stub with real DB query ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $where  = ['p.created_at BETWEEN :from AND :to'];
    $params = [':from' => $date['from'].' 00:00:00', ':to' => $date['to'].' 23:59:59'];

    if ($search) {
        $where[]      = "(CONCAT(m.first_name,' ',m.last_name) LIKE :s OR m.email LIKE :s OR p.reference_number LIKE :s)";
        $params[':s'] = '%'.$search.'%';
    }
    if ($type)   { $where[] = 'p.type = :type';           $params[':type']   = $type;   }
    if ($status) { $where[] = 'p.status = :status';       $params[':status'] = $status; }
    if ($method) { $where[] = 'p.payment_method = :meth'; $params[':meth']   = $method; }
    if ($member_id) { $where[] = 'p.member_id = :mid';    $params[':mid']    = $member_id; }

    $whereSQL = implode(' AND ', $where);

    $sort_map = [
        'date'   => 'p.created_at',
        'amount' => 'p.amount',
        'member' => 'm.first_name',
        'type'   => 'p.type',
    ];
    $orderSQL = $sort_map[$sort] . ' ' . $order;

    // Total count
    $count = $pdo->prepare("
        SELECT COUNT(*) FROM payments p
        JOIN members m ON m.id = p.member_id
        WHERE $whereSQL
    ");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    // Payments
    $stmt = $pdo->prepare("
        SELECT p.id, CONCAT(m.first_name,' ',m.last_name) AS member_name,
               m.email AS member_email, p.amount, p.type, p.payment_method,
               p.reference_number, p.status, p.created_at
        FROM payments p
        JOIN members m ON m.id = p.member_id
        WHERE $whereSQL
        ORDER BY $orderSQL
        LIMIT :limit OFFSET :offset
    ");
    $params[':limit']  = $per_page;
    $params[':offset'] = $offset;
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Revenue totals
    $totals_stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) AS gross_revenue,
            SUM(CASE WHEN status = 'refunded'  THEN amount ELSE 0 END) AS refunds,
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END)
              - SUM(CASE WHEN status = 'refunded' THEN amount ELSE 0 END) AS net_revenue
        FROM payments p
        JOIN members m ON m.id = p.member_id
        WHERE $whereSQL
    ");
    $totals_stmt->execute($params);
    $totals = $totals_stmt->fetch(PDO::FETCH_ASSOC);

    // Breakdown by type
    $by_type_stmt = $pdo->prepare("
        SELECT type, SUM(amount) AS total FROM payments p
        JOIN members m ON m.id = p.member_id
        WHERE $whereSQL AND p.status = 'completed'
        GROUP BY type
    ");
    $by_type_stmt->execute($params);
    $totals['by_type'] = $by_type_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Breakdown by method
    $by_method_stmt = $pdo->prepare("
        SELECT payment_method, SUM(amount) AS total FROM payments p
        JOIN members m ON m.id = p.member_id
        WHERE $whereSQL AND p.status = 'completed'
        GROUP BY payment_method
    ");
    $by_method_stmt->execute($params);
    $totals['by_method'] = $by_method_stmt->fetchAll(PDO::FETCH_ASSOC);

    success('Payments retrieved.', [
        'payments'   => $payments,
        'pagination' => [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil($total / $per_page),
        ],
        'totals' => $totals,
    ]);
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
