<?php
require_once 'database.php';

function setupDatabase() {
    $database = new Database();
    
    // Create database
    $database->createDatabase();
    
    // Connect to database
    $conn = $database->connect();
    
    if (!$conn) {
        die("Database connection failed");
    }
    
    // SQL for creating tables
    $sql = "
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
    ";
    
    try {
        $conn->exec($sql);
        
        // Insert default admin user
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT IGNORE INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@example.com', $defaultPassword, 'admin']);
        
        // Insert default settings
        $defaultSettings = [
            ['working_hours_start', '09:00', 'Default start time for working hours'],
            ['working_hours_end', '17:00', 'Default end time for working hours'],
            ['late_threshold', '15', 'Minutes after which attendance is marked as late'],
            ['company_name', 'Attendance Management System', 'Company or organization name']
        ];
        
        $stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
        foreach ($defaultSettings as $setting) {
            $stmt->execute($setting);
        }
        
        echo "Database setup completed successfully!";
        
    } catch(PDOException $e) {
        echo "Error setting up database: " . $e->getMessage();
    }
}

// Run setup if called directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    setupDatabase();
}
?>