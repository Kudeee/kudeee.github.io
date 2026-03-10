<?php
require_once __DIR__ . '/../config.php';
require_method('POST');

$member = require_member();
$pdo    = db();

$payment_method = sanitize_string($_POST['payment_method'] ?? '');
$type           = sanitize_string($_POST['type']           ?? ''); // renew|upgrade|change|billing-change
$new_plan       = sanitize_string($_POST['plan']           ?? '');
$billing_cycle  = sanitize_string($_POST['billing']        ?? 'monthly');
$amount         = (float)($_POST['amount']                 ?? 0);

if (!$payment_method) {
    error('Please select a payment method.');
}

// Plan prices
$price_map = [
    'BASIC PLAN'   => ['monthly' => 499,  'yearly' => 5028],
    'PREMIUM PLAN' => ['monthly' => 899,  'yearly' => 9067],
    'VIP PLAN'     => ['monthly' => 1500, 'yearly' => 15120],
];

// Determine plan + price based on type
if ($type === 'renew') {
    // Renew current plan
    $stmt = $pdo->prepare("SELECT plan, billing_cycle FROM members WHERE id = ? LIMIT 1");
    $stmt->execute([$member['id']]);
    $row = $stmt->fetch();
    $new_plan      = $row['plan'];
    $billing_cycle = $row['billing_cycle'];
    $amount        = $price_map[$new_plan][$billing_cycle] ?? 0;

} elseif ($type === 'upgrade') {
    $new_plan      = 'VIP PLAN';
    $billing_cycle = 'monthly';
    $amount        = $price_map[$new_plan][$billing_cycle];

} elseif (in_array($type, ['change', 'billing-change'])) {
    $allowed_plans = ['BASIC PLAN', 'PREMIUM PLAN', 'VIP PLAN'];
    if (!in_array($new_plan, $allowed_plans)) {
        error('Invalid plan selected.');
    }
    if (!in_array($billing_cycle, ['monthly', 'yearly'])) {
        $billing_cycle = 'monthly';
    }
    if ($amount <= 0) {
        $amount = $price_map[$new_plan][$billing_cycle] ?? 0;
    }
} else {
    error('Invalid payment type.');
}

if ($amount <= 0) {
    error('Invalid payment amount.');
}

// Expire old subscription
$pdo->prepare("UPDATE subscriptions SET status = 'expired' WHERE member_id = ? AND status = 'active'")
    ->execute([$member['id']]);

// New subscription dates
$start_date  = date('Y-m-d');
$expiry_date = ($billing_cycle === 'yearly')
    ? date('Y-m-d', strtotime('+1 year'))
    : date('Y-m-d', strtotime('+1 month'));

// Insert new subscription
$stmt = $pdo->prepare("
    INSERT INTO subscriptions (member_id, plan, billing_cycle, price, start_date, expiry_date, status)
    VALUES (?, ?, ?, ?, ?, ?, 'active')
");
$stmt->execute([$member['id'], $new_plan, $billing_cycle, $amount, $start_date, $expiry_date]);

// Update member plan + billing_cycle
$stmt = $pdo->prepare("UPDATE members SET plan = ?, billing_cycle = ? WHERE id = ?");
$stmt->execute([$new_plan, $billing_cycle, $member['id']]);

// Update session
$_SESSION['member_plan'] = $new_plan;

// Record payment
$txn_id = 'TXN-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
$desc   = ucfirst($type) . ": $new_plan ($billing_cycle)";
$stmt   = $pdo->prepare("
    INSERT INTO payments (member_id, type, amount, method, transaction_id, status, description)
    VALUES (?, 'subscription', ?, ?, ?, 'completed', ?)
");
$stmt->execute([$member['id'], $amount, $payment_method, $txn_id, $desc]);

success('Payment processed successfully.', [
    'plan'           => $new_plan,
    'billing_cycle'  => $billing_cycle,
    'amount'         => $amount,
    'expiry_date'    => $expiry_date,
    'transaction_id' => $txn_id,
]);