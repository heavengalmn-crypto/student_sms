<?php
// includes/auth.php
 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
 
function requireLogin(): void {
    if (empty($_SESSION['user_id']) || empty($_SESSION['otp_verified'])) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}
 
// ✅ FIXED: was incorrectly nested INSIDE requireLogin()
function isStudent(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}
 
function requireSuperAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'super_admin') {
        header('Location: ' . APP_URL . '/dashboard.php?error=unauthorized');
        exit;
    }
}
 
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['otp_verified']);
}
 
function hasPermission(string $perm): bool {
    if (!isLoggedIn()) return false;
    if ($_SESSION['role'] === 'super_admin') return true;
    return !empty($_SESSION['permissions'][$perm]);
}
 
function loadPermissions(int $userId): array {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT can_add, can_edit, can_delete, can_view FROM permissions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: ['can_add' => 0, 'can_edit' => 0, 'can_delete' => 0, 'can_view' => 0];
}
 
function refreshSessionPermissions(): void {
    if (!empty($_SESSION['user_id'])) {
        $_SESSION['permissions'] = loadPermissions($_SESSION['user_id']);
    }
}
 
function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}
 
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
 
function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
 
function flashMessage(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
 
function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}