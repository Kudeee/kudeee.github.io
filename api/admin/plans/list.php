<?php
require_once __DIR__ . '/../../../config.php';
require_method('GET');

$pdo = db();

// Ensure plan_configs table exists
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

// Seed defaults if empty
$count = (int)$pdo->query("SELECT COUNT(*) FROM plan_configs")->fetchColumn();
if ($count === 0) {
    $defaults = [
        [
            'BASIC PLAN', 499, 5389, '#9e9e9e',
            json_encode(['Gym access (6AM–10PM)', 'Locker room access', '2 group classes/week']),
            2, 0, 0, 1,
        ],
        [
            'PREMIUM PLAN', 899, 9709, '#ff6b35',
            json_encode(['24/7 gym access', 'Unlimited group classes', '1 PT session/month', 'Guest passes']),
            -1, 1, 1, 1,
        ],
        [
            'VIP PLAN', 1499, 16189, '#f9a825',
            json_encode(['All Premium features', '4 PT sessions/month', 'Priority class booking', '2 guest passes/month', 'Nutrition consultation']),
            -1, 4, 2, 1,
        ],
    ];
    $ins = $pdo->prepare("
        INSERT IGNORE INTO plan_configs
            (plan, monthly_price, yearly_price, color, benefits, max_classes, pt_sessions, guest_passes, is_active)
        VALUES (?,?,?,?,?,?,?,?,?)
    ");
    foreach ($defaults as $d) {
        $ins->execute($d);
    }
}

$plans = $pdo->query("SELECT * FROM plan_configs ORDER BY monthly_price ASC")->fetchAll();

// Decode benefits JSON for each plan
foreach ($plans as &$p) {
    $p['benefits']    = json_decode($p['benefits'] ?? '[]', true) ?: [];
    $p['is_active']   = (bool)$p['is_active'];
    $p['monthly_price'] = (float)$p['monthly_price'];
    $p['yearly_price']  = (float)$p['yearly_price'];
    $p['max_classes']   = (int)$p['max_classes'];
    $p['pt_sessions']   = (int)$p['pt_sessions'];
    $p['guest_passes']  = (int)$p['guest_passes'];
}

success('Plans retrieved.', ['plans' => $plans]);