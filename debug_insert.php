<?php
require 'config/db.php';
$db = Database::getInstance();
try {
    $res = $db->execute(
        "INSERT INTO thinking_requests
            (client_id, expert_id, is_global, title, problem_text, category_id, urgency, agreed_rate, currency, response_deadline, status, payment_status)
         VALUES (1, NULL, 1, 'Test', 'Test', 1, 'normal', 10, 'USD', '2027-01-01 00:00:00', 'submitted', 'held')"
    );
    echo "Insert successful: " . $db->getConnection()->lastInsertId();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
