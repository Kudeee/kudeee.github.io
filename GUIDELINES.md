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

Stores back-office staff accounts.

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

---

### `audit_log`

Immutable record of admin actions. Never update or delete rows from this table.

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `admin_id` | int UNSIGNED NULL | FK → `admin_users.id`; NULL if system-generated |
| `action` | varchar(100) | e.g. `'member_suspended'`, `'trainer_added'` |
| `target_type` | varchar(50) | e.g. `'member'`, `'trainer'`, `'event'`, `'class'` |
| `target_id` | int UNSIGNED NULL | ID of the affected record |
| `details` | longtext (JSON) | Must be valid JSON; use MariaDB `JSON_VALID` constraint |
| `ip_address` | varchar(45) | Supports IPv6 |
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
| `phone` | varchar(20) | |
| `zip` | varchar(10) | |
| `password_hash` | varchar(255) | bcrypt |
| `plan` | varchar(50) | `'BASIC PLAN'`, `'PREMIUM PLAN'`, `'VIP PLAN'` |
| `billing_cycle` | enum | `'monthly'`, `'yearly'` |
| `status` | enum | `'active'`, `'suspended'`, `'deleted'` |
| `join_date` | date | The date the member first joined |
| `created_at` | datetime | Row creation timestamp |

**Important:** `plan` and `billing_cycle` on this table are a denormalized cache of the member's current subscription. The canonical source of truth for plan status is the `subscriptions` table. Keep both in sync when changing plans.

**Soft deletes:** Use `status = 'deleted'` — do not hard-delete member rows, as they are referenced by payments, bookings, and audit records (CASCADE is set only for active foreign keys; the status flag is the soft-delete mechanism at the application layer).

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
| `pause_count_days` | int UNSIGNED | Cumulative days the subscription has been paused |
| `created_at` | datetime | |

**Index:** `idx_sub_member` on `member_id`

**Business rules:**
- A member should have at most one `status = 'active'` subscription at a time.
- When a subscription expires, set `status = 'expired'` and update `members.status` if no new active subscription exists.
- Pause logic: record `paused_at`, increment `pause_count_days` on resume, and update `expiry_date` accordingly.

---

### `payments`

Ledger of all financial transactions. Append-only — do not update rows; issue refund rows instead.

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `member_id` | int UNSIGNED | FK → `members.id` ON DELETE CASCADE |
| `type` | enum | `'subscription'`, `'class_booking'`, `'trainer_session'`, `'event'`, `'refund'` |
| `amount` | decimal(10,2) | |
| `method` | varchar(20) | `'gcash'`, `'maya'`, `'card'`, `'gotyme'`, etc. |
| `transaction_id` | varchar(50) | External payment gateway reference; format: `TXN-YYYYMMDD-NNNNN` |
| `reference_id` | int UNSIGNED NULL | booking or registration ID the payment is for |
| `status` | enum | `'pending'`, `'completed'`, `'refunded'`, `'failed'` |
| `description` | varchar(255) | Human-readable label, e.g. `'VIP PLAN — Yearly'` |
| `created_at` | datetime | |

**Indexes:** `idx_pay_member` on `member_id`; `idx_pay_txn` on `transaction_id`

**Notes:**
- `amount` may be `0.00` for complimentary bookings (e.g., VIP/Premium members with included classes).
- Refunds are recorded as a new row with `type = 'refund'` and a negative or matching amount, not by mutating the original row.

---

### `trainers`

