<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');
require_admin();

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$id     = sanitize_int($body['id'] ?? 0);
$action = sanitize_string($body['action'] ?? 'deactivate'); // 'deactivate' | 'delete'

if (!$id) error('Trainer ID required.');
if (!in_array($action, ['deactivate', 'activate', 'delete'])) error('Invalid action.');

$pdo = db();

// Check trainer exists
$trainer = $pdo->prepare("SELECT id, first_name, last_name, status FROM trainers WHERE id = ?");
$trainer->execute([$id]);
$row = $trainer->fetch();
if (!$row) error('Trainer not found.', 404);

if ($action === 'activate') {
    $stmt = $pdo->prepare("UPDATE trainers SET status = 'active' WHERE id = ?");
    $stmt->execute([$id]);
    success('Trainer reactivated successfully.');
}

if ($action === 'deactivate') {
    $stmt = $pdo->prepare("UPDATE trainers SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$id]);
    success('Trainer deactivated successfully.');
}

if ($action === 'delete') {
    // Require super_admin role for permanent deletion
    if ((isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : '') !== 'super_admin') {
        error('Only Super Admins can permanently delete trainers.', 403);
    }

    // Nullify trainer references in class_schedules to preserve records
    $pdo->prepare("UPDATE class_schedules SET trainer_id = NULL WHERE trainer_id = ?")->execute([$id]);

    // Nullify organizer references in events
    $pdo->prepare("UPDATE events SET organizer_id = NULL WHERE organizer_id = ?")->execute([$id]);

    // Hard delete the trainer
    $pdo->prepare("DELETE FROM trainers WHERE id = ?")->execute([$id]);

    success('Trainer permanently deleted.');
}