<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');
require_admin(['super_admin', 'admin']);

$body          = json_decode(file_get_contents('php://input'), true) ?? [];
$plan          = sanitize_string($body['plan']          ?? '');
$monthly_price = (float)($body['monthly_price']         ?? 0);
$yearly_price  = (float)($body['yearly_price']          ?? 0);
$color         = sanitize_string($body['color']         ?? '#ff6b35');
$benefits      = $body['benefits']                      ?? [];
$max_classes   = (int)($body['max_classes']             ?? -1);
$pt_sessions   = (int)($body['pt_sessions']             ?? 0);
$guest_passes  = (int)($body['guest_passes']            ?? 0);
$is_active     = (int)(!empty($body['is_active']));

if (!$plan) error('Plan name is required.');

$allowed_plans = ['BASIC PLAN', 'PREMIUM PLAN', 'VIP PLAN'];
if (!in_array($plan, $allowed_plans)) error('Invalid plan name.');
if ($monthly_price <= 0) error('Monthly price must be greater than 0.');
if ($yearly_price  <= 0) error('Yearly price must be greater than 0.');

$benefits_json = json_encode(array_values(array_filter(array_map('trim', (array)$benefits))));

$pdo = db();

// Ensure table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS plan_configs (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    plan          VARCHAR(50) NOT NULL UNIQUE,
    monthly_price DECIMAL(10,2) NOT NULL,
    yearly_price  DECIMAL(10,2) NOT NULL,
    color         VARCHAR(20) DEFAULT '#ff6b35',
    benefits      TEXT,
    max_classes   INT DEFAULT -1,
    pt_sessions   INT DEFAULT 0,
    guest_passes  INT DEFAULT 0,
    is_active     TINYINT(1) DEFAULT 1,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$stmt = $pdo->prepare("
    INSERT INTO plan_configs (plan, monthly_price, yearly_price, color, benefits, max_classes, pt_sessions, guest_passes, is_active)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        monthly_price = VALUES(monthly_price),
        yearly_price  = VALUES(yearly_price),
        color         = VALUES(color),
        benefits      = VALUES(benefits),
        max_classes   = VALUES(max_classes),
        pt_sessions   = VALUES(pt_sessions),
        guest_passes  = VALUES(guest_passes),
        is_active     = VALUES(is_active),
        updated_at    = NOW()
");
$stmt->execute([$plan, $monthly_price, $yearly_price, $color, $benefits_json, $max_classes, $pt_sessions, $guest_passes, $is_active]);

// Log the change
try {
    $admin = require_admin();
    $pdo->prepare("INSERT INTO audit_log (admin_id, action, target_type, created_at) VALUES (?,?,?,NOW())")
        ->execute([$admin['id'], 'plan_updated', 'plan_config']);
} catch (\Throwable $e) { /* audit_log may not exist */ }

success('Plan updated successfully.', [
    'plan'          => $plan,
    'monthly_price' => $monthly_price,
    'yearly_price'  => $yearly_price,
]);