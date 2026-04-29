<?php
$content = file_get_contents('pages/dashboard-expert.php');

$startStr = '<!-- ===== MY UPLOADS ===== -->';
$endStr = '<!-- ===== SETTINGS ===== -->';

$startPos = strpos($content, $startStr);
$endPos = strpos($content, $endStr);

if ($startPos !== false && $endPos !== false) {
    $newUploads = '
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
                    <div class="fw-medium small"><?= htmlspecialchars(substr($sol[\'req_title\'], 0, 50)) ?>...</div>
                    <div class="text-subtle" style="font-size:.72rem">Written Response: <?= $sol[\'written_response\'] ? \'Yes\' : \'No\' ?></div>
                  </td>
                  <td class="text-muted small"><?= htmlspecialchars($sol[\'client_name\']) ?></td>
                  <td>
                    <?php if ($sol[\'voice_response_path\']): ?>
                      <audio controls src="<?= htmlspecialchars($sol[\'voice_response_path\']) ?>" style="height:32px; width:150px;"></audio>
                    <?php else: ?>
                      <span class="text-muted small">None</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted small"><?= date(\'M j, Y\', strtotime($sol[\'created_at\'])) ?></td>
                  <td>
                    <button class="btn br-btn-gold btn-sm" onclick="jumpToSolve(<?= $sol[\'request_id\'] ?>)">Edit Solution</button>
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

      <div class="br-table">
        <div class="d-flex justify-content-between align-items-center p-3" style="border-bottom:1px solid var(--br-border)">
          <h6 class="fw-semibold mb-0">General Uploaded Files</h6>
        </div>
        <div class="table-responsive">
          <table class="table br-table mb-0">
            <thead>
              <tr>
                <th>Title</th>
                <th>Type</th>
                <th>Storage</th>
                <th>Uploaded</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($myUploads as $upload): ?>
                <?php
                $entityType = (string) $upload[\'entity_type\'];
                $entityId = (int) $upload[\'entity_id\'];
                $dbLinked = ((int) ($upload[\'db_linked\'] ?? 0)) === 1;
                $viewUrl = brUploadedMediaViewUrl($entityType, $entityId);
                $downloadUrl = brUploadedMediaDownloadUrl($entityType, $entityId);
                ?>
                <tr>
                  <td>
                    <div class="fw-medium small"><?= htmlspecialchars($upload[\'title\'] ?? \'Untitled\') ?></div>
                    <div class="text-subtle" style="font-size:.72rem">
                      <?= ((int) ($upload[\'is_active\'] ?? 1)) === 1 ? \'Visible\' : \'Disabled\' ?>
                    </div>
                  </td>
                  <td class="text-muted small"><?= htmlspecialchars(brUploadedMediaTypeLabel($entityType)) ?></td>
                  <td>
                    <?php if ($dbLinked): ?>
                      <span class="br-badge br-badge-teal">DB Stored</span>
                    <?php else: ?>
                      <span class="br-badge br-badge-danger">Pending DB Copy</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted small"><?= date(\'M j, Y\', strtotime((string) $upload[\'created_at\'])) ?></td>
                  <td>
                    <div class="d-flex gap-1">
                      <a class="btn br-btn-ghost btn-sm" href="<?= htmlspecialchars($viewUrl) ?>" target="_blank">View</a>
                      <a class="btn br-btn-ghost btn-sm" href="<?= htmlspecialchars($downloadUrl) ?>">Download</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$myUploads): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">No uploads found for this account.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
';

    $content = substr_replace($content, $newUploads, $startPos, $endPos - $startPos);
    file_put_contents('pages/dashboard-expert.php', $content);
    echo "Fixed uploads section!";
} else {
    echo "Not found!";
}
?>
