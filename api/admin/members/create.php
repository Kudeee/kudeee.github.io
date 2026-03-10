<?php
/**
 * POST /api/admin/members/create.php
 *
 * Manually creates a new member account (walk-in / cash registrations).
 *
 * Request (POST, form-data):
 *   csrf_token      string   required
 *   first_name      string   required
 *   last_name       string   required
 *   email           email    required, unique
 *   phone           string   required, PH format 09XXXXXXXXX
 *   password        string   required, min 8 chars
 *   plan            string   required: BASIC PLAN | PREMIUM PLAN | VIP PLAN
 *   billing_cycle   string   required: monthly | annually
 *   payment_method  string   required: cash | card | gcash
 *   notes           string   optional — admin notes
 *
 * Response 201:
 *   { "success": true, "message": "Member created.", "member_id": <int> }
 *
 * DB tables used:
 *   members, subscriptions, payments
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$first_name     = sanitize_string($_POST['first_name']     ?? '');
$last_name      = sanitize_string($_POST['last_name']      ?? '');
$email          = sanitize_email($_POST['email']           ?? '');
$phone          = sanitize_string($_POST['phone']          ?? '');
$password       = $_POST['password']                       ?? '';
$plan           = sanitize_string($_POST['plan']           ?? '');
$billing_cycle  = sanitize_string($_POST['billing_cycle']  ?? '');
$payment_method = sanitize_string($_POST['payment_method'] ?? '');
$notes          = sanitize_string($_POST['notes']          ?? '');

$valid_plans    = ['BASIC PLAN', 'PREMIUM PLAN', 'VIP PLAN'];
$valid_billing  = ['monthly', 'annually'];
$valid_payments = ['cash', 'card', 'gcash'];

if (!$first_name) error('First name is required.');
if (!$last_name)  error('Last name is required.');
if (!$email)      error('A valid email address is required.');
if (!preg_match('/^09\d{9}$/', $phone)) error('Phone must be in the format 09XXXXXXXXX.');
if (strlen($password) < 8) error('Password must be at least 8 characters.');
if (!in_array($plan, $valid_plans, true)) error('Invalid plan selected.');
if (!in_array($billing_cycle, $valid_billing, true)) error('Invalid billing cycle.');
if (!in_array($payment_method, $valid_payments, true)) error('Invalid payment method.');

// ─── Plan prices ─────────────────────────────────────────────────────────────
$prices = [
    'BASIC PLAN'   => ['monthly' => 499,  'annually' => 5028],
    'PREMIUM PLAN' => ['monthly' => 899,  'annually' => 9067],
    'VIP PLAN'     => ['monthly' => 1500, 'annually' => 15120],
];
$amount = $prices[$plan][$billing_cycle];

// ─── TODO: replace stub with real DB insert ───────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Unique email check
    $stmt = $pdo->prepare('SELECT id FROM members WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) error('This email address is already registered.', 409);

    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $start_date    = date('Y-m-d');
    $expiry_date   = $billing_cycle === 'monthly'
                     ? date('Y-m-d', strtotime('+1 month'))
                     : date('Y-m-d', strtotime('+1 year'));

    $pdo->beginTransaction();
    try {
        // Insert member
        $stmt = $pdo->prepare("
            INSERT INTO members
                (first_name, last_name, email, phone, password_hash, plan,
                 status, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'active', ?, NOW())
        ");
        $stmt->execute([$first_name, $last_name, $email, $phone,
                         $password_hash, $plan, $notes]);
        $member_id = (int) $pdo->lastInsertId();

        // Insert subscription
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions
                (member_id, plan, billing_cycle, start_date, expiry_date,
                 status, created_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW())
        ");
        $stmt->execute([$member_id, $plan, $billing_cycle,
                         $start_date, $expiry_date]);

        // Insert payment record
        $ref = 'TXN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $stmt = $pdo->prepare("
            INSERT INTO payments
                (member_id, amount, type, payment_method,
                 reference_number, status, created_at)
            VALUES (?, ?, 'membership', ?, ?, 'completed', NOW())
        ");
        $stmt->execute([$member_id, $amount, $payment_method, $ref]);

        // Log admin action
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_type, target_id, created_at)
            VALUES (?, 'create_member', 'member', ?, NOW())
        ");
        $stmt->execute([$admin['admin_id'], $member_id]);

        $pdo->commit();
        success('Member created successfully.', ['member_id' => $member_id], 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        error('Failed to create member. Please try again.', 500);
    }
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
