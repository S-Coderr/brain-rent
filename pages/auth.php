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
$forgotError = '';
$forgotSuccess = '';
$forgotVerified = false;
$formType = $_POST['form_type'] ?? '';

$activeTab = $_GET['tab'] ?? 'login';
if (!in_array($activeTab, ['login', 'signup', 'forgot'])) {
    $activeTab = 'login';
}

$defaultType = strtolower(trim($_POST['user_type'] ?? ((($_GET['type'] ?? '') === 'expert') ? 'expert' : 'client')));
if (!in_array($defaultType, ['client', 'expert'], true)) {
    $defaultType = 'client';
}

$securityQuestions = getSecurityQuestionOptions();
$registerSecurityQuestion = $formType === 'register'
    ? trim((string) ($_POST['security_question'] ?? ''))
    : '';
$forgotSecurityQuestion = $formType === 'forgot'
    ? trim((string) ($_POST['security_question'] ?? ''))
    : '';
$forgotEmailValue = $formType === 'forgot'
    ? trim((string) ($_POST['email'] ?? ''))
    : '';

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
        $securityQuestion = trim((string) ($_POST['security_question'] ?? ''));
        $securityAnswer = trim((string) ($_POST['security_answer'] ?? ''));

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
        } elseif ($securityQuestion === '' || !array_key_exists($securityQuestion, $securityQuestions)) {
            $registerError = 'Please select a security question';
        } elseif ($securityAnswer === '') {
            $registerError = 'Please provide a security answer';
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
                'security_question' => $securityQuestion,
                'security_answer' => $securityAnswer,
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

    if ($formType === 'forgot') {
        $activeTab = 'forgot';

        $email = trim((string) ($_POST['email'] ?? ''));
        $securityQuestion = trim((string) ($_POST['security_question'] ?? ''));
        $securityAnswer = trim((string) ($_POST['security_answer'] ?? ''));
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($email === '' || $securityQuestion === '' || $securityAnswer === '') {
            $forgotError = 'Please complete email, question, and answer';
        } else {
            $check = verifySecurityAnswer($email, $securityQuestion, $securityAnswer);
            if (empty($check['success'])) {
                $forgotError = $check['error'] ?? 'Security answer mismatch';
            } else {
                $forgotVerified = true;

                if ($newPassword === '' || $confirmPassword === '') {
                    $forgotError = 'Please set and confirm your new password';
                } elseif ($newPassword !== $confirmPassword) {
                    $forgotError = 'Passwords do not match';
                } elseif (strlen($newPassword) < 6) {
                    $forgotError = 'Password must be at least 6 characters';
                } else {
                    $reset = resetPasswordWithSecurityAnswer($email, $securityQuestion, $securityAnswer, $newPassword);
                    if (!empty($reset['success'])) {
                        $forgotSuccess = 'Password updated. You can log in now.';
                    } else {
                        $forgotError = $reset['error'] ?? 'Password reset failed';
                    }
                }
            }
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
    <main class="d-flex align-items-center justify-content-center py-0" style="min-height:100vh;background:var(--br-dark);">
        <div class="row g-0 w-100" style="min-height:100vh;">
            <!-- Left Side Branding / Visual -->
            <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5" style="background: linear-gradient(135deg, #1e1e2f 0%, #111827 100%); color: white; position: relative; overflow: hidden;">
                <!-- Decorative background shapes -->
                <div style="position:absolute;top:-20%;right:-20%;width:60%;height:60%;background:radial-gradient(circle, var(--br-gold-dim) 0%, transparent 60%);border-radius:50%;opacity:0.6"></div>
                <div style="position:absolute;bottom:-10%;left:-10%;width:50%;height:50%;background:radial-gradient(circle, rgba(47, 126, 234, 0.15) 0%, transparent 60%);border-radius:50%;opacity:0.6"></div>

                <div class="position-relative z-index-1">
                    <a href="<?= APP_URL ?>/pages/index.php" class="text-decoration-none">
                        <div class="d-inline-flex align-items-center gap-2 mb-3">
                            <span class="br-logo-icon bg-white text-dark border-0">BR</span>
                            <span class="br-brand text-white fs-4">Brain<span class="text-warning">Rent</span></span>
                        </div>
                    </a>
                </div>

                <div class="position-relative z-index-1 mt-auto mb-auto">
                    <h1 class="display-4 fw-bold mb-4" style="font-family:'Playfair Display',serif; line-height:1.2;">
                        Solve problems.<br>
                        Share knowledge.<br>
                        <span class="text-warning">Earn rewards.</span>
                    </h1>
                    <p class="text-white-50 fs-5 mb-5" style="max-width: 400px;">
                        Join the premier marketplace connecting clients with verified domain experts for rapid, high-quality solutions.
                    </p>

                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex">
                            <div class="rounded-circle bg-secondary border border-2 border-dark" style="width:40px;height:40px;margin-left:-0px;background-image:url('https://i.pravatar.cc/100?img=1');background-size:cover;"></div>
                            <div class="rounded-circle bg-secondary border border-2 border-dark" style="width:40px;height:40px;margin-left:-15px;background-image:url('https://i.pravatar.cc/100?img=2');background-size:cover;"></div>
                            <div class="rounded-circle bg-secondary border border-2 border-dark" style="width:40px;height:40px;margin-left:-15px;background-image:url('https://i.pravatar.cc/100?img=3');background-size:cover;"></div>
                            <div class="rounded-circle bg-dark border border-2 border-dark d-flex align-items-center justify-content-center text-white small fw-bold" style="width:40px;height:40px;margin-left:-15px;">+5k</div>
                        </div>
                        <div class="small text-white-50">Experts already joined</div>
                    </div>
                </div>

                <div class="position-relative z-index-1 small text-white-50">
                    &copy; <?= date('Y') ?> BrainRent Platform. All rights reserved.
                </div>
            </div>

            <!-- Right Side Auth Forms -->
            <div class="col-lg-7 d-flex align-items-center justify-content-center p-4 p-md-5 bg-white">
                <div class="w-100" style="max-width: 500px;">

                    <!-- Mobile logo (hidden on desktop) -->
                    <div class="text-center mb-4 d-lg-none">
                        <a href="<?= APP_URL ?>/pages/index.php" class="text-decoration-none">
                            <div class="d-inline-flex align-items-center gap-2 mb-3">
                                <span class="br-logo-icon">BR</span>
                                <span class="br-brand">Brain<span class="text-warning">Rent</span></span>
                            </div>
                        </a>
                    </div>

                    <div class="text-center text-lg-start mb-4">
                        <h2 class="fw-bold mb-2">Welcome Back</h2>
                        <p class="text-muted">Access your account or create a new one.</p>
                    </div>

                    <!-- Auth Tabs -->
                    <div class="d-flex flex-wrap gap-2 mb-4 p-1 rounded-3" style="background: var(--br-dark3);">
                        <button type="button" class="btn flex-fill <?= $activeTab === 'login' ? 'br-btn-gold' : 'br-btn-ghost border-0' ?> btn-sm py-2 rounded-2" data-auth-tab="login" data-auth-tab-style="pill">Log In</button>
                        <button type="button" class="btn flex-fill <?= $activeTab === 'signup' ? 'br-btn-gold' : 'br-btn-ghost border-0' ?> btn-sm py-2 rounded-2" data-auth-tab="signup" data-auth-tab-style="pill">Sign Up</button>
                    </div>

                    <!-- LOGIN PANEL -->
                    <div id="auth-login" data-auth-panel="login" style="display:<?= $activeTab === 'login' ? 'block' : 'none' ?>;">
                        <?php if ($loginError): ?>
                            <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 border-0 bg-danger text-white bg-opacity-75"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($loginError) ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="form_type" value="login">
                            <div class="mb-4">
                                <label class="br-form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent border-end-0 border-br2 text-muted"><i class="bi bi-envelope"></i></span>
                                    <input type="text" name="email" class="br-form-control border-start-0 ps-0" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="name@example.com">
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="br-form-label mb-0">Password</label>
                                    <button type="button" class="btn btn-link text-warning p-0 text-decoration-none small" data-auth-tab="forgot">Forgot?</button>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent border-end-0 border-br2 text-muted"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" class="br-form-control border-start-0 ps-0" required placeholder="••••••••">
                                </div>
                            </div>
                            <button type="submit" class="btn br-btn-gold w-100 mb-4 py-2 fs-6">
                                Sign In <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                            <div class="text-center">
                                <span class="text-muted">Don't have an account?</span>
                                <button type="button" class="btn btn-link text-warning p-0 text-decoration-none fw-semibold ms-1" data-auth-tab="signup">Create one now</button>
                            </div>
                        </form>
                    </div>

                    <!-- FORGOT PASSWORD PANEL -->
                    <div id="auth-forgot" data-auth-panel="forgot" style="display:<?= $activeTab === 'forgot' ? 'block' : 'none' ?>;">
                        <div class="mb-4 text-center">
                            <div class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle mb-3" style="width:60px;height:60px;font-size:24px;">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <h4 class="fw-bold">Reset Password</h4>
                            <p class="text-muted small">Verify your identity to set a new password.</p>
                        </div>

                        <?php if ($forgotError): ?>
                            <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 border-0 bg-danger text-white bg-opacity-75"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($forgotError) ?></div>
                        <?php endif; ?>
                        <?php if ($forgotSuccess): ?>
                            <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 border-0 bg-success text-white bg-opacity-75"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($forgotSuccess) ?></div>
                        <?php endif; ?>

                        <form method="post" id="forgot-form" data-forgot-verified="<?= $forgotVerified ? '1' : '0' ?>">
                            <input type="hidden" name="form_type" value="forgot">
                            <div class="mb-3">
                                <label class="br-form-label">Email Address</label>
                                <input type="email" name="email" id="forgot-email" class="br-form-control" required value="<?= htmlspecialchars($forgotEmailValue) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="br-form-label">Security Question</label>
                                <select name="security_question" id="forgot-question" class="br-form-control" required>
                                    <option value="">Select your question</option>
                                    <?php foreach ($securityQuestions as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= $forgotSecurityQuestion === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="br-form-label">Security Answer</label>
                                <input type="text" name="security_answer" id="forgot-answer" class="br-form-control" required>
                            </div>
                            <button type="button" class="btn br-btn-ghost w-100 mb-2 py-2" id="forgot-verify-btn">
                                Verify Answer
                            </button>
                            <div id="forgot-status" class="small text-muted mb-3 text-center"></div>
                            
                            <hr class="text-muted my-4">

                            <div class="row mb-3">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="br-form-label">New Password</label>
                                    <input type="password" name="new_password" id="forgot-new-password" class="br-form-control" minlength="6" required <?= $forgotVerified ? '' : 'disabled' ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="br-form-label">Confirm New</label>
                                    <input type="password" name="confirm_password" id="forgot-confirm-password" class="br-form-control" minlength="6" required <?= $forgotVerified ? '' : 'disabled' ?>>
                                </div>
                            </div>
                            <button type="submit" class="btn br-btn-gold w-100 mb-3 py-2">Reset Password</button>
                            <div class="text-center">
                                <button type="button" class="btn btn-link text-warning p-0 text-decoration-none small" data-auth-tab="login"><i class="bi bi-arrow-left me-1"></i> Back to login</button>
                            </div>
                        </form>
                    </div>

                    <!-- SIGNUP PANEL -->
                    <div id="auth-signup" data-auth-panel="signup" style="display:<?= $activeTab === 'signup' ? 'block' : 'none' ?>;">
                        <?php if ($registerError): ?>
                            <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 border-0 bg-danger text-white bg-opacity-75"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($registerError) ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="form_type" value="register">
                            
                            <!-- Account Type Switcher -->
                            <div class="mb-4">
                                <label class="br-form-label mb-2">I want to join as a...</label>
                                <div class="d-flex gap-3">
                                    <label class="position-relative w-50">
                                        <input type="radio" name="user_type" value="client" class="btn-check" <?= $defaultType === 'client' ? 'checked' : '' ?>>
                                        <div class="br-card p-3 text-center h-100 transition-all cursor-pointer type-selector border-2">
                                            <i class="bi bi-person fs-3 text-muted mb-2 d-block"></i>
                                            <span class="fw-semibold d-block">Client</span>
                                            <small class="text-muted d-block mt-1" style="font-size:0.7rem">I need answers</small>
                                        </div>
                                    </label>
                                    <label class="position-relative w-50">
                                        <input type="radio" name="user_type" value="expert" class="btn-check" <?= $defaultType === 'expert' ? 'checked' : '' ?>>
                                        <div class="br-card p-3 text-center h-100 transition-all cursor-pointer type-selector border-2">
                                            <i class="bi bi-briefcase fs-3 text-muted mb-2 d-block"></i>
                                            <span class="fw-semibold d-block">Expert</span>
                                            <small class="text-muted d-block mt-1" style="font-size:0.7rem">I provide solutions</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <style>
                                .btn-check:checked + .type-selector {
                                    border-color: var(--br-gold) !important;
                                    background: var(--br-gold-dim) !important;
                                }
                                .btn-check:checked + .type-selector i, .btn-check:checked + .type-selector span {
                                    color: var(--br-gold) !important;
                                }
                            </style>

                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label class="br-form-label">Full Name *</label>
                                    <input type="text" name="full_name" class="br-form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" placeholder="John Doe">
                                </div>
                                <div class="col-sm-6">
                                    <label class="br-form-label">Email Address *</label>
                                    <input type="email" name="email" class="br-form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="name@example.com">
                                </div>
                                <div class="col-sm-6">
                                    <label class="br-form-label">Password *</label>
                                    <input type="password" name="password" class="br-form-control" required minlength="6" placeholder="Min 6 chars">
                                </div>
                                <div class="col-sm-6">
                                    <label class="br-form-label">Confirm Password *</label>
                                    <input type="password" name="confirm_password" class="br-form-control" required minlength="6">
                                </div>
                            </div>

                            <hr class="text-muted my-4">
                            <h6 class="fw-semibold mb-3 fs-6">Account Security</h6>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="br-form-label">Security Question *</label>
                                    <select name="security_question" class="br-form-control" required>
                                        <option value="">Select a question for account recovery</option>
                                        <?php foreach ($securityQuestions as $value => $label): ?>
                                            <option value="<?= htmlspecialchars($value) ?>" <?= $registerSecurityQuestion === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="br-form-label">Security Answer *</label>
                                    <input type="text" name="security_answer" class="br-form-control" required value="<?= htmlspecialchars($formType === 'register' ? ($_POST['security_answer'] ?? '') : '') ?>">
                                </div>
                            </div>

                            <!-- Expert Fields (Dynamically Shown) -->
                            <div id="expert-fields" style="display:<?= $defaultType === 'expert' ? 'block' : 'none' ?>;" class="bg-light p-4 rounded-3 border mb-4">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center" style="width:30px;height:30px;"><i class="bi bi-star-fill small"></i></div>
                                    <h6 class="fw-semibold mb-0">Expert Profile Setup</h6>
                                </div>
                                <p class="small text-muted mb-4">Tell us about your expertise. Admins will review your profile before activating your account.</p>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="br-form-label">Primary Domain *</label>
                                        <input type="text" name="domain" class="br-form-control" value="<?= htmlspecialchars($_POST['domain'] ?? '') ?>" placeholder="e.g. Software Eng.">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="br-form-label">Highest Qual. *</label>
                                        <input type="text" name="qualification" class="br-form-control" value="<?= htmlspecialchars($_POST['qualification'] ?? '') ?>" placeholder="e.g. MS CompSci">
                                    </div>
                                    <div class="col-12">
                                        <label class="br-form-label">Skills (comma separated) *</label>
                                        <input type="text" name="skills" class="br-form-control" value="<?= htmlspecialchars($_POST['skills'] ?? '') ?>" placeholder="e.g. React, Node, PHP">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="br-form-label">Rate Per Session (USD) *</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent border-end-0 border-br2 text-muted">$</span>
                                            <input type="number" step="0.01" min="0" name="rate_per_session" class="br-form-control border-start-0 ps-0" value="<?= htmlspecialchars($_POST['rate_per_session'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="br-form-label">Exp (Years)</label>
                                        <input type="number" name="experience_years" class="br-form-control" min="0" value="<?= htmlspecialchars($_POST['experience_years'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn br-btn-gold w-100 py-2 fs-6 mb-3">Create Account</button>
                            <p class="text-center small text-muted">
                                By signing up, you agree to our Terms of Service and Privacy Policy.
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const tabButtons = document.querySelectorAll('[data-auth-tab]');
        const pillButtons = document.querySelectorAll('[data-auth-tab-style="pill"]');
        const panels = document.querySelectorAll('[data-auth-panel]');
        const expertFields = document.getElementById('expert-fields');

        function setActiveTab(tab) {
            panels.forEach(panel => {
                panel.style.display = panel.dataset.authPanel === tab ? 'block' : 'none';
            });
            pillButtons.forEach(btn => {
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

        const forgotForm = document.getElementById('forgot-form');
        if (forgotForm) {
            const emailInput = document.getElementById('forgot-email');
            const questionSelect = document.getElementById('forgot-question');
            const answerInput = document.getElementById('forgot-answer');
            const verifyBtn = document.getElementById('forgot-verify-btn');
            const statusEl = document.getElementById('forgot-status');
            const newPasswordInput = document.getElementById('forgot-new-password');
            const confirmPasswordInput = document.getElementById('forgot-confirm-password');
            const verifyUrl = "<?= APP_URL ?>/api/verify_security_answer.php";

            const setResetEnabled = (enabled) => {
                if (newPasswordInput) newPasswordInput.disabled = !enabled;
                if (confirmPasswordInput) confirmPasswordInput.disabled = !enabled;
            };

            const setStatus = (msg, isError = false) => {
                if (!statusEl) return;
                statusEl.textContent = msg;
                statusEl.classList.toggle('text-danger', isError);
                statusEl.classList.toggle('text-success', !isError && msg !== '');
                statusEl.classList.toggle('text-muted', msg === '');
            };

            setResetEnabled(forgotForm.dataset.forgotVerified === '1');

            [emailInput, questionSelect, answerInput].forEach(el => {
                if (!el) return;
                el.addEventListener('input', () => {
                    setResetEnabled(false);
                    setStatus('');
                });
            });

            if (verifyBtn) {
                verifyBtn.addEventListener('click', async () => {
                    const email = emailInput ? emailInput.value.trim() : '';
                    const question = questionSelect ? questionSelect.value.trim() : '';
                    const answer = answerInput ? answerInput.value.trim() : '';

                    if (!email || !question || !answer) {
                        setStatus('Please fill email, question, and answer first.', true);
                        setResetEnabled(false);
                        return;
                    }

                    verifyBtn.disabled = true;
                    setStatus('Verifying answer...');

                    try {
                        const body = new URLSearchParams({
                            email,
                            security_question: question,
                            security_answer: answer,
                        });
                        const res = await fetch(verifyUrl, {
                            method: 'POST',
                            body
                        });
                        const data = await res.json();

                        if (data && data.success) {
                            setResetEnabled(true);
                            setStatus('Answer verified. Set a new password.');
                            if (newPasswordInput) newPasswordInput.focus();
                        } else {
                            setResetEnabled(false);
                            setStatus(data.error || 'Verification failed.', true);
                        }
                    } catch (err) {
                        setResetEnabled(false);
                        setStatus('Unable to verify right now. Try again.', true);
                    } finally {
                        verifyBtn.disabled = false;
                    }
                });
            }
        }
    </script>
</body>

</html>