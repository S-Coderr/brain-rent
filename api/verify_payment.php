<?php
// =============================================
// api/verify_payment.php
// POST /api/verify_payment.php
// Verify Razorpay callback signature
// =============================================

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'POST required'], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$paymentId = $body['razorpay_payment_id'] ?? '';
$orderId   = $body['razorpay_order_id']   ?? '';
$signature = $body['razorpay_signature']  ?? '';

if (!$paymentId || !$orderId || !$signature) {
    jsonResponse(['success' => false, 'error' => 'Missing payment details'], 400);
}

// Verify signature
$generated = hash_hmac('sha256', $orderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);

if (!hash_equals($generated, $signature)) {
    jsonResponse(['success' => false, 'error' => 'Payment verification failed'], 400);
}

$db = Database::getInstance();

// Update payment record
$db->execute(
    "UPDATE payments
     SET gateway_payment_id = ?, status = 'captured', captured_at = NOW()
     WHERE gateway_order_id = ?",
    [$paymentId, $orderId]
);

// Update request payment_status to 'held'
$db->execute(
    "UPDATE thinking_requests
     SET payment_status = 'held', payment_id = ?
     WHERE id = (SELECT request_id FROM payments WHERE gateway_order_id = ?)",
    [$paymentId, $orderId]
);

jsonResponse(['success' => true, 'message' => 'Payment captured and held in escrow']);
