<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('POST');
require_admin(['super_admin']);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$id   = sanitize_int($body['id'] ?? 0);
if (!$id) error('Plan ID is required.');

// Check for active memberships
$stmt = db()->prepare("SELECT COUNT(*) FROM memberships WHERE plan_id = ? AND status = 'active'");
$stmt->execute([$id]);
if ((int) $stmt->fetchColumn() > 0) {
    error('Cannot delete plan with active memberships. Deactivate it instead.');
}

$stmt = db()->prepare("DELETE FROM membership_plans WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) error('Plan not found.', 404);

success('Plan deleted successfully.');
