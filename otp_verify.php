<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/AuthController.php';

session_start();

// Must have started login
if (empty($_SESSION['pre_auth_user_id'])) {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

if (isLoggedIn()) {
    $home = ($_SESSION['role'] ?? '') === 'student'
        ? APP_URL . '/student_dashboard.php'
        : APP_URL . '/dashboard.php';

    header('Location: ' . $home);
    exit;
}

$error = '';
$success = '';
$controller = new AuthController();

// Resend OTP
if (isset($_GET['resend']) && $_GET['resend'] === '1') {
    $res = $controller->resendOtp();
    $success = $res['message'] ?? 'OTP resent.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Refresh and try again.';
    } else {

        // 🔥 FIX: accept BOTH formats safely
        $_POST['otp'] = trim(
            $_POST['otp'] ??
            $_POST['otp_code'] ??
            ''
        );

        $result = $controller->verifyOtp($_POST);

        if ($result['success']) {

            if (isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
                header('Location: ' . APP_URL . '/student_dashboard.php');
            } else {
                header('Location: ' . APP_URL . '/dashboard.php');
            }
            exit;

        } else {
            $error = $result['message'] ?? 'Invalid OTP.';
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
            <p>Enter the 6-digit OTP sent to your email & phone</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-auto-dismiss">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="text-center mb-1">
            <p style="font-size:0.875rem;">
                Signed in as <strong>
                <?= htmlspecialchars($_SESSION['pre_auth_username'] ?? '') ?>
                </strong>
            </p>
        </div>

        <form method="POST" id="otp-form">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <!-- KEEP UI INPUTS -->
            <div class="otp-inputs">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric">
                <?php endfor; ?>
            </div>

            <!-- FIXED SINGLE FIELD -->
            <input type="hidden" name="otp" id="otp">

            <button type="submit" class="btn btn-primary w-100">
                Verify OTP
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="?resend=1">Resend OTP</a>
        </div>

    </div>
</div>

<script>
document.getElementById('otp-form').addEventListener('submit', function (e) {
    const boxes = document.querySelectorAll('.otp-box');

    const code = Array.from(boxes)
        .map(b => b.value.trim())
        .join('');

    document.getElementById('otp').value = code;

    if (code.length !== 6) {
        e.preventDefault();
        alert("Enter 6-digit OTP");
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
