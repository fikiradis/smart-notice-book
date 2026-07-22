<?php
$host     = '127.0.0.1';
$db       = 'smart_noticeboard';
$user     = 'root';
$pass     = '';
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    try {
        $sqlite_file = __DIR__ . '/database.sqlite';
        $pdo = new PDO("sqlite:" . $sqlite_file, null, null, $options);
    } catch (\PDOException $ex) {
        $pdo = null;
    }
}

if ($pdo) {
    // Create Tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS departments (
            id INTEGER PRIMARY KEY AUTOINCREMENT, 
            name TEXT NOT NULL, 
            code TEXT UNIQUE NOT NULL
        );
        CREATE TABLE IF NOT EXISTS sections (
            id INTEGER PRIMARY KEY AUTOINCREMENT, 
            department_id INTEGER, 
            year INTEGER NOT NULL, 
            section_name TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT, 
            full_name TEXT, 
            email TEXT UNIQUE, 
            password TEXT, 
            role TEXT
        );
        CREATE TABLE IF NOT EXISTS courses (
            id INTEGER PRIMARY KEY AUTOINCREMENT, 
            department_id INTEGER,
            course_code TEXT, 
            course_title TEXT
        );
        CREATE TABLE IF NOT EXISTS rooms (
            id INTEGER PRIMARY KEY AUTOINCREMENT, 
            room_number TEXT, 
            building_name TEXT
        );
        CREATE TABLE IF NOT EXISTS schedules (
            id INTEGER PRIMARY KEY AUTOINCREMENT, 
            section_id INTEGER, 
            course_id INTEGER, 
            instructor_id INTEGER, 
            room_id INTEGER, 
            day_of_week TEXT, 
            start_time TEXT, 
            end_time TEXT
        );
        CREATE TABLE IF NOT EXISTS notices (
            id INTEGER PRIMARY KEY AUTOINCREMENT, 
            title TEXT, 
            content TEXT, 
            category TEXT, 
            author_id INTEGER, 
            target_section_id INTEGER, 
            attachment_path TEXT, 
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Check Department Count
    $deptCount = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
    
    // If only 1 department exists (or none), Force Refresh to 28 Departments
    if ($deptCount <= 1) {
        // Clear existing partial records
        $pdo->exec("DELETE FROM departments");
        $pdo->exec("DELETE FROM sections");

        $all_departments = [
            ['Information Technology', 'IT'],
            ['Computer Science', 'CS'],
            ['Software Engineering', 'SE'],
            ['Information Systems', 'IS'],
            ['Electrical & Computer Engineering', 'ECE'],
            ['Civil Engineering', 'CE'],
            ['Mechanical Engineering', 'ME'],
            ['Chemical Engineering', 'ChE'],
            ['Biomedical Engineering', 'BME'],
            ['Architecture', 'ARCH'],
            ['Chemistry', 'CHEM'],
            ['Biology', 'BIOL'],
            ['Physics', 'PHYS'],
            ['Mathematics', 'MATH'],
            ['Statistics', 'STAT'],
            ['Medicine', 'MED'],
            ['Nursing', 'NURS'],
            ['Pharmacy', 'PHARM'],
            ['Public Health', 'PH'],
            ['Medical Laboratory Science', 'MLS'],
            ['Accounting & Finance', 'AcFn'],
            ['Management', 'MGMT'],
            ['Economics', 'ECON'],
            ['Marketing Management', 'MKTG'],
            ['Law', 'LAW'],
            ['English Language & Literature', 'ENG'],
            ['Sociology', 'SOC'],
            ['Psychology', 'PSY']
        ];

        $stmtDept = $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
        $stmtSec  = $pdo->prepare("INSERT INTO sections (department_id, year, section_name) VALUES (?, ?, ?)");

        foreach ($all_departments as $dept) {
            $stmtDept->execute([$dept[0], $dept[1]]);
            $dept_id = $pdo->lastInsertId();

            // Insert 1st, 2nd, 3rd, and 4th Year Sections (Sec A & B) for EVERY department
            for ($y = 1; $y <= 4; $y++) {
                $stmtSec->execute([$dept_id, $y, 'A']);
                $stmtSec->execute([$dept_id, $y, 'B']);
            }
        }
    }

    // Seed Admin User
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        $hashed = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (full_name, email, password, role) VALUES ('University Admin', 'admin@univ.edu', '$hashed', 'Admin')");
    }
}
?>