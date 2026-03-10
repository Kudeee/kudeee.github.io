<?php
/**
 * GET /api/admin/members/view.php?id=<member_id>
 * Returns full profile of a single member.
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

$member_id = sanitize_int($_GET['id'] ?? 0);
if (!$member_id || $member_id < 1) error('A valid member ID is required.');

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT * FROM members WHERE id = ? LIMIT 1');
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    if (!$member) error('Member not found.', 404);
    unset($member['password_hash']);

    // Active subscription
    $stmt = $pdo->prepare("
        SELECT *, DATEDIFF(expiry_date, CURDATE()) AS days_remaining
        FROM subscriptions WHERE member_id = ? AND status = 'active'
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$member_id]);
    $subscription = $stmt->fetch() ?: null;

    // Class bookings
    $stmt = $pdo->prepare("
        SELECT cb.id AS booking_id, cs.class_name, cb.booking_date,
               cb.booking_time, cb.status, cb.created_at
        FROM class_bookings cb
        JOIN class_schedules cs ON cs.id = cb.class_schedule_id
        WHERE cb.member_id = ?
        ORDER BY cb.booking_date DESC LIMIT 20
    ");
    $stmt->execute([$member_id]);
    $class_bookings = $stmt->fetchAll();

    // Trainer bookings
    $stmt = $pdo->prepare("
        SELECT tb.id AS booking_id,
               CONCAT(t.first_name,' ',t.last_name) AS trainer_name,
               tb.booking_date, tb.booking_time, tb.session_duration,
               tb.focus_area, tb.total_price, tb.status, tb.created_at
        FROM trainer_bookings tb
        JOIN trainers t ON t.id = tb.trainer_id
        WHERE tb.member_id = ?
        ORDER BY tb.booking_date DESC LIMIT 20
    ");
    $stmt->execute([$member_id]);
    $trainer_bookings = $stmt->fetchAll();

    // Payments
    $stmt = $pdo->prepare("
        SELECT id, amount, type, method, transaction_id, status, description, created_at
        FROM payments WHERE member_id = ?
        ORDER BY created_at DESC LIMIT 30
    ");
    $stmt->execute([$member_id]);
    $payments = $stmt->fetchAll();

    success('Member retrieved.', compact('member', 'subscription', 'class_bookings', 'trainer_bookings', 'payments'));
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}