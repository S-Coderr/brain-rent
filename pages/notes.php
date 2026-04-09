<?php
// pages/notes.php — Notes Sharing Platform
$title = 'Study Notes';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$user = currentUser();

// Handle search and filters
$search = $_GET['search'] ?? '';
$subject = $_GET['subject'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT n.*, u.full_name as uploader_name
        FROM notes n
        LEFT JOIN users u ON n.uploaded_by = u.id
        WHERE n.is_active = 1";

$params = [];
if ($search) {
  $sql .= " AND (n.title LIKE ? OR n.subject LIKE ? OR n.description LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
  $params[] = "%$search%";
}
if ($subject) {
  $sql .= " AND n.subject = ?";
  $params[] = $subject;
}
if ($category) {
  $sql .= " AND n.category = ?";
  $params[] = $category;
}

$sql .= " ORDER BY n.created_at DESC LIMIT 50";
$notes = $db->fetchAll($sql, $params);

// Get subjects and categories
$subjects = $db->fetchAll("SELECT DISTINCT subject FROM notes WHERE is_active = 1 AND subject IS NOT NULL ORDER BY subject");
$categories = $db->fetchAll("SELECT DISTINCT category FROM notes WHERE is_active = 1 AND category IS NOT NULL ORDER BY category");
?>

<main class="py-5">
  <div class="container">

    <!-- Header -->
    <div class="mb-5">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <h1 class="display-5 fw-bold mb-2">📝 Study Notes</h1>
          <p class="text-muted">Share and download study notes from students worldwide.</p>
        </div>
        <?php if ($user): ?>
          <a href="<?= APP_URL ?>/pages/upload-notes.php" class="btn br-btn-gold">
            <i class="bi bi-upload me-2"></i>Upload Notes
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Search & Filters -->
    <div class="row mb-4">
      <div class="col-md-5">
        <form method="get" class="br-search mb-3">
          <i class="bi bi-search br-search-icon"></i>
          <input type="text" name="search" placeholder="Search notes..." value="<?= htmlspecialchars($search) ?>">
          <button type="submit" class="btn br-btn-gold" style="margin: 4px 4px 4px 0">Search</button>
        </form>
      </div>
      <div class="col-md-3">
        <form method="get">
          <?php if ($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
          <?php if ($category): ?><input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>"><?php endif; ?>
          <select name="subject" class="br-form-control" onchange="this.form.submit()">
            <option value="">All Subjects</option>
            <?php foreach ($subjects as $s): ?>
              <option value="<?= htmlspecialchars($s['subject']) ?>" <?= $subject === $s['subject'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['subject']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
      <div class="col-md-4">
        <form method="get">
          <?php if ($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
          <?php if ($subject): ?><input type="hidden" name="subject" value="<?= htmlspecialchars($subject) ?>"><?php endif; ?>
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

    <!-- Notes List -->
    <?php if (empty($notes)): ?>
      <div class="text-center py-5">
        <div style="font-size: 4rem; margin-bottom: 1rem;">📝</div>
        <h3 class="mb-3">No notes found</h3>
        <p class="text-muted mb-4">Be the first to share your notes!</p>
        <?php if ($user): ?>
          <a href="<?= APP_URL ?>/pages/upload-notes.php" class="btn br-btn-gold">Upload Notes</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($notes as $note): ?>
          <div class="col-12">
            <div class="br-card p-4">
              <div class="row align-items-center">
                <div class="col-md-8">
                  <div class="d-flex gap-2 mb-2">
                    <?php if ($note['subject']): ?>
                      <span class="br-badge br-badge-violet"><?= htmlspecialchars($note['subject']) ?></span>
                    <?php endif; ?>
                    <?php if ($note['category']): ?>
                      <span class="br-badge br-badge-teal"><?= htmlspecialchars($note['category']) ?></span>
                    <?php endif; ?>
                  </div>
                  <h5 class="fw-semibold mb-2"><?= htmlspecialchars($note['title']) ?></h5>
                  <?php if ($note['description']): ?>
                    <p class="text-muted small mb-2"><?= htmlspecialchars($note['description']) ?></p>
                  <?php endif; ?>
                  <div class="d-flex gap-3 text-subtle" style="font-size: .8rem;">
                    <span><i class="bi bi-person me-1"></i><?= htmlspecialchars($note['uploader_name']) ?></span>
                    <span><i class="bi bi-download me-1"></i><?= number_format($note['downloads']) ?> downloads</span>
                    <span><i class="bi bi-eye me-1"></i><?= number_format($note['views']) ?> views</span>
                    <span><i class="bi bi-clock me-1"></i><?= date('M j, Y', strtotime($note['created_at'])) ?></span>
                  </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                  <a href="<?= APP_URL ?>/api/download-note.php?id=<?= $note['id'] ?>" class="btn br-btn-gold me-2">
                    <i class="bi bi-download me-1"></i>Download
                  </a>
                  <a href="<?= APP_URL ?>/api/view-note.php?id=<?= $note['id'] ?>" target="_blank" class="btn br-btn-ghost">
                    <i class="bi bi-eye me-1"></i>View
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>