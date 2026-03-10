<?php
/**
 * POST /api/payments/process.php
 * Processes a membership renewal, upgrade, downgrade, or billing-cycle change.
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_csrf();

$member = require_member();

$payment_method = sanitize_string($_POST['payment_method'] ?? '');
$target_plan    = sanitize_string($_POST['plan']           ?? '');
$billing        = sanitize_string($_POST['billing']        ?? 'monthly');
$payment_type   = sanitize_string($_POST['type']           ?? 'renew');

$allowed_methods = ['gcash', 'maya', 'gotyme', 'card'];
if (!in_array($payment_method, $allowed_methods, true)) { error('Please select a payment method.'); }

$allowed_billing = ['monthly', 'yearly'];
if (!in_array($billing, $allowed_billing, true)) { error('Invalid billing cycle.'); }

if ($payment_method === 'card') {
    $card_number = preg_replace('/\s/', '', $_POST['card_number'] ?? '');
    $card_expiry = sanitize_string($_POST['card_expiry'] ?? '');
    $card_cvv    = sanitize_string($_POST['card_cvv']    ?? '');
    if (!preg_match('/^\d{15,16}$/', $card_number)) { error('Invalid card number.'); }
    if (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) { error('Invalid expiry date.'); }
    if (!preg_match('/^\d{3,4}$/', $card_cvv)) { error('Invalid CVV.'); }
}

// Plan prices — canonical reference (mirrors subscription-data.js)
$plan_prices = [
    'BASIC PLAN'   => ['monthly' => 499,  'yearly' => 5028],
    'PREMIUM PLAN' => ['monthly' => 899,  'yearly' => 9067],
    'VIP PLAN'     => ['monthly' => 1500, 'yearly' => 15120],
];

if (!$target_plan) { $target_plan = $member['plan'] ?: 'PREMIUM PLAN'; }
if (!isset($plan_prices[$target_plan])) { error('Invalid membership plan.'); }

$amount = $plan_prices[$target_plan][$billing];

try {
    $pdo = db();

    $start_date  = date('Y-m-d');
    $expiry_date = $billing === 'yearly'
        ? date('Y-m-d', strtotime('+1 year'))
        : date('Y-m-d', strtotime('+1 month'));

    $pdo->beginTransaction();

    // Expire all current active subscriptions for this member
    $pdo->prepare("
        UPDATE subscriptions SET status = 'expired'
        WHERE member_id = ? AND status = 'active'
    ")->execute([$member['member_id']]);

    // Insert new subscription
    $pdo->prepare('
        INSERT INTO subscriptions
            (member_id, plan, billing_cycle, price, start_date, expiry_date, status)
        VALUES (?, ?, ?, ?, ?, ?, "active")
    ')->execute([$member['member_id'], $target_plan, $billing, $amount, $start_date, $expiry_date]);

    // Update member plan & billing cycle
    $pdo->prepare('
        UPDATE members SET plan = ?, billing_cycle = ? WHERE id = ?
    ')->execute([$target_plan, $billing, $member['member_id']]);

    // Unique transaction ID
    $txn_id = 'TXN-' . date('Ymd') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

    $pdo->prepare('
        INSERT INTO payments
            (member_id, type, amount, method, transaction_id, status, created_at)
        VALUES (?, "subscription", ?, ?, ?, "completed", NOW())
    ')->execute([$member['member_id'], $amount, $payment_method, $txn_id]);

    $pdo->commit();

    // Update session to reflect new plan
    $_SESSION['member_plan'] = $target_plan;

    success('Payment processed successfully.', ['transaction_id' => $txn_id]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    error('A database error occurred. Please try again.', 500);
}