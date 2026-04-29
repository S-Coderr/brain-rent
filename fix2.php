<?php
$content = file_get_contents('pages/dashboard-expert.php');

$activeReqsOld = <<<PHP
\$activeReqs = \$db->fetchAll(
  "SELECT tr.*, u.full_name AS client_name
  FROM thinking_requests tr
  INNER JOIN users u ON tr.client_id = u.id
  WHERE (tr.status = 'submitted' AND (tr.is_global = 1 OR tr.expert_id = ?))
    OR (tr.expert_id = ? AND tr.status IN ('accepted','thinking'))
  ORDER BY tr.created_at DESC",
  [\$userId, \$userId]
);
PHP;

$activeReqsNew = <<<PHP
\$activeReqs = \$db->fetchAll(
  "SELECT tr.*, u.full_name AS client_name
  FROM thinking_requests tr
  INNER JOIN users u ON tr.client_id = u.id
  WHERE tr.expert_id = ? AND tr.status IN ('accepted','thinking','completed')
  ORDER BY tr.created_at DESC",
  [\$userId]
);
\$mySolutions = \$db->fetchAll(
  "SELECT res.*, tr.title AS req_title, u.full_name AS client_name
   FROM thinking_responses res
   INNER JOIN thinking_requests tr ON res.request_id = tr.id
   INNER JOIN users u ON tr.client_id = u.id
   WHERE res.expert_id = ?
   ORDER BY res.created_at DESC",
  [\$userId]
);
PHP;

$content = str_replace($activeReqsOld, $activeReqsNew, $content);

$uploadsOldStart = '<div id="section-exp-uploads" style="display:none">';
$uploadsOldEndStr = '<!-- ===== PROFILE SETTINGS ===== -->';

$startPos = strpos($content, $uploadsOldStart);
$endPos = strpos($content, $uploadsOldEndStr);

if ($startPos !== false && $endPos !== false) {
    $newUploads = '
<div id="section-exp-uploads" style="display:none">
      <div class="mb-4">
        <h1 class="br-section-title fs-3 mb-1">My Uploads & Solutions 📂</h1>
        <p class="text-muted small">Your submitted solutions and attached voice notes.</p>
      </div>

      <div class="br-table">
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
    </div>
    
    ';
    $content = substr_replace($content, $newUploads, $startPos, $endPos - $startPos);
}

file_put_contents('pages/dashboard-expert.php', $content);
echo "Fix applied!";
?>
