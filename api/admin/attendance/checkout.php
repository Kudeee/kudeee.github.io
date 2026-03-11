<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('POST');
require_admin();

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$member_id = sanitize_int($body['member_id'] ?? 0);

if (!$member_id) error('member_id is required.');

// Find open attendance record for today
$stmt = db()->prepare("
    SELECT id FROM attendance
    WHERE member_id = ? AND DATE(check_in) = CURDATE() AND check_out IS NULL
    ORDER BY check_in DESC
    LIMIT 1
");
$stmt->execute([$member_id]);
$record = $stmt->fetch();

if (!$record) error('No open check-in found for this member today.');

$stmt = db()->prepare("UPDATE attendance SET check_out = NOW() WHERE id = ?");
$stmt->execute([$record['id']]);

success('Check-out recorded.', ['attendance_id' => $record['id']]);
