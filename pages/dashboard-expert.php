<?php
// pages/dashboard-expert.php — Expert Dashboard
$title = 'Expert Dashboard';
require_once __DIR__ . '/../config/auth.php';
requireExpert();
require_once __DIR__ . '/../includes/media_blob_helpers.php';
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$userId = currentUserId();
$hideFooter = true;

$profile = $db->fetchOne("SELECT ep.*, u.full_name FROM expert_profiles ep INNER JOIN users u ON ep.user_id = u.id WHERE ep.user_id = ?", [$userId]);
$wallet = $db->fetchOne("SELECT * FROM expert_wallet WHERE expert_user_id = ?", [$userId]);
$newReqs = $db->fetchAll(
  "SELECT tr.*, u.full_name AS client_name, pu.full_name AS preferred_expert_name,
          (CASE WHEN tr.expert_id = ? THEN 1 ELSE 0 END) AS is_assigned_to_me
   FROM thinking_requests tr
   INNER JOIN users u ON tr.client_id = u.id
   LEFT JOIN users pu ON tr.expert_id = pu.id
    WHERE (tr.status = 'submitted' AND (tr.is_global = 1 OR tr.expert_id = ?))
       OR (tr.status IN ('accepted','thinking') AND tr.expert_id = ?)
    ORDER BY tr.urgency DESC, tr.created_at",
  [$userId, $userId, $userId]
);
$activeReqs = $db->fetchAll(
  "SELECT tr.*, u.full_name AS client_name
  FROM thinking_requests tr
  INNER JOIN users u ON tr.client_id = u.id
  WHERE (tr.expert_id = ? AND tr.status IN ('submitted','accepted','thinking','completed'))
     OR (tr.is_global = 1 AND tr.status = 'accepted' AND tr.expert_id = ?)
  ORDER BY tr.created_at DESC",
  [$userId, $userId]
);
$thisMonth = $db->fetchOne("SELECT COUNT(*) AS cnt, IFNULL(SUM(p.expert_payout),0) AS earnings FROM payments p INNER JOIN thinking_requests tr ON p.request_id = tr.id WHERE p.payee_id = ? AND p.status = 'released' AND MONTH(p.released_at)=MONTH(NOW()) AND YEAR(p.released_at)=YEAR(NOW())", [$userId]);
$recentEarnings = $db->fetchAll("SELECT p.expert_payout, p.status, p.created_at, tr.title AS req_title, u.full_name AS client_name FROM payments p INNER JOIN thinking_requests tr ON p.request_id = tr.id INNER JOIN users u ON tr.client_id = u.id WHERE p.payee_id = ? ORDER BY p.created_at DESC LIMIT 10", [$userId]);
$categories = $db->fetchAll("SELECT id, name FROM expertise_categories WHERE is_active = 1 ORDER BY name");

$uploadSummary = brGetUploadedMediaSummary($db, (int) $userId);
$myUploads = brFetchUploadedMedia($db, (int) $userId, 12);
$mySolutions = $db->fetchAll(
  "SELECT res.*, tr.title AS req_title, u.full_name AS client_name
   FROM thinking_responses res
   INNER JOIN thinking_requests tr ON res.request_id = tr.id
   INNER JOIN users u ON tr.client_id = u.id
   WHERE res.expert_id = ?
   ORDER BY res.created_at DESC",
  [$userId]
);
$uploadSizeMb = $uploadSummary['total_bytes'] > 0
  ? round($uploadSummary['total_bytes'] / (1024 * 1024), 2)
  : 0;

$urgencyColors = ['normal' => 'br-badge-gray', 'urgent' => 'br-badge-gold', 'critical' => 'br-badge-danger'];
$urgencyLabels = ['normal' => 'Normal', 'urgent' => '⚡ Urgent', 'critical' => '🔥 Critical'];

$bankName = $wallet['bank_account_name'] ?? '';
$bankNumber = $wallet['bank_account_number'] ?? '';
$bankIfsc = $wallet['bank_ifsc'] ?? '';
$upiId = $wallet['upi_id'] ?? '';
$maskedAccount = $bankNumber
  ? str_repeat('*', max(0, strlen($bankNumber) - 4)) . substr($bankNumber, -4)
  : '—';
