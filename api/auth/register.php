<?php
/**
 * POST /api/auth/register.php
 * Registers a new gym member and starts their session.
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_csrf();

// ─── Input ────────────────────────────────────────────────────────────────────
$first_name     = sanitize_string($_POST['first_name']     ?? '');
$last_name      = sanitize_string($_POST['last_name']      ?? '');
$email          = sanitize_email($_POST['email']           ?? '');
$phone          = sanitize_string($_POST['phone']          ?? '');
$zip            = sanitize_string($_POST['zip']            ?? '');
$password       = $_POST['password']                       ?? '';
$confirm        = $_POST['confirm_password']               ?? '';
$plan           = sanitize_string($_POST['selected_plan']  ?? '');
$billing_cycle  = sanitize_string($_POST['billing_cycle']  ?? 'monthly');
$plan_price     = (float)($_POST['plan_price']             ?? 0);
$payment_method = sanitize_string($_POST['payment_method'] ?? '');
$agree_terms    = $_POST['agree_terms']                    ?? '';
$discount_code  = sanitize_string($_POST['discount_code']  ?? '');

// ─── Validation ───────────────────────────────────────────────────────────────
if (!$first_name) { error('First name is required.'); }
if (!$last_name)  { error('Last name is required.'); }
if (!$email)      { error('Please enter a valid email address.'); }

$phone_clean = preg_replace('/[\s\-]/', '', $phone);
if (!preg_match('/^(09|\+639)\d{9}$/', $phone_clean)) {
    error('Please enter a valid Philippine phone number (e.g., 09091234567).');
}

if (strlen($password) < 8
    || !preg_match('/[A-Z]/', $password)
    || !preg_match('/[a-z]/', $password)
    || !preg_match('/[0-9]/', $password)) {
    error('Password must be at least 8 characters with uppercase, lowercase, and a number.');
}

if (!hash_equals($password, $confirm)) { error('Passwords do not match.'); }

$allowed_plans = ['BASIC PLAN', 'PREMIUM PLAN', 'VIP PLAN'];
if (!in_array($plan, $allowed_plans, true)) { error('Please select a valid membership plan.'); }

$allowed_billing = ['monthly', 'yearly'];
if (!in_array($billing_cycle, $allowed_billing, true)) { error('Invalid billing cycle.'); }

if ($plan_price <= 0) { error('Invalid plan price.'); }

$allowed_methods = ['gcash', 'maya', 'gotyme', 'card'];
if (!in_array($payment_method, $allowed_methods, true)) { error('Please select a payment method.'); }

if ($payment_method === 'card') {
    $card_number = preg_replace('/\s/', '', $_POST['card_number'] ?? '');
    $card_expiry = sanitize_string($_POST['card_expiry'] ?? '');
    $card_cvv    = sanitize_string($_POST['card_cvv']    ?? '');
    if (!preg_match('/^\d{15,16}$/', $card_number)) { error('Please enter a valid card number.'); }
    if (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) { error('Please enter a valid expiry date (MM/YY).'); }
    if (!preg_match('/^\d{3,4}$/', $card_cvv)) { error('Please enter a valid CVV.'); }
}

if ($agree_terms !== '1') { error('You must agree to the Terms and Conditions.'); }

// ─── DB logic ─────────────────────────────────────────────────────────────────
try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id FROM members WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) { error('An account with this email already exists.'); }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $start_date    = date('Y-m-d');
    $expiry_date   = $billing_cycle === 'yearly'
        ? date('Y-m-d', strtotime('+1 year'))
        : date('Y-m-d', strtotime('+1 month'));

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        INSERT INTO members
            (first_name, last_name, email, phone, zip, password_hash, plan, billing_cycle, status, join_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, "active", ?)
    ');
    $stmt->execute([$first_name, $last_name, $email, $phone_clean, $zip,
                    $password_hash, $plan, $billing_cycle, $start_date]);
    $member_id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('
        INSERT INTO subscriptions
            (member_id, plan, billing_cycle, price, start_date, expiry_date, status)
        VALUES (?, ?, ?, ?, ?, ?, "active")
    ');
    $stmt->execute([$member_id, $plan, $billing_cycle, $plan_price, $start_date, $expiry_date]);

    $stmt = $pdo->prepare('
        INSERT INTO payments
            (member_id, type, amount, method, status, created_at)
        VALUES (?, "subscription", ?, ?, "completed", NOW())
    ');
    $stmt->execute([$member_id, $plan_price, $payment_method]);

    $pdo->commit();

    session_regenerate_id(true);
    $_SESSION['member_id']    = $member_id;
    $_SESSION['member_name']  = $first_name . ' ' . $last_name;
    $_SESSION['member_email'] = $email;
    $_SESSION['member_plan']  = $plan;
    $_SESSION['role']         = 'member';

    success('Account created successfully.');

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    error('A database error occurred. Please try again.', 500);
}