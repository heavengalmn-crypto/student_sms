<?php
// models/Student.php

require_once __DIR__ . '/../config/database.php';

class Student {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function all(): array {
        $stmt = $this->db->query(
            "SELECT s.*, u.username AS created_by_name
             FROM students s
             LEFT JOIN users u ON u.id = s.created_by
             ORDER BY s.id DESC"
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM students WHERE username = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public function studentIdExists(string $studentId, int $excludeId = 0): bool {
        $stmt = $this->db->prepare("SELECT id FROM students WHERE student_id = ? AND id != ? LIMIT 1");
        $stmt->execute([$studentId, $excludeId]);
        return (bool)$stmt->fetch();
    }

    public function usernameExists(string $username, int $excludeId = 0): bool {
        $stmt = $this->db->prepare("SELECT id FROM students WHERE username = ? AND id != ? LIMIT 1");
        $stmt->execute([$username, $excludeId]);
        return (bool)$stmt->fetch();
    }

    public function emailExists(string $email, int $excludeId = 0): bool {
        $stmt = $this->db->prepare("SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1");
        $stmt->execute([$email, $excludeId]);
        return (bool)$stmt->fetch();
    }

    public function create(array $data): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO students (student_id, username, password, email, phone, first_name, middle_name, last_name, address, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $data['student_id'],
            $data['username'],
            password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            $data['email'],
            $data['phone'] ?? null,
            $data['first_name'],
            $data['middle_name'] ?? null,
            $data['last_name'],
            $data['address'],
            $data['created_by'],
        ]);
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE students SET student_id = ?, username = ?, email = ?, phone = ?, first_name = ?, middle_name = ?, last_name = ?, address = ?";
        $params = [
            $data['student_id'],
            $data['username'],
            $data['email'],
            $data['phone'] ?? null,
            $data['first_name'],
            $data['middle_name'] ?? null,
            $data['last_name'],
            $data['address'],
        ];

        if (!empty($data['password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM students WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function count(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM students")->fetchColumn();
    }

    public function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }
}
