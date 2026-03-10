<?php
/**
 * POST /api/admin/payments/refund.php
 */
require_once __DIR__ . '/../../admin/config.php';
require_method('POST');
require_csrf();
$admin = require_admin(['admin', 'super_admin']);

$payment_id = sanitize_int($_POST['payment_id'] ?? 0);
$amount_raw = $_POST['amount'] ?? null;
$reason     = sanitize_string($_POST['reason']  ?? '');
$amount     = $amount_raw !== null ? filter_var($amount_raw, FILTER_VALIDATE_FLOAT) : null;

if (!$payment_id || $payment_id < 1)     error('A valid payment ID is required.');
if (!$reason)                            error('A refund reason is required.');
if ($amount !== null && ($amount === false || $amount <= 0)) error('Refund amount must be a positive number.');

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = ? LIMIT 1');
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();

    if (!$payment)                           error('Payment not found.', 404);
    if ($payment['status'] === 'refunded')   error('This payment has already been refunded.');
    if ($payment['status'] !== 'completed')  error('Only completed payments can be refunded.');

    $refund_amount = $amount ?? (float) $payment['amount'];
    if ($refund_amount > (float) $payment['amount']) {
        error('Refund amount cannot exceed the original payment of ₱' . number_format($payment['amount'], 2) . '.');
    }

    $refund_ref = 'REF-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

    $pdo->beginTransaction();

    $pdo->prepare("UPDATE payments SET status = 'refunded' WHERE id = ?")->execute([$payment_id]);

    $pdo->prepare("
        INSERT INTO payments (member_id, type, amount, method, transaction_id, reference_id, status, description, created_at)
        VALUES (?, 'refund', ?, ?, ?, ?, 'completed', ?, NOW())
    ")->execute([
        $payment['member_id'],
        $refund_amount,
        $payment['method'],
        $refund_ref,
        $payment_id,
        "Refund for #{$payment['transaction_id']}: {$reason}",
    ]);

    $pdo->prepare("
        INSERT INTO audit_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, 'refund_issued', 'payment', ?, ?, ?, NOW())
    ")->execute([$admin['admin_id'], $payment_id, json_encode(['amount' => $refund_amount, 'reason' => $reason, 'ref' => $refund_ref]), $_SERVER['REMOTE_ADDR'] ?? '']);

    $pdo->commit();
    success('Refund issued successfully.', ['refund_reference' => $refund_ref]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error('Database error: ' . $e->getMessage(), 500);
}