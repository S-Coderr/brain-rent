<?php
require 'config/db.php';
$db = Database::getInstance();
try {
    $db->execute("ALTER TABLE thinking_requests ADD COLUMN is_global TINYINT(1) DEFAULT 0 AFTER expert_id");
    echo "Added is_global\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $db->execute("ALTER TABLE thinking_requests ADD COLUMN category_id INT DEFAULT NULL AFTER problem_voice_duration");
    echo "Added category_id\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $db->execute("ALTER TABLE thinking_requests ADD COLUMN urgency VARCHAR(50) DEFAULT 'normal' AFTER category_id");
    echo "Added urgency\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $db->execute("ALTER TABLE thinking_requests ADD COLUMN agreed_rate DECIMAL(10,2) DEFAULT 0.00 AFTER urgency");
    echo "Added agreed_rate\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $db->execute("ALTER TABLE thinking_requests ADD COLUMN currency VARCHAR(10) DEFAULT 'USD' AFTER agreed_rate");
    echo "Added currency\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }
