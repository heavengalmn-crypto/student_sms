<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/User.php';

requireLogin();

if ($_SESSION['role'] !== 'student') {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$userModel = new User();
$profile = $userModel->findById($_SESSION['user_id']);

$pageTitle = 'Student Dashboard';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="mb-5">
        <h1 class="page-title">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h1>
        <p class="page-subtitle">Student Portal</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="sms-card">
                <div class="sms-card-header">
                    <h5><i class="bi bi-person-circle me-2"></i>My Profile</h5>
                </div>
                <div class="p-4">
                    <p><strong>Username:</strong> <?= htmlspecialchars($profile['username'] ?? 'N/A') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($profile['email'] ?? 'N/A') ?></p>
                    <p><strong>Role:</strong> Student</p>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="sms-card">
                <div class="sms-card-header">
                    <h5><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                </div>
                <div class="p-4">
                    <a href="<?= APP_URL ?>/students.php" class="btn btn-primary">View My Records</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>