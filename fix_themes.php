<?php

// 1. Client Portal
$clientFile = 'pages/dashboard-client.php';
$content = file_get_contents($clientFile);
$start = strpos($content, '<div class="dashboard-banner');
if ($start !== false) {
    $end = strpos($content, '</div>', strpos($content, '</div>', strpos($content, '</div>', $start) + 1) + 1) + 6;
    $date = date('F j, Y');
    $newBanner = '
      <div class="dashboard-banner p-4 mb-4" style="background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); border-radius: 20px; color: white; display: flex; justify-content: space-between; align-items: center; border: none; box-shadow: 0 10px 20px rgba(161, 140, 209, 0.3);">
        <div style="position: relative; z-index: 2;">
            <p style="margin-bottom: 5px; font-size: 0.9rem; opacity: 0.9;">' . $date . '</p>
            <h1 class="display-6 fw-bold mb-1" style="color: white;">Welcome back, <?= htmlspecialchars($user[\'full_name\'] ?? \'Client\') ?>!</h1>
            <p class="mb-0" style="opacity: 0.9;">Always stay updated in your student portal</p>
        </div>
        <div style="font-size: 6rem; z-index: 1; line-height: 1; text-shadow: 2px 2px 10px rgba(0,0,0,0.1);">👨‍🎓</div>
      </div>
';
    $content = substr_replace($content, $newBanner, $start, $end - $start);
    file_put_contents($clientFile, $content);
}

// 2. Expert Portal
$expertFile = 'pages/dashboard-expert.php';
$content = file_get_contents($expertFile);
$start = strpos($content, '<div class="dashboard-banner');
if ($start !== false) {
    $end = strpos($content, '</div>', strpos($content, '</div>', strpos($content, '</div>', $start) + 1) + 1) + 6;
    $newBanner = '
      <div class="dashboard-banner p-4 mb-4" style="background: white; border-radius: 20px; border: 1px solid #e0f2f1; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-left: 5px solid #4db6ac;">
        <div style="position: relative; z-index: 2; width: 100%; max-width: 600px;">
            <h1 class="display-6 fw-bold mb-2" style="color: #263238;">Welcome Back !</h1>
            <p class="text-muted mb-3" style="font-size: 0.95rem;">Professional Certificates offer flexible, online training designed to get you job-ready for high-growth fields.</p>
            <div style="background: #e0f2f1; border-radius: 10px; height: 10px; width: 100%; overflow: hidden;">
                <div style="background: #4db6ac; width: 82%; height: 100%; border-radius: 10px;"></div>
            </div>
            <small class="text-muted mt-1 d-block text-end">82%</small>
        </div>
        <div style="font-size: 6rem; z-index: 1; line-height: 1; filter: drop-shadow(0px 10px 10px rgba(0,0,0,0.1));">📚</div>
      </div>
';
    $content = substr_replace($content, $newBanner, $start, $end - $start);
    file_put_contents($expertFile, $content);
}

// 3. Admin Portal
$adminFile = 'admin/index.php';
$content = file_get_contents($adminFile);
$start = strpos($content, '<div class="dashboard-banner');
if ($start !== false) {
    $end = strpos($content, '</div>', strpos($content, '</div>', strpos($content, '</div>', $start) + 1) + 1) + 6;
    $newBanner = '
        <div class="dashboard-banner p-4 p-md-5 mb-5" style="background: #3bb19b; border-radius: 20px; color: white; display: flex; justify-content: space-between; align-items: center; border: none; box-shadow: 0 8px 20px rgba(59, 177, 155, 0.3); overflow: hidden;">
            <div style="position: relative; z-index: 2; max-width: 60%;">
                <h1 class="display-5 fw-bold mb-2" style="color: white;">Manage Effectively With Us!</h1>
                <p class="mb-4" style="opacity: 0.9;">Oversee the entire BrainRent ecosystem.</p>
                <div class="d-flex flex-wrap gap-3">
                    <div style="background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; display: flex; align-items: center; gap: 8px;">
                        <div style="background: #ff5252; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem;">🎓</div>
                        <span style="font-size: 0.9rem;">Experts <strong><?= number_format($stats[\'experts\']) ?>+</strong></span>
                    </div>
                    <div style="background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; display: flex; align-items: center; gap: 8px;">
                        <div style="background: #ffd740; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: black; font-size: 1rem;">👤</div>
                        <span style="font-size: 0.9rem;">Clients <strong><?= number_format($stats[\'clients\']) ?>+</strong></span>
                    </div>
                </div>
            </div>
            <div style="font-size: 8rem; z-index: 1; line-height: 1; filter: drop-shadow(0px 10px 10px rgba(0,0,0,0.1));">🏫</div>
        </div>
';
    $content = substr_replace($content, $newBanner, $start, $end - $start);
    file_put_contents($adminFile, $content);
}

echo "Themes Applied Successfully!";
?>
