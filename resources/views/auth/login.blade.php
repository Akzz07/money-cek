<x-guest-layout>
    <!-- Logo & Brand Header -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-sky-600 text-white shadow-lg shadow-sky-500/30 mb-3">
            <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Monetra</h1>
        <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto leading-relaxed">
            Pahami uangmu. Kendalikan pengeluaranmu. Bangun asetmu.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Input -->
        <div>
            <label for="email" class="block text-xs font-semibold text-slate-600 mb-1.5">Email</label>
            <div class="relative rounded-2xl border border-slate-200/90 focus-within:ring-2 focus-within:ring-sky-500 focus-within:border-transparent transition-all shadow-xs">
                <svg class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <input id="email" 
                       type="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       required 
                       autofocus 
                       autocomplete="username" 
                       placeholder="nama@email.com"
                       class="w-full pl-11 pr-4 py-3 text-sm text-slate-800 bg-transparent focus:outline-none placeholder-slate-300">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password Input with Toggle -->
        <div x-data="{ show: false }">
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-semibold text-slate-600">Kata Sandi</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-sky-600 hover:text-sky-700">
                        Lupa kata sandi?
                    </a>
                @endif
            </div>

            <div class="relative rounded-2xl border border-slate-200/90 focus-within:ring-2 focus-within:ring-sky-500 focus-within:border-transparent transition-all shadow-xs">
                <svg class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>

                <input id="password" 
                       :type="show ? 'text' : 'password'" 
                       name="password" 
                       required 
                       autocomplete="current-password" 
                       placeholder="••••••••"
                       class="w-full pl-11 pr-11 py-3 text-sm text-slate-800 bg-transparent focus:outline-none placeholder-slate-300">

                <button type="button" 
                        @click="show = !show" 
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none">
                    <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center pt-1">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" 
                       type="checkbox" 
                       name="remember" 
                       class="rounded-md border-slate-300 text-sky-600 shadow-xs focus:ring-sky-500">
                <span class="ms-2 text-xs text-slate-600 font-medium">Ingat saya di perangkat ini</span>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-3">
            <x-ui.button type="submit" variant="primary" size="lg" class="w-full">
                Masuk
            </x-ui.button>
        </div>

        <!-- Register Link -->
        <div class="pt-4 text-center">
            <p class="text-xs text-slate-500">
                Belum punya akun Monetra? 
                <a href="{{ route('register') }}" class="font-bold text-sky-600 hover:text-sky-700 hover:underline">
                    Daftar Sekarang
                </a>
            </p>
        </div>
    </form>
</x-guest-layout>
