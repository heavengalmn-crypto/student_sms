<?php
// dashboard.php
 
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Student.php';
require_once __DIR__ . '/models/User.php';
 
requireLogin();
 
// Redirect students to their own dashboard
if (isStudent()) {
    header('Location: ' . APP_URL . '/student_dashboard.php');
    exit;
}
 
refreshSessionPermissions();
 
$studentModel  = new Student();
$userModel     = new User();
$totalStudents = $studentModel->count();
$allUsers      = $userModel->all();
$totalUsers    = count($allUsers);
$recentStudents = array_slice($studentModel->all(), 0, 5);
 
$flash     = getFlash();
$pageTitle = 'Dashboard';
$role      = $_SESSION['role'] ?? 'user';
$username  = $_SESSION['username'] ?? 'User';
 
include __DIR__ . '/includes/header.php';
?>
 
<div class="page-wrapper">
 
    <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
        <div class="alert alert-danger alert-dismissible alert-auto-dismiss fade show">
            <i class="bi bi-shield-exclamation me-2"></i>You don't have permission to access that page.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
 
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible alert-auto-dismiss fade show">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= htmlspecialchars($flash['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
 
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="page-title mb-1">
                Welcome back, <?= htmlspecialchars($username) ?> 👋
            </h1>
            <p class="page-subtitle mb-0">
                <?= date('l, F j, Y') ?> &mdash;
                <span class="badge bg-secondary text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $role)) ?></span>
            </p>
        </div>
        <?php if (hasPermission('can_add') || $role === 'super_admin'): ?>
        <a href="<?= APP_URL ?>/students/create.php" class="btn btn-primary">
            <i class="bi bi-person-plus me-2"></i>Add Student
        </a>
        <?php endif; ?>
    </div>
 
    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(99,102,241,0.15)">
                    <i class="bi bi-mortarboard-fill" style="color:var(--accent-2);font-size:1.5rem"></i>
                </div>
                <div class="stat-value" style="color:var(--accent-2)"><?= number_format($totalStudents) ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.15)">
                    <i class="bi bi-people-fill" style="color:#34d399;font-size:1.5rem"></i>
                </div>
                <div class="stat-value" style="color:#34d399"><?= number_format($totalUsers) ?></div>
                <div class="stat-label">System Users</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(251,191,36,0.15)">
                    <i class="bi bi-shield-check-fill" style="color:#fbbf24;font-size:1.5rem"></i>
                </div>
                <div class="stat-value" style="color:#fbbf24"><?= htmlspecialchars(str_replace('_', ' ', ucfirst($role))) ?></div>
                <div class="stat-label">Your Role</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(236,72,153,0.15)">
                    <i class="bi bi-calendar-check-fill" style="color:#ec4899;font-size:1.5rem"></i>
                </div>
                <div class="stat-value" style="color:#ec4899"><?= date('M j') ?></div>
                <div class="stat-label">Today's Date</div>
            </div>
        </div>
    </div>
 
    <!-- Recent Students Table -->
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Students</h5>
                    <?php if (hasPermission('can_view') || $role === 'super_admin'): ?>
                    <a href="<?= APP_URL ?>/students/index.php" class="btn btn-sm btn-outline-primary">View All</a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentStudents)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox" style="font-size:2.5rem"></i>
                            <p class="mt-2 mb-0">No students yet.</p>
                            <?php if (hasPermission('can_add') || $role === 'super_admin'): ?>
                            <a href="<?= APP_URL ?>/students/create.php" class="btn btn-sm btn-primary mt-3">
                                <i class="bi bi-plus-lg me-1"></i>Add First Student
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <?php if (hasPermission('can_edit') || hasPermission('can_delete') || $role === 'super_admin'): ?>
                                    <th class="text-end">Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentStudents as $s): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($s['student_id']) ?></code></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:.75rem;flex-shrink:0">
                                                <?= strtoupper(substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1)) ?>
                                            </div>
                                            <span><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars($s['email']) ?></td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    </td>
                                    <?php if (hasPermission('can_edit') || hasPermission('can_delete') || $role === 'super_admin'): ?>
                                    <td class="text-end">
                                        <?php if (hasPermission('can_edit') || $role === 'super_admin'): ?>
                                        <a href="<?= APP_URL ?>/students/edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-secondary me-1">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('can_delete') || $role === 'super_admin'): ?>
                                        <a href="<?= APP_URL ?>/students/delete.php?id=<?= $s['id'] ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Delete this student?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
 
        <!-- Quick Actions + User Info -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning-charge me-2 text-warning"></i>Quick Actions</h5>
                </div>
                <div class="card-body d-grid gap-2">
                    <?php if (hasPermission('can_add') || $role === 'super_admin'): ?>
                    <a href="<?= APP_URL ?>/students/create.php" class="btn btn-primary btn-sm text-start">
                        <i class="bi bi-person-plus me-2"></i>Add New Student
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('can_view') || $role === 'super_admin'): ?>
                    <a href="<?= APP_URL ?>/students/index.php" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-people me-2"></i>View All Students
                    </a>
                    <?php endif; ?>
                    <?php if ($role === 'super_admin'): ?>
                    <a href="<?= APP_URL ?>/users/index.php" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-gear me-2"></i>Manage Users
                    </a>
                    <?php endif; ?>
                    <a href="<?= APP_URL ?>/profile.php" class="btn btn-outline-secondary btn-sm text-start">
                        <i class="bi bi-person-circle me-2"></i>My Profile
                    </a>
                    <a href="<?= APP_URL ?>/logout.php" class="btn btn-outline-danger btn-sm text-start">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </div>
            </div>
 
            <!-- Session Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2 text-info"></i>Session Info</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Logged in as</span>
                            <strong><?= htmlspecialchars($username) ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Role</span>
                            <span class="badge bg-primary text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $role)) ?></span>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Can Add</span>
                            <span class="badge bg-<?= hasPermission('can_add') ? 'success' : 'secondary' ?>">
                                <?= hasPermission('can_add') ? 'Yes' : 'No' ?>
                            </span>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Can Edit</span>
                            <span class="badge bg-<?= hasPermission('can_edit') ? 'success' : 'secondary' ?>">
                                <?= hasPermission('can_edit') ? 'Yes' : 'No' ?>
                            </span>
                        </li>
                        <li class="d-flex justify-content-between py-1">
                            <span class="text-muted">Can Delete</span>
                            <span class="badge bg-<?= hasPermission('can_delete') ? 'success' : 'secondary' ?>">
                                <?= hasPermission('can_delete') ? 'Yes' : 'No' ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
 
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-dismiss alerts after 4 seconds
    document.querySelectorAll('.alert-auto-dismiss').forEach(el => {
        setTimeout(() => {
            const alert = bootstrap.Alert.getOrCreateInstance(el);
            alert.close();
        }, 4000);
    });
</script>
</body>
</html>