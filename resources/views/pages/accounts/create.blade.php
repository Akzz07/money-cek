<x-app-layout :hideNav="true">
    <div class="px-5 pt-6 pb-8" x-data="{ 
        type: '{{ old('type', 'bank') }}',
        includeInSafeToSpend: {{ old('include_in_safe_to_spend', 'true') === 'true' || old('include_in_safe_to_spend') === true ? 'true' : 'false' }},
        updateSafeToSpend() {
            if (this.type === 'investment') {
                this.includeInSafeToSpend = false;
            } else {
                this.includeInSafeToSpend = true;
            }
        }
    }">
        <!-- Top Navigation -->
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('accounts.index') }}" class="w-10 h-10 rounded-full bg-white border border-slate-200/80 shadow-xs flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" aria-label="Kembali">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-base font-bold text-slate-800">Tambah Akun Keuangan</h1>
            <div class="w-10"></div>
        </div>

        <form action="{{ route('accounts.store') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Nama Akun -->
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-600 mb-1.5">Nama Akun</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="{{ old('name') }}" 
                       required 
                       placeholder="Contoh: BCA Tabungan, GoPay, Dompet Tunai" 
                       class="w-full px-4 py-3 text-sm text-slate-800 bg-white border border-slate-200/90 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs placeholder-slate-300">
                <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
            </div>

            <!-- Tipe Akun -->
            <div>
                <label for="type" class="block text-xs font-semibold text-slate-600 mb-1.5">Jenis Akun</label>
                <div class="relative">
                    <select id="type" 
                            name="type" 
                            x-model="type" 
                            @change="updateSafeToSpend()"
                            required 
                            class="w-full px-4 py-3 text-sm text-slate-800 bg-white border border-slate-200/90 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs appearance-none cursor-pointer">
                        @foreach(\App\Enums\AccountType::cases() as $accType)
                            <option value="{{ $accType->value }}" {{ old('type', 'bank') === $accType->value ? 'selected' : '' }}>
                                {{ $accType->label() }}
                            </option>
                        @endforeach
                    </select>
                    <svg class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </div>
                <x-input-error :messages="$errors->get('type')" class="mt-1.5" />
            </div>

            <!-- Lembaga / Institusi & Nomor Rekening -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="institution_name" class="block text-xs font-semibold text-slate-600 mb-1.5">Nama Lembaga</label>
                    <input type="text" 
                           id="institution_name" 
                           name="institution_name" 
                           value="{{ old('institution_name') }}" 
                           placeholder="BCA, Mandiri, GoTo" 
                           class="w-full px-3.5 py-3 text-xs sm:text-sm text-slate-800 bg-white border border-slate-200/90 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs placeholder-slate-300">
                    <x-input-error :messages="$errors->get('institution_name')" class="mt-1.5" />
                </div>
                <div>
                    <label for="account_number" class="block text-xs font-semibold text-slate-600 mb-1.5">No. Rekening/ID</label>
                    <input type="text" 
                           id="account_number" 
                           name="account_number" 
                           value="{{ old('account_number') }}" 
                           placeholder="1234567890" 
                           class="w-full px-3.5 py-3 text-xs sm:text-sm text-slate-800 bg-white border border-slate-200/90 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs placeholder-slate-300">
                    <x-input-error :messages="$errors->get('account_number')" class="mt-1.5" />
                </div>
            </div>

            <!-- Saldo Awal -->
            <div>
                <label for="opening_balance" class="block text-xs font-semibold text-slate-600 mb-1.5">Saldo Awal</label>
                <div class="relative rounded-2xl border border-slate-200/90 bg-white focus-within:ring-2 focus-within:ring-blue-500 shadow-xs">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">Rp</span>
                    <input type="text" 
                           id="opening_balance" 
                           name="opening_balance" 
                           value="{{ old('opening_balance', '0') }}" 
                           required 
                           placeholder="0" 
                           class="w-full pl-11 pr-4 py-3 text-base font-bold text-slate-800 bg-transparent focus:outline-none placeholder-slate-300">
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Saldo dasar saat pertama kali akun ini dicatat di Monetra.</p>
                <x-input-error :messages="$errors->get('opening_balance')" class="mt-1.5" />
            </div>

            <!-- Tanggal Saldo Awal -->
            <div>
                <label for="opening_date" class="block text-xs font-semibold text-slate-600 mb-1.5">Tanggal Saldo Awal</label>
                <input type="date" 
                       id="opening_date" 
                       name="opening_date" 
                       value="{{ old('opening_date', date('Y-m-d')) }}" 
                       max="{{ date('Y-m-d') }}"
                       required 
                       class="w-full px-4 py-3 text-sm text-slate-800 bg-white border border-slate-200/90 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs cursor-pointer">
                <x-input-error :messages="$errors->get('opening_date')" class="mt-1.5" />
            </div>

            <!-- Toggles Pengaturan Inklusi -->
            <div class="space-y-3 pt-2 border-t border-slate-100">
                <!-- Include in Safe-to-Spend -->
                <label class="flex items-start gap-3 p-3 bg-white rounded-2xl border border-slate-200/80 shadow-xs cursor-pointer">
                    <input type="checkbox" 
                           name="include_in_safe_to_spend" 
                           value="1" 
                           x-model="includeInSafeToSpend" 
                           class="mt-0.5 rounded-md border-slate-300 text-blue-600 focus:ring-blue-500">
                    <div class="text-xs">
                        <span class="font-bold text-slate-800 block">Hitung dalam Safe-to-Spend</span>
                        <span class="text-slate-400 leading-relaxed block mt-0.5">Saldo di akun ini dianggap siap dibelanjakan untuk kebutuhan operasional harian.</span>
                    </div>
                </label>

                <!-- Include in Total Assets -->
                <label class="flex items-start gap-3 p-3 bg-white rounded-2xl border border-slate-200/80 shadow-xs cursor-pointer">
                    <input type="checkbox" 
                           name="include_in_total_assets" 
                           value="1" 
                           {{ old('include_in_total_assets', '1') ? 'checked' : '' }} 
                           class="mt-0.5 rounded-md border-slate-300 text-blue-600 focus:ring-blue-500">
                    <div class="text-xs">
                        <span class="font-bold text-slate-800 block">Sertakan dalam Total Kekayaan Bersih</span>
                        <span class="text-slate-400 leading-relaxed block mt-0.5">Menambah angka total aset pada ringkasan dashboard dan halaman aset.</span>
                    </div>
                </label>
            </div>

            <!-- Catatan -->
            <div>
                <label for="notes" class="block text-xs font-semibold text-slate-600 mb-1.5">Catatan <span class="text-slate-400 font-normal">(opsional)</span></label>
                <textarea id="notes" 
                          name="notes" 
                          rows="2" 
                          placeholder="Keterangan tambahan akun ini..." 
                          class="w-full p-3 text-xs sm:text-sm text-slate-800 bg-white border border-slate-200/90 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs resize-none placeholder-slate-300">{{ old('notes') }}</textarea>
            </div>

            <!-- Submit Button -->
            <div class="pt-3">
                <x-ui.button type="submit" variant="primary" size="lg" class="w-full">
                    Simpan Akun
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
