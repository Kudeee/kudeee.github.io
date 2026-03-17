<?php
require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../../../../trainer-config.php';
require_method('POST');
$trainer_session = require_trainer();
$tid             = $trainer_session['id'];
$pdo             = db();

// Accept both JSON and form-data
$body = json_decode(file_get_contents('php://input'), true);
if (!$body) $body = $_POST;

$allowed = ['specialty', 'bio', 'session_rate', 'availability', 'exp_years', 'client_count'];
$set    = [];
$params = [];

foreach ($allowed as $field) {
    if (!isset($body[$field])) continue;
    $val = $field === 'bio' ? trim(strip_tags($body[$field])) : sanitize_string((string)$body[$field]);

    if ($field === 'session_rate') { $val = max(0, (float)$val); }
    if ($field === 'exp_years')    { $val = max(0, (int)$val); }
    if ($field === 'client_count') { $val = max(0, (int)$val); }
    if ($field === 'availability' && !in_array($val, ['available','limited'])) continue;

    $set[]    = "$field = ?";
    $params[] = $val;
}

// Specialty tags (JSON array from comma-separated input or array)
if (isset($body['specialty_tags'])) {
    $tags = is_array($body['specialty_tags'])
        ? array_map('trim', $body['specialty_tags'])
        : array_filter(array_map('trim', explode(',', $body['specialty_tags'])));
    $set[]    = "specialty_tags = ?";
    $params[] = json_encode(array_values($tags));
}

if (!$set) error('Nothing to update.');

$params[] = $tid;
$pdo->prepare("UPDATE trainers SET " . implode(', ', $set) . " WHERE id = ?")
    ->execute($params);

// Also sync session name in session if specialty changed
if (isset($body['specialty'])) {
    $_SESSION['trainer_specialty'] = sanitize_string($body['specialty']);
}

success('Profile updated.');
