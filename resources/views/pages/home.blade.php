<x-app-layout :activeNav="'home'">
    <div class="px-5 pt-6 pb-6">
        <!-- Top Header & Sapaan -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-lg font-bold text-slate-800 flex items-center gap-1.5">
                    Halo, {{ Auth::user()->name }} <span class="text-xl">👋</span>
                </h1>
                <p class="text-xs text-slate-500 font-medium">Semangat mengelola keuanganmu hari ini!</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="w-10 h-10 rounded-full bg-white border border-slate-200/80 shadow-xs flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" aria-label="Profil Pengguna">
                <span class="text-xs font-bold text-blue-600">{{ mb_substr(Auth::user()->name, 0, 2) }}</span>
            </a>
        </div>

        <!-- Month Navigation Bar -->
        @php
            $prevMonth = $metrics->startDate->subMonthNoOverflow()->format('Y-m');
            $nextMonth = $metrics->startDate->addMonthNoOverflow()->format('Y-m');
            $currentMonthKey = now()->setTimezone(Auth::user()->timezone ?? 'Asia/Jakarta')->format('Y-m');
            $isNotCurrent = $metrics->periodKey !== $currentMonthKey;
        @endphp
        <div class="flex items-center justify-between bg-slate-100/80 rounded-2xl p-1.5 mb-4 text-xs">
            <a href="{{ route('home', ['month' => $prevMonth]) }}" 
               class="p-1.5 rounded-xl text-slate-600 hover:bg-white hover:shadow-xs transition-all" 
               aria-label="Bulan Sebelumnya">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>

            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800">{{ $metrics->periodName }}</span>
                @if($isNotCurrent)
                    <a href="{{ route('home') }}" class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-semibold hover:bg-blue-200 transition-colors">
                        Bulan Ini
                    </a>
                @endif
            </div>

            <a href="{{ route('home', ['month' => $nextMonth]) }}" 
               class="p-1.5 rounded-xl text-slate-600 hover:bg-white hover:shadow-xs transition-all" 
               aria-label="Bulan Berikutnya">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <!-- Total Saldo Card (Primary Blue) -->
        <div x-data="{ showBalance: true }" class="bg-gradient-to-br from-blue-600 via-blue-600 to-blue-700 rounded-3xl p-5 text-white shadow-lg shadow-blue-500/25 mb-4 relative overflow-hidden">
            <!-- Decorative circle overlay -->
            <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-white/10 rounded-full blur-xs pointer-events-none"></div>

            <div class="flex items-center justify-between text-blue-100 text-xs font-medium mb-1.5">
                <span>Total Saldo</span>
                <button @click="showBalance = !showBalance" class="hover:text-white transition-colors flex items-center gap-1 text-[11px]" aria-label="Toggle Sembunyikan Saldo">
                    <svg x-show="showBalance" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="!showBalance" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                </button>
            </div>
            
            <div class="text-2xl sm:text-3xl font-extrabold tracking-tight mb-3">
                <span x-show="showBalance">@money($metrics->totalBalance)</span>
                <span x-show="!showBalance" x-cloak class="tracking-widest">••••••••••</span>
            </div>

            <!-- Net Cash Flow Indicator -->
            <div class="mb-4 flex items-center gap-1.5 text-[11px] text-blue-100 font-medium">
                <span>Arus Kas Bersih:</span>
                <span class="font-bold {{ $metrics->netCashFlow->isNegative() ? 'text-rose-200' : 'text-emerald-200' }}">
                    {{ $metrics->netCashFlow->isNegative() ? '-' : '+' }}@money($metrics->netCashFlow->abs())
                </span>
            </div>

            <!-- Sub-Cards: Pemasukan & Pengeluaran -->
            <div class="grid grid-cols-2 gap-2.5 pt-3 border-t border-white/15">
                <div class="bg-white/10 backdrop-blur-xs rounded-2xl p-2.5 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-emerald-400/20 text-emerald-300 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-blue-100 font-medium truncate">Pemasukan</p>
                        <p class="text-xs sm:text-sm font-bold truncate">
                            <span x-show="showBalance">@money($metrics->monthlyIncome)</span>
                            <span x-show="!showBalance" x-cloak>••••••</span>
                        </p>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-xs rounded-2xl p-2.5 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-rose-400/20 text-rose-300 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-blue-100 font-medium truncate">Pengeluaran</p>
                        <p class="text-xs sm:text-sm font-bold truncate">
                            <span x-show="showBalance">@money($metrics->monthlyExpense)</span>
                            <span x-show="!showBalance" x-cloak>••••••</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Safe-to-Spend Card -->
        <a href="{{ route('safe-to-spend.show') }}" class="block bg-gradient-to-r from-blue-600 via-indigo-600 to-indigo-700 rounded-2xl p-4 text-white shadow-md shadow-blue-500/20 mb-6 relative hover:shadow-lg transition-all active:scale-[0.99] group">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-xs flex items-center justify-center text-white shrink-0 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <h2 class="text-xs font-bold text-blue-100">Safe-to-Spend</h2>
                            <span class="text-[9px] bg-white/25 px-1.5 py-0.2 rounded-full font-bold uppercase tracking-wider">
                                {{ $safeToSpend->daysRemaining }} Hari Lagi
                            </span>
                        </div>
                        <p class="text-[11px] text-blue-100/80">Aman dibelanjakan s/d {{ $safeToSpend->planningDate->translatedFormat('d M') }}</p>
                        <p class="text-xl font-black tracking-tight {{ $safeToSpend->isNegative() ? 'text-rose-200' : 'text-white' }}">
                            @money($safeToSpend->safeToSpend)
                        </p>
                    </div>
                </div>
                <div class="text-right flex items-center gap-1 text-xs text-blue-100 group-hover:text-white font-semibold">
                    <span>Formula</span>
                    <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
            </div>
        </a>

        <!-- Tagihan Terdekat Section -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-1.5">
                    <h3 class="text-sm font-bold text-slate-800">Tagihan Terdekat</h3>
                    @if($safeToSpend->upcomingBills->isNotEmpty())
                        <span class="text-[10px] bg-blue-100 text-blue-700 font-extrabold px-1.5 py-0.2 rounded-full">
                            {{ $safeToSpend->upcomingBills->count() }}
                        </span>
                    @endif
                </div>
                <a href="{{ route('bills.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Lihat Semua</a>
            </div>

            @if($safeToSpend->upcomingBills->isEmpty())
                <x-ui.card padding="p-4" class="bg-slate-50/70 border-dashed border-slate-200 text-center">
                    <div class="text-xs text-slate-500 font-medium">Tidak ada tagihan tertunda hingga {{ $safeToSpend->planningDate->translatedFormat('d M Y') }}.</div>
                </x-ui.card>
            @else
                <div class="space-y-2">
                    @foreach($safeToSpend->upcomingBills->take(3) as $bill)
                        <a href="{{ route('bills.index') }}" class="block p-3 bg-white border border-slate-200/90 rounded-2xl shadow-xs hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800">{{ $bill->name }}</div>
                                        <div class="text-[10px] text-slate-400">Jatuh tempo: {{ $bill->due_date->translatedFormat('d M Y') }}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-extrabold text-slate-800">@money($bill->amount)</div>
                                    <span class="text-[10px] font-bold text-blue-600">Bayar →</span>
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
                <h3 class="text-sm font-bold text-slate-800">Aktivitas Terbaru</h3>
                <a href="{{ route('transactions.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Lihat Semua</a>
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
                                        'income' => 'bg-emerald-50 text-emerald-600',
                                        'expense' => 'bg-rose-50 text-rose-600',
                                        'transfer' => 'bg-blue-50 text-blue-600',
                                        'adjustment' => 'bg-purple-50 text-purple-600',
                                        default => 'bg-slate-100 text-slate-600',
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
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-800">
                                            {{ $tx->description ?: ($tx->category?->name ?? $tx->type->label()) }}
                                        </h4>
                                        <p class="text-[11px] text-slate-400">
                                            {{ $tx->transaction_date->format('d M Y') }} • {{ $tx->sourceAccount?->name }}
                                            @if($tx->destinationAccount)
                                                → {{ $tx->destinationAccount->name }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <p class="text-xs sm:text-sm font-bold {{ match($tx->type->value) {
                                        'income' => 'text-emerald-600',
                                        'expense' => 'text-slate-900',
                                        'transfer' => 'text-blue-600',
                                        'adjustment' => 'text-purple-700',
                                        default => 'text-slate-700',
                                    } }}">
                                        @if($tx->type->value === 'income')
                                            + @money($tx->amount)
                                        @elseif($tx->type->value === 'expense')
                                            - @money($tx->amount)
                                        @elseif($tx->type->value === 'transfer')
                                            ⇄ @money($tx->amount)
                                        @else
                                            ± @money($tx->amount)
                                        @endif
                                    </p>
                                    <p class="text-[10px] text-slate-400">{{ $tx->category?->name ?? $tx->type->label() }}</p>
                                </div>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
