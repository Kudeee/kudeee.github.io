<?php
/**
 * POST /api/admin/trainers/update.php
 *
 * Updates an existing trainer's profile.
 *
 * Request (POST, form-data):
 *   csrf_token        string   required
 *   trainer_id        int      required
 *   name              string   optional
 *   email             email    optional
 *   phone             string   optional
 *   specialty         string   optional
 *   specialty_tags    string   optional — JSON array
 *   experience_years  int      optional
 *   hourly_rate       float    optional
 *   bio               string   optional
 *   certifications    string   optional — JSON array
 *   status            string   optional: active | inactive
 *
 * Response 200:
 *   { "success": true, "message": "Trainer updated." }
 *
 * DB tables used:
 *   trainers, admin_logs
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$trainer_id       = sanitize_int($_POST['trainer_id']         ?? 0);
$name             = sanitize_string($_POST['name']             ?? '');
$raw_email        = trim($_POST['email']                       ?? '');
$email            = $raw_email ? sanitize_email($raw_email) : null;
$phone            = sanitize_string($_POST['phone']            ?? '');
$specialty        = sanitize_string($_POST['specialty']        ?? '');
$specialty_tags   = isset($_POST['specialty_tags']) ? trim($_POST['specialty_tags']) : null;
$experience_years = isset($_POST['experience_years'])
                    ? sanitize_int($_POST['experience_years']) : null;
$hourly_rate      = isset($_POST['hourly_rate'])
                    ? filter_var($_POST['hourly_rate'], FILTER_VALIDATE_FLOAT) : null;
$bio              = sanitize_string($_POST['bio']              ?? '');
$certifications   = isset($_POST['certifications']) ? trim($_POST['certifications']) : null;
$status           = sanitize_string($_POST['status']           ?? '');

if (!$trainer_id || $trainer_id < 1)                          error('A valid trainer ID is required.');
if ($raw_email && !$email)                                    error('Invalid email address format.');
if ($phone && !preg_match('/^09\d{9}$/', $phone))             error('Phone must be in the format 09XXXXXXXXX.');
if ($status && !in_array($status, ['active','inactive'], true)) error('Invalid status.');
if ($experience_years !== null && $experience_years === false) error('Invalid experience_years.');
if ($hourly_rate !== null && $hourly_rate === false)           error('Invalid hourly_rate.');

$tags_decoded  = null;
$certs_decoded = null;
if ($specialty_tags !== null) {
    $tags_decoded = json_decode($specialty_tags, true);
    if (!is_array($tags_decoded)) error('specialty_tags must be a valid JSON array.');
}
if ($certifications !== null) {
    $certs_decoded = json_decode($certifications, true);
    if (!is_array($certs_decoded)) error('certifications must be a valid JSON array.');
}

// ─── TODO: replace stub with real DB update ───────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare('SELECT id FROM trainers WHERE id = ? LIMIT 1');
    $stmt->execute([$trainer_id]);
    if (!$stmt->fetch()) error('Trainer not found.', 404);

    if ($email) {
        $stmt = $pdo->prepare('SELECT id FROM trainers WHERE email = ? AND id != ? LIMIT 1');
        $stmt->execute([$email, $trainer_id]);
        if ($stmt->fetch()) error('This email is already used by another trainer.', 409);
    }

    $fields = [];
    $params = [];

    if ($name)            { $fields[] = 'name = ?';             $params[] = $name;            }
    if ($email)           { $fields[] = 'email = ?';            $params[] = $email;           }
    if ($phone)           { $fields[] = 'phone = ?';            $params[] = $phone;           }
    if ($specialty)       { $fields[] = 'specialty = ?';        $params[] = $specialty;       }
    if ($tags_decoded !== null)  { $fields[] = 'specialty_tags = ?'; $params[] = json_encode($tags_decoded); }
    if ($experience_years !== null) { $fields[] = 'experience_years = ?'; $params[] = $experience_years; }
    if ($hourly_rate !== null)   { $fields[] = 'hourly_rate = ?';    $params[] = $hourly_rate;  }
    if ($bio !== '')      { $fields[] = 'bio = ?';              $params[] = $bio;             }
    if ($certs_decoded !== null) { $fields[] = 'certifications = ?'; $params[] = json_encode($certs_decoded); }
    if ($status)          { $fields[] = 'status = ?';           $params[] = $status;          }

    if (empty($fields)) error('No fields provided for update.');

    $fields[]  = 'updated_at = NOW()';
    $params[]  = $trainer_id;
    $pdo->prepare('UPDATE trainers SET ' . implode(', ', $fields) . ' WHERE id = ?')
        ->execute($params);

    $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, target_type, target_id, created_at)
        VALUES (?, 'update_trainer', 'trainer', ?, NOW())
    ")->execute([$admin['admin_id'], $trainer_id]);

    success('Trainer updated successfully.');
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
