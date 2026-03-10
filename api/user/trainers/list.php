<?php
require_once __DIR__ . '/../../config.php';

$member = require_member();
$pdo    = db();

$specialty   = sanitize_string($_GET['specialty']   ?? '');
$availability= sanitize_string($_GET['availability'] ?? '');

$where  = ["t.status = 'active'"];
$params = [];

if ($specialty) {
    $where[]  = "t.specialty LIKE ?";
    $params[] = "%$specialty%";
}
if ($availability) {
    $where[]  = "t.availability = ?";
    $params[] = $availability;
}

$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT id, first_name, last_name, specialty, bio, image_url,
           exp_years, client_count, session_rate, rating,
           availability, specialty_tags, status
    FROM trainers
    WHERE $whereSQL
    ORDER BY rating DESC, first_name ASC
");
$stmt->execute($params);
$trainers = $stmt->fetchAll();

// Decode specialty_tags JSON
foreach ($trainers as &$t) {
    $t['specialty_tags'] = json_decode($t['specialty_tags'] ?? '[]', true);
}

success('OK', ['trainers' => $trainers]);