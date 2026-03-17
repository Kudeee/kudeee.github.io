<?php
require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../../../../trainer-config.php';
require_method('POST');

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$email    = sanitize_email($body['email']    ?? '');
$password = $body['password']               ?? '';

if (!$email || !$password) error('Email and password are required.');

// Trainers don't have their own login table — they log in via admin_users
// with role matching their trainer record, OR we match by email if we add
// email to trainers table. For now: match admin_users with role='trainer'
// OR directly look up the trainers table by email if one is added.
// Currently trainers table has no email column, so we log in via admin_users
// where the trainer's email is stored and role is 'trainer' or 'staff'.
// This approach: check admin_users for a matching email + password, then
// find the linked trainer record by matching first_name + last_name.

$pdo = db();

// Try admin_users first (trainer portal access via staff/trainer role)
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ? AND status = 'active' LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    error('Invalid email or password.', 401);
}

// Look up matching trainer by name
$stmt2 = $pdo->prepare(
    "SELECT * FROM trainers WHERE first_name = ? AND last_name = ? AND status = 'active' LIMIT 1"
);
$stmt2->execute([$user['first_name'], $user['last_name']]);
$trainer = $stmt2->fetch();

if (!$trainer) {
    error('No trainer profile linked to this account. Please contact admin.', 403);
}

$_SESSION['trainer_id']        = $trainer['id'];
$_SESSION['trainer_name']      = $trainer['first_name'] . ' ' . $trainer['last_name'];
$_SESSION['trainer_specialty'] = $trainer['specialty'];
$_SESSION['trainer_image']     = $trainer['image_url'];

// Also keep admin session keys for compatibility
$_SESSION['admin_id']   = $user['id'];
$_SESSION['admin_role'] = $user['role'];
$_SESSION['admin_name'] = $user['first_name'] . ' ' . $user['last_name'];

success('Login successful.', [
    'trainer_id'        => $trainer['id'],
    'trainer_name'      => $_SESSION['trainer_name'],
    'trainer_specialty' => $trainer['specialty'],
    'image_url'         => $trainer['image_url'],
]);
