<?php
/**
 * POST /api/auth/login.php
 *
 * Authenticates a member or admin.
 *
 * Request (POST, form-data):
 *   csrf_token  string  required
 *   email       string  required
 *   password    string  required
 *
 * Response 200:
 *   { "success": true, "message": "Login successful.", "role": "member"|"admin" }
 *
 * Response 400 / 401:
 *   { "success": false, "message": "..." }
 *
 * DB tables used (when connected):
 *   members   (id, email, password_hash, first_name, last_name, status, plan)
 *   admin_users (id, email, password_hash, first_name, last_name, role, status)
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_csrf();

// ─── Input validation ─────────────────────────────────────────────────────────

$email    = sanitize_email($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$email) {
    error('Please enter a valid email address.');
}
if (strlen($password) < 1) {
    error('Please enter your password.');
}

// ─── TODO: replace stub with real DB lookup ───────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 1. Check admin_users first
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE email = ? AND status = "active" LIMIT 1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password_hash'])) {
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
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member || !password_verify($password, $member['password_hash'])) {
        error('Invalid email or password.', 401);
    }

    if ($member['status'] === 'suspended') {
        error('Your account has been suspended. Please contact support.', 403);
    }

    $_SESSION['member_id']    = $member['id'];
    $_SESSION['member_name']  = $member['first_name'] . ' ' . $member['last_name'];
    $_SESSION['member_email'] = $member['email'];
    $_SESSION['member_plan']  = $member['plan'];
    $_SESSION['role']         = 'member';

    success('Login successful.', ['role' => 'member']);
*/

// ─── STUB response (remove when DB is connected) ──────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
