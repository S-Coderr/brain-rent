<?php
// api/view-video.php — Stream video for browser playback with DB fallback
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/media_blob_helpers.php';

$videoId = (int) ($_GET['id'] ?? 0);
if ($videoId <= 0) {
    http_response_code(400);
    die('Invalid video ID');
}

$db = Database::getInstance();
$video = $db->fetchOne('SELECT * FROM problem_solving_videos WHERE id = ? AND is_active = 1', [$videoId]);

if (!$video) {
    http_response_code(404);
    die('Video not found');
}

$filePath = resolveUploadedFilePath((string) ($video['video_path'] ?? ''));
if (is_file($filePath)) {
    $size = filesize($filePath);
    $ext = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
    $mime = brDetectMimeTypeFromExtension($ext);

    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');

    $range = $_SERVER['HTTP_RANGE'] ?? '';
    if ($range && preg_match('/bytes=(\d+)-(\d*)/', $range, $m)) {
        $start = (int) $m[1];
        $end = ($m[2] === '') ? ($size - 1) : (int) $m[2];

        if ($start > $end || $end >= $size) {
            header('HTTP/1.1 416 Range Not Satisfiable');
            header('Content-Range: bytes */' . $size);
            exit;
        }

        $length = $end - $start + 1;
        header('HTTP/1.1 206 Partial Content');
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
        header('Content-Length: ' . $length);

        $fp = fopen($filePath, 'rb');
        if ($fp === false) {
            http_response_code(500);
            die('Unable to open video stream');
        }

        fseek($fp, $start);
        $remaining = $length;
        while (!feof($fp) && $remaining > 0) {
            $chunk = fread($fp, min(8192, $remaining));
            if ($chunk === false) {
                break;
            }
            echo $chunk;
            $remaining -= strlen($chunk);
            flush();
        }
        fclose($fp);
        exit;
    }

    header('Content-Length: ' . $size);
    readfile($filePath);
    exit;
}

$blobMeta = brGetEntityFileBlobMeta($db, 'problem_solving_videos', $videoId);
if (!$blobMeta) {
    http_response_code(404);
    die('Video file not found');
}

$contentType = $blobMeta['mime_type'] ?: brDetectMimeTypeFromExtension((string) ($blobMeta['file_extension'] ?? ''));
header('Content-Type: ' . $contentType);
header('Accept-Ranges: none');
header('Content-Length: ' . (int) ($blobMeta['file_size'] ?? 0));

if (!brStreamEntityFileBlobByUploadId($db, (int) $blobMeta['id'])) {
    http_response_code(500);
    die('Failed to stream video');
}
exit;
