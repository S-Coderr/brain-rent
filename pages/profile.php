<?php
// pages/profile.php — User Profile
$title = 'My Profile';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$user = currentUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = trim($_POST['full_name'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $country = trim($_POST['country'] ?? '');
  $bio = trim($_POST['bio'] ?? '');

  if (empty($fullName)) {
    $error = 'Full name is required';
  } else {
    $updated = $db->execute(
      "UPDATE users SET full_name = ?, phone = ?, country = ?, bio = ? WHERE id = ?",
      [$fullName, $phone, $country, $bio, $user['id']]
    );

    if ($updated !== false) {
      $success = 'Profile updated successfully!';
      $user = currentUser(); // Refresh user data
    } else {
      $error = 'Failed to update profile';
    }
  }
}

// Get full user details
$userDetails = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user['id']]);
?>

<main class="py-5">
  <div class="container" style="max-width: 800px;">

    <h1 class="display-6 fw-bold mb-4">My Profile</h1>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="br-card p-4 mb-4">
      <form method="post">

        <div class="mb-4">
          <label class="br-form-label">Full Name *</label>
          <input type="text" name="full_name" class="br-form-control" required value="<?= htmlspecialchars($userDetails['full_name']) ?>">
        </div>

        <div class="mb-4">
          <label class="br-form-label">Email Address</label>
          <input type="email" class="br-form-control" value="<?= htmlspecialchars($userDetails['email']) ?>" disabled>
          <small class="text-muted">Email cannot be changed</small>
        </div>

        <div class="row mb-4">
          <div class="col-md-6">
            <label class="br-form-label">Phone</label>
            <input type="tel" name="phone" class="br-form-control" value="<?= htmlspecialchars($userDetails['phone'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="br-form-label">Country</label>
            <input type="text" name="country" class="br-form-control" value="<?= htmlspecialchars($userDetails['country'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-4">
          <label class="br-form-label">Bio</label>
          <textarea name="bio" class="br-form-control" rows="4" placeholder="Tell us about yourself..."><?= htmlspecialchars($userDetails['bio'] ?? '') ?></textarea>
        </div>

        <div class="mb-4">
          <label class="br-form-label">Account Type</label>
          <input type="text" class="br-form-control" value="<?= ucfirst($userDetails['user_type']) ?>" disabled>
        </div>

        <div class="mb-4">
          <label class="br-form-label">Member Since</label>
          <input type="text" class="br-form-control" value="<?= date('F j, Y', strtotime($userDetails['created_at'])) ?>" disabled>
        </div>

        <button type="submit" class="btn br-btn-gold">
          <i class="bi bi-check-circle me-2"></i>Save Changes
        </button>

      </form>
    </div>

    <!-- Change Password Section -->
    <div class="br-card p-4">
      <h5 class="fw-semibold mb-3">Change Password</h5>
      <form method="post" action="<?= APP_URL ?>/api/change-password.php">
        <div class="mb-3">
          <label class="br-form-label">Current Password</label>
          <input type="password" name="current_password" class="br-form-control" required>
        </div>
        <div class="mb-3">
          <label class="br-form-label">New Password</label>
          <input type="password" name="new_password" class="br-form-control" required minlength="6">
        </div>
        <div class="mb-3">
          <label class="br-form-label">Confirm New Password</label>
          <input type="password" name="confirm_password" class="br-form-control" required minlength="6">
        </div>
        <button type="submit" class="btn br-btn-ghost">
          <i class="bi bi-key me-2"></i>Update Password
        </button>
      </form>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>