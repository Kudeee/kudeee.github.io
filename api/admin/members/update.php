<?php
/**
 * POST /api/admin/members/update.php
 *
 * Updates a member's profile or admin-controlled fields.
 *
 * Request (POST, form-data):
 *   csrf_token    string   required
 *   member_id     int      required
 *   first_name    string   optional
 *   last_name     string   optional
 *   email         email    optional (must remain unique)
 *   phone         string   optional, PH format
 *   plan          string   optional: BASIC PLAN | PREMIUM PLAN | VIP PLAN
 *   status        string   optional: active | expired | paused | suspended
 *   notes         string   optional — admin notes
 *   new_password  string   optional — if supplied, updates password (min 8 chars)
 *
 * Response 200:
 *   { "success": true, "message": "Member updated." }
 *
 * DB tables used:
 *   members, admin_logs
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$member_id    = sanitize_int($_POST['member_id']    ?? 0);
$first_name   = sanitize_string($_POST['first_name']   ?? '');
$last_name    = sanitize_string($_POST['last_name']    ?? '');
$raw_email    = trim($_POST['email'] ?? '');
$email        = $raw_email ? sanitize_email($raw_email) : null;
$phone        = sanitize_string($_POST['phone']        ?? '');
$plan         = sanitize_string($_POST['plan']         ?? '');
$status       = sanitize_string($_POST['status']       ?? '');
$notes        = sanitize_string($_POST['notes']        ?? '');
$new_password = $_POST['new_password'] ?? '';

if (!$member_id || $member_id < 1) error('A valid member ID is required.');

$valid_plans   = ['', 'BASIC PLAN', 'PREMIUM PLAN', 'VIP PLAN'];
$valid_statuses = ['', 'active', 'expired', 'paused', 'suspended'];

if ($raw_email && !$email)                         error('Invalid email address format.');
if ($phone && !preg_match('/^09\d{9}$/', $phone))  error('Phone must be in the format 09XXXXXXXXX.');
if (!in_array($plan, $valid_plans, true))           error('Invalid plan.');
if (!in_array($status, $valid_statuses, true))      error('Invalid status.');
if ($new_password && strlen($new_password) < 8)    error('New password must be at least 8 characters.');

// ─── TODO: replace stub with real DB update ───────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Confirm member exists
    $stmt = $pdo->prepare('SELECT id FROM members WHERE id = ? LIMIT 1');
    $stmt->execute([$member_id]);
    if (!$stmt->fetch()) error('Member not found.', 404);

    // Unique email check (exclude self)
    if ($email) {
        $stmt = $pdo->prepare('SELECT id FROM members WHERE email = ? AND id != ? LIMIT 1');
        $stmt->execute([$email, $member_id]);
        if ($stmt->fetch()) error('This email is already in use by another member.', 409);
    }

    // Build dynamic SET clause
    $fields = [];
    $params = [];

    if ($first_name) { $fields[] = 'first_name = ?'; $params[] = $first_name; }
    if ($last_name)  { $fields[] = 'last_name = ?';  $params[] = $last_name;  }
    if ($email)      { $fields[] = 'email = ?';      $params[] = $email;      }
    if ($phone)      { $fields[] = 'phone = ?';      $params[] = $phone;      }
    if ($plan)       { $fields[] = 'plan = ?';       $params[] = $plan;       }
    if ($status)     { $fields[] = 'status = ?';     $params[] = $status;     }
    if ($notes !== '') { $fields[] = 'notes = ?';    $params[] = $notes;      }
    if ($new_password) {
        $fields[] = 'password_hash = ?';
        $params[] = password_hash($new_password, PASSWORD_BCRYPT);
    }

    if (empty($fields)) error('No fields provided for update.');

    $fields[]  = 'updated_at = NOW()';
    $params[]  = $member_id;

    $pdo->prepare('UPDATE members SET ' . implode(', ', $fields) . ' WHERE id = ?')
        ->execute($params);

    // Log admin action
    $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, target_type, target_id, created_at)
        VALUES (?, 'update_member', 'member', ?, NOW())
    ")->execute([$admin['admin_id'], $member_id]);

    success('Member updated successfully.');
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
