<?php
/**
 * GET /api/admin/members/list.php
 *
 * Returns a paginated, filterable list of all members.
 *
 * Query params:
 *   search      string   Filter by name or email (partial match)
 *   plan        string   BASIC PLAN | PREMIUM PLAN | VIP PLAN
 *   status      string   active | expired | paused | suspended
 *   date_from   date     Registered on or after (YYYY-MM-DD)
 *   date_to     date     Registered on or before (YYYY-MM-DD)
 *   sort        string   name | plan | status | joined | expiry  (default: joined)
 *   order       string   asc | desc  (default: desc)
 *   page        int      default 1
 *   per_page    int      default 20, max 100
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "members": [ { id, first_name, last_name, email, phone, plan, status,
 *                    billing_cycle, joined_at, expiry_date, is_paused } ],
 *     "pagination": { total, page, per_page, total_pages },
 *     "summary": { total_members, active, expired, paused, suspended }
 *   }
 *
 * DB tables used:
 *   members, subscriptions
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$search   = sanitize_string($_GET['search']   ?? '');
$plan     = sanitize_string($_GET['plan']     ?? '');
$status   = sanitize_string($_GET['status']   ?? '');
$sort     = in_array($_GET['sort'] ?? '', ['name','plan','status','joined','expiry'])
            ? $_GET['sort'] : 'joined';
$order    = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

$date     = get_date_range();
[$offset, $per_page, $page] = get_pagination();

// ─── TODO: replace stub with real DB query ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $where  = ['m.created_at BETWEEN :from AND :to'];
    $params = [':from' => $date['from'] . ' 00:00:00', ':to' => $date['to'] . ' 23:59:59'];

    if ($search) {
        $where[]          = '(m.first_name LIKE :s OR m.last_name LIKE :s OR m.email LIKE :s)';
        $params[':s']     = '%' . $search . '%';
    }
    if ($plan) {
        $where[]         = 'm.plan = :plan';
        $params[':plan'] = $plan;
    }
    if ($status) {
        $where[]           = 'm.status = :status';
        $params[':status'] = $status;
    }

    $whereSQL = implode(' AND ', $where);

    $sort_map = [
        'name'   => 'm.first_name',
        'plan'   => 'm.plan',
        'status' => 'm.status',
        'joined' => 'm.created_at',
        'expiry' => 's.expiry_date',
    ];
    $orderSQL = $sort_map[$sort] . ' ' . $order;

    // Count
    $count = $pdo->prepare("
        SELECT COUNT(*) FROM members m
        LEFT JOIN subscriptions s ON s.member_id = m.id AND s.status = 'active'
        WHERE $whereSQL
    ");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    // Fetch
    $stmt = $pdo->prepare("
        SELECT m.id, m.first_name, m.last_name, m.email, m.phone,
               m.plan, m.status, m.created_at AS joined_at,
               s.billing_cycle, s.expiry_date, s.is_paused
        FROM members m
        LEFT JOIN subscriptions s ON s.member_id = m.id AND s.status = 'active'
        WHERE $whereSQL
        ORDER BY $orderSQL
        LIMIT :limit OFFSET :offset
    ");
    $params[':limit']  = $per_page;
    $params[':offset'] = $offset;
    $stmt->execute($params);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary counts
    $summary_stmt = $pdo->query("
        SELECT status, COUNT(*) AS cnt FROM members GROUP BY status
    ");
    $summary_rows = $summary_stmt->fetchAll(PDO::FETCH_ASSOC);
    $summary = ['total_members'=>0,'active'=>0,'expired'=>0,'paused'=>0,'suspended'=>0];
    foreach ($summary_rows as $r) {
        $summary[$r['status']] = (int) $r['cnt'];
        $summary['total_members'] += (int) $r['cnt'];
    }

    success('Members retrieved.', [
        'members'    => $members,
        'pagination' => [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil($total / $per_page),
        ],
        'summary' => $summary,
    ]);
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
