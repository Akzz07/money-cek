<x-app-layout :activeNav="'home'">
    <div class="px-5 pt-6 pb-12 space-y-5">
        <!-- Top Header Navigation -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="w-9 h-9 rounded-full bg-white/90 border border-white/60 shadow-xs flex items-center justify-center text-slate-700 hover:bg-white backdrop-blur-sm transition-colors" aria-label="Kembali ke Beranda">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 class="text-xl font-extrabold text-white tracking-tight drop-shadow-xs">Profil Pengguna</h1>
            </div>

            <!-- Quick Logout Button in Header -->
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-white/90 hover:bg-white text-rose-600 text-xs font-bold border border-white/60 shadow-xs backdrop-blur-sm flex items-center gap-1 transition-all active:scale-95" title="Keluar dari akun">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Keluar</span>
                </button>
            </form>
        </div>

        <!-- User Summary Card -->
        <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-md rounded-3xl p-5 border border-slate-200/80 dark:border-slate-700/80 shadow-xs flex items-center gap-4">
            @if(Auth::user()->avatar)
                <img src="{{ Auth::user()->avatar }}" alt="{{ Auth::user()->name }}" class="w-14 h-14 rounded-2xl object-cover shadow-md shadow-sky-500/20 shrink-0 border border-white/60">
            @else
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-500 to-sky-600 text-white flex items-center justify-center font-black text-xl shadow-md shadow-sky-500/20 shrink-0 uppercase">
                    {{ mb_substr(Auth::user()->name, 0, 2) }}
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <h2 class="text-base font-bold text-slate-800 dark:text-slate-100 truncate">{{ Auth::user()->name }}</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">{{ Auth::user()->email }}</p>
            </div>
        </div>

        <!-- Section 1: Update Profile Information -->
        <div class="p-5 bg-white/90 dark:bg-slate-800/90 backdrop-blur-md rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs">
            @include('profile.partials.update-profile-information-form')
        </div>

        <!-- Section 2: Update Password -->
        <div class="p-5 bg-white/90 dark:bg-slate-800/90 backdrop-blur-md rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs">
            @include('profile.partials.update-password-form')
        </div>

        <!-- Section 3: Logout Action Card -->
        <div class="p-5 bg-white/90 dark:bg-slate-800/90 backdrop-blur-md rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mb-1">Keluar Sesi</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 leading-relaxed">
                Akhiri sesi aktif Anda pada perangkat ini. Anda dapat masuk kembali kapan saja dengan email dan kata sandi Anda.
            </p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full py-2.5 px-4 rounded-2xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60 font-bold text-xs flex items-center justify-center gap-2 transition-all active:scale-98">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Keluar dari Akun (Logout)</span>
                </button>
            </form>
        </div>

        <!-- Section 4: Delete Account -->
        <div class="p-5 bg-white/90 dark:bg-slate-800/90 backdrop-blur-md rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
