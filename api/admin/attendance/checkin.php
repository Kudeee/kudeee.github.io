<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('POST');
require_admin();

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$member_id = sanitize_int($body['member_id'] ?? 0);
$notes     = sanitize_string($body['notes'] ?? '');

if (!$member_id) error('member_id is required.');

// Verify member exists and has active membership
$stmt = db()->prepare("
    SELECT m.id FROM members m
    JOIN memberships ms ON ms.member_id = m.id AND ms.status = 'active'
    WHERE m.id = ?
    LIMIT 1
");
$stmt->execute([$member_id]);
if (!$stmt->fetch()) error('Member not found or has no active membership.');

// Check if already checked in today (no check-out yet)
$stmt = db()->prepare("
    SELECT id FROM attendance
    WHERE member_id = ? AND DATE(check_in) = CURDATE() AND check_out IS NULL
    LIMIT 1
");
$stmt->execute([$member_id]);
if ($stmt->fetch()) error('Member is already checked in. Please check out first.');

$stmt = db()->prepare("
    INSERT INTO attendance (member_id, check_in, notes, created_at)
    VALUES (?, NOW(), ?, NOW())
");
$stmt->execute([$member_id, $notes]);

success('Check-in recorded.', ['attendance_id' => db()->lastInsertId()]);
