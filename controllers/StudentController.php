<?php
// controllers/StudentController.php

require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../includes/auth.php';

class StudentController {

    private Student $model;

    public function __construct() {
        $this->model = new Student();
    }

    public function index(): array {
        if (!hasPermission('can_view')) {
            return ['error' => 'You do not have permission to view students.'];
        }
        return ['students' => $this->model->all()];
    }

    public function store(array $post): array {
        if (!hasPermission('can_add'))
            return ['success' => false, 'message' => 'Permission denied: cannot add students.'];

        $errors = $this->validate($post);
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        if ($this->model->studentIdExists(trim($post['student_id'])))
            return ['success' => false, 'message' => 'Student ID already exists.'];

        if ($this->model->usernameExists(trim($post['username'])))
            return ['success' => false, 'message' => 'Username already exists.'];

        if ($this->model->emailExists(trim($post['email'])))
            return ['success' => false, 'message' => 'Email already exists.'];

        $ok = $this->model->create([
            'student_id'  => trim($post['student_id']),
            'username'    => trim($post['username']),
            'email'       => trim($post['email']),
            'password'    => $post['password'],
            'phone'       => trim($post['phone'] ?? ''),
            'first_name'  => trim($post['first_name']),
            'middle_name' => trim($post['middle_name'] ?? ''),
            'last_name'   => trim($post['last_name']),
            'address'     => trim($post['address']),
            'created_by'  => $_SESSION['user_id'],
        ]);

        return $ok
            ? ['success' => true,  'message' => 'Student added successfully.']
            : ['success' => false, 'message' => 'Failed to add student.'];
    }

    public function update(int $id, array $post): array {
        if (!hasPermission('can_edit'))
            return ['success' => false, 'message' => 'Permission denied: cannot edit students.'];

        $errors = $this->validate($post, true);
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        if ($this->model->studentIdExists(trim($post['student_id']), $id))
            return ['success' => false, 'message' => 'Student ID already exists.'];

        if ($this->model->usernameExists(trim($post['username'] ?? ''), $id))
            return ['success' => false, 'message' => 'Username already exists.'];

        if ($this->model->emailExists(trim($post['email'] ?? ''), $id))
            return ['success' => false, 'message' => 'Email already exists.'];

        $ok = $this->model->update($id, [
            'student_id'  => trim($post['student_id']),
            'username'    => trim($post['username'] ?? ''),
            'email'       => trim($post['email'] ?? ''),
            'phone'       => trim($post['phone'] ?? ''),
            'password'    => $post['password'] ?? '',
            'first_name'  => trim($post['first_name']),
            'middle_name' => trim($post['middle_name'] ?? ''),
            'last_name'   => trim($post['last_name']),
            'address'     => trim($post['address']),
        ]);

        return $ok
            ? ['success' => true,  'message' => 'Student updated successfully.']
            : ['success' => false, 'message' => 'Failed to update student.'];
    }

    public function delete(int $id): array {
        if (!hasPermission('can_delete'))
            return ['success' => false, 'message' => 'Permission denied: cannot delete students.'];

        $student = $this->model->findById($id);
        if (!$student)
            return ['success' => false, 'message' => 'Student not found.'];

        $ok = $this->model->delete($id);
        return $ok
            ? ['success' => true,  'message' => 'Student deleted successfully.']
            : ['success' => false, 'message' => 'Failed to delete student.'];
    }

    private function validate(array $post, bool $isEdit = false): array {
        $errors = [];
        
        // Student info validation
        if (empty(trim($post['student_id'] ?? '')))    $errors['student_id']  = 'Student ID is required.';
        if (empty(trim($post['first_name'] ?? '')))    $errors['first_name']  = 'First name is required.';
        if (empty(trim($post['last_name']  ?? '')))    $errors['last_name']   = 'Last name is required.';
        if (empty(trim($post['address']    ?? '')))    $errors['address']     = 'Address is required.';

        // Login credentials validation for both add and edit
        $username = trim($post['username'] ?? '');
        if (empty($username)) {
            $errors['username'] = 'Username is required.';
        } elseif (strlen($username) < 3) {
            $errors['username'] = 'Username must be at least 3 characters.';
        }

        $email = trim($post['email'] ?? '');
        if (empty($email)) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        // Phone validation (optional but recommended for SMS 2FA)
        $phone = trim($post['phone'] ?? '');
        if (!empty($phone) && !preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
            $errors['phone'] = 'Please enter a valid phone number (with country code, e.g., +1234567890).';
        }

        $password = $post['password'] ?? '';
        if (!$isEdit || !empty($password)) {
            if (empty($password)) {
                $errors['password'] = 'Password is required.';
            } elseif (strlen($password) < 8) {
                $errors['password'] = 'Password must be at least 8 characters.';
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $errors['password'] = 'Password must contain at least one uppercase letter.';
            } elseif (!preg_match('/[0-9]/', $password)) {
                $errors['password'] = 'Password must contain at least one number.';
            }
        }

        return $errors;
    }
}
