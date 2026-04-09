<?php
// pages/problem-solving.php — Problem Solving Videos Platform
$title = 'Problem Solving Videos';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$user = currentUser();

// Handle search and filters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$difficulty = $_GET['difficulty'] ?? '';

$sql = "SELECT v.*, u.full_name as uploader_name
        FROM problem_solving_videos v
        LEFT JOIN users u ON v.uploaded_by = u.id
        WHERE v.is_active = 1";

$params = [];
if ($search) {
  $sql .= " AND (v.title LIKE ? OR v.description LIKE ? OR v.problem_type LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
  $params[] = "%$search%";
}
if ($type) {
  $sql .= " AND v.problem_type = ?";
  $params[] = $type;
}
if ($difficulty) {
  $sql .= " AND v.difficulty = ?";
  $params[] = $difficulty;
}

$sql .= " ORDER BY v.created_at DESC LIMIT 50";
$videos = $db->fetchAll($sql, $params);

// Get problem types
$problemTypes = $db->fetchAll("SELECT DISTINCT problem_type FROM problem_solving_videos WHERE is_active = 1 AND problem_type IS NOT NULL ORDER BY problem_type");
?>

<main class="py-5">
  <div class="container">

    <!-- Header -->
    <div class="mb-5">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <h1 class="display-5 fw-bold mb-2">🎥 Problem Solving Videos</h1>
          <p class="text-muted">Learn from step-by-step video solutions to complex problems.</p>
        </div>
        <?php if ($user): ?>
          <a href="<?= APP_URL ?>/pages/upload-video.php" class="btn br-btn-gold">
            <i class="bi bi-upload me-2"></i>Upload Video
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Search & Filters -->
    <div class="row mb-4">
      <div class="col-md-5">
        <form method="get" class="br-search mb-3">
          <i class="bi bi-search br-search-icon"></i>
          <input type="text" name="search" placeholder="Search videos..." value="<?= htmlspecialchars($search) ?>">
          <button type="submit" class="btn br-btn-gold" style="margin: 4px 4px 4px 0">Search</button>
        </form>
      </div>
      <div class="col-md-4">
        <form method="get">
          <?php if ($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
          <?php if ($difficulty): ?><input type="hidden" name="difficulty" value="<?= htmlspecialchars($difficulty) ?>"><?php endif; ?>
          <select name="type" class="br-form-control" onchange="this.form.submit()">
            <option value="">All Problem Types</option>
            <?php foreach ($problemTypes as $pt): ?>
              <option value="<?= htmlspecialchars($pt['problem_type']) ?>" <?= $type === $pt['problem_type'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($pt['problem_type']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
      <div class="col-md-3">
        <form method="get">
          <?php if ($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
          <?php if ($type): ?><input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>"><?php endif; ?>
          <select name="difficulty" class="br-form-control" onchange="this.form.submit()">
            <option value="">All Levels</option>
            <option value="beginner" <?= $difficulty === 'beginner' ? 'selected' : '' ?>>Beginner</option>
            <option value="intermediate" <?= $difficulty === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
            <option value="advanced" <?= $difficulty === 'advanced' ? 'selected' : '' ?>>Advanced</option>
          </select>
        </form>
      </div>
    </div>

    <!-- Videos Grid -->
    <?php if (empty($videos)): ?>
      <div class="text-center py-5">
        <div style="font-size: 4rem; margin-bottom: 1rem;">🎥</div>
        <h3 class="mb-3">No videos found</h3>
        <p class="text-muted mb-4">Be the first to share a problem-solving video!</p>
        <?php if ($user): ?>
          <a href="<?= APP_URL ?>/pages/upload-video.php" class="btn br-btn-gold">Upload Video</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($videos as $video): ?>
          <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <a href="<?= APP_URL ?>/pages/video-detail.php?id=<?= $video['id'] ?>" class="text-decoration-none">
              <div class="br-card h-100">
                <!-- Thumbnail -->
                <div class="position-relative" style="aspect-ratio: 16/9; background: var(--br-dark3); border-radius: 16px 16px 0 0; overflow: hidden;">
                  <?php if ($video['thumbnail']): ?>
                    <img src="<?= htmlspecialchars($video['thumbnail']) ?>" alt="Thumbnail" style="width: 100%; height: 100%; object-fit: cover;">
                  <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100" style="font-size: 3rem;">🎥</div>
                  <?php endif; ?>
                  <div class="position-absolute bottom-0 end-0 m-2 px-2 py-1" style="background: rgba(0,0,0,.8); border-radius: 4px; font-size: .7rem;">
                    <?php
                    $duration = $video['video_duration'];
                    $minutes = floor($duration / 60);
                    $seconds = $duration % 60;
                    echo sprintf('%d:%02d', $minutes, $seconds);
                    ?>
                  </div>
                  <?php if ($video['difficulty']): ?>
                    <span class="position-absolute top-0 start-0 m-2 br-badge br-badge-<?= $video['difficulty'] === 'beginner' ? 'success' : ($video['difficulty'] === 'intermediate' ? 'gold' : 'danger') ?>">
                      <?= ucfirst($video['difficulty']) ?>
                    </span>
                  <?php endif; ?>
                </div>

                <!-- Video Info -->
                <div class="p-3">
                  <h6 class="fw-semibold mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                    <?= htmlspecialchars($video['title']) ?>
                  </h6>
                  <?php if ($video['problem_type']): ?>
                    <div class="mb-2">
                      <span class="br-badge br-badge-violet"><?= htmlspecialchars($video['problem_type']) ?></span>
                    </div>
                  <?php endif; ?>
                  <div class="text-muted small mb-2"><?= htmlspecialchars($video['uploader_name']) ?></div>

                  <!-- Stats -->
                  <div class="d-flex gap-3 text-subtle" style="font-size: .75rem;">
                    <span><i class="bi bi-eye me-1"></i><?= number_format($video['views']) ?></span>
                    <span><i class="bi bi-heart me-1"></i><?= number_format($video['likes']) ?></span>
                  </div>
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