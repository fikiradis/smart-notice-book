<?php
require_once 'config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success = '';
$error   = '';

// --- AUTO-SEED SAMPLE DATA IF TABLES ARE EMPTY ---
if (isset($pdo) && $pdo !== null) {
    try {
        // Ensure tables exist for SQLite/MySQL
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS departments (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, code TEXT);
            CREATE TABLE IF NOT EXISTS sections (id INTEGER PRIMARY KEY AUTOINCREMENT, department_id INTEGER, year INTEGER, section_name TEXT);
            CREATE TABLE IF NOT EXISTS courses (id INTEGER PRIMARY KEY AUTOINCREMENT, course_code TEXT, course_title TEXT);
            CREATE TABLE IF NOT EXISTS rooms (id INTEGER PRIMARY KEY AUTOINCREMENT, room_number TEXT, building_name TEXT);
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
        ");

        // Seed Sections
        $secCount = $pdo->query("SELECT COUNT(*) FROM sections")->fetchColumn();
        if ($secCount == 0) {
            $pdo->exec("INSERT INTO departments (id, name, code) VALUES (1, 'Information Technology', 'IT')");
            $pdo->exec("INSERT INTO sections (department_id, year, section_name) VALUES (1, 3, 'A'), (1, 3, 'B'), (1, 4, 'A')");
        }

        // Seed Courses
        $courseCount = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
        if ($courseCount == 0) {
            $pdo->exec("INSERT INTO courses (course_code, course_title) VALUES 
                ('IT301', 'Web Programming'),
                ('IT302', 'Database Management'),
                ('IT303', 'Mobile App Development'),
                ('IT304', 'Software Engineering')");
        }

        // Seed Rooms
        $roomCount = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
        if ($roomCount == 0) {
            $pdo->exec("INSERT INTO rooms (room_number, building_name) VALUES 
                ('Lab 01', 'IT Building'),
                ('Lab 02', 'IT Building'),
                ('Room 104', 'Block 55'),
                ('Hall B', 'Main Campus')");
        }
    } catch (PDOException $e) {
        // Continue gracefully
    }
}

// --- FETCH DATA FOR DROPDOWNS ---
$sections    = [];
$courses     = [];
$instructors = [];
$rooms       = [];

if (isset($pdo) && $pdo !== null) {
    try {
        // Fetch Sections with Department Info for Grouping
        $sections = $pdo->query("SELECT s.id, s.year, s.section_name, d.code as dept_code, d.name as dept_name 
                                 FROM sections s 
                                 JOIN departments d ON s.department_id = d.id 
                                 ORDER BY d.name ASC, s.year ASC, s.section_name ASC")->fetchAll();

        // Fetch Courses
        $courses = $pdo->query("SELECT id, course_code, course_title FROM courses ORDER BY course_code")->fetchAll();

        // Fetch Instructors
        $instructors = $pdo->query("SELECT id, full_name FROM users WHERE role IN ('Instructor', 'Admin', 'DeptHead') ORDER BY full_name")->fetchAll();

        // Fetch Rooms
        $rooms = $pdo->query("SELECT id, room_number, building_name FROM rooms ORDER BY room_number")->fetchAll();

    } catch (PDOException $e) {
        $error = "መረጃዎችን ከ Database ማምጣት አልተቻለም፡ " . $e->getMessage();
    }
}

// --- SAVE SCHEDULE HANDLING WITH OVERLAP CHECK ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section_id    = $_POST['section_id'] ?? '';
    $course_id     = $_POST['course_id'] ?? '';
    $instructor_id = $_POST['instructor_id'] ?? '';
    $room_id       = $_POST['room_id'] ?? '';
    $day_of_week   = $_POST['day_of_week'] ?? '';
    $start_time    = $_POST['start_time'] ?? '';
    $end_time      = $_POST['end_time'] ?? '';

    if (empty($section_id) || empty($course_id) || empty($instructor_id) || empty($room_id) || empty($day_of_week) || empty($start_time) || empty($end_time)) {
        $error = "እባክዎን ሁሉንም አስፈላጊ መረጃዎች በትክክል ይምረጡ!";
    } elseif ($start_time >= $end_time) {
        $error = "የመጀመሪያው ሰዓት ከመደመደሚያው ሰዓት መቀደም አለበት!";
    } else {
        if (isset($pdo) && $pdo !== null) {
            try {
                // 1. Check for Room Overlap
                $stmtRoom = $pdo->prepare("
                    SELECT COUNT(*) FROM schedules 
                    WHERE room_id = :room_id 
                      AND day_of_week = :day_of_week 
                      AND (:start_time < end_time AND :end_time > start_time)
                ");
                $stmtRoom->execute([
                    'room_id'     => $room_id,
                    'day_of_week' => $day_of_week,
                    'start_time'  => $start_time,
                    'end_time'    => $end_time
                ]);

                if ($stmtRoom->fetchColumn() > 0) {
                    $error = "የተመረጠው የመማሪያ ክፍል በተሰጠው ሰዓትና ቀን በሌላ ክፍል ተይዟል!";
                } else {
                    // 2. Check for Instructor Overlap
                    $stmtInst = $pdo->prepare("
                        SELECT COUNT(*) FROM schedules 
                        WHERE instructor_id = :instructor_id 
                          AND day_of_week = :day_of_week 
                          AND (:start_time < end_time AND :end_time > start_time)
                    ");
                    $stmtInst->execute([
                        'instructor_id' => $instructor_id,
                        'day_of_week'   => $day_of_week,
                        'start_time'    => $start_time,
                        'end_time'      => $end_time
                    ]);

                    if ($stmtInst->fetchColumn() > 0) {
                        $error = "የተመረጡት መምህር በተሰጠው ሰዓትና ቀን በሌላ ክፍል ክፍለ-ጊዜ አለባቸው!";
                    } else {
                        // Insert schedule if no collision detected
                        $stmt = $pdo->prepare("INSERT INTO schedules (section_id, course_id, instructor_id, room_id, day_of_week, start_time, end_time) 
                                               VALUES (:section_id, :course_id, :instructor_id, :room_id, :day_of_week, :start_time, :end_time)");
                        $stmt->execute([
                            'section_id'    => $section_id,
                            'course_id'     => $course_id,
                            'instructor_id' => $instructor_id,
                            'room_id'       => $room_id,
                            'day_of_week'   => $day_of_week,
                            'start_time'    => $start_time,
                            'end_time'      => $end_time
                        ]);

                        $success = "የክላስ መርሃ-ግብሩ በትክክል ተመዝግቧል!";
                    }
                }
            } catch (PDOException $e) {
                $error = "መረጃውን መመዝገብ አልተቻለም፡ " . $e->getMessage();
            }
        }
    }
}

include_once 'includes/header.php';
?>

<div class="max-w-3xl mx-auto my-8">
    
    <!-- Top Navigation Breadcrumb -->
    <div class="flex items-center justify-between mb-6">
        <a href="dashboard.php" class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition flex items-center gap-1">
            <i class="fa-solid fa-arrow-left"></i> ወደ Dashboard ተመለስ
        </a>
        <span class="text-xs bg-blue-50 text-blue-700 font-semibold px-2.5 py-1 rounded-full border border-blue-200">
            Schedule Management Module
        </span>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        
        <div class="bg-slate-900 text-white p-6 border-b border-slate-800">
            <h2 class="text-lg font-bold flex items-center gap-2">
                <i class="fa-solid fa-calendar-plus text-blue-400"></i> አዲስ የክላስ መርሃግብር ማስገባት
            </h2>
            <p class="text-slate-400 text-xs mt-1">ለተወሰነ ክፍል የትምህርት ክፍለ-ጊዜ እና የመማሪያ ክፍል ይመድቡ</p>
        </div>

        <form method="POST" action="add_schedule.php" class="p-6 space-y-5">
            
            <?php if (!empty($success)): ?>
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                    <span><?= htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-xl flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-base"></i>
                    <span><?= htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Grid 1: Target Section & Course -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Target Section -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        ክፍል (Target Section) <span class="text-rose-500">*</span>
                    </label>
                    <select name="section_id" required class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- ክፍል ይምረጡ --</option>
                        <?php 
                        $grouped = [];
                        foreach ($sections as $sec) {
                            $grouped[$sec['dept_name']][] = $sec;
                        }
                        foreach ($grouped as $dept_name => $sec_list): 
                        ?>
                            <optgroup label="🏢 <?= htmlspecialchars($dept_name); ?>">
                                <?php foreach ($sec_list as $sec): ?>
                                    <option value="<?= $sec['id']; ?>">
                                        <?= htmlspecialchars($sec['dept_code']); ?> - Year <?= $sec['year']; ?> (Section <?= htmlspecialchars($sec['section_name']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Course -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        ኮርስ (Course) <span class="text-rose-500">*</span>
                    </label>
                    <select name="course_id" required class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- ኮርስ ይምረጡ --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['id']; ?>">
                                <?= htmlspecialchars($c['course_code']); ?> - <?= htmlspecialchars($c['course_title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <!-- Grid 2: Instructor & Room -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Instructor -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        መምህር (Instructor) <span class="text-rose-500">*</span>
                    </label>
                    <select name="instructor_id" required class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- መምህር ይምረጡ --</option>
                        <?php foreach ($instructors as $inst): ?>
                            <option value="<?= $inst['id']; ?>">
                                <?= htmlspecialchars($inst['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Room & Building -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        የመማሪያ ክፍል/ህፃን (Room & Building) <span class="text-rose-500">*</span>
                    </label>
                    <select name="room_id" required class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- ክፍል ይምረጡ --</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= $r['id']; ?>">
                                <?= htmlspecialchars($r['building_name']); ?> - <?= htmlspecialchars($r['room_number']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <!-- Grid 3: Day, Start Time, End Time -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Day -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        ቀን (Day) <span class="text-rose-500">*</span>
                    </label>
                    <select name="day_of_week" required class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500">
                        <option value="Monday">Monday (ሰኞ)</option>
                        <option value="Tuesday">Tuesday (ማክሰኞ)</option>
                        <option value="Wednesday">Wednesday (ረቡዕ)</option>
                        <option value="Thursday">Thursday (ሐሙስ)</option>
                        <option value="Friday">Friday (አርብ)</option>
                        <option value="Saturday">Saturday (ቅዳሜ)</option>
                    </select>
                </div>

                <!-- Start Time -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        የመጀመሪያ ሰዓት (Start Time) <span class="text-rose-500">*</span>
                    </label>
                    <input type="time" name="start_time" required class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- End Time -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        የመደመደሚያ ሰዓት (End Time) <span class="text-rose-500">*</span>
                    </label>
                    <input type="time" name="end_time" required class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>

            </div>

            <!-- Submit Button -->
            <div class="pt-3">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-blue-500/20 text-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-plus"></i> መርሃግብር መዝግብ (Save Schedule)
                </button>
            </div>

        </form>

    </div>
</div>

<?php include_once 'includes/footer.php'; ?>