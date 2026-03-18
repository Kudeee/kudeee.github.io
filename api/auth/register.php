<?php
require_once __DIR__ . '/../config.php';
require_method('POST');

// Required fields
$first_name    = sanitize_string($_POST['first_name']    ?? '');
$last_name     = sanitize_string($_POST['last_name']     ?? '');
$email         = sanitize_email($_POST['email']          ?? '');
$phone         = sanitize_string($_POST['phone']         ?? '');
$zip           = sanitize_string($_POST['zip']           ?? '');
$password      = trim($_POST['password']                 ?? '');
$confirm_pass  = trim($_POST['confirm_password']         ?? '');
$plan          = sanitize_string($_POST['selected_plan'] ?? 'BASIC PLAN');
$billing_cycle = sanitize_string($_POST['billing_cycle'] ?? 'monthly');
$payment_method= sanitize_string($_POST['payment_method']?? '');
$plan_price    = (float)($_POST['plan_price']            ?? 0);

// is_recurring: checkbox sends value "1" when checked, absent when unchecked
// Also accept the hidden mirror field as fallback
$is_recurring  = isset($_POST['is_recurring']) ? 1 : (int)($_POST['signup_recurring_mirror'] ?? 1);

// Validations
if (!$first_name || !$last_name || !$email || !$password || !$phone) {
    error('All required fields must be filled out.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error('Please enter a valid email address.');
}

if (strlen($password) < 8) {
    error('Password must be at least 8 characters.');
}

if ($password !== $confirm_pass) {
    error('Passwords do not match.');
}

$allowed_plans = ['BASIC PLAN', 'PREMIUM PLAN', 'VIP PLAN'];
if (!in_array($plan, $allowed_plans)) {
    $plan = 'BASIC PLAN';
}

$pdo = db();

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM members WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    error('An account with this email already exists.');
}

// Ensure is_recurring column exists on subscriptions
try {
    $pdo->exec("ALTER TABLE subscriptions ADD COLUMN IF NOT EXISTS is_recurring TINYINT(1) NOT NULL DEFAULT 1");
} catch (\Throwable $e) {}

$password_hash = password_hash($password, PASSWORD_BCRYPT);
$join_date     = date('Y-m-d');

// Insert member
$stmt = $pdo->prepare("
    INSERT INTO members (first_name, last_name, email, phone, zip, password_hash, plan, billing_cycle, status, join_date)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
");
$stmt->execute([$first_name, $last_name, $email, $phone, $zip, $password_hash, $plan, $billing_cycle, $join_date]);
$member_id = (int)$pdo->lastInsertId();

// Calculate subscription dates
$start_date  = $join_date;
$expiry_date = ($billing_cycle === 'yearly')
    ? date('Y-m-d', strtotime('+1 year'))
    : date('Y-m-d', strtotime('+1 month'));

// Plan prices map (fallback if not provided)
$price_map = [
    'BASIC PLAN'   => ['monthly' => 499,   'yearly' => 5028],
    'PREMIUM PLAN' => ['monthly' => 899,   'yearly' => 9067],
    'VIP PLAN'     => ['monthly' => 1500,  'yearly' => 15120],
];
if ($plan_price <= 0) {
    $plan_price = $price_map[$plan][$billing_cycle] ?? 499;
}

// Insert subscription (with is_recurring)
$stmt = $pdo->prepare("
    INSERT INTO subscriptions (member_id, plan, billing_cycle, price, start_date, expiry_date, status, is_recurring)
    VALUES (?, ?, ?, ?, ?, ?, 'active', ?)
");
$stmt->execute([$member_id, $plan, $billing_cycle, $plan_price, $start_date, $expiry_date, $is_recurring]);

// Record payment
if ($payment_method) {
    $txn_id = 'TXN-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    $desc   = "$plan membership ($billing_cycle)" . ($is_recurring ? ' — auto-renew enabled' : '');
    $stmt   = $pdo->prepare("
        INSERT INTO payments (member_id, type, amount, method, transaction_id, status, description)
        VALUES (?, 'subscription', ?, ?, ?, 'completed', ?)
    ");
    $stmt->execute([$member_id, $plan_price, $payment_method, $txn_id, $desc]);
}

// Log in automatically
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ? LIMIT 1");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

$_SESSION['member_id']    = $member['id'];
$_SESSION['member_email'] = $member['email'];
$_SESSION['member_plan']  = $member['plan'];
$_SESSION['member_name']  = $member['first_name'] . ' ' . $member['last_name'];

success('Account created successfully.', ['member_id' => $member_id]);