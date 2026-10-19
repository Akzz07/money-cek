<x-app-layout :activeNav="'budget'">
    <div x-data="{ 
        entryTab: 'contribution',
        deleteModalOpen: false
    }" class="px-5 pt-6 pb-8 space-y-4">

        <!-- Top Header Navigation -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('savings-goals.index') }}" class="w-9 h-9 rounded-full bg-white border border-slate-200/80 shadow-xs flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" aria-label="Kembali ke Target Tabungan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 class="text-xl font-bold text-slate-800 truncate max-w-[200px]">{{ $savingsGoal->name }}</h1>
            </div>

            <div class="flex items-center gap-1.5">
                <a href="{{ route('savings-goals.edit', $savingsGoal) }}" class="p-2 text-slate-600 hover:bg-slate-100 rounded-xl transition-colors" aria-label="Edit Target">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium rounded-2xl flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium rounded-2xl flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Goal Hero Card -->
        <x-ui.card padding="p-5" class="relative overflow-hidden bg-gradient-to-br from-white to-slate-50 border-slate-200">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white shrink-0 shadow-xs"
                         style="background-color: {{ $savingsGoal->color ?? '#2563EB' }};">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Target Tabungan</span>
                        <h2 class="text-lg font-bold text-slate-800 leading-tight">{{ $savingsGoal->name }}</h2>
                    </div>
                </div>

                <span class="text-xs font-extrabold px-2.5 py-0.5 rounded-full border {{ $savingsGoal->status->badgeClasses() }}">
                    {{ $savingsGoal->status->label() }}
                </span>
            </div>

            <div class="space-y-1 my-3">
                <div class="flex items-baseline justify-between">
                    <div class="text-2xl font-extrabold text-slate-900">
                        @money($progress->currentAmount)
                    </div>
                    <div class="text-xs font-bold text-slate-400">
                        Target @money($progress->targetAmount)
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden">
                    <div class="h-3 rounded-full transition-all duration-500" 
                         style="width: {{ min($progress->percentage, 100) }}%; background-color: {{ $savingsGoal->color ?? '#2563EB' }};"></div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <span class="font-extrabold text-sky-600">{{ $progress->percentage }}% Tercapai</span>
                    @if($progress->surplusAmount)
                        <span class="font-bold text-emerald-600">Surplus: +@money($progress->surplusAmount)</span>
                    @else
                        <span class="text-slate-500 font-medium">Kurang @money($progress->remainingAmount)</span>
                    @endif
                </div>
            </div>

            <!-- Meta details -->
            <div class="grid grid-cols-2 gap-2 pt-3 border-t border-slate-100 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">Tenggat Waktu:</span>
                    <span class="font-bold text-slate-700">
                        {{ $savingsGoal->target_date ? $savingsGoal->target_date->translatedFormat('d F Y') : 'Tanpa Tenggat' }}
                    </span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Rekening Simpanan:</span>
                    <span class="font-bold text-slate-700">
                        {{ $savingsGoal->targetAccount ? $savingsGoal->targetAccount->name : 'Virtual / Dompet Mandiri' }}
                    </span>
                </div>
            </div>

            @if($savingsGoal->notes)
                <p class="text-xs text-slate-500 mt-2.5 pt-2 border-t border-slate-100">
                    {{ $savingsGoal->notes }}
                </p>
            @endif
        </x-ui.card>

        <!-- Record Mutation Form Card -->
        <x-ui.card padding="p-4 sm:p-5">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Catat Mutasi Tabungan</h3>
            
            <!-- Toggle Tabs: Setor vs Tarik -->
            <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-100 rounded-xl mb-3.5 text-xs font-semibold">
                <button type="button" 
                        @click="entryTab = 'contribution'"
                        :class="entryTab === 'contribution' ? 'bg-white text-emerald-700 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-700'"
                        class="py-1.5 rounded-lg transition-all flex items-center justify-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span>Setor (+)</span>
                </button>
                <button type="button" 
                        @click="entryTab = 'withdrawal'"
                        :class="entryTab === 'withdrawal' ? 'bg-white text-rose-700 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-700'"
                        class="py-1.5 rounded-lg transition-all flex items-center justify-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                    <span>Tarik (-)</span>
                </button>
            </div>

            <form action="{{ route('savings-goals.entries.store', $savingsGoal) }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="direction" :value="entryTab">

                <x-ui.money-input 
                    name="amount" 
                    label="Nominal Transaksi" 
                    placeholder="0" 
                    :required="true"
                    :showPresets="true" />

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Tanggal</label>
                    <input type="date" name="entry_date" required value="{{ now()->toDateString() }}" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">
                        <span x-text="entryTab === 'contribution' ? 'Rekening Sumber (opsional)' : 'Rekening Tujuan Penerima (opsional)'"></span>
                    </label>
                    <select name="account_id" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Hanya Catat Alokasi (Tanpa Transfer) --</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} (Saldo: @money($acc->opening_balance))</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Catatan (opsional)</label>
                    <input type="text" name="notes" placeholder="Contoh: Tabungan gaji bulan ini" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>

                <div class="pt-1">
                    <button type="submit" 
                            :class="entryTab === 'contribution' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
                            class="w-full py-2.5 px-3 text-white font-bold text-xs rounded-xl transition-colors shadow-xs">
                        <span x-text="entryTab === 'contribution' ? 'Simpan Setoran Dana' : 'Simpan Penarikan Dana'"></span>
                    </button>
                </div>
            </form>
        </x-ui.card>

        <!-- Ledger Entries History -->
        <x-ui.card padding="p-4 sm:p-5">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Riwayat Ledger Mutasi</h3>

            @if($savingsGoal->entries->isEmpty())
                <div class="text-center py-6 text-slate-400 text-xs">
                    Belum ada catatan mutasi setoran atau penarikan pada target ini.
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($savingsGoal->entries as $entry)
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ $entry->direction->value === 'contribution' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                    @if($entry->direction->value === 'contribution')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                    @endif
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800">
                                        {{ $entry->direction->label() }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ $entry->entry_date->translatedFormat('d M Y') }}
                                        @if($entry->notes)
                                            • <span class="text-slate-600">{{ $entry->notes }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="text-right">
                                <div class="font-extrabold {{ $entry->direction->value === 'contribution' ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $entry->direction->value === 'contribution' ? '+' : '-' }}@money($entry->amount)
                                </div>
                                @if($entry->linkedTransaction)
                                    <span class="text-[9px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-medium">
                                        Transfer #{{ $entry->linked_transaction_id }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

    </div>
</x-app-layout>
