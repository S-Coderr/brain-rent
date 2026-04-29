<?php
$content = file_get_contents('pages/dashboard-expert.php');

$old = <<<HTML
    <a href="javascript:void(0)" class="br-nav-item" onclick="showSection('exp-uploads',this)"><i
        class="bi bi-cloud-check me-2"></i>My Uploads
      <?php if (\$uploadSummary['total_items'] > 0): ?><span
          class="br-nav-badge"><?= \$uploadSummary['total_items'] ?></span><?php endif; ?>
    </a>
HTML;

$new = <<<HTML
    <a href="javascript:void(0)" class="br-nav-item" onclick="showSection('exp-uploads',this)"><i
        class="bi bi-cloud-check me-2"></i>My Uploads
    </a>
HTML;

$content = str_replace($old, $new, $content);
file_put_contents('pages/dashboard-expert.php', $content);
echo "Fixed!";
?>
