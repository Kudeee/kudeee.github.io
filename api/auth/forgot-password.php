<?php
require_once __DIR__ . '/../config.php';
require_method('POST');

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$email = sanitize_email($body['email'] ?? $_POST['email'] ?? '');

if (!$email) error('Email is required.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) error('Invalid email address.');

$pdo = db();

// Check members table first, then admin_users
$member = null;
$userType = null;

$stmt = $pdo->prepare("SELECT id, first_name, email FROM members WHERE email = ? AND status = 'active' LIMIT 1");
$stmt->execute([$email]);
$member = $stmt->fetch();
if ($member) $userType = 'member';

if (!$member) {
    $stmt = $pdo->prepare("SELECT id, first_name, email FROM admin_users WHERE email = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$email]);
    $member = $stmt->fetch();
    if ($member) $userType = 'admin';
}

// Always return success to prevent email enumeration
if (!$member) {
    success('If that email exists, a reset link has been sent.');
}

// Ensure password_resets table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL,
    token      VARCHAR(64)  NOT NULL UNIQUE,
    user_type  VARCHAR(20)  NOT NULL DEFAULT 'member',
    expires_at DATETIME     NOT NULL,
    used       TINYINT(1)   DEFAULT 0,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Invalidate existing tokens for this email
$pdo->prepare("UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0")->execute([$email]);

// Generate secure token
$token = bin2hex(random_bytes(32));

$pdo->prepare("INSERT INTO password_resets (email, token, user_type, expires_at) VALUES (?, ?, ?, NOW() + INTERVAL 1 HOUR)")
    ->execute([$email, $token, $userType]);

// Build reset URL
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Build base URL from this file's location: api/auth/ is 2 levels deep inside the project root
$projectRoot = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
$resetUrl  = "$protocol://$host$projectRoot/reset-password.php?token=$token";

// Send email via PHP mail()
$firstName = $member['first_name'] ?? 'User';
$subject   = "Society Fitness — Password Reset Request";
$htmlBody  = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='font-family:Segoe UI,Arial,sans-serif;background:#f5f6fb;margin:0;padding:40px 0;'>
  <div style='max-width:520px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);'>
    <div style='background:linear-gradient(135deg,#ff6b35,#ff8c5a);padding:32px 36px;'>
      <h1 style='color:#fff;margin:0;font-size:1.6rem;font-weight:900;text-transform:uppercase;letter-spacing:1px;'>
        Society<span style='color:#1a1a1a;'>Fit</span>
      </h1>
    </div>
    <div style='padding:36px;'>
      <h2 style='color:#1a1a1a;margin:0 0 12px;font-size:1.3rem;'>Password Reset Request</h2>
      <p style='color:#555;line-height:1.7;margin:0 0 24px;'>
        Hi $firstName,<br><br>
        We received a request to reset your password. Click the button below to create a new password.
        This link will expire in <strong>1 hour</strong>.
      </p>
      <div style='text-align:center;margin:28px 0;'>
        <a href='$resetUrl'
           style='display:inline-block;background:linear-gradient(135deg,#ff6b35,#ff8c5a);color:#fff;
                  padding:14px 36px;border-radius:10px;text-decoration:none;font-weight:700;
                  font-size:1rem;letter-spacing:0.5px;'>
          Reset My Password
        </a>
      </div>
      <p style='color:#888;font-size:0.85rem;line-height:1.6;'>
        If you did not request a password reset, you can safely ignore this email.
        Your password will not be changed.
      </p>
      <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0;'>
      <p style='color:#aaa;font-size:0.8rem;'>
        Or copy this link into your browser:<br>
        <span style='color:#ff6b35;word-break:break-all;'>$resetUrl</span>
      </p>
    </div>
  </div>
</body>
</html>";

$textBody = "Society Fitness — Password Reset\n\nHi $firstName,\n\nReset your password here:\n$resetUrl\n\nThis link expires in 1 hour.\n\nIf you didn't request this, ignore this email.";

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Society Fitness <noreply@societyfitness.com>\r\n";
$headers .= "Reply-To: noreply@societyfitness.com\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$mailSent = mail($email, $subject, $htmlBody, $headers);

success('If that email exists, a reset link has been sent.', [
    'dev_token'     => $token,
    'dev_reset_url' => $resetUrl,
    'mail_sent'     => $mailSent,
]);