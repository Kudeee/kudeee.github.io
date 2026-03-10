<?php
/**
 * GET /api/user/trainers/availability.php
 *
 * Returns available time slots for a specific trainer on a given date.
 * Used in the book-trainer wizard (Step 3) to show/hide unavailable slots.
 *
 * Query params (GET):
 *   trainer_id  int     required
 *   date        string  required  ISO "YYYY-MM-DD"
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "available_slots": ["6:00 AM", "8:00 AM", "12:00 PM", ...],
 *     "unavailable_slots": ["10:00 AM", "6:00 PM"]
 *   }
 *
 * DB tables used (when connected):
 *   trainer_bookings  (trainer_id, booking_date, booking_time, status)
 *   trainer_availability (trainer_id, day_of_week, start_time, end_time)
 */

require_once __DIR__ . '/../../config.php';
require_method('GET');

$trainer_id = sanitize_int($_GET['trainer_id'] ?? 0);
$date       = sanitize_string($_GET['date']     ?? '');

if (!$trainer_id || $trainer_id <= 0) { error('Trainer ID is required.'); }
if (!$date || !strtotime($date))       { error('Valid date is required.'); }
if (strtotime($date) < strtotime('today')) { error('Cannot query availability for past dates.'); }

// ─── TODO: replace stub with real DB logic ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // All possible slots the gym offers
    $all_slots = ['6:00 AM', '8:00 AM', '10:00 AM', '12:00 PM', '2:00 PM', '4:00 PM', '6:00 PM', '8:00 PM'];

    // Fetch booked slots for this trainer on this date
    $stmt = $pdo->prepare('
        SELECT booking_time
        FROM trainer_bookings
        WHERE trainer_id = ? AND booking_date = ? AND status != "cancelled"
    ');
    $stmt->execute([$trainer_id, $date]);
    $booked = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $booked_set = array_flip($booked);

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
*/

// ─── STUB response ────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
