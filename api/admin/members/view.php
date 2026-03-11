<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('GET');
require_admin();

$id = sanitize_int($_GET['id'] ?? 0);
if (!$id) error('Member ID is required.');

// Member details
$stmt = db()->prepare("
    SELECT m.*, mp.name AS plan_name, mp.price AS plan_price,
           ms.start_date, ms.end_date, ms.status AS membership_status
    FROM members m
    LEFT JOIN memberships ms ON ms.member_id = m.id AND ms.status = 'active'
    LEFT JOIN membership_plans mp ON mp.id = ms.plan_id
    WHERE m.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) error('Member not found.', 404);

// Recent attendance (last 10)
$stmt = db()->prepare("
    SELECT a.check_in, a.check_out, a.notes
    FROM attendance a
    WHERE a.member_id = ?
    ORDER BY a.check_in DESC
    LIMIT 10
");
$stmt->execute([$id]);
$attendance = $stmt->fetchAll();

// Payment history (last 10)
$stmt = db()->prepare("
    SELECT p.id, p.amount, p.method, p.status, p.created_at, mp.name AS plan_name
    FROM payments p
    LEFT JOIN membership_plans mp ON mp.id = p.plan_id
    WHERE p.member_id = ?
    ORDER BY p.created_at DESC
    LIMIT 10
");
$stmt->execute([$id]);
$payments = $stmt->fetchAll();

success('Member retrieved.', [
    'member'     => $member,
    'attendance' => $attendance,
    'payments'   => $payments,
]);
