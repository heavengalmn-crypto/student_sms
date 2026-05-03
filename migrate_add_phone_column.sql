-- ============================================================
-- Migration: Add phone column to students table
-- ============================================================
-- Run this if you have an existing database and students table
-- without the phone column

ALTER TABLE students ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email;

-- Clean up any old plaintext OTPs that won't work with new hashing
DELETE FROM otps WHERE otp_code NOT LIKE '$2y$%';
