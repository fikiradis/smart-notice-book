<?php
require_once 'config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user-selected Section ID filter
$selected_section_id = $_GET['section_id'] ?? '';

$sections  = [];
$notices   = [];
$schedules = [];
$error     = null;

if (isset($pdo) && $pdo !== null) {
    try {
        // 1. Fetch all Departments and Sections for Dynamic Filter Dropdown
        $sections = $pdo->query("
            SELECT s.id, s.year, s.section_name, d.code as dept_code, d.name as dept_name 
            FROM sections s 
            JOIN departments d ON s.department_id = d.id 
            ORDER BY d.name ASC, s.year ASC, s.section_name ASC
        ")->fetchAll();

        // 2. Fetch Notices targeting Selected Section or General Notices
        if (!empty($selected_section_id)) {
            $stmtNotice = $pdo->prepare("
                SELECT n.*, u.full_name as author_name 
                FROM notices n 
                LEFT JOIN users u ON n.author_id = u.id 
                WHERE (n.target_section_id = :sec_id OR n.target_section_id IS NULL OR n.target_section_id = '')
                ORDER BY n.created_at DESC
            ");
            $stmtNotice->execute(['sec_id' => $selected_section_id]);
            $notices = $stmtNotice->fetchAll();
        } else {
            // Default: Fetch latest general notices
            $notices = $pdo->query("
                SELECT n.*, u.full_name as author_name 
                FROM notices n 
                LEFT JOIN users u ON n.author_id = u.id 
                ORDER BY n.created_at DESC 
                LIMIT 10
            ")->fetchAll();
        }

        // 3. Fetch Class Schedule if a section is selected
        if (!empty($selected_section_id)) {
            $stmtSched = $pdo->prepare("
                SELECT sch.*, 
                       c.course_code, c.course_title, 
                       u.full_name as instructor_name, 
                       r.room_number, r.building_name 
                FROM schedules sch
                JOIN courses c ON sch.course_id = c.id
                JOIN users u ON sch.instructor_id = u.id
                JOIN rooms r ON sch.room_id = r.id
                WHERE sch.section_id = :sec_id
                ORDER BY CASE sch.day_of_week 
                    WHEN 'Monday' THEN 1 
                    WHEN 'Tuesday' THEN 2 
                    WHEN 'Wednesday' THEN 3 
                    WHEN 'Thursday' THEN 4 
                    WHEN 'Friday' THEN 5 
                    WHEN 'Saturday' THEN 6 
                    ELSE 7 END, sch.start_time ASC
            ");
            $stmtSched->execute(['sec_id' => $selected_section_id]);
            $schedules = $stmtSched->fetchAll();
        }

    } catch (PDOException $e) {
        $error = "መረጃዎችን ማምጣት አልተቻለም፡ " . $e->getMessage();
    }
}

include_once 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8">

    <!-- Database Error Alert (If Any) -->
    <?php if (isset($error)): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-xl text-sm">
            <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Top Banner & Section Selector Filter -->
    <div class="bg-slate-900 rounded-2xl p-6 md:p-8 text-white mb-8 shadow-lg flex flex-col md:flex-row justify-between items-center gap-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">🎓 የተማሪዎች የመረጃና የትምህርት መርሃ-ግብር ፖርታል</h1>
            <p class="text-slate-400 text-sm mt-2">የክላስ መርሃ-ግብርዎን እና ትኩስ ማስታወቂያዎችን ለመመልከት ክፍልዎን ይምረጡ።</p>
        </div>
        
        <form method="GET" action="index.php" class="w-full md:w-auto flex flex-col sm:flex-row gap-2">
            <select name="section_id" required class="bg-slate-800 text-white text-sm border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-blue-500 w-full md:w-72">
                <option value="">-- ክፍል/Section ይምረጡ --</option>
                <?php 
                $grouped = [];
                foreach ($sections as $sec) {
                    $grouped[$sec['dept_name']][] = $sec;
                }
                foreach ($grouped as $dept_name => $sec_list): 
                ?>
                    <optgroup label="🏢 <?= htmlspecialchars($dept_name); ?>">
                        <?php foreach ($sec_list as $sec): ?>
                            <option value="<?= $sec['id']; ?>" <?= $selected_section_id == $sec['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($sec['dept_code']); ?> - Yr <?= htmlspecialchars($sec['year']); ?> (Sec <?= htmlspecialchars($sec['section_name']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl transition text-sm flex items-center justify-center gap-2 shadow-lg shadow-blue-500/20">
                <i class="fa-solid fa-filter"></i> አሳይ
            </button>
        </form>
    </div>

    <!-- Main Content Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Left Column: Schedule Table -->
        <div class="lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-days text-blue-600"></i> የክላስ መርሃ-ግብር (Class Schedule)
                </h2>
                <?php if ($selected_section_id): ?>
                    <span class="text-xs font-medium bg-blue-50 text-blue-700 px-3 py-1 rounded-full border border-blue-200">
                        የተመረጠው ክፍል መርሃ-ግብር
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($selected_section_id)): ?>
                <?php if (!empty($schedules)): ?>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-slate-600">
                                <thead class="text-xs uppercase bg-slate-100 text-slate-700 border-b border-slate-200">
                                    <tr>
                                        <th class="p-4">ቀን (Day)</th>
                                        <th class="p-4">ሰዓት (Time)</th>
                                        <th class="p-4">ኮርስ (Course)</th>
                                        <th class="p-4">መምህር (Instructor)</th>
                                        <th class="p-4">ክፍል (Room)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($schedules as $sch): ?>
                                        <tr class="hover:bg-slate-50/80 transition">
                                            <td class="p-4 font-bold text-slate-800">
                                                <span class="bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                                                    <?= htmlspecialchars($sch['day_of_week']); ?>
                                                </span>
                                            </td>
                                            <td class="p-4 text-slate-600 whitespace-nowrap font-medium">
                                                <i class="fa-regular fa-clock text-slate-400 mr-1"></i>
                                                <?= htmlspecialchars($sch['start_time']); ?> - <?= htmlspecialchars($sch['end_time']); ?>
                                            </td>
                                            <td class="p-4">
                                                <div class="font-bold text-blue-600"><?= htmlspecialchars($sch['course_code']); ?></div>
                                                <div class="text-xs text-slate-500"><?= htmlspecialchars($sch['course_title']); ?></div>
                                            </td>
                                            <td class="p-4 font-medium text-slate-700">
                                                <?= htmlspecialchars($sch['instructor_name']); ?>
                                            </td>
                                            <td class="p-4">
                                                <span class="bg-emerald-50 text-emerald-700 text-xs font-semibold px-2.5 py-1 rounded-md border border-emerald-200 whitespace-nowrap">
                                                    <?= htmlspecialchars($sch['building_name']); ?> (<?= htmlspecialchars($sch['room_number']); ?>)
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center">
                        <i class="fa-solid fa-calendar-xmark text-amber-500 text-3xl mb-2"></i>
                        <h3 class="text-sm font-bold text-amber-900">ምንም የተመዘገበ የክላስ መርሃ-ግብር አልተገኘም!</h3>
                        <p class="text-xs text-amber-700 mt-1">ለዚህ ክፍል እስካሁን የክላስ ሰሌዳ አልተዘጋጀም ወይም አልተመዘገበም።</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="bg-slate-50 border border-dashed border-slate-300 rounded-2xl p-10 text-center">
                    <i class="fa-solid fa-hand-pointer text-slate-400 text-3xl mb-3 animate-bounce"></i>
                    <h3 class="text-base font-bold text-slate-700">እባክዎን ክፍልዎን ይምረጡ</h3>
                    <p class="text-xs text-slate-500 mt-1">ከላይ ከሚገኘው ማውጫ ዲፓርትመንት እና ክፍልዎን በመምረጥ የክላስ መርሃ-ግብርዎን ይመልከቱ።</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: General & Section Notices -->
        <div class="space-y-6">
            <div class="border-b border-slate-200 pb-3 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-bullhorn text-amber-500"></i> ማስታወቂያዎች
                </h2>
                <span class="text-[10px] bg-amber-50 text-amber-700 font-semibold px-2.5 py-1 rounded-full border border-amber-200">
                    አጠቃላይ እና የክፍል
                </span>
            </div>

            <?php if (!empty($notices)): ?>
                <div class="space-y-4">
                    <?php foreach ($notices as $notice): ?>
                        <?php $isGeneral = empty($notice['target_section_id']); ?>
                        <div class="bg-white rounded-2xl p-5 border <?= $isGeneral ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200'; ?> shadow-sm hover:shadow-md transition relative overflow-hidden">
                            
                            <?php if ($isGeneral): ?>
                                <div class="absolute top-0 right-0 bg-amber-500 text-white text-[9px] font-bold px-2.5 py-0.5 rounded-bl-lg uppercase tracking-wider">
                                    የዩኒቨርሲቲው አጠቃላይ
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center justify-between mb-2 mt-1">
                                <span class="text-[10px] font-extrabold uppercase px-2.5 py-0.5 rounded-full <?= $isGeneral ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-100'; ?>">
                                    <?= htmlspecialchars($notice['category'] ?? ($isGeneral ? 'Academic Calendar' : 'Section Notice')); ?>
                                </span>
                                <span class="text-[11px] text-slate-400">
                                    <?= date('M d, Y', strtotime($notice['created_at'] ?? 'now')); ?>
                                </span>
                            </div>

                            <h3 class="font-bold text-slate-900 text-sm mb-2">
                                <?= htmlspecialchars($notice['title']); ?>
                            </h3>

                            <p class="text-xs text-slate-600 leading-relaxed mb-3">
                                <?= nl2br(htmlspecialchars($notice['content'])); ?>
                            </p>

                            <?php if (!empty($notice['attachment_path'])): ?>
                                <a href="<?= htmlspecialchars($notice['attachment_path']); ?>" target="_blank" 
                                   class="inline-flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 font-semibold bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-100 transition">
                                    <i class="fa-solid fa-paperclip"></i> አባሪ ፋይል/ካላንደር ይመልከቱ
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 text-center text-slate-500 text-xs">
                    ምንም የወጣ ማስታወቂያ የለም።
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php include_once 'includes/footer.php'; ?>