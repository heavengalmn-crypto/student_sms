<?php // includes/navbar.php ?>
<nav class="navbar navbar-expand-lg sms-navbar">
    <div class="container-fluid px-4">
        <?php $homeLink = ($_SESSION['role'] ?? '') === 'student' ? APP_URL . '/students.php' : APP_URL . '/dashboard.php'; ?>
        <a class="navbar-brand" href="<?= $homeLink ?>">
            <i class="bi bi-mortarboard-fill me-2"></i>
            <span>SMS</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/dashboard.php">
                        <i class="bi bi-grid-1x2 me-1"></i> Dashboard
                    </a>
                </li>
                <?php if (hasPermission('can_view')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'students.php' ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/students.php">
                        <i class="bi bi-people me-1"></i> Students
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($_SESSION['role'] === 'super_admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'manage_permissions.php' ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/manage_permissions.php">
                        <i class="bi bi-shield-check me-1"></i> Permissions
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
                        <?php if ($_SESSION['role'] === 'super_admin'): ?>
                            <span class="badge-role">Admin</span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
