<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('POST');
require_admin();

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$member_id = sanitize_int($body['member_id'] ?? 0);
$plan_id   = sanitize_int($body['plan_id']   ?? 0);
$start_date = sanitize_string($body['start_date'] ?? date('Y-m-d'));

if (!$member_id || !$plan_id) error('member_id and plan_id are required.');

// Validate member and plan exist
$stmt = db()->prepare("SELECT id FROM members WHERE id = ?");
$stmt->execute([$member_id]);
if (!$stmt->fetch()) error('Member not found.', 404);

$stmt = db()->prepare("SELECT id, duration_days FROM membership_plans WHERE id = ?");
$stmt->execute([$plan_id]);
$plan = $stmt->fetch();
if (!$plan) error('Plan not found.', 404);

$end_date = date('Y-m-d', strtotime($start_date . ' + ' . $plan['duration_days'] . ' days'));

// Expire any existing active memberships for this member
$stmt = db()->prepare("UPDATE memberships SET status = 'expired' WHERE member_id = ? AND status = 'active'");
$stmt->execute([$member_id]);

// Insert new membership
$stmt = db()->prepare("
    INSERT INTO memberships (member_id, plan_id, start_date, end_date, status, created_at)
    VALUES (?, ?, ?, ?, 'active', NOW())
");
$stmt->execute([$member_id, $plan_id, $start_date, $end_date]);

success('Membership assigned successfully.', [
    'membership_id' => db()->lastInsertId(),
    'end_date'      => $end_date,
]);
