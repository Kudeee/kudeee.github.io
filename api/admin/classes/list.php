<?php
/**
 * GET /api/admin/classes/list.php
 *
 * Returns all class schedule slots with booking counts.
 *
 * Query params:
 *   search      string   Filter by class name or trainer name
 *   class_type  string   Filter by class type / category
 *   date_from   date     YYYY-MM-DD
 *   date_to     date     YYYY-MM-DD
 *   status      string   active | cancelled | completed
 *   page        int
 *   per_page    int      default 20
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "classes": [ { id, class_name, class_type, trainer_id, trainer_name,
 *                    schedule_date, start_time, end_time, max_participants,
 *                    current_participants, spots_left, status, created_at } ],
 *     "pagination": { total, page, per_page, total_pages }
 *   }
 *
 * DB tables used:
 *   class_schedules, trainers
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$search     = sanitize_string($_GET['search']     ?? '');
$class_type = sanitize_string($_GET['class_type'] ?? '');
$status     = sanitize_string($_GET['status']     ?? '');
$date       = get_date_range();
[$offset, $per_page, $page] = get_pagination();

// ─── TODO: replace stub with real DB query ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $where  = ['cs.schedule_date BETWEEN :from AND :to'];
    $params = [':from' => $date['from'], ':to' => $date['to']];

    if ($search) {
        $where[]      = '(cs.class_name LIKE :s OR t.name LIKE :s)';
        $params[':s'] = '%' . $search . '%';
    }
    if ($class_type) {
        $where[]            = 'cs.class_type = :ct';
        $params[':ct']      = $class_type;
    }
    if ($status) {
        $where[]            = 'cs.status = :status';
        $params[':status']  = $status;
    }

    $whereSQL = implode(' AND ', $where);

    $count = $pdo->prepare("
        SELECT COUNT(*) FROM class_schedules cs
        LEFT JOIN trainers t ON t.id = cs.trainer_id
        WHERE $whereSQL
    ");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT cs.*,
               t.name AS trainer_name,
               (cs.max_participants - cs.current_participants) AS spots_left
        FROM class_schedules cs
        LEFT JOIN trainers t ON t.id = cs.trainer_id
        WHERE $whereSQL
        ORDER BY cs.schedule_date ASC, cs.start_time ASC
        LIMIT :limit OFFSET :offset
    ");
    $params[':limit']  = $per_page;
    $params[':offset'] = $offset;
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    success('Classes retrieved.', [
        'classes'    => $classes,
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
