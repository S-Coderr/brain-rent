<?php
// =============================================
// api/manage_request.php
// POST /api/manage_request.php
// Handles: accept, decline, submit_response, complete, dispute
// =============================================

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

$action    = $_POST['action'] ?? ($_GET['action'] ?? '');
$requestId = (int) ($_POST['request_id'] ?? ($_GET['request_id'] ?? 0));

if (!$requestId || !$action) {
    jsonResponse(['success' => false, 'error' => 'Missing parameters'], 400);
}

$db     = Database::getInstance();
$userId = currentUserId();

// =============================================
// EXPERT: Accept Request
// =============================================
if ($action === 'accept') {
    requireExpert();

    $req = $db->fetchOne(
        "SELECT * FROM thinking_requests WHERE id = ? AND expert_id = ? AND status = 'submitted'",
        [$requestId, $userId]
    );
    if (!$req) jsonResponse(['success' => false, 'error' => 'Request not found or already handled'], 404);

    $db->execute(
        "UPDATE thinking_requests
         SET status = 'accepted', accepted_at = NOW(), thinking_started_at = NOW()
         WHERE id = ?",
        [$requestId]
    );

    // Notify client
    $db->execute(
        "INSERT INTO notifications (user_id, type, title, message, link)
         VALUES (?, 'request_accepted', 'Request accepted', 'Your request has been accepted and the expert is thinking.', ?)",
        [
            $req['client_id'],
            APP_URL . '/pages/dashboard-client.php?request_id=' . $requestId
        ]
    );

    jsonResponse(['success' => true, 'message' => 'Request accepted']);
}

// =============================================
// EXPERT: Decline Request
// =============================================
if ($action === 'decline') {
    requireExpert();

    $req = $db->fetchOne(
        "SELECT * FROM thinking_requests WHERE id = ? AND expert_id = ? AND status = 'submitted'",
        [$requestId, $userId]
    );
    if (!$req) jsonResponse(['success' => false, 'error' => 'Not found'], 404);

    $reason = htmlspecialchars($_POST['reason'] ?? '');
    $db->execute(
        "UPDATE thinking_requests SET status = 'declined' WHERE id = ?",
        [$requestId]
    );

    // Trigger refund
    $db->execute(
        "UPDATE payments SET status = 'refunded', refunded_at = NOW() WHERE request_id = ?",
        [$requestId]
    );

    $db->execute(
        "INSERT INTO notifications (user_id, type, title, message, link)
         VALUES (?, 'request_declined', 'Request declined', ?, ?)",
        [
            $req['client_id'],
            'Your request was declined. Reason: ' . ($reason ?: 'No reason provided') . '. A full refund will be processed.',
            APP_URL . '/pages/browse.php'
        ]
    );

    jsonResponse(['success' => true, 'message' => 'Request declined and refund initiated']);
}

// =============================================
// EXPERT: Submit Response
// =============================================
if ($action === 'submit_response') {
    requireExpert();

    $req = $db->fetchOne(
        "SELECT * FROM thinking_requests WHERE id = ? AND expert_id = ? AND status IN ('accepted','thinking')",
        [$requestId, $userId]
    );
    if (!$req) jsonResponse(['success' => false, 'error' => 'Request not found or not in correct status'], 404);

    // Voice response upload
    $voicePath     = null;
    $voiceDuration = null;
    if (!empty($_FILES['voice_response']) && $_FILES['voice_response']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['audio/webm', 'audio/wav', 'audio/mpeg', 'audio/ogg'];
        if (!in_array($_FILES['voice_response']['type'], $allowed)) {
            jsonResponse(['success' => false, 'error' => 'Invalid audio format'], 400);
        }

        $dir = __DIR__ . "/../uploads/voice_responses/{$userId}/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename  = uniqid('resp_', true) . '.webm';
        $voicePath = $dir . $filename;
        move_uploaded_file($_FILES['voice_response']['tmp_name'], $voicePath);

        $escaped       = escapeshellarg($voicePath);
        $output        = shell_exec("ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {$escaped} 2>/dev/null");
        if ($output) $voiceDuration = (int) floatval(trim($output));
    }

    // Parse JSON arrays
    $keyInsights    = json_encode(array_filter(array_map('trim', explode("\n", $_POST['key_insights']   ?? ''))));
    $actionItems    = json_encode(array_filter(array_map('trim', explode("\n", $_POST['action_items']   ?? ''))));
    $resourceLinks  = json_encode(array_filter(array_map('trim', explode("\n", $_POST['resource_links'] ?? ''))));

    $db->insertGetId(
        "INSERT INTO thinking_responses
            (request_id, expert_id, written_response, voice_response_path,
             voice_duration_seconds, key_insights, action_items, resources_links, actual_thinking_minutes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $requestId,
            $userId,
            htmlspecialchars($_POST['written_response'] ?? ''),
            $voicePath,
            $voiceDuration,
            $keyInsights,
            $actionItems,
            $resourceLinks,
            !empty($_POST['thinking_minutes']) ? (int) $_POST['thinking_minutes'] : null,
        ]
    );

    // Update request status
    $db->execute(
        "UPDATE thinking_requests SET status = 'responded', responded_at = NOW() WHERE id = ?",
        [$requestId]
    );

    // Update expert total sessions
    $db->execute(
        "UPDATE expert_profiles SET total_sessions = total_sessions + 1 WHERE user_id = ?",
        [$userId]
    );

    // Credit pending balance
    $payment = $db->fetchOne(
        "SELECT expert_payout FROM payments WHERE request_id = ?",
        [$requestId]
    );
    if ($payment) {
        $db->execute(
            "UPDATE expert_wallet SET pending_balance = pending_balance + ? WHERE expert_user_id = ?",
            [$payment['expert_payout'], $userId]
        );
        $db->execute(
            "UPDATE payments SET status = 'held', captured_at = NOW() WHERE request_id = ?",
            [$requestId]
        );
    }

    // Notify client
    $db->execute(
        "INSERT INTO notifications (user_id, type, title, message, link)
         VALUES (?, 'response_ready', 'Response ready!', 'Your expert has submitted their response. Please review it.', ?)",
        [
            $req['client_id'],
            APP_URL . '/pages/dashboard-client.php?request_id=' . $requestId
        ]
    );

    jsonResponse(['success' => true, 'message' => 'Response submitted. Client notified.']);
}

