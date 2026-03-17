<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../trainer-config.php';
require_method('GET');
$trainer_session = require_trainer();
$tid             = $trainer_session['id'];
$pdo             = db();

$stmt = $pdo->prepare(
    "SELECT t.*,
            COUNT(DISTINCT tb.id)                          AS total_sessions,
            COUNT(DISTINCT tb.member_id)                   AS total_clients,
            COALESCE(SUM(CASE WHEN tb.status='completed' THEN tb.total_price ELSE 0 END),0) AS lifetime_earnings
     FROM trainers t
     LEFT JOIN trainer_bookings tb ON tb.trainer_id = t.id
     WHERE t.id = ?
     GROUP BY t.id"
);
$stmt->execute([$tid]);
$trainer = $stmt->fetch();

if (!$trainer) error('Trainer not found.', 404);

$trainer['specialty_tags']  = json_decode($trainer['specialty_tags'] ?? '[]', true) ?: [];
$trainer['session_rate']     = (float)$trainer['session_rate'];
$trainer['rating']           = (float)$trainer['rating'];
$trainer['total_sessions']   = (int)$trainer['total_sessions'];
$trainer['total_clients']    = (int)$trainer['total_clients'];
$trainer['lifetime_earnings'] = (float)$trainer['lifetime_earnings'];

success('Profile retrieved.', ['trainer' => $trainer]);
