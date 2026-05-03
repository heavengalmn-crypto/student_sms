<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controllers/StudentController.php';
require_once __DIR__ . '/models/Student.php';

requireLogin();
refreshSessionPermissions();

if (!hasPermission('can_view')) {
    flashMessage('danger', 'You do not have permission to view students.');
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$controller   = new StudentController();
$studentModel = new Student();
$message      = '';
$messageType  = '';
$editStudent  = null;

// ── HANDLE POST ACTIONS ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $message     = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $result = $controller->store($_POST);
            $message     = $result['message'] ?? ($result['success'] ? 'Done.' : 'Error.');
            $messageType = $result['success'] ? 'success' : 'danger';

        } elseif ($action === 'edit') {
            $id     = (int)($_POST['student_db_id'] ?? 0);
            $result = $controller->update($id, $_POST);
            $message     = $result['message'] ?? ($result['success'] ? 'Done.' : 'Error.');
            $messageType = $result['success'] ? 'success' : 'danger';

        } elseif ($action === 'delete') {
            $id     = (int)($_POST['student_db_id'] ?? 0);
            $result = $controller->delete($id);
            $message     = $result['message'] ?? ($result['success'] ? 'Done.' : 'Error.');
            $messageType = $result['success'] ? 'success' : 'danger';
        }
    }
}

// Load edit data if requested
if (isset($_GET['edit'])) {
    $editStudent = $studentModel->findById((int)$_GET['edit']);
}

$students  = $studentModel->all();
$pageTitle = 'Students';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="page-wrapper">
    <!-- HEADER -->
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="page-title"><i class="bi bi-people me-2"></i>Student Records</h1>
            <p class="page-subtitle">Manage all enrolled students</p>
        </div>
        <?php if (hasPermission('can_add')): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#studentModal" onclick="openAddModal()">
            <i class="bi bi-person-plus me-2"></i>Add Student
        </button>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible alert-auto-dismiss fade show">
            <i class="bi <?= $messageType === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- STUDENT TABLE -->
    <div class="sms-card">
        <div class="sms-card-header">
            <h5><i class="bi bi-table me-2"></i>All Students <span class="ms-2 text-muted" style="font-size:0.85rem;font-weight:400">(<?= count($students) ?>)</span></h5>
            <input type="text" id="student-search" class="search-bar" placeholder="🔍  Search students...">
        </div>
        <div class="table-responsive">
            <?php if (empty($students)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🎓</div>
                    <p>No students found. <?= hasPermission('can_add') ? 'Add your first student above.' : 'Ask an admin to add students.' ?></p>
                </div>
            <?php else: ?>
            <table class="table sms-table mb-0" id="student-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Address</th>
                        <th>Added By</th>
                        <th>Date Added</th>
                        <?php if (hasPermission('can_edit') || hasPermission('can_delete')): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $i => $s): ?>
                    <tr>
                        <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                        <td><code style="color:var(--accent-2)"><?= htmlspecialchars($s['student_id']) ?></code></td>
                        <td><code style="color:var(--accent-2)"><?= htmlspecialchars($s['username'] ?? '—') ?></code></td>
                        <td>
                            <strong><?= htmlspecialchars($s['last_name']) ?></strong>,
                            <?= htmlspecialchars($s['first_name']) ?>
                            <?php if ($s['middle_name']): ?>
                                <span style="color:var(--text-muted)"><?= htmlspecialchars($s['middle_name']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                            title="<?= htmlspecialchars($s['address']) ?>">
                            <?= htmlspecialchars($s['address']) ?>
                        </td>
                        <td style="color:var(--text-muted);font-size:0.82rem"><?= htmlspecialchars($s['created_by_name'] ?? '—') ?></td>
                        <td style="color:var(--text-muted);font-size:0.82rem"><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                        <?php if (hasPermission('can_edit') || hasPermission('can_delete')): ?>
                        <td>
                            <div class="d-flex gap-1">
                                <?php if (hasPermission('can_edit')): ?>
                                <button class="btn btn-warning btn-sm" onclick='openEditModal(<?= json_encode($s) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (hasPermission('can_delete')): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="student_db_id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm btn-delete-confirm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ADD/EDIT STUDENT MODAL -->
<?php if (hasPermission('can_add') || hasPermission('can_edit')): ?>
<div class="modal fade" id="studentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="bi bi-person me-2"></i>Add Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="studentForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="student_db_id" id="studentDbId" value="">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Student ID <span class="text-danger">*</span></label>
                            <input type="text" name="student_id" id="f_student_id" class="form-control"
                                   placeholder="e.g. 2024-0001" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="f_username" class="form-control"
                                   placeholder="e.g. juan.delacruz" required>
                            <small class="form-text text-muted">Min. 3 chars, used for login</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" id="f_first_name" class="form-control"
                                   placeholder="Juan" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" id="f_middle_name" class="form-control"
                                   placeholder="(Optional)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" id="f_last_name" class="form-control"
                                   placeholder="dela Cruz" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="f_email" class="form-control"
                                   placeholder="juan@school.edu" required>
                            <small class="form-text text-muted">For OTP verification</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone (Optional)</label>
                            <input type="tel" name="phone" id="f_phone" class="form-control"
                                   placeholder="+1234567890">
                            <small class="form-text text-muted">With country code, for SMS 2FA</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="f_password" class="form-control"
                                   placeholder="Min. 8 chars, uppercase & number">
                            <small class="form-text text-muted" id="passwordHelpText">Min. 8 chars, needs uppercase & number</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" id="f_address" class="form-control"
                                   placeholder="Street, City, Province" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-floppy me-2"></i>Save Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-person-plus me-2"></i>Add New Student';
    document.getElementById('formAction').value = 'add';
    document.getElementById('studentDbId').value = '';
    document.getElementById('f_student_id').value = '';
    document.getElementById('f_username').value = '';
    document.getElementById('f_first_name').value = '';
    document.getElementById('f_middle_name').value = '';
    document.getElementById('f_last_name').value = '';
    document.getElementById('f_email').value = '';
    document.getElementById('f_phone').value = '';
    const passwordField = document.getElementById('f_password');
    passwordField.value = '';
    passwordField.required = true;
    document.getElementById('f_address').value = '';
    document.getElementById('passwordHelpText').textContent = 'Min. 8 chars, needs uppercase & number';
    document.getElementById('submitBtn').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Add Student';
}

function openEditModal(student) {
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Student';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('studentDbId').value = student.id;
    document.getElementById('f_student_id').value = student.student_id;
    document.getElementById('f_username').value = student.username || '';
    document.getElementById('f_first_name').value = student.first_name;
    document.getElementById('f_middle_name').value = student.middle_name || '';
    document.getElementById('f_last_name').value = student.last_name;
    document.getElementById('f_email').value = student.email || '';
    document.getElementById('f_phone').value = student.phone || '';
    const passwordField = document.getElementById('f_password');
    passwordField.value = '';
    passwordField.required = false;
    document.getElementById('f_address').value = student.address;
    document.getElementById('passwordHelpText').textContent = 'Leave blank to keep existing password.';
    document.getElementById('submitBtn').innerHTML = '<i class="bi bi-floppy me-2"></i>Update Student';
    new bootstrap.Modal(document.getElementById('studentModal')).show();
}

// Auto-open modal if add action requested via URL
<?php if (isset($_GET['action']) && $_GET['action'] === 'add' && hasPermission('can_add')): ?>
document.addEventListener('DOMContentLoaded', () => {
    openAddModal();
    new bootstrap.Modal(document.getElementById('studentModal')).show();
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