?>
<div style="padding-top:64px;display:flex;height:calc(100vh - 64px)">

  <!-- ===== SIDEBAR ===== -->
  <div class="br-sidebar">
    <div class="mb-3 px-2">
      <div class="text-subtle" style="font-size:.72rem;font-weight:600;margin-bottom:2px">EXPERT PORTAL</div>
      <div class="fw-semibold small"><?= htmlspecialchars($user['full_name']) ?></div>
      <?php if ($profile['is_verified']): ?><span class="br-badge br-badge-teal mt-1" style="font-size:.68rem">✓
          Verified</span><?php endif; ?>
    </div>
    <a href="javascript:void(0)" class="br-nav-item active" onclick="showSection('exp-overview',this)"><i
        class="bi bi-grid me-2"></i>Overview</a>
    <a href="javascript:void(0)" class="br-nav-item" onclick="showSection('exp-requests',this)" id="nav-exp-requests">
      <i class="bi bi-inbox me-2"></i>Requests
      <?php if (count($newReqs)): ?><span class="br-nav-badge"><?= count($newReqs) ?></span><?php endif; ?>
    </a>
    <a href="javascript:void(0)" class="br-nav-item" onclick="showSection('exp-respond',this)" id="nav-exp-respond"><i class="bi bi-mic me-2"></i>Submit
      Solution</a>
    <a href="javascript:void(0)" class="br-nav-item" onclick="showSection('exp-wallet',this)"><i class="bi bi-wallet2 me-2"></i>Wallet &
      Earnings</a>
    <a href="javascript:void(0)" class="br-nav-item" onclick="showSection('exp-notifications',this)">
      <i class="bi bi-bell me-2"></i>Notifications
      <span class="br-nav-badge" id="exp-notif-sidebar-badge" style="display:none">0</span>
    </a>
    <a href="javascript:void(0)" class="br-nav-item" onclick="showSection('exp-chat',this)" id="nav-exp-chat">
      <i class="bi bi-chat-dots me-2"></i>Chat
      <span class="br-nav-badge" id="exp-chat-sidebar-badge" style="display:none">0</span>
    </a>
    <a href="javascript:void(0)" class="br-nav-item" onclick="showSection('exp-uploads',this)"><i
        class="bi bi-cloud-check me-2"></i>My Uploads
    </a>
    <a href="javascript:void(0)" class="br-nav-item" onclick="showSection('exp-settings',this)"><i class="bi bi-gear me-2"></i>Profile
      Settings</a>
    <div style="margin-top:auto;padding-top:16px;border-top:1px solid var(--br-border)">
      <a href="<?= APP_URL ?>/pages/dashboard-client.php" class="br-nav-item"><i class="bi bi-person me-2"></i>Client
        Mode</a>
    </div>
  </div>

  <!-- ===== MAIN ===== -->
  <div class="br-dash-main">

    <!-- ===== OVERVIEW ===== -->
    <div id="section-exp-overview">
      <div class="dashboard-banner p-4 mb-4" style="background: white; border-radius: 20px; border: 1px solid #e0f2f1; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-left: 5px solid #4db6ac;">
        <div style="position: relative; z-index: 2; width: 100%; max-width: 600px;">
            <h1 class="display-6 fw-bold mb-2" style="color: #263238;">Welcome Back !</h1>
            <p class="text-muted mb-3" style="font-size: 0.95rem;">Empower minds and shape the future. Your expertise is the key to unlocking someone's potential today. Let's solve some problems and make an impact!</p>
        </div>
        <div style="font-size: 6rem; z-index: 1; line-height: 1; filter: drop-shadow(0px 10px 10px rgba(0,0,0,0.1));">📚</div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="br-metric-card br-metric-interactive">
            <div class="br-metric-label">Total Sessions</div>
            <div class="br-metric-value"><?= $profile['total_sessions'] ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="br-metric-card br-metric-interactive">
            <div class="br-metric-label">Active</div>
            <div class="br-metric-value text-violet"><?= count($activeReqs) ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="br-metric-card br-metric-interactive">
            <div class="br-metric-label">Avg. Rating</div>
            <div class="br-metric-value text-gold"><?= number_format($profile['average_rating'], 1) ?>⭐</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="br-metric-card br-metric-interactive">
            <div class="br-metric-label">This Month</div>
            <div class="br-metric-value text-gold">$<?= number_format($thisMonth['earnings'], 0) ?></div>
          </div>
        </div>
      </div>

      <!-- Wallet Quick View -->
      <div class="br-wallet-card mb-4">
        <div class="text-subtle small mb-1" style="text-transform:uppercase;letter-spacing:1px">Available Balance</div>
        <div class="br-wallet-balance">$<?= number_format($wallet['available_balance'], 2) ?></div>
        <div class="text-muted small mt-1">$<?= number_format($wallet['pending_balance'], 2) ?> pending ·
          $<?= number_format($wallet['total_earned'], 2) ?> total earned</div>
        <div class="d-flex gap-2 mt-3">
          <button class="btn br-btn-ghost btn-sm" data-bs-toggle="modal" data-bs-target="#withdrawModal">Withdraw
            Funds</button>
          <button class="btn br-btn-outline btn-sm" onclick="showSection('exp-wallet',null)">View Statement</button>
        </div>
      </div>

      <!-- New Requests -->
      <?php if ($newReqs): ?>
        <div class="br-table mb-4">
          <div class="d-flex justify-content-between align-items-center p-3"
            style="border-bottom:1px solid var(--br-border)">
            <h6 class="mb-0 fw-semibold">📥 Open Problem Grid</h6>
            <span class="br-badge br-badge-gold"><?= count($newReqs) ?> open</span>
          </div>
          <div class="table-responsive">
            <table class="table br-table mb-0">
              <thead>
                <tr>
                  <th>Problem</th>
                  <th>Client</th>
                  <th>Urgency</th>
                  <th>Rate</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($newReqs as $req): ?>
                  <tr>
                    <td>
                      <div class="fw-medium small">
                        <?= htmlspecialchars(substr($req['title'], 0, 55)) ?> <?= strlen($req['title']) > 55 ? '…' : '' ?>
                      </div>
                      <?php if (!empty($req['is_global'])): ?>
                        <div class="text-subtle" style="font-size:.72rem">Global request</div>
                      <?php elseif (!empty($req['preferred_expert_name'])): ?>
                        <div class="text-subtle" style="font-size:.72rem">Preferred:
                          <?= htmlspecialchars($req['preferred_expert_name']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($req['client_name']) ?></td>
                    <td><span
                        class="br-badge <?= $urgencyColors[$req['urgency']] ?>"><?= $urgencyLabels[$req['urgency']] ?></span>
                    </td>
                    <td class="mono text-gold small">$<?= number_format($req['agreed_rate'], 0) ?></td>
                    <td>
                      <?php if (!empty($req['is_assigned_to_me'])): ?>
                        <div class="d-flex gap-1">
                          <button class="btn br-btn-gold btn-sm" data-action="accept"
                            data-request-id="<?= $req['id'] ?>">Accept</button>
                          <button class="btn br-btn-ghost btn-sm" data-action="decline"
                            data-request-id="<?= $req['id'] ?>">Decline</button>
                        </div>
                      <?php else: ?>
                        <?php if ($req['is_global'] == 1): ?>
                          <button class="btn br-btn-gold btn-sm" data-action="accept_global" data-request-id="<?= $req['id'] ?>">Accept Global</button>
                        <?php else: ?>
                          <button class="btn br-btn-gold btn-sm" type="button" onclick="jumpToSolve(<?= (int) $req['id'] ?>)">Solve Now</button>
                        <?php endif; ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <!-- ===== REQUESTS SECTION ===== -->
    <div id="section-exp-requests" style="display:none">
      <div class="mb-4">
        <h1 class="br-section-title fs-3 mb-1">Incoming Requests 📥</h1>
        <p class="text-muted small">New problems assigned to you or available globally.</p>
      </div>

      <?php if ($newReqs): ?>
        <div class="br-table">
          <div class="table-responsive">
            <table class="table br-table mb-0">
              <thead>
                <tr>
                  <th>Problem</th>
                  <th>Client</th>
                  <th>Urgency</th>
                  <th>Rate</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($newReqs as $req): ?>
                  <tr>
                    <td>
                      <div class="fw-medium small"><?= htmlspecialchars(substr($req['title'], 0, 60)) ?>...</div>
                      <div class="text-subtle" style="font-size:.72rem">
                        <?= $req['is_global'] ? 'Global Pool' : 'Direct Request' ?>
                      </div>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($req['client_name']) ?></td>
                    <td><span class="br-badge <?= $urgencyColors[$req['urgency']] ?>"><?= $urgencyLabels[$req['urgency']] ?></span></td>
                    <td class="text-gold small mono">$<?= number_format($req['agreed_rate'], 0) ?></td>
                    <td>
                      <?php if ($req['status'] === 'submitted'): ?>
                        <?php if ($req['is_global']): ?>
                          <button class="btn br-btn-gold btn-sm" data-action="accept_global" data-request-id="<?= $req['id'] ?>">Accept Global</button>
                        <?php else: ?>
                          <div class="d-flex gap-1">
                            <button class="btn br-btn-gold btn-sm" data-action="accept" data-request-id="<?= $req['id'] ?>">Accept</button>
                            <button class="btn br-btn-ghost btn-sm" data-action="decline" data-request-id="<?= $req['id'] ?>">Decline</button>
                          </div>
                        <?php endif; ?>
                      <?php else: ?>
                        <button class="btn br-btn-gold btn-sm" type="button" onclick="jumpToSolve(<?= (int) $req['id'] ?>)">Solve Now</button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php else: ?>
        <div class="text-center py-5 br-profile-section">
          <div class="fs-1 mb-2">📥</div>
          <h5 class="fw-semibold">No pending requests</h5>
          <p class="text-muted small">When a client sends a problem, it will appear here.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- ===== SUBMIT RESPONSE ===== -->
    <div id="section-exp-respond" style="display:none">
      <div class="mb-4">
        <h1 class="br-section-title fs-3 mb-1">Submit Response 🎙️</h1>
        <p class="text-muted small">Select an active request and submit your thinking.</p>
      </div>

      <?php if ($activeReqs): ?>
        <div class="mb-4">
          <label class="br-form-label">Select Request</label>
          <select class="br-form-control form-control" id="respond-select" onchange="loadRequestDetail(this.value)">
            <option value="">— Choose a request —</option>
            <?php foreach ($activeReqs as $r): ?>
              <option value="<?= $r['id'] ?>"><?= htmlspecialchars(substr($r['title'], 0, 70)) ?> ·
                <?= htmlspecialchars($r['client_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <div id="request-detail-area"></div>

      <form id="response-form" enctype="multipart/form-data">
        <input type="hidden" name="action" value="submit_response">
        <input type="hidden" name="request_id" id="respond-req-id">

        <div class="row g-4">
          <div class="col-12 col-lg-8">
            <div class="br-profile-section mb-3">
              <h6 class="fw-semibold mb-3">🎙️ Voice Response <span class="text-subtle">(highly recommended)</span></h6>
              <div class="br-recorder">
                <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                  <div class="rec-dot" id="exp-rec-dot"></div>
                  <div class="rec-timer" id="exp-rec-timer">00:00</div>
                  <div class="text-subtle small" id="exp-rec-status">Record your thinking</div>
                </div>
                <div class="br-waveform" id="exp-rec-wave"></div>
                <div class="d-flex align-items-center justify-content-center gap-3 mt-3">
                  <button type="button" class="rec-btn-sec" id="exp-rec-trash" disabled>🗑️</button>
                  <button type="button" class="rec-btn-main" id="exp-rec-main">🎙️</button>
                  <button type="button" class="rec-btn-sec" id="exp-rec-play" disabled>▶️</button>
                </div>
                <div id="exp-rec-preview" style="display:none;margin-top:10px"><audio id="exp-rec-audio" controls
                    class="w-100"></audio></div>
              </div>
            </div>
            <div class="br-profile-section mb-3">
              <div class="mb-3">
                <label class="br-form-label">📝 Written Analysis *</label>
                <textarea name="written_response" class="br-form-control form-control" rows="6"
                  placeholder="Your structured analysis and reasoning…"></textarea>
              </div>
              <div class="mb-3">
                <label class="br-form-label">💡 Key Insights <span class="text-subtle">(one per line)</span></label>
                <textarea name="key_insights" class="br-form-control form-control" rows="4"
                  placeholder="1. At your current scale, pgvector is underrated&#10;2. Your Postgres expertise is a genuine moat here&#10;3. Pinecone's real cost isn't what you think"></textarea>
              </div>
              <div class="mb-3">
                <label class="br-form-label">☐ Action Items for Client <span class="text-subtle">(one per
                    line)</span></label>
                <textarea name="action_items" class="br-form-control form-control" rows="4"
                  placeholder="1. Run pgvector HNSW benchmark with your actual data&#10;2. Get Pinecone enterprise pricing before deciding&#10;3. Read the 2024 ANN benchmarks comparison"></textarea>
              </div>
              <div class="mb-3">
                <label class="br-form-label">🔗 Resource Links <span class="text-subtle">(one per line)</span></label>
                <textarea name="resource_links" class="br-form-control form-control" rows="3"
                  placeholder="https://pgvector.github.io&#10;https://ann-benchmarks.com"></textarea>
              </div>
              <div>
                <label class="br-form-label">⏱️ Time Spent (minutes)</label>
                <input type="number" name="thinking_minutes" class="br-form-control form-control" placeholder="e.g. 25"
                  style="max-width:160px">
              </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn br-btn-ghost">Save Draft</button>
              <button type="submit" class="btn br-btn-gold px-4">Submit Response <i
                  class="bi bi-arrow-right ms-1"></i></button>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="br-profile-section mb-3">
              <h6 class="fw-semibold mb-3">📋 Response Checklist</h6>
              <div class="d-flex flex-column gap-2 small">
                <?php foreach (['Voice recording included', 'At least 3 key insights', 'Clear, actionable items', 'Resource links added', 'Addressed client urgency'] as $item): ?>
                  <label class="d-flex align-items-center gap-2"><input type="checkbox"
                      style="accent-color:var(--br-gold)"> <?= $item ?></label>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="br-profile-section">
              <h6 class="fw-semibold mb-2">🎯 Quality Tips</h6>
              <div class="d-flex flex-column gap-2 text-muted small">
                <div>• Voice first — think out loud, then write the structured version</div>
                <div>• Be opinionated: "I would choose X because…" beats a pros/cons list</div>
                <div>• Surface what the client would miss without your expertise</div>
                <div>• Shorter and sharper beats comprehensive and vague</div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- ===== WALLET ===== -->
    <div id="section-exp-wallet" style="display:none">
      <div class="mb-4">
        <h1 class="br-section-title fs-3 mb-1">Wallet & Earnings 💰</h1>
      </div>
      <div class="br-wallet-card mb-4">
        <div class="text-subtle small mb-1" style="text-transform:uppercase;letter-spacing:1px">Available to Withdraw
        </div>
        <div class="br-wallet-balance">$<?= number_format($wallet['available_balance'], 2) ?></div>
        <div class="text-muted small mt-1">$<?= number_format($wallet['pending_balance'], 2) ?> pending · Releases after
          client confirmation</div>
        <div class="d-flex gap-2 mt-3">
          <button class="btn br-btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#withdrawModal">Withdraw
            to Bank</button>
          <button class="btn br-btn-ghost btn-sm" data-bs-toggle="modal" data-bs-target="#walletModal">Add Bank Account
            / UPI</button>
        </div>
      </div>

      <div class="br-profile-section mb-4">
        <h6 class="fw-semibold mb-2">Payout Details</h6>
        <div class="small text-muted mb-2">Add your bank account or UPI ID to receive payouts.</div>
        <div class="row g-2 small">
          <div class="col-12 col-md-6">
            <div class="text-subtle">Account Name</div>
            <div><?= htmlspecialchars($bankName ?: '—') ?></div>
          </div>
          <div class="col-12 col-md-6">
            <div class="text-subtle">Account Number</div>
            <div><?= htmlspecialchars($maskedAccount) ?></div>
          </div>
          <div class="col-12 col-md-6">
            <div class="text-subtle">IFSC</div>
            <div><?= htmlspecialchars($bankIfsc ?: '—') ?></div>
          </div>
          <div class="col-12 col-md-6">
            <div class="text-subtle">UPI ID</div>
            <div><?= htmlspecialchars($upiId ?: '—') ?></div>
          </div>
        </div>
        <div class="mt-3">
          <button class="btn br-btn-outline btn-sm" data-bs-toggle="modal" data-bs-target="#walletModal">Edit Payout
            Details</button>
        </div>
      </div>
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="br-metric-card br-metric-interactive">
            <div class="br-metric-label">This Month</div>
            <div class="br-metric-value text-gold">$<?= number_format($thisMonth['earnings'], 0) ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="br-metric-card br-metric-interactive">
            <div class="br-metric-label">Total Earned</div>
            <div class="br-metric-value">$<?= number_format($wallet['total_earned'], 0) ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="br-metric-card br-metric-interactive">
            <div class="br-metric-label">Total Withdrawn</div>
            <div class="br-metric-value">$<?= number_format($wallet['total_withdrawn'], 0) ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="br-metric-card br-metric-interactive">
            <div class="br-metric-label">Sessions (Month)</div>
            <div class="br-metric-value"><?= $thisMonth['cnt'] ?></div>
          </div>
        </div>
      </div>
      <div class="br-table">
        <div class="p-3 fw-semibold small" style="border-bottom:1px solid var(--br-border)">Recent Transactions</div>
        <div class="table-responsive">
          <table class="table br-table mb-0">
            <thead>
              <tr>
                <th>Request</th>
                <th>Client</th>
                <th>Date</th>
                <th>Status</th>
                <th class="text-end">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentEarnings as $e): ?>
                <tr>
                  <td class="small"><?= htmlspecialchars(substr($e['req_title'], 0, 50)) ?></td>
                  <td class="text-muted small"><?= htmlspecialchars($e['client_name']) ?></td>
                  <td class="text-muted small"><?= date('M j', strtotime($e['created_at'])) ?></td>
                  <td><span
                      class="br-status <?= $e['status'] === 'released' ? 'br-status-completed' : 'br-status-thinking' ?>"><?= ucfirst($e['status']) ?></span>
                  </td>
                  <td class="text-end mono <?= $e['status'] === 'released' ? 'text-success' : 'text-muted' ?>">
                    +$<?= number_format($e['expert_payout'], 2) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$recentEarnings): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">No transactions yet.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    
<!-- ===== MY UPLOADS ===== -->
    <div id="section-exp-uploads" style="display:none">
      <div class="mb-4">
        <h1 class="br-section-title fs-3 mb-1">My Uploads & Solutions 📂</h1>
        <p class="text-muted small">Your submitted solutions, voice notes, and uploaded attachments.</p>
      </div>

      <div class="br-table mb-4">
        <div class="d-flex justify-content-between align-items-center p-3" style="border-bottom:1px solid var(--br-border)">
          <h6 class="fw-semibold mb-0">Submitted Solutions</h6>
        </div>
        <div class="table-responsive">
          <table class="table br-table mb-0">
            <thead>
              <tr>
                <th>Problem / Request</th>
                <th>Client</th>
                <th>Voice Note</th>
                <th>Date Submitted</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($mySolutions as $sol): ?>
                <tr>
                  <td>
                    <div class="fw-medium small"><?= htmlspecialchars(substr($sol['req_title'], 0, 50)) ?>...</div>
                    <div class="text-subtle" style="font-size:.72rem">Written Response: <?= $sol['written_response'] ? 'Yes' : 'No' ?></div>
                  </td>
                  <td class="text-muted small"><?= htmlspecialchars($sol['client_name']) ?></td>
                  <td>
                    <?php if ($sol['voice_response_path']): ?>
                      <audio controls src="<?= htmlspecialchars($sol['voice_response_path']) ?>" style="height:32px; width:150px;"></audio>
                    <?php else: ?>
                      <span class="text-muted small">None</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted small"><?= date('M j, Y', strtotime($sol['created_at'])) ?></td>
                  <td>
                    <button class="btn br-btn-gold btn-sm" onclick="jumpToSolve(<?= $sol['request_id'] ?>)">Edit Solution</button>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$mySolutions): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">No solutions submitted yet.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>


    </div>
    
