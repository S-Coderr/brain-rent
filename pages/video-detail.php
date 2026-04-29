<?php
// pages/video-detail.php — Video Detail & Player
$title = 'Video Player';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$user = currentUser();

$videoId = (int)($_GET['id'] ?? 0);

if (!$videoId) {
  header('Location: ' . APP_URL . '/pages/problem-solving.php');
  exit;
}

$video = $db->fetchOne(
  "SELECT v.*, u.full_name as uploader_name
     FROM problem_solving_videos v
     LEFT JOIN users u ON v.uploaded_by = u.id
     WHERE v.id = ? AND v.is_active = 1",
  [$videoId]
);

if (!$video) {
  header('Location: ' . APP_URL . '/pages/problem-solving.php');
  exit;
}

// Increment view count
$db->execute("UPDATE problem_solving_videos SET views = views + 1 WHERE id = ?", [$videoId]);

// Get comments
$comments = $db->fetchAll(
  "SELECT c.*, u.full_name as commenter_name
     FROM video_comments c
     LEFT JOIN users u ON c.user_id = u.id
     WHERE c.video_id = ? AND c.is_active = 1 AND c.parent_id IS NULL
     ORDER BY c.created_at DESC",
  [$videoId]
);
?>

<main class="py-5">
  <div class="container">

    <div class="mb-4">
      <a href="<?= APP_URL ?>/pages/problem-solving.php" class="text-decoration-none text-muted">
        <i class="bi bi-arrow-left me-2"></i>Back to Videos
      </a>
    </div>

    <div class="row">
      <div class="col-lg-8">

        <!-- Video Player -->
        <div class="br-card p-0 mb-4">
          <video controls style="width: 100%; border-radius: 16px; background: #000;">
            <source src="<?= APP_URL ?>/api/view-video.php?id=<?= (int) $video['id'] ?>" type="video/mp4">
            Your browser does not support the video tag.
          </video>
        </div>

        <!-- Video Info -->
        <div class="br-card p-4 mb-4">
          <h2 class="fw-bold mb-3"><?= htmlspecialchars($video['title']) ?></h2>

          <div class="d-flex gap-2 mb-3">
            <?php if ($video['problem_type']): ?>
              <span class="br-badge br-badge-violet"><?= htmlspecialchars($video['problem_type']) ?></span>
            <?php endif; ?>
            <?php if ($video['difficulty']): ?>
              <span class="br-badge br-badge-<?= $video['difficulty'] === 'beginner' ? 'success' : ($video['difficulty'] === 'intermediate' ? 'gold' : 'danger') ?>">
                <?= ucfirst($video['difficulty']) ?>
              </span>
            <?php endif; ?>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom: 1px solid var(--br-border);">
            <div>
              <div class="fw-medium"><?= htmlspecialchars($video['uploader_name']) ?></div>
              <div class="text-muted small"><?= date('F j, Y', strtotime($video['created_at'])) ?></div>
            </div>
            <div class="d-flex gap-3">
              <span class="text-subtle"><i class="bi bi-eye me-1"></i><?= number_format($video['views']) ?> views</span>
              <span class="text-subtle"><i class="bi bi-heart me-1"></i><?= number_format($video['likes']) ?> likes</span>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="<?= APP_URL ?>/api/download-video.php?id=<?= $video['id'] ?>" class="btn br-btn-gold btn-sm">
              <i class="bi bi-download me-1"></i>Download
            </a>
          </div>

          <?php if ($video['description']): ?>
            <div>
              <h6 class="fw-semibold mb-2">Description</h6>
              <p class="text-muted"><?= nl2br(htmlspecialchars($video['description'])) ?></p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Comments Section -->
        <div class="br-card p-4">
          <h5 class="fw-semibold mb-4"><?= count($comments) ?> Comments</h5>

          <?php if ($user): ?>
            <form method="post" class="mb-4">
              <textarea class="br-form-control mb-2" rows="3" name="comment" placeholder="Add a comment..." required></textarea>
              <button type="submit" class="btn br-btn-gold">Post Comment</button>
            </form>
          <?php else: ?>
            <div class="alert br-alert-info mb-4">
              <a href="<?= APP_URL ?>/pages/auth.php?tab=login">Login</a> to leave a comment
            </div>
          <?php endif; ?>

          <div>
            <?php foreach ($comments as $comment): ?>
              <div class="mb-3 pb-3" style="border-bottom: 1px solid var(--br-border);">
                <div class="d-flex gap-3">
                  <div class="br-avatar-sm" style="width: 36px; height: 36px;">
                    <?= strtoupper(substr($comment['commenter_name'], 0, 2)) ?>
                  </div>
                  <div class="flex-fill">
                    <div class="d-flex justify-content-between mb-1">
                      <span class="fw-medium small"><?= htmlspecialchars($comment['commenter_name']) ?></span>
                      <span class="text-subtle" style="font-size: .75rem;"><?= date('M j, Y', strtotime($comment['created_at'])) ?></span>
                    </div>
                    <p class="mb-0 text-muted small"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div>

      <!-- Sidebar -->
      <div class="col-lg-4">
        <div class="br-card p-4">
          <h6 class="fw-semibold mb-3">Related Videos</h6>
          <?php
          $relatedVideos = $db->fetchAll(
            "SELECT * FROM problem_solving_videos
               WHERE is_active = 1 AND id != ?
               ORDER BY RAND()
               LIMIT 5",
            [$videoId]
          );
          foreach ($relatedVideos as $rv):
          ?>
            <a href="<?= APP_URL ?>/pages/video-detail.php?id=<?= $rv['id'] ?>" class="text-decoration-none d-block mb-3">
              <div class="d-flex gap-2">
                <div style="width: 120px; aspect-ratio: 16/9; background: var(--br-dark3); border-radius: 8px; flex-shrink: 0; overflow: hidden;">
                  <?php if ($rv['thumbnail']): ?>
                    <img src="<?= htmlspecialchars($rv['thumbnail']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                  <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100">🎥</div>
                  <?php endif; ?>
                </div>
                <div class="flex-fill">
                  <div class="fw-medium small mb-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                    <?= htmlspecialchars($rv['title']) ?>
                  </div>
                  <div class="text-subtle" style="font-size: .7rem;">
                    <?= number_format($rv['views']) ?> views
                  </div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>