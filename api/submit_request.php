<?php
// =============================================
// api/submit_request.php
// POST /api/submit_request.php
// Client submits a problem + creates payment order
// =============================================

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireLogin();

class ThinkingRequestSubmitter
{
    private Database $db;
    private int $clientId;

    public function __construct(int $clientId)
    {
        $this->db = Database::getInstance();
        $this->clientId = $clientId;
        ensureTemporaryPaymentsTable($this->db);
    }

    /**
     * Full submission flow:
     * 1. Validate expert availability
     * 2. Upload voice recording (if provided)
     * 3. Create thinking_requests row
     * 4. Upload attachments
     * 5. Save temporary fake payment
     * 6. Notify expert
     */
    public function submit(array $data): array
    {
        $scope = strtolower(trim((string) ($data['request_scope'] ?? 'direct')));
        $isGlobal = $scope === 'global';
        $expert = null;
        $preferredExpertId = null;

        // ---- 1. Load expert (direct requests only) ----
        if (!$isGlobal) {
            $preferredExpertId = (int) ($data['expert_id'] ?? 0);
            if ($preferredExpertId <= 0) {
                return ['success' => false, 'error' => 'Expert is required'];
            }

            $expert = $this->db->fetchOne(
                "SELECT ep.*, u.email AS expert_email, u.full_name AS expert_name
                 FROM expert_profiles ep
                 INNER JOIN users u ON ep.user_id = u.id
                 WHERE ep.user_id = ? AND ep.is_available = 1 AND u.is_active = 1",
                [$preferredExpertId]
            );

            if (!$expert) {
                return ['success' => false, 'error' => 'Expert not available'];
            }

            // ---- 2. Check expert active request cap ----
            $active = $this->db->fetchOne(
                "SELECT COUNT(*) AS cnt FROM thinking_requests
                 WHERE expert_id = ? AND status IN ('submitted','accepted','thinking')",
                [$preferredExpertId]
            );

            if ((int) ($active['cnt'] ?? 0) >= $expert['max_active_requests']) {
                return ['success' => false, 'error' => 'Expert has reached their request limit. Try again later.'];
            }
        }

        // ---- 3. Upload voice recording ----
        $voicePath = null;
        $voiceDuration = null;
        if (!empty($_FILES['voice_recording']) && $_FILES['voice_recording']['error'] === UPLOAD_ERR_OK) {
            $upload = $this->uploadVoice($_FILES['voice_recording'], 'problems');
            if (!$upload['success'])
                return $upload;
            $voicePath = $upload['path'];
            $voiceDuration = $upload['duration'];
        }

        // ---- 4. Urgency add-on ----
        $urgencyFees = ['normal' => 0, 'urgent' => 30, 'critical' => 60];
        $urgency = in_array($data['urgency'] ?? 'normal', array_keys($urgencyFees))
            ? $data['urgency'] : 'normal';
        if ($isGlobal) {
            $offerRaw = trim((string) ($data['offer_rate'] ?? ''));
            $offer = is_numeric($offerRaw) ? (float) $offerRaw : 0.0;
            if ($offer <= 0) {
                return ['success' => false, 'error' => 'Offer rate is required for global requests'];
            }
            $agreedRate = $offer + $urgencyFees[$urgency];
        } else {
            $agreedRate = $expert['rate_per_session'] + $urgencyFees[$urgency];
        }

        // ---- 5. Deadline ----
        $baseHours = $isGlobal ? 48 : (int) ($expert['max_response_hours'] ?? 48);
        $hours = $urgency === 'critical' ? 8 : ($urgency === 'urgent' ? 24 : $baseHours);
        $deadline = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));

        // ---- 6. Insert request ----
        if ($isGlobal && empty($data['category_id'])) {
            return ['success' => false, 'error' => 'Category is required for global requests'];
        }

        $currency = $isGlobal ? 'USD' : ($expert['currency'] ?? 'USD');

        $requestId = $this->db->insertGetId(
            "INSERT INTO thinking_requests
                (client_id, expert_id, is_global, title, problem_text,
                 problem_voice_path, problem_voice_duration,
                 category_id, urgency, agreed_rate, currency,
                 response_deadline, status, payment_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', 'held')",
            [
                $this->clientId,
                $preferredExpertId > 0 ? $preferredExpertId : null,
                $isGlobal ? 1 : 0,
                htmlspecialchars(trim((string) ($data['title'] ?? ''))),
                htmlspecialchars(trim((string) ($data['problem_text'] ?? ''))),
                $voicePath,
                $voiceDuration,
                !empty($data['category_id']) ? (int) $data['category_id'] : null,
                $urgency,
                $agreedRate,
                $currency,
                $deadline,
            ]
        );

        if (!$requestId) {
            return ['success' => false, 'error' => 'Could not create request'];
        }

        // ---- 7. Attachments ----
        if (!empty($_FILES['attachments']['name'][0])) {
            $this->handleAttachments($requestId, $_FILES['attachments']);
        }

        // ---- 8. Temporary fake payment ----
        $gateway = strtolower(trim((string) ($data['payment_gateway'] ?? 'razorpay')));
        if (!in_array($gateway, ['stripe', 'razorpay'], true)) {
            $gateway = 'razorpay';
        }
        $payment = $this->createTemporaryPayment($requestId, $preferredExpertId, $expert, $agreedRate, $gateway);
        $this->createPaymentRecord($requestId, $preferredExpertId, $agreedRate, $currency, $gateway, $payment['data']['transaction_ref'] ?? null);

        // ---- 9. Notify experts ----
        if ($isGlobal) {
            $this->notifyExpertsForGlobal($requestId, (int) ($data['category_id'] ?? 0), (string) ($data['title'] ?? ''), $urgency);
        } elseif ($expert) {
            $this->notifyExpert($expert, $requestId, (string) ($data['title'] ?? ''));
        }

        // ---- 10. Notify admins ----
        $this->notifyAdminsAboutRequest($requestId, (string) ($data['title'] ?? ''), $urgency, $isGlobal, $preferredExpertId, $expert);

        return [
            'success' => true,
            'request_id' => $requestId,
            'problem_url' => APP_URL . '/pages/problem.php?id=' . $requestId,
            'payment' => $payment['data'],
        ];
    }

    // ---- Temporary payment (bypass gateway for now) ----
    private function createTemporaryPayment(int $requestId, ?int $preferredExpertId, ?array $expert, float $agreedRate, string $gateway): array
    {
        $txRef = 'temp_' . $gateway . '_' . $requestId . '_' . time();
        $currency = $expert && !empty($expert['currency']) ? $expert['currency'] : 'USD';

        // We bypass inserting into temporary_payments and just pretend it succeeded
        // since the actual payment module is not implemented yet.

        return [
            'success' => true,
            'data' => [
                'gateway' => $gateway,
                'transaction_ref' => $txRef,
                'amount' => (float) $agreedRate,
                'currency' => $currency,
                'mode' => 'temporary',
            ]
        ];
    }

    private function createPaymentRecord(int $requestId, ?int $payeeId, float $agreedRate, string $currency, string $gateway, ?string $txRef): void
    {
        $platformFee = round($agreedRate * (PLATFORM_FEE_PERCENT / 100), 2);
        $amount = $agreedRate + $platformFee;

        $this->db->execute(
            "INSERT INTO payments
                (request_id, payer_id, payee_id, amount, platform_fee, expert_payout, currency, gateway,
                 gateway_payment_id, gateway_order_id, status, captured_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'captured', NOW())",
            [
                $requestId,
                $this->clientId,
                $payeeId,
                $amount,
                $platformFee,
                $agreedRate,
                $currency,
                $gateway,
                $txRef,
                $txRef,
            ]
        );
    }

    private function notifyExpertsForGlobal(int $requestId, int $categoryId, string $title, string $urgency): void
    {
        if ($categoryId <= 0) {
            return;
        }

        $cat = $this->db->fetchOne("SELECT name FROM expertise_categories WHERE id = ?", [$categoryId]);
        $categoryName = trim((string) ($cat['name'] ?? ''));
        if ($categoryName === '') {
            return;
        }

        $match = $this->db->fetchAll(
            "SELECT u.id, u.full_name
             FROM expert_profiles ep
             INNER JOIN users u ON u.id = ep.user_id
             WHERE ep.is_verified = 1
               AND ep.is_available = 1
               AND u.is_active = 1
               AND ep.domain IS NOT NULL
               AND LOWER(ep.domain) LIKE ?",
            ['%' . strtolower($categoryName) . '%']
        );

        if (!$match) {
            return;
        }

        foreach ($match as $row) {
            insertNotification(
                $this->db,
                (int) $row['id'],
                'new_global_request',
                'New global request in your domain',
                'A new ' . $categoryName . ' request was posted: ' . $title . ' (' . ucfirst($urgency) . ').',
                APP_URL . '/pages/dashboard-expert.php?request_id=' . $requestId
            );
        }
    }

    private function notifyAdminsAboutRequest(int $requestId, string $title, string $urgency, bool $isGlobal, ?int $preferredExpertId, ?array $expert): void
    {
        $client = $this->db->fetchOne("SELECT full_name, email FROM users WHERE id = ?", [$this->clientId]);
        $clientName = $client['full_name'] ?? 'Client';
        $clientEmail = $client['email'] ?? '';
        $scopeLabel = $isGlobal ? 'Global' : 'Direct';
        $expertLabel = 'Any expert';
        if (!$isGlobal && $expert) {
            $expertLabel = $expert['expert_name'] ?? 'Expert';
        }

        $message = $scopeLabel . ' request: ' . $title . ' (' . ucfirst($urgency) . '). '
            . 'Client: ' . $clientName . ($clientEmail ? ' (' . $clientEmail . ')' : '')
            . '. Preferred: ' . $expertLabel . '.';

        notifyAdmins(
            $this->db,
            'admin_request_posted',
            'New problem posted',
            $message,
            APP_URL . '/admin/requests.php'
        );
    }

    // ---- Voice Upload ----
    private function uploadVoice(array $file, string $folder): array
    {
        $allowed = ['audio/webm', 'audio/wav', 'audio/mpeg', 'audio/ogg', 'audio/mp4'];
        $isValid = false;
        foreach ($allowed as $mime) {
            if (strpos($file['type'], $mime) === 0) {
                $isValid = true; break;
            }
        }
        if (!$isValid) {
            return ['success' => false, 'error' => 'Invalid audio format: ' . $file['type']];
        }
        if ($file['size'] > 50 * 1024 * 1024) {
            return ['success' => false, 'error' => 'Voice file too large (max 50MB)'];
        }

        $dir = __DIR__ . "/../uploads/voice_{$folder}/{$this->clientId}/";
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['success' => false, 'error' => 'Could not create voice upload directory'];
        }

        $filename = uniqid('voice_', true) . '.webm';
        $path = $dir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return ['success' => false, 'error' => 'Could not save voice recording'];
        }

        // Try to get duration via ffprobe
        $duration = null;
        $escaped = escapeshellarg($path);
        $output = shell_exec("ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 {$escaped} 2>/dev/null");
        if ($output)
            $duration = (int) floatval(trim($output));

        return ['success' => true, 'path' => $path, 'duration' => $duration];
    }

    // ---- Attachments ----
    private function handleAttachments(int $requestId, array $files): void
    {
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK)
                continue;

            $dir = __DIR__ . "/../uploads/attachments/{$requestId}/";
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                continue;
            }

            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            $filename = uniqid('att_') . '.' . $ext;
            $path = $dir . $filename;
            if (!move_uploaded_file($files['tmp_name'][$i], $path)) {
                continue;
            }

            $this->db->execute(
                "INSERT INTO thinking_request_attachments
                    (request_id, uploaded_by, file_path, file_name, file_size_mb, file_type)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $requestId,
                    $this->clientId,
                    $path,
                    htmlspecialchars($files['name'][$i]),
                    round($files['size'][$i] / (1024 * 1024), 2),
                    $files['type'][$i],
                ]
            );
        }
    }

    // ---- Notification ----
    private function notifyExpert(array $expert, int $requestId, string $title): void
    {
        $db = $this->db;

        // 1. In-app notification
        $db->execute(
            "INSERT INTO notifications (user_id, type, title, message, link)
             VALUES (?, 'new_request', ?, ?, ?)",
            [
                $expert['user_id'],
                'New thinking request',
                "You have a new direct request: {$title}",
                APP_URL . '/pages/dashboard-expert.php',
            ]
        );

        // 2. Email notification
        $expertEmail = $expert['expert_email'] ?? '';
        $expertName  = $expert['expert_name']  ?? 'Expert';
        if ($expertEmail) {
            $client = $db->fetchOne("SELECT full_name FROM users WHERE id = ?", [$this->clientId]);
            $clientName = htmlspecialchars($client['full_name'] ?? 'A client');
            $safeTitle  = htmlspecialchars($title);
            $dashUrl    = APP_URL . '/pages/dashboard-expert.php';

            $subject = "🧠 New Request: {$title}";
            $body    = "
<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;background:#0f0f17;color:#e2e8f0;margin:0;padding:0'>
<div style='max-width:600px;margin:40px auto;background:#1a1a2e;border-radius:16px;overflow:hidden;border:1px solid #2d2d4e'>
  <div style='background:linear-gradient(135deg,#7c3aed,#d97706);padding:32px;text-align:center'>
    <div style='font-size:2rem'>🧠</div>
    <h1 style='color:#fff;margin:8px 0;font-size:1.4rem'>New Thinking Request</h1>
  </div>
  <div style='padding:32px'>
    <p style='margin:0 0 16px'>Hi <strong>{$expertName}</strong>,</p>
    <p style='margin:0 0 20px;color:#94a3b8'>{$clientName} has submitted a new problem directly to you:</p>
    <div style='background:#0f0f17;border:1px solid #2d2d4e;border-radius:12px;padding:20px;margin-bottom:24px'>
      <div style='font-size:.72rem;text-transform:uppercase;letter-spacing:1px;color:#64748b;margin-bottom:6px'>Problem Title</div>
      <div style='font-size:1.1rem;font-weight:600;color:#f8fafc'>{$safeTitle}</div>
    </div>
    <a href='{$dashUrl}' style='display:inline-block;background:linear-gradient(135deg,#d97706,#b45309);color:#fff;padding:14px 28px;border-radius:10px;text-decoration:none;font-weight:600'>
      View &amp; Accept Request →
    </a>
    <p style='margin-top:24px;font-size:.8rem;color:#64748b'>
      Log in to your expert dashboard to accept, review, and respond to this request.
    </p>
  </div>
  <div style='background:#0f0f17;padding:16px;text-align:center;font-size:.75rem;color:#475569'>
    © BrainRent · You received this because you are a registered expert.
  </div>
</div></body></html>";

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: BrainRent <noreply@brainrent.com>\r\n";
            $headers .= "X-Mailer: BrainRent-PHP\r\n";

            @mail($expertEmail, $subject, $body, $headers);
        }
    }
}

// ---- Handle POST ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'POST required'], 405);
}

$submitter = new ThinkingRequestSubmitter(currentUserId());
$result = $submitter->submit($_POST);

http_response_code($result['success'] ? 200 : 400);
echo json_encode($result);
