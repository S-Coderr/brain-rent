<?php
// =============================================
// api/update_wallet.php
// POST /api/update_wallet.php
// Update expert payout details (bank/UPI)
// =============================================

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();
requireExpert();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'POST required'], 405);
}

if (!verifyCsrf($_POST['csrf'] ?? '')) {
    jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
}

$db     = Database::getInstance();
$userId = currentUserId();

$bankName = trim($_POST['bank_account_name'] ?? '');
$bankNumberRaw = trim($_POST['bank_account_number'] ?? '');
$bankNumber = preg_replace('/\s+/', '', $bankNumberRaw);
$bankIfsc = strtoupper(trim($_POST['bank_ifsc'] ?? ''));
$upiId = trim($_POST['upi_id'] ?? '');

$hasBank = $bankName !== '' || $bankNumber !== '' || $bankIfsc !== '';
$hasUpi = $upiId !== '';

if (!$hasBank && !$hasUpi) {
    jsonResponse(['success' => false, 'error' => 'Provide bank details or a UPI ID'], 400);
}

if ($hasBank && ($bankName === '' || $bankNumber === '' || $bankIfsc === '')) {
    jsonResponse(['success' => false, 'error' => 'Complete bank name, account number, and IFSC'], 400);
}

if (!$hasBank) {
    $bankName = '';
    $bankNumber = '';
    $bankIfsc = '';
}

$existing = $db->fetchOne("SELECT id FROM expert_wallet WHERE expert_user_id = ?", [$userId]);
if (!$existing) {
    $db->execute("INSERT INTO expert_wallet (expert_user_id) VALUES (?)", [$userId]);
}

$db->execute(
    "UPDATE expert_wallet
     SET bank_account_name = ?, bank_account_number = ?, bank_ifsc = ?, upi_id = ?
     WHERE expert_user_id = ?",
    [
        $bankName ?: null,
        $bankNumber ?: null,
        $bankIfsc ?: null,
        $upiId ?: null,
        $userId
    ]
);

jsonResponse(['success' => true]);
