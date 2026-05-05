<?php
// controllers/AuthController.php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Otp.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthController {

    private User $userModel;
    private Student $studentModel;
    private Otp $otpModel;
    private Permission $permModel;

    public function __construct() {
        $this->userModel    = new User();
        $this->studentModel = new Student();
        $this->otpModel     = new Otp();
        $this->permModel    = new Permission();
    }

    // =========================
    // REGISTER
    // =========================
    public function register(array $post): array {
        $username        = trim($post['username'] ?? '');
        $email           = trim($post['email'] ?? '');
        $phone           = trim($post['phone'] ?? '');
        $password        = $post['password'] ?? '';
        $confirmPassword = $post['confirm_password'] ?? '';

        $errors = [];

        if (empty($username)) {
            $errors['username'] = 'Username is required.';
        } elseif (strlen($username) < 3) {
            $errors['username'] = 'Username must be at least 3 characters.';
        } elseif ($this->userModel->usernameExists($username)) {
            $errors['username'] = 'Username already exists.';
        }

        if (empty($email)) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email.';
        } elseif ($this->userModel->emailExists($email)) {
            $errors['email'] = 'Email already registered.';
        }

        if (empty($phone)) {
            $errors['phone'] = 'Phone is required.';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $userId = $this->userModel->create([
                'username' => $username,
                'email'    => $email,
                'phone'    => $phone,
                'password' => $password,
                'role'     => 'user',
            ]);

            $this->permModel->createDefault($userId);

            flashMessage('success', 'Account created! Please login.');
            header('Location: ' . APP_URL . '/login.php');
            exit;

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration error.'];
        }
    }

    // =========================
    // LOGIN
    // =========================
    public function login(array $post): array {
        $username = trim($post['username'] ?? '');
        $password = $post['password'] ?? '';

        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Please fill in all fields.'];
        }

        $user = $this->userModel->findByUsername($username);
        $accountType = 'user';

        if (!$user) {
            $user = $this->studentModel->findByUsername($username);
            $accountType = 'student';
        }

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        $otp = $this->otpModel->generate($user['id']);

        $phone = $user['phone'] ?? '';
        $otpResults = sendOtp2FA($user['email'], $phone, $user['username'], $otp);

        if (empty($otpResults['email'])) {
            return ['success' => false, 'message' => 'Failed to send OTP email.'];
        }

        // ✅ FIX: SESSION ONLY (NO POST DEPENDENCY)
        $_SESSION['pre_auth_user_id'] = $user['id'];
        $_SESSION['pre_auth_username'] = $user['username'];
        $_SESSION['pre_auth_account_type'] = $accountType;

        return [
            'success' => true,
            'message' => 'OTP sent. Please verify.'
        ];
    }

    // =========================
    // VERIFY OTP (FIXED)
    // =========================
    public function verifyOtp(array $post): array {

    session_start();

    // 🔥 FIX: get user from SESSION (not POST)
    $userId = $_SESSION['pre_auth_user_id'] ?? null;
    $accountType = $_SESSION['pre_auth_account_type'] ?? null;

    if (is_null($userId)) {
        return ['success' => false, 'message' => 'User ID is required.'];
    }

    // 🔥 FIX: accept OTP from either field
    $otpCode = trim(
        $post['otp'] ??
        $post['otp_code'] ??
        ''
    );

    if (empty($otpCode)) {
        return ['success' => false, 'message' => 'OTP is required.'];
    }

    // verify OTP
    $isValid = $this->otpModel->verify($userId, $otpCode);

    if (!$isValid) {
        return ['success' => false, 'message' => 'Invalid or expired OTP.'];
    }

    // get user
    $user = $this->userModel->findById((int)$userId);

    // 🔥 FIX: THIS IS THE MOST IMPORTANT PART (LOGIN LOOP FIX)
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $user['username'] ?? '';
    $_SESSION['role'] = $accountType ?? 'user';

    // clear temp auth session
    unset($_SESSION['pre_auth_user_id']);
    unset($_SESSION['pre_auth_username']);
    unset($_SESSION['pre_auth_account_type']);

    // redirect target
    $redirect = ($accountType === 'student')
        ? APP_URL . '/student_dashboard.php'
        : APP_URL . '/dashboard.php';

    return [
        'success' => true,
        'redirect' => $redirect
    ];
}

    // =========================
    // LOGOUT
    // =========================
    public function logout(): void {
        session_destroy();
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}