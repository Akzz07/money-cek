<x-app-layout :activeNav="'budget'">
    <div class="px-5 pt-6 pb-8" x-data="{ submitting: false }">
        <!-- Top Navigation Bar -->
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('budgets.index') }}" class="w-9 h-9 rounded-full bg-white border border-slate-200/80 shadow-xs flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" aria-label="Kembali">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-base font-bold text-slate-800">Ubah Anggaran</h1>
            <div class="w-9"></div>
        </div>

        @if(session('error'))
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium rounded-2xl">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('budgets.update', $budget) }}" method="POST" @submit="submitting = true" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- Info Kategori & Periode -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-1 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-400">Kategori:</span>
                    <span class="font-bold text-slate-800">{{ $budget->category->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Periode:</span>
                    <span class="font-semibold text-slate-800">{{ $budget->period_start->format('F Y') }}</span>
                </div>
            </div>

            <!-- Input: Batas Nominal (Limit Amount) dengan Format Otomatis & Terbilang -->
            <x-ui.money-input 
                name="limit_amount" 
                id="limit_amount"
                label="Batas Pengeluaran Maksimal" 
                :value="old('limit_amount', $budget->limit_amount->amount())" 
                placeholder="1.000.000" 
                :required="true"
                :showPresets="true" />


            <!-- Input: Ambang Peringatan (Warning Threshold) -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="warning_threshold" class="text-xs font-bold text-slate-600 uppercase tracking-wider">Ambang Peringatan</label>
                    <span class="text-xs font-bold text-amber-600" id="threshold_label">{{ old('warning_threshold', $budget->warning_threshold) }}%</span>
                </div>
                <input type="number" 
                       id="warning_threshold" 
                       name="warning_threshold" 
                       value="{{ old('warning_threshold', $budget->warning_threshold) }}" 
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
                    <span x-show="!submitting">Perbarui Anggaran</span>
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
    </div>
</x-app-layout>
