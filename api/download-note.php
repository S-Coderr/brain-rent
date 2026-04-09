<?php
// api/download-note.php — Download Notes File
require_once __DIR__ . '/../config/db.php';

$noteId = (int)($_GET['id'] ?? 0);

if (!$noteId) {
    die('Invalid note ID');
}

$db = Database::getInstance();
$note = $db->fetchOne("SELECT * FROM notes WHERE id = ? AND is_active = 1", [$noteId]);

if (!$note) {
    die('Note not found');
}

// Increment download count
$db->execute("UPDATE notes SET downloads = downloads + 1 WHERE id = ?", [$noteId]);

// Get file path
$filePath = resolveUploadedFilePath($note['file_path']);

if (!file_exists($filePath)) {
    die('File not found on server');
}

// Force download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($note['title']) . '.' . $note['file_type'] . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
