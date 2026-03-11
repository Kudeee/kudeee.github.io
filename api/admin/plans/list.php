<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('GET');
require_admin();

$stmt = db()->query("
    SELECT p.*, 
           COUNT(ms.id) AS active_members
    FROM membership_plans p
    LEFT JOIN memberships ms ON ms.plan_id = p.id AND ms.status = 'active'
    GROUP BY p.id
    ORDER BY p.price ASC
");
$plans = $stmt->fetchAll();

success('Plans retrieved.', ['plans' => $plans]);
