<?php
/**
 * GET /api/user/trainers/availability.php
 * Returns available time slots for a specific trainer on a given date.
 */

require_once __DIR__ . '/../../config.php';
require_method('GET');

$trainer_id = sanitize_int($_GET['trainer_id'] ?? 0);
$date       = sanitize_string($_GET['date']     ?? '');

if (!$trainer_id || $trainer_id <= 0) { error('Trainer ID is required.'); }
if (!$date || !strtotime($date))       { error('Valid date is required.'); }
if (strtotime($date) < strtotime('today')) { error('Cannot query availability for past dates.'); }

try {
    $pdo = db();

    // Verify trainer exists and is active
    $stmt = $pdo->prepare("SELECT id FROM trainers WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$trainer_id]);
    if (!$stmt->fetch()) { error('Trainer not found.', 404); }

    $all_slots = ['6:00 AM', '8:00 AM', '10:00 AM', '12:00 PM', '2:00 PM', '4:00 PM', '6:00 PM', '8:00 PM'];

    $stmt = $pdo->prepare("
        SELECT booking_time
        FROM trainer_bookings
        WHERE trainer_id = ? AND booking_date = ? AND status != 'cancelled'
    ");
    $stmt->execute([$trainer_id, $date]);
    $booked_set = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

    $available_slots   = [];
    $unavailable_slots = [];

    foreach ($all_slots as $slot) {
        if (isset($booked_set[$slot])) {
            $unavailable_slots[] = $slot;
        } else {
            $available_slots[] = $slot;
        }
    }

    success('OK', [
        'available_slots'   => $available_slots,
        'unavailable_slots' => $unavailable_slots,
    ]);

} catch (PDOException $e) {
    error('A database error occurred. Please try again.', 500);
}