<?php
/**
 * GET /api/admin/members/view.php?id=<member_id>
 *
 * Returns full profile of a single member including subscription,
 * booking history, and payment history.
 *
 * Query params:
 *   id   int   required — member ID
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "member": { id, first_name, last_name, email, phone, address, plan,
 *                 status, created_at, profile_photo },
 *     "subscription": { id, plan, billing_cycle, start_date, expiry_date,
 *                       is_paused, paused_at, resume_date, days_remaining },
 *     "class_bookings":   [ { booking_id, class_name, booking_date,
 *                              booking_time, status, created_at } ],
 *     "trainer_bookings": [ { booking_id, trainer_name, session_date,
 *                              session_time, duration_minutes, status } ],
 *     "payments":         [ { id, amount, type, method, reference, status,
 *                              created_at } ]
 *   }
 *
 * DB tables used:
 *   members, subscriptions, class_bookings, class_schedules,
 *   trainer_bookings, trainers, payments
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$member_id = sanitize_int($_GET['id'] ?? 0);
if (!$member_id || $member_id < 1) {
    error('A valid member ID is required.');
}

// ─── TODO: replace stub with real DB query ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Member
    $stmt = $pdo->prepare('SELECT * FROM members WHERE id = ? LIMIT 1');
    $stmt->execute([$member_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$member) error('Member not found.', 404);
    unset($member['password_hash']);

    // Active subscription
    $stmt = $pdo->prepare("
        SELECT *, DATEDIFF(expiry_date, CURDATE()) AS days_remaining
        FROM subscriptions WHERE member_id = ? AND status = 'active' LIMIT 1
    ");
    $stmt->execute([$member_id]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    // Class bookings (latest 20)
    $stmt = $pdo->prepare("
        SELECT cb.id AS booking_id, cs.class_name, cb.booking_date,
               cs.start_time AS booking_time, cb.status, cb.created_at
        FROM class_bookings cb
        JOIN class_schedules cs ON cs.id = cb.class_schedule_id
        WHERE cb.member_id = ?
        ORDER BY cb.booking_date DESC LIMIT 20
    ");
    $stmt->execute([$member_id]);
    $class_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Trainer bookings (latest 20)
    $stmt = $pdo->prepare("
        SELECT tb.id AS booking_id, t.name AS trainer_name,
               tb.session_date, tb.session_time, tb.session_minutes,
               tb.focus_area, tb.status, tb.created_at
        FROM trainer_bookings tb
        JOIN trainers t ON t.id = tb.trainer_id
        WHERE tb.member_id = ?
        ORDER BY tb.session_date DESC LIMIT 20
    ");
    $stmt->execute([$member_id]);
    $trainer_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Payments (latest 30)
    $stmt = $pdo->prepare("
        SELECT id, amount, type, payment_method AS method,
               reference_number AS reference, status, created_at
        FROM payments WHERE member_id = ?
        ORDER BY created_at DESC LIMIT 30
    ");
    $stmt->execute([$member_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    success('Member retrieved.', compact(
        'member','subscription','class_bookings','trainer_bookings','payments'
    ));
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
