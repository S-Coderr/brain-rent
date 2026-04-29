<?php
$content = file_get_contents('pages/dashboard-expert.php');

$jsOld = <<<JS
  async function loadRequestDetail(id) {
    document.getElementById('respond-req-id').value = id;
    const area = document.getElementById('request-detail-area');
    if (!id || !area) return;
    area.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-warning"></div> Loading request...</div>';
    try {
      const res = await fetch(APP_URL + '/api/get_request.php?request_id=' + id);
      const data = await res.json();
      if (!data.success || !data.request) {
        area.innerHTML = '<div class="br-alert br-alert-warning small">Could not load request details.</div>';
        return;
      }
      const r = data.request;
      const urgMap = {normal:'br-badge-gray', urgent:'br-badge-gold', critical:'br-badge-danger'};
      const urgLabel = {normal:'Normal', urgent:'⚡ Urgent', critical:'🔥 Critical'};
      area.innerHTML = `
        <div class="br-profile-section mb-3">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="fw-semibold mb-0">\${escHtml(r.title)}</h6>
            <span class="br-badge \${urgMap[r.urgency]||'br-badge-gray'}">\${urgLabel[r.urgency]||r.urgency}</span>
          </div>
          \${r.category_name ? '<div class="text-subtle small mb-2">📂 ' + escHtml(r.category_name) + '</div>' : ''}
          <div class="text-muted small mb-2" style="white-space:pre-wrap;line-height:1.6">\${escHtml(r.problem_text || 'No written description.')}</div>
          \${r.problem_voice_url ? '<div class="mt-2"><label class="br-form-label small">🎙️ Client Voice Note</label><audio controls class="w-100" src="'+r.problem_voice_url+'"></audio></div>' : ''}
          <div class="d-flex gap-3 mt-2 text-subtle small">
            <span>💰 Rate: $\${parseFloat(r.agreed_rate||0).toFixed(0)}</span>
            <span>📅 \${new Date(r.created_at).toLocaleDateString()}</span>
            <span>📊 Status: \${r.status}</span>
          </div>
        </div>`;
    } catch(e) {
      area.innerHTML = '<div class="br-alert br-alert-warning small">Network error loading request.</div>';
    }
  }
JS;

$jsNew = <<<JS
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
          document.querySelector('textarea[name="key_insights"]').value = (data.response.key_insights ? JSON.parse(data.response.key_insights).join("\\n") : '');
          document.querySelector('textarea[name="action_items"]').value = (data.response.action_items ? JSON.parse(data.response.action_items).join("\\n") : '');
          document.querySelector('textarea[name="resource_links"]').value = (data.response.resources_links ? JSON.parse(data.response.resources_links).join("\\n") : '');
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
            <h6 class="fw-semibold mb-0">\${escHtml(r.title)}</h6>
            <span class="br-badge \${urgMap[r.urgency]||'br-badge-gray'}">\${urgLabel[r.urgency]||r.urgency}</span>
          </div>
          \${r.category_name ? '<div class="text-subtle small mb-2">📂 ' + escHtml(r.category_name) + '</div>' : ''}
          <div class="text-muted small mb-2" style="white-space:pre-wrap;line-height:1.6">\${escHtml(r.problem_text || 'No written description.')}</div>
          \${r.problem_voice_url ? '<div class="mt-2"><label class="br-form-label small">🎙️ Client Voice Note</label><audio controls class="w-100" src="'+r.problem_voice_url+'"></audio></div>' : ''}
          <div class="d-flex gap-3 mt-2 text-subtle small">
            <span>💰 Rate: $\${parseFloat(r.agreed_rate||0).toFixed(0)}</span>
            <span>📅 \${new Date(r.created_at).toLocaleDateString()}</span>
            <span>📊 Status: \${r.status}</span>
          </div>
        </div>`;
    } catch(e) {
      area.innerHTML = '<div class="br-alert br-alert-warning small">Network error loading request.</div>';
    }
  }
JS;

$content = str_replace($jsOld, $jsNew, $content);

$notifHtmlOld = <<<HTML
        const tag    = n.link ? 'a' : 'div';
        const href   = n.link ? `href="\${n.link}"` : '';
HTML;

$notifHtmlNew = <<<HTML
        const isChat = n.type === 'new_message';
        const tag    = 'a';
        let href = `href="javascript:void(0)"`;
        if (isChat) {
            href = `href="javascript:void(0)" onclick="showSection('exp-chat', document.getElementById('nav-exp-chat'))"`;
        } else if (n.link) {
            href = `href="\${n.link}"`;
        }
HTML;

$content = str_replace($notifHtmlOld, $notifHtmlNew, $content);

file_put_contents('pages/dashboard-expert.php', $content);
echo "Done";
?>
