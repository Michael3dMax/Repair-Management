-- Attendance Management System Database Schema
-- Run this SQL script to set up the database manually

-- Create database
CREATE DATABASE IF NOT EXISTS attendance_system;
USE attendance_system;

-- Users table (for admin authentication)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'hr') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Students/Employees table
CREATE TABLE IF NOT EXISTS people (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(15),
    department VARCHAR(50),
    position VARCHAR(50),
    type ENUM('student', 'employee') DEFAULT 'student',
    status ENUM('active', 'inactive') DEFAULT 'active',
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Attendance records table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    date DATE NOT NULL,
    check_in_time TIME,
    check_out_time TIME,
    status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'present',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_person_date (person_id, date)
);

-- Departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    head_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (head_id) REFERENCES people(id) ON DELETE SET NULL
);

-- Leave requests table
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    leave_type ENUM('sick', 'vacation', 'personal', 'emergency') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT IGNORE INTO users (username, email, password, role) VALUES 
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Default password is 'admin123'

-- Insert default settings
INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES 
('working_hours_start', '09:00', 'Default start time for working hours'),
('working_hours_end', '17:00', 'Default end time for working hours'),
('late_threshold', '15', 'Minutes after which attendance is marked as late'),
('company_name', 'Attendance Management System', 'Company or organization name');

-- Sample data for demonstration (optional)
INSERT IGNORE INTO people (employee_id, first_name, last_name, email, phone, department, position, type) VALUES 
('EMP001', 'John', 'Doe', 'john.doe@example.com', '+1234567890', 'IT', 'Software Developer', 'employee'),
('EMP002', 'Jane', 'Smith', 'jane.smith@example.com', '+1234567891', 'HR', 'HR Manager', 'employee'),
('STU001', 'Alice', 'Johnson', 'alice.johnson@example.com', '+1234567892', 'Computer Science', 'Student', 'student'),
('STU002', 'Bob', 'Wilson', 'bob.wilson@example.com', '+1234567893', 'Engineering', 'Student', 'student');

-- Sample attendance data (optional)
INSERT IGNORE INTO attendance (person_id, date, check_in_time, status, created_by) VALUES 
(1, CURDATE(), '09:00:00', 'present', 1),
(2, CURDATE(), '09:15:00', 'late', 1),
(3, CURDATE(), '09:05:00', 'present', 1),
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '09:00:00', 'present', 1),
(2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '09:00:00', 'present', 1),
(3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), NULL, 'absent', 1);

-- Create indexes for better performance
CREATE INDEX idx_attendance_date ON attendance(date);
CREATE INDEX idx_attendance_person_date ON attendance(person_id, date);
CREATE INDEX idx_people_department ON people(department);
CREATE INDEX idx_people_status ON people(status);
CREATE INDEX idx_people_employee_id ON people(employee_id);

-- Show table information
SHOW TABLES;

-- Show user count
SELECT COUNT(*) as user_count FROM users;

-- Show people count
SELECT COUNT(*) as people_count FROM people;

-- Show attendance count
SELECT COUNT(*) as attendance_count FROM attendance;

COMMIT;