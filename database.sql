-- Create the Database
DROP DATABASE IF EXISTS jdbms_db;
CREATE DATABASE jdbms_db;
USE jdbms_db;

-- 1. USER_ACCOUNT Table (Login Info)
CREATE TABLE user_account (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'prisoner') NOT NULL
);

-- 2. ADMIN Table
CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    admin_name VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES user_account(user_id) ON DELETE CASCADE
);

-- 3. PRISONER Table (With full personal details)
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

-- 4. CRIME Table
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

-- 5. SENTENCE Table
CREATE TABLE sentence (
    sentence_id INT AUTO_INCREMENT PRIMARY KEY,
    prisoner_id VARCHAR(10), 
    sentence_type VARCHAR(50),
    start_date DATE,
    end_date DATE,
    duration_in_months INT,
    parole_eligibility BOOLEAN DEFAULT 0,
    FOREIGN KEY (prisoner_id) REFERENCES prisoner(prisoner_id) ON DELETE CASCADE
);

-- 6. BEHAVIOR_RECORD Table
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

-- 7. DUTY Table (List of available jobs)
CREATE TABLE duty (
    duty_id INT AUTO_INCREMENT PRIMARY KEY,
    duty_name VARCHAR(50),
    required_hours_per_date INT
);

-- 8. DUTY_ASSIGNMENT Table
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

-- 9. PAROLE_EVALUATION Table
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

-- 10. PAROLE_REQUESTS Table
CREATE TABLE parole_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    prisoner_id VARCHAR(10), 
    request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Reviewed') DEFAULT 'Pending',
    FOREIGN KEY (prisoner_id) REFERENCES prisoner(prisoner_id) ON DELETE CASCADE
);

-- --- INSERT INITIAL DATA ---

-- 1. Create Default Admin Account
INSERT INTO user_account (username, password, role) VALUES ('admin', 'admin123', 'admin');
INSERT INTO admin (user_id, admin_name) VALUES (1, 'Chief Warden');

-- 2. Create Standard Duties
INSERT INTO duty (duty_name, required_hours_per_date) VALUES 
('Kitchen Staff', 5), 
('Laundry Service', 4), 
('Library Assistant', 3), 
('Cleaning / Janitor', 6), 
('Gardening', 4);