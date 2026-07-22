<?php
// Ensure database connection is included properly
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

// Fetch Departments & Sections for Dynamic Target Selection
$departments = [];
$sections    = [];

if (isset($pdo) && $pdo !== null) {
    try {
        // Fetch all departments
        $departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();

        // Fetch sections grouped with departments
        $stmtSec = $pdo->query("SELECT s.id, s.year, s.section_name, s.department_id, d.code as dept_code, d.name as dept_name 
                                FROM sections s 
                                JOIN departments d ON s.department_id = d.id 
                                ORDER BY d.name ASC, s.year ASC, s.section_name ASC");
        $sections = $stmtSec->fetchAll();
    } catch (PDOException $e) {
        $error = "የክፍሎችን መረጃ ማምጣት አልተቻለም፡ " . $e->getMessage();
    }
} else {
    $error = "ከ Database ጋር መገናኘት አልተቻለም! እባክዎን config/db.php ን ያረጋግጡ።";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title             = trim($_POST['title'] ?? '');
    $category          = trim($_POST['category'] ?? 'General');
    $content           = trim($_POST['content'] ?? '');
    $target_section_id = !empty($_POST['target_section_id']) ? $_POST['target_section_id'] : null;
    $author_id         = $_SESSION['user_id'];
    $attachment_path   = null;

    if (empty($title) || empty($content)) {
        $error = "እባክዎን የማስታወቂያውን ርዕስ እና ዝርዝር መረጃ ያስገቡ።";
    } else {
        // File Upload Handling
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['attachment']['tmp_name'];
            $fileName      = $_FILES['attachment']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'doc'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $uploadFileDir = __DIR__ . '/uploads/';
                
                if (!file_exists($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }

                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $dest_path   = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $attachment_path = 'uploads/' . $newFileName;
                } else {
                    $error = "ፋይሉን መጫን አልተቻለም። የ 'uploads' ፎልደር የመጻፍ ፈቃድ እንዳለው ያረጋግጡ።";
                }
            } else {
                $error = "የተከለከለ የፋይል ዓይነት! የተፈቀዱት፡ JPG, PNG, PDF, DOCX ብቻ ናቸው።";
            }
        }

        // Save directly to Database
        if (empty($error)) {
            if (isset($pdo) && $pdo !== null) {
                try {
                    $sql = "INSERT INTO notices (title, content, category, author_id, target_section_id, attachment_path) 
                            VALUES (:title, :content, :category, :author_id, :target_section_id, :attachment_path)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'title'             => $title,
                        'content'           => $content,
                        'category'          => $category,
                        'author_id'         => $author_id,
                        'target_section_id' => $target_section_id,
                        'attachment_path'   => $attachment_path
                    ]);

                    $success = "ማስታወቂያው በትክክል ተለጥፏል! አሁን Public Portal ላይ ይታያል።";
                } catch (PDOException $e) {
                    $error = "ማስታወቂያውን መዝገብ ላይ መጻፍ አልተቻለም፡ " . $e->getMessage();
                }
            } else {
                $error = "የ Database ግንኙነት የለም! ማስታወቂያው አልተመዘገበም።";
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
            Notice Publisher Module
        </span>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        
        <div class="bg-slate-900 text-white p-6 border-b border-slate-800">
            <h2 class="text-lg font-bold flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-amber-400"></i> አዲስ ማስታወቂያ መለቀቅ
            </h2>
            <p class="text-slate-400 text-xs mt-1">ከ28 በላይ ለሚሆኑ የትምህርት ክፍሎች ወይም ለተወሰነ Section ማስታወቂያ ይልኩ</p>
        </div>

        <form method="POST" action="post_notice.php" enctype="multipart/form-data" class="p-6 space-y-5">
            
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

            <!-- Title -->
            <div>
                <label for="title" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    የማስታወቂያው ርዕስ (Title) <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="title" id="title" required placeholder="ምሳሌ፡ የ3ኛ ዓመት ሚድ ኤግዛም ፕሮግራም ማስተካከያ"
                       class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Grid for Category & Target Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Category -->
                <div>
                    <label for="category" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        ምድብ (Category)
                    </label>
                    <select name="category" id="category" class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500">
                        <option value="General">General (ጠቅላላ)</option>
                        <option value="Exam">Exam (ፈተና)</option>
                        <option value="Assignment">Assignment (አሳይመንት)</option>
                        <option value="Make-up Class">Make-up Class (ማካካሻ ክፍለ-ጊዜ)</option>
                        <option value="Event">Event (ክስተት/ስብሰባ)</option>
                    </select>
                </div>

                <!-- Target Section with Dynamic Optgroup -->
                <div>
                    <label for="target_section_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        የሚመለከተው ክፍል (Target Section)
                    </label>
                    <select name="target_section_id" id="target_section_id" class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">📢 ለሁሉም ዲፓርትመንትና ክፍሎች (All Public)</option>
                        
                        <?php 
                        // Group Sections by Department
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

            </div>

            <!-- Content -->
            <div>
                <label for="content" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    የማስታወቂያው ዝርዝር መልዕክት (Notice Details) <span class="text-rose-500">*</span>
                </label>
                <textarea name="content" id="content" rows="6" required placeholder="የማስታወቂያውን ሙሉ ዝርዝር መረጃ እዚህ ይፃፉ..."
                          class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg p-3 focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <!-- File Attachment -->
            <div>
                <label for="attachment" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                    አባሪ ፋይል ማያያዣ (File Attachment - PDF, Image, Word)
                </label>
                <input type="file" name="attachment" id="attachment"
                       class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-slate-300 rounded-lg bg-slate-50">
            </div>

            <!-- Submit Button -->
            <div class="pt-3">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-blue-500/20 text-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> ማስታወቂያውን ልቀቅ (Publish Notice)
                </button>
            </div>

        </form>

    </div>
</div>

<?php include_once 'includes/footer.php'; ?>