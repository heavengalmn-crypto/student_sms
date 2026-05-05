<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// If user is already logged in, redirect to their dashboard
if (isLoggedIn()) {
    $home = ($_SESSION['role'] ?? '') === 'student'
        ? APP_URL . '/students.php'
        : APP_URL . '/dashboard.php';
    header('Location: ' . $home);
    exit;
}

$pageTitle = 'Student Management System';
include __DIR__ . '/includes/header.php';
?>

<div class="hero-section">
    <div class="hero-content">
        <div class="hero-logo">
            <div class="logo-icon">🎓</div>
            <h1>Student Management System</h1>
            <p class="hero-subtitle">Secure, efficient, and user-friendly student data management</p>
        </div>

        <div class="hero-features">
            <div class="feature-card">
                <div class="feature-icon">🔐</div>
                <h3>Secure Authentication</h3>
                <p>Two-factor authentication with email and SMS OTP verification</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3>Role-Based Access</h3>
                <p>Super admin, user, and student roles with granular permissions</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Data Management</h3>
                <p>Complete CRUD operations for student records and user management</p>
            </div>
        </div>

        <div class="hero-actions">
            <a href="<?= APP_URL ?>/login.php" class="btn btn-primary btn-lg">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </a>
            <a href="<?= APP_URL ?>/register.php" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-person-plus me-2"></i>Register
            </a>
        </div>
    </div>
</div>

<div class="info-section">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h2>Why Choose Our SMS?</h2>
                <ul class="feature-list">
                    <li><i class="bi bi-check-circle text-success"></i> Secure OTP-based login system</li>
                    <li><i class="bi bi-check-circle text-success"></i> Role-based permissions and access control</li>
                    <li><i class="bi bi-check-circle text-success"></i> Responsive design for all devices</li>
                    <li><i class="bi bi-check-circle text-success"></i> Email and SMS notifications</li>
                    <li><i class="bi bi-check-circle text-success"></i> Comprehensive student data management</li>
                    <li><i class="bi bi-check-circle text-success"></i> Modern dark theme interface</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h2>Quick Start</h2>
                <div class="quick-start-steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Register an Account</h4>
                            <p>Create your account with email verification</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Login with OTP</h4>
                            <p>Secure login with two-factor authentication</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Manage Students</h4>
                            <p>Add, edit, and manage student records</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hero-section {
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent-1) 100%);
    color: white;
    padding: 4rem 0;
    min-height: 70vh;
    display: flex;
    align-items: center;
}

.hero-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    text-align: center;
}

.hero-logo .logo-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.hero-logo h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: white;
}

.hero-subtitle {
    font-size: 1.25rem;
    opacity: 0.9;
    margin-bottom: 3rem;
}

.hero-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin: 3rem 0;
}

.feature-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.feature-card .feature-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.feature-card h3 {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
}

.feature-card p {
    opacity: 0.9;
    font-size: 0.95rem;
}

.hero-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.info-section {
    padding: 4rem 0;
    background: var(--bg-secondary);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.row {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
}

.col-md-6 {
    flex: 1 1 400px;
}

.feature-list {
    list-style: none;
    padding: 0;
}

.feature-list li {
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border);
}

.feature-list li:last-child {
    border-bottom: none;
}

.quick-start-steps {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.step {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.step-number {
    width: 40px;
    height: 40px;
    background: var(--accent-2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
}

.step-content h4 {
    margin: 0 0 0.25rem 0;
    color: var(--text-primary);
}

.step-content p {
    margin: 0;
    color: var(--text-muted);
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .hero-logo h1 {
        font-size: 2.5rem;
    }

    .hero-features {
        grid-template-columns: 1fr;
    }

    .hero-actions {
        flex-direction: column;
        align-items: center;
    }

    .row {
        flex-direction: column;
    }
}
</style>

<?php
include __DIR__ . '/includes/footer.php';
?>