Trainer profiles used across class scheduling, trainer bookings, and events.

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `first_name` | varchar(100) | |
| `last_name` | varchar(100) | |
| `specialty` | varchar(150) | Short specialty label, e.g. `'Strength & Powerlifting'` |
| `bio` | text NULL | Full profile description |
| `image_url` | varchar(255) | Path relative to web root, e.g. `/assets/trainers/marco.jpg` |
| `exp_years` | tinyint UNSIGNED | Years of experience |
| `client_count` | smallint UNSIGNED | Lifetime clients served |
| `session_rate` | decimal(10,2) | Base rate per 30-minute session unit |
| `rating` | decimal(3,1) | Average rating out of 5.0 |
| `availability` | enum | `'available'`, `'limited'` |
| `specialty_tags` | longtext (JSON) | JSON array of tag strings; must pass `JSON_VALID` |
| `status` | enum | `'active'`, `'inactive'` |
| `created_at` | datetime | |

**Session pricing:** `total_price = session_rate × price_multiplier`, where multiplier is stored in `trainer_bookings`. Standard multipliers are `1.0` (30 min), `2.0` (60 min), `3.0` (90 min).

---

### `trainer_bookings`

One-on-one personal training session bookings.

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `member_id` | int UNSIGNED | FK → `members.id` ON DELETE CASCADE |
| `trainer_id` | int UNSIGNED | FK → `trainers.id` |
| `session_duration` | varchar(20) | Display label: `'30 Min'`, `'60 Min'`, `'90 Min'` |
| `session_minutes` | tinyint UNSIGNED | Numeric: `30`, `60`, `90` |
| `price_multiplier` | decimal(3,1) | Multiplied against `trainers.session_rate` |
| `focus_area` | varchar(50) | e.g. `'Strength'`, `'Fat Loss'`, `'Cardio'` |
| `booking_date` | date | |
| `booking_time` | varchar(20) | e.g. `'8:00 AM'` |
| `total_price` | decimal(10,2) | Pre-computed: `session_rate × price_multiplier` |
| `fitness_goals` | text NULL | Member-provided goals |
| `fitness_level` | enum | `'beginner'`, `'intermediate'`, `'advanced'` |
| `medical_info` | text NULL | Health disclosures |
| `recurring` | tinyint(1) | Boolean: `0` or `1` |
| `payment_method` | varchar(20) | |
| `status` | enum | `'confirmed'`, `'cancelled'`, `'completed'` |
| `created_at` | datetime | |

**Indexes:** `idx_tb_member`, `idx_tb_trainer`, `idx_tb_date` on `(booking_date, booking_time)`

---

### `class_schedules`

Scheduled group fitness sessions. Each row is a single occurrence (not a recurring template).

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `class_name` | varchar(100) | e.g. `'Strength Training'`, `'Zumba Party'` |
| `trainer_id` | int UNSIGNED | FK → `trainers.id` |
| `scheduled_at` | datetime | Date and time of the session |
| `duration_minutes` | smallint UNSIGNED | Default 60; HIIT sessions often 45 |
| `max_participants` | tinyint UNSIGNED | Capacity cap |
| `current_participants` | tinyint UNSIGNED | Incremented on booking, decremented on cancellation |
| `location` | varchar(100) | e.g. `'Main Studio'`, `'Studio B'`, `'Weight Room'`, `'Functional Zone'` |
| `status` | enum | `'active'`, `'cancelled'` |
| `created_at` | datetime | |

**Indexes:** `idx_cs_trainer` on `trainer_id`; `idx_cs_scheduled` on `scheduled_at`

**Known locations:** Main Studio, Studio B, Weight Room, Functional Zone

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
| `emergency_name` | varchar(100) | Emergency contact name |
| `emergency_phone` | varchar(20) | Emergency contact phone |
| `payment_method` | varchar(20) | |
| `status` | enum | `'confirmed'`, `'cancelled'`, `'attended'` |
| `created_at` | datetime | |

**Indexes:** `idx_cb_member` on `member_id`; `idx_cb_schedule` on `class_schedule_id`

**On booking:** increment `class_schedules.current_participants`. On cancellation: decrement it. Never allow booking when `current_participants >= max_participants`.

---

### `events`

Special gym events (challenges, workshops, seminars, competitions, wellness sessions, trial classes).