// =============================================
// CLIENT: Complete Request (Release Payment)
// =============================================
if ($action === 'complete') {
    $req = $db->fetchOne(
        "SELECT * FROM thinking_requests WHERE id = ? AND client_id = ? AND status = 'responded'",
        [$requestId, $userId]
    );
    if (!$req) jsonResponse(['success' => false, 'error' => 'Request not found or not in responded status'], 404);

    // Use stored procedure
    $conn = $db->getConnection();
    $stmt = $conn->prepare("CALL sp_complete_request(?,?)");
    $stmt->execute([$requestId, $userId]);
    $row = $stmt->fetch();
    $result = $row['result'] ?? 'error';

    if ($result !== 'success') {
        jsonResponse(['success' => false, 'error' => $result], 500);
    }

    // Save review if provided
    $rating = (int) ($_POST['rating'] ?? 0);
    if ($rating >= 1 && $rating <= 5) {
        $db->execute(
            "INSERT INTO reviews
                (request_id, reviewer_id, expert_id, rating, review_text,
                 clarity_rating, depth_rating, usefulness_rating)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $requestId,
                $userId,
                $req['expert_id'],
                $rating,
                htmlspecialchars($_POST['review_text'] ?? ''),
                (int) ($_POST['clarity_rating']    ?? $rating),
                (int) ($_POST['depth_rating']      ?? $rating),
                (int) ($_POST['usefulness_rating'] ?? $rating),
            ]
        );
        // Update expert average rating
        $conn = $db->getConnection();
        $stmt = $conn->prepare("CALL sp_update_expert_rating(?)");
        $stmt->execute([$req['expert_id']]);
    }

    // Notify expert
    $db->execute(
        "INSERT INTO notifications (user_id, type, title, message, link)
         VALUES (?, 'payment_released', 'Payment released!', 'The client confirmed your response. Payment has been released to your wallet.', ?)",
        [
            $req['expert_id'],
            APP_URL . '/pages/dashboard-expert.php#wallet'
        ]
    );

    jsonResponse(['success' => true, 'message' => 'Session completed. Payment released.']);
}

// =============================================
// CLIENT: Raise Dispute
// =============================================
if ($action === 'dispute') {
    $req = $db->fetchOne(
        "SELECT * FROM thinking_requests WHERE id = ? AND client_id = ? AND status = 'responded'",
        [$requestId, $userId]
    );
    if (!$req) jsonResponse(['success' => false, 'error' => 'Not found'], 404);

    $reason = htmlspecialchars($_POST['reason'] ?? '');
    $db->execute(
        "UPDATE thinking_requests SET status = 'disputed' WHERE id = ?",
        [$requestId]
    );

    // Admin notification (in real app — email admin)
    jsonResponse(['success' => true, 'message' => 'Dispute raised. Our team will contact you within 24 hours.']);
}

jsonResponse(['success' => false, 'error' => 'Unknown action'], 400);
