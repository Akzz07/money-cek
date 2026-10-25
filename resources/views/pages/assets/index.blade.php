<x-app-layout :activeNav="'asset'">
    <div x-data="{ showTable: false }" class="px-5 pt-6 pb-8 space-y-4">

        <!-- Top Header Navigation -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-extrabold text-white tracking-tight drop-shadow-xs">Aset & Laporan</h1>
                <div class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-white/95 dark:bg-white/90 text-sky-800 dark:text-sky-900 shadow-xs border border-white/80 backdrop-blur-xs">
                        <svg class="w-3.5 h-3.5 me-1 text-sky-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $overview->periodLabel }}
                    </span>
                </div>
            </div>
            
            <a href="{{ route('accounts.index') }}" class="px-3 py-1.5 bg-white/90 hover:bg-white text-slate-700 text-xs font-semibold rounded-xl transition-colors flex items-center gap-1 shadow-2xs border border-white/60 backdrop-blur-sm">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span>Kelola Akun</span>
            </a>
        </div>

        <!-- Period Selection Tabs -->
        <div class="flex items-center bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border border-white/60 dark:border-slate-700/80 p-1 rounded-2xl text-xs font-semibold overflow-x-auto no-scrollbar shadow-2xs">
            <a href="{{ route('assets.index', ['period' => 'this_month']) }}" 
               class="flex-1 py-1.5 px-2.5 text-center rounded-xl whitespace-nowrap transition-all {{ $overview->periodKey === 'this_month' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">
                Bulan Ini
            </a>
            <a href="{{ route('assets.index', ['period' => 'last_month']) }}" 
               class="flex-1 py-1.5 px-2.5 text-center rounded-xl whitespace-nowrap transition-all {{ $overview->periodKey === 'last_month' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">
                Bulan Lalu
            </a>
            <a href="{{ route('assets.index', ['period' => 'last_3_months']) }}" 
               class="flex-1 py-1.5 px-2.5 text-center rounded-xl whitespace-nowrap transition-all {{ $overview->periodKey === 'last_3_months' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">
                3 Bulan
            </a>
            <a href="{{ route('assets.index', ['period' => 'this_year']) }}" 
               class="flex-1 py-1.5 px-2.5 text-center rounded-xl whitespace-nowrap transition-all {{ $overview->periodKey === 'this_year' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">
                Tahun Ini
            </a>
        </div>

        <!-- Ringkasan Kekayaan Bersih (Variant 5 Blue Sky Hero Card) -->
        <div class="bg-gradient-to-br from-[#1E88E5] via-[#0284C7] to-[#38BDF8] rounded-3xl p-5 text-white shadow-xl shadow-sky-500/25 dark:shadow-sky-950/50 border border-white/20 relative overflow-hidden ring-1 ring-inset ring-white/20">
            <!-- Subtle ambient card highlight -->
            <div class="absolute inset-0 bg-gradient-to-tr from-white/[0.12] via-transparent to-white/[0.05] pointer-events-none"></div>

            <div class="relative flex items-center justify-between text-white/90 text-xs font-semibold mb-1">
                <span class="tracking-wide uppercase text-[11px] font-bold">Total Kekayaan Bersih</span>
                <span class="text-[10px] bg-white/20 text-white border border-white/25 backdrop-blur-xs px-2.5 py-0.5 rounded-full font-bold">
                    {{ $overview->accountsSummary->count() }} Akun Aktif
                </span>
            </div>

            <div class="relative text-3xl sm:text-4xl font-extrabold tracking-tight mb-4 text-white">
                @money($overview->totalNetWorth)
            </div>

            <!-- Breakdown by Account Type Chips -->
            <div class="relative grid grid-cols-2 sm:grid-cols-4 gap-2 pt-3 border-t border-white/20 text-xs">
                <div class="bg-white/15 backdrop-blur-md rounded-2xl p-2.5 border border-white/20 shadow-xs">
                    <span class="text-[10px] text-white/80 block font-medium">Bank</span>
                    <span class="font-bold text-white text-xs">
                        @money($overview->typeSummary['bank'] ?? \App\Domain\Money\Money::zero())
                    </span>
                </div>
                <div class="bg-white/15 backdrop-blur-md rounded-2xl p-2.5 border border-white/20 shadow-xs">
                    <span class="text-[10px] text-white/80 block font-medium">E-Wallet</span>
                    <span class="font-bold text-white text-xs">
                        @money($overview->typeSummary['e_wallet'] ?? \App\Domain\Money\Money::zero())
                    </span>
                </div>
                <div class="bg-white/15 backdrop-blur-md rounded-2xl p-2.5 border border-white/20 shadow-xs">
                    <span class="text-[10px] text-white/80 block font-medium">Tunai (Kas)</span>
                    <span class="font-bold text-white text-xs">
                        @money($overview->typeSummary['cash'] ?? \App\Domain\Money\Money::zero())
                    </span>
                </div>
                <div class="bg-white/15 backdrop-blur-md rounded-2xl p-2.5 border border-white/20 shadow-xs">
                    <span class="text-[10px] text-white/80 block font-medium">Investasi</span>
                    <span class="font-bold text-white text-xs">
                        @money($overview->typeSummary['investment'] ?? \App\Domain\Money\Money::zero())
                    </span>
                </div>
            </div>
        </div>

        <!-- Cash Flow Summary for the Period -->
        <x-ui.card padding="p-4 sm:p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Arus Kas Periode</h2>
                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full {{ $overview->savingsRate >= 20.0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                    Rasio Tabungan: {{ $overview->savingsRate }}%
                </span>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center pt-1">
                <div class="p-2.5 bg-emerald-50/70 border border-emerald-100 rounded-2xl">
                    <span class="text-[10px] text-emerald-700 font-bold block mb-0.5">Pemasukan</span>
                    <span class="text-xs sm:text-sm font-black text-emerald-700 block truncate">
                        +@money($overview->totalIncome)
                    </span>
                </div>

                <div class="p-2.5 bg-rose-50/70 border border-rose-100 rounded-2xl">
                    <span class="text-[10px] text-rose-700 font-bold block mb-0.5">Pengeluaran</span>
                    <span class="text-xs sm:text-sm font-black text-rose-700 block truncate">
                        -@money($overview->totalExpense)
                    </span>
                </div>

                <div class="p-2.5 bg-sky-50/70 border border-sky-100 rounded-2xl">
                    <span class="text-[10px] text-sky-700 font-bold block mb-0.5">Arus Kas Bersih</span>
                    <span class="text-xs sm:text-sm font-black {{ $overview->netCashFlow->isNegative() ? 'text-rose-600' : 'text-sky-700' }} block truncate">
                        {{ $overview->netCashFlow->isNegative() ? '-' : '+' }}@money($overview->netCashFlow->abs())
                    </span>
                </div>
            </div>
        </x-ui.card>

        <!-- Visual Cash Flow Bar Chart (Pure CSS Native & Accessible) -->
        <x-ui.card padding="p-4 sm:p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tren Arus Kas (6 Bulan)</h2>
                
                <!-- Chart Legend -->
                <div class="flex items-center gap-3 text-[11px] font-semibold">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-slate-600">Pemasukan</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        <span class="text-slate-600">Pengeluaran</span>
                    </div>
                </div>
            </div>

            <!-- CSS Bar Chart Canvas -->
            <div class="h-44 w-full flex items-end justify-between gap-2 pt-4 pb-2 border-b border-slate-100">
                @foreach($overview->monthlyTrends as $m)
                    <div class="flex-1 flex flex-col items-center h-full justify-end group relative cursor-pointer">
                        
                        <!-- Tooltip on hover/active -->
                        <div class="absolute -top-12 z-20 hidden group-hover:flex flex-col items-center bg-slate-900 text-white text-[9px] py-1 px-2 rounded-lg shadow-lg whitespace-nowrap pointer-events-none">
                            <span>+@money($m->income)</span>
                            <span>-@money($m->expense)</span>
                            <div class="w-2 h-2 bg-slate-900 rotate-45 -mb-1 mt-0.5"></div>
                        </div>

                        <!-- Bar pair container -->
                        <div class="w-full flex items-end justify-center gap-1 h-32">
                            <!-- Income Bar -->
                            <div class="w-2.5 sm:w-3 bg-emerald-500 hover:bg-emerald-600 rounded-t-sm transition-all duration-300"
                                 style="height: {{ $m->incomeBarHeightPercentage }}%;"
                                 aria-label="Pemasukan {{ $m->periodName }}: {{ $m->income->formatted() }}"></div>

                            <!-- Expense Bar -->
                            <div class="w-2.5 sm:w-3 bg-rose-500 hover:bg-rose-600 rounded-t-sm transition-all duration-300"
                                 style="height: {{ $m->expenseBarHeightPercentage }}%;"
                                 aria-label="Pengeluaran {{ $m->periodName }}: {{ $m->expense->formatted() }}"></div>
                        </div>

                        <!-- Month Label -->
                        <span class="text-[10px] font-medium text-slate-500 mt-2 truncate max-w-full">
                            {{ $m->periodName }}
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between pt-2 text-[10px] text-slate-400">
                <span>* Angka transfer antar rekening dan penyesuaian saldo dikecualikan.</span>
                <button type="button" @click="showTable = !showTable" class="text-sky-600 hover:underline font-semibold">
                    <span x-text="showTable ? 'Sembunyikan Tabel' : 'Lihat Data Tabel'"></span>
                </button>
            </div>

            <!-- Accessible Data Table (Toggled for precision) -->
            <div x-show="showTable" x-cloak class="mt-3 overflow-x-auto text-xs">
                <table class="w-full text-left divide-y divide-slate-100">
                    <thead>
                        <tr class="text-slate-400 font-bold text-[10px] uppercase">
                            <th class="py-1.5">Bulan</th>
                            <th class="py-1.5 text-right">Pemasukan</th>
                            <th class="py-1.5 text-right">Pengeluaran</th>
                            <th class="py-1.5 text-right">Net</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 font-medium">
                        @foreach($overview->monthlyTrends as $m)
                            <tr>
                                <td class="py-1 text-slate-700">{{ $m->periodName }}</td>
                                <td class="py-1 text-right text-emerald-600">+@money($m->income)</td>
                                <td class="py-1 text-right text-rose-600">-@money($m->expense)</td>
                                <td class="py-1 text-right font-bold text-slate-800">@money($m->net)</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <!-- Category Expense Breakdown (Layar #8 Mockup) -->
        <x-ui.card padding="p-4 sm:p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Rincian Pengeluaran Kategori</h2>
                <span class="text-xs font-extrabold text-slate-800">
                    Total: @money($overview->totalExpense)
                </span>
            </div>

            @if($overview->categoryBreakdown->isEmpty())
                <div class="text-center py-6 text-slate-400 text-xs">
                    Tidak ada pengeluaran yang tercatat pada periode ini.
                </div>
            @else
                <div class="space-y-3.5 pt-1">
                    @foreach($overview->categoryBreakdown as $catItem)
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $catItem->color }};"></span>
                                    <span class="font-bold text-slate-800">{{ $catItem->categoryName }}</span>
                                </div>
                                <div class="text-right flex items-center gap-2">
                                    <span class="font-extrabold text-slate-800">@money($catItem->amount)</span>
                                    <span class="text-[11px] font-bold text-slate-400 min-w-[36px] text-right">{{ $catItem->percentage }}%</span>
                                </div>
                            </div>

                            <!-- Progress Proportion Bar -->
                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full transition-all duration-500" 
                                     style="width: {{ $catItem->percentage }}%; background-color: {{ $catItem->color }};"></div>
                            </div>

                            @if($catItem->changePercentage !== null)
                                <div class="text-[10px] text-right pt-0.5">
                                    @if($catItem->changePercentage > 0)
                                        <span class="text-rose-600 font-semibold">+{{ $catItem->changePercentage }}% vs periode lalu</span>
                                    @elseif($catItem->changePercentage < 0)
                                        <span class="text-emerald-600 font-semibold">{{ $catItem->changePercentage }}% vs periode lalu</span>
                                    @else
                                        <span class="text-slate-400">Sama dengan periode lalu</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

    </div>
</x-app-layout>
