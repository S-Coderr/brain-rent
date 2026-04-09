<?php
// api/download-video.php — Download Video File
require_once __DIR__ . '/../config/db.php';

$videoId = (int)($_GET['id'] ?? 0);

if (!$videoId) {
    die('Invalid video ID');
}

$db = Database::getInstance();
$video = $db->fetchOne("SELECT * FROM problem_solving_videos WHERE id = ? AND is_active = 1", [$videoId]);

if (!$video) {
    die('Video not found');
}

// Get file path
$filePath = resolveUploadedFilePath($video['video_path']);

if (!file_exists($filePath)) {
    die('File not found on server');
}

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$base = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $video['title'] ?: 'video');
$base = trim($base, '_');
$filename = $base ?: 'video';
if ($ext) {
    $filename .= '.' . $ext;
}

// Force download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
