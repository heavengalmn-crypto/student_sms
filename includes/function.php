<?php
// includes/functions.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';   // Only if you installed PHPMailer via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendOTP($email) {
    global $conn;   // Make sure your database connection $conn is available

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Expiry time
    $expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

    // Delete old OTP for this email
    $stmt = $conn->prepare("DELETE FROM otp_codes WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // Insert new OTP
    $stmt = $conn->prepare("INSERT INTO otp_codes (email, otp, expiry) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $otp, $expiry);
    $stmt->execute();

    // Send Email
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your Login OTP - ' . APP_NAME;
        $mail->Body    = "
            <h2>Your One-Time Password (OTP)</h2>
            <p style='font-size: 28px; font-weight: bold; color: #333;'>{$otp}</p>
            <p>This code will expire in " . OTP_EXPIRY_MINUTES . " minutes.</p>
            <p>If you didn't request this OTP, please ignore this email.</p>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        if (APP_DEBUG) {
            echo "Mailer Error: " . $mail->ErrorInfo . "<br>";
        }
        return false;
    }
}