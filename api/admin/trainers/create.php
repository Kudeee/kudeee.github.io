<?php
/**
 * POST /api/admin/trainers/create.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

$first_name   = sanitize_string($_POST['first_name']   ?? '');
$last_name    = sanitize_string($_POST['last_name']    ?? '');
$raw_email    = trim($_POST['email']                   ?? '');
$email        = sanitize_email($raw_email);
$phone        = sanitize_string($_POST['phone']        ?? '');
$specialty    = sanitize_string($_POST['specialty']    ?? '');
$session_rate = filter_var($_POST['session_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
$bio          = sanitize_string($_POST['bio']          ?? '');
$status       = sanitize_string($_POST['status']       ?? 'active');

if (!$first_name)                                          error('First name is required.');
if (!$last_name)                                           error('Last name is required.');
if (!$email)                                               error('A valid email is required.');
if (!$specialty)                                           error('Specialty is required.');
if ($session_rate === false || $session_rate < 0)          error('Session rate must be a positive number.');
if (!in_array($status, ['active', 'inactive', 'on_leave'], true)) error('Invalid status.');

try {
    $pdo = db();

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO trainers (first_name, last_name, specialty, bio, session_rate, rating, availability, status, created_at)
        VALUES (?, ?, ?, ?, ?, 0.0, 'available', ?, NOW())
    ");
    $stmt->execute([$first_name, $last_name, $specialty, $bio, $session_rate, $status]);
    $trainer_id = (int) $pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, 'trainer_added', 'trainer', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $trainer_id, json_encode(['name' => "$first_name $last_name", 'specialty' => $specialty]), $_SERVER['REMOTE_ADDR'] ?? '']);

    $pdo->commit();
    success('Trainer created successfully.', ['trainer_id' => $trainer_id], 201);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error('Database error: ' . $e->getMessage(), 500);
}