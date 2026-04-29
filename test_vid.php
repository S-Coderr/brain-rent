<?php
require_once __DIR__ . '/config/db.php';
$db = Database::getInstance();
$res = $db->fetchAll('SELECT id, title, is_active FROM problem_solving_videos');
var_dump($res);
?>
