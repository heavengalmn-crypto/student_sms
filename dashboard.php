<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Student.php';
require_once __DIR__ . '/models/User.php';

requireLogin();
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
        <h1 class="page-title">
            Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>
            <?php if ($_SESSION['role'] === 'super_admin'): ?>
                <span class="badge-admin ms-2" style="font-size:0.9rem;vertical-align:middle">Super Admin</span>
            <?php endif; ?>
        </h1>
        <p class="page-subtitle">Here's what's happening in your system today.</p>
    </div>

    <!-- STATS -->
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
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.15)">🔑</div>
                <div class="stat-value" style="color:#fbbf24">
                    <?= $_SESSION['role'] === 'super_admin' ? 'Full' : array_sum(array_filter([
                        $_SESSION['permissions']['can_add']    ?? false,
                        $_SESSION['permissions']['can_edit']   ?? false,
                        $_SESSION['permissions']['can_delete'] ?? false,
                        $_SESSION['permissions']['can_view']   ?? false,
                    ])) ?>
                </div>
                <div class="stat-label">Your Permissions</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(239,68,68,0.12)">🛡️</div>
                <div class="stat-value" style="color:#f87171;font-size:1.2rem;padding-top:0.25rem">
                    <?= ucfirst(str_replace('_', ' ', $_SESSION['role'])) ?>
                </div>
                <div class="stat-label">Your Role</div>
            </div>
        </div>
    </div>

    <!-- PERMISSIONS SUMMARY -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="sms-card">
                <div class="sms-card-header">
                    <h5><i class="bi bi-shield me-2"></i>Your Access Permissions</h5>
                </div>
                <div class="p-4">
                    <div class="row g-2">
                        <?php
                        $perms = [
                            ['can_view',   'View Students',   'bi-eye',       'rgba(99,102,241,0.15)',  '#818cf8'],
                            ['can_add',    'Add Students',    'bi-plus-circle','rgba(16,185,129,0.15)', '#34d399'],
                            ['can_edit',   'Edit Students',   'bi-pencil',    'rgba(245,158,11,0.15)',  '#fbbf24'],
                            ['can_delete', 'Delete Students', 'bi-trash',     'rgba(239,68,68,0.12)',   '#f87171'],
                        ];
                        foreach ($perms as [$key, $label, $icon, $bg, $color]):
                            $has = hasPermission($key);
                        ?>
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2 p-2 rounded"
                                 style="background:<?= $has ? $bg : 'rgba(51,65,85,0.3)' ?>;border:1px solid <?= $has ? $color.'44' : 'var(--border)' ?>">
                                <i class="bi <?= $icon ?>" style="color:<?= $has ? $color : 'var(--text-muted)' ?>;font-size:1.1rem"></i>
                                <span style="font-size:0.82rem;color:<?= $has ? $color : 'var(--text-muted)' ?>;font-weight:500"><?= $label ?></span>
                                <i class="bi <?= $has ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> ms-auto"
                                   style="color:<?= $has ? $color : '#475569' ?>;font-size:0.9rem"></i>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="sms-card h-100">
                <div class="sms-card-header">
                    <h5><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                </div>
                <div class="p-4 d-flex flex-column gap-2">
                    <?php if (hasPermission('can_view')): ?>
                    <a href="<?= APP_URL ?>/students.php" class="btn btn-primary">
                        <i class="bi bi-people me-2"></i>View All Students
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('can_add')): ?>
                    <a href="<?= APP_URL ?>/students.php?action=add" class="btn btn-success">
                        <i class="bi bi-person-plus me-2"></i>Add New Student
                    </a>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'super_admin'): ?>
                    <a href="<?= APP_URL ?>/manage_permissions.php" class="btn btn-warning">
                        <i class="bi bi-shield-check me-2"></i>Manage Permissions
                    </a>
                    <?php endif; ?>
                    <?php if (!hasPermission('can_view') && !hasPermission('can_add')): ?>
                    <div class="empty-state py-3">
                        <div class="empty-icon">🔒</div>
                        <p style="font-size:0.875rem">No permissions assigned yet.<br>Contact your administrator.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
