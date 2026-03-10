<?php
/**
 * GET /api/user/schedule/list.php
 *
 * Returns the weekly class schedule, optionally filtered.
 * Also used to populate available time slots on the book-class page.
 *
 * Query params (GET):
 *   class_type  string  optional  e.g. "HIIT Training"
 *   trainer     string  optional  trainer name
 *   time_range  string  optional  "morning" | "afternoon" | "evening"
 *   date_from   string  optional  ISO "YYYY-MM-DD" (default: today)
 *   date_to     string  optional  ISO "YYYY-MM-DD" (default: +7 days)
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "classes": [
 *       {
 *         id, class_name, trainer_name, trainer_id,
 *         scheduled_at, duration_minutes, location,
 *         max_participants, current_participants, spots_left,
 *         is_full, is_booked_by_me
 *       }
 *     ]
 *   }
 *
 * DB tables used (when connected):
 *   class_schedules  (id, class_name, trainer_id, scheduled_at, duration_minutes,
 *                     max_participants, current_participants, location, status)
 *   trainers         (id, name)
 *   class_bookings   (id, member_id, class_schedule_id, status)
 */

require_once __DIR__ . '/../../config.php';
require_method('GET');

// Schedule is publicly readable; member check is optional (for "is_booked_by_me")
$current_member_id = is_logged_in() ? (int)$_SESSION['member_id'] : null;

// ─── Filters ─────────────────────────────────────────────────────────────────

$class_type = sanitize_string($_GET['class_type'] ?? '');
$trainer    = sanitize_string($_GET['trainer']    ?? '');
$time_range = sanitize_string($_GET['time_range'] ?? '');
$date_from  = sanitize_string($_GET['date_from']  ?? date('Y-m-d'));
$date_to    = sanitize_string($_GET['date_to']    ?? date('Y-m-d', strtotime('+7 days')));

// Validate dates
if (!strtotime($date_from)) { $date_from = date('Y-m-d'); }
if (!strtotime($date_to))   { $date_to   = date('Y-m-d', strtotime('+7 days')); }

// Time range to hour boundaries
$hour_min = 0;
$hour_max = 23;
switch ($time_range) {
    case 'morning':   $hour_min = 5;  $hour_max = 11; break;
    case 'afternoon': $hour_min = 12; $hour_max = 16; break;
    case 'evening':   $hour_min = 17; $hour_max = 23; break;
}

// ─── TODO: replace stub with real DB logic ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $conditions = [
        "cs.status = 'active'",
        "DATE(cs.scheduled_at) BETWEEN ? AND ?",
        "HOUR(cs.scheduled_at) BETWEEN ? AND ?"
    ];
    $params = [$date_from, $date_to, $hour_min, $hour_max];

    if ($class_type) {
        $conditions[] = 'cs.class_name = ?';
        $params[]     = $class_type;
    }
    if ($trainer) {
        $conditions[] = 't.name LIKE ?';
        $params[]     = '%' . $trainer . '%';
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    $stmt = $pdo->prepare("
        SELECT
            cs.id,
            cs.class_name,
            t.name       AS trainer_name,
            t.id         AS trainer_id,
            cs.scheduled_at,
            cs.duration_minutes,
            cs.location,
            cs.max_participants,
            cs.current_participants,
            (cs.max_participants - cs.current_participants) AS spots_left,
            (cs.current_participants >= cs.max_participants) AS is_full
        FROM class_schedules cs
        JOIN trainers t ON t.id = cs.trainer_id
        $where
        ORDER BY cs.scheduled_at ASC
    ");
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark which classes the current member has already booked
    if ($current_member_id && count($classes)) {
        $ids = array_column($classes, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $booked_stmt = $pdo->prepare("
            SELECT class_schedule_id
            FROM class_bookings
            WHERE member_id = ? AND class_schedule_id IN ($placeholders) AND status = 'confirmed'
        ");
        $booked_stmt->execute(array_merge([$current_member_id], $ids));
        $booked_ids = $booked_stmt->fetchAll(PDO::FETCH_COLUMN);
        $booked_set = array_flip($booked_ids);

        foreach ($classes as &$cls) {
            $cls['is_booked_by_me'] = isset($booked_set[$cls['id']]);
        }
        unset($cls);
    } else {
        foreach ($classes as &$cls) { $cls['is_booked_by_me'] = false; }
        unset($cls);
    }

    success('OK', ['classes' => $classes]);
*/

// ─── STUB response ────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
