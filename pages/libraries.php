<?php
// pages/libraries.php — E-Books Library
$title = 'Digital Library';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$user = currentUser();

// Handle search and filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT l.*, u.full_name as uploader_name
        FROM libraries l
        LEFT JOIN users u ON l.uploaded_by = u.id
        WHERE l.is_active = 1";

$params = [];
if ($search) {
  $sql .= " AND (l.title LIKE ? OR l.author LIKE ? OR l.description LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
  $params[] = "%$search%";
}
if ($category) {
  $sql .= " AND l.category = ?";
  $params[] = $category;
}

$sql .= " ORDER BY l.created_at DESC LIMIT 50";
$ebooks = $db->fetchAll($sql, $params);

// Get categories
$categories = $db->fetchAll("SELECT DISTINCT category FROM libraries WHERE is_active = 1 AND category IS NOT NULL ORDER BY category");
?>

<main class="py-5">
  <div class="container">

    <!-- Header -->
    <div class="mb-5">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <h1 class="display-5 fw-bold mb-2">📚 Digital Library</h1>
          <p class="text-muted">Access thousands of free e-books. Upload and share your favorites.</p>
        </div>
        <?php if ($user): ?>
          <a href="<?= APP_URL ?>/pages/upload-ebook.php" class="btn br-btn-gold">
            <i class="bi bi-upload me-2"></i>Upload E-Book
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Search & Filters -->
    <div class="row mb-4">
      <div class="col-md-8">
        <form method="get" class="br-search mb-3">
          <i class="bi bi-search br-search-icon"></i>
          <input type="text" name="search" placeholder="Search books by title, author, or keywords..." value="<?= htmlspecialchars($search) ?>">
          <button type="submit" class="btn br-btn-gold" style="margin: 4px 4px 4px 0">Search</button>
        </form>
      </div>
      <div class="col-md-4">
        <form method="get">
          <?php if ($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
          <select name="category" class="br-form-control" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $category === $cat['category'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['category']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
    </div>

    <!-- E-Books Grid -->
    <?php if (empty($ebooks)): ?>
      <div class="text-center py-5">
        <div style="font-size: 4rem; margin-bottom: 1rem;">📚</div>
        <h3 class="mb-3">No books found</h3>
        <p class="text-muted mb-4">Be the first to upload an e-book!</p>
        <?php if ($user): ?>
          <a href="<?= APP_URL ?>/pages/upload-ebook.php" class="btn br-btn-gold">Upload E-Book</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($ebooks as $book): ?>
          <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <a href="<?= APP_URL ?>/pages/ebook-detail.php?id=<?= $book['id'] ?>" class="text-decoration-none">
              <div class="br-card h-100 p-3">
                <!-- Cover Image -->
                <div class="mb-3" style="aspect-ratio: 2/3; background: var(--br-dark3); border-radius: 12px; overflow: hidden; position: relative;">
                  <?php if ($book['cover_image']): ?>
                    <img src="<?= htmlspecialchars($book['cover_image']) ?>" alt="Cover" style="width: 100%; height: 100%; object-fit: cover;">
                  <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100" style="font-size: 3rem;">📖</div>
                  <?php endif; ?>
                  <?php if ($book['category']): ?>
                    <span class="position-absolute top-0 start-0 m-2 br-badge br-badge-gold"><?= htmlspecialchars($book['category']) ?></span>
                  <?php endif; ?>
                </div>

                <!-- Book Info -->
                <h6 class="fw-semibold mb-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                  <?= htmlspecialchars($book['title']) ?>
                </h6>
                <?php if ($book['author']): ?>
                  <div class="text-muted small mb-2">by <?= htmlspecialchars($book['author']) ?></div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="d-flex justify-content-between align-items-center pt-2" style="border-top: 1px solid var(--br-border); font-size: .75rem;">
                  <span class="text-subtle"><i class="bi bi-download me-1"></i><?= number_format($book['downloads']) ?></span>
                  <span class="text-subtle"><i class="bi bi-eye me-1"></i><?= number_format($book['views']) ?></span>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>