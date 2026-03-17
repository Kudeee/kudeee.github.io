<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../trainer-config.php';
if (!isset($_SESSION['trainer_id'])) error('Not authenticated.', 401);
success('Authenticated.', [
    'trainer_id'        => $_SESSION['trainer_id'],
    'trainer_name'      => $_SESSION['trainer_name']      ?? '',
    'trainer_specialty' => $_SESSION['trainer_specialty'] ?? '',
]);
