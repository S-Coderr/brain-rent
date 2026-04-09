<?php
// pages/ebook-detail.php — E-Book Details
$title = 'E-Book Details';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$user = currentUser();

$bookId = (int)($_GET['id'] ?? 0);
if (!$bookId) {
    header('Location: ' . APP_URL . '/pages/libraries.php');
    exit;
}

$book = $db->fetchOne(
    "SELECT l.*, u.full_name as uploader_name
     FROM libraries l
     LEFT JOIN users u ON l.uploaded_by = u.id
     WHERE l.id = ? AND l.is_active = 1",
    [$bookId]
);

if (!$book) {
    header('Location: ' . APP_URL . '/pages/libraries.php');
    exit;
}

$relatedSql = "SELECT id, title, author, cover_image, category
                FROM libraries
                WHERE is_active = 1 AND id != ?";
$relatedParams = [$bookId];
if (!empty($book['category'])) {
    $relatedSql .= " AND category = ?";
    $relatedParams[] = $book['category'];
}
$relatedSql .= " ORDER BY created_at DESC LIMIT 6";
$relatedBooks = $db->fetchAll($relatedSql, $relatedParams);

$fileSize = $book['file_size'] ? number_format($book['file_size'] / 1024 / 1024, 2) . ' MB' : 'N/A';
$fileType = strtoupper($book['file_type'] ?? '');
?>

<main class="py-5">
    <div class="container">

        <div class="mb-4">
            <a href="<?= APP_URL ?>/pages/libraries.php" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-2"></i>Back to Library
            </a>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="br-card p-3">
                    <div style="aspect-ratio: 2/3; background: var(--br-dark3); border-radius: 12px; overflow: hidden;">
                        <?php if ($book['cover_image']): ?>
                            <img src="<?= htmlspecialchars($book['cover_image']) ?>" alt="Cover" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100" style="font-size: 2.5rem;">
                                <i class="bi bi-book"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <a href="<?= APP_URL ?>/api/download-ebook.php?id=<?= $book['id'] ?>" class="btn br-btn-gold">
                            <i class="bi bi-download me-1"></i>Download
                        </a>
                        <a href="<?= APP_URL ?>/api/view-ebook.php?id=<?= $book['id'] ?>" target="_blank" class="btn br-btn-ghost">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </div>

                    <div class="mt-3 small text-muted">
                        <div>File type: <?= htmlspecialchars($fileType ?: 'N/A') ?></div>
                        <div>File size: <?= htmlspecialchars($fileSize) ?></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="br-card p-4 mb-4">
                    <?php if ($book['category']): ?>
                        <span class="br-badge br-badge-gold mb-3"><?= htmlspecialchars($book['category']) ?></span>
                    <?php endif; ?>

                    <h2 class="fw-bold mb-2"><?= htmlspecialchars($book['title']) ?></h2>
                    <?php if ($book['author']): ?>
                        <div class="text-muted mb-3">by <?= htmlspecialchars($book['author']) ?></div>
                    <?php endif; ?>

                    <div class="d-flex flex-wrap gap-3 text-subtle" style="font-size: .85rem;">
                        <span><i class="bi bi-download me-1"></i><?= number_format($book['downloads']) ?> downloads</span>
                        <span><i class="bi bi-eye me-1"></i><?= number_format($book['views']) ?> views</span>
                        <span><i class="bi bi-person me-1"></i><?= htmlspecialchars($book['uploader_name'] ?? 'Unknown') ?></span>
                        <span><i class="bi bi-clock me-1"></i><?= date('M j, Y', strtotime($book['created_at'])) ?></span>
                    </div>

                    <?php if ($book['description']): ?>
                        <div class="mt-4">
                            <h6 class="fw-semibold mb-2">Description</h6>
                            <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($book['description'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($relatedBooks)): ?>
                    <div class="br-card p-4">
                        <h6 class="fw-semibold mb-3">Related Books</h6>
                        <div class="row g-3">
                            <?php foreach ($relatedBooks as $rb): ?>
                                <div class="col-12 col-sm-6">
                                    <a href="<?= APP_URL ?>/pages/ebook-detail.php?id=<?= $rb['id'] ?>" class="text-decoration-none">
                                        <div class="br-card h-100 p-3">
                                            <div class="d-flex gap-3">
                                                <div style="width: 72px; aspect-ratio: 2/3; background: var(--br-dark3); border-radius: 8px; overflow: hidden;">
                                                    <?php if ($rb['cover_image']): ?>
                                                        <img src="<?= htmlspecialchars($rb['cover_image']) ?>" alt="Cover" style="width: 100%; height: 100%; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="d-flex align-items-center justify-content-center h-100"><i class="bi bi-book"></i></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-fill">
                                                    <div class="fw-medium small mb-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                        <?= htmlspecialchars($rb['title']) ?>
                                                    </div>
                                                    <?php if ($rb['author']): ?>
                                                        <div class="text-muted" style="font-size: .75rem;">by <?= htmlspecialchars($rb['author']) ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($rb['category']): ?>
                                                        <div class="mt-2"><span class="br-badge br-badge-violet"><?= htmlspecialchars($rb['category']) ?></span></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>