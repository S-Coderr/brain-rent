<?php
$content = file_get_contents('pages/dashboard-expert.php');

// 1. Add Notif Modal HTML
$notifModalHtml = <<<HTML
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
HTML;

if (strpos($content, 'id="notifModal"') === false) {
    $content = str_replace('</body>', $notifModalHtml . "\n" . '<script>' . "\n" . 'function openNotifModal(title, msg, link, type) {' . "\n" . '  document.getElementById("notif-modal-title").innerText = title;' . "\n" . '  document.getElementById("notif-modal-body").innerText = msg;' . "\n" . '  if(link && link !== "null" && link !== "") {' . "\n" . '    document.getElementById("notif-modal-action").style.display = "block";' . "\n" . '    document.getElementById("notif-modal-link").href = link;' . "\n" . '  } else {' . "\n" . '    document.getElementById("notif-modal-action").style.display = "none";' . "\n" . '  }' . "\n" . '  new bootstrap.Modal(document.getElementById("notifModal")).show();' . "\n" . '}' . "\n" . '</script>' . "\n</body>", $content);
}

// 2. Update loadExpNotifications logic
$oldNotifJS = <<<JS
        const isChat = n.type === 'new_message';
        const tag    = 'a';
        let href = `href="javascript:void(0)"`;
        if (isChat) {
            href = `href="javascript:void(0)" onclick="showSection('exp-chat', document.getElementById('nav-exp-chat'))"`;
        } else if (n.link) {
            href = `href="\${n.link}"`;
        }
JS;

$newNotifJS = <<<JS
        const isChat = n.type === 'new_message';
        const tag    = 'a';
        let href = `href="javascript:void(0)" onclick="openNotifModal('\${escHtml(n.title)}', '\${escHtml(n.message)}', '\${n.link}', '\${n.type}')"`;
        if (isChat) {
            href = `href="javascript:void(0)" onclick="showSection('exp-chat', document.getElementById('nav-exp-chat'))"`;
        }
JS;

$content = str_replace($oldNotifJS, $newNotifJS, $content);

// 3. Add file input to response-form
$oldTimeSpent = <<<HTML
              <div>
                <label class="br-form-label">⏱️ Time Spent (minutes)</label>
                <input type="number" name="thinking_minutes" class="br-form-control form-control" placeholder="e.g. 25"
                  style="max-width:160px">
              </div>
HTML;

$newTimeSpent = <<<HTML
              <div>
                <label class="br-form-label">⏱️ Time Spent (minutes)</label>
                <input type="number" name="thinking_minutes" class="br-form-control form-control" placeholder="e.g. 25"
                  style="max-width:160px">
              </div>
              <div class="mt-3">
                <label class="br-form-label">📎 Attachments (Files/Documents)</label>
                <input type="file" name="response_attachments[]" multiple class="br-form-control form-control" style="background:var(--br-dark3); border:1px solid var(--br-border)">
                <div class="text-subtle small mt-1">These will be stored in the database and visible to the client.</div>
              </div>
HTML;

$content = str_replace($oldTimeSpent, $newTimeSpent, $content);

// 4. Update Chat Bubbles to WhatsApp Style
$oldChatMine = <<<JS
<div style="max-width:70%;padding:10px 14px;border-radius:\${isMine?'16px 16px 4px 16px':'16px 16px 16px 4px'};background:\${isMine?'linear-gradient(135deg,var(--br-gold),#d97706)':'var(--br-dark3)'};color:\${isMine?'#000':'var(--br-text1)'};font-size:.88rem;line-height:1.45"> ` +
JS;

$newChatMine = <<<JS
<div style="max-width:75%;padding:10px 14px;border-radius:\${isMine?'12px 0px 12px 12px':'0px 12px 12px 12px'};background:\${isMine?'#056162':'#262d31'};color:'#fff';font-size:.88rem;line-height:1.45;box-shadow:0 1px 2px rgba(0,0,0,0.3)"> ` +
JS;

$content = str_replace($oldChatMine, $newChatMine, $content);

$oldChatOptMine = <<<JS
<div style="max-width:70%;padding:10px 14px;border-radius:16px 16px 4px 16px;background:linear-gradient(135deg,var(--br-gold),#d97706);color:#000;font-size:.88rem;line-height:1.45;opacity:.7"> ` +
JS;

$newChatOptMine = <<<JS
<div style="max-width:75%;padding:10px 14px;border-radius:12px 0px 12px 12px;background:#056162;color:#fff;font-size:.88rem;line-height:1.45;opacity:.7;box-shadow:0 1px 2px rgba(0,0,0,0.3)"> ` +
JS;

$content = str_replace($oldChatOptMine, $newChatOptMine, $content);

// Update loadRequestDetail to display attachments
$oldReqDetail = <<<JS
          \${r.problem_voice_url ? '<div class="mt-2"><label class="br-form-label small">🎙️ Client Voice Note</label><audio controls class="w-100" src="'+r.problem_voice_url+'"></audio></div>' : ''}
          <div class="d-flex gap-3 mt-2 text-subtle small">
JS;

$newReqDetail = <<<JS
          \${r.problem_voice_url ? '<div class="mt-2"><label class="br-form-label small">🎙️ Client Voice Note</label><audio controls class="w-100" src="'+r.problem_voice_url+'"></audio></div>' : ''}
          \${data.attachments && data.attachments.length > 0 ? '<div class="mt-3"><label class="br-form-label small">📎 Attached Files</label> <div class="d-flex flex-column gap-2">' + data.attachments.map(a => '<a href="'+a.url+'" target="_blank" class="text-violet small" style="text-decoration:none"><i class="bi bi-file-earmark me-2"></i>' + escHtml(a.file_name) + '</a>').join('') + '</div></div>' : ''}
          <div class="d-flex gap-3 mt-2 text-subtle small">
JS;

$content = str_replace($oldReqDetail, $newReqDetail, $content);

file_put_contents('pages/dashboard-expert.php', $content);
echo "Dashboard Expert HTML/JS fixed.\n";
?>
