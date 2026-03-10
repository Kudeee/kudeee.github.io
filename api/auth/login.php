<?php
/**
 * POST /api/auth/login.php
 * Authenticates a member or admin.
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_csrf();

$email    = sanitize_email($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$email)               { error('Please enter a valid email address.'); }
if (strlen($password) < 1) { error('Please enter your password.'); }

try {
    $pdo = db();

    // 1. Check admin_users first
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE email = ? AND status = "active" LIMIT 1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['member_id']    = $admin['id'];
        $_SESSION['member_name']  = $admin['first_name'] . ' ' . $admin['last_name'];
        $_SESSION['member_email'] = $admin['email'];
        $_SESSION['member_plan']  = null;
        $_SESSION['role']         = $admin['role'];
        success('Login successful.', ['role' => $admin['role']]);
    }

    // 2. Check members table
    $stmt = $pdo->prepare('SELECT * FROM members WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $member = $stmt->fetch();

    if (!$member || !password_verify($password, $member['password_hash'])) {
        error('Invalid email or password.', 401);
    }

    if ($member['status'] === 'suspended') {
        error('Your account has been suspended. Please contact support.', 403);
    }

    session_regenerate_id(true);
    $_SESSION['member_id']    = $member['id'];
    $_SESSION['member_name']  = $member['first_name'] . ' ' . $member['last_name'];
    $_SESSION['member_email'] = $member['email'];
    $_SESSION['member_plan']  = $member['plan'];
    $_SESSION['role']         = 'member';

    success('Login successful.', ['role' => 'member']);

} catch (PDOException $e) {
    error('A database error occurred. Please try again.', 500);
}