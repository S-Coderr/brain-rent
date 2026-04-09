<?php
// admin/notes.php — Notes Management
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

$title = 'Admin - Notes';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$notes = $db->fetchAll(
    "SELECT n.*, u.full_name AS uploader_name
     FROM notes n LEFT JOIN users u ON n.uploaded_by = u.id
     ORDER BY n.created_at DESC"
);

$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
?>

<main class="py-5">
    <div class="container">
        <div class="mb-4">
            <h1 class="display-6 fw-bold">Notes Management</h1>
            <p class="text-muted">View and manage uploaded notes.</p>
        </div>

        <?php
        $activeAdminPage = 'notes';
        require __DIR__ . '/_nav.php';
        ?>

        <?php if ($status && $message): ?>
            <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="br-card p-3">
            <div class="d-flex justify-content-end mb-3">
                <a href="<?= APP_URL ?>/pages/upload-notes.php" class="btn br-btn-gold btn-sm">Upload Notes</a>
            </div>
            <div class="table-responsive">
                <table class="table br-table table-dark admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Uploader</th>
                            <th>Downloads</th>
                            <th>Views</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notes as $n): ?>
                            <tr>
                                <td><?= htmlspecialchars($n['title']) ?></td>
                                <td><?= htmlspecialchars($n['subject'] ?? '') ?></td>
                                <td><?= htmlspecialchars($n['category'] ?? '') ?></td>
                                <td><?= htmlspecialchars($n['uploader_name'] ?? 'Unknown') ?></td>
                                <td><?= number_format($n['downloads']) ?></td>
                                <td><?= number_format($n['views']) ?></td>
                                <td><?= $n['is_active'] ? 'Active' : 'Disabled' ?></td>
                                <td>
                                    <div class="text-muted small mb-1">
                                        Note: <?= htmlspecialchars($n['title']) ?>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a class="btn br-btn-ghost btn-sm" href="<?= APP_URL ?>/api/view-note.php?id=<?= (int)$n['id'] ?>" target="_blank">View</a>
                                        <a class="btn br-btn-ghost btn-sm" href="<?= APP_URL ?>/api/download-note.php?id=<?= (int)$n['id'] ?>">Download</a>
                                        <form method="post" action="<?= APP_URL ?>/admin/actions.php" style="display:inline-block">
                                            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="entity" value="notes">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                                            <input type="hidden" name="redirect" value="<?= APP_URL ?>/admin/notes.php">
                                            <button type="submit" class="btn br-btn-ghost btn-sm"><?= $n['is_active'] ? 'Disable' : 'Enable' ?></button>
                                        </form>
                                        <form method="post" action="<?= APP_URL ?>/admin/actions.php" style="display:inline-block" onsubmit="return confirm('Delete this note?');">
                                            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="entity" value="notes">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                                            <input type="hidden" name="redirect" value="<?= APP_URL ?>/admin/notes.php">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$notes): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No notes found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>