<?php
require_once __DIR__ . '/../../../config.php';
require_method('GET');
// Return static plan definitions (could be moved to a DB table later)
$plans = [
    [
        'id'           => 1,
        'plan'         => 'BASIC PLAN',
        'monthly_price'=> 499,
        'yearly_price' => 5028,
        'color'        => '#9e9e9e',
        'benefits'     => ['Gym access (6AM–10PM)', 'Locker room access', '2 group classes/week'],
        'max_classes'  => 2,
        'pt_sessions'  => 0,
        'guest_passes' => 0,
        'is_active'    => true,
    ],
    [
        'id'           => 2,
        'plan'         => 'PREMIUM PLAN',
        'monthly_price'=> 899,
        'yearly_price' => 9067,
        'color'        => '#ff6b35',
        'benefits'     => ['24/7 gym access', 'Unlimited group classes', '1 PT session/month'],
        'max_classes'  => -1,
        'pt_sessions'  => 1,
        'guest_passes' => 0,
        'is_active'    => true,
    ],
    [
        'id'           => 3,
        'plan'         => 'VIP PLAN',
        'monthly_price'=> 1499,
        'yearly_price' => 15189,
        'color'        => '#f9a825',
        'benefits'     => ['All Premium features', '4 PT sessions/month', 'Priority class booking'],
        'max_classes'  => -1,
        'pt_sessions'  => 4,
        'guest_passes' => 2,
        'is_active'    => true,
    ],
];

// Try to pull overrides from DB if table exists
try {
    $pdo = db();
    $rows = $pdo->query("SELECT * FROM plan_configs ORDER BY id ASC")->fetchAll();
    foreach ($rows as $row) {
        foreach ($plans as &$p) {
            if ($p['plan'] === $row['plan']) {
                $p['monthly_price'] = (float)$row['monthly_price'];
                $p['yearly_price']  = (float)$row['yearly_price'];
                $p['color']         = $row['color']        ?? $p['color'];
                $p['benefits']      = json_decode($row['benefits'] ?? '[]', true) ?: $p['benefits'];
                $p['max_classes']   = (int)($row['max_classes']  ?? $p['max_classes']);
                $p['pt_sessions']   = (int)($row['pt_sessions']  ?? $p['pt_sessions']);
                $p['guest_passes']  = (int)($row['guest_passes'] ?? $p['guest_passes']);
                $p['is_active']     = (bool)($row['is_active']   ?? true);
            }
        }
    }
} catch (\Throwable $e) {
    // Table doesn't exist yet — return defaults silently
}

success('Plans retrieved.', ['plans' => $plans]);