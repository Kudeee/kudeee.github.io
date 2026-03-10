<?php
/**
 * GET /api/admin/settings/audit-log.php
 *
 * Returns the admin activity/audit log.
 * Restricted to super_admin and admin roles.
 *
 * Query params:
 *   admin_id    int      Filter by specific admin
 *   action      string   Filter by action type (e.g. create_member, update_class)
 *   target_type string   Filter by target (member | trainer | class_schedule | event | payment | admin_user)
 *   date_from   date     YYYY-MM-DD
 *   date_to     date     YYYY-MM-DD
 *   page        int
 *   per_page    int      default 50
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "logs": [ { id, admin_name, admin_role, action, target_type,
 *                 target_id, notes, created_at } ],
 *     "pagination": { total, page, per_page, total_pages }
 *   }
 *
 * DB tables used:
 *   admin_logs, admin_users
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin(['admin', 'super_admin']);

// ─── Input ────────────────────────────────────────────────────────────────────
$filter_admin_id   = isset($_GET['admin_id'])    ? sanitize_int($_GET['admin_id'])            : null;
$filter_action     = sanitize_string($_GET['action']      ?? '');
$filter_target     = sanitize_string($_GET['target_type'] ?? '');
$date              = get_date_range();
[$offset, $per_page, $page] = get_pagination(50);

// ─── TODO: replace stub with real DB query ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $where  = ['al.created_at BETWEEN :from AND :to'];
    $params = [':from' => $date['from'].' 00:00:00', ':to' => $date['to'].' 23:59:59'];

    if ($filter_admin_id) {
        $where[]         = 'al.admin_id = :aid';
        $params[':aid']  = $filter_admin_id;
    }
    if ($filter_action) {
        $where[]          = 'al.action = :act';
        $params[':act']   = $filter_action;
    }
    if ($filter_target) {
        $where[]           = 'al.target_type = :tgt';
        $params[':tgt']    = $filter_target;
    }

    $whereSQL = implode(' AND ', $where);

    $count = $pdo->prepare("SELECT COUNT(*) FROM admin_logs al WHERE $whereSQL");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT al.id, CONCAT(u.first_name,' ',u.last_name) AS admin_name,
               u.role AS admin_role, al.action, al.target_type, al.target_id,
               al.notes, al.created_at
        FROM admin_logs al
        JOIN admin_users u ON u.id = al.admin_id
        WHERE $whereSQL
        ORDER BY al.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $params[':limit']  = $per_page;
    $params[':offset'] = $offset;
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    success('Audit log retrieved.', [
        'logs'       => $logs,
        'pagination' => [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil($total / $per_page),
        ],
    ]);
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
