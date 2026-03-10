<?php
/**
 * POST /api/payments/process.php
 *
 * Processes a membership renewal, upgrade, downgrade, or billing-cycle change
 * for the authenticated member.
 *
 * This endpoint is called from payment.html via payment-methods.js.
 *
 * Request (POST, form-data):
 *   csrf_token      string   required
 *   payment_method  string   required  ("gcash"|"maya"|"gotyme"|"card")
 *   plan            string   optional  target plan name (for change/upgrade)
 *   billing         string   optional  "monthly" | "yearly"
 *   type            string   optional  URL param echoed back: "renew"|"upgrade"|"change"|"billing-change"
 *   card_number     string   optional
 *   card_expiry     string   optional
 *   card_cvv        string   optional
 *
 * Response 200:
 *   { "success": true, "message": "Payment processed.", "transaction_id": "TXN-..." }
 *
 * DB tables used (when connected):
 *   payments      (id, member_id, type, amount, method, status, created_at)
 *   subscriptions (id, member_id, plan, billing_cycle, price, start_date,
 *                  expiry_date, status)
 *   members       (id, plan, billing_cycle)
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_csrf();

$member = require_member();

// ─── Input ────────────────────────────────────────────────────────────────────

$payment_method = sanitize_string($_POST['payment_method'] ?? '');
$target_plan    = sanitize_string($_POST['plan']           ?? '');
$billing        = sanitize_string($_POST['billing']        ?? 'monthly');
$payment_type   = sanitize_string($_POST['type']           ?? 'renew');

// ─── Validation ───────────────────────────────────────────────────────────────

$allowed_methods = ['gcash', 'maya', 'gotyme', 'card'];
if (!in_array($payment_method, $allowed_methods, true)) {
    error('Please select a payment method.');
}

$allowed_billing = ['monthly', 'yearly'];
if (!in_array($billing, $allowed_billing, true)) {
    error('Invalid billing cycle.');
}

if ($payment_method === 'card') {
    $card_number = preg_replace('/\s/', '', $_POST['card_number'] ?? '');
    $card_expiry = sanitize_string($_POST['card_expiry'] ?? '');
    $card_cvv    = sanitize_string($_POST['card_cvv']    ?? '');

    if (!preg_match('/^\d{15,16}$/', $card_number)) { error('Invalid card number.'); }
    if (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) { error('Invalid expiry date.'); }
    if (!preg_match('/^\d{3,4}$/', $card_cvv)) { error('Invalid CVV.'); }
}

// ─── Resolve plan & price ─────────────────────────────────────────────────────

// Prices mirror subscription-data.js
$plan_prices = [
    'BASIC PLAN'   => ['monthly' => 499,  'yearly' => 5028],
    'PREMIUM PLAN' => ['monthly' => 899,  'yearly' => 9067],
    'VIP PLAN'     => ['monthly' => 1500, 'yearly' => 15120],
];

// If no target plan supplied, renew the member's current plan
if (!$target_plan) {
    $target_plan = $member['plan'] ?: 'PREMIUM PLAN';
}

if (!isset($plan_prices[$target_plan])) {
    error('Invalid membership plan.');
}

$amount = $plan_prices[$target_plan][$billing];

// ─── TODO: replace stub with real DB logic ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $start_date  = date('Y-m-d');
    $expiry_date = $billing === 'yearly'
        ? date('Y-m-d', strtotime('+1 year'))
        : date('Y-m-d', strtotime('+1 month'));

    $pdo->beginTransaction();

    // Expire the old active subscription
    $pdo->prepare('
        UPDATE subscriptions
        SET status = "expired"
        WHERE member_id = ? AND status = "active"
    ')->execute([$member['member_id']]);

    // Insert new subscription
    $pdo->prepare('
        INSERT INTO subscriptions
            (member_id, plan, billing_cycle, price, start_date, expiry_date, status)
        VALUES (?, ?, ?, ?, ?, ?, "active")
    ')->execute([$member['member_id'], $target_plan, $billing, $amount, $start_date, $expiry_date]);

    // Update member's plan & billing fields
    $pdo->prepare('
        UPDATE members SET plan = ?, billing_cycle = ? WHERE id = ?
    ')->execute([$target_plan, $billing, $member['member_id']]);

    // Generate unique transaction ID
    $txn_id = 'TXN-' . date('Ymd') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

    // Record payment
    $pdo->prepare('
        INSERT INTO payments
            (member_id, type, amount, method, transaction_id, status, created_at)
        VALUES (?, "subscription", ?, ?, ?, "completed", NOW())
    ')->execute([$member['member_id'], $amount, $payment_method, $txn_id]);

    $pdo->commit();

    // Update session to reflect new plan
    $_SESSION['member_plan'] = $target_plan;

    // TODO: send payment confirmation email

    success('Payment processed successfully.', ['transaction_id' => $txn_id]);
*/

// ─── STUB response ────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
