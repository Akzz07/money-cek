<x-app-layout :activeNav="'home'">
    <div class="px-5 pt-6 pb-8 space-y-4">

        <!-- Top Header Navigation -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="w-9 h-9 rounded-full bg-white/90 border border-white/60 shadow-xs flex items-center justify-center text-slate-700 hover:bg-white backdrop-blur-sm transition-colors" aria-label="Kembali ke Beranda">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 class="text-xl font-extrabold text-white tracking-tight drop-shadow-xs">Kalkulasi Safe-to-Spend</h1>
            </div>
            <span class="text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-full bg-white/90 text-sky-800 border border-white/60 shadow-xs backdrop-blur-xs">
                Formula Transparan
            </span>
        </div>

        <!-- Hero Safe-to-Spend Card -->
        <div class="rounded-3xl p-5 text-white shadow-lg relative overflow-hidden transition-all {{ $result->safeToSpend->isNegative() ? 'bg-gradient-to-br from-rose-600 via-rose-600 to-rose-700 shadow-rose-500/25' : 'bg-gradient-to-br from-[#1E88E5] via-[#0284C7] to-[#38BDF8] shadow-sky-500/25' }}">
            <div class="flex items-center justify-between text-white/80 text-xs font-semibold mb-1">
                <span>Dana Aman Dibelanjakan</span>
                <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full backdrop-blur-xs">
                    {{ $result->daysRemaining }} hari lagi
                </span>
            </div>

            <div class="text-3xl font-extrabold tracking-tight mb-2">
                @money($result->safeToSpend)
            </div>

            <p class="text-xs text-white/90 font-medium">
                Estimasi s/d <span class="font-bold underline decoration-white/40">{{ $result->planningDate->translatedFormat('d F Y') }}</span> (Hari Gajian / Rencana).
            </p>

            @if($result->safeToSpend->isNegative())
                <div class="mt-3 p-2.5 bg-rose-800/40 border border-white/20 rounded-xl text-xs text-white/95 flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-amber-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Perhatian: Kewajiban tagihan dan alokasi tabungan Anda melebihi saldo kas operasional saat ini.</span>
                </div>
            @endif
        </div>

        <!-- Planning Date Simulator -->
        <x-ui.card padding="p-4" class="bg-slate-50/70 border-slate-200">
            <form action="{{ route('safe-to-spend.show') }}" method="GET" class="flex items-end gap-2 text-xs">
                <div class="flex-1">
                    <label for="planning_date" class="block font-bold text-slate-600 mb-1">Simulasi Tanggal Perencanaan Lain</label>
                    <input type="date" 
                           id="planning_date" 
                           name="planning_date" 
                           value="{{ $result->planningDate->toDateString() }}" 
                           class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-sky-500 font-medium" />
                </div>
                <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition-colors shrink-0">
                    Hitung Ulang
                </button>
            </form>
        </x-ui.card>

        <!-- Formula Breakdown Card -->
        <x-ui.card padding="p-5" class="space-y-4">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Rincian Komponen Perhitungan</h2>

            <!-- 1. Saldo Kas Harian -->
            <div class="space-y-2 pb-3 border-b border-slate-100">
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded-md bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center text-[11px]">+</span>
                        <span class="font-bold text-slate-800">Total Saldo Kas Operasional</span>
                    </div>
                    <span class="font-extrabold text-emerald-600">@money($result->spendableBalance)</span>
                </div>
                <div class="pl-7 space-y-1">
                    @forelse($result->spendableAccounts as $item)
                        <div class="flex items-center justify-between text-[11px] text-slate-500">
                            <span>{{ $item['account']->name }} ({{ $item['account']->type->label() }})</span>
                            <span class="font-medium text-slate-700">@money($item['balance'])</span>
                        </div>
                    @empty
                        <div class="text-[11px] text-slate-400 italic">Tidak ada rekening operasional aktif yang ditandai spendable.</div>
                    @endforelse
                </div>
            </div>

            <!-- 2. Tagihan Belum Dibayar -->
            <div class="space-y-2 pb-3 border-b border-slate-100">
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded-md bg-rose-100 text-rose-700 font-bold flex items-center justify-center text-[11px]">-</span>
                        <span class="font-bold text-slate-800">Tagihan s/d Tanggal Rencana</span>
                    </div>
                    <span class="font-extrabold text-rose-600">-@money($result->unpaidBills)</span>
                </div>
                <div class="pl-7 space-y-1">
                    @forelse($result->upcomingBills as $bill)
                        <div class="flex items-center justify-between text-[11px] text-slate-500">
                            <span>{{ $bill->name }} (Jatuh tempo: {{ $bill->due_date->translatedFormat('d M') }})</span>
                            <span class="font-medium text-slate-700">@money($bill->amount)</span>
                        </div>
                    @empty
                        <div class="text-[11px] text-slate-400 italic">Tidak ada tagihan tertunda hingga tanggal perencanaan.</div>
                    @endforelse
                </div>
            </div>

            <!-- 3. Alokasi Tabungan (Anti Double-Deduction) -->
            <div class="space-y-2 pb-3 border-b border-slate-100">
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded-md bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-[11px]">-</span>
                        <span class="font-bold text-slate-800">Cadangan Target Tabungan</span>
                    </div>
                    <span class="font-extrabold text-indigo-600">-@money($result->pendingAllocations)</span>
                </div>
                <div class="pl-7 space-y-1">
                    @forelse($result->allocations as $alloc)
                        <div class="flex items-center justify-between text-[11px] text-slate-500">
                            <div>
                                <span class="font-medium text-slate-700">{{ $alloc['goal']->name }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ $alloc['reason'] }}</span>
                            </div>
                            <span class="font-medium text-slate-700 shrink-0">@money($alloc['amount'])</span>
                        </div>
                    @empty
                        <div class="text-[11px] text-slate-400 italic">Tidak ada cadangan tabungan yang harus disisihkan dari kas harian.</div>
                    @endforelse
                </div>
            </div>

            <!-- Summary Total -->
            <div class="pt-1 flex items-center justify-between text-sm">
                <span class="font-extrabold text-slate-800">Hasil Safe-to-Spend:</span>
                <span class="text-base font-black {{ $result->safeToSpend->isNegative() ? 'text-rose-600' : 'text-sky-600' }}">
                    @money($result->safeToSpend)
                </span>
            </div>
        </x-ui.card>

        <!-- Transparent Assumptions & Disclaimer -->
        <x-ui.card padding="p-4 sm:p-5" class="bg-sky-50/40 border-sky-100">
            <h3 class="text-xs font-bold text-slate-700 mb-2 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Asumsi Transparan & Kebijakan Deduksi</span>
            </h3>

            <ul class="space-y-1.5 text-xs text-slate-600 mb-3">
                @foreach($result->assumptions as $assumption)
                    <li class="flex items-start gap-2">
                        <span class="text-sky-500 font-bold">•</span>
                        <span>{{ $assumption }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="p-2.5 bg-white/80 rounded-xl border border-sky-200/60 text-[11px] text-slate-500 italic">
                Disclaimer: Safe-to-Spend merupakan estimasi perencanaan keuangan mandiri berbasis input riil Anda dan bukan merupakan jaminan, kepastian, atau nasihat keuangan formal.
            </div>
        </x-ui.card>

    </div>
</x-app-layout>
