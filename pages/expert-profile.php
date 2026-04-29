<?php
// pages/expert-profile.php — Expert Public Profile
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$expertUserId = (int) ($_GET['id'] ?? 0);
if (!$expertUserId) {
  header('Location: ' . APP_URL . '/pages/browse.php');
  exit;
}

$db = Database::getInstance();
ensureUserProfileColumns($db);
ensureExpertProfilesColumns($db);

$expert = $db->fetchOne(
  "SELECT ep.*, ep.current_role_name AS `current_role`, u.full_name, u.profile_photo, u.country
     FROM expert_profiles ep INNER JOIN users u ON ep.user_id = u.id
     WHERE ep.user_id = ? AND u.is_active = 1",
  [$expertUserId]
);
if (!$expert) {
  header('Location: ' . APP_URL . '/pages/browse.php');
  exit;
}

$reviews = $db->fetchAll(
  "SELECT r.*, u.full_name AS reviewer_name, u.profile_photo AS reviewer_photo
     FROM reviews r INNER JOIN users u ON r.reviewer_id = u.id
     WHERE r.expert_id = ? AND r.is_public = 1
     ORDER BY r.created_at DESC
     LIMIT 5",
  [$expertUserId]
);

$tags = json_decode($expert['expertise_areas'] ?? '[]', true) ?: [];
$avColors = ['av-1', 'av-2', 'av-3', 'av-4', 'av-5', 'av-6'];
$avColor = $avColors[$expertUserId % count($avColors)];

