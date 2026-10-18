<div x-data="{
    isDark: document.documentElement.classList.contains('dark'),
    toggleTheme() {
        if (this.isDark) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('monetra_theme', 'light');
            this.isDark = false;
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('monetra_theme', 'dark');
            this.isDark = true;
        }
    }
}" class="relative inline-flex items-center">
    <button type="button"
            @click="toggleTheme()"
            class="w-10 h-10 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700/80 shadow-xs flex items-center justify-center text-slate-600 dark:text-amber-400 hover:bg-slate-50 dark:hover:bg-slate-700 active:scale-90 transition-all focus:outline-none"
            :title="isDark ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap'"
            aria-label="Toggle Mode Gelap / Terang">
        
        <!-- Sun Icon (shown in dark mode) -->
        <svg x-show="isDark" x-cloak class="w-5 h-5 transition-transform hover:rotate-45" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>

        <!-- Moon Icon (shown in light mode) -->
        <svg x-show="!isDark" class="w-5 h-5 transition-transform hover:-rotate-12 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
    </button>
</div>
