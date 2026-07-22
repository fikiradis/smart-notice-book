<?php
require_once 'config/db.php';
session_start();

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$notice_msg = '';

// Delete Notice Handling
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM notices WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);
            $notice_msg = "ማስታወቂያው በትክክል ተሰርዟል!";
        } catch (PDOException $e) {
            $notice_msg = "ማስታወቂያውን ማጥፋት አልተቻለም፡ " . $e->getMessage();
        }
    }
}

// Fetch All Notices
$notices = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT n.*, u.full_name as author_name, s.year, s.section_name 
                             FROM notices n
                             JOIN users u ON n.author_id = u.id
                             LEFT JOIN sections s ON n.target_section_id = s.id
                             ORDER BY n.created_at DESC");
        $notices = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Fallback
    }
}

include_once 'includes/header.php';
?>

<div class="max-w-6xl mx-auto my-8">
    
    <!-- Top Navigation Breadcrumb -->
    <div class="flex items-center justify-between mb-6">
        <a href="dashboard.php" class="text-xs font-semibold text-slate-500 hover:text-slate-800 transition flex items-center gap-1">
            <i class="fa-solid fa-arrow-left"></i> ወደ Dashboard ተመለስ
        </a>
        <a href="post_notice.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition shadow-md shadow-blue-500/20 flex items-center gap-1.5">
            <i class="fa-solid fa-plus"></i> አዲስ ማስታወቂያ
        </a>
    </div>

    <?php if (!empty($notice_msg)): ?>
        <div class="p-4 mb-6 bg-slate-900 border border-slate-700 text-amber-400 text-xs rounded-xl flex items-center gap-2">
            <i class="fa-solid fa-circle-info text-amber-400 text-base"></i>
            <span><?= htmlspecialchars($notice_msg); ?></span>
        </div>
    <?php endif; ?>

    <!-- Main Table Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        
        <div class="bg-slate-900 text-white p-6 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-blue-400"></i> የማስታወቂያዎች ማኔጅመንት (Notice Board Control)
                </h2>
                <p class="text-slate-400 text-xs mt-1">የወጡ ማስታወቂያዎችን ዝርዝር ይቆጣጠሩ ወይም ያጥፉ</p>
            </div>
            <span class="bg-slate-800 text-slate-300 text-xs px-3 py-1 rounded-full border border-slate-700">
                ጠቅላላ፡ <?= count($notices); ?>
            </span>
        </div>

        <?php if (empty($notices)): ?>
            <div class="p-12 text-center text-slate-500">
                <i class="fa-regular fa-folder-open text-4xl mb-3 text-slate-300"></i>
                <p class="text-sm font-medium">ምንም ማስታወቂያ አልተገኘም።</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 uppercase font-semibold border-b border-slate-200">
                        <tr>
                            <th class="p-4">Title & Details</th>
                            <th class="p-4">Category</th>
                            <th class="p-4">Target Section</th>
                            <th class="p-4">Author</th>
                            <th class="p-4">Attachment</th>
                            <th class="p-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($notices as $n): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="p-4">
                                    <div class="font-bold text-slate-900 text-sm mb-1"><?= htmlspecialchars($n['title']); ?></div>
                                    <p class="text-slate-500 text-xs line-clamp-1 max-w-md"><?= htmlspecialchars($n['content']); ?></p>
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        <?= htmlspecialchars($n['category']); ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <?php if ($n['target_section_id']): ?>
                                        <span class="text-blue-600 font-medium">Year <?= $n['year']; ?> (Sec <?= htmlspecialchars($n['section_name']); ?>)</span>
                                    <?php else: ?>
                                        <span class="text-slate-400 font-medium">ለሁሉም ክፍሎች</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 font-medium text-slate-700"><?= htmlspecialchars($n['author_name']); ?></td>
                                <td class="p-4">
                                    <?php if (!empty($n['attachment_path'])): ?>
                                        <a href="<?= htmlspecialchars($n['attachment_path']); ?>" target="_blank" class="inline-flex items-center gap-1 text-blue-600 hover:underline font-semibold">
                                            <i class="fa-solid fa-paperclip"></i> ፋይል ተመልከት
                                        </a>
                                    <?php else: ?>
                                        <span class="text-slate-300">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <a href="manage_notices.php?action=delete&id=<?= $n['id']; ?>" 
                                       onclick="return confirm('እርግጠኛ ነዎት ይህ ማስታወቂያ እንዲጠፋ ይፈልጋሉ?');"
                                       class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg transition inline-block">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include_once 'includes/footer.php'; ?>