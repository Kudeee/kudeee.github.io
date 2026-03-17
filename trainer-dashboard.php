<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/trainer-config.php';

// Allow access if trainer_id is set OR if admin is logged in and has a trainer session
if (!isset($_SESSION['trainer_id'])) {
    // Check if they just logged in as admin but also have trainer role
    if (isset($_SESSION['admin_id'])) {
        // Try to find their trainer record by name
        $pdo = db();
        $admin_name = $_SESSION['admin_name'] ?? '';
        $parts = explode(' ', $admin_name, 2);
        if (count($parts) === 2) {
            $stmt = $pdo->prepare(
                "SELECT id, specialty, image_url FROM trainers
                 WHERE LOWER(TRIM(first_name)) = LOWER(TRIM(?))
                 AND LOWER(TRIM(last_name)) = LOWER(TRIM(?))
                 AND status = 'active'
                 LIMIT 1"
            );
            $stmt->execute([trim($parts[0]), trim($parts[1])]);
            $trainer = $stmt->fetch();
            if ($trainer) {
                $_SESSION['trainer_id']        = (int)$trainer['id'];
                $_SESSION['trainer_name']      = $admin_name;
                $_SESSION['trainer_specialty'] = $trainer['specialty'];
                $_SESSION['trainer_image']     = $trainer['image_url'];
            }
        }
    }
    // If still no trainer_id, redirect to login
    if (!isset($_SESSION['trainer_id'])) {
        header('Location: login-page.php');
        exit;
    }
}

$trainer_id        = $_SESSION['trainer_id']       ?? 0;
$trainer_name      = $_SESSION['trainer_name']      ?? 'Trainer';
$trainer_specialty = $_SESSION['trainer_specialty'] ?? '';
$trainer_role      = 'trainer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Trainer Dashboard — Society Fitness</title>
  <link rel="stylesheet" href="css/GENERAL-LAYOUT.css" />
  <link rel="stylesheet" href="css/trainer-dashboard.css" />
</head>
<body>

  <!-- ─── SIDEBAR ──────────────────────────────────────────────────── -->
  <div class="sidebar">
    <div class="sidebar-brand">
      <h2>Society<span>Fit</span></h2>
      <p class="sidebar-role">Trainer Portal</p>
    </div>

    <nav class="nav">
      <a class="active" data-page="dashboard">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
          <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
        </svg>
        Dashboard
      </a>
      <a data-page="bookings">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        My Bookings
      </a>
      <a data-page="availability">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
        Availability
      </a>
      <a data-page="members">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        My Members
      </a>
      <a data-page="earnings">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="12" y1="1" x2="12" y2="23"/>
          <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
        </svg>
        Earnings
      </a>
      <a data-page="profile">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
        My Profile
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="trainer-info-box">
        <div class="trainer-avatar-sidebar" id="sidebarAvatar">
          <?= strtoupper(substr($trainer_name, 0, 1)) ?>
        </div>
        <div>
          <p class="trainer-sidebar-name" id="sidebarName"><?= htmlspecialchars($trainer_name) ?></p>
          <p class="trainer-sidebar-spec" id="sidebarSpec"><?= htmlspecialchars($trainer_specialty) ?></p>
        </div>
      </div>
      <button class="logout-btn" onclick="trainerLogout()">Logout</button>
    </div>
  </div>

  <!-- ─── MAIN CONTENT ──────────────────────────────────────────────── -->
  <div class="main" id="mainContent">
    <div class="loading"><div class="spinner"></div> Loading…</div>
  </div>

  <!-- ─── TOAST ─────────────────────────────────────────────────────── -->
  <div id="trainerToast"></div>

  <script>
    window.TRAINER_ID        = <?= json_encode($trainer_id) ?>;
    window.TRAINER_NAME      = <?= json_encode($trainer_name) ?>;
    window.TRAINER_SPECIALTY = <?= json_encode($trainer_specialty) ?>;
  </script>
  <script src="js/trainer-dashboard.js"></script>
</body>
</html>