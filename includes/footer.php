</main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 border-t border-slate-800 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center sm:flex sm:justify-between sm:items-center">
            <p class="text-sm">&copy; <?= date('Y'); ?> Department Noticeboard & Class Scheduler System</p>
            <p class="text-xs text-slate-500 mt-2 sm:mt-0">Powered by PHP & MySQL | PWA Ready</p>
        </div>
    </footer>

    <!-- Mobile Menu Toggle Script -->
    <script>
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');

        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>