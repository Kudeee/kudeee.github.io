<?php

/**
 * api/user/trainers/list.php  (also serves as public endpoint)
 * Returns active trainers with optional filters.
 * Does NOT require authentication — used by trainers-page, landing page,
 * and book-trainer page.
 */
require_once __DIR__ . '/../../config.php';

$pdo = db();

$specialty    = sanitize_string($_GET['specialty']    ?? '');
$availability = sanitize_string($_GET['availability'] ?? '');
$search       = sanitize_string($_GET['search']       ?? '');

$where  = ["t.status = 'active'"];
$params = [];

if ($specialty !== '') {
    $where[]  = "t.specialty LIKE ?";
    $params[] = "%$specialty%";
}
if ($availability !== '') {
    $where[]  = "t.availability = ?";
    $params[] = $availability;
}
if ($search !== '') {
    $where[]  = "(t.first_name LIKE ? OR t.last_name LIKE ? OR t.specialty LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT t.id,
           t.first_name, t.last_name,
           CONCAT(t.first_name, ' ', t.last_name) AS full_name,
           t.specialty, t.bio, t.image_url,
           t.exp_years, t.client_count, t.session_rate, t.rating,
           t.availability, t.specialty_tags, t.status,
           COUNT(DISTINCT cs.id) AS total_sessions,
           COUNT(DISTINCT CASE WHEN cs.scheduled_at >= NOW() AND cs.status='active' THEN cs.id END) AS upcoming_sessions
    FROM trainers t
    LEFT JOIN class_schedules cs ON cs.trainer_id = t.id
    $where_sql
    GROUP BY t.id
    ORDER BY t.rating DESC, t.first_name ASC
");
$stmt->execute($params);
$trainers = $stmt->fetchAll();

foreach ($trainers as &$tr) {
    $tr['specialty_tags'] = json_decode($tr['specialty_tags'] ?? '[]', true) ?: [];
    $tr['session_rate']   = (float) $tr['session_rate'];
    $tr['rating']         = (float) $tr['rating'];
    $tr['exp_years']      = (int)   $tr['exp_years'];
    $tr['client_count']   = (int)   $tr['client_count'];
    $tr['total_sessions'] = (int)   $tr['total_sessions'];
    $tr['upcoming_sessions'] = (int) $tr['upcoming_sessions'];
}

success('Trainers retrieved.', ['trainers' => $trainers]);