<!-- ===== SETTINGS ===== -->
    <div id="section-exp-settings" style="display:none">
      <div class="mb-4">
        <h1 class="br-section-title fs-3 mb-1">Profile Settings ⚙️</h1>
      </div>
      <div class="row g-4">
        <div class="col-12 col-lg-8">
          <div class="br-profile-section">
            <form id="profile-settings-form">
              <input type="hidden" name="action" value="update_profile">
              <input type="hidden" name="request_id" value="1"> <!-- Dummy for backend validation -->
              <div class="mb-3"><label class="br-form-label">Headline</label><input type="text" name="headline"
                  class="br-form-control form-control" value="<?= htmlspecialchars($profile['headline'] ?? '') ?>">
              </div>
              <div class="mb-3"><label class="br-form-label">Bio</label><textarea name="bio"
                  class="br-form-control form-control"
                  rows="5"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea></div>
              <div class="mb-3"><label class="br-form-label">Expertise Areas <span
                    class="text-subtle">(comma-separated)</span></label>
                <input type="text" name="expertise_areas" class="br-form-control form-control"
                  value="<?= htmlspecialchars(implode(', ', json_decode($profile['expertise_areas'] ?? '[]', true) ?? [])) ?>">
              </div>
              <div class="row g-3 mb-3">
                <div class="col-6"><label class="br-form-label">Session Rate (USD)</label><input type="number"
                    name="rate_per_session" class="br-form-control form-control"
                    value="<?= $profile['rate_per_session'] ?>"></div>
                <div class="col-6"><label class="br-form-label">Max Response Hours</label><input type="number"
                    name="max_response_hours" class="br-form-control form-control"
                    value="<?= $profile['max_response_hours'] ?>"></div>
                <div class="col-6"><label class="br-form-label">Max Concurrent Requests</label><input type="number"
                    name="max_active_requests" class="br-form-control form-control"
                    value="<?= $profile['max_active_requests'] ?>"></div>
                <div class="col-6"><label class="br-form-label">Availability</label>
                  <select name="is_available" class="br-form-control form-control form-select">
                    <option value="1" <?= $profile['is_available'] ? 'selected' : '' ?>>🟢 Available</option>
                    <option value="0" <?= !$profile['is_available'] ? 'selected' : '' ?>>🔴 Paused</option>
                  </select>
                </div>
              </div>
              <button type="submit" class="btn br-btn-gold" id="profile-save-btn">Save
                Changes</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== NOTIFICATIONS SECTION ===== -->
    <div id="section-exp-notifications" style="display:none">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="br-section-title fs-3 mb-1">Notifications 🔔</h1>
          <p class="text-muted small">Your latest updates, request alerts, and payment info.</p>
        </div>
        <button class="btn br-btn-ghost btn-sm" id="exp-mark-all-read">
          <i class="bi bi-check2-all me-1"></i>Mark All Read
        </button>
      </div>
      <div id="exp-notif-list">
        <div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-warning"></div> Loading...</div>
      </div>
    </div>

    <!-- ===== CHAT SECTION ===== -->
    <div id="section-exp-chat" style="display:none">
      <div class="mb-4">
        <h1 class="br-section-title fs-3 mb-1">Chat 💬</h1>
        <p class="text-muted small">Direct messages with clients and admin.</p>
      </div>
      <div class="d-flex gap-3" style="height:calc(100vh - 220px);min-height:400px">
        <!-- Contacts List -->
        <div class="br-profile-section" style="width:280px;flex-shrink:0;overflow-y:auto;padding:0">
          <div class="p-3" style="border-bottom:1px solid var(--br-border)">
            <input type="text" class="br-form-control form-control form-control-sm" placeholder="Search contacts..." id="chat-search-input" oninput="filterChatContacts(this.value)">
          </div>
          <div id="chat-contacts-list">
            <div class="text-center text-muted py-4 small"><div class="spinner-border spinner-border-sm text-warning"></div> Loading...</div>
          </div>
        </div>
        <!-- Chat Area -->
        <div class="br-profile-section flex-grow-1 d-flex flex-column" style="padding:0;overflow:hidden">
          <div id="chat-header" class="d-flex align-items-center gap-3 p-3" style="border-bottom:1px solid var(--br-border);min-height:60px">
            <div class="text-muted small" id="chat-header-name">Select a contact to start chatting</div>
          </div>
          <div id="chat-messages-area" class="flex-grow-1 p-3" style="overflow-y:auto;display:flex;flex-direction:column;gap:10px">
            <div class="text-center text-muted py-5 small">
              <div style="font-size:2.5rem;margin-bottom:8px">💬</div>
              Choose a contact from the left to view your conversation.
            </div>
          </div>
          <div id="chat-input-area" class="p-3" style="border-top:1px solid var(--br-border);display:none">
            <form id="chat-send-form" class="d-flex gap-2" onsubmit="return sendChatMessage(event)">
              <input type="text" class="br-form-control form-control" id="chat-message-input" placeholder="Type a message..." autocomplete="off">
              <button type="submit" class="btn br-btn-gold btn-sm px-3" style="white-space:nowrap"><i class="bi bi-send"></i> Send</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Special Footer for Dashboard (Minimal) -->
    <div class="br-dash-footer text-center py-3 border-top small text-muted mt-auto">
      © <?= date('Y') ?> BrainRent · 🔒 Secured Dashboard Mode
    </div>

  </div>
