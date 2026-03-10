<?php
/**
 * POST /api/admin/members/update.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

$member_id    = sanitize_int($_POST['member_id']    ?? 0);
$first_name   = sanitize_string($_POST['first_name']   ?? '');
$last_name    = sanitize_string($_POST['last_name']    ?? '');
$raw_email    = trim($_POST['email'] ?? '');
$email        = $raw_email ? sanitize_email($raw_email) : null;
$phone        = sanitize_string($_POST['phone']        ?? '');
$plan         = sanitize_string($_POST['plan']         ?? '');
$status       = sanitize_string($_POST['status']       ?? '');
$new_password = $_POST['new_password'] ?? '';

if (!$member_id || $member_id < 1) error('A valid member ID is required.');
if ($raw_email && !$email) error('Invalid email address format.');
if ($phone && !preg_match('/^09\d{9}$/', $phone)) error('Phone must be 09XXXXXXXXX format.');
if ($new_password && strlen($new_password) < 8) error('New password must be at least 8 characters.');

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id FROM members WHERE id = ? LIMIT 1');
    $stmt->execute([$member_id]);
    if (!$stmt->fetch()) error('Member not found.', 404);

    if ($email) {
        $stmt = $pdo->prepare('SELECT id FROM members WHERE email = ? AND id != ? LIMIT 1');
        $stmt->execute([$email, $member_id]);
        if ($stmt->fetch()) error('This email is already in use by another member.', 409);
    }

    $fields = [];
    $params = [];

    if ($first_name)   { $fields[] = 'first_name = ?';    $params[] = $first_name;  }
    if ($last_name)    { $fields[] = 'last_name = ?';     $params[] = $last_name;   }
    if ($email)        { $fields[] = 'email = ?';         $params[] = $email;       }
    if ($phone)        { $fields[] = 'phone = ?';         $params[] = $phone;       }
    if ($plan)         { $fields[] = 'plan = ?';          $params[] = $plan;        }
    if ($status)       { $fields[] = 'status = ?';        $params[] = $status;      }
    if ($new_password) {
        $fields[] = 'password_hash = ?';
        $params[] = password_hash($new_password, PASSWORD_BCRYPT);
    }

    if (empty($fields)) error('No fields provided for update.');

    $params[] = $member_id;
    $pdo->prepare('UPDATE members SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, 'member_updated', 'member', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $member_id, json_encode(['fields' => array_keys(array_flip($fields))]), $_SERVER['REMOTE_ADDR'] ?? '']);

    success('Member updated successfully.');
} catch (PDOException $e) {
    error('Database error: ' . $e->getMessage(), 500);
}