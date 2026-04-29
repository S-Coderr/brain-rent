<?php
// api/delete-account.php — Delete (anonymize) the current user account
require_once __DIR__ . '/../config/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/pages/profile.php?del_status=error&del_message=' . rawurlencode('Invalid request method.'));
    exit;
}

if (!verifyCsrf($_POST['csrf'] ?? '')) {
    header('Location: ' . APP_URL . '/pages/profile.php?del_status=error&del_message=' . rawurlencode('Session expired. Please try again.'));
    exit;
}

$userId = currentUserId();
$result = deleteUserAccount($userId);

if (empty($result['success'])) {
    header('Location: ' . APP_URL . '/pages/profile.php?del_status=error&del_message=' . rawurlencode($result['error'] ?? 'Could not delete account.'));
    exit;
}

logoutUser();
header('Location: ' . APP_URL . '/pages/auth.php?tab=login');
exit;
