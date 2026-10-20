<x-app-layout :activeNav="'home'">
    <div class="px-5 pt-6 pb-6">
        <h1 class="sr-only">Dashboard Keuangan Monetra</h1>

        <!-- Top Header with Interactive Cat Mascot & Dark Mode Toggle -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2.5 min-w-0">
                <!-- Interactive Cat Mascot based on current total balance -->
                <x-ui.cat-mascot :balance="$metrics->totalBalance" />

                @php
                    $hour = now()->setTimezone(Auth::user()->timezone ?? 'Asia/Jakarta')->hour;
                    $waktu = match (true) {
                        $hour >= 4 && $hour < 11 => 'Pagi',
                        $hour >= 11 && $hour < 15 => 'Siang',
                        $hour >= 15 && $hour < 18 => 'Sore',
                        default => 'Malam',
                    };

                    $balVal = (float) $metrics->totalBalance->toDecimalString();

                    if ($balVal < 100000) {
                        $tierName = 'Mode Survival';
                        $tierColor = 'text-rose-600 dark:text-rose-400';
                    } elseif ($balVal <= 500000) {
                        $tierName = 'Miskin';
                        $tierColor = 'text-orange-600 dark:text-orange-400';
                    } elseif ($balVal <= 1000000) {
                        $tierName = 'Pejuang';
                        $tierColor = 'text-amber-600 dark:text-amber-400';
                    } elseif ($balVal <= 10000000) {
                        $tierName = 'Perintis';
                        $tierColor = 'text-sky-600 dark:text-sky-400';
                    } else {
                        $tierName = 'Sultan';
                        $tierColor = 'text-emerald-600 dark:text-emerald-400';
                    }
                @endphp
                <div class="min-w-0 bg-white/70 dark:bg-slate-900/60 backdrop-blur-md px-3 py-1.5 rounded-2xl border border-white/60 dark:border-slate-800/80 shadow-2xs">
                    <p class="text-xs sm:text-sm font-bold tracking-tight text-slate-800 dark:text-slate-100 truncate">
                        Selamat {{ $waktu }}, <span class="{{ $tierColor }} font-black">{{ $tierName }}</span>
                    </p>
                </div>
            </div>

            <!-- Header Actions: Theme Toggle & Profile Avatar -->
            <div class="flex items-center gap-2 shrink-0">
                <!-- Dark Mode Toggle Button -->
                <x-ui.theme-toggle />

                <!-- Profile Avatar -->
                <a href="{{ route('profile.edit') }}" 
                   class="w-10 h-10 rounded-2xl bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border border-white/60 dark:border-slate-700/80 shadow-xs flex items-center justify-center text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 active:scale-95 transition-all" 
                   aria-label="Profil Pengguna">
                    <span class="text-xs font-black text-sky-600 dark:text-sky-400 uppercase">{{ mb_substr(Auth::user()->name, 0, 2) }}</span>
                </a>
            </div>
        </div>

        <!-- Month Navigation Bar -->
        @php
            $prevMonth = $metrics->startDate->subMonthNoOverflow()->format('Y-m');
            $nextMonth = $metrics->startDate->addMonthNoOverflow()->format('Y-m');
            $currentMonthKey = now()->setTimezone(Auth::user()->timezone ?? 'Asia/Jakarta')->format('Y-m');
            $isNotCurrent = $metrics->periodKey !== $currentMonthKey;
        @endphp
        <div class="flex items-center justify-between bg-white/90 dark:bg-slate-800/90 backdrop-blur-md rounded-2xl p-1.5 mb-4 text-xs border border-sky-100/80 dark:border-slate-700/80 shadow-2xs transition-colors">
            <a href="{{ route('home', ['month' => $prevMonth]) }}" 
               class="p-1.5 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-sky-50 dark:hover:bg-slate-700 hover:text-sky-600 dark:hover:text-sky-400 hover:shadow-xs transition-all" 
               aria-label="Bulan Sebelumnya">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>

            <div class="flex items-center gap-2">
                <span class="font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">{{ $metrics->periodName }}</span>
                @if($isNotCurrent)
                    <a href="{{ route('home') }}" class="text-[10px] bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200/50 dark:border-sky-800/50 px-2 py-0.5 rounded-full font-bold hover:bg-sky-100 dark:hover:bg-sky-900 transition-colors">
                        Bulan Ini
                    </a>
                @endif
            </div>

            <a href="{{ route('home', ['month' => $nextMonth]) }}" 
               class="p-1.5 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-sky-50 dark:hover:bg-slate-700 hover:text-sky-600 dark:hover:text-sky-400 hover:shadow-xs transition-all" 
               aria-label="Bulan Berikutnya">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <!-- Total Saldo Card (Variant 5 — Blue Sky Fresh Hero Card) -->
        <div x-data="{ showBalance: true }" class="bg-gradient-to-br from-[#1E88E5] via-[#0284C7] to-[#38BDF8] rounded-3xl p-5 text-white shadow-xl shadow-sky-500/25 dark:shadow-sky-950/50 border border-white/20 mb-5 relative overflow-hidden ring-1 ring-inset ring-white/20">
            <!-- Subtle ambient card highlight -->
            <div class="absolute inset-0 bg-gradient-to-tr from-white/[0.12] via-transparent to-white/[0.05] pointer-events-none"></div>

            <div class="relative flex items-center justify-between text-white/85 text-xs font-semibold mb-2">
                <span class="tracking-wider uppercase text-[11px] font-bold text-white/90">Total Saldo Terkonsolidasi</span>
                <button @click="showBalance = !showBalance" class="text-white hover:text-white transition-colors flex items-center gap-1.5 text-xs font-medium py-1 px-2.5 rounded-xl bg-white/20 hover:bg-white/30 backdrop-blur-sm border border-white/25 active:scale-95" aria-label="Toggle Sembunyikan Saldo">
                    <svg x-show="showBalance" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="!showBalance" x-cloak class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                    <span x-text="showBalance ? 'Tutup' : 'Lihat'" class="text-[10px] font-bold"></span>
                </button>
            </div>
            
            <div class="relative text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-2">
                <span x-show="showBalance">@money($metrics->totalBalance)</span>
                <span x-show="!showBalance" x-cloak class="tracking-widest">••••••••••</span>
            </div>

            <!-- Net Cash Flow Indicator -->
            <div class="relative mb-5 flex items-center gap-2 text-xs">
                <span class="text-white/85 font-medium">Arus Kas Bersih:</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold {{ $metrics->netCashFlow->isNegative() ? 'bg-rose-500/85 text-white border border-rose-300/40 backdrop-blur-xs shadow-xs' : 'bg-emerald-500/85 text-white border border-emerald-300/40 backdrop-blur-xs shadow-xs' }}">
                    {{ $metrics->netCashFlow->isNegative() ? '-' : '+' }}@money($metrics->netCashFlow->abs())
                </span>
            </div>

            <!-- Sub-Cards: Pemasukan & Pengeluaran (Frosted Translucent Glass Cards) -->
            <div class="relative grid grid-cols-2 gap-2.5 pt-4 border-t border-white/20">
                <div class="bg-white/15 backdrop-blur-md rounded-2xl p-3 flex items-center gap-2.5 border border-white/20 shadow-xs">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-white/80 font-bold uppercase tracking-wider truncate">Pemasukan</p>
                        <p class="text-xs sm:text-sm font-bold text-white truncate mt-0.5">
                            <span x-show="showBalance">@money($metrics->monthlyIncome)</span>
                            <span x-show="!showBalance" x-cloak>••••••</span>
                        </p>
                    </div>
                </div>

                <div class="bg-white/15 backdrop-blur-md rounded-2xl p-3 flex items-center gap-2.5 border border-white/20 shadow-xs">
                    <div class="w-8 h-8 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-white/80 font-bold uppercase tracking-wider truncate">Pengeluaran</p>
                        <p class="text-xs sm:text-sm font-bold text-white truncate mt-0.5">
                            <span x-show="showBalance">@money($metrics->monthlyExpense)</span>
                            <span x-show="!showBalance" x-cloak>••••••</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Safe-to-Spend Card (Variant 5 Fresh Blue Sky Widget) -->
        <a href="{{ route('safe-to-spend.show') }}" class="block bg-white dark:bg-slate-800/90 border border-sky-100/90 dark:border-slate-700/80 rounded-3xl p-4 sm:p-5 shadow-[0_4px_20px_-4px_rgba(14,165,233,0.08)] hover:shadow-md transition-all mb-6 group">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-11 h-11 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-100 dark:border-sky-800/60 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Safe-to-Spend</h2>
                            <span class="text-[10px] bg-sky-50 dark:bg-sky-950/80 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800/80 px-2 py-0.5 rounded-full font-extrabold uppercase tracking-wider">
                                {{ $safeToSpend->daysRemaining }} Hari Lagi
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 font-medium mt-0.5">Aman dibelanjakan s/d {{ $safeToSpend->planningDate->translatedFormat('d M') }}</p>
                        <p class="text-xl sm:text-2xl font-black tracking-tight {{ $safeToSpend->isNegative() ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} mt-0.5">
                            @money($safeToSpend->safeToSpend)
                        </p>
                    </div>
                </div>
                <div class="text-right flex items-center gap-1 text-xs font-bold text-sky-600 dark:text-sky-400 group-hover:text-sky-700 dark:group-hover:text-sky-300 shrink-0 ml-2">
                    <span>Formula</span>
                    <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
            </div>
        </a>

        <!-- Tagihan Terdekat Section -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-1.5">
                    <h3 class="text-sm font-black text-slate-800 dark:text-slate-100">Tagihan Terdekat</h3>
                    @if($safeToSpend->upcomingBills->isNotEmpty())
                        <span class="text-[10px] bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 font-extrabold px-1.5 py-0.2 rounded-full border border-sky-200 dark:border-sky-800">
                            {{ $safeToSpend->upcomingBills->count() }}
                        </span>
                    @endif
                </div>
                <a href="{{ route('bills.index') }}" class="text-xs font-bold text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300">Lihat Semua</a>
            </div>

            @if($safeToSpend->upcomingBills->isEmpty())
                <x-ui.card padding="p-4" class="bg-slate-50/70 dark:bg-slate-800/40 border-dashed border-slate-200 dark:border-slate-700 text-center">
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Tidak ada tagihan tertunda hingga {{ $safeToSpend->planningDate->translatedFormat('d M Y') }}.</div>
                </x-ui.card>
            @else
                <div class="space-y-2">
                    @foreach($safeToSpend->upcomingBills->take(3) as $bill)
                        <a href="{{ route('bills.index') }}" class="block p-3.5 bg-white dark:bg-slate-800/90 border border-slate-200/90 dark:border-slate-700/80 rounded-2xl shadow-2xs hover:shadow-md transition-all">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 border border-amber-200/50 dark:border-amber-800/50 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800 dark:text-slate-100">{{ $bill->name }}</div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500">Jatuh tempo: {{ $bill->due_date->translatedFormat('d M Y') }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-extrabold text-slate-800 dark:text-slate-100">@money($bill->amount)</div>
                                    <span class="text-[10px] font-bold text-sky-600 dark:text-sky-400">Bayar →</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Aktivitas Terbaru Section -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-black text-slate-800 dark:text-slate-100">Aktivitas Terbaru</h3>
                <a href="{{ route('transactions.index') }}" class="text-xs font-bold text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300">Lihat Semua</a>
            </div>

            @if($metrics->recentTransactions->isEmpty())
                <x-ui.empty-state 
                    title="Belum Ada Transaksi" 
                    description="Belum ada transaksi tercatat. Mulai catat pemasukan atau pengeluaran pertamamu."
                    actionText="Catat Transaksi Sekarang"
                    :actionUrl="route('transactions.create')"
                />
            @else
                <div class="space-y-2.5">
                    @foreach($metrics->recentTransactions as $tx)
                        <x-ui.card padding="p-3.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <!-- Type Icon Avatar -->
                                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 font-bold text-sm {{ match($tx->type->value) {
                                        'income' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/50',
                                        'expense' => 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200/50 dark:border-rose-800/50',
                                        'transfer' => 'bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-200/50 dark:border-sky-800/50',
                                        'adjustment' => 'bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200/50 dark:border-purple-800/50',
                                        default => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
                                    } }}">
                                        @if($tx->type->value === 'income')
                                             <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                        @elseif($tx->type->value === 'expense')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                        @elseif($tx->type->value === 'transfer')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                        @else
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                                        @endif
                                    </div>

                                    <div>
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100">
                                            {{ $tx->description ?: ($tx->category?->name ?? $tx->type->label()) }}
                                        </h4>
                                        <p class="text-[11px] text-slate-400 dark:text-slate-500">
                                            {{ $tx->transaction_date->format('d M Y') }} • {{ $tx->sourceAccount?->name }}
                                            @if($tx->destinationAccount)
                                                → {{ $tx->destinationAccount->name }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <p class="text-xs sm:text-sm font-extrabold {{ match($tx->type->value) {
                                        'income' => 'text-emerald-600 dark:text-emerald-400',
                                        'expense' => 'text-slate-900 dark:text-slate-100',
                                        'transfer' => 'text-sky-600 dark:text-sky-400',
                                        'adjustment' => 'text-purple-600 dark:text-purple-400',
                                        default => 'text-slate-700 dark:text-slate-300',
                                    } }}">
                                        @if($tx->type->value === 'income')
                                            + @money($tx->amount)
                                        @elseif($tx->type->value === 'expense')
                                            - @money($tx->amount)
                                        @elseif($tx->type->value === 'transfer')
                                            - @money($tx->amount)
                                        @else
                                            ± @money($tx->amount)
                                        @endif
                                    </p>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">{{ $tx->category?->name ?? $tx->type->label() }}</p>
                                </div>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
