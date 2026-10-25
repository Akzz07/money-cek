<x-app-layout :activeNav="'budget'">
    <div x-data="{ 
        payModalOpen: false,
        payBillId: null,
        payBillName: '',
        payBillAmount: '',
        openPayModal(id, name, amount) {
            this.payBillId = id;
            this.payBillName = name;
            this.payBillAmount = amount;
            this.payModalOpen = true;
        }
    }" class="px-5 pt-6 pb-6">

        <!-- Top Header & Action -->
        <div class="flex items-center justify-between mb-3">
            <h1 class="text-xl font-extrabold text-white tracking-tight drop-shadow-xs">Tagihan & Kewajiban</h1>
            
            <x-ui.button :href="route('bills.create')" size="sm" class="flex items-center gap-1 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah</span>
            </x-ui.button>
        </div>

        <!-- Planning Sub-Navigation (Anggaran / Tagihan / Tabungan) -->
        <div class="flex items-center bg-slate-100/90 p-1 rounded-2xl mb-4 text-xs font-semibold">
            <a href="{{ route('budgets.index') }}" class="flex-1 py-1.5 text-center rounded-xl text-slate-500 hover:text-slate-800 transition-colors">
                Anggaran
            </a>
            <a href="{{ route('bills.index') }}" class="flex-1 py-1.5 text-center rounded-xl bg-white text-sky-600 shadow-xs font-bold transition-all">
                Tagihan
            </a>
            <a href="{{ route('savings-goals.index') }}" class="flex-1 py-1.5 text-center rounded-xl text-slate-500 hover:text-slate-800 transition-colors">
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

        <!-- Summary Stats Card (Layar #6 Mockup) -->
        <x-ui.card padding="p-4 sm:p-5" class="mb-4">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Ringkasan Tagihan</h2>
            <div class="grid grid-cols-3 gap-2 text-center divide-x divide-slate-100">
                <div>
                    <div class="flex items-center justify-center gap-1">
                        <p class="text-[10px] sm:text-[11px] text-slate-400 font-medium">Mendatang</p>
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    </div>
                    <p class="text-xs sm:text-sm font-bold text-slate-800 mt-1">@money($overview->totalUpcoming)</p>
                    <span class="text-[10px] text-slate-400 font-semibold">{{ $overview->upcomingCount }} item</span>
                </div>
                <div>
                    <div class="flex items-center justify-center gap-1">
                        <p class="text-[10px] sm:text-[11px] text-slate-400 font-medium">Terlambat</p>
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    </div>
                    <p class="text-xs sm:text-sm font-bold {{ $overview->overdueCount > 0 ? 'text-rose-600' : 'text-slate-800' }} mt-1">@money($overview->totalOverdue)</p>
                    <span class="text-[10px] text-slate-400 font-semibold">{{ $overview->overdueCount }} item</span>
                </div>
                <div>
                    <div class="flex items-center justify-center gap-1">
                        <p class="text-[10px] sm:text-[11px] text-slate-400 font-medium">Lunas</p>
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    </div>
                    <p class="text-xs sm:text-sm font-bold text-emerald-600 mt-1">@money($overview->totalPaid)</p>
                    <span class="text-[10px] text-slate-400 font-semibold">{{ $overview->paidCount }} item</span>
                </div>
            </div>
        </x-ui.card>

        <!-- Filter Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-3 mb-2 text-xs">
            <a href="{{ route('bills.index') }}" 
               class="px-3.5 py-1.5 rounded-full font-medium transition-all {{ !request('tab') ? 'bg-sky-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Semua
            </a>
            <a href="{{ route('bills.index', ['tab' => 'upcoming']) }}" 
               class="px-3.5 py-1.5 rounded-full font-medium transition-all {{ request('tab') === 'upcoming' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Mendatang ({{ $overview->upcomingCount }})
            </a>
            <a href="{{ route('bills.index', ['tab' => 'overdue']) }}" 
               class="px-3.5 py-1.5 rounded-full font-medium transition-all {{ request('tab') === 'overdue' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Terlambat ({{ $overview->overdueCount }})
            </a>
            <a href="{{ route('bills.index', ['tab' => 'paid']) }}" 
               class="px-3.5 py-1.5 rounded-full font-medium transition-all {{ request('tab') === 'paid' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                Lunas ({{ $overview->paidCount }})
            </a>
        </div>

        <!-- Bills List -->
        @if($overview->bills->isEmpty())
            <x-ui.empty-state 
                title="Belum Ada Tagihan" 
                description="Catat tagihan rutin bulananmu seperti PLN, Wifi, Air, dan BPJS untuk memantau jatuh tempo secara tepat waktu."
                actionText="Tambah Tagihan Sekarang"
                :actionUrl="route('bills.create')"
            />
        @else
            <div class="space-y-3">
                @foreach($overview->bills as $bill)
                    <x-ui.card padding="p-4" class="{{ $bill->isPaid() ? 'bg-slate-50/70 border-slate-200/60' : '' }}">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <!-- Service Icon -->
                                <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 {{ match($bill->displayStatusColor()) {
                                    'rose' => 'bg-rose-50 text-rose-600',
                                    'emerald' => 'bg-emerald-50 text-emerald-600',
                                    'amber' => 'bg-amber-50 text-amber-600',
                                    default => 'bg-slate-100 text-slate-600',
                                } }}">
                                    @if(str_contains(strtolower($bill->name), 'listrik') || str_contains(strtolower($bill->name), 'pln'))
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    @elseif(str_contains(strtolower($bill->name), 'wifi') || str_contains(strtolower($bill->name), 'internet'))
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                                    @elseif(str_contains(strtolower($bill->name), 'air') || str_contains(strtolower($bill->name), 'pdam'))
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    @endif
                                </div>

                                <div>
                                    <h3 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                        {{ $bill->name }}
                                    </h3>
                                    <p class="text-[11px] text-slate-400">
                                        Jatuh tempo: <span class="font-semibold {{ $bill->isOverdue() ? 'text-rose-600 font-bold' : 'text-slate-600' }}">{{ $bill->due_date->format('d M Y') }}</span>
                                        @if($bill->category)
                                            • {{ $bill->category->name }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="text-right">
                                <p class="text-xs sm:text-sm font-bold text-slate-800">
                                    @money($bill->amount)
                                </p>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full inline-block {{ match($bill->displayStatusColor()) {
                                    'rose' => 'bg-rose-100 text-rose-700',
                                    'emerald' => 'bg-emerald-100 text-emerald-700',
                                    'amber' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-slate-200 text-slate-600',
                                } }}">
                                    {{ $bill->displayStatusLabel() }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Action Footer -->
                        <div class="flex items-center justify-between pt-3 mt-3 border-t border-slate-100 text-xs">
                            <div class="flex items-center gap-2">
                                @if(! $bill->isPaid())
                                    <a href="{{ route('bills.edit', $bill) }}" class="text-slate-500 hover:text-slate-700 font-semibold">
                                        Ubah
                                    </a>
                                    <span class="text-slate-300">•</span>
                                    <form action="{{ route('bills.skip', $bill) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-slate-400 hover:text-slate-600">
                                            Lewati
                                        </button>
                                    </form>
                                    <span class="text-slate-300">•</span>
                                    <form action="{{ route('bills.destroy', $bill) }}" method="POST" onsubmit="return confirm('Hapus tagihan ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-rose-600">
                                            Hapus
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        Lunas tercatat
                                    </span>
                                @endif
                            </div>

                            <div>
                                @if(! $bill->isPaid())
                                    <button type="button" 
                                            @click="openPayModal({{ $bill->id }}, '{{ addslashes($bill->name) }}', '@money($bill->amount)')" 
                                            class="px-3.5 py-1.5 bg-sky-600 hover:bg-sky-700 text-white font-semibold rounded-xl text-xs shadow-xs transition-colors">
                                        Bayar Sekarang
                                    </button>
                                @else
                                    <form action="{{ route('bills.cancel-payment', $bill) }}" method="POST" onsubmit="return confirm('Batalkan pembayaran tagihan ini? Transaksi pengeluaran terkait akan di-void.');">
                                        @csrf
                                        <button type="submit" class="text-[11px] text-rose-600 hover:text-rose-700 font-semibold underline">
                                            Batalkan Lunas
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif

        <!-- Pay Bill Modal -->
        <div x-show="payModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            
            <div @click.away="payModalOpen = false" 
                 class="bg-white w-full max-w-sm rounded-3xl p-6 shadow-2xl space-y-4">
                
                <div class="text-center">
                    <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">Bayar Tagihan</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <span class="font-bold text-slate-800" x-text="payBillName"></span> sebesar <span class="font-extrabold text-sky-600" x-text="payBillAmount"></span>
                    </p>
                </div>

                <form :action="'/bills/' + payBillId + '/pay'" method="POST" class="space-y-3.5">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Pilih Akun Sumber Pembayaran</label>
                        <select name="source_account_id" required class="w-full px-3 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500">
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->type->label() }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Catatan Tambahan (opsional)</label>
                        <input type="text" name="notes" placeholder="Contoh: No. Referensi Transaksi" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <button type="button" 
                                @click="payModalOpen = false" 
                                class="py-2.5 px-3 bg-slate-100 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-200 transition-colors">
                            Batal
                        </button>
                        <button type="submit" 
                                class="py-2.5 px-3 bg-sky-600 text-white font-semibold text-xs rounded-xl hover:bg-sky-700 transition-colors">
                            Konfirmasi Bayar
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
