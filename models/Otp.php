<?php
// models/Otp.php

require_once __DIR__ . '/../config/database.php';

class Otp {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Generate a new OTP for the user, delete old ones.
     * Returns the plaintext OTP once (to send via email/SMS).
     * Stores it hashed in the database for security.
     */
    public function generate(int $userId): string {
        // Remove any previous unused OTPs for this user
        $this->deletePrevious($userId);

        // Generate 6-digit code
        $otp = sprintf("%06d", random_int(0, 999999));

        // Hash the OTP for secure storage
        $otpHash = password_hash($otp, PASSWORD_BCRYPT, ['cost' => 10]);

        // Expiry time (using PHP time, converted to MySQL datetime)
        $expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

        $sql = "INSERT INTO otps (user_id, otp_code, expires_at, created_at, used) 
                VALUES (:user_id, :otp_code, :expires_at, NOW(), 0)";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':user_id'   => $userId,
            ':otp_code'  => $otpHash,
            ':expires_at'=> $expiry
        ]);

        if (!$success) {
            error_log("Otp::generate() FAILED for user $userId");
        }

        return $otp;
    }

    /**
     * Verify the entered OTP for the user using secure hash comparison.
     */
    public function verify(int $userId, string $enteredOtp): bool {
        $enteredOtp = trim($enteredOtp);

        if (!ctype_digit($enteredOtp) || strlen($enteredOtp) !== 6) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("OTP format invalid for user $userId: '$enteredOtp'");
            }
            return false;
        }

        $sql = "SELECT id, otp_code, expires_at 
                FROM otps 
                WHERE user_id = :user_id 
                  AND used = 0 
                  AND expires_at > NOW() 
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("No valid OTP record found for user $userId");
                // Check if there are any OTPs at all for this user
                $checkSql = "SELECT COUNT(*) as count FROM otps WHERE user_id = :user_id";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([':user_id' => $userId]);
                $count = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
                error_log("Total OTP records for user $userId: $count");
            }
            return false;
        }

        if (password_verify($enteredOtp, $record['otp_code'])) {
            $this->markUsedById($record['id']);
            return true;
        }

        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("OTP hash verification failed for user $userId");
        }
        return false;
    }

    /**
     * Mark OTP as used after successful verification (by OTP ID).
     */
    private function markUsedById(int $otpId): void {
        $sql = "UPDATE otps SET used = 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $otpId]);
    }

    /**
     * Delete all unused OTPs for a user (cleanup before new generation).
     */
    private function deletePrevious(int $userId): void {
        $sql = "DELETE FROM otps WHERE user_id = :user_id AND used = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
    }
}