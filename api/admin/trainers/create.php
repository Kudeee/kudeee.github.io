<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');
require_admin();

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$first     = sanitize_string($body['first_name']   ?? '');
$last      = sanitize_string($body['last_name']    ?? '');
$specialty = sanitize_string($body['specialty']    ?? '');
$bio       = sanitize_string($body['bio']          ?? '');
$rate      = (float)($body['session_rate']         ?? 0);
$status    = sanitize_string($body['status']       ?? 'active');

if (!$first || !$last || !$specialty) error('First name, last name, and specialty are required.');

$stmt = db()->prepare("
    INSERT INTO trainers (first_name, last_name, specialty, bio, session_rate, status, rating, exp_years, created_at)
    VALUES (?,?,?,?,?,'active',5.0,0,NOW())
");
$stmt->execute([$first, $last, $specialty, $bio, $rate]);
success('Trainer added.', ['trainer_id' => db()->lastInsertId()]);
