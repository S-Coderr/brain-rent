<?php
// Upgrade Admin Portal UI
$adminFile = 'admin/index.php';
if (file_exists($adminFile)) {
    $content = file_get_contents($adminFile);
    
    // Add custom CSS for hover effects
    $customCss = "
    <style>
        .br-metric-interactive {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 1px solid var(--br-border);
            position: relative;
            overflow: hidden;
        }
        .br-metric-interactive:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            border-color: var(--br-gold);
        }
        .br-metric-interactive::after {
            content: '';
            position: absolute;
            top: 0; left: -100%; width: 50%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transform: skewX(-20deg);
            transition: left 0.5s ease;
        }
        .br-metric-interactive:hover::after {
            left: 200%;
        }
        .dashboard-banner {
            background: linear-gradient(135deg, rgba(250, 245, 235, 1) 0%, rgba(245, 248, 250, 1) 100%);
            border-left: 6px solid var(--br-gold);
            border-radius: 16px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }
        [data-bs-theme=\"dark\"] .dashboard-banner {
            background: linear-gradient(135deg, rgba(30, 35, 40, 1) 0%, rgba(20, 22, 25, 1) 100%);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
    </style>
    ";
    
    // Replace header
    $oldHeader = '        <div class="mb-4">
            <h1 class="display-6 fw-bold">Admin Portal</h1>
            <p class="text-muted">Experts, clients, and uploads overview.</p>
        </div>';
        
    $newHeader = $customCss . '
        <div class="dashboard-banner p-4 p-md-5 mb-5">
            <div style="position: relative; z-index: 2;">
                <h1 class="display-5 fw-bold mb-2">🛡️ Admin Command Center</h1>
                <p class="text-muted" style="font-size: 1.1rem; max-width: 600px;">Oversee the entire BrainRent ecosystem. Manage verified experts 🧑‍🏫, support clients 🤝, and moderate platform content 📚.</p>
            </div>
            <div style="position: absolute; right: 20px; top: -10px; font-size: 8rem; opacity: 0.05; z-index: 1;">👑</div>
        </div>';
        
    $content = str_replace($oldHeader, $newHeader, $content);
    
    // Upgrade stat cards
    $replacements = [
        '<div class="br-card p-3">' => '<div class="br-card p-3 br-metric-interactive h-100">',
        '<div class="text-subtle small">Experts</div>' => '<div class="text-subtle small fw-semibold text-uppercase tracking-wide">Experts 🧑‍🏫</div>',
        '<div class="text-subtle small">Clients</div>' => '<div class="text-subtle small fw-semibold text-uppercase tracking-wide">Clients 🤝</div>',
        '<div class="text-subtle small">Notes</div>' => '<div class="text-subtle small fw-semibold text-uppercase tracking-wide">Notes 📝</div>',
        '<div class="text-subtle small">Books</div>' => '<div class="text-subtle small fw-semibold text-uppercase tracking-wide">Books 📚</div>',
        '<div class="text-subtle small">Videos</div>' => '<div class="text-subtle small fw-semibold text-uppercase tracking-wide">Videos 🎥</div>',
        '<div class="text-subtle small">Upload Rows</div>' => '<div class="text-subtle small fw-semibold text-uppercase tracking-wide">DB Rows 💾</div>',
    ];
    
    foreach ($replacements as $old => $new) {
        $content = str_replace($old, $new, $content);
    }
    
    file_put_contents($adminFile, $content);
}

// Upgrade Client Portal UI
$clientFile = 'pages/dashboard-client.php';
if (file_exists($clientFile)) {
    $content = file_get_contents($clientFile);
    
    $oldHeader = '      <div class="mb-4">
        <h1 class="br-section-title fs-3 mb-1">Client Dashboard 🚀</h1>
        <p class="text-muted small">Manage your problems, active sessions, and payments.</p>
      </div>';
      
    $newHeader = '      <div class="dashboard-banner p-4 mb-4">
        <div style="position: relative; z-index: 2;">
            <h1 class="display-6 fw-bold mb-1">Client Portal 🚀</h1>
            <p class="text-muted mb-0">Manage your problems, active sessions, and track expert solutions.</p>
        </div>
        <div style="position: absolute; right: 20px; top: -10px; font-size: 6rem; opacity: 0.05; z-index: 1;">💡</div>
      </div>';
      
    $content = str_replace($oldHeader, $newHeader, $content);
    
    // Make metric cards interactive
    $content = str_replace('class="br-metric-card"', 'class="br-metric-card br-metric-interactive"', $content);
    
    file_put_contents($clientFile, $content);
}

// Upgrade Expert Portal UI
$expertFile = 'pages/dashboard-expert.php';
if (file_exists($expertFile)) {
    $content = file_get_contents($expertFile);
    
    $oldHeader = '      <div class="mb-4">
        <h1 class="br-section-title fs-3 mb-1">Expert Dashboard 🧠</h1>
        <p class="text-muted small"><?= count($newReqs) ?> open problem(s) available to experts.</p>
      </div>';
      
    $newHeader = '      <div class="dashboard-banner p-4 mb-4">
        <div style="position: relative; z-index: 2;">
            <h1 class="display-6 fw-bold mb-1">Expert Portal 🧠</h1>
            <p class="text-muted mb-0"><?= count($newReqs) ?> open problem(s) awaiting your brilliant solutions!</p>
        </div>
        <div style="position: absolute; right: 20px; top: -10px; font-size: 6rem; opacity: 0.05; z-index: 1;">🎓</div>
      </div>';
      
    $content = str_replace($oldHeader, $newHeader, $content);
    
    // Make metric cards interactive
    $content = str_replace('class="br-metric-card"', 'class="br-metric-card br-metric-interactive"', $content);
    
    file_put_contents($expertFile, $content);
}

echo "UI Upgraded Successfully!";
?>
