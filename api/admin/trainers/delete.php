<?php
/**
 * POST /api/admin/trainers/delete.php
 *
 * Deactivates or permanently removes a trainer.
 * Deletion is restricted to super_admin.
 *
 * Request (POST, form-data):
 *   csrf_token  string   required
 *   trainer_id  int      required
 *   action      string   required: deactivate | reactivate | delete
 *   reason      string   required for deactivate/delete
 *
 * Response 200:
 *   { "success": true, "message": "Trainer deactivated." }
 *
 * DB tables used:
 *   trainers, trainer_bookings, admin_logs
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$trainer_id = sanitize_int($_POST['trainer_id'] ?? 0);
$action     = sanitize_string($_POST['action']  ?? '');
$reason     = sanitize_string($_POST['reason']  ?? '');

if (!$trainer_id || $trainer_id < 1)                                        error('A valid trainer ID is required.');
if (!in_array($action, ['deactivate','reactivate','delete'], true))          error('Invalid action.');
if (in_array($action, ['deactivate','delete'], true) && !$reason)            error('A reason is required.');
if ($action === 'delete' && !is_super_admin())                               error('Only super admins can permanently delete trainers.', 403);

// ─── TODO: replace stub with real DB operation ────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare('SELECT id FROM trainers WHERE id = ? LIMIT 1');
    $stmt->execute([$trainer_id]);
    if (!$stmt->fetch()) error('Trainer not found.', 404);

    if ($action === 'deactivate') {
        $pdo->prepare("UPDATE trainers SET status = 'inactive', updated_at = NOW() WHERE id = ?")
            ->execute([$trainer_id]);
        $msg = 'Trainer deactivated.';
    } elseif ($action === 'reactivate') {
        $pdo->prepare("UPDATE trainers SET status = 'active', updated_at = NOW() WHERE id = ?")
            ->execute([$trainer_id]);
        $msg = 'Trainer reactivated.';
    } else {
        // Soft delete: check for upcoming bookings first
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM trainer_bookings
            WHERE trainer_id = ? AND session_date >= CURDATE() AND status = 'confirmed'
        ");
        $stmt->execute([$trainer_id]);
        if ((int) $stmt->fetchColumn() > 0) {
            error('Cannot delete trainer with upcoming confirmed bookings. Deactivate them instead.', 409);
        }
        $pdo->prepare("UPDATE trainers SET status = 'deleted', updated_at = NOW() WHERE id = ?")
            ->execute([$trainer_id]);
        $msg = 'Trainer deleted.';
    }

    $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, target_type, target_id, notes, created_at)
        VALUES (?, ?, 'trainer', ?, ?, NOW())
    ")->execute([$admin['admin_id'], $action . '_trainer', $trainer_id, $reason]);

    success($msg);
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
