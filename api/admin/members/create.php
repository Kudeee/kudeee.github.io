<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');
require_admin();

$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$first_name = sanitize_string($body['first_name'] ?? '');
$last_name  = sanitize_string($body['last_name']  ?? '');
$email      = sanitize_email($body['email']        ?? '');
$phone      = sanitize_string($body['phone']       ?? '');
$plan       = sanitize_string($body['plan']        ?? 'BASIC PLAN');
$billing    = sanitize_string($body['billing_cycle'] ?? 'monthly');
$join_date  = sanitize_string($body['join_date']   ?? date('Y-m-d'));

if (!$first_name || !$last_name || !$email) error('First name, last name, and email are required.');

// Check email unique
$check = $pdo = db();
$dup = $pdo->prepare("SELECT id FROM members WHERE email = ?");
$dup->execute([$email]);
if ($dup->fetch()) error('Email already exists.');

$password_hash = password_hash('Member@1234', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO members (first_name, last_name, email, phone, plan, billing_cycle, join_date, password_hash, status, created_at)
    VALUES (?,?,?,?,?,?,?, ?, 'active', NOW())
");
$stmt->execute([$first_name, $last_name, $email, $phone, $plan, $billing, $join_date, $password_hash]);
$member_id = (int)$pdo->lastInsertId();

// Create subscription
$prices = ['BASIC PLAN' => 499, 'PREMIUM PLAN' => 899, 'VIP PLAN' => 1499];
$price = $prices[$plan] ?? 499;
if ($billing === 'yearly') { $price = $price * 12 * 0.9; }
$expiry = $billing === 'yearly' ? date('Y-m-d', strtotime('+1 year')) : date('Y-m-d', strtotime('+1 month'));

$sub = $pdo->prepare("INSERT INTO subscriptions (member_id, plan, billing_cycle, price, start_date, expiry_date, status, created_at) VALUES (?,?,?,?,CURDATE(),?,'active',NOW())");
$sub->execute([$member_id, $plan, $billing, $price, $expiry]);

success('Member added successfully.', ['member_id' => $member_id]);