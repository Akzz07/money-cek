@props([
    'active' => 'home', // home, activity, budget, asset
])

<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200/80 dark:border-slate-800 px-2 py-1.5 z-40 safe-bottom shadow-[0_-4px_25px_rgba(0,0,0,0.06)] dark:shadow-[0_-4px_25px_rgba(0,0,0,0.4)] transition-colors duration-200" aria-label="Navigasi Utama">
    <div class="flex items-center justify-around relative">
        
        <!-- Tab 1: Beranda -->
        <a href="{{ route('home') }}" class="flex flex-col items-center justify-center min-w-[56px] min-h-[50px] py-1 transition-colors {{ $active === 'home' ? 'text-sky-600 dark:text-sky-400 font-semibold' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 font-medium' }}">
            <svg class="w-6 h-6 mb-1" fill="{{ $active === 'home' ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="{{ $active === 'home' ? '0' : '2' }}" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-[11px] leading-tight">Beranda</span>
        </a>

        <!-- Tab 2: Aktivitas -->
        <a href="{{ route('transactions.index') }}" class="flex flex-col items-center justify-center min-w-[56px] min-h-[50px] py-1 transition-colors {{ $active === 'activity' ? 'text-sky-600 dark:text-sky-400 font-semibold' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 font-medium' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span class="text-[11px] leading-tight">Aktivitas</span>
        </a>

        <!-- Center Floating Action Button: Tambah Transaksi -->
        <div class="relative -top-5 flex flex-col items-center">
            <a href="{{ route('transactions.create') }}" class="w-13 h-13 rounded-full bg-gradient-to-tr from-[#0284C7] via-[#0EA5E9] to-[#38BDF8] text-white shadow-lg shadow-sky-500/35 dark:shadow-sky-500/25 flex items-center justify-center active:scale-95 transition-all hover:shadow-xl hover:shadow-sky-500/40 focus:outline-none focus:ring-4 focus:ring-sky-300 dark:focus:ring-sky-800" aria-label="Tambah Transaksi">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
            </a>
        </div>

        <!-- Tab 4: Anggaran -->
        <a href="{{ route('budgets.index') }}" class="flex flex-col items-center justify-center min-w-[56px] min-h-[50px] py-1 transition-colors {{ $active === 'budget' ? 'text-sky-600 dark:text-sky-400 font-semibold' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 font-medium' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
            </svg>
            <span class="text-[11px] leading-tight">Anggaran</span>
        </a>

        <!-- Tab 5: Aset & Laporan -->
        <a href="{{ route('assets.index') }}" class="flex flex-col items-center justify-center min-w-[56px] min-h-[50px] py-1 transition-colors {{ $active === 'asset' ? 'text-sky-600 dark:text-sky-400 font-semibold' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 font-medium' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <span class="text-[11px] leading-tight">Aset</span>
        </a>

    </div>
</nav>
