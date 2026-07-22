<?php
require_once 'config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['full_name'] ?? 'Staff Member';
$user_role = $_SESSION['role'] ?? 'Instructor';

// Default Stats Counters
$counts = [
    'courses'    => 0,
    'sections'   => 0,
    'notices'    => 0,
    'instructors'=> 0
];

$recent_notices = [];

if ($pdo) {
    try {
        // Fetch Counts
        $counts['courses']     = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
        $counts['sections']    = $pdo->query("SELECT COUNT(*) FROM sections")->fetchColumn();
        $counts['notices']     = $pdo->query("SELECT COUNT(*) FROM notices")->fetchColumn();
        $counts['instructors'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Instructor'")->fetchColumn();

        // Fetch Recent Notices
        $stmtN = $pdo->query("SELECT n.*, u.full_name as author_name, s.year, s.section_name 
                              FROM notices n
                              JOIN users u ON n.author_id = u.id
                              LEFT JOIN sections s ON n.target_section_id = s.id
                              ORDER BY n.created_at DESC LIMIT 5");
        $recent_notices = $stmtN->fetchAll();
    } catch (PDOException $e) {
        // Fallback gracefully
    }
}

include_once 'includes/header.php';
?>

<!-- Welcome Banner -->
<div class="bg-slate-900 text-white rounded-2xl p-6 md:p-8 mb-8 shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
    <div>
        <div class="inline-flex items-center gap-2 bg-slate-800 text-blue-400 text-xs font-semibold px-3 py-1 rounded-full mb-3 border border-slate-700">
            <i class="fa-solid fa-user-shield"></i> <?= htmlspecialchars($user_role); ?> Access
        </div>
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">
            እንኳን ደህና መጡ፣ <?= htmlspecialchars($user_name); ?>! 👋
        </h1>
        <p class="text-slate-400 text-sm mt-1">
            Department Operations & Notice Control Panel
        </p>
    </div>

    <div class="flex items-center gap-3">
        <a href="post_notice.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm px-4 py-2.5 rounded-xl transition shadow-lg shadow-blue-500/20 flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> አዲስ ማስታወቂያ አውጣ
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500 uppercase">Courses</span>
            <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-book"></i></span>
        </div>
        <p class="text-2xl font-bold text-slate-800"><?= $counts['courses']; ?></p>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500 uppercase">Sections</span>
            <span class="p-2 bg-indigo-50 text-indigo-600 rounded-lg text-sm"><i class="fa-solid fa-users"></i></span>
        </div>
        <p class="text-2xl font-bold text-slate-800"><?= $counts['sections']; ?></p>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500 uppercase">Notices</span>
            <span class="p-2 bg-amber-50 text-amber-600 rounded-lg text-sm"><i class="fa-solid fa-bullhorn"></i></span>
        </div>
        <p class="text-2xl font-bold text-slate-800"><?= $counts['notices']; ?></p>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-500 uppercase">Instructors</span>
            <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg text-sm"><i class="fa-solid fa-user-tie"></i></span>
        </div>
        <p class="text-2xl font-bold text-slate-800"><?= $counts['instructors']; ?></p>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    
    <!-- Recent Notices Table (8 cols) -->
    <div class="lg:col-span-8 bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-blue-600"></i> በቅርብ የወጡ ማስታወቂያዎች
            </h2>
            <a href="manage_notices.php" class="text-xs font-semibold text-blue-600 hover:underline">ሁሉንም ተመልከት →</a>
        </div>

        <?php if (empty($recent_notices)): ?>
            <div class="p-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200 text-slate-500">
                <i class="fa-regular fa-folder-open text-3xl mb-2 text-slate-300"></i>
                <p class="text-sm">ምንም ማስታወቂያ አልተለጠፈም።</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 uppercase font-semibold border-b border-slate-200">
                        <tr>
                            <th class="p-3">Title</th>
                            <th class="p-3">Category</th>
                            <th class="p-3">Author</th>
                            <th class="p-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($recent_notices as $n): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="p-3 font-semibold text-slate-800"><?= htmlspecialchars($n['title']); ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200 font-medium">
                                        <?= htmlspecialchars($n['category']); ?>
                                    </span>
                                </td>
                                <td class="p-3"><?= htmlspecialchars($n['author_name']); ?></td>
                                <td class="p-3 text-slate-400"><?= date('M d, Y', strtotime($n['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Shortcuts (4 cols) -->
    <div class="lg:col-span-4 space-y-4">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-bolt text-amber-500"></i> ፈጣን አቋራጮች (Quick Actions)
            </h2>
            <div class="space-y-2.5">
                <a href="post_notice.php" class="w-full flex items-center justify-between p-3 bg-slate-50 hover:bg-blue-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 hover:text-blue-700 transition">
                    <span class="flex items-center gap-2"><i class="fa-solid fa-pen-to-square text-blue-600"></i> አዲስ ማስታወቂያ መለቀቅ</span>
                    <i class="fa-solid fa-chevron-right text-slate-400"></i>
                </a>

                <a href="add_schedule.php" class="w-full flex items-center justify-between p-3 bg-slate-50 hover:bg-blue-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 hover:text-blue-700 transition">
                    <span class="flex items-center gap-2"><i class="fa-solid fa-calendar-plus text-indigo-600"></i> የክላስ መርሃግብር ማስገባት</span>
                    <i class="fa-solid fa-chevron-right text-slate-400"></i>
                </a>

                <a href="index.php" target="_blank" class="w-full flex items-center justify-between p-3 bg-slate-50 hover:bg-emerald-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 hover:text-emerald-700 transition">
                    <span class="flex items-center gap-2"><i class="fa-solid fa-eye text-emerald-600"></i> የተማሪዎችን ፖርታል ማየት</span>
                    <i class="fa-solid fa-arrow-up-right-from-square text-slate-400"></i>
                </a>
            </div>
        </div>
    </div>

</div>

<?php include_once 'includes/footer.php'; ?>