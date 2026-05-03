<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Must have started login
if (empty($_SESSION['pre_auth_user_id'])) {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}
if (isLoggedIn()) {
    $home = ($_SESSION['role'] ?? '') === 'student'
        ? APP_URL . '/students.php'
        : APP_URL . '/dashboard.php';
    header('Location: ' . $home);
    exit;
}

$error   = '';
$success = '';
$controller = new AuthController();

// Resend OTP
if (isset($_GET['resend']) && $_GET['resend'] === '1') {
    $res = $controller->resendOtp();
    $success = $res['message'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Refresh and try again.';
    } else {
        $result = $controller->verifyOtp($_POST);
        if ($result['success']) {
            header('Location: ' . ($result['redirect'] ?? APP_URL . '/dashboard.php'));
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'OTP Verification';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon">🔐</div>
            <h1>Two-Factor Authentication</h1>
            <p>Enter the 6-digit OTP sent to your email &amp; phone</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-auto-dismiss">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="text-center mb-1">
            <p style="font-size:0.875rem;color:var(--text-muted)">
                Signed in as <strong style="color:var(--accent-2)"><?= htmlspecialchars($_SESSION['pre_auth_username'] ?? '') ?></strong>
            </p>
        </div>

        <form method="POST" id="otp-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="otp_code" id="otp_code">

            <div class="otp-inputs">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <input type="text" class="otp-box" name="otp_digit[]" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                <?php endfor; ?>
            </div>

            <div class="text-center mb-3">
                <span style="font-size:0.78rem;color:var(--text-muted)">
                    <i class="bi bi-clock me-1"></i>OTP expires in <?= OTP_EXPIRY_MINUTES ?> minutes
                </span>
            </div>

            <button type="submit" class="btn btn-primary w-100" onclick="showLoading()">
                <i class="bi bi-shield-check me-2"></i>Verify OTP
            </button>
        </form>

        <div class="text-center mt-3">
            <span style="font-size:0.85rem;color:var(--text-muted)">Didn't receive it?</span>
            <a href="?resend=1" class="ms-2" style="font-size:0.85rem;color:var(--accent-2)">Resend OTP</a>
        </div>

        <div class="divider-text">Wrong account?</div>
        <a href="<?= APP_URL ?>/login.php" class="btn btn-outline-secondary w-100">
            <i class="bi bi-arrow-left me-2"></i>Back to Login
        </a>


    </div>
</div>

<div class="spinner-overlay">
    <div class="text-center">
        <div class="spinner-border text-light mb-3" style="width:3rem;height:3rem"></div>
        <p class="text-muted">Verifying OTP...</p>
    </div>
</div>

<script>
document.getElementById('otp-form').addEventListener('submit', function(e) {
    const boxes = document.querySelectorAll('.otp-box');
    const code = Array.from(boxes).map(b => b.value.trim()).join('');
    document.getElementById('otp_code').value = code;

    if (code.length !== 6) {
        e.preventDefault();
        boxes.forEach(b => b.style.borderColor = 'var(--danger)');
        setTimeout(() => boxes.forEach(b => b.style.borderColor = ''), 1500);
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
