<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Student.php';
require_once __DIR__ . '/models/User.php';

requireLogin();

// === TEMPORARILY COMMENTED OUT ===
/*
if (isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
    header('Location: ' . APP_URL . '/student_dashboard.php');
    exit;
}
*/

refreshSessionPermissions();

$studentModel = new Student();
$userModel    = new User();

$totalStudents = $studentModel->count();
$allUsers      = $userModel->all();
$totalUsers    = count($allUsers);

$flash = getFlash();
$pageTitle = 'Dashboard';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="page-wrapper">
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible alert-auto-dismiss fade show">
            <?= htmlspecialchars($flash['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="mb-4">
        <h1 class="page-title">Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></h1>
        <p class="page-subtitle">Here's what's happening in your system today.</p>
    </div>

    <!-- Your original dashboard content starts here -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(99,102,241,0.15)">🎓</div>
                <div class="stat-value" style="color:var(--accent-2)"><?= $totalStudents ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.15)">👥</div>
                <div class="stat-value" style="color:#34d399"><?= $totalUsers ?></div>
                <div class="stat-label">System Users</div>
            </div>
        </div>
        <!-- ... paste the rest of your original cards and sections here ... -->
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>