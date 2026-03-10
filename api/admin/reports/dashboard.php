<?php
/**
 * GET /api/admin/reports/dashboard.php
 *
 * Returns high-level KPIs for the admin dashboard.
 * All counts and revenue figures are for the current month unless overridden.
 *
 * Query params:
 *   date_from   date   YYYY-MM-DD  (default: start of current month)
 *   date_to     date   YYYY-MM-DD  (default: end of current month)
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "period": { "from": "...", "to": "..." },
 *     "members": {
 *       "total": int, "active": int, "new_this_period": int,
 *       "expired": int, "paused": int, "suspended": int
 *     },
 *     "revenue": {
 *       "gross": float, "refunds": float, "net": float,
 *       "by_type": [ { type, total } ],
 *       "by_plan": [ { plan, total } ]
 *     },
 *     "bookings": {
 *       "class_total": int, "class_confirmed": int, "class_cancelled": int,
 *       "trainer_total": int, "trainer_confirmed": int, "trainer_cancelled": int
 *     },
 *     "classes": { "scheduled": int, "completed": int, "cancelled": int },
 *     "events":  { "upcoming": int, "total_registrations": int },
 *     "top_classes":  [ { class_name, bookings } ],
 *     "top_trainers": [ { trainer_name, sessions } ]
 *   }
 *
 * DB tables used:
 *   members, subscriptions, payments, class_bookings, class_schedules,
 *   trainer_bookings, trainers, events, event_registrations
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

$date = get_date_range();
$from = $date['from'] . ' 00:00:00';
$to   = $date['to']   . ' 23:59:59';

// ─── TODO: replace stub with real DB queries ──────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // ── Members ──────────────────────────────────────────────────────────────
    $stmt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM members GROUP BY status");
    $member_counts = ['total'=>0,'active'=>0,'expired'=>0,'paused'=>0,'suspended'=>0];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (isset($member_counts[$r['status']])) $member_counts[$r['status']] = (int) $r['cnt'];
        $member_counts['total'] += (int) $r['cnt'];
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$from, $to]);
    $member_counts['new_this_period'] = (int) $stmt->fetchColumn();

    // ── Revenue ───────────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN status='completed' AND amount > 0 THEN amount ELSE 0 END) AS gross,
            SUM(CASE WHEN status='refunded'  OR  amount < 0 THEN ABS(amount) ELSE 0 END) AS refunds
        FROM payments WHERE created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$from, $to]);
    $rev = $stmt->fetch(PDO::FETCH_ASSOC);
    $rev['net'] = (float)$rev['gross'] - (float)$rev['refunds'];

    $stmt = $pdo->prepare("
        SELECT type, SUM(amount) AS total FROM payments
        WHERE created_at BETWEEN ? AND ? AND status='completed' AND amount > 0
        GROUP BY type
    ");
    $stmt->execute([$from, $to]);
    $rev['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT m.plan, SUM(p.amount) AS total
        FROM payments p JOIN members m ON m.id=p.member_id
        WHERE p.created_at BETWEEN ? AND ? AND p.type='membership' AND p.status='completed'
        GROUP BY m.plan
    ");
    $stmt->execute([$from, $to]);
    $rev['by_plan'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Bookings ──────────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) AS cnt FROM class_bookings
        WHERE created_at BETWEEN ? AND ? GROUP BY status
    ");
    $stmt->execute([$from, $to]);
    $cb = ['class_total'=>0,'class_confirmed'=>0,'class_cancelled'=>0];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $cb['class_total'] += (int)$r['cnt'];
        if ($r['status']==='confirmed') $cb['class_confirmed'] = (int)$r['cnt'];
        if ($r['status']==='cancelled') $cb['class_cancelled'] = (int)$r['cnt'];
    }

    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) AS cnt FROM trainer_bookings
        WHERE created_at BETWEEN ? AND ? GROUP BY status
    ");
    $stmt->execute([$from, $to]);
    $tb = ['trainer_total'=>0,'trainer_confirmed'=>0,'trainer_cancelled'=>0];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $tb['trainer_total'] += (int)$r['cnt'];
        if ($r['status']==='confirmed') $tb['trainer_confirmed'] = (int)$r['cnt'];
        if ($r['status']==='cancelled') $tb['trainer_cancelled'] = (int)$r['cnt'];
    }
    $bookings = array_merge($cb, $tb);

    // ── Classes ───────────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) AS cnt FROM class_schedules
        WHERE schedule_date BETWEEN ? AND ? GROUP BY status
    ");
    $stmt->execute([$date['from'], $date['to']]);
    $classes = ['scheduled'=>0,'completed'=>0,'cancelled'=>0];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (isset($classes[$r['status']])) $classes[$r['status']] = (int)$r['cnt'];
    }

    // ── Events ────────────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE() AND status='active'");
    $stmt->execute();
    $upcoming_events = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE created_at BETWEEN ? AND ? AND status='registered'");
    $stmt->execute([$from, $to]);
    $event_registrations = (int) $stmt->fetchColumn();

    // ── Top Classes ───────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT cs.class_name, COUNT(cb.id) AS bookings
        FROM class_bookings cb
        JOIN class_schedules cs ON cs.id = cb.class_schedule_id
        WHERE cb.created_at BETWEEN ? AND ? AND cb.status = 'confirmed'
        GROUP BY cs.class_name ORDER BY bookings DESC LIMIT 5
    ");
    $stmt->execute([$from, $to]);
    $top_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Top Trainers ──────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT t.name AS trainer_name, COUNT(tb.id) AS sessions
        FROM trainer_bookings tb
        JOIN trainers t ON t.id = tb.trainer_id
        WHERE tb.created_at BETWEEN ? AND ? AND tb.status = 'confirmed'
        GROUP BY t.name ORDER BY sessions DESC LIMIT 5
    ");
    $stmt->execute([$from, $to]);
    $top_trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    success('Dashboard data retrieved.', [
        'period'       => $date,
        'members'      => $member_counts,
        'revenue'      => $rev,
        'bookings'     => $bookings,
        'classes'      => $classes,
        'events'       => ['upcoming' => $upcoming_events, 'total_registrations' => $event_registrations],
        'top_classes'  => $top_classes,
        'top_trainers' => $top_trainers,
    ]);
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
