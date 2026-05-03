<?php
// models/Permission.php

require_once __DIR__ . '/../config/database.php';

class Permission {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getByUser(int $userId): array {
        $stmt = $this->db->prepare("SELECT * FROM permissions WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: ['can_add' => 0, 'can_edit' => 0, 'can_delete' => 0, 'can_view' => 0];
    }

    public function upsert(int $userId, array $perms): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO permissions (user_id, can_add, can_edit, can_delete, can_view)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               can_add = VALUES(can_add),
               can_edit = VALUES(can_edit),
               can_delete = VALUES(can_delete),
               can_view = VALUES(can_view)"
        );
        return $stmt->execute([
            $userId,
            (int)(bool)($perms['can_add']    ?? false),
            (int)(bool)($perms['can_edit']   ?? false),
            (int)(bool)($perms['can_delete'] ?? false),
            (int)(bool)($perms['can_view']   ?? false),
        ]);
    }

    public function createDefault(int $userId): void {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO permissions (user_id, can_add, can_edit, can_delete, can_view)
             VALUES (?, 0, 0, 0, 1)"
        );
        $stmt->execute([$userId]);
    }
}
