<?php
// admin/libraries.php — Library Management
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

$title = 'Admin - Books';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$books = $db->fetchAll(
    "SELECT l.*, u.full_name AS uploader_name
     FROM libraries l LEFT JOIN users u ON l.uploaded_by = u.id
     ORDER BY l.created_at DESC"
);

$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';
?>

<main class="py-5">
    <div class="container">
        <div class="mb-4">
            <h1 class="display-6 fw-bold">Books Management</h1>
            <p class="text-muted">View and manage uploaded books.</p>
        </div>

        <?php
        $activeAdminPage = 'libraries';
        require __DIR__ . '/_nav.php';
        ?>

        <?php if ($status && $message): ?>
            <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="br-card p-3">
            <div class="d-flex justify-content-end mb-3">
                <a href="<?= APP_URL ?>/pages/upload-ebook.php" class="btn br-btn-gold btn-sm">Upload Book</a>
            </div>
            <div class="table-responsive">
                <table class="table br-table table-dark admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Uploader</th>
                            <th>Downloads</th>
                            <th>Views</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $b): ?>
                            <tr>
                                <td><?= htmlspecialchars($b['title']) ?></td>
                                <td><?= htmlspecialchars($b['author'] ?? '') ?></td>
                                <td><?= htmlspecialchars($b['category'] ?? '') ?></td>
                                <td><?= htmlspecialchars($b['uploader_name'] ?? 'Unknown') ?></td>
                                <td><?= number_format($b['downloads']) ?></td>
                                <td><?= number_format($b['views']) ?></td>
                                <td><?= $b['is_active'] ? 'Active' : 'Disabled' ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a class="btn br-btn-ghost btn-sm" href="<?= APP_URL ?>/api/view-ebook.php?id=<?= (int)$b['id'] ?>" target="_blank">View</a>
                                        <a class="btn br-btn-ghost btn-sm" href="<?= APP_URL ?>/api/download-ebook.php?id=<?= (int)$b['id'] ?>">Download</a>
                                        <form method="post" action="<?= APP_URL ?>/admin/actions.php" style="display:inline-block">
                                            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="entity" value="libraries">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                            <input type="hidden" name="redirect" value="<?= APP_URL ?>/admin/libraries.php">
                                            <button type="submit" class="btn br-btn-ghost btn-sm"><?= $b['is_active'] ? 'Disable' : 'Enable' ?></button>
                                        </form>
                                        <form method="post" action="<?= APP_URL ?>/admin/actions.php" style="display:inline-block" onsubmit="return confirm('Delete this book?');">
                                            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="entity" value="libraries">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                            <input type="hidden" name="redirect" value="<?= APP_URL ?>/admin/libraries.php">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$books): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No books found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>