<?php
/**
 * GET /api/admin/trainers/list.php
 *
 * Returns all trainers with optional filters.
 *
 * Query params:
 *   search      string   Filter by name or specialty
 *   status      string   active | inactive
 *   sort        string   name | rating | experience | bookings  (default: name)
 *   order       string   asc | desc  (default: asc)
 *   page        int
 *   per_page    int      default 20
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "trainers": [ { id, name, email, phone, specialty, specialty_tags,
 *                     experience_years, rating, hourly_rate, status,
 *                     total_bookings, joined_at } ],
 *     "pagination": { total, page, per_page, total_pages }
 *   }
 *
 * DB tables used:
 *   trainers, trainer_bookings
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$search  = sanitize_string($_GET['search'] ?? '');
$status  = sanitize_string($_GET['status'] ?? '');
$sort    = in_array($_GET['sort'] ?? '', ['name','rating','experience','bookings'])
           ? $_GET['sort'] : 'name';
$order   = strtoupper($_GET['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
[$offset, $per_page, $page] = get_pagination();

// ─── TODO: replace stub with real DB query ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $where  = ['1=1'];
    $params = [];

    if ($search) {
        $where[]      = '(t.name LIKE :s OR t.specialty LIKE :s)';
        $params[':s'] = '%' . $search . '%';
    }
    if ($status) {
        $where[]           = 't.status = :status';
        $params[':status'] = $status;
    }

    $sort_map = [
        'name'       => 't.name',
        'rating'     => 't.rating',
        'experience' => 't.experience_years',
        'bookings'   => 'total_bookings',
    ];
    $orderSQL   = $sort_map[$sort] . ' ' . $order;
    $whereSQL   = implode(' AND ', $where);

    $count = $pdo->prepare("SELECT COUNT(*) FROM trainers t WHERE $whereSQL");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT t.*,
               (SELECT COUNT(*) FROM trainer_bookings tb WHERE tb.trainer_id = t.id) AS total_bookings
        FROM trainers t
        WHERE $whereSQL
        ORDER BY $orderSQL
        LIMIT :limit OFFSET :offset
    ");
    $params[':limit']  = $per_page;
    $params[':offset'] = $offset;
    $stmt->execute($params);
    $trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode JSON specialty_tags
    foreach ($trainers as &$t) {
        $t['specialty_tags'] = json_decode($t['specialty_tags'] ?? '[]', true);
        unset($t['password_hash']); // safety
    }
    unset($t);

    success('Trainers retrieved.', [
        'trainers'   => $trainers,
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
