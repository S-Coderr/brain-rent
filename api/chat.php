<?php
// api/chat.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$db = Database::getInstance();
$userId = currentUserId();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'send') {
    $receiverId = (int) ($_POST['receiver_id'] ?? 0);
    $text = trim($_POST['message_text'] ?? '');

    if ($receiverId <= 0 || $text === '') {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
        exit;
    }

    $db->execute(
        "INSERT INTO direct_messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)",
        [$userId, $receiverId, $text]
    );

    // Get the newly inserted message
    $msgId = $db->getConnection()->lastInsertId();
    $msg = $db->fetchOne("SELECT * FROM direct_messages WHERE id = ?", [$msgId]);

    $previewText = mb_strlen($text) > 40 ? mb_substr($text, 0, 40) . '...' : $text;

    // Send a notification if this is a new chat/message (rate limiting omitted for simplicity)
    insertNotification(
        $db,
        $receiverId,
        'new_message',
        'New Message',
        $previewText,
        APP_URL . "/pages/chat.php?user_id=" . $userId
    );

    echo json_encode(['success' => true, 'message' => $msg]);
    exit;
} elseif ($action === 'fetch') {
    $otherUserId = (int) ($_GET['other_user_id'] ?? 0);
    if ($otherUserId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
        exit;
    }

    // Mark messages as read
    $db->execute(
        "UPDATE direct_messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?",
        [$userId, $otherUserId]
    );

    $messages = $db->fetchAll(
        "SELECT * FROM direct_messages 
         WHERE (sender_id = ? AND receiver_id = ?) 
            OR (sender_id = ? AND receiver_id = ?)
         ORDER BY created_at ASC",
        [$userId, $otherUserId, $otherUserId, $userId]
    );

    echo json_encode(['success' => true, 'messages' => $messages, 'current_user_id' => $userId]);
    exit;
} elseif ($action === 'contacts') {
    $contacts = $db->fetchAll(
        "SELECT u.id AS user_id, u.full_name,
            m.message_text AS last_message, m.created_at AS last_message_at,
            (SELECT COUNT(*) FROM direct_messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) AS unread_count
         FROM users u
         JOIN (
            SELECT 
                IF(sender_id = ?, receiver_id, sender_id) AS contact_id,
                MAX(id) as max_msg_id
            FROM direct_messages
            WHERE sender_id = ? OR receiver_id = ?
            GROUP BY 1
         ) latest ON u.id = latest.contact_id
         JOIN direct_messages m ON latest.max_msg_id = m.id
         ORDER BY m.created_at DESC",
        [$userId, $userId, $userId, $userId]
    );
    echo json_encode(['success' => true, 'contacts' => $contacts]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
