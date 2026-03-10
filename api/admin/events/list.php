<?php
/**
 * GET /api/admin/events/list.php
 *
 * Returns all events with registration counts.
 *
 * Query params:
 *   search      string   Filter by event name
 *   type        string   Filter by event type
 *   status      string   active | cancelled | completed | draft
 *   date_from   date     YYYY-MM-DD
 *   date_to     date     YYYY-MM-DD
 *   page        int
 *   per_page    int      default 20
 *
 * Response 200:
 *   {
 *     "success": true,
 *     "events": [ { id, name, type, description, event_date, start_time,
 *                   end_time, location, fee, max_attendees,
 *                   current_attendees, spots_left, members_only,
 *                   status, created_at } ],
 *     "pagination": { total, page, per_page, total_pages }
 *   }
 *
 * DB tables used:
 *   events
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('GET');
$admin = require_admin();

// ─── Input ────────────────────────────────────────────────────────────────────
$search = sanitize_string($_GET['search'] ?? '');
$type   = sanitize_string($_GET['type']   ?? '');
$status = sanitize_string($_GET['status'] ?? '');
$date   = get_date_range();
[$offset, $per_page, $page] = get_pagination();

// ─── TODO: replace stub with real DB query ────────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $where  = ['e.event_date BETWEEN :from AND :to'];
    $params = [':from' => $date['from'], ':to' => $date['to']];

    if ($search) { $where[] = 'e.name LIKE :s'; $params[':s'] = '%'.$search.'%'; }
    if ($type)   { $where[] = 'e.type = :type';  $params[':type'] = $type;       }
    if ($status) { $where[] = 'e.status = :st';  $params[':st']   = $status;     }

    $whereSQL = implode(' AND ', $where);

    $count = $pdo->prepare("SELECT COUNT(*) FROM events e WHERE $whereSQL");
    $count->execute($params);
    $total = (int) $count->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT e.*,
               (e.max_attendees - e.current_attendees) AS spots_left
        FROM events e
        WHERE $whereSQL
        ORDER BY e.event_date ASC, e.start_time ASC
        LIMIT :limit OFFSET :offset
    ");
    $params[':limit']  = $per_page;
    $params[':offset'] = $offset;
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    success('Events retrieved.', [
        'events'     => $events,
        'pagination' => [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil($total / $per_page),
        ],
    ]);
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
