<?php
require_once 'config/db.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "እባክዎን ኢሜይል እና ፓስወርድ ያስገቡ።";
    } else {
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();

                // Password Verification
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Set Session Data
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email']     = $user['email'];
                    $_SESSION['role']      = $user['role'];

                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "የተሳሳተ ኢሜይል ወይም ፓስወርድ አስገብተዋል።";
                }
            } catch (PDOException $e) {
                $error = "የሲስተም ኤረር አጋጥሟል፡ " . $e->getMessage();
            }
        } else {
            // Demo Fallback Login (Database ገና ካልተገናኘ)
            if ($email === 'admin@dept.edu' && $password === 'password123') {
                $_SESSION['user_id']   = 1;
                $_SESSION['full_name'] = 'Demo Admin';
                $_SESSION['email']     = 'admin@dept.edu';
                $_SESSION['role']      = 'DeptHead';

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "የተሳሳተ ኢሜይል ወይም ፓስወርድ! (Demo: admin@dept.edu / password123)";
            }
        }
    }
}

include_once 'includes/header.php';
?>

<div class="max-w-md mx-auto my-12">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        
        <!-- Login Header -->
        <div class="bg-slate-900 text-white p-6 text-center">
            <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg shadow-blue-500/30">
                <i class="fa-solid fa-lock text-xl text-white"></i>
            </div>
            <h2 class="text-xl font-bold">Staff Login Portal</h2>
            <p class="text-slate-400 text-xs mt-1">የመምህራን እና የአስተዳደር ሰራተኞች መግቢያ</p>
        </div>

        <!-- Login Form -->
        <form method="POST" action="login.php" class="p-6 space-y-4">
            
            <?php if (!empty($error)): ?>
                <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                    <span><?= htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">ኢሜይል (Email)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input type="email" name="email" id="email" required placeholder="user@department.edu"
                           class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg pl-10 p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">ፓስወርድ (Password)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fa-solid fa-key"></i>
                    </span>
                    <input type="password" name="password" id="password" required placeholder="••••••••"
                           class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg pl-10 p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg transition duration-200 shadow-md shadow-blue-500/20 text-sm">
                ግባ (Sign In)
            </button>

            <div class="text-center pt-2">
                <a href="index.php" class="text-xs text-slate-500 hover:text-slate-800 transition">
                    <i class="fa-solid fa-arrow-left mr-1"></i> ወደ ዋናው ማስታወቂያ ሰሌዳ ተመለስ
                </a>
            </div>

        </form>

    </div>
</div>

<?php include_once 'includes/footer.php'; ?>