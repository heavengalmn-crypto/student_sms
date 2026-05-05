<?php
// config/config.php

define('APP_NAME', 'Student Management System');
define('APP_URL', 'http://localhost/sms');
define('APP_DEBUG', true);  // Set to false in production to prevent debug information exposure
define('OTP_EXPIRY_MINUTES', 5);

// ==================== PHPMailer Gmail SMTP Config ====================
// Ensure email credentials are configured for secure OTP delivery
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'heavengalmn@gmail.com');
define('MAIL_PASSWORD', 'vacqqtpdfggljplz');        // ← IMPORTANT: Use App-Specific Password, not your Gmail password
define('MAIL_FROM', 'heavengalmn@gmail.com');
define('MAIL_FROM_NAME', APP_NAME);
define('MAIL_ENCRYPTION', 'tls');
date_default_timezone_set('Asia/Manila');

// ==================== Twilio SMS Config ====================
// Configure for production use. SMS OTP requires valid Twilio credentials
define('TWILIO_SID', 'your_twilio_account_sid');
define('TWILIO_TOKEN', 'your_twilio_auth_token');
define('TWILIO_FROM', 'your_twilio_phone_number');

// ==================== Session Security Config ====================
// Enforce secure session handling
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Disable session in URLs to prevent session fixation attacks
ini_set('session.use_trans_sid', 0);

// Set session lifetime to 30 minutes of inactivity
ini_set('session.gc_maxlifetime', 1800);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
