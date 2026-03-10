<?php
/**
 * GET /api/admin/bookings/list.php
 *
 * Returns all bookings (class and/or trainer) with filters.
 *
 * Query params:
 *   type        string   all | class | trainer  (default: all)
 *   search      string   Member name/email or trainer name
 *   status      string   confirmed | cancelled | completed | no_show
 *   date_from   date     YYYY-MM-DD
 *   date_to     date     YYYY-MM-DD
 *   member_id   int      Filter by specific member
 *   trainer_id  int      Filter by specific trainer
 *   sort        string   date | member | trainer | status  (default: date)
 *   order       string   asc | desc  (default: desc)
 *   page        int
 *   per_page    int      default 25
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "bookings": [ { booking_id, booking_type, member_name, member_email,
 *                     class_or_trainer, date, time, status,
 *                     amount_paid, created_at } ],
 *     "pagination": { total, page, per_page, total_pages },
 *     "summary": { total, confirmed, cancelled, completed, no_show }
 *   }
 *
 * DB tables used:
 *   class_bookings, class_schedules, trainer_bookings, trainers, members
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$type      = in_array($_GET['type'] ?? 'all', ['all','class','trainer']) ? ($_GET['type'] ?? 'all') : 'all';
$search    = sanitize_string($_GET['search']     ?? '');
$status    = sanitize_string($_GET['status']     ?? '');
$member_id = isset($_GET['member_id'])  ? sanitize_int($_GET['member_id'])  : null;
$trainer_id= isset($_GET['trainer_id']) ? sanitize_int($_GET['trainer_id']) : null;
$sort      = in_array($_GET['sort'] ?? '', ['date','member','trainer','status']) ? $_GET['sort'] : 'date';
$order     = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$date      = get_date_range();
[$offset, $per_page, $page] = get_pagination(25);

// ─── TODO: replace stub with real DB query ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Build unified bookings view via UNION
    // Class bookings
    $class_sql = "
        SELECT cb.id AS booking_id, 'class' AS booking_type,
               CONCAT(m.first_name,' ',m.last_name) AS member_name, m.email AS member_email,
               cs.class_name AS class_or_trainer,
               cb.booking_date AS booking_date, cs.start_time AS booking_time,
               cb.status, p.amount AS amount_paid, cb.created_at
        FROM class_bookings cb
        JOIN members m ON m.id = cb.member_id
        JOIN class_schedules cs ON cs.id = cb.class_schedule_id
        LEFT JOIN payments p ON p.id = cb.payment_id
    ";

    // Trainer bookings
    $trainer_sql = "
        SELECT tb.id AS booking_id, 'trainer' AS booking_type,
               CONCAT(m.first_name,' ',m.last_name) AS member_name, m.email AS member_email,
               t.name AS class_or_trainer,
               tb.session_date AS booking_date, tb.session_time AS booking_time,
               tb.status, p.amount AS amount_paid, tb.created_at
        FROM trainer_bookings tb
        JOIN members m ON m.id = tb.member_id
        JOIN trainers t ON t.id = tb.trainer_id
        LEFT JOIN payments p ON p.id = tb.payment_id
    ";

    // Filter and combine
    $where_class   = ['cb.booking_date BETWEEN :from AND :to'];
    $where_trainer = ['tb.session_date BETWEEN :from AND :to'];
    $params = [':from' => $date['from'], ':to' => $date['to']];

    if ($search) {
        $where_class[]   = "(CONCAT(m.first_name,' ',m.last_name) LIKE :s OR m.email LIKE :s OR cs.class_name LIKE :s)";
        $where_trainer[] = "(CONCAT(m.first_name,' ',m.last_name) LIKE :s OR m.email LIKE :s OR t.name LIKE :s)";
        $params[':s']    = '%' . $search . '%';
    }
    if ($status) {
        $where_class[]   = 'cb.status = :status';
        $where_trainer[] = 'tb.status = :status';
        $params[':status'] = $status;
    }
    if ($member_id) {
        $where_class[]   = 'cb.member_id = :mid';
        $where_trainer[] = 'tb.member_id = :mid';
        $params[':mid']  = $member_id;
    }
    if ($trainer_id) {
        $where_class[]   = '0=1'; // no trainer filter on class bookings
        $where_trainer[] = 'tb.trainer_id = :tid';
        $params[':tid']  = $trainer_id;
    }

    $class_sql   .= ' WHERE ' . implode(' AND ', $where_class);
    $trainer_sql .= ' WHERE ' . implode(' AND ', $where_trainer);

    $sort_map = [
        'date'    => 'booking_date',
        'member'  => 'member_name',
        'trainer' => 'class_or_trainer',
        'status'  => 'status',
    ];
    $orderSQL = $sort_map[$sort] . ' ' . $order;

    $union_sql = match($type) {
        'class'   => $class_sql,
        'trainer' => $trainer_sql,
        default   => "($class_sql) UNION ALL ($trainer_sql)",
    };

    // Count
    $count = $pdo->prepare("SELECT COUNT(*) FROM ($union_sql) AS combined");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    // Fetch
    $stmt = $pdo->prepare("SELECT * FROM ($union_sql) AS combined ORDER BY $orderSQL LIMIT :limit OFFSET :offset");
    $params[':limit']  = $per_page;
    $params[':offset'] = $offset;
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary counts
    $sum_stmt = $pdo->prepare("SELECT status, COUNT(*) AS cnt FROM ($union_sql) AS combined GROUP BY status");
    $sum_stmt->execute($params);
    $summary = ['total'=>$total,'confirmed'=>0,'cancelled'=>0,'completed'=>0,'no_show'=>0];
    foreach ($sum_stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (isset($summary[$r['status']])) $summary[$r['status']] = (int) $r['cnt'];
    }

    success('Bookings retrieved.', [
        'bookings'   => $bookings,
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
