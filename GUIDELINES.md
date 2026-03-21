# Society Fitness — Developer Guidelines

## Project Overview

Society Fitness is a gym management web application built on PHP 8.1 and MariaDB 10.4.32. It serves two audiences: **members** (public-facing portal) and **admin staff** (back-office dashboard). This document describes the database schema, data conventions, and key business rules that all developers must follow when writing queries, building features, or modifying existing code.

---

## Database

- **Database name:** `society_fitness`
- **Engine:** InnoDB (all tables)
- **Charset:** `utf8mb4` / `utf8mb4_unicode_ci`
- **Server:** MariaDB 10.4.32

---

## Table Reference

### `admin_users`

Stores back-office staff accounts. Trainers also have admin_user accounts (linked by matching first/last name).

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `first_name` | varchar(100) | |
| `last_name` | varchar(100) | |
| `email` | varchar(255) | UNIQUE (`uq_admin_email`) |
| `password_hash` | varchar(255) | bcrypt via `password_hash()` |
| `role` | enum | `'staff'`, `'admin'`, `'super_admin'` |
| `status` | enum | `'active'`, `'inactive'` |
| `created_at` | datetime | default `current_timestamp()` |

**Roles and permissions** (enforce in application layer):
- `staff` — read access, can cancel classes and view records
- `admin` — can create/edit trainers, events, schedules
- `super_admin` — full access including user management

**Trainer login:** Trainers log in via their `admin_users` record. The login flow matches the admin user's `first_name` + `last_name` against the `trainers` table to establish a trainer session.

---

### `audit_log`

Immutable record of admin actions. Never update or delete rows from this table.

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `admin_id` | int UNSIGNED NULL | FK → `admin_users.id`; NULL if system-generated |
| `action` | varchar(100) | e.g. `'member_suspended'`, `'trainer_added'`, `'plan_updated'`, `'member_cancelled'` |
| `target_type` | varchar(50) | e.g. `'member'`, `'trainer'`, `'event'`, `'class'`, `'subscription'`, `'plan_config'` |
| `target_id` | int UNSIGNED NULL | ID of the affected record |
| `details` | longtext (JSON) | Must be valid JSON; uses MariaDB `JSON_VALID` constraint. May be NULL. |
| `ip_address` | varchar(45) | Supports IPv6. Defaults to empty string `''`. |
| `created_at` | datetime | default `current_timestamp()` |

**Indexes:** `idx_al_admin` on `admin_id`; `idx_al_target` on `(target_type, target_id)`

---

### `members`

Registered gym members (public portal accounts).

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `first_name` | varchar(100) | |
| `last_name` | varchar(100) | |
| `email` | varchar(255) | UNIQUE (`uq_members_email`) |
| `phone` | varchar(20) | Defaults to `''` |
| `zip` | varchar(10) | Defaults to `''` |
| `password_hash` | varchar(255) | bcrypt |
| `plan` | varchar(50) | `'BASIC PLAN'`, `'PREMIUM PLAN'`, `'VIP PLAN'` |
| `billing_cycle` | enum | `'monthly'`, `'yearly'` |
| `status` | enum | `'active'`, `'suspended'`, `'deleted'` |
| `join_date` | date | The date the member first joined |
| `created_at` | datetime | Row creation timestamp |
| `subscription_recurring` | tinyint(1) | `1` = auto-renew enabled, `0` = disabled. Default `1`. Convenience mirror of the active subscription's `is_recurring`. |

**Important:** `plan` and `billing_cycle` on this table are a denormalized cache of the member's current subscription. The canonical source of truth for plan status is the `subscriptions` table. Keep both in sync when changing plans.

**Soft deletes:** Use `status = 'deleted'` — do not hard-delete member rows, as they are referenced by payments, bookings, and audit records.

---

### `subscriptions`

