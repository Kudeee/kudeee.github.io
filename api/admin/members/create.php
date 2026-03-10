<?php
/**
 * POST /api/admin/members/create.php
 * Manually creates a new member (walk-in registration).
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

$first_name     = sanitize_string($_POST['first_name']     ?? '');
$last_name      = sanitize_string($_POST['last_name']      ?? '');
$email          = sanitize_email($_POST['email']           ?? '');
$phone          = sanitize_string($_POST['phone']          ?? '');
$password       = $_POST['password']                       ?? 'Member@1234';
$plan           = sanitize_string($_POST['plan']           ?? '');
$billing_cycle  = sanitize_string($_POST['billing_cycle']  ?? 'monthly');
$payment_method = sanitize_string($_POST['payment_method'] ?? 'cash');
$notes          = sanitize_string($_POST['notes']          ?? '');
$join_date      = sanitize_string($_POST['join_date']      ?? date('Y-m-d'));

$valid_plans   = ['BASIC PLAN', 'PREMIUM PLAN', 'VIP PLAN'];
$valid_billing = ['monthly', 'yearly'];

if (!$first_name) error('First name is required.');
if (!$last_name)  error('Last name is required.');
if (!$email)      error('A valid email address is required.');
if (!$plan || !in_array($plan, $valid_plans, true)) error('Invalid plan selected.');
if (!in_array($billing_cycle, $valid_billing, true)) error('Invalid billing cycle.');

$prices = [
    'BASIC PLAN'   => ['monthly' => 499,  'yearly' => 5028],
    'PREMIUM PLAN' => ['monthly' => 899,  'yearly' => 9067],
    'VIP PLAN'     => ['monthly' => 1500, 'yearly' => 15120],
];
$amount = $prices[$plan][$billing_cycle];

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id FROM members WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) error('This email address is already registered.', 409);

    $password_hash = password_hash($password ?: 'Member@1234', PASSWORD_BCRYPT);
    $expiry_date   = $billing_cycle === 'monthly'
                     ? date('Y-m-d', strtotime($join_date . ' +1 month'))
                     : date('Y-m-d', strtotime($join_date . ' +1 year'));

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO members (first_name, last_name, email, phone, password_hash, plan, billing_cycle, status, join_date, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW())
    ");
    $stmt->execute([$first_name, $last_name, $email, $phone, $password_hash, $plan, $billing_cycle, $join_date]);
    $member_id = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO subscriptions (member_id, plan, billing_cycle, price, start_date, expiry_date, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
    ");
    $stmt->execute([$member_id, $plan, $billing_cycle, $amount, $join_date, $expiry_date]);

    $txn_id = 'TXN-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("
        INSERT INTO payments (member_id, type, amount, method, transaction_id, status, description, created_at)
        VALUES (?, 'subscription', ?, ?, ?, 'completed', ?, NOW())
    ");
    $stmt->execute([$member_id, $amount, $payment_method, $txn_id, "{$plan} ({$billing_cycle}) - Walk-in registration"]);

    $stmt = $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, 'member_created', 'member', ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $admin['admin_id'], $member_id,
        json_encode(['plan' => $plan, 'billing' => $billing_cycle, 'notes' => $notes]),
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    $pdo->commit();
    success('Member created successfully.', ['member_id' => $member_id], 201);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error('Database error: ' . $e->getMessage(), 500);
}