<?php
require 'config/db.php';
$db = Database::getInstance();
$res = $db->fetchAll('DESCRIBE thinking_requests');
print_r($res);
