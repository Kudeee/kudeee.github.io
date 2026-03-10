<?php
/**
 * GET /api/admin/roles/list.php
 * Returns all admin users.
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin(['super_admin', 'admin']);

try {
    $pdo = db();

    $stmt = $pdo->query("
        SELECT id, first_name, last_name, email, role, status, created_at
        FROM admin_users
        ORDER BY FIELD(role,'super_admin','admin','staff'), first_name ASC
    ");
    $users = $stmt->fetchAll();

    // Stats
    $stmt = $pdo->query("SELECT role, COUNT(*) AS cnt FROM admin_users WHERE status='active' GROUP BY role");
    $role_counts = ['total' => 0, 'super_admin' => 0, 'admin' => 0, 'staff' => 0];
    foreach ($stmt->fetchAll() as $r) {
        $role_counts[$r['role']] = (int) $r['cnt'];
        $role_counts['total'] += (int) $r['cnt'];
    }

    // Also get trainer count from trainers table (they have system access via admin_users)
    $stmt = $pdo->query("SELECT COUNT(*) FROM trainers WHERE status='active'");
    $trainer_count = (int) $stmt->fetchColumn();

    success('Admin users retrieved.', [
        'users'         => $users,
        'stats'         => $role_counts,
        'trainer_count' => $trainer_count,
    ]);
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}