</div>


<!-- ===== WALLET MODAL ===== -->
<div class="modal fade" id="walletModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="background:var(--br-card);border:1px solid var(--br-border2);border-radius:18px">
      <div class="modal-header" style="border-color:var(--br-border)">
        <h5 class="modal-title fw-semibold" style="font-family:'Playfair Display',serif">Bank Account / UPI</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="wallet-form">
        <div class="modal-body">
          <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="br-form-label">Account Holder Name</label>
              <input type="text" name="bank_account_name" class="br-form-control form-control"
                value="<?= htmlspecialchars($bankName) ?>" placeholder="e.g. Rahul Sharma">
            </div>
            <div class="col-12 col-md-6">
              <label class="br-form-label">Account Number</label>
              <input type="text" name="bank_account_number" class="br-form-control form-control"
                value="<?= htmlspecialchars($bankNumber) ?>" placeholder="e.g. 1234567890">
            </div>
            <div class="col-12 col-md-6">
              <label class="br-form-label">IFSC</label>
              <input type="text" name="bank_ifsc" class="br-form-control form-control"
                value="<?= htmlspecialchars($bankIfsc) ?>" placeholder="e.g. HDFC0001234">
            </div>
            <div class="col-12 col-md-6">
              <label class="br-form-label">UPI ID</label>
              <input type="text" name="upi_id" class="br-form-control form-control"
                value="<?= htmlspecialchars($upiId) ?>" placeholder="e.g. name@upi">
            </div>
          </div>
          <div class="text-subtle small mt-3">Provide either complete bank details or a UPI ID. You can update this
            anytime.</div>
        </div>
        <div class="modal-footer" style="border-color:var(--br-border)">
          <button type="button" class="btn br-btn-ghost" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn br-btn-gold" id="wallet-save-btn">Save Details</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== WITHDRAW MODAL ===== -->
