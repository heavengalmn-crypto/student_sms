<?php
// models/User.php

require_once __DIR__ . '/../config/database.php';

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function emailExists(string $email, int $excludeId = 0): bool {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmt->execute([$email, $excludeId]);
        return (bool)$stmt->fetch();
    }

    public function usernameExists(string $username, int $excludeId = 0): bool {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
        $stmt->execute([$username, $excludeId]);
        return (bool)$stmt->fetch();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password, email, phone, role) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['username'],
            password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            $data['email'],
            $data['phone'],
            $data['role'] ?? 'user',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function all(): array {
        $stmt = $this->db->query(
            "SELECT u.*, p.can_add, p.can_edit, p.can_delete, p.can_view
             FROM users u
             LEFT JOIN permissions p ON p.user_id = u.id
             ORDER BY u.id ASC"
        );
        return $stmt->fetchAll();
    }

    public function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }
}
