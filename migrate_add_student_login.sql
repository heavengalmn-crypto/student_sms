-- ============================================================
-- Migration: Add Student Login Credentials
-- Date: May 2, 2026
-- ============================================================

USE sms_db;

-- Add login columns to students table if they don't exist
ALTER TABLE students 
ADD COLUMN username VARCHAR(50) UNIQUE AFTER student_id,
ADD COLUMN password VARCHAR(255) AFTER username,
ADD COLUMN email VARCHAR(100) UNIQUE AFTER password,
ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER address;

-- Set default values for existing students (you should update these manually with real data)
-- This creates temporary login credentials - CHANGE THESE IMMEDIATELY
UPDATE students 
SET 
    username = CONCAT('student_', id),
    password = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Password: Admin@1234
    email = CONCAT('student', id, '@sms.local'),
    is_active = 1
WHERE username IS NULL;

-- Create index on username and email for faster lookups
CREATE INDEX idx_students_username ON students(username);
CREATE INDEX idx_students_email ON students(email);