One subscription row per billing period per member. A member may have multiple rows (e.g., an expired row and an active one).

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `member_id` | int UNSIGNED | FK → `members.id` ON DELETE CASCADE |
| `plan` | varchar(50) | `'BASIC PLAN'`, `'PREMIUM PLAN'`, `'VIP PLAN'` |
| `billing_cycle` | enum | `'monthly'`, `'yearly'` |
| `price` | decimal(10,2) | Amount charged for this period |
| `start_date` | date | |
| `expiry_date` | date | |
| `status` | enum | `'active'`, `'paused'`, `'expired'`, `'cancelled'` |
| `paused_at` | datetime NULL | Set when subscription is paused |
| `resumed_at` | datetime NULL | Set when subscription is resumed |
| `pause_count_days` | int UNSIGNED | Cumulative days the subscription has been paused. Default `0`. |
| `created_at` | datetime | |
| `cancelled_at` | datetime NULL | Set when subscription is cancelled |
| `cancel_reason` | varchar(50) NULL | e.g. `'too_expensive'`, `'not_using'`, `'moving'`, `'health'`, `'switching'`, `'other'` |
| `cancel_note` | text NULL | Free-text note when `cancel_reason = 'other'` |
| `is_recurring` | tinyint(1) | `1` = auto-renew enabled, `0` = disabled. Default `1`. |

**Index:** `idx_sub_member` on `member_id`

**Business rules:**
- A member should have at most one `status = 'active'` subscription at a time.
- When a subscription expires, set `status = 'expired'` and update `members.status` if no new active subscription exists.
- Pause logic: record `paused_at`, increment `pause_count_days` on resume, and update `expiry_date` accordingly.
- Cancellation: set `status = 'cancelled'`, record `cancelled_at`, `cancel_reason`, and `cancel_note`. Also set `members.status = 'suspended'` (member retains access until expiry).

---

### `payments`

Ledger of all financial transactions. Append-only — do not update rows; issue refund rows instead.

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `member_id` | int UNSIGNED | FK → `members.id` ON DELETE CASCADE |
| `type` | enum | `'subscription'`, `'class_booking'`, `'trainer_session'`, `'event'`, `'refund'` |
| `amount` | decimal(10,2) | |
| `method` | varchar(20) | `'gcash'`, `'maya'`, `'card'`, `'gotyme'`. Defaults to `''`. |
| `transaction_id` | varchar(50) | External payment gateway reference. Format: `TXN-YYYYMMDD-NNNNN`. Refunds use `REF-YYYYMMDD-NNNNN`. Defaults to `''`. |
| `reference_id` | int UNSIGNED NULL | booking or registration ID the payment is for |
| `status` | enum | `'pending'`, `'completed'`, `'refunded'`, `'failed'` |
| `description` | varchar(255) | Human-readable label. Defaults to `''`. |
| `created_at` | datetime | |

**Indexes:** `idx_pay_member` on `member_id`; `idx_pay_txn` on `transaction_id`

**Notes:**
- `amount` may be `0.00` for complimentary bookings (e.g., VIP/Premium members with included classes).
- Refunds are recorded as a new row with `type = 'refund'` and the matching amount, not by mutating the original row. The original payment's `status` is set to `'refunded'`.

---

### `plan_configs`

Admin-editable subscription plan configuration. Created dynamically if it does not exist.

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `plan` | varchar(50) | UNIQUE. `'BASIC PLAN'`, `'PREMIUM PLAN'`, `'VIP PLAN'` |
| `monthly_price` | decimal(10,2) | |
| `yearly_price` | decimal(10,2) | |
| `color` | varchar(7) | Hex color for UI. Default `'#ff6b35'`. |
| `is_active` | tinyint(1) | `1` = enabled in member-facing UI. Default `1`. |
| `max_classes` | int | Max group classes per week. `-1` = unlimited. Default `-1`. |
| `pt_sessions` | int | PT sessions included per month. Default `0`. |
| `guest_passes` | int | Guest passes per month. Default `0`. |
| `benefits` | longtext (JSON) | JSON array of benefit strings. Must be valid JSON. |
| `updated_at` | timestamp | Auto-updated on row change. |

**Charset:** `utf8mb4_general_ci` (differs from other tables which use `utf8mb4_unicode_ci`)

**Current plan data (as of seed):**

