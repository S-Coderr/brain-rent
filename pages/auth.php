<?php
// pages/auth.php — Combined Login + Signup
$title = 'Account Access';
require_once __DIR__ . '/../config/auth.php';

if (isLoggedIn()) {
    $user = currentUser();
    if ($user && $user['user_type'] === 'admin') {
        header('Location: ' . APP_URL . '/admin/index.php');
    } elseif (in_array($user['user_type'] ?? 'client', ['expert', 'both'])) {
        if (isExpertVerified($user['id'])) {
            header('Location: ' . APP_URL . '/pages/dashboard-expert.php');
        } else {
            header('Location: ' . APP_URL . '/pages/expert-pending.php');
        }
    } else {
        header('Location: ' . APP_URL . '/pages/dashboard-client.php');
    }
    exit;
}

$loginError = '';
$registerError = '';
$formType = $_POST['form_type'] ?? '';

$activeTab = $_GET['tab'] ?? 'login';
if (!in_array($activeTab, ['login', 'signup'])) {
    $activeTab = 'login';
}

$defaultType = strtolower(trim($_POST['user_type'] ?? ((($_GET['type'] ?? '') === 'expert') ? 'expert' : 'client')));
if (!in_array($defaultType, ['client', 'expert'], true)) {
    $defaultType = 'client';
}

