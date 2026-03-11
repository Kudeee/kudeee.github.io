<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('POST');
$admin = require_admin();

$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$title   = sanitize_string($body['title']   ?? '');
$content = sanitize_string($body['content'] ?? '');
$type    = sanitize_string($body['type']    ?? 'general');

if (!$title || !$content) error('Title and content are required.');

$stmt = db()->prepare("
    INSERT INTO announcements (admin_id, title, content, type, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->execute([$admin['id'], $title, $content, $type]);

success('Announcement created.', ['announcement_id' => db()->lastInsertId()]);