| Plan | Monthly | Yearly | Color |
|---|---|---|---|
| BASIC PLAN | ₱499 | ₱5,028 | `#9e9e9e` |
| PREMIUM PLAN | ₱899 | ₱9,709 | `#ff6b35` |
| VIP PLAN | ₱1,499 | ₱16,189 | `#f9a825` |

Do not hardcode these prices in application logic — always read from `plan_configs`.

---

### `password_resets`

Stores password reset tokens.

| Column | Type | Notes |
|---|---|---|
| `id` | int AI PK | |
| `email` | varchar(255) | |
| `token` | varchar(64) | UNIQUE |
| `user_type` | varchar(20) | `'member'` or `'admin'`. Default `'member'`. |
| `expires_at` | datetime | Token expiry (1 hour from creation) |
| `used` | tinyint(1) | `0` = unused, `1` = used. Default `0`. |
| `created_at` | datetime | default `current_timestamp()` |

**Charset:** `utf8mb4_general_ci`

**Indexes:** `idx_token` on `token`; `idx_email` on `email`

---

### `trainers`

Trainer profiles used across class scheduling, trainer bookings, and events.

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `first_name` | varchar(100) | |
| `last_name` | varchar(100) | |
| `specialty` | varchar(150) | Short specialty label |
| `bio` | text NULL | Full profile description |
| `image_url` | varchar(255) | Path relative to web root. Defaults to `''`. |
| `exp_years` | tinyint UNSIGNED | Years of experience |
| `client_count` | smallint UNSIGNED | Lifetime clients served |
| `session_rate` | decimal(10,2) | Base rate per 30-minute session unit |
| `rating` | decimal(3,1) | Average rating out of 5.0. Default `5.0`. |
| `availability` | enum | `'available'`, `'limited'` |
| `specialty_tags` | longtext (JSON) | JSON array of tag strings; must pass `JSON_VALID`. May be NULL. |
| `status` | enum | `'active'`, `'inactive'` |
| `created_at` | datetime | |

**Session pricing:** `total_price = session_rate × price_multiplier`, where multiplier is stored in `trainer_bookings`. Standard multipliers: `1.0` (30 min), `1.5` (60 min), `2.0` (90 min).

Note: The seed data also uses `2.0` and `3.0` for 60/90 min sessions in older records — the application layer controls this, not the DB.

---

### `trainer_availability`

Trainer-managed slot availability grid (open/blocked by date and time).

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `trainer_id` | int UNSIGNED | FK → `trainers.id` |
| `slot_date` | date | |
| `slot_time` | varchar(10) | 24h format `HH:MM` |
| `is_open` | tinyint(1) | `1` = open for booking, `0` = blocked. Default `1`. |
| `created_at` | datetime | default `current_timestamp()` |
| `updated_at` | datetime | auto-updated on change |

**Unique constraint:** `uq_ta_slot` on `(trainer_id, slot_date, slot_time)`

**Indexes:** `idx_ta_trainer` on `trainer_id`; `idx_ta_date` on `slot_date`

This table is created dynamically by the availability API if it does not yet exist.

---

### `trainer_bookings`

One-on-one personal training session bookings.

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `member_id` | int UNSIGNED | FK → `members.id` ON DELETE CASCADE |
| `trainer_id` | int UNSIGNED | FK → `trainers.id` |
| `session_duration` | varchar(20) | Display label: `'30 Min'`, `'60 Min'`, `'90 Min'`. Default `'30 Min'`. |
| `session_minutes` | tinyint UNSIGNED | Numeric: `30`, `60`, `90`. Default `30`. |
| `price_multiplier` | decimal(3,1) | Multiplied against `trainers.session_rate`. Default `1.0`. |
| `focus_area` | varchar(50) | e.g. `'strength_training'`, `'weight_loss'`, `'muscle_building'`, `'flexibility'`, `'cardio'`, `'endurance'`, `'general_fitness'`, `'core'`, `'combat'`. Defaults to `''`. |
| `booking_date` | date | |
| `booking_time` | varchar(20) | e.g. `'8:00 AM'` |
| `total_price` | decimal(10,2) | Pre-computed: `session_rate × price_multiplier` |
| `fitness_goals` | text NULL | Member-provided goals |
| `fitness_level` | enum | `'beginner'`, `'intermediate'`, `'advanced'`. Default `'beginner'`. |
| `medical_info` | text NULL | Health disclosures |
| `recurring` | tinyint(1) | `0` or `1`. Default `0`. |
| `payment_method` | varchar(20) | Defaults to `''`. |
| `status` | enum | `'confirmed'`, `'cancelled'`, `'completed'` |
| `created_at` | datetime | |
| `rescheduled_at` | datetime NULL | Timestamp of the last reschedule action |
| `rescheduled_from_date` | date NULL | Original booking date before first reschedule |
| `rescheduled_from_time` | varchar(20) NULL | Original booking time before first reschedule |

