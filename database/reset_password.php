<?php
// database/reset_password.php — Reset a user's password via CLI.
require_once __DIR__ . '/../config/db.php';

if ($argc < 3) {
    echo "Usage: php database/reset_password.php <email> <new_password>\n";
    exit(1);
}

$email = trim((string) $argv[1]);
$newPassword = (string) $argv[2];

if ($email == '' || $newPassword == '') {
    echo "Email and password are required.\n";
    exit(1);
}

$db = Database::getInstance();
$hash = password_hash($newPassword, PASSWORD_BCRYPT, array('cost' => 12));

$db->execute(
    "UPDATE users SET password_hash = ?, is_active = 1, is_email_verified = 1 WHERE email = ?",
    array($hash, $email)
);

$row = $db->fetchOne("SELECT id, user_type FROM users WHERE email = ?", array($email));
if (!$row) {
    echo "User not found.\n";
    exit(2);
}

echo "Password reset for user id {$row['id']} ({$row['user_type']}).\n";
