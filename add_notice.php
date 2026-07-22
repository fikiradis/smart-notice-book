<?php
require_once 'config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$error = '';

// Fetch sections for optional filtering
$sections = [];
if (isset($pdo)) {
    $sections = $pdo->query("SELECT s.id, s.year, s.section_name, d.code as dept_code FROM sections s JOIN departments d ON s.department_id = d.id")->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title           = trim($_POST['title']);
    $content         = trim($_POST['content']);
    $target_audience = $_POST['target_audience']; // 'all', 'students', 'instructors'
    $section_id      = !empty($_POST['section_id']) ? $_POST['section_id'] : null;
    $author_id       = $_SESSION['user_id'] ?? 1; // Default to 1 for test

    if (!empty($title) && !empty($content)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notices (title, content, target_audience, target_section_id, author_id, created_at) 
                VALUES (:title, :content, :target_audience, :section_id, :author_id, NOW())
            ");
            $stmt->execute([
                'title'           => $title,
                'content'         => $content,
                'target_audience' => $target_audience,
                'section_id'      => $section_id,
                'author_id'       => $author_id
            ]);
            $message = "ማስታወቂያው በጥሩ ሁኔታ ተለጥፏል!";
        } catch (PDOException $e) {
            $error = "ስህተት ተከሰቷል፡ " . $e->getMessage();
        }
    } else {
        $error = "እባክዎን ርዕስ እና ይዘት ያስገቡ!";
    }
}

include_once 'includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
        <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
            <i class="fa-solid fa-pen-to-square text-blue-600"></i> አዲስ ማስታወቂያ መለጠፊያ
        </h2>

        <?php if ($message): ?>
            <div class="bg-emerald-50 text-emerald-700 p-4 rounded-xl border border-emerald-200 text-sm mb-4">
                <?= $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-rose-50 text-rose-700 p-4 rounded-xl border border-rose-200 text-sm mb-4">
                <?= $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_notice.php" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">የማስታወቂያው ርዕስ (Title)</label>
                <input type="text" name="title" required class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500">
            </div>

            <!-- Target Audience Selector -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">ማስታወቂያው ለማን ይታይ? (Target Audience)</label>
                <select name="target_audience" required class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 bg-white">
                    <option value="all">🌐 ለሁለቱም (ለተማሪዎች እና ለተማራን/መምህራን)</option>
                    <option value="students">🎓 ለተማሪዎች ብቻ (Students Only)</option>
                    <option value="instructors">👨‍🏫 ለተማራን/መምህራን ብቻ (Instructors Only)</option>
                </select>
            </div>

            <!-- Optional Specific Section -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">ለተወሰነ ክፍል ብቻ ከሆነ (Optional Section Filter)</label>
                <select name="section_id" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500 bg-white">
                    <option value="">-- ለሁሉም ክፍሎች (General Notice) --</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= $sec['id']; ?>">
                            <?= htmlspecialchars($sec['dept_code']); ?> - Year <?= $sec['year']; ?> (Sec <?= htmlspecialchars($sec['section_name']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">የማስታወቂያው ይዘት (Content)</label>
                <textarea name="content" rows="5" required class="w-full border border-slate-300 rounded-xl p-4 text-sm focus:outline-none focus:border-blue-500"></textarea>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition text-sm">
                ማስታወቂያውን ለጥፍ (Post Notice)
            </button>
        </form>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>