<?php
require_once __DIR__ . '/config/db.php';
$db = Database::getInstance();
$db->execute("UPDATE problem_solving_videos SET is_active = 1 WHERE is_active = 0");
echo "Updated!";
?>
