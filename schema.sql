-- Smart Department Noticeboard & Class Scheduler Database Schema
-- Target Engine: MySQL 8.0+ / MariaDB

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notices;
DROP TABLE IF EXISTS schedules;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS sections;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Departments Table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Sections Table
CREATE TABLE sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    section_name VARCHAR(10) NOT NULL,
    department_id INT NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Users Table (Admin & Instructors)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'instructor') NOT NULL DEFAULT 'instructor',
    department_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Courses Table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_title VARCHAR(150) NOT NULL,
    department_id INT NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Rooms Table
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    building_name VARCHAR(50) NOT NULL,
    capacity INT DEFAULT 50
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Schedules Table
CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    instructor_id INT NOT NULL,
    room_id INT NOT NULL,
    section_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Notices Table
CREATE TABLE notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    category ENUM('Exam', 'Assignment', 'Make-up Class', 'General') NOT NULL DEFAULT 'General',
    target_section_id INT NULL, -- NULL means all sections in department
    author_id INT NOT NULL,
    attachment_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (target_section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- SAMPLE SEED DATA FOR TESTING
-- ================================================= realm
-- Default Password for all demo accounts: "password123"
-- Hash generated using PHP password_hash('password123', PASSWORD_DEFAULT)
-- ========================================================

-- Insert Department
INSERT INTO departments (id, name, code) VALUES 
(1, 'Information Technology', 'IT');

-- Insert Sections
INSERT INTO sections (id, year, section_name, department_id) VALUES 
(1, 3, 'Section A', 1),
(2, 3, 'Section B', 1);

-- Insert Users (1 Admin, 2 Instructors)
-- Password for all is: password123
INSERT INTO users (id, full_name, email, password_hash, role, department_id) VALUES 
(1, 'Dept Admin', 'admin@univ.edu.et', '$2y$10$44.fMUnA0fCq/k8f1B1.oONe.CqK3YV/1Kq4CGBX9Zz8XpM1X1/3O', 'admin', 1),
(2, 'Abebe Bikila', 'abebe@univ.edu.et', '$2y$10$44.fMUnA0fCq/k8f1B1.oONe.CqK3YV/1Kq4CGBX9Zz8XpM1X1/3O', 'instructor', 1),
(3, 'Kebede Kassaye', 'kebede@univ.edu.et', '$2y$10$44.fMUnA0fCq/k8f1B1.oONe.CqK3YV/1Kq4CGBX9Zz8XpM1X1/3O', 'instructor', 1);

-- Insert Courses
INSERT INTO courses (id, course_code, course_title, department_id) VALUES 
(1, 'IT301', 'Web Programming', 1),
(2, 'IT302', 'Database Systems', 1);

-- Insert Rooms
INSERT INTO rooms (id, room_number, building_name, capacity) VALUES 
(1, 'Room 204', 'Block 42', 60),
(2, 'Lab 02', 'IT Building', 40);

-- Insert Sample Schedules
INSERT INTO schedules (course_id, instructor_id, room_id, section_id, day_of_week, start_time, end_time) VALUES 
(1, 2, 1, 1, 'Wednesday', '08:30:00', '10:30:00'),
(2, 3, 2, 1, 'Wednesday', '10:30:00', '12:30:00');

-- Insert Sample Notices
INSERT INTO notices (title, content, category, target_section_id, author_id) VALUES 
('Mid-Exam Rescheduled', 'The Web Programming mid-exam is shifted to Friday at 2:00 PM.', 'Exam', 1, 2),
('Assignment 1 Release', 'Assignment 1 for Database Systems has been published.', 'Assignment', 1, 3);