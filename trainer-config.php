<?php
/**
 * trainer-config.php
 * Drop this file in the project ROOT (same folder as config.php).
 * It adds the require_trainer() helper used by all trainer API endpoints.
 *
 * Each trainer API file includes: require_once __DIR__ . '/../../../../config.php';
 * which already has all DB, session, and sanitize helpers.
 * This file adds require_trainer() so it's available after config.php loads.
 *
 * HOW TO ACTIVATE:
 *   Add this one line at the BOTTOM of your existing config.php:
 *
 *       if (file_exists(__DIR__ . '/trainer-config.php')) {
 *           require_once __DIR__ . '/trainer-config.php';
 *       }
 *
 *   OR just copy the require_trainer() function body directly into config.php.
 */

if (!function_exists('require_trainer')) {
    function require_trainer(): array {
        if (!isset($_SESSION['trainer_id'])) {
            // Return JSON 401 the same way error() does
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Trainer authentication required.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        return [
            'id'        => (int)$_SESSION['trainer_id'],
            'name'      => $_SESSION['trainer_name']      ?? '',
            'specialty' => $_SESSION['trainer_specialty'] ?? '',
        ];
    }
}

if (!function_exists('is_trainer_logged_in')) {
    function is_trainer_logged_in(): bool {
        return isset($_SESSION['trainer_id']);
    }
}