$title = $expert['full_name'];
require_once __DIR__ . '/../includes/header.php';
?>
<main class="py-4" style="padding-top:80px!important">
  <div class="container">
    <div class="mb-3">
      <a href="<?= APP_URL ?>/pages/browse.php" class="text-muted small text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>Browse Experts
      </a>
    </div>

    <div class="row g-4">
      <!-- ===== LEFT COLUMN ===== -->
      <div class="col-12 col-lg-8">

        <!-- Profile Header -->
        <div class="br-profile-header d-flex gap-4 flex-wrap mb-4">
          <div class="br-avatar-lg <?= $avColor ?>" style="flex-shrink:0">
            <?php if ($expert['profile_photo']): ?>
              <img src="<?= htmlspecialchars($expert['profile_photo']) ?>"
                style="width:100%;height:100%;object-fit:cover;border-radius:20px">
            <?php else: ?>
              <?= strtoupper(substr($expert['full_name'], 0, 2)) ?>
            <?php endif; ?>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
              <h1 class="fs-3 fw-bold mb-0" style="font-family:'Playfair Display',serif">
                <?= htmlspecialchars($expert['full_name']) ?>
              </h1>
              <?php if ($expert['is_verified']): ?>
                <span class="br-badge br-badge-teal"><i class="bi bi-check-circle-fill me-1"></i>Verified</span>
              <?php endif; ?>
              <?php if ($expert['is_available']): ?>
                <span class="br-badge br-badge-success">🟢 Available</span>
              <?php endif; ?>
            </div>
            <p class="text-muted mb-3"><?= htmlspecialchars($expert['headline'] ?? '') ?></p>
            <div class="d-flex flex-wrap gap-3 small text-muted mb-3">
              <span>⭐ <strong class="text-white"><?= number_format($expert['average_rating'], 1) ?></strong>
                (<?= $expert['total_reviews'] ?> reviews)</span>
              <span>📋 <strong class="text-white"><?= number_format($expert['total_sessions']) ?></strong>
                sessions</span>
              <span>⏱️ Responds in <strong class="text-warning">~<?= $expert['max_response_hours'] ?>
                  hours</strong></span>
              <?php if ($expert['country']): ?><span>🌍
                  <?= htmlspecialchars($expert['country']) ?></span><?php endif; ?>
            </div>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($tags as $t): ?>
                <span class="badge"
                  style="background:var(--br-dark3);color:var(--br-text2);border:1px solid var(--br-border);font-weight:400"><?= htmlspecialchars($t) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- About -->
        <div class="br-profile-section mb-4">
          <h5 class="fw-semibold mb-3 pb-2" style="border-bottom:1px solid var(--br-border)">About</h5>
          <p class="text-muted small lh-lg">
            <?= nl2br(htmlspecialchars($expert['bio'] ?? 'This expert has not added a bio yet.')) ?>
          </p>
          <?php if ($expert['current_role'] || $expert['company'] || $expert['linkedin_url']): ?>
            <hr class="br-divider">
            <div class="row g-3 small">
              <?php if ($expert['current_role'] && $expert['company']): ?>
                <div class="col-6">
                  <div class="text-subtle mb-1">Current Role</div>
                  <div><?= htmlspecialchars($expert['current_role'] . ' @ ' . $expert['company']) ?></div>
                </div>
              <?php endif; ?>
              <?php if ($expert['experience_years']): ?>
                <div class="col-6">
                  <div class="text-subtle mb-1">Experience</div>
                  <div><?= $expert['experience_years'] ?> years</div>
                </div>
              <?php endif; ?>
              <?php if ($expert['linkedin_url']): ?>
                <div class="col-12"><a href="<?= htmlspecialchars($expert['linkedin_url']) ?>" target="_blank"
                    class="text-violet text-decoration-none small"><i class="bi bi-linkedin me-1"></i>LinkedIn Profile</a>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Stats -->
        <div class="br-profile-section mb-4">
          <h5 class="fw-semibold mb-3 pb-2" style="border-bottom:1px solid var(--br-border)">Performance Stats</h5>
          <div class="row g-3">
            <?php
            $stats = [
              ['Response Rate', '98%', 98],
              ['On-time Delivery', '96%', 96],
              ['Client Satisfaction', '99%', 99],
            ];
            foreach ($stats as $s): ?>
              <div class="col-12">
                <div class="d-flex justify-content-between small mb-1">
                  <span class="text-muted"><?= $s[0] ?></span>
                  <span><?= $s[1] ?></span>
                </div>
                <div class="br-progress">
                  <div class="br-progress-bar" style="width:<?= $s[2] ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Reviews -->
        <div class="br-profile-section">
          <h5 class="fw-semibold mb-3 pb-2" style="border-bottom:1px solid var(--br-border)">
            Client Reviews <span class="text-subtle fw-normal" style="font-size:.85rem">(<?= $expert['total_reviews'] ?>
              total)</span>
          </h5>
          <?php if ($reviews):
            foreach ($reviews as $r): ?>
              <div class="py-3" style="border-bottom:1px solid var(--br-border)">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <div class="fw-medium small"><?= htmlspecialchars($r['reviewer_name']) ?></div>
                    <div><?php for ($s = 1; $s <= 5; $s++)
                            echo $s <= $r['rating'] ? '⭐' : '☆'; ?></div>
                  </div>
                  <div class="text-subtle small"><?= date('M j, Y', strtotime($r['created_at'])) ?></div>
                </div>
                <p class="text-muted small mb-0 lh-lg">"<?= htmlspecialchars($r['review_text'] ?? '') ?>"</p>
              </div>
            <?php endforeach;
          else: ?>
            <p class="text-muted small">No reviews yet.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- ===== BOOKING SIDEBAR ===== -->
      <div class="col-12 col-lg-4">
        <div class="br-booking-card">
          <div class="br-booking-price">$<?= number_format($expert['rate_per_session'], 0) ?> <span class="text-muted"
              style="font-size:1rem;font-family:'DM Sans',sans-serif;font-weight:400">/ session</span></div>
          <p class="text-subtle small mt-1">~<?= $expert['session_duration_minutes'] ?> minutes of focused thinking</p>
          <hr class="br-divider">
          <div class="d-flex flex-column gap-2 mb-4 small text-muted">
            <div><i class="bi bi-check-lg text-success me-2"></i>Written analysis + key insights</div>
            <div><i class="bi bi-check-lg text-success me-2"></i>Recorded voice response</div>
            <div><i class="bi bi-check-lg text-success me-2"></i>Action items & resource links</div>
            <div><i class="bi bi-check-lg text-success me-2"></i>Response within <?= $expert['max_response_hours'] ?>
              hours</div>
            <div><i class="bi bi-check-lg text-success me-2"></i>Escrow-protected payment</div>
          </div>
          <a href="<?= APP_URL ?>/pages/submit-problem.php?expert_id=<?= $expertUserId ?>"
            class="btn br-btn-gold w-100 py-3 mb-2 fw-semibold">
            Submit Your Problem <i class="bi bi-arrow-right ms-1"></i>
          </a>
          <button id="btn-ask-question" class="btn br-btn-ghost w-100 py-2">
            <i class="bi bi-chat-dots me-2"></i>Ask a Quick Question
          </button>
          
          <!-- Chat Widget Inline -->
          <div id="chat-widget" class="br-chat-widget shadow-lg mt-3" style="display:none; background:var(--br-card); border-radius:15px; border:1px solid var(--br-border); overflow:hidden; flex-direction:column; height:350px;">
            <!-- Header -->
            <div class="p-3 d-flex justify-content-between align-items-center" style="background:var(--br-gold); color:#fff;">
              <div class="fw-semibold" style="font-size:0.9rem;">
                <i class="bi bi-chat-dots-fill me-2"></i><?= htmlspecialchars($expert['full_name']) ?>
              </div>
              <button id="btn-close-chat" class="btn btn-sm text-white" style="background:none; border:none; padding:0; line-height:1;"><i class="bi bi-x-lg"></i></button>
            </div>
            <!-- Messages Area -->
            <div id="chat-messages" class="p-3" style="flex:1; overflow-y:auto; background:var(--br-bg-base); display:flex; flex-direction:column; gap:10px; font-size:0.85rem">
              <!-- Messages will be injected here -->
            </div>
            <!-- Input Area -->
            <div class="p-2 border-top d-flex gap-2" style="background:var(--br-card);">
              <input type="text" id="chat-input" class="form-control br-form-control" placeholder="Type a message..." style="flex:1; font-size:0.85rem">
              <button id="btn-send-chat" class="btn br-btn-gold" style="border-radius:10px;"><i class="bi bi-send-fill"></i></button>
            </div>
          </div>

          <div class="text-center text-subtle mt-3" style="font-size:.72rem">
            <i class="bi bi-lock-fill me-1"></i>15% platform fee included · Secured by escrow
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
  const expertId = <?= (int) $expertUserId ?>;
  const btnAsk = document.getElementById('btn-ask-question');
  const chatWidget = document.getElementById('chat-widget');
  const btnCloseChat = document.getElementById('btn-close-chat');
  const chatMessages = document.getElementById('chat-messages');
  const chatInput = document.getElementById('chat-input');
  const btnSendChat = document.getElementById('btn-send-chat');
  let chatInterval = null;

  btnAsk.addEventListener('click', () => {
    chatWidget.style.display = chatWidget.style.display === 'none' ? 'flex' : 'none';
    if (chatWidget.style.display === 'flex') {
      fetchMessages();
      if (!chatInterval) chatInterval = setInterval(fetchMessages, 3000);
    } else {
      if (chatInterval) {
        clearInterval(chatInterval);
        chatInterval = null;
      }
    }
  });

  btnCloseChat.addEventListener('click', () => {
    chatWidget.style.display = 'none';
    if (chatInterval) {
      clearInterval(chatInterval);
      chatInterval = null;
    }
  });

  async function fetchMessages() {
    try {
      const res = await fetch(`<?= APP_URL ?>/api/chat.php?action=fetch&other_user_id=${expertId}`);
      const data = await res.json();
      if (data.success) {
        chatMessages.innerHTML = '';
        data.messages.forEach(msg => {
          const isMine = msg.sender_id === data.current_user_id;
          const div = document.createElement('div');
          div.style.padding = '8px 12px';
          div.style.borderRadius = '15px';
          div.style.maxWidth = '80%';
          div.style.fontSize = '0.9rem';
          div.textContent = msg.message_text;

          if (isMine) {
            div.style.background = 'var(--br-gold)';
            div.style.color = '#fff';
            div.style.alignSelf = 'flex-end';
            div.style.borderBottomRightRadius = '2px';
          } else {
            div.style.background = 'var(--br-dark3)';
            div.style.color = 'var(--br-text)';
            div.style.border = '1px solid var(--br-border)';
            div.style.alignSelf = 'flex-start';
            div.style.borderBottomLeftRadius = '2px';
          }
          chatMessages.appendChild(div);
        });
        chatMessages.scrollTop = chatMessages.scrollHeight;
      }
    } catch(e) { console.error('Chat error', e); }
  }

  async function sendMessage() {
    const text = chatInput.value.trim();
    if (!text) return;

    chatInput.value = '';
    btnSendChat.disabled = true;

    const fd = new URLSearchParams();
    fd.append('action', 'send');
    fd.append('receiver_id', expertId);
    fd.append('message_text', text);

    try {
      const res = await fetch(`<?= APP_URL ?>/api/chat.php`, {
        method: 'POST',
        body: fd
      });
      const data = await res.json();
      if (data.success) {
        fetchMessages();
      } else if (data.error === 'Not authenticated') {
        alert('Please login to ask a question');
        window.location.href = '<?= APP_URL ?>/pages/auth.php';
      }
    } catch(e) { console.error(e); }
    
    btnSendChat.disabled = false;
  }

  btnSendChat.addEventListener('click', sendMessage);
  chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') sendMessage();
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>