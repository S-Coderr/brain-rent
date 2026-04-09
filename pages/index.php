<?php
// pages/index.php — Landing Page
$title = 'BrainRent - Learn, Share, Solve';
require_once __DIR__ . '/../includes/header.php';

// Load featured experts
$db      = Database::getInstance();
$experts = $db->fetchAll(
  "SELECT ep.rate_per_session, ep.average_rating, ep.total_reviews,
            ep.total_sessions, ep.is_verified, ep.expertise_areas,
            ep.headline, u.full_name, u.id AS user_id
     FROM expert_profiles ep
     INNER JOIN users u ON ep.user_id = u.id
     WHERE ep.is_available = 1 AND u.is_active = 1 AND ep.is_verified = 1
     ORDER BY ep.average_rating DESC
     LIMIT 4"
);

// Get stats
$stats = [
  'ebooks' => $db->fetchOne("SELECT COUNT(*) as count FROM libraries WHERE is_active = 1")['count'] ?? 0,
  'notes' => $db->fetchOne("SELECT COUNT(*) as count FROM notes WHERE is_active = 1")['count'] ?? 0,
  'videos' => $db->fetchOne("SELECT COUNT(*) as count FROM problem_solving_videos WHERE is_active = 1")['count'] ?? 0,
  'experts' => $db->fetchOne("SELECT COUNT(*) as count FROM expert_profiles WHERE is_verified = 1")['count'] ?? 0
];

