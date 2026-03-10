<?php
/**
 * GET /api/admin/reports/dashboard.php
 * Returns high-level KPIs for the admin dashboard.
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

$date = get_date_range();
$from = $date['from'] . ' 00:00:00';
$to   = $date['to']   . ' 23:59:59';

try {
    $pdo = db();

    // ── Members ──────────────────────────────────────────────────────────────
    $stmt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM members GROUP BY status");
    $member_counts = ['total' => 0, 'active' => 0, 'expired' => 0, 'paused' => 0, 'suspended' => 0];
    foreach ($stmt->fetchAll() as $r) {
        $key = $r['status'];
        if (isset($member_counts[$key])) $member_counts[$key] = (int) $r['cnt'];
        $member_counts['total'] += (int) $r['cnt'];
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$from, $to]);
    $member_counts['new_this_period'] = (int) $stmt->fetchColumn();

    // ── Revenue ───────────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN status='completed' AND amount > 0 THEN amount ELSE 0 END), 0) AS gross,
            COALESCE(SUM(CASE WHEN status='refunded' THEN amount ELSE 0 END), 0) AS refunds
        FROM payments WHERE created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$from, $to]);
    $rev = $stmt->fetch();
    $rev['gross']   = (float) $rev['gross'];
    $rev['refunds'] = (float) $rev['refunds'];
    $rev['net']     = $rev['gross'] - $rev['refunds'];

    $stmt = $pdo->prepare("
        SELECT type, COALESCE(SUM(amount), 0) AS total
        FROM payments
        WHERE created_at BETWEEN ? AND ? AND status='completed' AND amount > 0
        GROUP BY type
    ");
    $stmt->execute([$from, $to]);
    $rev['by_type'] = $stmt->fetchAll();

    // ── Bookings ──────────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) AS cnt FROM class_bookings
        WHERE created_at BETWEEN ? AND ? GROUP BY status
    ");
    $stmt->execute([$from, $to]);
    $cb = ['class_total' => 0, 'class_confirmed' => 0, 'class_cancelled' => 0];
    foreach ($stmt->fetchAll() as $r) {
        $cb['class_total'] += (int) $r['cnt'];
        if ($r['status'] === 'confirmed') $cb['class_confirmed'] = (int) $r['cnt'];
        if ($r['status'] === 'cancelled') $cb['class_cancelled'] = (int) $r['cnt'];
    }

    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) AS cnt FROM trainer_bookings
        WHERE created_at BETWEEN ? AND ? GROUP BY status
    ");
    $stmt->execute([$from, $to]);
    $tb = ['trainer_total' => 0, 'trainer_confirmed' => 0, 'trainer_cancelled' => 0];
    foreach ($stmt->fetchAll() as $r) {
        $tb['trainer_total'] += (int) $r['cnt'];
        if ($r['status'] === 'confirmed') $tb['trainer_confirmed'] = (int) $r['cnt'];
        if ($r['status'] === 'cancelled') $tb['trainer_cancelled'] = (int) $r['cnt'];
    }
    $bookings = array_merge($cb, $tb);

    // ── Classes ───────────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) AS cnt FROM class_schedules
        WHERE scheduled_at BETWEEN ? AND ? GROUP BY status
    ");
    $stmt->execute([$from, $to]);
    $classes = ['scheduled' => 0, 'completed' => 0, 'cancelled' => 0];
    foreach ($stmt->fetchAll() as $r) {
        $key = $r['status'];
        if (isset($classes[$key])) $classes[$key] = (int) $r['cnt'];
        $classes['scheduled'] += (int) $r['cnt'];
    }

    // ── Events ────────────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE() AND status='active'");
    $stmt->execute();
    $upcoming_events = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE registered_at BETWEEN ? AND ? AND status='registered'");
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
    $top_classes = $stmt->fetchAll();

    // ── Top Trainers ──────────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT CONCAT(t.first_name,' ',t.last_name) AS trainer_name, COUNT(tb.id) AS sessions
        FROM trainer_bookings tb
        JOIN trainers t ON t.id = tb.trainer_id
        WHERE tb.created_at BETWEEN ? AND ? AND tb.status = 'confirmed'
        GROUP BY trainer_name ORDER BY sessions DESC LIMIT 5
    ");
    $stmt->execute([$from, $to]);
    $top_trainers = $stmt->fetchAll();

    // ── Recent activity ───────────────────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT al.action, al.target_type, al.target_id, al.created_at,
               CONCAT(au.first_name,' ',au.last_name) AS admin_name
        FROM audit_log al
        LEFT JOIN admin_users au ON au.id = al.admin_id
        ORDER BY al.created_at DESC LIMIT 10
    ");
    $stmt->execute();
    $recent_activity = $stmt->fetchAll();

    success('Dashboard data retrieved.', [
        'period'          => $date,
        'members'         => $member_counts,
        'revenue'         => $rev,
        'bookings'        => $bookings,
        'classes'         => $classes,
        'events'          => ['upcoming' => $upcoming_events, 'total_registrations' => $event_registrations],
        'top_classes'     => $top_classes,
        'top_trainers'    => $top_trainers,
        'recent_activity' => $recent_activity,
    ]);
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}