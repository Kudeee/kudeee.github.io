<?php
/**
 * POST /api/admin/trainers/create.php
 *
 * Adds a new trainer to the system.
 *
 * Request (POST, form-data):
 *   csrf_token        string   required
 *   name              string   required
 *   email             email    required, unique
 *   phone             string   required, PH format
 *   specialty         string   required
 *   specialty_tags    string   required — JSON array e.g. ["HIIT","Yoga"]
 *   experience_years  int      required
 *   hourly_rate       float    required
 *   bio               string   optional
 *   certifications    string   optional — JSON array
 *   status            string   optional: active | inactive  (default: active)
 *
 * Response 201:
 *   { "success": true, "message": "Trainer created.", "trainer_id": <int> }
 *
 * DB tables used:
 *   trainers, admin_logs
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$name             = sanitize_string($_POST['name']             ?? '');
$raw_email        = trim($_POST['email']                       ?? '');
$email            = sanitize_email($raw_email);
$phone            = sanitize_string($_POST['phone']            ?? '');
$specialty        = sanitize_string($_POST['specialty']        ?? '');
$specialty_tags   = trim($_POST['specialty_tags']              ?? '[]');
$experience_years = sanitize_int($_POST['experience_years']    ?? '');
$hourly_rate      = filter_var($_POST['hourly_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
$bio              = sanitize_string($_POST['bio']              ?? '');
$certifications   = trim($_POST['certifications']              ?? '[]');
$status           = sanitize_string($_POST['status']           ?? 'active');

if (!$name)                                                error('Trainer name is required.');
if (!$email)                                               error('A valid email address is required.');
if (!preg_match('/^09\d{9}$/', $phone))                   error('Phone must be in the format 09XXXXXXXXX.');
if (!$specialty)                                           error('Specialty is required.');
if ($experience_years === false || $experience_years < 0)  error('Experience years must be a non-negative integer.');
if ($hourly_rate === false || $hourly_rate < 0)            error('Hourly rate must be a valid positive number.');
if (!in_array($status, ['active', 'inactive'], true))     error('Status must be active or inactive.');

// Validate JSON arrays
$tags_decoded  = json_decode($specialty_tags, true);
$certs_decoded = json_decode($certifications, true);
if (!is_array($tags_decoded))  error('specialty_tags must be a valid JSON array.');
if (!is_array($certs_decoded)) error('certifications must be a valid JSON array.');

// ─── TODO: replace stub with real DB insert ───────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Unique email
    $stmt = $pdo->prepare('SELECT id FROM trainers WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) error('A trainer with this email already exists.', 409);

    $stmt = $pdo->prepare("
        INSERT INTO trainers
            (name, email, phone, specialty, specialty_tags, experience_years,
             hourly_rate, bio, certifications, rating, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0.0, ?, NOW())
    ");
    $stmt->execute([
        $name, $email, $phone, $specialty,
        json_encode($tags_decoded),
        $experience_years, $hourly_rate,
        $bio, json_encode($certs_decoded),
        $status,
    ]);
    $trainer_id = (int) $pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, target_type, target_id, created_at)
        VALUES (?, 'create_trainer', 'trainer', ?, NOW())
    ")->execute([$admin['admin_id'], $trainer_id]);

    success('Trainer created successfully.', ['trainer_id' => $trainer_id], 201);
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
