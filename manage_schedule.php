<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$message = '';
$success = false;

if ($db_available) {
    $stmt = $pdo->query('SELECT c.id, c.course_code, c.course_title FROM courses c ORDER BY c.course_code');
    $courses = $stmt->fetchAll();

    $stmt = $pdo->query('SELECT u.id, u.full_name FROM users u WHERE u.role = "instructor" ORDER BY u.full_name');
    $instructors = $stmt->fetchAll();

    $stmt = $pdo->query('SELECT r.id, r.room_number FROM rooms r ORDER BY r.room_number');
    $rooms = $stmt->fetchAll();

    $stmt = $pdo->query('SELECT s.id, s.year, s.section_name FROM sections s ORDER BY s.year, s.section_name');
    $sections = $stmt->fetchAll();
} else {
    $courses = [['id' => 1, 'course_code' => 'CS101', 'course_title' => 'Intro to Programming']];
    $instructors = [['id' => 1, 'full_name' => 'Demo Instructor']];
    $rooms = [['id' => 1, 'room_number' => 'R101']];
    $sections = [['id' => 1, 'year' => 1, 'section_name' => 'A']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_schedule'])) {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $instructorId = (int)($_POST['instructor_id'] ?? 0);
    $roomId = (int)($_POST['room_id'] ?? 0);
    $sectionId = (int)($_POST['section_id'] ?? 0);
    $dayOfWeek = $_POST['day_of_week'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';

    if ($db_available) {
        $conflictStmt = $pdo->prepare('SELECT id FROM schedules WHERE room_id = :room_id AND day_of_week = :day_of_week AND ((start_time < :end_time AND end_time > :start_time))');
        $conflictStmt->execute([':room_id' => $roomId, ':day_of_week' => $dayOfWeek, ':start_time' => $startTime, ':end_time' => $endTime]);
        $roomConflict = $conflictStmt->fetch();

        $sectionConflictStmt = $pdo->prepare('SELECT id FROM schedules WHERE section_id = :section_id AND day_of_week = :day_of_week AND ((start_time < :end_time AND end_time > :start_time))');
        $sectionConflictStmt->execute([':section_id' => $sectionId, ':day_of_week' => $dayOfWeek, ':start_time' => $startTime, ':end_time' => $endTime]);
        $sectionConflict = $sectionConflictStmt->fetch();

        if ($roomConflict || $sectionConflict) {
            $message = 'Conflict Detected! Room/Section is busy.';
        } else {
            $insertStmt = $pdo->prepare('INSERT INTO schedules (course_id, instructor_id, room_id, section_id, day_of_week, start_time, end_time) VALUES (:course_id, :instructor_id, :room_id, :section_id, :day_of_week, :start_time, :end_time)');
            $insertStmt->execute([
                ':course_id' => $courseId,
                ':instructor_id' => $instructorId,
                ':room_id' => $roomId,
                ':section_id' => $sectionId,
                ':day_of_week' => $dayOfWeek,
                ':start_time' => $startTime,
                ':end_time' => $endTime,
            ]);
            $message = 'Schedule saved successfully.';
            $success = true;
        }
    } else {
        $message = 'Demo mode is active. The schedule was not stored because the database is unavailable.';
        $success = true;
    }
}

if ($db_available) {
    $stmt = $pdo->query('SELECT s.id, s.day_of_week, s.start_time, s.end_time, c.course_code, c.course_title, sec.year, sec.section_name, r.room_number, u.full_name AS instructor_name FROM schedules s JOIN courses c ON c.id = s.course_id JOIN sections sec ON sec.id = s.section_id JOIN rooms r ON r.id = s.room_id JOIN users u ON u.id = s.instructor_id ORDER BY FIELD(s.day_of_week, "Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"), s.start_time');
    $schedules = $stmt->fetchAll();
} else {
    $schedules = [['day_of_week' => 'Monday', 'start_time' => '09:00:00', 'end_time' => '10:30:00', 'course_title' => 'Intro to Programming', 'year' => 1, 'section_name' => 'A', 'room_number' => 'R101', 'instructor_name' => 'Demo Instructor']];
}
?>

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
  <div class="rounded-2xl bg-white p-6 shadow-sm">
    <h1 class="text-2xl font-semibold">Manage Schedule</h1>
    <p class="mt-2 text-slate-600">Create class schedules and prevent conflicts automatically.</p>
  </div>

  <?php if ($message !== ''): ?>
    <div class="mt-4 rounded border px-3 py-2 text-sm <?= $success ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' ?>">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="rounded-2xl bg-white p-6 shadow-sm">
      <h2 class="text-xl font-semibold">Add New Schedule</h2>
      <form method="post" class="mt-4 space-y-4">
        <input type="hidden" name="save_schedule" value="1">
        <div>
          <label class="mb-1 block text-sm font-medium" for="course_id">Course</label>
          <select id="course_id" name="course_id" required class="w-full rounded border border-slate-300 px-3 py-2">
            <?php foreach ($courses as $course): ?>
              <option value="<?= (int)$course['id'] ?>"><?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium" for="instructor_id">Instructor</label>
          <select id="instructor_id" name="instructor_id" required class="w-full rounded border border-slate-300 px-3 py-2">
            <?php foreach ($instructors as $instructor): ?>
              <option value="<?= (int)$instructor['id'] ?>"><?= htmlspecialchars($instructor['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium" for="room_id">Room</label>
          <select id="room_id" name="room_id" required class="w-full rounded border border-slate-300 px-3 py-2">
            <?php foreach ($rooms as $room): ?>
              <option value="<?= (int)$room['id'] ?>"><?= htmlspecialchars($room['room_number']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium" for="section_id">Section</label>
          <select id="section_id" name="section_id" required class="w-full rounded border border-slate-300 px-3 py-2">
            <?php foreach ($sections as $section): ?>
              <option value="<?= (int)$section['id'] ?>"><?= htmlspecialchars($section['year'] . ' ' . $section['section_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium" for="day_of_week">Day</label>
            <select id="day_of_week" name="day_of_week" required class="w-full rounded border border-slate-300 px-3 py-2">
              <option>Monday</option>
              <option>Tuesday</option>
              <option>Wednesday</option>
              <option>Thursday</option>
              <option>Friday</option>
              <option>Saturday</option>
              <option>Sunday</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium" for="start_time">Start Time</label>
            <input id="start_time" name="start_time" type="time" required class="w-full rounded border border-slate-300 px-3 py-2">
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium" for="end_time">End Time</label>
            <input id="end_time" name="end_time" type="time" required class="w-full rounded border border-slate-300 px-3 py-2">
          </div>
        </div>
        <button class="w-full rounded bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-500">Save Schedule</button>
      </form>
    </section>

    <section class="rounded-2xl bg-white p-6 shadow-sm">
      <h2 class="text-xl font-semibold">Current Schedules</h2>
      <?php if (empty($schedules)): ?>
        <p class="mt-4 text-sm text-slate-500">No schedules added yet.</p>
      <?php else: ?>
        <div class="mt-4 space-y-3">
          <?php foreach ($schedules as $schedule): ?>
            <div class="rounded-xl border border-slate-200 p-4">
              <p class="font-semibold text-slate-800"><?= htmlspecialchars($schedule['course_title']) ?></p>
              <p class="mt-1 text-sm text-slate-600">
                <?= htmlspecialchars($schedule['day_of_week']) ?> <?= substr($schedule['start_time'], 0, 5) ?>-<?= substr($schedule['end_time'], 0, 5) ?> • <?= htmlspecialchars($schedule['room_number']) ?> • <?= htmlspecialchars($schedule['year'] . ' ' . $schedule['section_name']) ?>
              </p>
              <p class="mt-1 text-sm text-slate-600">Instructor: <?= htmlspecialchars($schedule['instructor_name']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>