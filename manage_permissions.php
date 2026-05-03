<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/PermissionController.php';

requireSuperAdmin();
refreshSessionPermissions();

$controller  = new PermissionController();
$message     = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request.';
        $messageType = 'danger';
    } else {
        $userId = (int)($_POST['user_id'] ?? 0);
        $result = $controller->update($userId, $_POST);
        $message     = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';
    }
}

$data      = $controller->index();
$users     = $data['users'];
$pageTitle = 'Manage Permissions';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="mb-4">
        <h1 class="page-title"><i class="bi bi-shield-check me-2"></i>Manage Permissions</h1>
        <p class="page-subtitle">Control what each user can do in the system</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible alert-auto-dismiss fade show">
            <i class="bi <?= $messageType === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Legend -->
    <div class="mb-4 p-3 rounded" style="background:rgba(99,102,241,0.06);border:1px solid rgba(99,102,241,0.15)">
        <p class="mb-0" style="font-size:0.84rem;color:var(--text-muted)">
            <i class="bi bi-info-circle me-2" style="color:var(--accent-2)"></i>
            Super Admin has <strong style="color:var(--accent-2)">full access</strong> by default.
            Permissions below only apply to <strong>User</strong> accounts.
            Changes take effect the next time the user logs in.
        </p>
    </div>

    <div class="row g-3">
        <?php foreach ($users as $user): ?>
        <div class="col-lg-6">
            <div class="sms-card">
                <div class="sms-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                        <div>
                            <div style="font-size:0.95rem;font-weight:600"><?= htmlspecialchars($user['username']) ?></div>
                            <div style="font-size:0.78rem;color:var(--text-muted)"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        <?php if ($user['role'] === 'super_admin'): ?>
                            <span class="badge-admin ms-auto">Super Admin</span>
                        <?php else: ?>
                            <span class="badge-user ms-auto">User</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="p-4">
                    <?php if ($user['role'] === 'super_admin'): ?>
                        <div class="d-flex align-items-center gap-3 p-3 rounded" style="background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.2)">
                            <i class="bi bi-shield-fill-check" style="color:var(--accent-2);font-size:1.5rem"></i>
                            <div>
                                <div style="font-size:0.875rem;font-weight:600;color:var(--accent-2)">Full System Access</div>
                                <div style="font-size:0.78rem;color:var(--text-muted)">Super Admin cannot be restricted</div>
                            </div>
                        </div>
                    <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">

                        <div class="row g-2 mb-3">
                            <?php
                            $permDefs = [
                                ['can_view',   'View Students',    'bi-eye',         'rgba(99,102,241,0.15)',  '#818cf8'],
                                ['can_add',    'Add Students',     'bi-plus-circle', 'rgba(16,185,129,0.15)', '#34d399'],
                                ['can_edit',   'Edit Students',    'bi-pencil',      'rgba(245,158,11,0.15)', '#fbbf24'],
                                ['can_delete', 'Delete Students',  'bi-trash',       'rgba(239,68,68,0.12)',  '#f87171'],
                            ];
                            foreach ($permDefs as [$key, $label, $icon, $bg, $color]):
                                $checked = (bool)($user[$key] ?? 0);
                            ?>
                            <div class="col-6">
                                <label class="perm-card">
                                    <input type="checkbox" class="form-check-input perm-check"
                                           name="<?= $key ?>" <?= $checked ? 'checked' : '' ?>>
                                    <div class="perm-icon" style="background:<?= $bg ?>">
                                        <i class="bi <?= $icon ?>" style="color:<?= $color ?>"></i>
                                    </div>
                                    <span style="font-size:0.82rem;font-weight:500;color:var(--text)"><?= $label ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <label style="font-size:0.8rem;color:var(--text-muted);cursor:pointer">
                                <input type="checkbox" id="all-<?= $user['id'] ?>" class="me-1"
                                    onchange="toggleAll(this, <?= $user['id'] ?>)">
                                Toggle All
                            </label>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-floppy me-1"></i>Save Permissions
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($users)): ?>
        <div class="col-12">
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <p>No users found in the system.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleAll(masterCb, userId) {
    const card = masterCb.closest('form');
    card.querySelectorAll('.perm-check').forEach(cb => cb.checked = masterCb.checked);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
