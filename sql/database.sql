-- Level Up Fitness Database Schema
-- Created: January 2026

-- Create Database
CREATE DATABASE IF NOT EXISTS level_up_fitness;
USE level_up_fitness;

-- ============================================
-- 1. USERS TABLE (Authentication)
-- ============================================
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'member', 'trainer') NOT NULL DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL,
    INDEX idx_email (email),
    INDEX idx_user_type (user_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. MEMBERS TABLE
-- ============================================
CREATE TABLE members (
    member_id VARCHAR(50) PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    member_name VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    membership_type ENUM('Monthly', 'Quarterly', 'Annual') NOT NULL,
    join_date DATE NOT NULL,
    out_date DATE NULL,
    trainer_id VARCHAR(50),
    status ENUM('Active', 'Inactive', 'Expired') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_member_name (member_name),
    INDEX idx_email (email),
    INDEX idx_trainer_id (trainer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. TRAINERS TABLE
-- ============================================
CREATE TABLE trainers (
    trainer_id VARCHAR(50) PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    trainer_name VARCHAR(255) NOT NULL,
    specialization VARCHAR(255) NOT NULL,
    years_of_experience INT NOT NULL DEFAULT 0,
    availability LONGTEXT,
    contact_number VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_trainer_name (trainer_name),
    INDEX idx_specialization (specialization)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. GYMS TABLE
-- ============================================
CREATE TABLE gyms (
    gym_id VARCHAR(50) PRIMARY KEY,
    gym_branch VARCHAR(255) NOT NULL,
    gym_name VARCHAR(255) NOT NULL,
    location TEXT,
    description TEXT,
    contact_number VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gym_branch (gym_branch)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. WORKOUT_PLANS TABLE
-- ============================================
CREATE TABLE workout_plans (
    workout_plan_id VARCHAR(50) PRIMARY KEY,
    template_id VARCHAR(50),
    member_id VARCHAR(50) NOT NULL,
    trainer_id VARCHAR(50),
    plan_name VARCHAR(255) NOT NULL,
    weekly_schedule LONGTEXT NOT NULL,
    plan_details LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES workout_templates(template_id) ON DELETE SET NULL,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE SET NULL,
    INDEX idx_member_id (member_id),
    INDEX idx_trainer_id (trainer_id),
    INDEX idx_template_id (template_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. SESSIONS TABLE
-- ============================================
CREATE TABLE sessions (
    session_id VARCHAR(50) PRIMARY KEY,
    session_name VARCHAR(255) NOT NULL,
    session_date DATE NOT NULL,
    session_time TIME NOT NULL,
    day_of_week VARCHAR(20) NOT NULL,
    session_plan LONGTEXT NOT NULL,
    trainer_id VARCHAR(50),
    member_id VARCHAR(50),
    session_status ENUM('Scheduled', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE SET NULL,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE SET NULL,
    INDEX idx_session_date (session_date),
    INDEX idx_session_status (session_status),
    INDEX idx_trainer_id (trainer_id),
    INDEX idx_member_id (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. PAYMENTS TABLE
-- ============================================
CREATE TABLE payments (
    payment_id VARCHAR(50) PRIMARY KEY,
    member_id VARCHAR(50) NOT NULL,
    session_id VARCHAR(50),
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('Cash', 'Card', 'GCash', 'Bank Transfer') NOT NULL,
    payment_status ENUM('Paid', 'Pending', 'Overdue') NOT NULL DEFAULT 'Pending',
    payment_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(session_id) ON DELETE SET NULL,
    INDEX idx_member_id (member_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. CLASSES TABLE
-- ============================================
CREATE TABLE classes (
    class_id VARCHAR(50) PRIMARY KEY,
    class_name VARCHAR(255) NOT NULL,
    trainer_id VARCHAR(50) NOT NULL,
    class_description LONGTEXT,
    schedule_day VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_capacity INT NOT NULL DEFAULT 20,
    class_status ENUM('Active', 'Inactive', 'Cancelled') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE,
    INDEX idx_trainer_id (trainer_id),
    INDEX idx_class_status (class_status),
    INDEX idx_schedule_day (schedule_day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8A. CLASS_ATTENDANCE TABLE
-- ============================================
CREATE TABLE class_attendance (
    attendance_id VARCHAR(50) PRIMARY KEY,
    class_id VARCHAR(50) NOT NULL,
    member_id VARCHAR(50) NOT NULL,
    enrollment_date DATE NOT NULL,
    attendance_date DATE NOT NULL,
    attendance_status ENUM('Present', 'Absent', 'Late') NOT NULL DEFAULT 'Present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    INDEX idx_class_id (class_id),
    INDEX idx_member_id (member_id),
    INDEX idx_attendance_date (attendance_date),
    INDEX idx_attendance_status (attendance_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. ATTENDANCE TABLE (General attendance/check-in)
-- ============================================
CREATE TABLE attendance (
    attendance_id VARCHAR(50) PRIMARY KEY,
    member_id VARCHAR(50) NOT NULL,
    check_in_time DATETIME NOT NULL,
    check_out_time DATETIME,
    attendance_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    INDEX idx_member_id (member_id),
    INDEX idx_attendance_date (attendance_date),
    INDEX idx_check_in_time (check_in_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. RESERVATIONS TABLE
-- ============================================
-- ============================================
-- 10. RESERVATIONS TABLE
-- ============================================
CREATE TABLE reservations (
    reservation_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id VARCHAR(50) NOT NULL,
    trainer_id VARCHAR(50) NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    status ENUM('Confirmed', 'Cancelled', 'Completed') NOT NULL DEFAULT 'Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE,
    INDEX idx_member_id (member_id),
    INDEX idx_trainer_id (trainer_id),
    INDEX idx_reservation_date (reservation_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. ACTIVITY_LOG TABLE
-- ============================================
CREATE TABLE activity_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(100) NOT NULL,
    details LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. TRAINING_SESSIONS TABLE (Group Sessions)
-- ============================================
CREATE TABLE training_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    session_name VARCHAR(255) NOT NULL,
    trainer_id VARCHAR(50) NOT NULL,
    gym_id VARCHAR(50) NOT NULL,
    session_date DATE NOT NULL,
    session_time TIME NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    max_capacity INT NOT NULL DEFAULT 20,
    description LONGTEXT,
    status ENUM('Scheduled', 'Ongoing', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE,
    FOREIGN KEY (gym_id) REFERENCES gyms(gym_id) ON DELETE CASCADE,
    INDEX idx_session_date (session_date),
    INDEX idx_trainer_id (trainer_id),
    INDEX idx_gym_id (gym_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 13. TRAINING_SESSION_ATTENDEES TABLE
-- ============================================
CREATE TABLE training_session_attendees (
    attendee_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    member_id VARCHAR(50) NOT NULL,
    check_in_time DATETIME,
    check_out_time DATETIME,
    attendance_status ENUM('Present', 'Absent', 'Late', 'Cancelled') NOT NULL DEFAULT 'Present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES training_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    UNIQUE KEY unique_session_member (session_id, member_id),
    INDEX idx_session_id (session_id),
    INDEX idx_member_id (member_id),
    INDEX idx_attendance_status (attendance_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 14. WORKOUT_TEMPLATES TABLE
-- ============================================
CREATE TABLE workout_templates (
    template_id VARCHAR(50) PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    template_type VARCHAR(100) NOT NULL,
    difficulty_level VARCHAR(50) NOT NULL,
    description LONGTEXT NOT NULL,
    goal VARCHAR(255) NOT NULL,
    duration_weeks INT NOT NULL,
    exercises_count INT NOT NULL,
    equipment_required VARCHAR(255),
    popularity_score INT DEFAULT 0,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_difficulty_level (difficulty_level),
    INDEX idx_template_type (template_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAMPLE DATA (Optional)
-- ============================================

-- Insert sample admin user
INSERT INTO users (email, password, user_type) VALUES 
('admin@levelupfitness.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

COMMIT;
