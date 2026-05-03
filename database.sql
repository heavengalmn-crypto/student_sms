-- ============================================================
-- Student Management System - Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS sms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sms_db;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    role ENUM('super_admin', 'user') NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- PERMISSIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    can_add TINYINT(1) NOT NULL DEFAULT 0,
    can_edit TINYINT(1) NOT NULL DEFAULT 0,
    can_delete TINYINT(1) NOT NULL DEFAULT 0,
    can_view TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- STUDENTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- OTP CODES TABLE (Secure with hashed storage)
-- ============================================================
CREATE TABLE IF NOT EXISTS otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id, used, expires_at)
) ENGINE=InnoDB;

-- ============================================================
-- DEFAULT SUPER ADMIN ACCOUNT (SECURE: Use database role assignment only)
-- ============================================================
INSERT INTO users (username, password, email, phone, role) VALUES (
    'superadmin',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin@sms.local',
    '09000000000',
    'super_admin'
) ON DUPLICATE KEY UPDATE id=id;

-- Grant all permissions to super admin
INSERT INTO permissions (user_id, can_add, can_edit, can_delete, can_view)
SELECT id, 1, 1, 1, 1 FROM users WHERE username = 'superadmin'
ON DUPLICATE KEY UPDATE can_add=1, can_edit=1, can_delete=1, can_view=1;
