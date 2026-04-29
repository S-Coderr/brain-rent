<?php
require_once __DIR__ . '/config/db.php';
$db = Database::getInstance();
$userId = 1;
try {
    $res = $db->fetchAll(
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
    var_dump($res);
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
?>
