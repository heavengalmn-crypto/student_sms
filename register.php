<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/AuthController.php';

if (isLoggedIn()) { header('Location: ' . APP_URL . '/dashboard.php'); exit; }

$errors  = [];
$success = '';
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid form submission. Please try again.';
    } else {
        $controller = new AuthController();
        $result     = $controller->register($_POST);
        if ($result['success']) {
            $success = $result['message'];
            $old     = [];
        } else {
            $errors = $result['errors'] ?? ['general' => $result['message'] ?? 'Registration failed.'];
            $old    = $_POST;
        }
    }
}

$pageTitle = 'Register';
include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon">🎓</div>
            <h1><?= APP_NAME ?></h1>
            <p>Create your account</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible alert-auto-dismiss">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <a href="<?= APP_URL ?>/login.php" class="alert-link ms-2">Login now →</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate onsubmit="showLoading()">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                           placeholder="e.g. jdoe123"
                           value="<?= htmlspecialchars($old['username'] ?? '') ?>" required>
                    <?php if (isset($errors['username'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['username']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           placeholder="you@example.com"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                    <input type="tel" name="phone" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                           placeholder="09XXXXXXXXX"
                           value="<?= htmlspecialchars($old['phone'] ?? '') ?>" required>
                    <?php if (isset($errors['phone'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['phone']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password"
                           class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           placeholder="Min. 8 chars, uppercase & number" required>
                    <button type="button" class="input-group-text toggle-pwd" data-target="password">
                        <i class="bi bi-eye"></i>
                    </button>
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="mt-2">
                    <div style="height:4px;background:var(--border);border-radius:4px;overflow:hidden">
                        <div id="pwd-strength" style="height:100%;width:0;transition:all 0.3s;border-radius:4px"></div>
                    </div>
                    <span id="pwd-label" class="form-text" style="font-size:0.75rem"></span>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="confirm_password" id="confirm_password"
                           class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                           placeholder="Re-enter password" required>
                    <button type="button" class="input-group-text toggle-pwd" data-target="confirm_password">
                        <i class="bi bi-eye"></i>
                    </button>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-person-plus me-2"></i>Create Account
            </button>
        </form>

        <div class="divider-text">Already have an account?</div>
        <a href="<?= APP_URL ?>/login.php" class="btn btn-outline-secondary w-100">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </a>
    </div>
</div>

<div class="spinner-overlay">
    <div class="text-center">
        <div class="spinner-border text-light mb-3" style="width:3rem;height:3rem"></div>
        <p class="text-muted">Creating your account...</p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
