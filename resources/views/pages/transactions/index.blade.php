<x-app-layout :activeNav="'activity'">
    <div x-data="{ 
        filterOpen: {{ $filter->accountId || $filter->categoryId || $filter->startDate || $filter->endDate ? 'true' : 'false' }},
        selectedTx: null,
        voidModalOpen: false,
        voidTxId: null,
        voidTxDescription: '',
        openDetail(tx) {
            this.selectedTx = tx;
        },
        confirmVoid(id, desc) {
            this.voidTxId = id;
            this.voidTxDescription = desc;
            this.voidModalOpen = true;
        }
    }" class="px-5 pt-6 pb-6">

        <!-- Title Header -->
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-bold text-slate-800">Riwayat Transaksi</h1>
            <div class="flex items-center gap-1.5">
                <a href="{{ route('transactions.export', request()->query()) }}" class="p-2 text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-colors border border-slate-200/80 shadow-2xs flex items-center gap-1 text-xs font-semibold" title="Ekspor CSV">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>CSV</span>
                </a>
                <x-ui.button :href="route('transactions.create')" size="sm" class="flex items-center gap-1 shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span>Catat</span>
                </x-ui.button>
            </div>
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

        <!-- Search Bar & Filter Toggle -->
        <form method="GET" action="{{ route('transactions.index') }}" class="mb-3 space-y-2">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif

            <div class="relative flex items-center">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Cari transaksi atau catatan..." 
                       class="w-full pl-10 pr-20 py-2.5 text-xs bg-white border border-slate-200/80 rounded-2xl focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all shadow-xs" />
                
                <div class="absolute right-1.5 flex items-center gap-1">
                    @if(request('search'))
                        <a href="{{ route('transactions.index', array_filter(request()->except('search'))) }}" class="p-1 text-slate-400 hover:text-slate-600" title="Hapus pencarian">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    <button type="button" 
                            @click="filterOpen = !filterOpen" 
                            :class="filterOpen ? 'bg-sky-50 text-sky-600 border-sky-200' : 'bg-slate-50 text-slate-600 border-slate-200'"
                            class="px-2.5 py-1 text-[11px] font-semibold border rounded-xl flex items-center gap-1 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        <span>Filter</span>
                    </button>
                </div>
            </div>

            <!-- Advanced Filter Panel (Collapsible) -->
            <div x-show="filterOpen" x-cloak class="p-3 bg-white border border-slate-200/80 rounded-2xl shadow-xs space-y-3 text-xs">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Akun</label>
                        <select name="account_id" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-sky-500 focus:outline-none">
                            <option value="">Semua Akun</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" @selected(request('account_id') == $acc->id)>
                                    {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Kategori</label>
                        <select name="category_id" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-sky-500 focus:outline-none">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dari Tanggal</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-sky-500 focus:outline-none" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-sky-500 focus:outline-none" />
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <a href="{{ route('transactions.index') }}" class="text-[11px] text-slate-500 hover:text-slate-800 font-semibold underline">
                        Reset Semua Filter
                    </a>
                    <button type="submit" class="px-4 py-1.5 bg-sky-600 text-white font-semibold rounded-xl text-xs hover:bg-sky-700 transition-colors">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>

        <!-- Type Filter Chips -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-3 mb-2 text-xs">
            @php
                $baseParams = request()->except(['type', 'page']);
            @endphp
            <a href="{{ route('transactions.index', $baseParams) }}" 
               class="px-3.5 py-1.5 rounded-full font-medium transition-all {{ !request('type') ? 'bg-sky-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Semua
            </a>
            <a href="{{ route('transactions.index', array_merge($baseParams, ['type' => 'expense'])) }}" 
               class="px-3.5 py-1.5 rounded-full font-medium transition-all {{ request('type') === 'expense' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Pengeluaran
            </a>
            <a href="{{ route('transactions.index', array_merge($baseParams, ['type' => 'income'])) }}" 
               class="px-3.5 py-1.5 rounded-full font-medium transition-all {{ request('type') === 'income' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Pemasukan
            </a>
            <a href="{{ route('transactions.index', array_merge($baseParams, ['type' => 'transfer'])) }}" 
               class="px-3.5 py-1.5 rounded-full font-medium transition-all {{ request('type') === 'transfer' ? 'bg-sky-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Transfer
            </a>
        </div>

        @if($transactions->isEmpty())
            @if($filter->hasActiveFilters())
                <x-ui.empty-state 
                    title="Tidak Ada Transaksi Ditemukan" 
                    description="Tidak ada transaksi yang cocok dengan kata kunci atau filter yang dipilih."
                    actionText="Reset Semua Filter"
                    :actionUrl="route('transactions.index')"
                />
            @else
                <x-ui.empty-state 
                    title="Belum Ada Transaksi" 
                    description="Catat pengeluaran dan pemasukan harianmu untuk memantau arus kas secara real-time."
                    actionText="Catat Transaksi Pertama"
                    :actionUrl="route('transactions.create')"
                />
            @endif
        @else
            <!-- Transactions List Grouped by Date -->
            @php
                $grouped = $transactions->groupBy(fn($t) => $t->transaction_date->format('d M Y'));
            @endphp

            <div class="space-y-4">
                @foreach($grouped as $dateLabel => $items)
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">{{ $dateLabel }}</p>
                        <div class="space-y-2">
                            @foreach($items as $tx)
                                <div @click="openDetail({
                                        id: {{ $tx->id }},
                                        type: '{{ $tx->type->value }}',
                                        typeLabel: '{{ $tx->type->label() }}',
                                        amount: '@money($tx->amount)',
                                        description: '{{ addslashes($tx->description ?: ($tx->category?->name ?? $tx->type->label())) }}',
                                        notes: '{{ addslashes($tx->notes ?? '') }}',
                                        categoryName: '{{ addslashes($tx->category?->name ?? '-') }}',
                                        sourceAccount: '{{ addslashes($tx->sourceAccount?->name ?? '-') }}',
                                        destinationAccount: '{{ addslashes($tx->destinationAccount?->name ?? '') }}',
                                        date: '{{ $tx->transaction_date->format('d M Y') }}',
                                        isVoided: {{ $tx->isVoided() ? 'true' : 'false' }},
                                        voidReason: '{{ addslashes($tx->void_reason ?? '') }}',
                                        voidedAt: '{{ $tx->voided_at?->format('d M Y H:i') ?? '' }}'
                                    })"
                                     class="cursor-pointer">
                                    <x-ui.card padding="p-3.5" class="hover:border-slate-300 transition-colors {{ $tx->isVoided() ? 'opacity-60 bg-slate-50/80' : '' }}">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <!-- Category Icon Avatar -->
                                                <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 font-bold text-sm {{ match($tx->type->value) {
                                                    'income' => 'bg-emerald-50 text-emerald-600',
                                                    'expense' => 'bg-rose-50 text-rose-600',
                                                    'transfer' => 'bg-sky-50 text-sky-600',
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
                                                    <h2 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                                        {{ $tx->description ?: ($tx->category?->name ?? $tx->type->label()) }}
                                                        @if($tx->isVoided())
                                                            <span class="text-[10px] bg-slate-200 text-slate-600 px-1.5 py-0.2 rounded font-medium">Batal</span>
                                                        @endif
                                                    </h2>
                                                    <p class="text-[11px] text-slate-400">
                                                        {{ $tx->category?->name ?? $tx->type->label() }} • {{ $tx->sourceAccount?->name }}
                                                        @if($tx->destinationAccount)
                                                            → {{ $tx->destinationAccount->name }}
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="text-right">
                                                <p class="text-xs sm:text-sm font-bold {{ $tx->isVoided() ? 'line-through text-slate-400' : match($tx->type->value) {
                                                    'income' => 'text-emerald-600',
                                                    'expense' => 'text-slate-800',
                                                    'transfer' => 'text-sky-600',
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
                                                <p class="text-[10px] text-slate-400 font-medium">Klik untuk rincian</p>
                                            </div>
                                        </div>
                                    </x-ui.card>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5">
                {{ $transactions->links() }}
            </div>
        @endif

        <!-- Transaction Detail Modal -->
        <div x-show="selectedTx !== null" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4">
            
            <div @click.away="selectedTx = null" 
                 class="bg-white w-full max-w-md rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl space-y-4">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-800">Rincian Transaksi</h3>
                    <button @click="selectedTx = null" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <template x-if="selectedTx">
                    <div class="space-y-3.5 text-xs">
                        <div class="text-center py-2">
                            <span class="text-2xl font-extrabold" :class="{
                                'text-emerald-600': selectedTx.type === 'income',
                                'text-slate-900': selectedTx.type === 'expense',
                                'text-sky-600': selectedTx.type === 'transfer',
                                'text-purple-700': selectedTx.type === 'adjustment'
                            }" x-text="selectedTx.amount"></span>
                            <div class="mt-1">
                                <span class="px-2 py-0.5 rounded-full font-semibold text-[11px]" :class="selectedTx.isVoided ? 'bg-slate-200 text-slate-700' : 'bg-sky-100 text-sky-700'" x-text="selectedTx.isVoided ? 'Dibatalkan (Void)' : selectedTx.typeLabel"></span>
                            </div>
                        </div>

                        <div class="space-y-2 bg-slate-50 rounded-2xl p-3.5">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Deskripsi:</span>
                                <span class="font-bold text-slate-800 text-right" x-text="selectedTx.description"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Tanggal:</span>
                                <span class="font-semibold text-slate-800" x-text="selectedTx.date"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Akun Sumber:</span>
                                <span class="font-semibold text-slate-800" x-text="selectedTx.sourceAccount"></span>
                            </div>
                            <template x-if="selectedTx.destinationAccount">
                                <div class="flex justify-between">
                                    <span class="text-slate-400">Akun Tujuan:</span>
                                    <span class="font-semibold text-slate-800" x-text="selectedTx.destinationAccount"></span>
                                </div>
                            </template>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Kategori:</span>
                                <span class="font-semibold text-slate-800" x-text="selectedTx.categoryName"></span>
                            </div>
                            <template x-if="selectedTx.notes">
                                <div class="pt-2 border-t border-slate-200/60">
                                    <span class="text-slate-400 block mb-0.5">Catatan Tambahan:</span>
                                    <p class="font-medium text-slate-700" x-text="selectedTx.notes"></p>
                                </div>
                            </template>
                            <template x-if="selectedTx.isVoided">
                                <div class="pt-2 border-t border-slate-200/60 text-rose-600">
                                    <span class="font-semibold block mb-0.5">Alasan Pembatalan:</span>
                                    <p class="font-medium" x-text="selectedTx.voidReason"></p>
                                    <p class="text-[10px] text-slate-400 mt-1" x-text="'Dibatalkan pada: ' + selectedTx.voidedAt"></p>
                                </div>
                            </template>
                        </div>

                        <!-- Void Action Button (Only if not voided) -->
                        <template x-if="!selectedTx.isVoided">
                            <div class="pt-2">
                                <button type="button" 
                                        @click="confirmVoid(selectedTx.id, selectedTx.description); selectedTx = null;" 
                                        class="w-full py-2.5 px-4 bg-rose-50 text-rose-700 hover:bg-rose-100 font-semibold rounded-2xl transition-colors flex items-center justify-center gap-1.5 border border-rose-200/80">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>Batalkan Transaksi Ini (Void)</span>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- Void Confirmation Modal -->
        <div x-show="voidModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            
            <div @click.away="voidModalOpen = false" 
                 class="bg-white w-full max-w-sm rounded-3xl p-6 shadow-2xl space-y-4">
                
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>

                <div class="text-center">
                    <h3 class="text-base font-bold text-slate-800">Batalkan Transaksi?</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Transaksi <span class="font-semibold text-slate-800" x-text="voidTxDescription"></span> akan dibatalkan secara permanen dan saldo akun akan otomatis dipulihkan.
                    </p>
                </div>

                <form :action="'/transactions/' + voidTxId + '/void'" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Alasan Pembatalan (Wajib, min. 5 karakter)</label>
                        <textarea name="reason" 
                                  required 
                                  minlength="5" 
                                  rows="2" 
                                  placeholder="Contoh: Salah input nominal / transaksi dibatalkan merchant" 
                                  class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-rose-500"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button type="button" 
                                @click="voidModalOpen = false" 
                                class="py-2.5 px-3 bg-slate-100 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-200 transition-colors">
                            Kembali
                        </button>
                        <button type="submit" 
                                class="py-2.5 px-3 bg-rose-600 text-white font-semibold text-xs rounded-xl hover:bg-rose-700 transition-colors">
                            Ya, Batalkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
