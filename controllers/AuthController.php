<?php
// controllers/AuthController.php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Otp.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/auth.php';

class AuthController {

    private User $userModel;
    private Student $studentModel;
    private Otp  $otpModel;
    private Permission $permModel;

    public function __construct() {
        $this->userModel    = new User();
        $this->studentModel = new Student();
        $this->otpModel     = new Otp();
        $this->permModel    = new Permission();
    }

    // REGISTER
    public function register(array $post): array {
        $username        = trim($post['username'] ?? '');
        $email           = trim($post['email'] ?? '');
        $phone           = trim($post['phone'] ?? '');
        $password        = $post['password'] ?? '';
        $confirmPassword = $post['confirm_password'] ?? '';

        $errors = [];

        // Validate username
        if (empty($username)) {
            $errors['username'] = 'Username is required.';
        } elseif (strlen($username) < 3) {
            $errors['username'] = 'Username must be at least 3 characters.';
        } elseif ($this->userModel->usernameExists($username)) {
            $errors['username'] = 'Username already exists. Please choose another.';
        }

        // Validate email
        if (empty($email)) {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        } elseif ($this->userModel->emailExists($email)) {
            $errors['email'] = 'Email is already registered. Please log in or use another email.';
        }

        // Validate phone
        if (empty($phone)) {
            $errors['phone'] = 'Phone number is required.';
        }

        // Validate password
        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one number.';
        }

        // Validate confirm password
        if (empty($confirmPassword)) {
            $errors['confirm_password'] = 'Please confirm your password.';
        } elseif ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Create the user
        try {
            $userId = $this->userModel->create([
                'username' => $username,
                'email'    => $email,
                'phone'    => $phone,
                'password' => $password,
                'role'     => 'user',
            ]);

            // Create default permissions for new user
            $this->permModel->createDefault($userId);

            flashMessage('success', 'Account created successfully! Please log in.');
            header('Location: ' . APP_URL . '/login.php');
            exit;

        } catch (Exception $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Registration error: " . $e->getMessage());
            }
            return ['success' => false, 'message' => 'An error occurred during registration. Please try again.'];
        }
    }

    // LOGIN – generate and send OTP (supports both users and students)
    public function login(array $post): array {
        $username = trim($post['username'] ?? '');
        $password = $post['password'] ?? '';

        if (empty($username) || empty($password))
            return ['success' => false, 'message' => 'Please fill in all fields.'];

        // First try to find in users table
        $user = $this->userModel->findByUsername($username);
        $accountType = 'user';

        // If not found, try students table
        if (!$user) {
            $user = $this->studentModel->findByUsername($username);
            $accountType = 'student';
        }

        // Check if user/student exists and password is correct
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        // Verify password based on account type
        if ($accountType === 'user' && !$this->userModel->verifyPassword($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        } elseif ($accountType === 'student' && !$this->studentModel->verifyPassword($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        // Generate OTP (this also deletes old unused OTPs)
        $otp = $this->otpModel->generate($user['id']);

        // Send OTP via Email and SMS (2FA)
        $phone = $user['phone'] ?? '';
        $otpResults = sendOtp2FA($user['email'], $phone, $user['username'], $otp);
        
        $emailSent = $otpResults['email'];
        $smsSent = $otpResults['sms'];

        // Check if at least email was sent
        if (!$emailSent && defined('APP_DEBUG') && APP_DEBUG) {
            error_log("Email sending failed, but OTP is: $otp");
        } elseif (!$emailSent) {
            return ['success' => false, 'message' => 'Unable to send OTP email. Contact support.'];
        }

        // Store user ID and account type in session for the OTP verification step
        $_SESSION['pre_auth_user_id']  = $user['id'];
        $_SESSION['pre_auth_username'] = $user['username'];
        $_SESSION['pre_auth_account_type'] = $accountType;

        // Build message based on what was sent
        $message = 'OTP sent to your email';
        if ($smsSent) {
            $message .= ' and SMS';
        }
        $message .= '. Please verify.';

        return [
            'success'   => true,
            'message'   => $message,
            'otp_debug' => (defined('APP_DEBUG') && APP_DEBUG) ? $otp : null,
        ];
    }

    // VERIFY OTP (supports both users and students)
    public function verifyOtp(array $post): array {
        // Must have started login process
        if (empty($_SESSION['pre_auth_user_id'])) {
            return ['success' => false, 'redirect' => APP_URL . '/login.php', 'message' => 'Session expired. Login again.'];
        }

        $userId = (int)$_SESSION['pre_auth_user_id'];
        $accountType = $_SESSION['pre_auth_account_type'] ?? 'user';
        $code   = trim($post['otp_code'] ?? '');

        // If OTP digits were sent as array (from 6 input boxes)
        if (empty($code) && !empty($post['otp_digit']) && is_array($post['otp_digit'])) {
            $code = implode('', array_map('trim', $post['otp_digit']));
        }

        // Validate format
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return ['success' => false, 'message' => 'Enter a valid 6-digit OTP.'];
        }

        // Perform verification
        $valid = $this->otpModel->verify($userId, $code);

        if (!$valid) {
            return ['success' => false, 'message' => 'Invalid or expired OTP. Please try again.'];
        }

        // Fetch full user/student data based on account type
        if ($accountType === 'student') {
            $user = $this->studentModel->findById($userId);
        } else {
            $user = $this->userModel->findById($userId);
        }

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Set full session
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['email']        = $user['email'];
        $_SESSION['account_type'] = $accountType;
        $_SESSION['otp_verified'] = true;

        // Set role-based data based on account type
        if ($accountType === 'student') {
            $_SESSION['role']        = 'student';
            $_SESSION['permissions'] = ['can_view' => 1, 'can_add' => 0, 'can_edit' => 0, 'can_delete' => 0];
        } else {
            $_SESSION['role']        = $user['role'];
            $_SESSION['permissions'] = $this->permModel->getByUser($userId);
        }

        // Clean temporary OTP session data
        unset($_SESSION['pre_auth_user_id'], $_SESSION['pre_auth_username'], $_SESSION['pre_auth_account_type']);

        $redirect = $accountType === 'student'
            ? APP_URL . '/students.php'
            : APP_URL . '/dashboard.php';

        return ['success' => true, 'redirect' => $redirect];
    }

    // RESEND OTP (supports both users and students)
    public function resendOtp(): array {
        if (empty($_SESSION['pre_auth_user_id'])) {
            return ['success' => false, 'message' => 'Session expired. Please log in again.'];
        }

        $userId = (int)$_SESSION['pre_auth_user_id'];
        $accountType = $_SESSION['pre_auth_account_type'] ?? 'user';

        // Fetch user/student data based on account type
        if ($accountType === 'student') {
            $user = $this->studentModel->findById($userId);
        } else {
            $user = $this->userModel->findById($userId);
        }

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        $otp = $this->otpModel->generate($userId);
        sendOtpEmail($user['email'], $user['username'], $otp);
        // Optional SMS: sendOtpSms($user['phone'], $otp);

        return ['success' => true, 'message' => 'A new OTP has been sent to your email.'];
    }

    // LOGOUT
    public function logout(): void {
        // Destroy all session data
        session_destroy();
        
        // Redirect to login page
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}