| Column | Type | Notes |
|---|---|---|
| `id` | int UNSIGNED AI PK | |
| `name` | varchar(150) | |
| `type` | varchar(50) | `'challenge'`, `'workshop'`, `'seminar'`, `'competition'`, `'wellness'`, `'trial'` |
| `event_date` | date | |
| `event_time` | time | |
| `location` | varchar(100) | |
| `fee` | decimal(10,2) | `0.00` for free events |
| `max_attendees` | smallint UNSIGNED | |
| `current_attendees` | smallint UNSIGNED | Incremented on registration |
| `is_members_only` | tinyint(1) | `1` = restricted to active members |
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
| `phone` | varchar(30) | May be empty |
| `interest` | varchar(100) | e.g. `'membership'`, `'classes'`, `'personal training'`, `'general'` |
| `message` | text | |
| `created_at` | datetime | |

---

## Entity Relationship Summary

```
trainers ──< class_schedules ──< class_bookings >── members
trainers ──< trainer_bookings >── members
trainers ──< events ──< event_registrations >── members
members ──< subscriptions
members ──< payments
admin_users ──< audit_log
```

---

## Membership Plans

| Plan | Monthly Price | Yearly Price |
|---|---|---|
| BASIC PLAN | ₱499 | ₱5,028 |
| PREMIUM PLAN | ₱899 | ₱9,067 (approx.) |
| VIP PLAN | ₱1,500 | ₱15,120 |

These prices are stored per-row in `subscriptions.price` and are not fixed in the schema — do not hardcode them in application logic. Always read from the `subscriptions` row.

---

## Payment Methods

Accepted payment method values stored in `varchar` fields: `'gcash'`, `'maya'`, `'card'`, `'gotyme'`. Store lowercase. The list may expand — avoid hardcoding validation enums in the database; handle in the application layer.

---

## Data Conventions

- **Passwords:** Always hashed with PHP `password_hash()` using `PASSWORD_BCRYPT`. Never store or log plaintext passwords.
- **Timestamps:** All `created_at` fields default to `current_timestamp()`. Do not override on insert.
- **Soft deletes:** Members use `status = 'deleted'`. Trainers and admin users use `status = 'inactive'`. Hard deletes are reserved for truly orphaned or test data.
- **JSON columns:** `audit_log.details` and `trainers.specialty_tags` use `longtext` with a `JSON_VALID()` check constraint. Always validate JSON before inserting.
- **Denormalization:** `class_bookings` stores `booking_date`, `booking_time`, and `class_name` copied from `class_schedules` at booking time. These are snapshots — do not update them if the schedule changes after booking.
- **Transaction IDs:** Follow the format `TXN-YYYYMMDD-NNNNN` (e.g., `TXN-20260311-00042`). Generate at payment time.

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
| `audit_log` | `admin_id` | `admin_users` | (no constraint — nullable) |

RESTRICT means the parent cannot be deleted while child rows exist. Plan deletions accordingly (cancel/reassign before removing a trainer, for example).

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

---

## Seed Data Summary

The database ships with the following seed data for development and testing:

- **3 admin users** — `super_admin`, `admin`, `staff` roles (all share the same bcrypt hash in seed)
- **12 members** — mix of BASIC, PREMIUM, and VIP plans; monthly and yearly billing
- **8 trainers** — covering Strength, Yoga, HIIT, Zumba, Bodybuilding, Pilates, CrossFit, Kickboxing
- **38 class schedules** — spanning March 11–22, 2026
- **16 class bookings** — confirmed and attended statuses
- **11 trainer bookings** — confirmed and completed statuses
- **6 events** — challenges, workshops, seminars, competitions, wellness, trial classes
- **22 event registrations** — free and paid
- **35 payment records** — subscriptions, class bookings, trainer sessions, event payments
- **13 subscription records** — active and one expired
- **5 audit log entries** — member actions, trainer additions, class cancellation
- **4 contact inquiries**
