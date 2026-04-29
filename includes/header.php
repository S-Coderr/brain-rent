<?php
// =============================================
// includes/header.php
// Bootstrap 5 navbar — include at top of every page
// Usage: include __DIR__.'/../includes/header.php';
// =============================================

require_once __DIR__ . '/../config/auth.php';
$user  = currentUser();
$title = $title ?? 'BrainRent';
$uri = $_SERVER['REQUEST_URI'] ?? '';
$bodyClass = (strpos($uri, '/admin/') !== false) ? 'admin-page' : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?> — BrainRent</title>
  <meta name="app-url" content="<?= APP_URL ?>">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <link href="<?= APP_URL ?>/assets/css/custom.css?v=<?= filemtime(__DIR__ . '/../assets/css/custom.css') ?>" rel="stylesheet">
</head>

<body<?= $bodyClass ? ' class="' . $bodyClass . '"' : '' ?>>

  <!-- ======= NAVBAR ======= -->
  <nav class="navbar navbar-expand-lg navbar-light br-navbar sticky-top">
    <div class="container">

      <!-- Brand -->
      <a class="navbar-brand d-flex align-items-center gap-2" href="<?= APP_URL ?>/pages/index.php">
        <span class="br-logo-icon">🧠</span>
        <span class="br-brand">Brain<span class="text-warning">Rent</span></span>
      </a>

      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <?php if ($user): ?>
            <?php if ($user['user_type'] === 'admin'): ?>
              <li class="nav-item">
                <a class="nav-link" href="<?= APP_URL ?>/admin/index.php">
                  <i class="bi bi-shield-lock me-1"></i>Admin Portal
                </a>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <a class="nav-link" href="<?= APP_URL ?>/pages/dashboard-client.php">
                  <i class="bi bi-grid me-1"></i>Dashboard
                </a>
              </li>
              <?php if (in_array($user['user_type'], ['expert', 'both']) && isExpertVerified($user['id'])): ?>
                <li class="nav-item">
                  <a class="nav-link" href="<?= APP_URL ?>/pages/dashboard-expert.php">
                    <i class="bi bi-brain me-1"></i>Expert Panel
                  </a>
                </li>
              <?php endif; ?>
            <?php endif; ?>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= APP_URL ?>/pages/libraries.php">
              <i class="bi bi-book me-1"></i>Library
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= APP_URL ?>/pages/notes.php">
              <i class="bi bi-file-text me-1"></i>Notes
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= APP_URL ?>/pages/problem-solving.php">
              <i class="bi bi-play-circle me-1"></i>Problem Solving
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= APP_URL ?>/pages/browse.php">
              <i class="bi bi-people me-1"></i>Experts
            </a>
          </li>
        </ul>

        <div class="d-flex align-items-center gap-2">
          <?php if ($user): ?>
            <!-- Notifications -->
            <div class="dropdown">
              <button class="btn br-btn-icon position-relative" data-bs-toggle="dropdown" id="notif-btn">
                <i class="bi bi-bell"></i>
                <span class="br-notif-badge" id="notif-count" style="display:none">0</span>
              </button>
              <div class="dropdown-menu dropdown-menu-end br-notif-dropdown p-0" style="width:340px">
                <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                  <span class="fw-semibold">Notifications</span>
                  <a href="#" class="text-muted small" id="mark-all-read">Mark all read</a>
                </div>
                <div id="notif-list" style="max-height:320px;overflow-y:auto">
                  <div class="text-center text-muted py-4 small">Loading...</div>
                </div>
              </div>
            </div>

            <!-- User Menu -->
            <div class="dropdown">
              <button class="btn d-flex align-items-center gap-2 br-user-btn" data-bs-toggle="dropdown">
                <div class="br-avatar-sm">
                  <?php if ($user['profile_photo']): ?>
                    <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="">
                  <?php else: ?>
                    <?= strtoupper(substr($user['full_name'], 0, 2)) ?>
                  <?php endif; ?>
                </div>
                <span class="d-none d-md-inline"><?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?></span>
                <i class="bi bi-chevron-down small"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <h6 class="dropdown-header"><?= htmlspecialchars($user['full_name']) ?></h6>
                </li>
                <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($user['email']) ?></span></li>
                <li>
                  <hr class="dropdown-divider">
                </li>
                <?php if ($user['user_type'] === 'admin'): ?>
                  <li>
                    <a class="dropdown-item" href="<?= APP_URL ?>/admin/index.php">
                      <i class="bi bi-shield-lock me-2"></i>Admin Portal
                    </a>
                  </li>
                <?php else: ?>
                  <li>
                    <a class="dropdown-item" href="<?= APP_URL ?>/pages/profile.php">
                      <i class="bi bi-person me-2"></i>My Profile
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="<?= APP_URL ?>/pages/dashboard-client.php">
                      <i class="bi bi-grid me-2"></i>Client Dashboard
                    </a>
                  </li>
                  <?php if (in_array($user['user_type'], ['expert', 'both']) && isExpertVerified($user['id'])): ?>
                    <li>
                      <a class="dropdown-item" href="<?= APP_URL ?>/pages/dashboard-expert.php">
                        <i class="bi bi-brain me-2"></i>Expert Dashboard
                      </a>
                    </li>
                  <?php endif; ?>
                <?php endif; ?>
                <li>
                  <hr class="dropdown-divider">
                </li>
                <li>
                  <a class="dropdown-item text-danger" href="<?= APP_URL ?>/pages/logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Log Out
                  </a>
                </li>
              </ul>
            </div>

          <?php else: ?>
            <a href="<?= APP_URL ?>/pages/auth.php?tab=login" class="btn br-btn-ghost">Log In</a>
            <a href="<?= APP_URL ?>/pages/auth.php?tab=signup" class="btn br-btn-gold">Get Started Free</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>