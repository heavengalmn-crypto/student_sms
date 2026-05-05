<?php
// verify_otp.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/AuthController.php';

session_start();

if (empty($_SESSION['pre_auth_user_id'])) {
  header('Location: ' . ($result['redirect'] ?? APP_URL . '/dashboard.php'));
    exit;
}

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error = '';
$success = '';
$controller = new AuthController();

if (isset($_GET['resend']) && $_GET['resend'] === '1') {
    $res = $controller->resendOtp();
    if ($res['success']) {
        $success = $res['message'];
    } else {
        $error = $res['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['otp']) || empty($_POST['otp'])) {
        $error = 'OTP is required.';
    } elseif (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please refresh and try again.';
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

// Render the OTP verification form
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon">🔐</div>
            <h1>Two-Factor Authentication</h1>
            <p>Enter the 6-digit OTP sent to your email</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="text-center mb-1">
            <p style="font-size:0.875rem;color:var(--text-muted)">
                Signed in as <strong><?= htmlspecialchars($_SESSION['pre_auth_username'] ?? '') ?></strong>
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

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-shield-check me-2"></i>Verify OTP
            </button>
        </form>

        <div class="text-center mt-3">
            <span style="font-size:0.85rem;color:var(--text-muted)">Didn't receive it?</span>
            <a href="?resend=1" class="ms-2">Resend OTP</a>
        </div>

        <div class="divider-text">Wrong account?</div>
        <a href="<?= APP_URL ?>/login.php" class="btn btn-outline-secondary w-100">Back to Login</a>

        <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
        <div class="mt-3 p-3 rounded" style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2)">
            <p class="mb-0" style="font-size:0.78rem;color:#fbbf24">
                <i class="bi bi-info-circle me-1"></i>
                DEBUG MODE: OTP is also written to PHP error log and optionally displayed below.
                <?php if (!empty($_SESSION['debug_otp'])): ?>
                <br>Your OTP: <strong><?= $_SESSION['debug_otp'] ?></strong>
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('otp-form').addEventListener('submit', function(e) {
    const boxes = document.querySelectorAll('.otp-box');
    const code = Array.from(boxes).map(b => b.value.trim()).join('');
    document.getElementById('otp_code').value = code;

    if (code.length !== 6) {
        e.preventDefault();
        alert('Please enter all 6 digits.');
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>