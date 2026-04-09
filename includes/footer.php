<?php
// includes/footer.php
?>
<!-- ======= FOOTER ======= -->
<footer class="br-footer mt-5">
  <div class="container">
    <div class="row g-4 py-5">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span class="br-logo-icon" style="font-size:1.4rem">🧠</span>
          <span class="br-brand fs-5">Brain<span class="text-warning">Rent</span></span>
        </div>
        <p class="text-muted small">The async expert thinking marketplace. Get structured insights from verified experts — no meetings required.</p>
        <div class="d-flex gap-3 mt-3">
          <a href="#" class="br-social-link"><i class="bi bi-twitter-x"></i></a>
          <a href="#" class="br-social-link"><i class="bi bi-linkedin"></i></a>
          <a href="#" class="br-social-link"><i class="bi bi-instagram"></i></a>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="fw-semibold mb-3">Platform</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="<?= APP_URL ?>/pages/browse.php" class="br-footer-link">Browse Experts</a></li>
          <li class="mb-2"><a href="<?= APP_URL ?>/pages/index.php#how-it-works" class="br-footer-link">How It Works</a></li>
          <li class="mb-2"><a href="<?= APP_URL ?>/pages/auth.php?tab=signup&type=expert" class="br-footer-link">Become an Expert</a></li>
          <li class="mb-2"><a href="#" class="br-footer-link">Pricing</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="fw-semibold mb-3">Support</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="#" class="br-footer-link">Help Center</a></li>
          <li class="mb-2"><a href="#" class="br-footer-link">Dispute Policy</a></li>
          <li class="mb-2"><a href="#" class="br-footer-link">Contact Us</a></li>
          <li class="mb-2"><a href="#" class="br-footer-link">Status Page</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="fw-semibold mb-3">Legal</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="#" class="br-footer-link">Privacy Policy</a></li>
          <li class="mb-2"><a href="#" class="br-footer-link">Terms of Service</a></li>
          <li class="mb-2"><a href="#" class="br-footer-link">Cookie Policy</a></li>
          <li class="mb-2"><a href="#" class="br-footer-link">Refund Policy</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="fw-semibold mb-3">Company</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="#" class="br-footer-link">About</a></li>
          <li class="mb-2"><a href="#" class="br-footer-link">Blog</a></li>
          <li class="mb-2"><a href="#" class="br-footer-link">Careers</a></li>
          <li class="mb-2"><a href="#" class="br-footer-link">Press</a></li>
        </ul>
      </div>
    </div>
    <div class="border-top py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="text-muted small">© <?= date('Y') ?> BrainRent. All rights reserved.</span>
      <span class="text-muted small">🔒 Payments secured by Razorpay · SSL encrypted</span>
    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>

</html>