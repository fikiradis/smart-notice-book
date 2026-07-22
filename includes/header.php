<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ቋንቋ መቀየርን ማስተናገድ (Language Switch Logic)
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = ($_GET['lang'] === 'am') ? 'am' : 'en';
}

$lang = $_SESSION['lang'] ?? 'am'; // Default language is Amharic

// የቃላት ትርጉም ዝርዝር (Translations)
$translations = [
    'en' => [
        'title' => 'Smart Department Noticeboard & Scheduler',
        'brand' => 'Smart Noticeboard',
        'feed' => 'Public Feed',
        'dashboard' => 'Dashboard',
        'login' => 'Staff Login',
        'logout' => 'Logout',
        'select_lang' => 'Language'
    ],
    'am' => [
        'title' => 'ስማርት የትምህርት ክፍል ማስታወቂያ ሰሌዳ',
        'brand' => 'ስማርት ኖቲስቦርድ',
        'feed' => 'ዋና ገጽ',
        'dashboard' => 'ዳሽቦርድ',
        'login' => 'ሰራተኛ ለመግባት',
        'logout' => 'ውጣ',
        'select_lang' => 'ቋንቋ'
    ]
];

$t = $translations[$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title']; ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- PWA Web Manifest & Mobile Config -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#1e40af">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>
<body class="bg-slate-100 text-slate-800 flex flex-col min-h-screen">

    <!-- Top Navigation Bar -->
    <nav class="bg-blue-700 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <!-- Logo & Brand -->
                <a href="index.php" class="flex items-center gap-3 font-bold text-lg md:text-xl tracking-wide hover:text-amber-300 transition">
                    <i class="fa-solid fa-chalkboard-user text-amber-400 text-2xl"></i>
                    <span><?= $t['brand']; ?></span>
                </a>

                <!-- Desktop Navigation Links & Language Switcher -->
                <div class="hidden md:flex items-center space-x-3">
                    <a href="index.php" class="px-3 py-2 rounded-lg text-sm font-medium hover:bg-blue-800 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-house text-blue-200"></i> <?= $t['feed']; ?>
                    </a>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="px-3 py-2 rounded-lg text-sm font-medium bg-blue-800 hover:bg-blue-900 transition flex items-center gap-1.5">
                            <i class="fa-solid fa-gauge text-blue-200"></i> <?= $t['dashboard']; ?>
                        </a>
                        <span class="text-xs bg-blue-900 px-2.5 py-1 rounded-full text-blue-200 uppercase font-bold tracking-wider border border-blue-600">
                            <?= htmlspecialchars($_SESSION['role'] ?? 'Staff'); ?>
                        </span>
                        <a href="logout.php" class="px-3 py-2 rounded-lg text-sm font-medium bg-rose-600 hover:bg-rose-700 transition flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-right-from-bracket"></i> <?= $t['logout']; ?>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="px-4 py-2 rounded-lg text-sm font-semibold bg-amber-400 hover:bg-amber-500 text-slate-950 transition flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-right-to-bracket"></i> <?= $t['login']; ?>
                        </a>
                    <?php endif; ?>

                    <!-- Language Switcher Dropdown (Desktop) -->
                    <div class="relative ml-3 border-l border-blue-500 pl-3">
                        <form method="GET" class="inline">
                            <?php foreach($_GET as $key => $val): if($key !== 'lang'): ?>
                                <input type="hidden" name="<?= htmlspecialchars($key); ?>" value="<?= htmlspecialchars($val); ?>">
                            <?php endif; endforeach; ?>
                            <select name="lang" onchange="this.form.submit()" class="bg-blue-800 text-white text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-blue-500 cursor-pointer focus:outline-none focus:ring-2 focus:ring-amber-400">
                                <option value="am" <?= $lang === 'am' ? 'selected' : ''; ?>>🇪🇹 አማርኛ</option>
                                <option value="en" <?= $lang === 'en' ? 'selected' : ''; ?>>🇬🇧 English</option>
                            </select>
                        </form>
                    </div>

                </div>

                <!-- Mobile Navigation Right Area -->
                <div class="flex items-center gap-2 md:hidden">
                    <!-- Language Switcher (Mobile) -->
                    <form method="GET" class="inline">
                        <?php foreach($_GET as $key => $val): if($key !== 'lang'): ?>
                            <input type="hidden" name="<?= htmlspecialchars($key); ?>" value="<?= htmlspecialchars($val); ?>">
                        <?php endif; endforeach; ?>
                        <select name="lang" onchange="this.form.submit()" class="bg-blue-800 text-white text-xs font-semibold px-2 py-1 rounded-lg border border-blue-500 cursor-pointer">
                            <option value="am" <?= $lang === 'am' ? 'selected' : ''; ?>>🇪🇹 AM</option>
                            <option value="en" <?= $lang === 'en' ? 'selected' : ''; ?>>🇬🇧 EN</option>
                        </select>
                    </form>

                    <!-- Mobile Menu Toggle Button -->
                    <button id="mobile-menu-btn" type="button" class="p-2 rounded-lg text-white hover:bg-blue-800 focus:outline-none transition">
                        <i id="mobile-menu-icon" class="fa-solid fa-bars text-xl"></i>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div id="mobile-menu" class="hidden md:hidden bg-blue-800 px-4 pt-3 pb-4 space-y-2 border-t border-blue-600 shadow-inner">
            <a href="index.php" class="block px-3 py-2 rounded-lg text-base font-medium hover:bg-blue-700 transition">
                <i class="fa-solid fa-house mr-2 text-blue-300"></i> <?= $t['feed']; ?>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="block px-3 py-2 rounded-lg text-base font-medium hover:bg-blue-700 transition">
                    <i class="fa-solid fa-gauge mr-2 text-blue-300"></i> <?= $t['dashboard']; ?>
                </a>
                <a href="logout.php" class="block px-3 py-2 rounded-lg text-base font-medium bg-rose-600 hover:bg-rose-700 text-center transition mt-2">
                    <i class="fa-solid fa-right-from-bracket mr-2"></i> <?= $t['logout']; ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="block px-3 py-2 rounded-lg text-base font-medium bg-amber-400 text-slate-950 font-bold text-center transition mt-2">
                    <i class="fa-solid fa-right-to-bracket mr-2"></i> <?= $t['login']; ?>
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Interactive Script for Mobile Toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('mobile-menu-btn');
            const menu = document.getElementById('mobile-menu');
            const icon = document.getElementById('mobile-menu-icon');

            if (btn && menu && icon) {
                btn.addEventListener('click', () => {
                    menu.classList.toggle('hidden');
                    if (menu.classList.contains('hidden')) {
                        icon.classList.remove('fa-xmark');
                        icon.classList.add('fa-bars');
                    } else {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-xmark');
                    }
                });
            }
        });
    </script>

    <!-- Main Content Container Wrapper -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">