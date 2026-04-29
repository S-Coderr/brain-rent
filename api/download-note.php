<?php
// api/download-note.php — Download Notes File
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/media_blob_helpers.php';

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

$blobMeta = brGetEntityFileBlobMeta($db, 'notes', $noteId);
if ($blobMeta) {
    $ext = strtolower((string) ($blobMeta['file_extension'] ?: ($note['file_type'] ?? '')));
    $filename = brBuildSafeDownloadName((string) ($note['title'] ?? ''), $ext, 'note');
    $contentType = $blobMeta['mime_type'] ?: 'application/octet-stream';

    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . (int) ($blobMeta['file_size'] ?? 0));

    if (!brStreamEntityFileBlobByUploadId($db, (int) $blobMeta['id'])) {
        http_response_code(500);
        die('Failed to stream file from database');
    }
    exit;
}

// Get file path
$filePath = resolveUploadedFilePath($note['file_path']);

if (!file_exists($filePath)) {
    die('File not found on server');
}

// Force download
$ext = strtolower((string) ($note['file_type'] ?: pathinfo($filePath, PATHINFO_EXTENSION)));
$filename = brBuildSafeDownloadName((string) ($note['title'] ?? ''), $ext, 'note');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
