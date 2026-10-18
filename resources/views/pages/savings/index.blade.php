<x-app-layout :activeNav="'budget'">
    <div x-data="{ 
        entryModalOpen: false,
        entryGoalId: null,
        entryGoalName: '',
        entryGoalColor: '#2563EB',
        entryDirection: 'contribution',
        openEntryModal(id, name, color, direction = 'contribution') {
            this.entryGoalId = id;
            this.entryGoalName = name;
            this.entryGoalColor = color || '#2563EB';
            this.entryDirection = direction;
            this.entryModalOpen = true;
        }
    }" class="px-5 pt-6 pb-6">

        <!-- Top Header & Action -->
        <div class="flex items-center justify-between mb-3">
            <h1 class="text-xl font-bold text-slate-800">Target Tabungan</h1>
            
            <x-ui.button :href="route('savings-goals.create')" size="sm" class="flex items-center gap-1 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah</span>
            </x-ui.button>
        </div>

        <!-- Planning Sub-Navigation (Anggaran / Tagihan / Tabungan) -->
        <div class="flex items-center bg-slate-100/90 p-1 rounded-2xl mb-4 text-xs font-semibold">
            <a href="{{ route('budgets.index') }}" class="flex-1 py-1.5 text-center rounded-xl text-slate-500 hover:text-slate-800 transition-colors">
                Anggaran
            </a>
            <a href="{{ route('bills.index') }}" class="flex-1 py-1.5 text-center rounded-xl text-slate-500 hover:text-slate-800 transition-colors">
                Tagihan
            </a>
            <a href="{{ route('savings-goals.index') }}" class="flex-1 py-1.5 text-center rounded-xl bg-white text-blue-600 shadow-xs font-bold transition-all">
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

        <!-- Summary Overview Card (Layar #7 Mockup) -->
        <x-ui.card padding="p-4 sm:p-5" class="mb-4 bg-gradient-to-br from-white to-blue-50/40 border-blue-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Terkumpul</span>
                <span class="text-xs font-extrabold text-blue-600 bg-blue-100/80 px-2.5 py-0.5 rounded-full">
                    {{ $overview->overallPercentage }}% Tercapai
                </span>
            </div>

            <div class="flex items-baseline gap-2 mb-3">
                <div class="text-2xl font-extrabold text-slate-900 tracking-tight">
                    @money($overview->totalSaved)
                </div>
                <div class="text-xs text-slate-400 font-medium">
                    / @money($overview->totalTarget)
                </div>
            </div>

            <!-- Overall Progress Bar -->
            <div class="w-full bg-slate-200/80 rounded-full h-2.5 mb-3.5 overflow-hidden">
                <div class="h-2.5 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 transition-all duration-500" 
                     style="width: {{ min($overview->overallPercentage, 100) }}%"></div>
            </div>

            <!-- Mini Summary Stats -->
            <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100 text-center">
                <div class="p-1.5 bg-slate-50/80 rounded-xl">
                    <div class="text-[10px] text-slate-400 font-medium">Target Aktif</div>
                    <div class="text-xs font-extrabold text-slate-800">{{ $overview->activeGoalsCount }}</div>
                </div>
                <div class="p-1.5 bg-slate-50/80 rounded-xl">
                    <div class="text-[10px] text-slate-400 font-medium">Tercapai</div>
                    <div class="text-xs font-extrabold text-emerald-600">{{ $overview->completedGoalsCount }}</div>
                </div>
                <div class="p-1.5 bg-slate-50/80 rounded-xl">
                    <div class="text-[10px] text-slate-400 font-medium">Sisa Target</div>
                    <div class="text-xs font-extrabold text-slate-800">@money($overview->totalRemaining)</div>
                </div>
            </div>
        </x-ui.card>

        <!-- Filter Status Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-2 mb-3 no-scrollbar text-xs">
            <a href="{{ route('savings-goals.index') }}" 
               class="px-3 py-1.5 rounded-xl font-medium whitespace-nowrap transition-colors {{ empty($statusParam) ? 'bg-slate-800 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Semua
            </a>
            <a href="{{ route('savings-goals.index', ['status' => 'active']) }}" 
               class="px-3 py-1.5 rounded-xl font-medium whitespace-nowrap transition-colors {{ $statusParam === 'active' ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Aktif
            </a>
            <a href="{{ route('savings-goals.index', ['status' => 'completed']) }}" 
               class="px-3 py-1.5 rounded-xl font-medium whitespace-nowrap transition-colors {{ $statusParam === 'completed' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Tercapai
            </a>
            <a href="{{ route('savings-goals.index', ['status' => 'paused']) }}" 
               class="px-3 py-1.5 rounded-xl font-medium whitespace-nowrap transition-colors {{ $statusParam === 'paused' ? 'bg-amber-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Dijeda
            </a>
        </div>

        <!-- Goals List -->
        @if($overview->goals->isEmpty())
            <x-ui.card padding="p-8" class="text-center">
                <div class="w-14 h-14 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800 mb-1">Belum Ada Target Tabungan</h3>
                <p class="text-xs text-slate-500 mb-4 max-w-xs mx-auto">
                    Mulai sisihkan dana untuk impian, dana darurat, liburan, atau pembelian penting Anda.
                </p>
                <x-ui.button :href="route('savings-goals.create')" size="sm">
                    Buat Target Sekarang
                </x-ui.button>
            </x-ui.card>
        @else
            <div class="space-y-3">
                @foreach($overview->goals as $item)
                    @php $goal = $item->goal; @endphp
                    <x-ui.card padding="p-4" class="transition-shadow hover:shadow-md">
                        <div class="flex items-start justify-between gap-3 mb-2.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-white shrink-0 shadow-xs"
                                     style="background-color: {{ $goal->color ?? '#2563EB' }};">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-800 leading-tight">
                                        {{ $goal->name }}
                                    </h3>
                                    <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5">
                                        @if($goal->target_date)
                                            <span>Target: {{ $goal->target_date->translatedFormat('d M Y') }}</span>
                                        @else
                                            <span>Tanpa tenggat waktu</span>
                                        @endif
                                        @if($goal->targetAccount)
                                            <span>• {{ $goal->targetAccount->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full border {{ $goal->status->badgeClasses() }}">
                                {{ $goal->status->label() }}
                            </span>
                        </div>

                        <!-- Progress Bar & Numbers -->
                        <div class="space-y-1 mb-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-extrabold text-slate-800">@money($item->currentAmount)</span>
                                <span class="text-[11px] text-slate-400 font-medium">/ @money($item->targetAmount)</span>
                            </div>

                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full transition-all duration-500" 
                                     style="width: {{ min($item->percentage, 100) }}%; background-color: {{ $goal->color ?? '#2563EB' }};"></div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-slate-500 pt-0.5">
                                <span class="font-bold text-blue-600">{{ $item->percentage }}%</span>
                                @if($item->surplusAmount)
                                    <span class="text-emerald-600 font-bold">Surplus: +@money($item->surplusAmount)</span>
                                @else
                                    <span>Sisa: @money($item->remainingAmount)</span>
                                @endif
                            </div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="flex items-center justify-between pt-2.5 border-t border-slate-100 text-xs">
                            <a href="{{ route('savings-goals.show', $goal) }}" class="text-slate-500 hover:text-slate-800 font-semibold flex items-center gap-1 transition-colors">
                                <span>Lihat Rincian</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </a>

                            <div class="flex items-center gap-1.5">
                                <button type="button" 
                                        @click="openEntryModal({{ $goal->id }}, '{{ addslashes($goal->name) }}', '{{ $goal->color }}', 'contribution')"
                                        class="px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold rounded-xl transition-colors flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    <span>Setor</span>
                                </button>
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif

        <!-- Quick Setor / Entry Modal (Alpine.js) -->
        <div x-show="entryModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            
            <div @click.away="entryModalOpen = false" 
                 class="bg-white w-full max-w-sm rounded-3xl p-6 shadow-2xl space-y-4">
                
                <div class="text-center">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-2 text-white shadow-xs"
                         :style="'background-color: ' + entryGoalColor">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">Setor Tabungan</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Target: <span class="font-bold text-slate-800" x-text="entryGoalName"></span>
                    </p>
                </div>

                <form :action="'/savings-goals/' + entryGoalId + '/entries'" method="POST" class="space-y-3.5">
                    @csrf
                    <input type="hidden" name="direction" value="contribution">

                    <x-ui.money-input 
                        name="amount" 
                        label="Nominal Setoran" 
                        placeholder="Contoh: 500.000" 
                        :required="true"
                        :showPresets="true" />


                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Tanggal</label>
                        <input type="date" name="entry_date" required value="{{ now()->toDateString() }}" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Pilih Akun Sumber (opsional)</label>
                        <select name="account_id" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Hanya Catat Alokasi (Tanpa Transfer) --</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: @money($acc->opening_balance))</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Jika target memiliki rekening tujuan khusus, saldo akan otomatis ditransfer.</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Catatan Tambahan (opsional)</label>
                        <input type="text" name="notes" placeholder="Contoh: Sisa THR / Bonus Proyek" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <button type="button" 
                                @click="entryModalOpen = false" 
                                class="py-2.5 px-3 bg-slate-100 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-200 transition-colors">
                            Batal
                        </button>
                        <button type="submit" 
                                class="py-2.5 px-3 bg-blue-600 text-white font-semibold text-xs rounded-xl hover:bg-blue-700 transition-colors shadow-xs">
                            Simpan Setoran
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
