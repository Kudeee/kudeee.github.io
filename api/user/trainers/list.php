<?php
/**
 * GET /api/user/trainers/list.php
 * Returns the public trainer directory with optional filters.
 */

require_once __DIR__ . '/../../config.php';
require_method('GET');

$specialty    = sanitize_string($_GET['specialty']    ?? '');
$availability = sanitize_string($_GET['availability'] ?? '');
$min_exp      = max(0, (int)($_GET['min_exp']         ?? 0));

try {
    $pdo = db();

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

    $where = 'WHERE ' . implode(' AND ', $conditions);

    $stmt = $pdo->prepare("
        SELECT id,
               CONCAT(first_name, ' ', last_name) AS name,
               specialty,
               bio,
               image_url,
               exp_years    AS exp,
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
    $trainers = $stmt->fetchAll();

    foreach ($trainers as &$t) {
        $t['specialty_tags'] = json_decode($t['specialty_tags'] ?? '[]', true);
    }
    unset($t);

    success('OK', ['trainers' => $trainers]);

} catch (PDOException $e) {
    error('A database error occurred. Please try again.', 500);
}