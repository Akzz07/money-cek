<x-app-layout :activeNav="'budget'">
    <div class="px-5 pt-6 pb-8" x-data="{ submitting: false }">
        <!-- Top Navigation Bar -->
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('budgets.index') }}" class="w-9 h-9 rounded-full bg-white border border-slate-200/80 shadow-xs flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" aria-label="Kembali">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-base font-bold text-slate-800">Buat Anggaran Bulanan</h1>
            <div class="w-9"></div>
        </div>

        @if(session('error'))
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium rounded-2xl">
                {{ session('error') }}
            </div>
        @endif

        @if($categories->isEmpty())
            <x-ui.empty-state 
                title="Belum Ada Kategori Pengeluaran"
                description="Anda membutuhkan setidaknya satu kategori pengeluaran aktif sebelum membuat anggaran."
                actionText="Kelola Kategori"
                :actionUrl="route('categories.index')"
            />
        @else
            <form action="{{ route('budgets.store') }}" method="POST" @submit="submitting = true" class="space-y-4">
                @csrf

                <!-- Selector: Kategori Pengeluaran -->
                <div>
                    <label for="category_id" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Kategori Pengeluaran</label>
                    <select id="category_id" 
                            name="category_id" 
                            required
                            class="w-full px-4 py-3 bg-white border @error('category_id') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs">
                        <option value="" disabled {{ old('category_id') ? '' : 'selected' }}>Pilih Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Input: Periode Bulan -->
                <div>
                    <label for="period" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Bulan Anggaran</label>
                    <input type="month" 
                           id="period" 
                           name="period" 
                           value="{{ old('period', request('period', now()->format('Y-m'))) }}" 
                           required 
                           class="w-full px-4 py-3 bg-white border @error('period') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs">
                    @error('period')
                        <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Input: Batas Nominal (Limit Amount) dengan Format Otomatis & Terbilang -->
                <x-ui.money-input 
                    name="limit_amount" 
                    id="limit_amount"
                    label="Batas Pengeluaran Maksimal" 
                    :value="old('limit_amount')" 
                    placeholder="1.000.000" 
                    :required="true"
                    :showPresets="true" />


                <!-- Input: Ambang Peringatan (Warning Threshold) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="warning_threshold" class="text-xs font-bold text-slate-600 uppercase tracking-wider">Ambang Peringatan</label>
                        <span class="text-xs font-bold text-amber-600" id="threshold_label">80%</span>
                    </div>
                    <input type="number" 
                           id="warning_threshold" 
                           name="warning_threshold" 
                           value="{{ old('warning_threshold', 80) }}" 
                           min="1" 
                           max="100" 
                           required 
                           oninput="document.getElementById('threshold_label').innerText = this.value + '%'"
                           class="w-full px-4 py-3 bg-white border @error('warning_threshold') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs">
                    <p class="text-[11px] text-slate-400 mt-1">Sistem akan menampilkan status waspada ketika pengeluaran mencapai persentase ini.</p>
                    @error('warning_threshold')
                        <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="pt-3">
                    <button type="submit" 
                            :disabled="submitting" 
                            :class="submitting ? 'opacity-60 cursor-not-allowed' : 'hover:bg-blue-700 active:scale-95 shadow-blue-500/25'" 
                            class="w-full py-4 text-base font-bold bg-blue-600 text-white rounded-2xl shadow-lg transition-all flex items-center justify-center gap-2">
                        <span x-show="!submitting">Simpan Anggaran</span>
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
