<?php
// api/verify_security_answer.php
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Invalid request method'], 405);
}

$email = trim((string) ($_POST['email'] ?? ''));
$question = trim((string) ($_POST['security_question'] ?? ''));
$answer = (string) ($_POST['security_answer'] ?? '');

$result = verifySecurityAnswer($email, $question, $answer);
if (!empty($result['success'])) {
    jsonResponse(['success' => true]);
}

jsonResponse(['success' => false, 'error' => $result['error'] ?? 'Verification failed'], 400);
