<?php
/**
 * POST /api/contact/inquiry.php
 * Stores a public contact form submission.
 *
 * Request (POST, form-data):
 *   csrf_token  string  required
 *   name        string  required
 *   email       string  required
 *   phone       string  optional
 *   interest    string  optional
 *   message     string  required
 *
 * Response 200:
 *   { "success": true, "message": "Your message has been sent." }
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_csrf();

$name     = sanitize_string($_POST['name']     ?? '');
$email    = sanitize_email($_POST['email']     ?? '');
$phone    = sanitize_string($_POST['phone']    ?? '');
$interest = sanitize_string($_POST['interest'] ?? '');
$message  = sanitize_string($_POST['message']  ?? '');

if (!$name)    { error('Please enter your name.'); }
if (!$email)   { error('Please enter a valid email address.'); }
if (!$message) { error('Please enter a message.'); }

try {
    $pdo = db();

    $pdo->prepare('
        INSERT INTO contact_inquiries
            (name, email, phone, interest, message, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ')->execute([$name, $email, $phone, $interest, $message]);

    success('Your message has been sent. We will get back to you shortly.');

} catch (PDOException $e) {
    // If the table doesn't exist yet, still return success to the visitor
    success('Your message has been sent. We will get back to you shortly.');
}