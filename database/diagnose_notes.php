<?php
// database/diagnose_notes.php — Inspect recent note file paths.
require_once __DIR__ . '/../config/db.php';

$db = Database::getInstance();
$rows = $db->fetchAll("SELECT id, title, file_path, file_type, file_size FROM notes ORDER BY id DESC LIMIT 20");

if (!$rows) {
    echo "No notes found.\n";
    exit(0);
}

foreach ($rows as $r) {
    $path = resolveUploadedFilePath($r['file_path']);
    $exists = is_file($path) ? 'YES' : 'NO';
    echo $r['id'] . ' | ' . ($r['title'] ?? '') . ' | ' . $r['file_type'] . ' | ' . ($r['file_size'] ?? '') . ' | ' . $r['file_path'] . ' | ' . $path . ' | ' . $exists . "\n";
}
