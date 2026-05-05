<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/AuthController.php'; // We'll extend if needed

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add logic here: find user by email/username, generate reset token, send email
    $email = trim($_POST['email'] ?? '');
    if (!empty($email)) {
        // For now, placeholder
        $success = "If an account exists with that email, a reset link has been sent.";
        // TODO: Implement full reset flow (token table, email with link)
    } else {
        $error = "Please enter your email.";
    }
}

$pageTitle = 'Forgot Password';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h2>Forgot Password</h2>
        <p class="text-muted">Enter your email to receive a reset link.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
        </form>

        <div class="mt-3 text-center">
            <a href="<?= APP_URL ?>/login.php">Back to Login</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>