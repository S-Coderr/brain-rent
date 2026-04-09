<?php
// pages/expert-pending.php — Expert Verification Pending
$title = 'Expert Verification';
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$user = currentUser();
if (!$user || !in_array($user['user_type'], ['expert', 'both'])) {
    header('Location: ' . APP_URL . '/pages/dashboard-client.php');
    exit;
}

if (isExpertVerified($user['id'])) {
    header('Location: ' . APP_URL . '/pages/dashboard-expert.php');
    exit;
}

$db = Database::getInstance();
$profile = $db->fetchOne("SELECT * FROM expert_profiles WHERE user_id = ?", [$user['id']]) ?: [];
require_once __DIR__ . '/../includes/header.php';
?>

<main class="py-5" style="padding-top:90px;">
    <div class="container" style="max-width:900px;">
        <div class="br-card p-4">
            <h1 class="display-6 fw-bold mb-2">Expert Verification Pending</h1>
            <p class="text-muted">Your expert profile is under review. You will be able to access the expert dashboard after admin approval.</p>

            <div class="br-card p-3 mb-4" style="background:var(--br-card2);">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-subtle small">Qualification</div>
                        <div class="fw-medium"><?= htmlspecialchars($profile['qualification'] ?? 'N/A') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-subtle small">Domain</div>
                        <div class="fw-medium"><?= htmlspecialchars($profile['domain'] ?? 'N/A') ?></div>
                    </div>
                    <div class="col-12">
                        <div class="text-subtle small">Skills</div>
                        <div class="fw-medium"><?= htmlspecialchars($profile['skills'] ?? 'N/A') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-subtle small">Experience (Years)</div>
                        <div class="fw-medium"><?= htmlspecialchars($profile['experience_years'] ?? 'N/A') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-subtle small">Rate</div>
                        <div class="fw-medium"><?= htmlspecialchars(($profile['currency'] ?? 'USD') . ' ' . ($profile['rate_per_session'] ?? '0.00')) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-subtle small">Response Time</div>
                        <div class="fw-medium">
                            <?php if (!empty($profile['max_response_hours'])): ?>
                                <?= htmlspecialchars($profile['max_response_hours']) ?> hours
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= APP_URL ?>/pages/dashboard-client.php" class="btn br-btn-ghost">Go to Client Dashboard</a>
                <a href="<?= APP_URL ?>/pages/profile.php" class="btn br-btn-gold">Update Profile</a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>