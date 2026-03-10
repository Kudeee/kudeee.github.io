<?php
/**
 * GET /api/user/trainers/list.php
 *
 * Returns the public trainer directory with optional filters.
 * Used by trainers-page.html and the book-trainer wizard (Step 1).
 *
 * Query params (GET):
 *   specialty   string  optional  e.g. "Yoga"
 *   availability string optional "available" | "limited"
 *   min_exp     int     optional  minimum years of experience
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "trainers": [
 *       {
 *         id, name, specialty, bio, image_url,
 *         exp, clients, base_rate, rating,
 *         availability, specialty_tags, status
 *       }
 *     ]
 *   }
 *
 * DB tables used (when connected):
 *   trainers  (id, first_name, last_name, specialty, bio, image_url,
 *              exp_years, client_count, session_rate, rating, availability,
 *              specialty_tags, status)
 */

require_once __DIR__ . '/../../config.php';
require_method('GET');

// Publicly accessible — no auth required
$specialty    = sanitize_string($_GET['specialty']    ?? '');
$availability = sanitize_string($_GET['availability'] ?? '');
$min_exp      = max(0, (int)($_GET['min_exp']         ?? 0));

// ─── TODO: replace stub with real DB logic ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $conditions = ["status = 'active'"];
    $params     = [];

    if ($specialty) {
        $conditions[] = 'specialty LIKE ?';
        $params[]     = '%' . $specialty . '%';
    }
    if ($availability && in_array($availability, ['available', 'limited'], true)) {
        $conditions[] = 'availability = ?';
        $params[]     = $availability;
    }
    if ($min_exp > 0) {
        $conditions[] = 'exp_years >= ?';
        $params[]     = $min_exp;
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $stmt = $pdo->prepare("
        SELECT id,
               CONCAT(first_name, ' ', last_name) AS name,
               specialty,
               bio,
               image_url,
               exp_years   AS exp,
               client_count AS clients,
               session_rate AS base_rate,
               rating,
               availability,
               specialty_tags,
               status
        FROM trainers
        $where
        ORDER BY rating DESC, exp_years DESC
    ");
    $stmt->execute($params);
    $trainers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode JSON specialty_tags stored as JSON string in DB
    foreach ($trainers as &$t) {
        $t['specialty_tags'] = json_decode($t['specialty_tags'] ?? '[]', true);
    }
    unset($t);

    success('OK', ['trainers' => $trainers]);
*/

// ─── STUB response ────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
