<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/AuthController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error   = '';
$success = '';
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF Token. Please refresh the page.';
    } else {

        $controller = new AuthController();
        $result     = $controller->login($_POST);

        if (!empty($result['success'])) {

            // ✅ FIX: store user id for OTP step
            $_SESSION['pending_user_id'] = $result['user']['id'];

            header('Location: ' . APP_URL . '/otp_verify.php');
            exit;

        } else {
            $error = $result['message'] ?? 'Login failed. Please check your credentials.';
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">

        <div class="auth-logo">
            <div class="logo-icon">🎓</div>
            <h1><?= APP_NAME ?></h1>
            <p>Sign in to your account</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-auto-dismiss">
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate onsubmit="showLoading()">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control"
                        placeholder="Enter your username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        autocomplete="username" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control"
                        placeholder="Enter your password"
                        autocomplete="current-password" required>
                    <button type="button" class="input-group-text toggle-pwd" data-target="password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3 text-end">
                <a href="<?= APP_URL ?>/forgot_password.php" class="text-decoration-none small">
                    Forgot Password?
                </a>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="divider-text">Don't have an account?</div>

        <a href="<?= APP_URL ?>/register.php" class="btn btn-outline-secondary w-100">
            <i class="bi bi-person-plus me-2"></i>Create Account
        </a>

    </div>
</div>

<div class="spinner-overlay">
    <div class="text-center">
        <div class="spinner-border text-light mb-3" style="width:3rem;height:3rem"></div>
        <p class="text-muted">Verifying credentials...</p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>