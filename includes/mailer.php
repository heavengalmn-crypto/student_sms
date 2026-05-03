<?php
// includes/mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Twilio\Rest\Client;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

function buildOtpEmailHtml(string $name, string $otp): string {
    return '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
        <div style="max-width: 500px; margin: auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <div style="background: #1a1a2e; padding: 20px; text-align: center;">
                <h2 style="color: #e94560; margin: 0;">Student Management System</h2>
            </div>
            <div style="padding: 30px;">
                <p>Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
                <p>Your One-Time Password (OTP) for login verification is:</p>
                <div style="text-align: center; margin: 24px 0;">
                    <span style="font-size: 48px; font-weight: bold; letter-spacing: 12px; color: #1a1a2e; background: #f0f4ff; padding: 16px 28px; border-radius: 8px; display: inline-block; font-family: monospace;">' . htmlspecialchars($otp) . '</span>
                </div>
                <p style="color: #666;">This OTP is valid for <strong>' . OTP_EXPIRY_MINUTES . ' minutes</strong> and can only be used once.</p>
                <p style="color: #d32f2f; font-weight: bold; font-size: 12px;">⚠️ Never share this code with anyone. We will never ask for your OTP.</p>
                <p style="color: #999; font-size: 12px;">If you did not attempt to log in, please secure your account immediately by contacting support.</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Send OTP email via PHPMailer using Gmail SMTP
 * 
 * @param string $email    Recipient email address
 * @param string $username Recipient username (for greeting)
 * @param string $otp      The one-time password to send
 * @return bool            True if email sent successfully, false otherwise
 */
function sendOtpEmail(string $email, string $username, string $otp): bool {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;
        
        // Recipients
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($email, $username);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for ' . APP_NAME;
        $mail->Body    = buildOtpEmailHtml($username, $otp);
        $mail->AltBody = "Your OTP is: $otp\nValid for " . OTP_EXPIRY_MINUTES . " minutes.";
        
        // Set timeout and SMTPDebug if in debug mode
        $mail->Timeout = 10;
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $mail->SMTPDebug = 0; // Set to 2 or 3 for verbose debug output
        }
        
        return $mail->send();
        
    } catch (Exception $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
        }
        return false;
    }
}

/**
 * Send OTP via SMS using Twilio
 * 
 * @param string $phone    Recipient phone number (with country code, e.g., +1234567890)
 * @param string $otp      The one-time password to send
 * @return bool            True if SMS sent successfully, false otherwise
 */
function sendOtpSms(string $phone, string $otp): bool {
    try {
        // Check if Twilio credentials are configured
        if (TWILIO_SID === 'your_twilio_account_sid' || 
            TWILIO_TOKEN === 'your_twilio_auth_token' || 
            TWILIO_FROM === 'your_twilio_phone_number') {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Twilio SMS not configured. Please set TWILIO_SID, TWILIO_TOKEN, and TWILIO_FROM in config.php");
            }
            return false;
        }

        $client = new Client(TWILIO_SID, TWILIO_TOKEN);
        
        $message = "Your OTP for " . APP_NAME . " is: $otp. Valid for " . OTP_EXPIRY_MINUTES . " minutes. Do not share this code.";
        
        $client->messages->create(
            $phone,
            [
                'from' => TWILIO_FROM,
                'body' => $message
            ]
        );
        
        return true;
        
    } catch (Exception $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("Twilio SMS Error: " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Send OTP via both Email and SMS (2FA)
 * 
 * @param string $email    Recipient email address
 * @param string $phone    Recipient phone number (optional, for SMS)
 * @param string $username Recipient username (for email greeting)
 * @param string $otp      The one-time password to send
 * @return array           Array with 'email' and 'sms' boolean results
 */
function sendOtp2FA(string $email, string $phone, string $username, string $otp): array {
    $results = [
        'email' => sendOtpEmail($email, $username, $otp),
        'sms'   => false
    ];
    
    // Send SMS if phone is provided
    if (!empty($phone)) {
        $results['sms'] = sendOtpSms($phone, $otp);
    }
    
    return $results;
}