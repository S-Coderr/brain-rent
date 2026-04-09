<?php
// database/seed_admin_user.php — Seed admin account
require_once __DIR__ . '/../config/db.php';

$db = Database::getInstance();

// Ensure admin role exists in the enum for existing databases
$db->execute("ALTER TABLE users MODIFY user_type ENUM('client','expert','both','admin') NOT NULL DEFAULT 'client'");

$loginId = 'admin123';
$password = 'admin123';
$fullName = 'Admin User';

$existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$loginId]);
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

if ($existing) {
    $db->execute(
        "UPDATE users SET password_hash = ?, user_type = 'admin', full_name = ?, is_active = 1, is_email_verified = 1 WHERE id = ?",
        [$hash, $fullName, $existing['id']]
    );
    $adminId = (int) $existing['id'];
} else {
    $adminId = $db->insertGetId(
        "INSERT INTO users (full_name, email, password_hash, user_type, is_active, is_email_verified)
         VALUES (?, ?, ?, 'admin', 1, 1)",
        [$fullName, $loginId, $hash]
    );
}

echo "Admin account ready:\n";
echo "- login id: {$loginId}\n";
echo "- password: {$password}\n";
