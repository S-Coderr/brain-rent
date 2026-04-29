<?php
// api/download-video.php — Download Video File
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/media_blob_helpers.php';

$videoId = (int)($_GET['id'] ?? 0);

if (!$videoId) {
    die('Invalid video ID');
}

$db = Database::getInstance();
$video = $db->fetchOne("SELECT * FROM problem_solving_videos WHERE id = ? AND is_active = 1", [$videoId]);

if (!$video) {
    die('Video not found');
}

$blobMeta = brGetEntityFileBlobMeta($db, 'problem_solving_videos', $videoId);
if ($blobMeta) {
    $ext = strtolower((string) ($blobMeta['file_extension'] ?: pathinfo((string) ($video['video_path'] ?? ''), PATHINFO_EXTENSION)));
    $filename = brBuildSafeDownloadName((string) ($video['title'] ?? ''), $ext, 'video');
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
$filePath = resolveUploadedFilePath($video['video_path']);

if (!file_exists($filePath)) {
    die('File not found on server');
}

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$filename = brBuildSafeDownloadName((string) ($video['title'] ?? ''), $ext, 'video');

// Force download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