**Indexes:** `idx_tb_member` on `member_id`; `idx_tb_trainer` on `trainer_id`; `idx_tb_date` on `(booking_date, booking_time)`

---

### `class_schedules`

Scheduled group fitness sessions. Each row is a single occurrence.

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `class_name` | varchar(100) | e.g. `'Strength Training'`, `'Zumba Party'`, `'CrossFit WOD'`, `'HIIT Blast'`, `'HIIT Circuit'`, `'Yoga Flow'`, `'Morning Yoga'`, `'Yoga & Stretch'`, `'Mobility & Recovery'`, `'Pilates Core'`, `'Kickboxing'`, `'Bodybuilding Basics'`, `'Muay Thai Cardio'`, `'Zumba Saturday'`, `'Open CrossFit'` |
| `trainer_id` | int UNSIGNED | FK → `trainers.id` |
| `scheduled_at` | datetime | Date and time of the session |
| `duration_minutes` | smallint UNSIGNED | Default 60; HIIT sessions often 45 |
| `max_participants` | tinyint UNSIGNED | Capacity cap |
| `current_participants` | tinyint UNSIGNED | Incremented on booking, decremented on cancellation |
| `location` | varchar(100) | Default `'Main Studio'` |
| `status` | enum | `'active'`, `'cancelled'` |
| `created_at` | datetime | |

**Indexes:** `idx_cs_trainer` on `trainer_id`; `idx_cs_scheduled` on `scheduled_at`

**Known locations:** Main Studio, Studio B, Weight Room, Functional Zone, Outdoor Area

---

### `class_bookings`

A member's registration for a specific class schedule.

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `member_id` | int UNSIGNED | FK → `members.id` ON DELETE CASCADE |
| `class_schedule_id` | int UNSIGNED | FK → `class_schedules.id` |
| `booking_date` | date | Denormalized from `class_schedules.scheduled_at` |
| `booking_time` | varchar(20) | Denormalized time string |
| `class_name` | varchar(100) | Denormalized class name |
| `special_requirements` | text NULL | Accessibility or health notes |
| `emergency_name` | varchar(100) | Emergency contact name. Defaults to `''`. |
| `emergency_phone` | varchar(20) | Emergency contact phone. Defaults to `''`. |
| `payment_method` | varchar(20) | Defaults to `''`. |
| `status` | enum | `'confirmed'`, `'cancelled'`, `'attended'` |
| `created_at` | datetime | |

**Indexes:** `idx_cb_member` on `member_id`; `idx_cb_schedule` on `class_schedule_id`

**On booking:** increment `class_schedules.current_participants`. On cancellation: decrement it (using `GREATEST(current_participants - 1, 0)`). Never allow booking when `current_participants >= max_participants`.

**Cancellation policy:** Members must cancel at least 24 hours before the class. Paid bookings (Basic Plan: ₱200) are automatically refunded.

---

### `events`

Special gym events (challenges, workshops, seminars, competitions, wellness sessions, trial classes).

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `name` | varchar(150) | |
| `type` | varchar(50) | `'challenge'`, `'workshop'`, `'seminar'`, `'competition'`, `'wellness'`, `'trial'`, `'fitness_challenge'`. Default `'general'`. |
| `event_date` | date | |
| `event_time` | time | |
| `location` | varchar(100) | Defaults to `''`. |
| `fee` | decimal(10,2) | `0.00` for free events |
| `max_attendees` | smallint UNSIGNED | Default `50`. |
| `current_attendees` | smallint UNSIGNED | Incremented on registration. Default `0`. |
| `is_members_only` | tinyint(1) | `1` = restricted to active members. Default `0`. |
| `organizer_id` | int UNSIGNED NULL | FK → `trainers.id` ON DELETE SET NULL |
| `description` | text NULL | |
| `status` | enum | `'active'`, `'cancelled'` |
| `created_at` | datetime | |

