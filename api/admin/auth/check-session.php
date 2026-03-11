<?php
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['admin_id'])) {
    error('Not authenticated.', 401);
}

success('Authenticated.', [
    'admin_id'   => $_SESSION['admin_id'],
    'admin_role' => $_SESSION['admin_role'] ?? '',
    'admin_name' => $_SESSION['admin_name'] ?? '',
]);
