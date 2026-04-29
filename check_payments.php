<?php
require 'config/db.php';
$db = Database::getInstance();
try {
    $db->execute('SELECT 1 FROM payments LIMIT 1');
    echo 'Payments table exists';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
