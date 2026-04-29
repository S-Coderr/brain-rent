<?php
$content = file_get_contents('admin/index.php');

// 1. Remove "Database Upload Storage" table.
$startPos = strpos($content, '<div class="br-card p-3 mb-4">');
if ($startPos !== false) {
    // We know it is followed by <h6 class="fw-semibold mb-0">Database Upload Storage</h6>
    $dbStorePos = strpos($content, 'Database Upload Storage', $startPos);
    if ($dbStorePos !== false && $dbStorePos - $startPos < 200) {
        $endPos = strpos($content, '</div>', strpos($content, '</table>', $startPos)) + 6; 
        // Need to find the end of the .br-card div.
        // It's the closing div of <div class="br-card p-3 mb-4">
        // It has <div class="table-responsive"> inside.
        // So after </table>, there are two closing </div> tags.
        $tableEnd = strpos($content, '</table>', $startPos);
        $div1End = strpos($content, '</div>', $tableEnd) + 6;
        $div2End = strpos($content, '</div>', $div1End) + 6;
        
        $content = substr_replace($content, '', $startPos, $div2End - $startPos);
    }
}

// 2. Fix heights for Notes, Books, Videos
// Find: <div class="br-card p-3">
// Change to: <div class="br-card p-3 h-100 d-flex flex-column">
// Only inside the "admin-triple-row"

$tripleRowPos = strpos($content, '<div class="row g-4 admin-triple-row">');
if ($tripleRowPos !== false) {
    $endTripleRow = strlen($content);
    $tripleBlock = substr($content, $tripleRowPos);
    
    // Replace card classes
    $tripleBlock = str_replace('<div class="br-card p-3">', '<div class="br-card p-3 h-100 d-flex flex-column">', $tripleBlock);
    
    // Replace table-responsive classes
    $tripleBlock = str_replace('<div class="table-responsive">', '<div class="table-responsive flex-grow-1" style="max-height: 300px; overflow-y: auto;">', $tripleBlock);
    
    // Also style the th elements so the header is sticky!
    $tripleBlock = str_replace('<th>', '<th style="position: sticky; top: 0; background-color: var(--br-dark2); z-index: 1;">', $tripleBlock);
    
    $content = substr_replace($content, $tripleBlock, $tripleRowPos);
}

file_put_contents('admin/index.php', $content);
echo "Fixed!";
?>