$normalizeNumberInput = static function ($value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    $value = preg_replace('/[^0-9.]/', '', $value);
    return $value ?? '';
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($formType === 'login') {
        $activeTab = 'login';
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $loginError = 'Please enter your email and password';
        } else {
            $result = loginUser($email, $password);
            if ($result['success']) {
                $user = $result['user'];
                if ($user['user_type'] === 'admin') {
                    header('Location: ' . APP_URL . '/admin/index.php');
                } elseif (in_array($user['user_type'], ['expert', 'both'])) {
                    if (isExpertVerified($user['id'])) {
                        header('Location: ' . APP_URL . '/pages/dashboard-expert.php');
                    } else {
                        header('Location: ' . APP_URL . '/pages/expert-pending.php');
                    }
                } else {
                    header('Location: ' . APP_URL . '/pages/dashboard-client.php');
                }
                exit;
            }
            $loginError = $result['error'] ?? 'Login failed';
        }
    }

    if ($formType === 'register') {
        $activeTab = 'signup';

        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $userType = strtolower(trim($_POST['user_type'] ?? $defaultType));
        if (!in_array($userType, ['client', 'expert'], true)) {
            $userType = 'client';
        }
        $phone = trim($_POST['phone'] ?? '');
        $country = trim($_POST['country'] ?? '');

        $expertData = [
            'qualification' => trim($_POST['qualification'] ?? ''),
            'domain' => trim($_POST['domain'] ?? ''),
            'skills' => trim($_POST['skills'] ?? ''),
            'experience_years' => $normalizeNumberInput($_POST['experience_years'] ?? ''),
            'current_role' => trim($_POST['current_role'] ?? ''),
            'company' => trim($_POST['company'] ?? ''),
            'linkedin_url' => trim($_POST['linkedin_url'] ?? ''),
            'portfolio_url' => trim($_POST['portfolio_url'] ?? ''),
            'headline' => trim($_POST['headline'] ?? ''),
            'rate_per_session' => $normalizeNumberInput($_POST['rate_per_session'] ?? ''),
            'currency' => trim($_POST['currency'] ?? 'USD'),
            'session_duration_minutes' => $normalizeNumberInput($_POST['session_duration_minutes'] ?? ''),
            'max_response_hours' => $normalizeNumberInput($_POST['max_response_hours'] ?? ''),
        ];

        if (empty($fullName) || empty($email) || empty($password)) {
            $registerError = 'Please fill in all required fields';
        } elseif ($password !== $confirmPassword) {
            $registerError = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $registerError = 'Password must be at least 6 characters';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $registerError = 'Invalid email address';
        } elseif (!in_array($userType, ['client', 'expert'])) {
            $registerError = 'Invalid account type selected';
        } elseif ($userType === 'expert') {
            $rate = (float) $expertData['rate_per_session'];
            if ($expertData['qualification'] === '' || $expertData['domain'] === '' || $expertData['skills'] === '') {
                $registerError = 'Please complete all expert details';
            } elseif ($rate <= 0) {
                $registerError = 'Expert rate must be greater than 0';
            }
        }

        if ($registerError === '') {
            $result = registerUser([
                'full_name' => $fullName,
                'email' => $email,
                'password' => $password,
                'user_type' => $userType,
                'phone' => $phone,
                'country' => $country,
                'expert' => $expertData,
            ]);

            if ($result['success']) {
                loginUser($email, $password);
                if ($userType === 'expert') {
                    header('Location: ' . APP_URL . '/pages/expert-pending.php');
                } else {
                    header('Location: ' . APP_URL . '/pages/dashboard-client.php');
                }
                exit;
            }

            $registerError = $result['error'] ?? 'Registration failed';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> — BrainRent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/custom.css" rel="stylesheet">
</head>

<body>
    <main class="d-flex align-items-center justify-content-center py-5" style="min-height:100vh;background:var(--br-dark);">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-4">
                        <a href="<?= APP_URL ?>/pages/index.php" class="text-decoration-none">
                            <div class="d-inline-flex align-items-center gap-2 mb-3">
                                <span class="br-logo-icon">BR</span>
                                <span class="br-brand">Brain<span class="text-warning">Rent</span></span>
                            </div>
                        </a>
                        <h2 class="fw-bold mb-2">Account Access</h2>
                        <p class="text-muted">Login or create your account</p>
                    </div>

                    <div class="br-card p-4">
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <button type="button" class="btn <?= $activeTab === 'login' ? 'br-btn-gold' : 'br-btn-ghost' ?> btn-sm" data-auth-tab="login">Log In</button>
                            <button type="button" class="btn <?= $activeTab === 'signup' ? 'br-btn-gold' : 'br-btn-ghost' ?> btn-sm" data-auth-tab="signup">Create Account</button>
                        </div>

                        <div id="auth-login" data-auth-panel="login" style="display:<?= $activeTab === 'login' ? 'block' : 'none' ?>;">
                            <?php if ($loginError): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($loginError) ?></div>
                            <?php endif; ?>
                            <form method="post">
                                <input type="hidden" name="form_type" value="login">
                                <div class="mb-3">
                                    <label class="br-form-label">Email Address</label>
                                    <input type="text" name="email" class="br-form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                                <div class="mb-4">
                                    <label class="br-form-label">Password</label>
                                    <input type="password" name="password" class="br-form-control" required>
                                </div>
                                <button type="submit" class="btn br-btn-gold w-100 mb-3">
                                    Log In
                                </button>
                                <div class="text-center">
                                    <span class="text-muted">No account yet?</span>
                                    <button type="button" class="btn btn-link text-warning p-0" data-auth-tab="signup">Create one</button>
                                </div>
                            </form>
                        </div>

                        <div id="auth-signup" data-auth-panel="signup" style="display:<?= $activeTab === 'signup' ? 'block' : 'none' ?>;">
                            <?php if ($registerError): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($registerError) ?></div>
                            <?php endif; ?>
                            <form method="post">
                                <input type="hidden" name="form_type" value="register">
                                <div class="mb-3">
                                    <label class="br-form-label">Full Name *</label>
                                    <input type="text" name="full_name" class="br-form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="br-form-label">Email Address *</label>
                                    <input type="email" name="email" class="br-form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="br-form-label">Password *</label>
                                        <input type="password" name="password" class="br-form-control" required minlength="6">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="br-form-label">Confirm Password *</label>
                                        <input type="password" name="confirm_password" class="br-form-control" required minlength="6">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="br-form-label">Account Type *</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <label class="btn btn-sm <?= $defaultType === 'client' ? 'br-btn-gold' : 'br-btn-ghost' ?>">
                                            <input type="radio" name="user_type" value="client" class="form-check-input me-1" <?= $defaultType === 'client' ? 'checked' : '' ?>> User
                                        </label>
                                        <label class="btn btn-sm <?= $defaultType === 'expert' ? 'br-btn-gold' : 'br-btn-ghost' ?>">
                                            <input type="radio" name="user_type" value="expert" class="form-check-input me-1" <?= $defaultType === 'expert' ? 'checked' : '' ?>> Expert
                                        </label>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="br-form-label">Phone (Optional)</label>
                                        <input type="tel" name="phone" class="br-form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="br-form-label">Country (Optional)</label>
                                        <input type="text" name="country" class="br-form-control" value="<?= htmlspecialchars($_POST['country'] ?? '') ?>">
                                    </div>
                                </div>

                                <div id="expert-fields" style="display:<?= $defaultType === 'expert' ? 'block' : 'none' ?>;">
                                    <div class="br-card p-3 mb-3" style="background:var(--br-card2);">
                                        <h6 class="fw-semibold mb-3">Expert Details</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="br-form-label">Qualification *</label>
                                                <input type="text" name="qualification" class="br-form-control" value="<?= htmlspecialchars($_POST['qualification'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="br-form-label">Domain *</label>
                                                <input type="text" name="domain" class="br-form-control" value="<?= htmlspecialchars($_POST['domain'] ?? '') ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="br-form-label">Skills (comma separated) *</label>
                                                <input type="text" name="skills" class="br-form-control" value="<?= htmlspecialchars($_POST['skills'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="br-form-label">Experience (Years)</label>
                                                <input type="number" name="experience_years" class="br-form-control" min="0" value="<?= htmlspecialchars($_POST['experience_years'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="br-form-label">Current Role</label>
                                                <input type="text" name="current_role" class="br-form-control" value="<?= htmlspecialchars($_POST['current_role'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="br-form-label">Company</label>
                                                <input type="text" name="company" class="br-form-control" value="<?= htmlspecialchars($_POST['company'] ?? '') ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="br-form-label">Headline</label>
                                                <input type="text" name="headline" class="br-form-control" value="<?= htmlspecialchars($_POST['headline'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="br-form-label">Rate Per Session *</label>
                                                <input type="number" step="0.01" min="0" name="rate_per_session" class="br-form-control" value="<?= htmlspecialchars($_POST['rate_per_session'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="br-form-label">Currency</label>
                                                <input type="text" name="currency" class="br-form-control" value="<?= htmlspecialchars($_POST['currency'] ?? 'USD') ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="br-form-label">Session Duration (Minutes)</label>
                                                <input type="number" min="1" name="session_duration_minutes" class="br-form-control" value="<?= htmlspecialchars($_POST['session_duration_minutes'] ?? '10') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="br-form-label">Max Response Hours</label>
                                                <input type="number" min="1" name="max_response_hours" class="br-form-control" value="<?= htmlspecialchars($_POST['max_response_hours'] ?? '48') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="br-form-label">LinkedIn URL</label>
                                                <input type="url" name="linkedin_url" class="br-form-control" value="<?= htmlspecialchars($_POST['linkedin_url'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="br-form-label">Portfolio URL</label>
                                                <input type="url" name="portfolio_url" class="br-form-control" value="<?= htmlspecialchars($_POST['portfolio_url'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="text-muted small mt-3">Your expert profile will be reviewed by admin before activation.</div>
                                    </div>
                                </div>

                                <button type="submit" class="btn br-btn-gold w-100 mb-3">
                                    Create Account
                                </button>
                                <div class="text-center">
                                    <span class="text-muted">Already have an account?</span>
                                    <button type="button" class="btn btn-link text-warning p-0" data-auth-tab="login">Log in</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
        const tabButtons = document.querySelectorAll('[data-auth-tab]');
        const panels = document.querySelectorAll('[data-auth-panel]');
        const expertFields = document.getElementById('expert-fields');

        function setActiveTab(tab) {
            panels.forEach(panel => {
                panel.style.display = panel.dataset.authPanel === tab ? 'block' : 'none';
            });
            tabButtons.forEach(btn => {
                const isActive = btn.dataset.authTab === tab;
                btn.classList.toggle('br-btn-gold', isActive);
                btn.classList.toggle('br-btn-ghost', !isActive);
            });
        }

        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => setActiveTab(btn.dataset.authTab));
        });

        const typeInputs = document.querySelectorAll('input[name="user_type"]');

        function updateAccountType() {
            const checked = document.querySelector('input[name="user_type"]:checked');
            const isExpert = checked && checked.value === 'expert';
            if (expertFields) expertFields.style.display = isExpert ? 'block' : 'none';

            typeInputs.forEach(input => {
                const label = input.closest('label');
                if (!label) return;
                label.classList.toggle('br-btn-gold', input.checked);
                label.classList.toggle('br-btn-ghost', !input.checked);
            });
        }

        typeInputs.forEach(input => input.addEventListener('change', updateAccountType));
        updateAccountType();
    </script>
</body>

</html>