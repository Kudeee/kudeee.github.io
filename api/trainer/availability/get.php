<?php
require_once __DIR__ . '/../../../../config.php';
require_once __DIR__ . '/../../../../trainer-config.php';
require_method('GET');
$trainer = require_trainer();
$tid     = $trainer['id'];
$pdo     = db();

$week_offset = sanitize_int($_GET['week_offset'] ?? 0);

// Ensure trainer_availability table exists (migration)
$pdo->exec("CREATE TABLE IF NOT EXISTS trainer_availability (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trainer_id  INT UNSIGNED NOT NULL,
    slot_date   DATE NOT NULL,
    slot_time   VARCHAR(10) NOT NULL COMMENT '24h format HH:MM',
    is_open     TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=open, 0=blocked',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ta_slot (trainer_id, slot_date, slot_time),
    KEY idx_ta_trainer (trainer_id),
    KEY idx_ta_date (slot_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Calculate week range (Monday to Sunday)
$today  = new DateTime('today', new DateTimeZone('Asia/Manila'));
$dow    = (int)$today->format('N'); // 1=Mon, 7=Sun
$monday = clone $today;
$monday->modify('-' . ($dow - 1) . ' days');
$monday->modify('+' . ($week_offset * 7) . ' days');
$sunday = clone $monday;
$sunday->modify('+6 days');

$date_from = $monday->format('Y-m-d');
$date_to   = $sunday->format('Y-m-d');

// Get saved availability slots for this week
$stmt = $pdo->prepare(
    "SELECT slot_date, slot_time, is_open
     FROM trainer_availability
     WHERE trainer_id = ? AND slot_date BETWEEN ? AND ?"
);
$stmt->execute([$tid, $date_from, $date_to]);
$rows = $stmt->fetchAll();

// Build key-value map: "YYYY-MM-DD|HH:MM" => "open"|"blocked"
$slots = [];
foreach ($rows as $r) {
    $key          = $r['slot_date'] . '|' . $r['slot_time'];
    $slots[$key]  = $r['is_open'] ? 'open' : 'blocked';
}

// Get booked trainer_bookings in this week for this trainer
$booked_stmt = $pdo->prepare(
    "SELECT tb.booking_date, tb.booking_time,
            CONCAT(m.first_name,' ',m.last_name) AS member_name
     FROM trainer_bookings tb
     JOIN members m ON m.id = tb.member_id
     WHERE tb.trainer_id = ? AND tb.status = 'confirmed'
       AND tb.booking_date BETWEEN ? AND ?"
);
$booked_stmt->execute([$tid, $date_from, $date_to]);
$booked_rows = $booked_stmt->fetchAll();

// Convert booking_time (stored as "9:00 AM") to 24h for key matching
$booked = [];
foreach ($booked_rows as $b) {
    $t24 = date('H:i', strtotime($b['booking_time']));
    $key = $b['booking_date'] . '|' . $t24;
    $booked[$key] = $b['member_name'];
}

success('Availability retrieved.', [
    'slots'       => $slots,
    'booked'      => $booked,
    'week_offset' => $week_offset,
    'date_from'   => $date_from,
    'date_to'     => $date_to,
]);