**Indexes:** `idx_ev_date` on `event_date`; `fk_ev_organizer` on `organizer_id`

---

### `event_registrations`

A member's registration for an event.

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `event_id` | int UNSIGNED | FK → `events.id` ON DELETE CASCADE |
| `member_id` | int UNSIGNED | FK → `members.id` ON DELETE CASCADE |
| `payment_method` | varchar(20) NULL | NULL for free events |
| `amount_paid` | decimal(10,2) | `0.00` for free events |
| `status` | enum | `'registered'`, `'cancelled'`, `'attended'` |
| `registered_at` | datetime | |

**Unique constraint:** `uq_er_event_member` on `(event_id, member_id)` — a member can register for an event only once.

---

### `contact_inquiries`

Inbound messages from the public contact form. Read-only from the member side.

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `name` | varchar(150) | |
| `email` | varchar(255) | |
| `phone` | varchar(30) | Defaults to `''`. May be empty. |
| `interest` | varchar(100) | e.g. `'membership'`, `'classes'`, `'personal training'`, `'personal_training'`, `'general'`. Defaults to `''`. |
| `message` | text | |
| `created_at` | datetime | |

---

## Entity Relationship Summary

```
trainers ──< class_schedules ──< class_bookings >── members
trainers ──< trainer_bookings >── members
trainers ──< trainer_availability
trainers ──< events ──< event_registrations >── members
members ──< subscriptions
members ──< payments
admin_users ──< audit_log
plan_configs (standalone)
password_resets (standalone)
```

---

## Membership Plans

Always read prices from `plan_configs` — do not hardcode in application logic.

| Plan | Monthly Price | Yearly Price | Notes |
|---|---|---|---|
| BASIC PLAN | ₱499 | ₱5,028 | Class booking fee: ₱200/class |
| PREMIUM PLAN | ₱899 | ₱9,709 | Classes included free |
| VIP PLAN | ₱1,499 | ₱16,189 | Classes included free |

**Class booking fee rule:** Basic Plan members pay ₱200 per class booking. Premium and VIP members book for free (amount stored as `0.00`).

---

## Payment Methods

Accepted values: `'gcash'`, `'maya'`, `'card'`, `'gotyme'`. Store lowercase. Handle validation in the application layer.

---

## Data Conventions

- **Passwords:** Always hashed with PHP `password_hash()` using `PASSWORD_BCRYPT`. Never store or log plaintext passwords.
- **Timestamps:** All `created_at` fields default to `current_timestamp()`. Do not override on insert.
- **Soft deletes:** Members use `status = 'deleted'`. Trainers and admin users use `status = 'inactive'`. Hard deletes are reserved for truly orphaned or test data.
- **JSON columns:** `audit_log.details`, `trainers.specialty_tags`, `plan_configs.benefits` use `longtext` with a `JSON_VALID()` check constraint. Always validate JSON before inserting. `audit_log.details` may be NULL.
- **Denormalization:** `class_bookings` stores `booking_date`, `booking_time`, and `class_name` copied from `class_schedules` at booking time. These are snapshots — do not update them if the schedule changes after booking.
- **Transaction IDs:** Follow the format `TXN-YYYYMMDD-NNNNN` (e.g., `TXN-20260311-00042`). Refund IDs use `REF-YYYYMMDD-NNNNN`. Generated at payment time.
- **Recurring subscriptions:** `subscriptions.is_recurring` and `members.subscription_recurring` mirror each other. Update both when toggling auto-renew.
- **Timezone:** Application uses `Asia/Manila` (PST, UTC+8). Set via `date_default_timezone_set('Asia/Manila')` in `config.php`.

---

## Foreign Key Cascade Behaviour

