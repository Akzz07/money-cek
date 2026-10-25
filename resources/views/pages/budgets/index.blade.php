<x-app-layout :activeNav="'budget'">
    <div class="px-5 pt-6 pb-6">
        <!-- Header & Action -->
        <div class="flex items-center justify-between mb-3">
            <h1 class="text-xl font-extrabold text-white tracking-tight drop-shadow-xs">Anggaran Bulanan</h1>
            <x-ui.button :href="route('budgets.create', ['period' => $overview->periodKey])" size="sm" class="flex items-center gap-1 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>Buat</span>
            </x-ui.button>
        </div>

        <!-- Planning Sub-Navigation (Anggaran / Tagihan / Tabungan) -->
        <div class="flex items-center bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border border-white/60 dark:border-slate-700/80 p-1 rounded-2xl mb-4 text-xs font-semibold shadow-2xs">
            <a href="{{ route('budgets.index') }}" class="flex-1 py-1.5 text-center rounded-xl bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-xs font-bold transition-all">
                Anggaran
            </a>
            <a href="{{ route('bills.index') }}" class="flex-1 py-1.5 text-center rounded-xl text-slate-600 dark:text-slate-300 hover:text-slate-900 transition-colors">
                Tagihan
            </a>
            <a href="{{ route('savings-goals.index') }}" class="flex-1 py-1.5 text-center rounded-xl text-slate-600 dark:text-slate-300 hover:text-slate-900 transition-colors">
                Tabungan
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium rounded-2xl flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium rounded-2xl flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Month Navigation Bar -->
        @php
            $prevMonth = $overview->startDate->subMonthNoOverflow()->format('Y-m');
            $nextMonth = $overview->startDate->addMonthNoOverflow()->format('Y-m');
            $currentMonthKey = now()->setTimezone(Auth::user()->timezone ?? 'Asia/Jakarta')->format('Y-m');
            $isNotCurrent = $overview->periodKey !== $currentMonthKey;
        @endphp
        <div class="flex items-center justify-between bg-slate-100/80 rounded-2xl p-1.5 mb-4 text-xs">
            <a href="{{ route('budgets.index', ['month' => $prevMonth]) }}" 
               class="p-1.5 rounded-xl text-slate-600 hover:bg-white hover:shadow-xs transition-all" 
               aria-label="Bulan Sebelumnya">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>

            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800">{{ $overview->periodName }}</span>
                @if($isNotCurrent)
                    <a href="{{ route('budgets.index') }}" class="text-[10px] bg-sky-100 text-sky-700 px-2 py-0.5 rounded-full font-semibold hover:bg-sky-200 transition-colors">
                        Bulan Ini
                    </a>
                @endif
            </div>

            <a href="{{ route('budgets.index', ['month' => $nextMonth]) }}" 
               class="p-1.5 rounded-xl text-slate-600 hover:bg-white hover:shadow-xs transition-all" 
               aria-label="Bulan Berikutnya">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <!-- Ringkasan Anggaran Card -->
        <x-ui.card padding="p-4 sm:p-5" class="mb-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ringkasan Anggaran</h2>
                <span class="text-xs font-bold {{ $overview->overallPercentage > 100 ? 'text-rose-600' : 'text-slate-700' }}">
                    {{ $overview->overallPercentage }}% Terpakai
                </span>
            </div>

            <!-- Overall Progress Bar -->
            <div class="w-full bg-slate-100 rounded-full h-2.5 mb-4 overflow-hidden">
                <div class="h-2.5 rounded-full transition-all duration-500 {{ $overview->overallPercentage > 100 ? 'bg-rose-500' : ($overview->overallPercentage >= 80 ? 'bg-amber-500' : 'bg-sky-600') }}" 
                     style="width: {{ min($overview->overallPercentage, 100) }}%"></div>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center divide-x divide-slate-100 pt-1">
                <div>
                    <p class="text-[10px] sm:text-[11px] text-slate-400 font-medium">Batas Total</p>
                    <p class="text-xs sm:text-sm font-bold text-slate-800 mt-1">@money($overview->totalLimit)</p>
                </div>
                <div>
                    <p class="text-[10px] sm:text-[11px] text-slate-400 font-medium">Terpakai</p>
                    <p class="text-xs sm:text-sm font-bold text-slate-800 mt-1">@money($overview->totalSpent)</p>
                </div>
                <div>
                    <p class="text-[10px] sm:text-[11px] text-slate-400 font-medium">{{ $overview->totalRemaining->isNegative() ? 'Defisit' : 'Sisa' }}</p>
                    <p class="text-xs sm:text-sm font-bold {{ $overview->totalRemaining->isNegative() ? 'text-rose-600' : 'text-emerald-600' }} mt-1">
                        {{ $overview->totalRemaining->isNegative() ? '-' : '' }}@money($overview->totalRemaining->abs())
                    </p>
                </div>
            </div>
        </x-ui.card>

        <!-- Kategori List -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Anggaran per Kategori</h2>
                <span class="text-[11px] text-slate-400 font-medium">{{ $overview->budgetsUsage->count() }} Kategori</span>
            </div>
            
            @if($overview->budgetsUsage->isEmpty())
                <x-ui.empty-state 
                    title="Belum Ada Anggaran" 
                    description="Tentukan batas anggaran untuk kategori pengeluaranmu di {{ $overview->periodName }} agar pengeluaran tetap terkontrol."
                    actionText="Buat Anggaran Sekarang"
                    :actionUrl="route('budgets.create', ['period' => $overview->periodKey])"
                />
            @else
                <div class="space-y-3">
                    @foreach($overview->budgetsUsage as $usage)
                        <x-ui.card padding="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 font-bold text-sm">
                                        {{ mb_substr($usage->budget->category->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <h3 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                            {{ $usage->budget->category->name }}
                                        </h3>
                                        <p class="text-[11px] text-slate-400 font-medium">
                                            @money($usage->spent) / @money($usage->limit)
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full inline-block {{ match($usage->colorClass) {
                                        'rose' => 'bg-rose-100 text-rose-700',
                                        'amber' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-emerald-100 text-emerald-700',
                                    } }}">
                                        {{ $usage->statusLabel }}
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-slate-100 rounded-full h-2 my-2.5 overflow-hidden">
                                <div class="h-2 rounded-full transition-all duration-500 {{ match($usage->colorClass) {
                                    'rose' => 'bg-rose-500',
                                    'amber' => 'bg-amber-500',
                                    default => 'bg-emerald-500',
                                } }}" style="width: {{ min($usage->percentage, 100) }}%"></div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                                <div>
                                    <span class="text-slate-400">{{ $usage->isExceeded ? 'Melebihi' : 'Sisa Kuota' }}:</span>
                                    <span class="font-bold {{ $usage->isExceeded ? 'text-rose-600' : 'text-slate-700' }}">
                                        @money($usage->remaining->abs())
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <a href="{{ route('budgets.edit', $usage->budget) }}" class="text-sky-600 hover:text-sky-700 font-semibold">
                                        Ubah
                                    </a>
                                    <span class="text-slate-300">•</span>
                                    <form action="{{ route('budgets.destroy', $usage->budget) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengarsipkan anggaran ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-rose-600 transition-colors">
                                            Arsipkan
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
