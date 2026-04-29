<?php
// api/notifications.php — Fetch and update notifications

header('Content-Type: application/json');
require_once __DIR__ . '/../config/auth.php';

requireLogin();

$db = Database::getInstance();
$userId = currentUserId();
$action = strtolower(trim((string) ($_GET['action'] ?? $_POST['action'] ?? '')));
$redirect = trim((string) ($_GET['redirect'] ?? $_POST['redirect'] ?? ''));

if ($action === 'mark_all_read') {
    $db->execute("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$userId]);

    if ($redirect !== '') {
        header('Location: ' . $redirect);
        exit;
    }

    jsonResponse(['success' => true]);
}

if ($action === 'mark_read') {
    $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($id > 0) {
        $db->execute(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    if ($redirect !== '') {
        header('Location: ' . $redirect);
        exit;
    }

    jsonResponse(['success' => true]);
}

$limit = (int) ($_GET['limit'] ?? 20);
$limit = max(1, min($limit, 100));

$notifications = $db->fetchAll(
    "SELECT id, type, title, message, link, is_read, created_at
     FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT {$limit}",
    [$userId]
);

$unread = $db->fetchOne(
    "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0",
    [$userId]
);

jsonResponse([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => (int) ($unread['cnt'] ?? 0),
]);
