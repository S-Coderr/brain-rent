<?php
// admin/videos.php — Video Management
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

$title = 'Admin - Videos';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$videos = $db->fetchAll(
    "SELECT v.*, u.full_name AS uploader_name
     FROM problem_solving_videos v LEFT JOIN users u ON v.uploaded_by = u.id
     ORDER BY v.created_at DESC"
);

$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
?>

<main class="py-5">
    <div class="container">
        <div class="mb-4">
            <h1 class="display-6 fw-bold">Videos Management</h1>
            <p class="text-muted">View and manage uploaded videos.</p>
        </div>

        <?php
        $activeAdminPage = 'videos';
        require __DIR__ . '/_nav.php';
        ?>

        <?php if ($status && $message): ?>
            <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="br-card p-3">
            <div class="d-flex justify-content-end mb-3">
                <a href="<?= APP_URL ?>/pages/upload-video.php" class="btn br-btn-gold btn-sm">Upload Video</a>
            </div>
            <div class="table-responsive">
                <table class="table br-table table-dark admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Difficulty</th>
                            <th>Uploader</th>
                            <th>Views</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($videos as $v): ?>
                            <tr>
                                <td><?= htmlspecialchars($v['title']) ?></td>
                                <td><?= htmlspecialchars($v['problem_type'] ?? '') ?></td>
                                <td><?= htmlspecialchars($v['difficulty'] ?? '') ?></td>
                                <td><?= htmlspecialchars($v['uploader_name'] ?? 'Unknown') ?></td>
                                <td><?= number_format($v['views']) ?></td>
                                <td><?= $v['is_active'] ? 'Active' : 'Disabled' ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a class="btn br-btn-ghost btn-sm" href="<?= APP_URL ?>/pages/video-detail.php?id=<?= (int)$v['id'] ?>" target="_blank">Open</a>
                                        <a class="btn br-btn-ghost btn-sm" href="<?= APP_URL ?>/api/download-video.php?id=<?= (int)$v['id'] ?>">Download</a>
                                        <form method="post" action="<?= APP_URL ?>/admin/actions.php" style="display:inline-block">
                                            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="entity" value="videos">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                            <input type="hidden" name="redirect" value="<?= APP_URL ?>/admin/videos.php">
                                            <button type="submit" class="btn br-btn-ghost btn-sm"><?= $v['is_active'] ? 'Disable' : 'Enable' ?></button>
                                        </form>
                                        <form method="post" action="<?= APP_URL ?>/admin/actions.php" style="display:inline-block" onsubmit="return confirm('Delete this video?');">
                                            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="entity" value="videos">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                            <input type="hidden" name="redirect" value="<?= APP_URL ?>/admin/videos.php">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$videos): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No videos found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>