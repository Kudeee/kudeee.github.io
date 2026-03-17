<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../trainer-config.php';
require_method('POST');
$trainer = require_trainer();
$tid     = $trainer['id'];
$pdo     = db();

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$slots = $body['slots'] ?? [];

if (!is_array($slots) || empty($slots)) error('No slots provided.');

// Ensure table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS trainer_availability (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trainer_id  INT UNSIGNED NOT NULL,
    slot_date   DATE NOT NULL,
    slot_time   VARCHAR(10) NOT NULL,
    is_open     TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ta_slot (trainer_id, slot_date, slot_time),
    KEY idx_ta_trainer (trainer_id),
    KEY idx_ta_date (slot_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$stmt = $pdo->prepare(
    "INSERT INTO trainer_availability (trainer_id, slot_date, slot_time, is_open)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE is_open = VALUES(is_open), updated_at = NOW()"
);

$saved = 0;
foreach ($slots as $slot) {
    $date    = sanitize_string($slot['date']      ?? '');
    $time    = sanitize_string($slot['time']      ?? '');  // expects "HH:MM" 24h
    $is_open = (int)!empty($slot['available']);

    if (!$date || !$time) continue;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))   continue;
    if (!preg_match('/^\d{2}:\d{2}$/', $time))          continue;

    $stmt->execute([$tid, $date, $time, $is_open]);
    $saved++;
}

success('Availability saved.', ['saved' => $saved]);
