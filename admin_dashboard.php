<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

if ($db_available) {
    $stmt = $pdo->query('SELECT COUNT(*) AS total_notices FROM notices');
    $totalNotices = $stmt->fetch();

    $stmt = $pdo->query('SELECT COUNT(*) AS total_schedules FROM schedules');
    $totalSchedules = $stmt->fetch();
} else {
    $totalNotices = ['total_notices' => 0];
    $totalSchedules = ['total_schedules' => 0];
}
?>

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
  <div class="rounded-2xl bg-white p-6 shadow-sm">
    <h1 class="text-2xl font-semibold">Admin Dashboard</h1>
    <p class="mt-2 text-slate-600">Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>.</p>
  </div>

  <div class="mt-6 grid gap-6 md:grid-cols-2">
    <a href="manage_notices.php" class="rounded-2xl bg-sky-600 p-6 text-white shadow-sm hover:bg-sky-500">
      <h2 class="text-xl font-semibold">Manage Notices</h2>
      <p class="mt-2 text-sm text-sky-100">Create, view, and remove announcements.</p>
    </a>
    <a href="manage_schedule.php" class="rounded-2xl bg-emerald-600 p-6 text-white shadow-sm hover:bg-emerald-500">
      <h2 class="text-xl font-semibold">Manage Schedule</h2>
      <p class="mt-2 text-sm text-emerald-100">Add and protect class schedules from conflicts.</p>
    </a>
  </div>

  <div class="mt-6 grid gap-6 md:grid-cols-2">
    <div class="rounded-2xl bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold">Overview</h2>
      <p class="mt-2 text-sm text-slate-600">Total notices: <?= (int)$totalNotices['total_notices'] ?></p>
      <p class="mt-1 text-sm text-slate-600">Total schedules: <?= (int)$totalSchedules['total_schedules'] ?></p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold">Quick Links</h2>
      <ul class="mt-2 space-y-2 text-sm text-slate-600">
        <li><a href="index.php" class="text-sky-600 hover:underline">Public portal</a></li>
        <li><a href="logout.php" class="text-sky-600 hover:underline">Logout</a></li>
      </ul>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>