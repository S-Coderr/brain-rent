<?php
// pages/index.php — Landing Page (Redesigned 3D Illustration Theme)
$title = 'BrainRent - Learn, Share, Solve';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();

// Get stats
$stats = [
  'ebooks' => $db->fetchOne("SELECT COUNT(*) as count FROM libraries WHERE is_active = 1")['count'] ?? 0,
  'notes' => $db->fetchOne("SELECT COUNT(*) as count FROM notes WHERE is_active = 1")['count'] ?? 0,
  'videos' => $db->fetchOne("SELECT COUNT(*) as count FROM problem_solving_videos WHERE is_active = 1")['count'] ?? 0,
  'experts' => $db->fetchOne("SELECT COUNT(*) as count FROM expert_profiles WHERE is_verified = 1")['count'] ?? 0
];
?>
<main style="overflow-x: hidden;">

  <!-- ======= HERO ======= -->
  <section class="br-hero position-relative pt-5 pb-5" style="background: var(--br-bg-base); min-height: 85vh; display: flex; align-items: center;">
    <div class="container position-relative z-1 pt-5">
      <div class="row align-items-center g-5">
        <div class="col-lg-6 order-2 order-lg-1 text-center text-lg-start reveal-up">
          <div class="br-eyebrow mb-3 d-inline-flex align-items-center">
            <span class="live-indicator"></span>
            <span class="fw-medium text-muted" style="letter-spacing: 1px;">LIVE E-LEARNING REIMAGINED</span>
          </div>
          <h1 class="display-3 fw-bold mb-4" style="color: var(--br-text); line-height: 1.1;">
            We create <br>
            <span style="color: var(--br-gold);">solutions</span> for <br>
            your learning.
          </h1>
          <p class="lead text-muted mb-5" style="max-width: 500px; margin: 0 auto 0 0;">
            Our platform keeps a keen eye on emerging trends and technologies to ensure your educational journey remains cutting-edge.
          </p>
          <div class="d-flex gap-3 justify-content-center justify-content-lg-start flex-wrap">
            <a href="<?= APP_URL ?>/pages/auth.php?tab=signup" class="btn br-btn-gold btn-lg px-5 py-3 rounded-pill shadow-sm br-hover-glow" style="font-weight: 600;">
              Get Started
            </a>
            <a href="#services" class="btn br-btn-ghost btn-lg px-4 py-3 rounded-pill d-flex align-items-center br-hover-glow">
              <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px; color: var(--br-gold);">
                <i class="bi bi-play-fill fs-5"></i>
              </div>
              Explore more
            </a>
          </div>
        </div>
        <div class="col-lg-6 order-1 order-lg-2 position-relative reveal-up" style="transition-delay: 0.2s;">
          <!-- Floating Shapes -->
          <div class="position-absolute br-float-slow" style="width: 40px; height: 40px; background: #fce8cd; border-radius: 50%; top: 10%; right: 10%;"></div>
          <div class="position-absolute br-float" style="width: 20px; height: 20px; background: #fce8cd; border-radius: 50%; bottom: 20%; left: 0%;"></div>
          <div class="position-absolute br-float-slow" style="width: 15px; height: 15px; background: #fce8cd; border-radius: 50%; top: 40%; right: 40%;"></div>
          
          <img src="<?= APP_URL ?>/assets/img/hero_3d.png" alt="3D Learning Illustration" class="img-fluid br-float br-3d-image border-0 bg-transparent shadow-none" style="max-height: 550px; width: auto; display: block; margin: 0 auto; object-fit: contain;">
        </div>
      </div>
    </div>
  </section>

  <!-- ======= SERVICES ======= -->
  <section id="services" class="py-5" style="background: var(--br-card2); position: relative;">
    <!-- Abstract bubbles -->
    <div class="position-absolute br-float" style="width: 30px; height: 30px; background: #fce8cd; border-radius: 50%; top: 5%; left: 10%;"></div>
    
    <div class="container py-5 reveal-up">
      <div class="text-center mb-5">
        <h2 class="fw-bold mb-3" style="color: var(--br-text);">We Provide The Best <span style="color: var(--br-gold);">Services</span></h2>
        <p class="text-muted" style="max-width: 600px; margin: 0 auto;">Let us unleash the full potential of your educational experience with our data-driven strategies.</p>
      </div>

      <div class="row g-4 justify-content-center">
        <!-- Service 1 -->
        <div class="col-12 col-md-6 col-lg-3 reveal-up" style="transition-delay: 0.1s;">
          <div class="card border-0 h-100 p-4 br-glass-card br-hover-glow" style="border-radius: 20px;">
            <div class="mb-4 d-inline-flex align-items-center justify-content-center shadow-sm br-float-slow" style="width: 60px; height: 60px; background: #E8743B; border-radius: 15px; color: #fff; font-size: 1.5rem;">
              <i class="bi bi-book"></i>
            </div>
            <h5 class="fw-bold mb-3">E-Books</h5>
            <p class="text-muted small mb-4">Access thousands of free e-books across all subjects. Upload and share your favorite books.</p>
            <a href="<?= APP_URL ?>/pages/libraries.php" class="text-decoration-none fw-semibold" style="color: #E8743B; font-size: 0.9rem;">Read more <i class="bi bi-arrow-right ms-1"></i></a>
          </div>
        </div>
        
        <!-- Service 2 -->
        <div class="col-12 col-md-6 col-lg-3 reveal-up" style="transition-delay: 0.2s;">
          <div class="card border-0 h-100 p-4 br-glass-card br-hover-glow" style="border-radius: 20px;">
            <div class="mb-4 d-inline-flex align-items-center justify-content-center shadow-sm br-float" style="width: 60px; height: 60px; background: #78B388; border-radius: 15px; color: #fff; font-size: 1.5rem;">
              <i class="bi bi-journal-text"></i>
            </div>
            <h5 class="fw-bold mb-3">Notes</h5>
            <p class="text-muted small mb-4">Upload your study notes and access notes from other students. Download and collaborate.</p>
            <a href="<?= APP_URL ?>/pages/notes.php" class="text-decoration-none fw-semibold" style="color: #78B388; font-size: 0.9rem;">Read more <i class="bi bi-arrow-right ms-1"></i></a>
          </div>
        </div>

        <!-- Service 3 -->
        <div class="col-12 col-md-6 col-lg-3 reveal-up" style="transition-delay: 0.3s;">
          <div class="card border-0 h-100 p-4 br-glass-card br-hover-glow" style="border-radius: 20px;">
            <div class="mb-4 d-inline-flex align-items-center justify-content-center shadow-sm br-float-slow" style="width: 60px; height: 60px; background: #62A4AD; border-radius: 15px; color: #fff; font-size: 1.5rem;">
              <i class="bi bi-play-circle"></i>
            </div>
            <h5 class="fw-bold mb-3">Videos</h5>
            <p class="text-muted small mb-4">Watch video solutions to complex problems. Upload your own tutorials to help others.</p>
            <a href="<?= APP_URL ?>/pages/problem-solving.php" class="text-decoration-none fw-semibold" style="color: #62A4AD; font-size: 0.9rem;">Read more <i class="bi bi-arrow-right ms-1"></i></a>
          </div>
        </div>

        <!-- Service 4 -->
        <div class="col-12 col-md-6 col-lg-3 reveal-up" style="transition-delay: 0.4s;">
          <div class="card border-0 h-100 p-4 br-glass-card br-hover-glow" style="border-radius: 20px;">
            <div class="mb-4 d-inline-flex align-items-center justify-content-center shadow-sm br-float" style="width: 60px; height: 60px; background: #e05e5e; border-radius: 15px; color: #fff; font-size: 1.5rem;">
              <i class="bi bi-person-badge"></i>
            </div>
            <h5 class="fw-bold mb-3">Experts</h5>
            <p class="text-muted small mb-4">Connect with verified experts for personalized guidance, consultations, and quick problem solving.</p>
            <a href="<?= APP_URL ?>/pages/browse.php" class="text-decoration-none fw-semibold" style="color: #e05e5e; font-size: 0.9rem;">Read more <i class="bi bi-arrow-right ms-1"></i></a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= SIMPLE SOLUTIONS ======= -->
  <section class="py-5 position-relative" style="background: var(--br-dark3);">
    <div class="container py-5">
      <div class="row align-items-center g-5">
        <div class="col-lg-6 position-relative reveal-up">
           <img src="<?= APP_URL ?>/assets/img/simple_3d.png" alt="3D Sofa Illustration" class="img-fluid br-float-slow br-3d-image border-0 bg-transparent shadow-none" style="max-height: 450px; display: block; margin: 0 auto;">
           
           <!-- Floating dots -->
           <div class="position-absolute br-float" style="width: 15px; height: 15px; background: #E8743B; border-radius: 50%; opacity: 0.3; top: 10%; right: 20%;"></div>
           <div class="position-absolute br-float-slow" style="width: 25px; height: 25px; background: #E8743B; border-radius: 50%; opacity: 0.2; bottom: 10%; left: 10%;"></div>
        </div>
        <div class="col-lg-6 reveal-up" style="transition-delay: 0.2s;">
          <h2 class="fw-bold mb-3">Simple <span style="color: var(--br-gold);">Solutions!</span></h2>
          <p class="text-muted mb-4">We understand that no two learners are alike. That's why we take the time to understand your unique needs.</p>
          
          <ul class="list-unstyled d-flex flex-column gap-4 mb-5">
            <li class="d-flex align-items-center">
              <div class="d-flex align-items-center justify-content-center rounded-circle me-3 fw-bold text-white shadow-sm" style="width: 35px; height: 35px; background: var(--br-gold); font-size: 0.9rem;">1</div>
              <div class="fw-semibold text-dark">Register an account</div>
            </li>
            <li class="d-flex align-items-center">
              <div class="d-flex align-items-center justify-content-center rounded-circle me-3 fw-bold text-white shadow-sm" style="width: 35px; height: 35px; background: var(--br-gold); font-size: 0.9rem;">2</div>
              <div class="fw-semibold text-dark">Browse or Search resources</div>
            </li>
            <li class="d-flex align-items-center">
              <div class="d-flex align-items-center justify-content-center rounded-circle me-3 fw-bold text-white shadow-sm" style="width: 35px; height: 35px; background: var(--br-gold); font-size: 0.9rem;">3</div>
              <div class="fw-semibold text-dark">Post a problem to Experts</div>
            </li>
            <li class="d-flex align-items-center">
              <div class="d-flex align-items-center justify-content-center rounded-circle me-3 fw-bold text-white shadow-sm" style="width: 35px; height: 35px; background: var(--br-gold); font-size: 0.9rem;">4</div>
              <div class="fw-semibold text-dark">Learn and Grow</div>
            </li>
          </ul>

          <div class="d-flex gap-3 flex-wrap">
            <a href="<?= APP_URL ?>/pages/auth.php" class="btn br-btn-gold px-4 py-2 rounded-pill shadow-sm">Get Started</a>
            <a href="<?= APP_URL ?>/pages/libraries.php" class="btn btn-outline-secondary px-4 py-2 rounded-pill bg-white border-white shadow-sm" style="color: var(--br-gold);">Explore</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= STATS BANNER ======= -->
  <section class="py-5 text-white reveal-up" style="background: var(--br-gold); transition-delay: 0.1s;">
    <div class="container py-4">
      <div class="row text-center g-4">
        <div class="col-6 col-md-3">
          <h2 class="display-5 fw-bold mb-2"><?= number_format($stats['ebooks']) ?>+</h2>
          <p class="mb-0 text-white-50 fw-semibold text-uppercase" style="letter-spacing: 1px; font-size: 0.85rem;">E-Books</p>
        </div>
        <div class="col-6 col-md-3">
          <h2 class="display-5 fw-bold mb-2"><?= number_format($stats['notes']) ?>+</h2>
          <p class="mb-0 text-white-50 fw-semibold text-uppercase" style="letter-spacing: 1px; font-size: 0.85rem;">Shared Notes</p>
        </div>
        <div class="col-6 col-md-3">
          <h2 class="display-5 fw-bold mb-2"><?= number_format($stats['videos']) ?>+</h2>
          <p class="mb-0 text-white-50 fw-semibold text-uppercase" style="letter-spacing: 1px; font-size: 0.85rem;">Tutorials</p>
        </div>
        <div class="col-6 col-md-3">
          <h2 class="display-5 fw-bold mb-2"><?= number_format($stats['experts']) ?>+</h2>
          <p class="mb-0 text-white-50 fw-semibold text-uppercase" style="letter-spacing: 1px; font-size: 0.85rem;">Experts</p>
        </div>
      </div>
    </div>
  </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const reveals = document.querySelectorAll('.reveal-up');
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('active');
        // observer.unobserve(entry.target); // Optional: stop observing once revealed
      }
    });
  }, {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px"
  });

  reveals.forEach(el => observer.observe(el));
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>