<div class="modal fade" id="withdrawModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--br-card);border:1px solid var(--br-border2);border-radius:18px">
      <div class="modal-header" style="border-color:var(--br-border)">
        <h5 class="modal-title fw-semibold" style="font-family:'Playfair Display',serif">💸 Withdraw to Bank</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if (!$bankName && !$upiId): ?>
          <div class="br-alert br-alert-warning mb-3">
            <i class="bi bi-exclamation-triangle me-2"></i>
            No bank account or UPI ID on file. Please add your payout details first.
          </div>
          <button class="btn br-btn-gold w-100" data-bs-toggle="modal" data-bs-target="#walletModal" data-bs-dismiss="modal">
            Add Bank / UPI Details
          </button>
        <?php else: ?>
          <div class="br-card p-3 mb-3" style="background:var(--br-dark3)">
            <div class="text-subtle small mb-2" style="text-transform:uppercase;letter-spacing:1px">Payout To</div>
            <?php if ($bankName): ?>
              <div class="fw-medium small"><?= htmlspecialchars($bankName) ?></div>
              <div class="text-muted small"><?= htmlspecialchars($maskedAccount) ?> · IFSC: <?= htmlspecialchars($bankIfsc ?: '—') ?></div>
            <?php endif; ?>
            <?php if ($upiId): ?>
              <div class="text-muted small mt-1">UPI: <?= htmlspecialchars($upiId) ?></div>
            <?php endif; ?>
          </div>
          <div class="mb-3">
            <label class="br-form-label">Amount to Withdraw</label>
            <div class="d-flex gap-2 align-items-center">
              <span class="text-gold fw-bold fs-5">$</span>
              <input type="number" id="withdraw-amount" class="br-form-control form-control" min="1" step="0.01"
                max="<?= number_format($wallet['available_balance'] ?? 0, 2, '.', '') ?>"
                placeholder="<?= number_format($wallet['available_balance'] ?? 0, 2) ?>" style="max-width:200px">
              <button class="btn br-btn-ghost btn-sm" onclick="document.getElementById('withdraw-amount').value='<?= number_format($wallet['available_balance'] ?? 0, 2, '.', '') ?>'">
                Max
              </button>
            </div>
            <div class="text-subtle small mt-1">Available: $<?= number_format($wallet['available_balance'] ?? 0, 2) ?></div>
          </div>
          <div class="br-alert br-alert-info small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Withdrawals are processed within 2–3 business days. This is a simulated transfer for demo purposes.
          </div>
          <button class="btn br-btn-gold w-100" id="withdraw-submit-btn">
            <i class="bi bi-send me-2"></i>Request Withdrawal
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
  const APP_URL = '<?= APP_URL ?>';
  const CURRENT_USER_ID = <?= (int)$userId ?>;

  document.addEventListener('DOMContentLoaded', function() {
    window._expertRecorder = new VoiceRecorder({
      dot    : 'exp-rec-dot',
      timer  : 'exp-rec-timer',
      status : 'exp-rec-status',
      wave   : 'exp-rec-wave',
      main   : 'exp-rec-main',
      trash  : 'exp-rec-trash',
      play   : 'exp-rec-play',
      audio  : 'exp-rec-audio',
      preview: 'exp-rec-preview'
    });
  });

  function showSection(id, el) {
    document.querySelectorAll('[id^="section-exp-"]').forEach(s => s.style.display = 'none');
    const t = document.getElementById('section-' + id);
    if (t) t.style.display = 'block';
    // Only remove active from sidebar nav items, not top navbar
    document.querySelectorAll('.br-sidebar .br-nav-item').forEach(a => a.classList.remove('active'));
    if (el) el.classList.add('active');
    // Reset scroll position so the new section is visible at top
    const main = document.querySelector('.br-dash-main');
    if (main) main.scrollTop = 0;
    // Lazy-load notifications when that section is opened
    if (id === 'exp-notifications') loadExpNotifications();
    if (id === 'exp-chat') loadChatContacts();
  }

  document.getElementById('response-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const reqId = document.getElementById('respond-req-id').value;
    if (!reqId) {
      BrainRent.toast('Please select a request first', 'error');
      return;
    }
    const fd = new FormData(this);
    window._expertRecorder?.appendTo(fd, 'voice_response');
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    const res = await fetch(APP_URL + '/api/manage_request.php', {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    BrainRent.toast(data.success ? 'Response submitted! Client notified.' : (data.error || 'Error'), data.success ? 'success' : 'error');
    if (data.success) {
      setTimeout(() => location.reload(), 1500);
    } else {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Submit Response';
    }
  });

  document.getElementById('profile-settings-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('profile-save-btn');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    const fd = new FormData(this);
    const res = await fetch(APP_URL + '/api/manage_request.php', {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    BrainRent.toast(data.success ? 'Profile updated!' : (data.error || 'Update failed'), data.success ? 'success' : 'error');
    btn.disabled = false;
    btn.textContent = 'Save Changes';
    if (data.success) setTimeout(() => location.reload(), 600);
  });

  async function loadRequestDetail(id) {
    document.getElementById('respond-req-id').value = id;
    const area = document.getElementById('request-detail-area');
    if (!id || !area) {
        document.querySelector('textarea[name="written_response"]').value = '';
        return;
    }
    area.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-warning"></div> Loading request...</div>';
    try {
      const res = await fetch(APP_URL + '/api/get_request.php?request_id=' + id);
      const data = await res.json();
      if (!data.success || !data.request) {
        area.innerHTML = '<div class="br-alert br-alert-warning small">Could not load request details.</div>';
        return;
      }
      
      if (data.response) {
          document.querySelector('textarea[name="written_response"]').value = data.response.written_response || '';
          document.querySelector('textarea[name="key_insights"]').value = (data.response.key_insights ? JSON.parse(data.response.key_insights).join("\n") : '');
          document.querySelector('textarea[name="action_items"]').value = (data.response.action_items ? JSON.parse(data.response.action_items).join("\n") : '');
          document.querySelector('textarea[name="resource_links"]').value = (data.response.resources_links ? JSON.parse(data.response.resources_links).join("\n") : '');
          document.querySelector('input[name="thinking_minutes"]').value = data.response.actual_thinking_minutes || '';
      } else {
          document.querySelector('textarea[name="written_response"]').value = '';
          document.querySelector('textarea[name="key_insights"]').value = '';
          document.querySelector('textarea[name="action_items"]').value = '';
          document.querySelector('textarea[name="resource_links"]').value = '';
          document.querySelector('input[name="thinking_minutes"]').value = '';
      }

      const r = data.request;
      const urgMap = {normal:'br-badge-gray', urgent:'br-badge-gold', critical:'br-badge-danger'};
      const urgLabel = {normal:'Normal', urgent:'⚡ Urgent', critical:'🔥 Critical'};
      area.innerHTML = `
        <div class="br-profile-section mb-3">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="fw-semibold mb-0">${escHtml(r.title)}</h6>
            <span class="br-badge ${urgMap[r.urgency]||'br-badge-gray'}">${urgLabel[r.urgency]||r.urgency}</span>
          </div>
          ${r.category_name ? '<div class="text-subtle small mb-2">📂 ' + escHtml(r.category_name) + '</div>' : ''}
          <div class="text-muted small mb-2" style="white-space:pre-wrap;line-height:1.6">${escHtml(r.problem_text || 'No written description.')}</div>
          ${r.problem_voice_url ? '<div class="mt-2"><label class="br-form-label small">🎙️ Client Voice Note</label><audio controls class="w-100" src="'+r.problem_voice_url+'"></audio></div>' : ''}
          ${data.attachments && data.attachments.length > 0 ? '<div class="mt-3"><label class="br-form-label small">📎 Attached Files</label> <div class="d-flex flex-column gap-2">' + data.attachments.map(a => '<a href="'+a.url+'" target="_blank" class="text-violet small" style="text-decoration:none"><i class="bi bi-file-earmark me-2"></i>' + escHtml(a.file_name) + '</a>').join('') + '</div></div>' : ''}
          <div class="d-flex gap-3 mt-2 text-subtle small">
            <span>💰 Rate: $${parseFloat(r.agreed_rate||0).toFixed(0)}</span>
            <span>📅 ${new Date(r.created_at).toLocaleDateString()}</span>
            <span>📊 Status: ${r.status}</span>
          </div>
        </div>`;
    } catch(e) {
      area.innerHTML = '<div class="br-alert br-alert-warning small">Network error loading request.</div>';
    }
  }

  function escHtml(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  function jumpToSolve(requestId) {
    showSection('exp-respond', document.getElementById('nav-exp-respond'));
    const select = document.getElementById('respond-select');
    if (!select) return;
    select.value = String(requestId);
    loadRequestDetail(requestId);
  }

  // Add click handlers for accept/decline
  document.addEventListener('click', async function(e) {
    const btn = e.target.closest('[data-action="accept"], [data-action="decline"]');
    if (btn) {
      const action = btn.dataset.action;
      const rid = btn.dataset.requestId;
      btn.disabled = true;
      
      let body = `action=${action}&request_id=${rid}`;
      if (action === 'decline') {
        const reason = prompt('Reason for declining?');
        if (reason === null) { btn.disabled = false; return; }
        body += `&reason=${encodeURIComponent(reason)}`;
      }

      const res = await fetch(APP_URL + '/api/manage_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
      });
      const data = await res.json();
      BrainRent.toast(data.message || data.error, data.success ? 'success' : 'error');
      if (data.success) setTimeout(() => location.reload(), 600);
      else btn.disabled = false;
    }
  });

  // Add click handlers for global accept
  document.addEventListener('click', async function(e) {
    const btn = e.target.closest('[data-action="accept_global"]');
    if (btn) {
      const rid = btn.dataset.requestId;
      btn.disabled = true;
      const res = await fetch(APP_URL + '/api/manage_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=accept_global&request_id=${rid}`
      });
      const data = await res.json();
      if (data.success) {
        BrainRent.toast('Global request accepted! Redirecting to solve...', 'success');
        setTimeout(() => jumpToSolve(rid), 1000);
      } else {
        BrainRent.toast(data.error || 'Failed to accept', 'error');
        btn.disabled = false;
      }
    }
  });

  document.querySelectorAll('[id^="section-exp-"]').forEach(s => {
    if (s.id !== 'section-exp-overview') s.style.display = 'none';
  });

  document.getElementById('wallet-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('wallet-save-btn');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    const fd = new FormData(this);
    const res = await fetch(APP_URL + '/api/update_wallet.php', {
      method: 'POST',
      body: fd
    });
    const data = await res.json();

    if (data.success) {
      BrainRent.toast('Payout details saved', 'success');
      const modalEl = document.getElementById('walletModal');
      const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
      modal.hide();
      setTimeout(() => location.reload(), 600);
    } else {
      BrainRent.toast(data.error || 'Unable to save details', 'error');
      btn.disabled = false;
      btn.textContent = 'Save Details';
    }
  });

  // ── Expert Notifications ───────────────────────────────────────────────
  const expNotifIcons = {
    new_request       : '📥',
    new_global_request: '🌐',
    expert_approved   : '✅',
    expert_rejected   : '❌',
    payment_released  : '💰',
    request_accepted  : '✅',
    default           : '🔔',
  };

  async function loadExpNotifications() {
    const el = document.getElementById('exp-notif-list');
    if (!el) return;
    try {
      const res  = await fetch(APP_URL + '/api/notifications.php?limit=50');
      const data = await res.json();
      if (!data.success) return;

      // Update sidebar badge
      const badge = document.getElementById('exp-notif-sidebar-badge');
      if (badge) {
        badge.textContent = data.unread_count;
        badge.style.display = data.unread_count > 0 ? 'inline-flex' : 'none';
      }

      const list = data.notifications || [];
      if (!list.length) {
        el.innerHTML = '';
        return;
      }
      el.innerHTML = list.map(n => {
        const icon   = expNotifIcons[n.type] || expNotifIcons.default;
        const unread = n.is_read == 0;
        const isChat = n.type === 'new_message';
        const tag    = 'a';
        let href = `href="javascript:void(0)" onclick="openNotifModal('${escHtml(n.title).replace(/'/g, "\\'")}', '${escHtml(n.message).replace(/'/g, "\\'")}', '${n.link}', '${n.type}')"`;
        const ago    = (dateStr => {
          const d = Math.floor((Date.now() - new Date(dateStr)) / 1000);
          if (d < 60) return 'just now';
          if (d < 3600) return Math.floor(d/60) + ' min ago';
          if (d < 86400) return Math.floor(d/3600) + ' hr ago';
          return Math.floor(d/86400) + ' days ago';
        })(n.created_at);
        return `<${tag} ${href} class="br-notif-item${unread?' unread':''}" style="display:flex;gap:12px;padding:14px 16px;border-bottom:1px solid var(--br-border);text-decoration:none;color:inherit">
          <div style="font-size:1.3rem;flex-shrink:0;margin-top:2px">${icon}</div>
          <div style="flex:1;min-width:0">
            <div class="fw-semibold small${unread?' text-white':' text-muted'}" style="margin-bottom:2px">${n.title}</div>
            <div class="text-muted" style="font-size:.8rem;line-height:1.4">${n.message}</div>
            <div class="text-subtle" style="font-size:.72rem;margin-top:4px">${ago}</div>
          </div>
          ${unread ? '<div style="width:8px;height:8px;border-radius:50%;background:var(--br-gold);flex-shrink:0;margin-top:6px"></div>' : ''}
        </${tag}>`;
      }).join('');
    } catch(_) {
      el.innerHTML = '<div class="br-card p-4 text-center text-muted small">Unable to load notifications.</div>';
    }
  }

  document.getElementById('exp-mark-all-read')?.addEventListener('click', async () => {
    await fetch(APP_URL + '/api/notifications.php?action=mark_all_read');
    BrainRent.loadNotifications();
    loadExpNotifications();
    BrainRent.toast('All notifications marked as read', 'success');
  });

  // Load sidebar badge count on page init
  (async () => {
    try {
      const r = await fetch(APP_URL + '/api/notifications.php?limit=1');
      const d = await r.json();
      const badge = document.getElementById('exp-notif-sidebar-badge');
      if (badge && d.unread_count > 0) {
        badge.textContent = d.unread_count;
        badge.style.display = 'inline-flex';
      }
    } catch(_) {}
  })();

  // ── Withdrawal modal ──────────────────────────────────────────────────
  document.getElementById('withdraw-submit-btn')?.addEventListener('click', async function() {
    const amount = parseFloat(document.getElementById('withdraw-amount')?.value || '0');
    const maxAmt = <?= number_format($wallet['available_balance'] ?? 0, 2, '.', '') ?>;
    if (!amount || amount <= 0) { BrainRent.toast('Enter a valid amount', 'error'); return; }
    if (amount > maxAmt)        { BrainRent.toast('Amount exceeds available balance', 'error'); return; }

    this.disabled = true;
    this.textContent = 'Processing…';
    // Simulate a withdrawal request (no real gateway)
    await new Promise(r => setTimeout(r, 900));
    BrainRent.toast('✅ Withdrawal of $' + amount.toFixed(2) + ' requested! Will arrive in 2–3 business days.', 'success');
    const modal = bootstrap.Modal.getInstance(document.getElementById('withdrawModal'));
    if (modal) modal.hide();
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-send me-2"></i>Request Withdrawal';
  });

  // =============================================
  // CHAT SYSTEM
  // =============================================
  let _chatOtherUserId = 0;
  let _chatPollTimer = null;
  let _chatContactsCache = [];

  async function loadChatContacts() {
    const el = document.getElementById('chat-contacts-list');
    if (!el) return;
    try {
      const res = await fetch(APP_URL + '/api/chat.php?action=contacts');
      const data = await res.json();
      if (!data.success) { el.innerHTML = '<div class="text-center text-muted py-4 small">No contacts yet.</div>'; return; }
      _chatContactsCache = data.contacts || [];
      renderChatContacts(_chatContactsCache);
      // Update badge
      const totalUnread = _chatContactsCache.reduce((sum,c) => sum + (c.unread_count||0), 0);
      const badge = document.getElementById('exp-chat-sidebar-badge');
      if (badge) { badge.textContent = totalUnread; badge.style.display = totalUnread > 0 ? 'inline-flex' : 'none'; }
    } catch(_) { el.innerHTML = '<div class="text-center text-muted py-4 small">Unable to load contacts.</div>'; }
  }

  function renderChatContacts(contacts) {
    const el = document.getElementById('chat-contacts-list');
    if (!contacts.length) { el.innerHTML = '<div class="text-center text-muted py-4 small"><div style="font-size:2rem;margin-bottom:8px">💬</div>No conversations yet.</div>'; return; }
    el.innerHTML = contacts.map(c => {
      const active = c.user_id == _chatOtherUserId ? 'background:var(--br-dark3);' : '';
      const unread = (c.unread_count||0) > 0;
      const ago = c.last_message_at ? timeAgo(c.last_message_at) : '';
      return `<div class="chat-contact-item" onclick="openChat(${c.user_id}, '${escHtml(c.full_name)}')" ` +
        ` style="display:flex;gap:10px;padding:12px 16px;cursor:pointer;border-bottom:1px solid var(--br-border);transition:background .15s;${active}" ` +
        ` onmouseover="this.style.background='var(--br-dark3)'" onmouseout="this.style.background='${active?'var(--br-dark3)':'transparent'}'"> ` +
        `<div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--br-gold),#d97706);display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:600;flex-shrink:0;color:#000">${escHtml(c.full_name).charAt(0).toUpperCase()}</div> ` +
        `<div style="flex:1;min-width:0"> ` +
          `<div class="d-flex justify-content-between align-items-center"> ` +
            `<div class="fw-semibold small${unread?' text-white':''}" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escHtml(c.full_name)}</div> ` +
            `<div class="text-subtle" style="font-size:.68rem;flex-shrink:0">${ago}</div> ` +
          `</div> ` +
          `<div class="d-flex justify-content-between align-items-center mt-1"> ` +
            `<div class="text-muted" style="font-size:.75rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px">${escHtml(c.last_message||'')}</div> ` +
            (unread ? `<span style="background:var(--br-gold);color:#000;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;flex-shrink:0">${c.unread_count}</span>` : '') +
          `</div> ` +
        `</div> ` +
      `</div>`;
    }).join('');
  }

  function filterChatContacts(query) {
    const q = query.toLowerCase();
    const filtered = _chatContactsCache.filter(c => (c.full_name||'').toLowerCase().includes(q));
    renderChatContacts(filtered);
  }

  function timeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60) return "now";
    if (diff < 3600) return Math.floor(diff / 60) + "m";
    if (diff < 86400) return Math.floor(diff / 3600) + "h";
    return Math.floor(diff / 86400) + "d";
  }

  async function openChat(userId, name) {
    _chatOtherUserId = userId;
    document.getElementById('chat-header-name').innerHTML = '<div class="fw-semibold">'+escHtml(name)+'</div><div class="text-subtle small">Online</div>';
    document.getElementById('chat-input-area').style.display = 'block';
    document.getElementById('chat-message-input').focus();
    renderChatContacts(_chatContactsCache); // highlight active
    await fetchChatMessages();
    // Start polling
    clearInterval(_chatPollTimer);
    _chatPollTimer = setInterval(fetchChatMessages, 4000);
  }

  async function fetchChatMessages() {
    if (!_chatOtherUserId) return;
    const area = document.getElementById('chat-messages-area');
    try {
      const res = await fetch(APP_URL + '/api/chat.php?action=fetch&other_user_id=' + _chatOtherUserId);
      const data = await res.json();
      if (!data.success) return;
      const msgs = data.messages || [];
      if (!msgs.length) { area.innerHTML = '<div class="text-center text-muted py-5 small">No messages yet. Start the conversation!</div>'; return; }
      const wasAtBottom = area.scrollHeight - area.scrollTop - area.clientHeight < 60;
      area.innerHTML = msgs.map(m => {
        const isMine = m.sender_id == CURRENT_USER_ID;
        const time = new Date(m.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
        return `<div style="display:flex;justify-content:${isMine?'flex-end':'flex-start'}"> ` +
          `<div style="max-width:75%;padding:10px 14px;border-radius:${isMine?'12px 0px 12px 12px':'0px 12px 12px 12px'};background:${isMine?'#056162':'#262d31'};color:#fff;font-size:.88rem;line-height:1.45;box-shadow:0 1px 2px rgba(0,0,0,0.3)"> ` +
            `<div>${escHtml(m.message_text)}</div> ` +
            `<div style="font-size:.65rem;margin-top:4px;opacity:.7;text-align:${isMine?'right':'left'}">${time}${m.is_read==1&&isMine?' ✓✓':''}</div> ` +
          `</div> ` +
        `</div>`;
      }).join('');
      if (wasAtBottom) area.scrollTop = area.scrollHeight;
    } catch(_) {}
  }

  function sendChatMessage(e) {
    e.preventDefault();
    const input = document.getElementById('chat-message-input');
    const text = input.value.trim();
    if (!text || !_chatOtherUserId) return false;
    input.value = '';
    // Optimistic render
    const area = document.getElementById('chat-messages-area');
    const time = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    area.innerHTML += `<div style="display:flex;justify-content:flex-end"> ` +
      `<div style="max-width:75%;padding:10px 14px;border-radius:12px 0px 12px 12px;background:#056162;color:#fff;font-size:.88rem;line-height:1.45;opacity:.7;box-shadow:0 1px 2px rgba(0,0,0,0.3)"> ` +
        `<div>${escHtml(text)}</div> ` +
        `<div style="font-size:.65rem;margin-top:4px;opacity:.7;text-align:right">${time}</div> ` +
      `</div> ` +
    `</div>`;
    area.scrollTop = area.scrollHeight;
    // Send to server
    fetch(APP_URL + '/api/chat.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: `action=send&receiver_id=${_chatOtherUserId}&message_text=${encodeURIComponent(text)}`
    }).then(r => r.json()).then(d => {
      if (d.success) fetchChatMessages();
      else BrainRent.toast(d.error||'Failed to send', 'error');
    });
    return false;
  }

  // Load chat unread badge on init
  (async () => {
    try {
      const r = await fetch(APP_URL + '/api/chat.php?action=contacts');
      const d = await r.json();
      if (d.success) {
        const total = (d.contacts||[]).reduce((s,c) => s + (c.unread_count||0), 0);
        const badge = document.getElementById('exp-chat-sidebar-badge');
        if (badge && total > 0) { badge.textContent = total; badge.style.display = 'inline-flex'; }
      }
    } catch(_) {}
  })();
