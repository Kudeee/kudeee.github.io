<?php
require_once __DIR__ . '/../../config.php';
require_method('POST');

$email    = sanitize_email($_POST['email'] ?? '');
$password = trim($_POST['password']        ?? '');

if (!$email || !$password) {
    error('Email and password are required.');
}

$pdo = db();

// ── Check admin_users first ───────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ? AND status = 'active' LIMIT 1");
$stmt->execute([$email]);
$admin = $stmt->fetch();

if ($admin && password_verify($password, $admin['password_hash'])) {
    $_SESSION['admin_id']   = $admin['id'];
    $_SESSION['admin_email']= $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];

    // Check if this admin user is also a trainer (case-insensitive, trimmed match)
    $trainerStmt = $pdo->prepare(
        "SELECT id, specialty, image_url FROM trainers
         WHERE LOWER(TRIM(first_name)) = LOWER(TRIM(?))
         AND LOWER(TRIM(last_name)) = LOWER(TRIM(?))
         AND status = 'active'
         LIMIT 1"
    );
    $trainerStmt->execute([$admin['first_name'], $admin['last_name']]);
    $trainer = $trainerStmt->fetch();

    if ($trainer) {
        $_SESSION['trainer_id']        = $trainer['id'];
        $_SESSION['trainer_name']      = $admin['first_name'] . ' ' . $admin['last_name'];
        $_SESSION['trainer_specialty'] = $trainer['specialty'];
        $_SESSION['trainer_image']     = $trainer['image_url'];

        success('Login successful.', [
            'role'       => 'trainer',
            'is_trainer' => true,
        ]);
    }

    success('Login successful.', [
        'role'       => $admin['role'],
        'is_trainer' => false,
    ]);
}

// ── Check member accounts ─────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM members WHERE email = ? AND status = 'active' LIMIT 1");
$stmt->execute([$email]);
$member = $stmt->fetch();

if (!$member || !password_verify($password, $member['password_hash'])) {
    error('Invalid email or password.', 401);
}

$_SESSION['member_id']    = $member['id'];
$_SESSION['member_email'] = $member['email'];
$_SESSION['member_plan']  = $member['plan'];
$_SESSION['member_name']  = $member['first_name'] . ' ' . $member['last_name'];

success('Login successful.', ['role' => 'member', 'is_trainer' => false]);