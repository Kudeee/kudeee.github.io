<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('POST');
require_admin(['super_admin', 'admin']);

$body        = json_decode(file_get_contents('php://input'), true) ?? [];
$name        = sanitize_string($body['name']        ?? '');
$description = sanitize_string($body['description'] ?? '');
$price       = (float) ($body['price'] ?? 0);
$duration    = sanitize_int($body['duration_days'] ?? 30);
$features    = sanitize_string($body['features']   ?? '');

if (!$name || $price <= 0) error('Plan name and a valid price are required.');

$stmt = db()->prepare("
    INSERT INTO membership_plans (name, description, price, duration_days, features, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");
$stmt->execute([$name, $description, $price, $duration, $features]);

success('Plan created successfully.', ['plan_id' => db()->lastInsertId()]);
