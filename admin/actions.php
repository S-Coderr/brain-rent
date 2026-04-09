<?php
// admin/actions.php — Admin actions for content management
require_once __DIR__ . '/../config/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/admin/index.php');
    exit;
}

if (!verifyCsrf($_POST['csrf'] ?? '')) {
    header('Location: ' . APP_URL . '/admin/index.php?status=error&message=Invalid+CSRF');
    exit;
}

$entity = $_POST['entity'] ?? '';
$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$redirect = $_POST['redirect'] ?? APP_URL . '/admin/index.php';

$tableMap = [
    'notes' => ['table' => 'notes', 'fileCols' => ['file_path']],
    'libraries' => ['table' => 'libraries', 'fileCols' => ['file_path', 'cover_image']],
    'videos' => ['table' => 'problem_solving_videos', 'fileCols' => ['video_path', 'thumbnail']],
    'users' => ['table' => 'users', 'fileCols' => []],
    'experts' => ['table' => 'expert_profiles', 'fileCols' => []],
];

if (!$id || !isset($tableMap[$entity])) {
    header('Location: ' . $redirect . '?status=error&message=Invalid+request');
    exit;
}

$db = Database::getInstance();
$table = $tableMap[$entity]['table'];
$fileCols = $tableMap[$entity]['fileCols'];

function appendStatus(string $url, string $status, string $message): string
{
    $separator = strpos($url, '?') === false ? '?' : '&';
    return $url . $separator . 'status=' . rawurlencode($status) . '&message=' . rawurlencode($message);
}

if ($action === 'toggle_active') {
    $db->execute("UPDATE {$table} SET is_active = IF(is_active=1,0,1) WHERE id = ?", [$id]);
    header('Location: ' . appendStatus($redirect, 'success', 'Status updated'));
    exit;
}

if ($action === 'verify_expert') {
    $user = $db->fetchOne("SELECT user_type FROM users WHERE id = ?", [$id]);
    if (!$user || !in_array($user['user_type'], ['expert', 'both'])) {
        header('Location: ' . appendStatus($redirect, 'error', 'User is not an expert'));
        exit;
    }

    $profile = $db->fetchOne("SELECT id FROM expert_profiles WHERE user_id = ?", [$id]);
    if (!$profile) {
        header('Location: ' . appendStatus($redirect, 'error', 'Expert profile not found'));
        exit;
    }

    $db->execute("UPDATE expert_profiles SET is_verified = 1, is_available = 1 WHERE user_id = ?", [$id]);
    $db->execute("UPDATE users SET is_active = 1 WHERE id = ?", [$id]);

    header('Location: ' . appendStatus($redirect, 'success', 'Expert verified'));
    exit;
}

if ($action === 'delete') {
    if ($entity === 'users') {
        header('Location: ' . appendStatus($redirect, 'error', 'Users cannot be deleted'));
        exit;
    }

    $row = $db->fetchOne("SELECT * FROM {$table} WHERE id = ?", [$id]);
    if (!$row) {
        header('Location: ' . appendStatus($redirect, 'error', 'Record not found'));
        exit;
    }

    $db->execute("DELETE FROM {$table} WHERE id = ?", [$id]);

    foreach ($fileCols as $col) {
        if (empty($row[$col])) {
            continue;
        }

        $value = $row[$col];
        $filePath = resolveUploadedFilePath($value);

        if (is_string($filePath) && file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    header('Location: ' . appendStatus($redirect, 'success', 'Record deleted'));
    exit;
}

header('Location: ' . appendStatus($redirect, 'error', 'Unknown action'));
exit;
