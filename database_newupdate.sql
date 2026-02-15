-- =============================================================================
-- JDBMS - Jail Database Management System
-- Complete database: one file with all tables and initial data
-- =============================================================================
-- Usage: Import in phpMyAdmin, or: mysql -u root < database_full.sql
-- This will DROP and recreate jdbms_db. Backup first if you have existing data.
-- =============================================================================

DROP DATABASE IF EXISTS jdbms_db;
CREATE DATABASE jdbms_db;
USE jdbms_db;

-- -----------------------------------------------------------------------------
-- 1. USER_ACCOUNT (Login)
-- -----------------------------------------------------------------------------
CREATE TABLE user_account (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'prisoner') NOT NULL
);

-- -----------------------------------------------------------------------------
-- 2. ADMIN
-- -----------------------------------------------------------------------------
CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    admin_name VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES user_account(user_id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- 3. PRISONER
-- -----------------------------------------------------------------------------
CREATE TABLE prisoner (
    prisoner_id VARCHAR(10) PRIMARY KEY,
    user_id INT,
    name VARCHAR(100),
    father_name VARCHAR(100),
    mother_name VARCHAR(100),
    present_address TEXT,
    permanent_address TEXT,
    dob DATE,
    gender ENUM('Male', 'Female', 'Other'),
    height_cm INT,
    weight_kg INT,
    blood_group VARCHAR(5),
    eye_color VARCHAR(20),
    hair_color VARCHAR(20),
    emergency_contact_name VARCHAR(100),
    emergency_contact_no VARCHAR(20),
    admission_date DATE DEFAULT CURRENT_DATE,
    cell_no VARCHAR(10),
    security_level VARCHAR(50) DEFAULT 'Medium',
    current_status ENUM('Normal', 'Paroled', 'Isolated') DEFAULT 'Normal',
    total_points INT DEFAULT 50,
    FOREIGN KEY (user_id) REFERENCES user_account(user_id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- 4. CRIME
-- -----------------------------------------------------------------------------
CREATE TABLE crime (
    crime_id INT AUTO_INCREMENT PRIMARY KEY,
    prisoner_id VARCHAR(10),
    crime_type VARCHAR(100),
    description TEXT,
    severity_level VARCHAR(50),
    crime_date DATE,
    location VARCHAR(100),
    FOREIGN KEY (prisoner_id) REFERENCES prisoner(prisoner_id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- 5. SENTENCE
-- -----------------------------------------------------------------------------
CREATE TABLE sentence (
    sentence_id INT AUTO_INCREMENT PRIMARY KEY,
    prisoner_id VARCHAR(10),
    sentence_type VARCHAR(50),
    start_date DATE,
    end_date DATE,
    duration_in_months INT,
    parole_eligibility TINYINT(1) DEFAULT 0,
    FOREIGN KEY (prisoner_id) REFERENCES prisoner(prisoner_id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- 6. BEHAVIOR_RECORD
-- -----------------------------------------------------------------------------
CREATE TABLE behavior_record (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    prisoner_id VARCHAR(10),
    admin_id INT,
    record_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    points_change INT,
    reason VARCHAR(255),
    FOREIGN KEY (prisoner_id) REFERENCES prisoner(prisoner_id),
    FOREIGN KEY (admin_id) REFERENCES admin(admin_id)
);

-- -----------------------------------------------------------------------------
-- 7. DUTY
-- -----------------------------------------------------------------------------
CREATE TABLE duty (
    duty_id INT AUTO_INCREMENT PRIMARY KEY,
    duty_name VARCHAR(50),
    required_hours_per_date INT
);

-- -----------------------------------------------------------------------------
-- 8. DUTY_ASSIGNMENT
-- -----------------------------------------------------------------------------
CREATE TABLE duty_assignment (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    prisoner_id VARCHAR(10),
    duty_id INT,
    admin_id INT,
    hours_assigned INT,
    hours_completed INT DEFAULT 0,
    status ENUM('Pending', 'Approved') DEFAULT 'Pending',
    FOREIGN KEY (prisoner_id) REFERENCES prisoner(prisoner_id),
    FOREIGN KEY (duty_id) REFERENCES duty(duty_id),
    FOREIGN KEY (admin_id) REFERENCES admin(admin_id)
);

-- -----------------------------------------------------------------------------
-- 9. PAROLE_EVALUATION
-- -----------------------------------------------------------------------------
CREATE TABLE parole_evaluation (
    evaluation_id INT AUTO_INCREMENT PRIMARY KEY,
    prisoner_id VARCHAR(10),
    admin_id INT,
    evaluation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    points_at_evaluation INT,
    decision VARCHAR(50),
    comments TEXT,
    FOREIGN KEY (prisoner_id) REFERENCES prisoner(prisoner_id),
    FOREIGN KEY (admin_id) REFERENCES admin(admin_id)
);

-- -----------------------------------------------------------------------------
-- 10. PAROLE_REQUESTS
-- -----------------------------------------------------------------------------
CREATE TABLE parole_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    prisoner_id VARCHAR(10),
    request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Reviewed') DEFAULT 'Pending',
    FOREIGN KEY (prisoner_id) REFERENCES prisoner(prisoner_id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- 11. VISITOR (optional lookup; visit log can store info inline)
-- -----------------------------------------------------------------------------
CREATE TABLE visitor (
    visitor_id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_name VARCHAR(100) NOT NULL,
    relation_to_prisoner VARCHAR(50),
    phone VARCHAR(20),
    id_proof VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------------------------------
-- 12. VISIT_LOG (with inline visitor fields for logging without adding visitor)
-- -----------------------------------------------------------------------------
CREATE TABLE visit_log (
    visit_id INT AUTO_INCREMENT PRIMARY KEY,
    prisoner_id VARCHAR(10) NOT NULL,
    visitor_id INT NULL,
    visitor_name VARCHAR(100) NULL,
    relation_to_prisoner VARCHAR(50) NULL,
    visitor_phone VARCHAR(20) NULL,
    visit_date DATE NOT NULL,
    duration_minutes INT DEFAULT 30,
    status ENUM('Scheduled', 'Completed', 'Cancelled', 'No-show') DEFAULT 'Scheduled',
    notes TEXT,
    logged_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prisoner_id) REFERENCES prisoner(prisoner_id) ON DELETE CASCADE,
    FOREIGN KEY (visitor_id) REFERENCES visitor(visitor_id) ON DELETE SET NULL,
    FOREIGN KEY (logged_by) REFERENCES admin(admin_id)
);

-- -----------------------------------------------------------------------------
-- 13. INCIDENT_REPORT
-- -----------------------------------------------------------------------------
CREATE TABLE incident_report (
    incident_id INT AUTO_INCREMENT PRIMARY KEY,
    prisoner_id VARCHAR(10) NOT NULL,
    admin_id INT NOT NULL,
    incident_type VARCHAR(100) NOT NULL,
    description TEXT,
    incident_date DATE NOT NULL,
    action_taken VARCHAR(255),
    severity ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prisoner_id) REFERENCES prisoner(prisoner_id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admin(admin_id)
);

-- -----------------------------------------------------------------------------
-- 14. ANNOUNCEMENT
-- -----------------------------------------------------------------------------
CREATE TABLE announcement (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    body TEXT,
    created_by INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin(admin_id)
);

-- -----------------------------------------------------------------------------
-- 15. ACTIVITY_LOG (optional)
-- -----------------------------------------------------------------------------
CREATE TABLE activity_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    role VARCHAR(20),
    action VARCHAR(100),
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =============================================================================
-- INITIAL DATA
-- =============================================================================

INSERT INTO user_account (username, password, role) VALUES ('admin', 'admin123', 'admin');
INSERT INTO admin (user_id, admin_name) VALUES (1, 'Chief Warden');

INSERT INTO duty (duty_name, required_hours_per_date) VALUES
('Kitchen Staff', 5),
('Laundry Service', 4),
('Library Assistant', 3),
('Cleaning / Janitor', 6),
('Gardening', 4);
