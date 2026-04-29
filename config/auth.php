<?php
// =============================================
// config/auth.php  —  Session & Auth Helpers
// =============================================

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Helpers ----

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/pages/auth.php?tab=login');
        exit;
    }
}

function currentUser(): ?array
{
    if (!isLoggedIn())
        return null;
    $db = Database::getInstance();
    return $db->fetchOne(
        "SELECT id, full_name, email, user_type, profile_photo FROM users WHERE id = ? AND is_active = 1",
        [$_SESSION['user_id']]
    );
}

function currentUserId(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function ensurePendingExpertProfilesTable(Database $db): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $db->execute(
        "CREATE TABLE IF NOT EXISTS pending_expert_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            desired_user_type ENUM('expert','both') NOT NULL DEFAULT 'expert',
            headline VARCHAR(255),
            qualification VARCHAR(255),
            domain VARCHAR(150),
            skills TEXT,
            expertise_areas TEXT,
            experience_years INT,
            current_role_name VARCHAR(200),
            company VARCHAR(200),
            linkedin_url VARCHAR(500),
            portfolio_url VARCHAR(500),
            rate_per_session DECIMAL(10,2) NOT NULL DEFAULT 0,
            currency VARCHAR(3) DEFAULT 'USD',
            session_duration_minutes INT DEFAULT 10,
            max_response_hours INT DEFAULT 48,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            admin_note TEXT,
            reviewed_by INT NULL,
            reviewed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_pep_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_pep_admin FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_pep_status_created (status, created_at),
            INDEX idx_pep_user_status (user_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ensured = true;
}

function ensureTemporaryPaymentsTable(Database $db): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $db->execute(
        "CREATE TABLE IF NOT EXISTS temporary_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_id INT NOT NULL,
            client_id INT NOT NULL,
            preferred_expert_id INT NULL,
            gateway ENUM('stripe','razorpay') NOT NULL DEFAULT 'razorpay',
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'USD',
            status ENUM('initiated','paid','failed') NOT NULL DEFAULT 'paid',
            transaction_ref VARCHAR(191),
            paid_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_temp_pay_request FOREIGN KEY (request_id) REFERENCES thinking_requests(id) ON DELETE CASCADE,
            CONSTRAINT fk_temp_pay_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_temp_pay_expert FOREIGN KEY (preferred_expert_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_temp_pay_request (request_id),
            INDEX idx_temp_pay_client (client_id),
            INDEX idx_temp_pay_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ensured = true;
}

function getSecurityQuestionOptions(): array
{
    return [
        'first_pet' => 'What was the name of your first pet?',
        'first_school' => 'What is the name of your first school?',
        'favorite_teacher' => 'Who was your favorite teacher?',
        'birth_city' => 'In which city were you born?',
        'childhood_nickname' => 'What was your childhood nickname?',
    ];
}

function normalizeSecurityAnswer(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    $value = preg_replace('/\s+/', ' ', $value);
    return strtolower($value ?? '');
}

function ensureSecurityQuestionColumns(Database $db): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $columns = $db->fetchAll("SHOW COLUMNS FROM users");
    $existing = [];
    foreach ($columns as $col) {
        $existing[strtolower($col['Field'])] = true;
    }

    $conn = $db->getConnection();
    if (!isset($existing['security_question'])) {
        $conn->exec("ALTER TABLE users ADD COLUMN security_question VARCHAR(255) NULL");
    }
    if (!isset($existing['security_answer_hash'])) {
        $conn->exec("ALTER TABLE users ADD COLUMN security_answer_hash VARCHAR(255) NULL");
    }

    $ensured = true;
}

function ensureUserProfileColumns(Database $db): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $columns = $db->fetchAll("SHOW COLUMNS FROM users");
    $existing = [];
    foreach ($columns as $col) {
        $existing[strtolower($col['Field'])] = true;
    }

    $desired = [
        'phone' => "VARCHAR(20) NULL",
        'user_type' => "ENUM('client','expert','both','admin') NOT NULL DEFAULT 'client'",
        'profile_photo' => "VARCHAR(500) NULL",
        'bio' => "TEXT NULL",
        'country' => "VARCHAR(100) NULL",
        'timezone' => "VARCHAR(50) NULL",
        'is_email_verified' => "TINYINT(1) DEFAULT 0",
        'is_active' => "TINYINT(1) DEFAULT 1",
        'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    ];

    $conn = $db->getConnection();
    foreach ($desired as $name => $definition) {
        if (isset($existing[strtolower($name)])) {
            continue;
        }
        $conn->exec("ALTER TABLE users ADD COLUMN {$name} {$definition}");
    }

    $ensured = true;
}

function ensureExpertProfilesColumns(Database $db): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $exists = $db->fetchOne(
        "SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ?",
        [DB_NAME, 'expert_profiles']
    );
    if (!$exists) {
        return;
    }

    $columns = $db->fetchAll("SHOW COLUMNS FROM expert_profiles");
    $existing = [];
    foreach ($columns as $col) {
        $existing[strtolower($col['Field'])] = true;
    }

    $desired = [
        'headline' => "VARCHAR(255) NULL",
        'qualification' => "VARCHAR(255) NULL",
        'domain' => "VARCHAR(150) NULL",
        'skills' => "TEXT NULL",
        'expertise_areas' => "TEXT NULL",
        'experience_years' => "INT NULL",
        'current_role_name' => "VARCHAR(200) NULL",
        'company' => "VARCHAR(200) NULL",
        'linkedin_url' => "VARCHAR(500) NULL",
        'portfolio_url' => "VARCHAR(500) NULL",
        'rate_per_session' => "DECIMAL(10,2) NOT NULL DEFAULT 0",
        'currency' => "VARCHAR(3) DEFAULT 'USD'",
        'session_duration_minutes' => "INT DEFAULT 10",
        'max_response_hours' => "INT DEFAULT 48",
        'is_available' => "TINYINT(1) DEFAULT 1",
        'max_active_requests' => "INT DEFAULT 5",
        'total_sessions' => "INT DEFAULT 0",
        'total_earnings' => "DECIMAL(12,2) DEFAULT 0",
        'average_rating' => "DECIMAL(3,2) DEFAULT 0",
        'total_reviews' => "INT DEFAULT 0",
        'is_verified' => "TINYINT(1) DEFAULT 0",
        'verification_docs' => "VARCHAR(500) NULL",
        'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    ];

    $conn = $db->getConnection();
    foreach ($desired as $name => $definition) {
        if (isset($existing[strtolower($name)])) {
            continue;
        }
        $conn->exec("ALTER TABLE expert_profiles ADD COLUMN {$name} {$definition}");
    }

    $ensured = true;
}

function isExpertVerified(int $userId): bool
{
    if (isset($_SESSION['expert_verified']) && (int) ($_SESSION['user_id'] ?? 0) === $userId && (int) $_SESSION['expert_verified'] === 1) {
        return true;
    }

    $db = Database::getInstance();
    $row = $db->fetchOne("SELECT is_verified FROM expert_profiles WHERE user_id = ?", [$userId]);
    $verified = !empty($row) && (int) $row['is_verified'] === 1;

    if ((int) ($_SESSION['user_id'] ?? 0) === $userId) {
        $_SESSION['expert_verified'] = $verified ? 1 : 0;
    }

    return $verified;
}

function requireExpert(): void
{
    requireLogin();
    $user = currentUser();
    if (!$user || !in_array($user['user_type'], ['expert', 'both'])) {
        header('Location: ' . APP_URL . '/pages/dashboard-client.php');
        exit;
    }
    if (!isExpertVerified($user['id'])) {
        header('Location: ' . APP_URL . '/pages/expert-pending.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    $user = currentUser();
    if (!$user || $user['user_type'] !== 'admin') {
        header('Location: ' . APP_URL . '/pages/index.php');
        exit;
    }
}

// ---- Registration ----

function registerUser(array $data): array
{
    $db = Database::getInstance();
    ensureSecurityQuestionColumns($db);
    $userType = strtolower(trim($data['user_type'] ?? 'client'));
    if (!in_array($userType, ['client', 'expert', 'both'], true)) {
        $userType = 'client';
    }
    $email = strtolower(trim($data['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email address'];
    }

    $phone = trim((string) ($data['phone'] ?? ''));
    $phone = $phone === '' ? null : $phone;
    $country = trim((string) ($data['country'] ?? ''));
    $country = $country === '' ? null : $country;

    $securityQuestions = getSecurityQuestionOptions();
    $securityQuestion = trim((string) ($data['security_question'] ?? ''));
    $securityAnswerRaw = (string) ($data['security_answer'] ?? '');
    $securityAnswer = normalizeSecurityAnswer($securityAnswerRaw);
    if ($securityQuestion === '' || !array_key_exists($securityQuestion, $securityQuestions)) {
        return ['success' => false, 'error' => 'Please select a valid security question'];
    }
    if ($securityAnswer === '') {
        return ['success' => false, 'error' => 'Please provide a security answer'];
    }

    $securityAnswerHash = password_hash($securityAnswer, PASSWORD_BCRYPT, ['cost' => 12]);

    // Check duplicate email
    $existing = $db->fetchOne(
        "SELECT id FROM users WHERE email = ?",
        [$email]
    );
    if ($existing) {
        return ['success' => false, 'error' => 'Email already registered'];
    }

    $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare(
            "INSERT INTO users (full_name, email, password_hash, user_type, country, phone, security_question, security_answer_hash)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            htmlspecialchars(trim($data['full_name'] ?? '')),
            $email,
            $hash,
            $userType,
            $country,
            $phone,
            $securityQuestion,
            $securityAnswerHash,
        ]);
        $id = (int) $conn->lastInsertId();

        if (!$id) {
            $conn->rollBack();
            return ['success' => false, 'error' => 'Registration failed'];
        }

        if (in_array($userType, ['expert', 'both'])) {
            ensurePendingExpertProfilesTable($db);

            $expert = $data['expert'] ?? [];
            $normalizeText = static function ($value): ?string {
                $value = trim((string) $value);
                return $value === '' ? null : $value;
            };

            $normalizeNumber = static function ($value): string {
                $value = trim((string) $value);
                if ($value === '') {
                    return '';
                }
                $value = preg_replace('/[^0-9.]/', '', $value);
                return $value ?? '';
            };

            $skillsRaw = $normalizeText($expert['skills'] ?? '');
            $skillsList = $skillsRaw
                ? array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', $skillsRaw))))
                : [];
            $expertiseJson = $skillsList ? json_encode($skillsList) : null;

            $rateRaw = $normalizeNumber($expert['rate_per_session'] ?? null);
            $rate = is_numeric($rateRaw) ? (float) $rateRaw : 0.0;
            $sessionRaw = $normalizeNumber($expert['session_duration_minutes'] ?? null);
            $sessionMinutes = is_numeric($sessionRaw)
                ? (int) $sessionRaw
                : 10;
            $maxResponseRaw = $normalizeNumber($expert['max_response_hours'] ?? null);
            $maxResponseHours = is_numeric($maxResponseRaw)
                ? (int) $maxResponseRaw
                : 48;
            $experienceRaw = $normalizeNumber($expert['experience_years'] ?? null);
            $experienceYears = $experienceRaw !== ''
                ? (int) $experienceRaw
                : null;
            $currency = strtoupper(trim((string) ($expert['currency'] ?? 'USD')));
            $currency = $currency === '' ? 'USD' : $currency;
            $currency = substr($currency, 0, 3);

            $stmt = $conn->prepare(
                "INSERT INTO pending_expert_profiles
                    (user_id, desired_user_type, headline, qualification, domain, skills, expertise_areas, experience_years,
                     current_role_name, company, linkedin_url, portfolio_url, rate_per_session, currency,
                     session_duration_minutes, max_response_hours, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([
                $id,
                $userType,
                $normalizeText($expert['headline'] ?? null),
                $normalizeText($expert['qualification'] ?? null),
                $normalizeText($expert['domain'] ?? null),
                $skillsRaw,
                $expertiseJson,
                $experienceYears,
                $normalizeText($expert['current_role'] ?? null),
                $normalizeText($expert['company'] ?? null),
                $normalizeText($expert['linkedin_url'] ?? null),
                $normalizeText($expert['portfolio_url'] ?? null),
                $rate,
                $currency,
                $sessionMinutes,
                $maxResponseHours,
            ]);
        }

        $conn->commit();

        $adminTitle = $userType === 'expert'
            ? 'New expert registration'
            : 'New client registration';
        $adminMessage = $userType === 'expert'
            ? 'Expert signup: ' . ($data['full_name'] ?? 'New expert') . ' (' . $email . '). Review pending profile.'
            : 'Client signup: ' . ($data['full_name'] ?? 'New client') . ' (' . $email . ').';
        $adminLink = $userType === 'expert'
            ? APP_URL . '/admin/expert-review.php?id=' . $id
            : APP_URL . '/admin/users.php';
        notifyAdmins($db, $userType === 'expert' ? 'admin_expert_registered' : 'admin_client_registered', $adminTitle, $adminMessage, $adminLink);
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log('Registration failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Registration failed'];
    }

    return ['success' => true, 'user_id' => $id];
}

function verifySecurityAnswer(string $email, string $question, string $answer): array
{
    $db = Database::getInstance();
    ensureSecurityQuestionColumns($db);

    $email = strtolower(trim($email));
    $question = trim($question);
    $answer = normalizeSecurityAnswer($answer);

    if ($email === '' || $question === '' || $answer === '') {
        return ['success' => false, 'error' => 'Please complete all fields'];
    }

    $questions = getSecurityQuestionOptions();
    if (!array_key_exists($question, $questions)) {
        return ['success' => false, 'error' => 'Invalid security question'];
    }

    $row = $db->fetchOne(
        "SELECT id, security_question, security_answer_hash, is_active FROM users WHERE email = ?",
        [$email]
    );

    if (!$row || (int) ($row['is_active'] ?? 0) !== 1) {
        return ['success' => false, 'error' => 'Account not found'];
    }

    if (empty($row['security_question']) || empty($row['security_answer_hash'])) {
        return ['success' => false, 'error' => 'Security question not set for this account'];
    }

    if ($row['security_question'] !== $question) {
        return ['success' => false, 'error' => 'Security answer mismatch'];
    }

    if (!password_verify($answer, $row['security_answer_hash'])) {
        return ['success' => false, 'error' => 'Security answer mismatch'];
    }

    return ['success' => true, 'user_id' => (int) $row['id']];
}

function resetPasswordWithSecurityAnswer(string $email, string $question, string $answer, string $newPassword): array
{
    $check = verifySecurityAnswer($email, $question, $answer);
    if (!$check['success']) {
        return $check;
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $db = Database::getInstance();
    $updated = $db->execute(
        "UPDATE users SET password_hash = ? WHERE id = ?",
        [$hash, (int) $check['user_id']]
    );

    if ($updated < 1) {
        return ['success' => false, 'error' => 'Unable to update password'];
    }

    return ['success' => true];
}

function deleteUserAccount(int $userId): array
{
    $db = Database::getInstance();
    ensureUserProfileColumns($db);
    ensureSecurityQuestionColumns($db);
    ensurePendingExpertProfilesTable($db);
    ensureExpertProfilesColumns($db);

    $user = $db->fetchOne(
        "SELECT id, full_name, email, user_type FROM users WHERE id = ?",
        [$userId]
    );

    if (!$user) {
        return ['success' => false, 'error' => 'Account not found'];
    }

    $deletedName = 'Deleted Account';
    $deletedSnapshot = [
        'full_name' => (string) ($user['full_name'] ?? 'Unknown'),
        'email' => (string) ($user['email'] ?? ''),
        'user_type' => (string) ($user['user_type'] ?? 'client'),
    ];
    $deletedEmail = 'deleted_' . $userId . '_' . time() . '@brainrent.local';
    $randomHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT, ['cost' => 12]);

    $conn = $db->getConnection();

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare(
            "UPDATE users
             SET full_name = ?,
                 email = ?,
                 phone = NULL,
                 country = NULL,
                 timezone = NULL,
                 profile_photo = NULL,
                 bio = NULL,
                 password_hash = ?,
                 is_email_verified = 0,
                 is_active = 0,
                 security_question = NULL,
                 security_answer_hash = NULL
             WHERE id = ?"
        );
        $stmt->execute([$deletedName, $deletedEmail, $randomHash, $userId]);

        $stmt = $conn->prepare("DELETE FROM pending_expert_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);

        $stmt = $conn->prepare("DELETE FROM expert_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);

        $stmt = $conn->prepare(
            "UPDATE expert_wallet
             SET bank_account_name = NULL,
                 bank_account_number = NULL,
                 bank_ifsc = NULL,
                 upi_id = NULL
             WHERE expert_user_id = ?"
        );
        $stmt->execute([$userId]);

        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$userId]);

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollBack();
        error_log('Delete account failed: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Unable to delete account right now'];
    }

    $roleLabel = $deletedSnapshot['user_type'] !== '' ? $deletedSnapshot['user_type'] : 'user';
    $adminTitle = 'User account deleted';
    $adminMessage = 'Deleted ' . $roleLabel . ': ' . $deletedSnapshot['full_name'] .
        ($deletedSnapshot['email'] ? ' (' . $deletedSnapshot['email'] . ')' : '') . '.';
    notifyAdmins($db, 'admin_user_deleted', $adminTitle, $adminMessage, APP_URL . '/admin/users.php');

    return ['success' => true];
}

// ---- Login ----

function loginUser(string $email, string $password): array
{
    $db = Database::getInstance();
    $user = $db->fetchOne(
        "SELECT u.id, u.password_hash, u.full_name, u.user_type, u.is_active, u.is_email_verified,
                IFNULL(ep.is_verified, 0) AS expert_verified
         FROM users u
         LEFT JOIN expert_profiles ep ON ep.user_id = u.id
         WHERE u.email = ?",
        [strtolower(trim($email))]
    );

    if (!$user || !$user['is_active']) {
        return ['success' => false, 'error' => 'Invalid credentials'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid credentials'];
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['expert_verified'] = (int) ($user['expert_verified'] ?? 0);

    return ['success' => true, 'user' => $user];
}

// ---- Logout ----

function logoutUser(): void
{
    $_SESSION = [];
    session_destroy();
}

// ---- CSRF ----

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) &&
        hash_equals($_SESSION['csrf_token'], $token);
}

// ---- JSON response helper ----

function insertNotification(Database $db, int $userId, string $type, string $title, string $message, ?string $link = null): void
{
    if ($userId <= 0) {
        return;
    }

    $db->execute(
        "INSERT INTO notifications (user_id, type, title, message, link)
         VALUES (?, ?, ?, ?, ?)",
        [
            $userId,
            substr($type, 0, 50),
            substr($title, 0, 255),
            substr($message, 0, 2000),
            $link ? substr($link, 0, 500) : null,
        ]
    );
}

function notifyAdmins(Database $db, string $type, string $title, string $message, ?string $link = null): void
{
    $admins = $db->fetchAll("SELECT id FROM users WHERE user_type = 'admin' AND is_active = 1");
    if (!$admins) {
        return;
    }

    foreach ($admins as $admin) {
        insertNotification($db, (int) $admin['id'], $type, $title, $message, $link);
    }
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