</script>
<!-- ===== NOTIFICATION MODAL ===== -->
<div class="modal fade" id="notifModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--br-card);border:1px solid var(--br-border2);border-radius:18px">
      <div class="modal-header" style="border-color:var(--br-border)">
        <h5 class="modal-title fw-semibold" id="notif-modal-title">Notification</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="notif-modal-body" class="text-muted small lh-lg"></div>
        <div id="notif-modal-action" class="mt-4" style="display:none">
          <a href="#" id="notif-modal-link" class="btn br-btn-gold w-100">View Details</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let _notifModalInstance = null;
function openNotifModal(title, msg, link, type) {
  document.getElementById("notif-modal-title").innerText = title;
  document.getElementById("notif-modal-body").innerText = msg;
  const actionDiv = document.getElementById("notif-modal-action");
  const linkBtn = document.getElementById("notif-modal-link");
  
  if (type === 'new_message') {
      actionDiv.style.display = "block";
      linkBtn.innerText = "Open Chat";
      linkBtn.href = "javascript:void(0)";
      linkBtn.onclick = function() {
          if(_notifModalInstance) _notifModalInstance.hide();
          showSection('exp-chat', document.getElementById('nav-exp-chat'));
      };
  } else if (link && link !== "null" && link !== "") {
      actionDiv.style.display = "block";
      linkBtn.innerText = "View Details";
      linkBtn.href = link;
      linkBtn.onclick = null;
  } else {
      actionDiv.style.display = "none";
  }
  
  const el = document.getElementById("notifModal");
  if(!_notifModalInstance) _notifModalInstance = new bootstrap.Modal(el);
  _notifModalInstance.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
