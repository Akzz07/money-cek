<x-app-layout :activeNav="'budget'">
    <div class="px-5 pt-6 pb-8" x-data="{ submitting: false, selectedColor: '#2563EB' }">
        <!-- Top Navigation Bar -->
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('savings-goals.index') }}" class="w-9 h-9 rounded-full bg-white border border-slate-200/80 shadow-xs flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" aria-label="Kembali">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-base font-bold text-slate-800">Buat Target Tabungan Baru</h1>
            <div class="w-9"></div>
        </div>

        @if(session('error'))
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium rounded-2xl">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('savings-goals.store') }}" method="POST" @submit="submitting = true" class="space-y-4">
            @csrf

            <!-- Input: Nama Target -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Nama Target Tabungan</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="{{ old('name') }}" 
                       required 
                       placeholder="Contoh: Dana Darurat 6 Bulan / Liburan ke Jepang" 
                       class="w-full px-4 py-3 bg-white border @error('name') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs">
                @error('name')
                    <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Input: Nominal Target dengan Format Otomatis & Terbilang -->
            <x-ui.money-input 
                name="target_amount" 
                id="target_amount"
                label="Nominal Target Akhir" 
                :value="old('target_amount')" 
                placeholder="0" 
                :required="true"
                :showPresets="true" />


            <!-- Input: Tanggal Target (Opsional) -->
            <div>
                <label for="target_date" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Tenggat Waktu / Target Selesai (opsional)</label>
                <input type="date" 
                       id="target_date" 
                       name="target_date" 
                       value="{{ old('target_date') }}"
                       class="w-full px-4 py-3 bg-white border @error('target_date') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs">
                @error('target_date')
                    <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Input: Rekening Khusus Simpanan (Opsional) -->
            <div>
                <label for="target_account_id" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Rekening Simpanan Tertaut (opsional)</label>
                <div class="relative">
                    <select id="target_account_id" 
                            name="target_account_id" 
                            class="w-full px-4 py-3 bg-white border @error('target_account_id') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs appearance-none">
                        <option value="">-- Virtual / Dompet Mandiri --</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('target_account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->name }} ({{ $account->type->label() }})
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Jika dipilih, setoran dari rekening lain akan mencatat mutasi transfer dana riil.</p>
            </div>

            <!-- Input: Pilihan Warna Aksen -->
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Warna Aksen</label>
                <div class="flex items-center gap-3">
                    @php
                        $colors = ['#2563EB', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4'];
                    @endphp
                    @foreach($colors as $color)
                        <label class="cursor-pointer">
                            <input type="radio" name="color" value="{{ $color }}" class="sr-only" x-model="selectedColor" {{ $loop->first ? 'checked' : '' }}>
                            <div class="w-8 h-8 rounded-full flex items-center justify-center transition-transform hover:scale-110 shadow-xs"
                                 :class="selectedColor === '{{ $color }}' ? 'ring-3 ring-offset-2 ring-blue-500 scale-105' : ''"
                                 style="background-color: {{ $color }};">
                                <svg x-show="selectedColor === '{{ $color }}'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Input: Catatan Rencana -->
            <div>
                <label for="notes" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Catatan & Strategi (opsional)</label>
                <textarea id="notes" 
                          name="notes" 
                          rows="2" 
                          placeholder="Contoh: Sisihkan Rp 1.500.000 setiap tanggal gajian" 
                          class="w-full px-4 py-3 bg-white border @error('notes') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs">{{ old('notes') }}</textarea>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" 
                        :disabled="submitting"
                        class="w-full py-4 px-4 bg-gradient-to-r from-sky-600 via-sky-500 to-blue-600 text-white font-bold text-sm rounded-2xl shadow-lg shadow-sky-500/30 hover:shadow-xl hover:from-sky-700 hover:to-blue-700 active:scale-[0.99] transition-all flex items-center justify-center gap-2 disabled:opacity-70">
                    <span x-show="!submitting">Simpan Target Tabungan</span>
                    <span x-show="submitting" x-cloak>Menyimpan Target...</span>
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
