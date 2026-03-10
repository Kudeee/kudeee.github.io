<?php
/**
 * GET /api/csrf-token.php
 * Returns the current session's CSRF token.
 * Used by admin-js.js to inject tokens into dynamically-loaded forms.
 */
require_once __DIR__ . '/config.php';
require_method('GET');

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['csrf_token' => $_SESSION['csrf_token']]);