$avColors = ['av-1', 'av-2', 'av-3', 'av-4', 'av-5', 'av-6'];
?>
<main>

  <!-- ======= HERO ======= -->
  <section class="br-hero">
    <div class="br-hero-grid"></div>
    <div class="container position-relative">
      <div class="br-eyebrow mb-4">
        <span class="br-eyebrow-dot"></span>
        Your Gateway to Knowledge & Problem Solving
      </div>

      <h1 class="display-3 fw-bold mb-4" style="font-family:'Playfair Display',serif;letter-spacing:-1px;max-width:820px;margin:0 auto">
        Learn, Share & Solve
        <span class="text-warning">Together</span>
      </h1>

      <p class="lead text-muted mb-5" style="max-width:660px;margin:0 auto">
        Access thousands of e-books, share your notes, watch problem-solving videos,
        and connect with verified experts — all in one platform.
      </p>

      <div class="d-flex gap-3 justify-content-center flex-wrap mb-5">
        <a href="<?= APP_URL ?>/pages/libraries.php" class="btn br-btn-gold btn-lg px-4 py-3">
          <i class="bi bi-book me-2"></i>Explore Library
        </a>
        <a href="<?= APP_URL ?>/pages/browse.php" class="btn br-btn-ghost btn-lg px-4 py-3">
          Browse Experts <i class="bi bi-arrow-right ms-2"></i>
        </a>
      </div>

      <!-- Stats -->
      <div class="d-flex justify-content-center gap-5 flex-wrap pt-4" style="border-top:1px solid rgba(255,255,255,.07)">
        <div class="text-center">
          <div class="br-stat-num"><?= number_format($stats['ebooks']) ?>+</div>
          <div class="br-stat-label">E-Books</div>
        </div>
        <div class="text-center">
          <div class="br-stat-num"><?= number_format($stats['notes']) ?>+</div>
          <div class="br-stat-label">Shared Notes</div>
        </div>
        <div class="text-center">
          <div class="br-stat-num"><?= number_format($stats['videos']) ?>+</div>
          <div class="br-stat-label">Video Solutions</div>
        </div>
        <div class="text-center">
          <div class="br-stat-num"><?= number_format($stats['experts']) ?>+</div>
          <div class="br-stat-label">Expert Tutors</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= FEATURES ======= -->
  <section class="py-5">
    <div class="container">
      <div class="mb-5 text-center">
        <div class="br-section-label">Platform Features</div>
        <h2 class="br-section-title fs-2">Everything you need to succeed</h2>
      </div>
      <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
        <!-- Libraries -->
        <div class="col">
          <a href="<?= APP_URL ?>/pages/libraries.php" class="text-decoration-none">
            <div class="br-feature-card h-100 p-4">
              <div class="br-feature-icon mb-3">📚</div>
              <h4 class="fw-semibold mb-2">Digital Library</h4>
              <p class="text-muted mb-3">Access thousands of free e-books across all subjects. Upload and share your favorite books with the community.</p>
              <div class="d-flex align-items-center text-warning">
                <span class="small fw-medium">Explore Library</span>
                <i class="bi bi-arrow-right ms-2"></i>
              </div>
            </div>
          </a>
        </div>

        <!-- Notes -->
        <div class="col">
          <a href="<?= APP_URL ?>/pages/notes.php" class="text-decoration-none">
            <div class="br-feature-card h-100 p-4">
              <div class="br-feature-icon mb-3">📝</div>
              <h4 class="fw-semibold mb-2">Share Notes</h4>
              <p class="text-muted mb-3">Upload your study notes and access notes from other students. Download, share, and collaborate on knowledge.</p>
              <div class="d-flex align-items-center text-warning">
                <span class="small fw-medium">Browse Notes</span>
                <i class="bi bi-arrow-right ms-2"></i>
              </div>
            </div>
          </a>
        </div>

        <!-- Problem Solving -->
        <div class="col">
          <a href="<?= APP_URL ?>/pages/problem-solving.php" class="text-decoration-none">
            <div class="br-feature-card h-100 p-4">
              <div class="br-feature-icon mb-3">🎥</div>
              <h4 class="fw-semibold mb-2">Problem Solving</h4>
              <p class="text-muted mb-3">Watch video solutions to complex problems. Upload your own tutorials and help others learn step by step.</p>
              <div class="d-flex align-items-center text-warning">
                <span class="small fw-medium">Watch Videos</span>
                <i class="bi bi-arrow-right ms-2"></i>
              </div>
            </div>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= HOW IT WORKS ======= -->
  <section class="py-5 bg-dark2" id="how-it-works">
    <div class="container">
      <div class="mb-5 text-center">
        <div class="br-section-label">How It Works</div>
        <h2 class="br-section-title fs-2">Your Learning Hub in 4 Easy Steps</h2>
        <p class="text-muted">Access, share, and learn from a global community</p>
      </div>
      <div class="row g-4">
        <div class="col-12 col-md-6 col-lg-3">
          <div class="text-center p-4 h-100" style="background: var(--br-card); border-radius: 16px; border: 1px solid var(--br-border);">
            <div class="mb-3 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: var(--br-gold-dim); border-radius: 20px; font-size: 2.5rem;">
              📚
            </div>
            <h5 class="fw-semibold mb-2">Browse Library</h5>
            <p class="text-muted small mb-0">Search thousands of e-books, notes, and tutorial videos across all subjects</p>
          </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
          <div class="text-center p-4 h-100" style="background: var(--br-card); border-radius: 16px; border: 1px solid var(--br-border);">
            <div class="mb-3 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: var(--br-violet-dim); border-radius: 20px; font-size: 2.5rem;">
              ⬇️
            </div>
            <h5 class="fw-semibold mb-2">Download & Learn</h5>
            <p class="text-muted small mb-0">Download notes and e-books instantly. Watch problem-solving videos anytime</p>
          </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
          <div class="text-center p-4 h-100" style="background: var(--br-card); border-radius: 16px; border: 1px solid var(--br-border);">
            <div class="mb-3 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: var(--br-teal-dim); border-radius: 20px; font-size: 2.5rem;">
              ⬆️
            </div>
            <h5 class="fw-semibold mb-2">Share Knowledge</h5>
            <p class="text-muted small mb-0">Upload your notes, e-books, and tutorial videos to help others learn</p>
          </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
          <div class="text-center p-4 h-100" style="background: var(--br-card); border-radius: 16px; border: 1px solid var(--br-border);">
            <div class="mb-3 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: rgba(78,203,113,.1); border-radius: 20px; font-size: 2.5rem;">
              🧠
            </div>
            <h5 class="fw-semibold mb-2">Get Expert Help</h5>
            <p class="text-muted small mb-0">Connect with verified experts for personalized guidance and consultations</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php if (false): // Hide featured experts section for now 
  ?>
    <!-- ======= FEATURED EXPERTS ======= -->
    <section class="py-5">
      <div class="container">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
          <div>
            <div class="br-section-label">Top Thinkers</div>
            <h2 class="br-section-title fs-2 mb-0">Featured Experts</h2>
          </div>
          <a href="<?= APP_URL ?>/pages/browse.php" class="btn br-btn-outline">View All Experts <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-3">
          <?php foreach ($experts as $i => $e): ?>
            <div class="col-12 col-sm-6 col-lg-3">
              <a href="<?= APP_URL ?>/pages/expert-profile.php?id=<?= $e['user_id'] ?>" class="text-decoration-none">
                <div class="br-card br-expert-card p-4 h-100">
                  <?php if ($e['is_verified']): ?>
                    <div class="position-absolute top-0 end-0 m-3">
                      <span class="br-badge br-badge-teal"><i class="bi bi-check-circle-fill me-1"></i>Verified</span>
                    </div>
                  <?php endif; ?>
                  <div class="br-expert-avatar <?= $avColors[$i % count($avColors)] ?> mb-3">
                    <?= strtoupper(substr($e['full_name'], 0, 2)) ?>
                  </div>
                  <div class="fw-semibold"><?= htmlspecialchars($e['full_name']) ?></div>
                  <div class="text-muted small mb-3"><?= htmlspecialchars($e['headline'] ?? '') ?></div>
                  <div class="d-flex flex-wrap gap-1 mb-3">
                    <?php
                    $tags = json_decode($e['expertise_areas'] ?? '[]', true) ?: [];
                    foreach (array_slice($tags, 0, 3) as $tag): ?>
                      <span class="badge" style="background:var(--br-dark3);color:var(--br-text2);border:1px solid var(--br-border);font-weight:400"><?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                  </div>
                  <div class="d-flex justify-content-between align-items-center pt-3" style="border-top:1px solid var(--br-border)">
                    <div>
                      <div class="mono text-gold"><?= '$' . number_format($e['rate_per_session'], 0) ?><span class="text-muted" style="font-size:.75rem">/session</span></div>
                      <div class="text-subtle" style="font-size:.72rem"><?= number_format($e['total_sessions']) ?> sessions</div>
                    </div>
                    <div class="text-end">
                      <div class="small text-muted">⭐ <?= number_format($e['average_rating'], 1) ?> <span class="text-subtle">(<?= $e['total_reviews'] ?>)</span></div>
                    </div>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <!-- ======= VALUE PROPS ======= -->
  <section class="py-5 bg-dark2">
    <div class="container">
      <div class="mb-4 text-center">
        <div class="br-section-label">Why Choose BrainRent</div>
        <h2 class="br-section-title fs-2">Everything You Need to Succeed</h2>
      </div>
      <div class="row g-4">
        <div class="col-12 col-md-6 col-lg-3">
          <div class="p-4 h-100" style="background:var(--br-dark3);border-radius:18px;border:1px solid var(--br-border)">
            <div class="mb-3" style="font-size:2rem">📚</div>
            <h5 class="fw-semibold mb-2">Vast Library</h5>
            <p class="text-muted small mb-0">Access thousands of e-books, study notes, and educational videos. Free downloads, anytime, anywhere.</p>
          </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
          <div class="p-4 h-100" style="background:var(--br-dark3);border-radius:18px;border:1px solid var(--br-border)">
            <div class="mb-3" style="font-size:2rem">🤝</div>
            <h5 class="fw-semibold mb-2">Community Driven</h5>
            <p class="text-muted small mb-0">Share your knowledge with the world. Upload notes, books, and videos to help others learn.</p>
          </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
          <div class="p-4 h-100" style="background:var(--br-dark3);border-radius:18px;border:1px solid var(--br-border)">
            <div class="mb-3" style="font-size:2rem">🎯</div>
            <h5 class="fw-semibold mb-2">Easy to Use</h5>
            <p class="text-muted small mb-0">Intuitive interface. Search, filter, and find exactly what you need in seconds. One-click downloads.</p>
          </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
          <div class="p-4 h-100" style="background:var(--br-dark3);border-radius:18px;border:1px solid var(--br-border)">
            <div class="mb-3" style="font-size:2rem">✨</div>
            <h5 class="fw-semibold mb-2">Expert Help Available</h5>
            <p class="text-muted small mb-0">Need personalized guidance? Connect with verified experts for one-on-one consultations.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= RECENT CONTENT ======= -->
  <section class="py-5">
    <div class="container">
      <div class="mb-4 text-center">
        <div class="br-section-label">Latest Uploads</div>
        <h2 class="br-section-title fs-2">Recently Added Content</h2>
        <p class="text-muted">Fresh content from our community</p>
      </div>

      <div class="row g-4">
        <?php
        // Get recent e-books
        $recentEbooks = $db->fetchAll("SELECT title, category, created_at FROM libraries WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3");
        // Get recent notes
        $recentNotes = $db->fetchAll("SELECT title, subject, created_at FROM notes WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3");
        // Get recent videos
        $recentVideos = $db->fetchAll("SELECT title, problem_type, created_at FROM problem_solving_videos WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3");
        ?>

        <!-- Recent E-Books -->
        <div class="col-md-4">
          <div class="br-card p-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div style="width: 40px; height: 40px; background: var(--br-gold-dim); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">📚</div>
              <div>
                <h6 class="fw-semibold mb-0">Recent E-Books</h6>
                <small class="text-muted">Latest additions</small>
              </div>
            </div>
            <?php if (empty($recentEbooks)): ?>
              <p class="text-muted small">No e-books yet. Be the first to upload!</p>
            <?php else: ?>
              <?php foreach ($recentEbooks as $book): ?>
                <div class="mb-2 pb-2" style="border-bottom: 1px solid var(--br-border);">
                  <div class="small fw-medium"><?= htmlspecialchars($book['title']) ?></div>
                  <div class="text-subtle" style="font-size: .7rem;">
                    <?= htmlspecialchars($book['category'] ?? 'General') ?> • <?= date('M j', strtotime($book['created_at'])) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
            <a href="<?= APP_URL ?>/pages/libraries.php" class="btn br-btn-outline w-100 mt-3">View All E-Books</a>
          </div>
        </div>

        <!-- Recent Notes -->
        <div class="col-md-4">
          <div class="br-card p-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div style="width: 40px; height: 40px; background: var(--br-violet-dim); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">📝</div>
              <div>
                <h6 class="fw-semibold mb-0">Recent Notes</h6>
                <small class="text-muted">Study materials</small>
              </div>
            </div>
            <?php if (empty($recentNotes)): ?>
              <p class="text-muted small">No notes yet. Share your notes!</p>
            <?php else: ?>
              <?php foreach ($recentNotes as $note): ?>
                <div class="mb-2 pb-2" style="border-bottom: 1px solid var(--br-border);">
                  <div class="small fw-medium"><?= htmlspecialchars($note['title']) ?></div>
                  <div class="text-subtle" style="font-size: .7rem;">
                    <?= htmlspecialchars($note['subject'] ?? 'General') ?> • <?= date('M j', strtotime($note['created_at'])) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
            <a href="<?= APP_URL ?>/pages/notes.php" class="btn br-btn-outline w-100 mt-3">View All Notes</a>
          </div>
        </div>

        <!-- Recent Videos -->
        <div class="col-md-4">
          <div class="br-card p-4 h-100">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div style="width: 40px; height: 40px; background: var(--br-teal-dim); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">🎥</div>
              <div>
                <h6 class="fw-semibold mb-0">Recent Videos</h6>
                <small class="text-muted">Tutorial solutions</small>
              </div>
            </div>
            <?php if (empty($recentVideos)): ?>
              <p class="text-muted small">No videos yet. Upload tutorials!</p>
            <?php else: ?>
              <?php foreach ($recentVideos as $video): ?>
                <div class="mb-2 pb-2" style="border-bottom: 1px solid var(--br-border);">
                  <div class="small fw-medium"><?= htmlspecialchars($video['title']) ?></div>
                  <div class="text-subtle" style="font-size: .7rem;">
                    <?= htmlspecialchars($video['problem_type'] ?? 'General') ?> • <?= date('M j', strtotime($video['created_at'])) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
            <a href="<?= APP_URL ?>/pages/problem-solving.php" class="btn br-btn-outline w-100 mt-3">View All Videos</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= CTA BANNER ======= -->
  <section class="py-5 text-center bg-dark2" id="pricing">
    <div class="container" style="max-width:750px">
      <h2 class="br-section-title" style="font-size:2.4rem">
        Join thousands of learners
        <span class="text-warning">growing together</span>
      </h2>
      <p class="text-muted mt-3 mb-4">Access free resources, share your knowledge, and accelerate your learning journey today.</p>
      <div class="d-flex gap-3 justify-content-center flex-wrap">
        <a href="<?= APP_URL ?>/pages/auth.php?tab=signup" class="btn br-btn-gold btn-lg px-5">Get Started Free</a>
        <a href="<?= APP_URL ?>/pages/libraries.php" class="btn br-btn-ghost btn-lg px-5">Explore Library</a>
      </div>
      <div class="mt-4">
        <small class="text-muted">✓ Free forever ✓ No credit card required ✓ Instant access</small>
      </div>
    </div>
  </section>

</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>