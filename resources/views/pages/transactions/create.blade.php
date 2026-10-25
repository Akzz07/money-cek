<x-app-layout :activeNav="'activity'">
    <div class="px-5 pt-6 pb-8" x-data="{ 
        tab: '{{ old('type', request('type', 'expense')) }}',
        selectedCategory: '{{ old('category_id', request('category_id', '')) }}',
        amount: '{{ old('amount', request('amount', '')) }}',
        submitting: false
    }">
        <!-- Top Navigation Bar -->
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('home') }}" class="w-9 h-9 rounded-full bg-white dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 shadow-xs flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-50 transition-colors" aria-label="Kembali">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-base font-bold text-slate-800 dark:text-slate-100">Catat Transaksi</h1>
            <div class="w-9"></div>
        </div>

        <!-- Voice Command Quick Banner -->
        <button type="button" 
                @click="$dispatch('open-voice-modal')" 
                class="w-full mb-5 p-3 rounded-2xl bg-gradient-to-r from-sky-500/10 via-indigo-500/10 to-sky-500/5 dark:from-sky-950/40 dark:via-indigo-950/40 dark:to-sky-950/20 border border-sky-300/50 dark:border-sky-800/60 flex items-center justify-between group hover:border-sky-400 active:scale-98 transition-all shadow-2xs">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-sky-500 to-indigo-600 text-white flex items-center justify-center shadow-md shadow-sky-500/25 group-hover:scale-105 transition-transform shrink-0">
                    <svg class="w-4.5 h-4.5 animate-pulse" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                </div>
                <div class="text-left min-w-0">
                    <p class="text-xs font-bold text-sky-950 dark:text-sky-200 truncate">Catat Cepat dengan Suara (AI)</p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">Contoh: "Beli kopi 25rb pakai BCA"</p>
                </div>
            </div>
            <span class="text-[11px] font-bold text-sky-600 dark:text-sky-400 bg-white/90 dark:bg-slate-800 px-2.5 py-1 rounded-xl border border-sky-200/80 dark:border-sky-800/80 shadow-2xs shrink-0">
                Bicara 🎤
            </span>
        </button>

        @if(session('error'))
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium rounded-2xl">
                {{ session('error') }}
            </div>
        @endif

        @if($accounts->isEmpty())
            <x-ui.empty-state 
                title="Belum Ada Akun Aktif" 
                description="Anda membutuhkan setidaknya satu akun aktif (Bank/Tunai/E-Wallet) sebelum mencatat transaksi."
                actionText="Buat Akun Sekarang"
                :actionUrl="route('accounts.create')"
            />
        @else
            <!-- Form Container -->
            <form :action="tab === 'transfer' ? '{{ route('transfers.store') }}' : '{{ route('transactions.store') }}'" method="POST" @submit="submitting = true" class="space-y-5">
                @csrf
                <input type="hidden" name="type" :value="tab">
                <input type="hidden" name="client_request_id" value="{{ (string) Illuminate\Support\Str::uuid() }}">

                <!-- Segmented Control Tabs (Pengeluaran vs Pemasukan vs Transfer) -->
                <div class="grid grid-cols-3 bg-slate-200/80 p-1 rounded-2xl gap-1 text-xs font-semibold select-none">
                    <button type="button" 
                            @click="tab = 'expense'; selectedCategory = ''" 
                            :class="tab === 'expense' ? 'bg-white text-rose-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                            class="py-2.5 rounded-xl transition-all text-center flex items-center justify-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 inline-block"></span>
                        <span>Pengeluaran</span>
                    </button>
                    <button type="button" 
                            @click="tab = 'income'; selectedCategory = ''" 
                            :class="tab === 'income' ? 'bg-white text-emerald-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                            class="py-2.5 rounded-xl transition-all text-center flex items-center justify-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                        <span>Pemasukan</span>
                    </button>
                    <button type="button" 
                            @click="tab = 'transfer'; selectedCategory = ''" 
                            :class="tab === 'transfer' ? 'bg-white text-sky-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                            class="py-2.5 rounded-xl transition-all text-center flex items-center justify-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500 inline-block"></span>
                        <span>Transfer</span>
                    </button>
                </div>

                <!-- Input: Jumlah Nominal dengan Format Otomatis & Terbilang -->
                <x-ui.money-input 
                    name="amount" 
                    id="amount"
                    label="Nominal Transaksi" 
                    :value="old('amount', request('amount'))" 
                    placeholder="0" 
                    :required="true"
                    :showPresets="true" />


                <!-- Selector: Akun Sumber -->
                <div>
                    <label for="source_account_id" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5" x-text="tab === 'transfer' ? 'Akun Pengirim (Sumber)' : 'Akun Keuangan'"></label>
                    <select id="source_account_id" 
                            name="source_account_id" 
                            required
                            class="w-full px-4 py-3 bg-white border @error('source_account_id') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-xs">
                        <option value="" disabled {{ old('source_account_id', request('source_account_id')) ? '' : 'selected' }}>Pilih Akun</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ (string) old('source_account_id', request('source_account_id')) === (string) $account->id ? 'selected' : '' }}>
                                {{ $account->name }} ({{ $account->type->label() }})
                            </option>
                        @endforeach
                    </select>
                    @error('source_account_id')
                        <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Selector: Akun Tujuan (Hanya untuk transfer) -->
                <div x-show="tab === 'transfer'" style="display: none;">
                    <label for="destination_account_id" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Akun Penerima (Tujuan)</label>
                    <select id="destination_account_id" 
                            name="destination_account_id" 
                            class="w-full px-4 py-3 bg-white border @error('destination_account_id') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-xs">
                        <option value="" disabled {{ old('destination_account_id') ? '' : 'selected' }}>Pilih Akun Penerima</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ (string) old('destination_account_id') === (string) $account->id ? 'selected' : '' }}>
                                {{ $account->name }} ({{ $account->type->label() }})
                            </option>
                        @endforeach
                    </select>
                    @error('destination_account_id')
                        <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Selector: Kategori (Grid Chips) (Hanya untuk Income & Expense) -->
                <div x-show="tab !== 'transfer'">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Kategori</label>
                    
                    <!-- Expense Categories Chips -->
                    <div x-show="tab === 'expense'" class="grid grid-cols-3 gap-2">
                        @foreach($expenseCategories as $cat)
                            <label class="cursor-pointer">
                                <input type="radio" name="category_id" value="{{ $cat->id }}" x-model="selectedCategory" class="sr-only">
                                <div class="p-2.5 rounded-2xl border text-center transition-all flex flex-col items-center justify-center gap-1"
                                     :class="selectedCategory == '{{ $cat->id }}' ? 'border-rose-500 bg-rose-50 text-rose-700 font-bold shadow-xs' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                                    <span class="text-xs truncate max-w-full">{{ $cat->name }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <!-- Income Categories Chips -->
                    <div x-show="tab === 'income'" class="grid grid-cols-3 gap-2" style="display: none;">
                        @foreach($incomeCategories as $cat)
                            <label class="cursor-pointer">
                                <input type="radio" name="category_id" value="{{ $cat->id }}" x-model="selectedCategory" class="sr-only">
                                <div class="p-2.5 rounded-2xl border text-center transition-all flex flex-col items-center justify-center gap-1"
                                     :class="selectedCategory == '{{ $cat->id }}' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 font-bold shadow-xs' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                                    <span class="text-xs truncate max-w-full">{{ $cat->name }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('category_id')
                        <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Selector: Tanggal Transaksi -->
                <div>
                    <label for="transaction_date" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Tanggal</label>
                    <input type="date" 
                           id="transaction_date" 
                           name="transaction_date" 
                           value="{{ old('transaction_date', request('transaction_date', date('Y-m-d'))) }}" 
                           required 
                           class="w-full px-4 py-3 bg-white border @error('transaction_date') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-xs">
                    @error('transaction_date')
                        <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Input: Keterangan Singkat -->
                <div>
                    <label for="description" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Keterangan <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                    <input type="text" 
                           id="description" 
                           name="description" 
                           value="{{ old('description', request('description')) }}" 
                           placeholder="Contoh: Makan siang nasi padang" 
                           class="w-full px-4 py-3 bg-white border border-slate-200/90 rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-xs placeholder-slate-300">
                </div>

                <!-- Input: Catatan Tambahan (opsional) -->
                <div>
                    <label for="notes" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Catatan Tambahan <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                    <textarea id="notes" 
                              name="notes" 
                              rows="2" 
                              placeholder="Detail tambahan jika diperlukan..." 
                              class="w-full p-3.5 text-sm text-slate-800 bg-white border border-slate-200/90 rounded-2xl focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-xs placeholder-slate-300 resize-none">{{ old('notes') }}</textarea>
                </div>

                <!-- Button: Simpan Transaksi -->
                <div class="pt-2">
                    <button type="submit" 
                            :disabled="submitting" 
                            :class="submitting ? 'opacity-60 cursor-not-allowed' : 'hover:bg-sky-700 active:scale-95 shadow-sky-500/25'" 
                            class="w-full py-4 text-base font-bold bg-sky-600 text-white rounded-2xl shadow-lg transition-all flex items-center justify-center gap-2">
                        <span x-show="!submitting">Simpan Transaksi</span>
                        <span x-show="submitting" x-cloak class="flex items-center gap-2">
                            <svg class="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span>Menyimpan...</span>
                        </span>
                    </button>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
