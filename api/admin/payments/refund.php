<?php
/**
 * POST /api/admin/payments/refund.php
 *
 * Issues a full or partial refund for a completed payment.
 * Restricted to super_admin and admin roles (not staff).
 *
 * Request (POST, form-data):
 *   csrf_token  string   required
 *   payment_id  int      required
 *   amount      float    optional — partial refund amount; defaults to full amount
 *   reason      string   required
 *
 * Response 200:
 *   { "success": true, "message": "Refund issued.", "refund_reference": "REF-..." }
 *
 * DB tables used:
 *   payments, admin_logs
 */

require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin(['admin', 'super_admin']); // staff cannot issue refunds

// ─── Input ────────────────────────────────────────────────────────────────────
$payment_id = sanitize_int($_POST['payment_id'] ?? 0);
$amount_raw = $_POST['amount'] ?? null;
$reason     = sanitize_string($_POST['reason']  ?? '');
$amount     = $amount_raw !== null ? filter_var($amount_raw, FILTER_VALIDATE_FLOAT) : null;

if (!$payment_id || $payment_id < 1)           error('A valid payment ID is required.');
if (!$reason)                                  error('A refund reason is required.');
if ($amount !== null && ($amount === false || $amount <= 0)) {
    error('Refund amount must be a positive number.');
}

// ─── TODO: replace stub with real DB operation ────────────────────────────────
/*
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = ? LIMIT 1');
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment)                           error('Payment not found.', 404);
    if ($payment['status'] === 'refunded')   error('This payment has already been refunded.');
    if ($payment['status'] !== 'completed')  error('Only completed payments can be refunded.');

    $refund_amount = $amount ?? (float) $payment['amount'];
    if ($refund_amount > (float) $payment['amount']) {
        error('Refund amount cannot exceed the original payment amount of ₱' . number_format($payment['amount'], 2) . '.');
    }

    $refund_ref = 'REF-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $is_full    = $refund_amount >= (float) $payment['amount'];

    $pdo->beginTransaction();
    try {
        // Update original payment status
        $pdo->prepare("UPDATE payments SET status = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$is_full ? 'refunded' : 'partial_refund', $payment_id]);

        // Insert refund record
        $pdo->prepare("
            INSERT INTO payments
                (member_id, amount, type, payment_method, reference_number,
                 status, notes, created_at)
            VALUES (?, ?, 'refund', ?, ?, 'completed', ?, NOW())
        ")->execute([
            $payment['member_id'],
            -$refund_amount,
            $payment['payment_method'],
            $refund_ref,
            "Refund for payment #{$payment_id}: {$reason}",
        ]);

        // Log
        $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_type, target_id, notes, created_at)
            VALUES (?, 'issue_refund', 'payment', ?, ?, NOW())
        ")->execute([$admin['admin_id'], $payment_id, "₱{$refund_amount} — {$reason}"]);

        $pdo->commit();
        success('Refund issued successfully.', ['refund_reference' => $refund_ref]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error('Failed to issue refund. Please try again.', 500);
    }
*/

// ─── STUB ─────────────────────────────────────────────────────────────────────
error('Database not connected yet. This endpoint is ready for integration.', 503);
