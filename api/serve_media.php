<?php
// api/serve_media.php
// Serves request-related media (attachments, voice) securely, verifying user permissions.

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();
$userId = currentUserId();
$userType = currentUser()['user_type'] ?? '';
$isAdmin = $userType === 'admin';
$isVerifiedExpert = in_array($userType, ['expert', 'both'], true) && isExpertVerified($userId);

$path = $_GET['path'] ?? '';
if (empty($path)) {
    http_response_code(400);
    exit('Missing path');
}

// Prevent directory traversal
$realBase = realpath(__DIR__ . '/../');
$realPath = realpath(__DIR__ . '/../' . $path);

if ($realPath === false || strpos($realPath, $realBase) !== 0 || !is_file($realPath)) {
    http_response_code(404);
    exit('File not found');
}

$canAccess = false;
if ($isAdmin) {
    $canAccess = true;
} else {
    // Determine the request ID associated with this file.
    $db = Database::getInstance();
    $request = null;

    // Search in thinking_requests (problem voice)
    $request = $db->fetchOne(
        "SELECT * FROM thinking_requests WHERE problem_voice_path LIKE ?",
        ['%' . basename($path)]
    );

    // Search in thinking_request_attachments
    if (!$request) {
        $att = $db->fetchOne(
            "SELECT request_id FROM thinking_request_attachments WHERE file_path LIKE ?",
            ['%' . basename($path)]
        );
        if ($att) {
            $request = $db->fetchOne("SELECT * FROM thinking_requests WHERE id = ?", [$att['request_id']]);
        }
    }

    // Search in thinking_responses (response voice)
    if (!$request) {
        $resp = $db->fetchOne(
            "SELECT request_id FROM thinking_responses WHERE voice_response_path LIKE ?",
            ['%' . basename($path)]
        );
        if ($resp) {
            $request = $db->fetchOne("SELECT * FROM thinking_requests WHERE id = ?", [$resp['request_id']]);
        }
    }

    if ($request) {
        if ((int) $request['client_id'] === $userId) {
            $canAccess = true;
        } elseif ($isVerifiedExpert) {
            $isGlobal = (int) ($request['is_global'] ?? 0) === 1;
            if ((int) ($request['expert_id'] ?? 0) === $userId || ($isGlobal && $request['status'] === 'submitted')) {
                $canAccess = true;
            }
        }
    }
}

if (!$canAccess) {
    http_response_code(403);
    exit('Access denied');
}

// Serve the file
$ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
$mime = 'application/octet-stream';
if ($ext === 'webm') $mime = 'audio/webm';
elseif ($ext === 'mp3') $mime = 'audio/mpeg';
elseif ($ext === 'mp4') $mime = 'video/mp4';
elseif ($ext === 'wav') $mime = 'audio/wav';
elseif ($ext === 'pdf') $mime = 'application/pdf';
elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'gif'])) {
    $mime = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: private, max-age=86400'); // Cache securely
readfile($realPath);
exit;
