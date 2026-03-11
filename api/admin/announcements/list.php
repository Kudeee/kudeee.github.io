<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../config.php';
require_method('GET');
require_admin();

$page    = max(1, sanitize_int($_GET['page']     ?? 1));
$per_page = max(1, sanitize_int($_GET['per_page'] ?? 20));

$stmt = db()->query("SELECT COUNT(*) FROM announcements");
$total = (int) $stmt->fetchColumn();

$pag = get_pagination($total, $page, $per_page);

$stmt = db()->prepare("
    SELECT a.*, adm.name AS posted_by_name
    FROM announcements a
    LEFT JOIN admins adm ON adm.id = a.admin_id
    ORDER BY a.created_at DESC
    LIMIT {$pag['per_page']} OFFSET {$pag['offset']}
");
$stmt->execute();
$announcements = $stmt->fetchAll();

success('Announcements retrieved.', [
    'announcements' => $announcements,
    'pagination'    => $pag,
]);
