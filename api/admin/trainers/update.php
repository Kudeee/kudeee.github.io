<?php
/**
 * POST /api/admin/trainers/update.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

$trainer_id   = sanitize_int($_POST['trainer_id']   ?? 0);
$first_name   = sanitize_string($_POST['first_name']   ?? '');
$last_name    = sanitize_string($_POST['last_name']    ?? '');
$specialty    = sanitize_string($_POST['specialty']    ?? '');
$bio          = sanitize_string($_POST['bio']          ?? '');
$session_rate = isset($_POST['session_rate']) ? filter_var($_POST['session_rate'], FILTER_VALIDATE_FLOAT) : null;
$status       = sanitize_string($_POST['status']       ?? '');

if (!$trainer_id || $trainer_id < 1) error('A valid trainer ID is required.');
if ($status && !in_array($status, ['active', 'inactive', 'on_leave'], true)) error('Invalid status.');
if ($session_rate !== null && $session_rate === false) error('Invalid session rate.');

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id FROM trainers WHERE id = ? LIMIT 1');
    $stmt->execute([$trainer_id]);
    if (!$stmt->fetch()) error('Trainer not found.', 404);

    $fields = [];
    $params = [];

    if ($first_name)           { $fields[] = 'first_name = ?';    $params[] = $first_name;    }
    if ($last_name)            { $fields[] = 'last_name = ?';     $params[] = $last_name;     }
    if ($specialty)            { $fields[] = 'specialty = ?';     $params[] = $specialty;     }
    if ($bio !== '')           { $fields[] = 'bio = ?';           $params[] = $bio;           }
    if ($session_rate !== null){ $fields[] = 'session_rate = ?';  $params[] = $session_rate;  }
    if ($status)               { $fields[] = 'status = ?';        $params[] = $status;        }

    if (empty($fields)) error('No fields provided for update.');

    $params[] = $trainer_id;
    $pdo->prepare('UPDATE trainers SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, 'trainer_updated', 'trainer', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $trainer_id, json_encode(['fields_updated' => count($fields)]), $_SERVER['REMOTE_ADDR'] ?? '']);

    success('Trainer updated successfully.');
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}