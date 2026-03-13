<?php
require_once __DIR__ . '/../../config.php';
require_method('POST');

$name     = sanitize_string($_POST['name']     ?? '');
$email    = sanitize_email($_POST['email']     ?? '');
$phone    = sanitize_string($_POST['phone']    ?? '');
$interest = sanitize_string($_POST['interest'] ?? '');
$message  = sanitize_string($_POST['message']  ?? '');

if (!$name || !$email) error('Name and email are required.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) error('Invalid email address.');

$stmt = db()->prepare("
    INSERT INTO contact_inquiries (name, email, phone, interest, message)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$name, $email, $phone, $interest, $message]);

success('Message sent successfully.');