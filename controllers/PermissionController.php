<?php
// controllers/PermissionController.php

require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/auth.php';

class PermissionController {

    private Permission $permModel;
    private User $userModel;

    public function __construct() {
        $this->permModel = new Permission();
        $this->userModel = new User();
    }

    public function index(): array {
        requireSuperAdmin();
        return ['users' => $this->userModel->all()];
    }

    public function update(int $userId, array $post): array {
        requireSuperAdmin();

        $user = $this->userModel->findById($userId);
        if (!$user) return ['success' => false, 'message' => 'User not found.'];
        if ($user['role'] === 'super_admin') return ['success' => false, 'message' => 'Cannot modify super admin permissions.'];

        $ok = $this->permModel->upsert($userId, [
            'can_add'    => isset($post['can_add']),
            'can_edit'   => isset($post['can_edit']),
            'can_delete' => isset($post['can_delete']),
            'can_view'   => isset($post['can_view']),
        ]);

        return $ok
            ? ['success' => true,  'message' => 'Permissions updated for ' . htmlspecialchars($user['username']) . '.']
            : ['success' => false, 'message' => 'Failed to update permissions.'];
    }
}
