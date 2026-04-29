<?php
// api/view-ebook.php — View E-Book File
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/media_blob_helpers.php';

$bookId = (int)($_GET['id'] ?? 0);

if (!$bookId) {
    die('Invalid book ID');
}

$db = Database::getInstance();
$book = $db->fetchOne("SELECT * FROM libraries WHERE id = ? AND is_active = 1", [$bookId]);

if (!$book) {
    die('Book not found');
}

// Increment view count
$db->execute("UPDATE libraries SET views = views + 1 WHERE id = ?", [$bookId]);

$blobMeta = brGetEntityFileBlobMeta($db, 'libraries', $bookId);
if ($blobMeta) {
    $ext = strtolower((string) ($blobMeta['file_extension'] ?: ($book['file_type'] ?? '')));
    $filename = brBuildSafeDownloadName((string) ($book['title'] ?? ''), $ext, 'ebook');
    $contentType = $blobMeta['mime_type'] ?: brDetectMimeTypeFromExtension($ext);

    header('Content-Type: ' . $contentType);
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . (int) ($blobMeta['file_size'] ?? 0));

    if (!brStreamEntityFileBlobByUploadId($db, (int) $blobMeta['id'])) {
        http_response_code(500);
        die('Failed to stream file from database');
    }
    exit;
}

// Get file path
$filePath = resolveUploadedFilePath($book['file_path']);

if (!file_exists($filePath)) {
    die('File not found on server');
}

$contentTypes = [
    'pdf' => 'application/pdf',
    'epub' => 'application/epub+zip',
    'mobi' => 'application/x-mobipocket-ebook',
];

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$contentType = $contentTypes[$ext] ?? 'application/octet-stream';
$filename = brBuildSafeDownloadName((string) ($book['title'] ?? ''), $ext, 'ebook');

header('Content-Type: ' . $contentType);
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