| Child Table | FK Column | Parent | On Delete |
|---|---|---|---|
| `class_bookings` | `member_id` | `members` | CASCADE |
| `class_bookings` | `class_schedule_id` | `class_schedules` | RESTRICT |
| `class_schedules` | `trainer_id` | `trainers` | RESTRICT |
| `events` | `organizer_id` | `trainers` | SET NULL |
| `event_registrations` | `event_id` | `events` | CASCADE |
| `event_registrations` | `member_id` | `members` | CASCADE |
| `payments` | `member_id` | `members` | CASCADE |
| `subscriptions` | `member_id` | `members` | CASCADE |
| `trainer_bookings` | `member_id` | `members` | CASCADE |
| `trainer_bookings` | `trainer_id` | `trainers` | RESTRICT |
| `trainer_availability` | `trainer_id` | `trainers` | (no explicit constraint — check before deleting) |
| `audit_log` | `admin_id` | `admin_users` | (no constraint — nullable) |

RESTRICT means the parent cannot be deleted while child rows exist. Plan deletions accordingly (cancel/reassign before removing a trainer, for example). When permanently deleting a trainer (`super_admin` only), the API nullifies `class_schedules.trainer_id` and `events.organizer_id` first.

---

## Common Query Patterns

**Get a member's active subscription:**
```sql
SELECT * FROM subscriptions
WHERE member_id = ? AND status = 'active'
ORDER BY created_at DESC
LIMIT 1;
```

**Check class availability before booking:**
```sql
SELECT (max_participants - current_participants) AS spots_remaining
FROM class_schedules
WHERE id = ? AND status = 'active';
```

**Get all upcoming trainer bookings for a member:**
```sql
SELECT tb.*, CONCAT(t.first_name, ' ', t.last_name) AS trainer_name
FROM trainer_bookings tb
JOIN trainers t ON t.id = tb.trainer_id
WHERE tb.member_id = ? AND tb.booking_date >= CURDATE() AND tb.status = 'confirmed'
ORDER BY tb.booking_date, tb.booking_time;
```

**Full payment history for a member:**
```sql
SELECT * FROM payments
WHERE member_id = ?
ORDER BY created_at DESC;
```

**Members-only event check:**
```sql
SELECT e.*
FROM events e
WHERE e.status = 'active'
  AND (e.is_members_only = 0 OR EXISTS (
    SELECT 1 FROM members m
    WHERE m.id = ? AND m.status = 'active'
  ))
ORDER BY e.event_date;
```

**Find trainer by admin user name (for trainer login):**
```sql
SELECT * FROM trainers
WHERE LOWER(TRIM(first_name)) = LOWER(TRIM(?))
  AND LOWER(TRIM(last_name)) = LOWER(TRIM(?))
  AND status = 'active'
LIMIT 1;
```

**Get plan config:**
```sql
SELECT * FROM plan_configs ORDER BY monthly_price ASC;
```

---

## Seed Data Summary

The database ships with the following seed data:

- **14 admin users** — 1 `super_admin`, 1 `admin`, 12 `staff` (trainers also have staff accounts)
- **22 members** — mix of BASIC, PREMIUM, and VIP plans; monthly and yearly billing; some suspended
- **11 trainers** — covering Yoga & Pilates, HIIT & CrossFit, Strength & Conditioning, Weight Loss & Nutrition, Functional Training, Zumba & Dance Fitness, Bodybuilding, Sports Performance, Rehabilitation & Mobility, Beginner Fitness, and Zumba (Charles Laceda)
- **39 class schedules** — spanning March 11–22, 2026
- **29 class bookings** — confirmed, cancelled, and attended statuses
- **22 trainer bookings** — confirmed, cancelled, and completed statuses; includes reschedule data
- **7 events** — challenges, workshops, seminars, competitions, wellness, trial classes (1 cancelled)
- **25 event registrations** — free and paid
- **81 payment records** — subscriptions, class bookings, trainer sessions, event payments, refunds
- **31 subscription records** — active, expired, and cancelled
- **11 audit log entries** — member actions, trainer additions, class cancellations, plan updates
- **5 contact inquiries**
- **18 password reset tokens** (all used/expired)
- **3 plan_configs** — BASIC, PREMIUM, VIP