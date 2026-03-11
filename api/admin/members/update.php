<?php
require_once __DIR__ . '/../../../config.php';
require_method('POST');
require_admin();

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$id   = sanitize_int($body['id'] ?? 0);
if (!$id) error('Member ID required.');

$allowed = ['first_name','last_name','email','phone','status','plan','billing_cycle'];
$set = []; $params = [];
foreach ($allowed as $f) {
    if (isset($body[$f])) { $set[] = "$f = ?"; $params[] = sanitize_string((string)$body[$f]); }
}
if (!$set) error('No fields to update.');
$params[] = $id;

$stmt = db()->prepare("UPDATE members SET " . implode(', ', $set) . " WHERE id = ?");
$stmt->execute($params);
success('Member updated.');
