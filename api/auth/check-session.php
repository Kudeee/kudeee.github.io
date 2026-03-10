<?php
require_once __DIR__ . '/../config.php';

if (!is_logged_in()) {
    error('Not authenticated.', 401);
}

$pdo  = db();
$stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, plan, billing_cycle, status, join_date FROM members WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['member_id']]);
$member = $stmt->fetch();

if (!$member) {
    session_destroy();
    error('Session invalid.', 401);
}

success('Authenticated.', ['member' => $member]);