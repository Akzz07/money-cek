<x-app-layout :activeNav="'budget'">
    <div class="px-5 pt-6 pb-8" x-data="{ submitting: false }">
        <!-- Top Navigation Bar -->
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('bills.index') }}" class="w-9 h-9 rounded-full bg-white border border-slate-200/80 shadow-xs flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" aria-label="Kembali">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-base font-bold text-slate-800">Tambah Tagihan Baru</h1>
            <div class="w-9"></div>
        </div>

        @if(session('error'))
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium rounded-2xl">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('bills.store') }}" method="POST" @submit="submitting = true" class="space-y-4">
            @csrf

            <!-- Input: Nama Tagihan -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Nama Tagihan / Kewajiban</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="{{ old('name') }}" 
                       required 
                       placeholder="Contoh: Listrik PLN / Wifi Indihome" 
                       class="w-full px-4 py-3 bg-white border @error('name') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs">
                @error('name')
                    <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Input: Nominal Tagihan dengan Format Otomatis & Terbilang -->
            <x-ui.money-input 
                name="amount" 
                id="amount"
                label="Nominal Tagihan" 
                :value="old('amount')" 
                placeholder="0" 
                :required="true"
                :showPresets="true" />


            <!-- Input: Tanggal Jatuh Tempo -->
            <div>
                <label for="due_date" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Tanggal Jatuh Tempo</label>
                <input type="date" 
                       id="due_date" 
                       name="due_date" 
                       value="{{ old('due_date', now()->addDays(5)->format('Y-m-d')) }}" 
                       required 
                       class="w-full px-4 py-3 bg-white border @error('due_date') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs">
                @error('due_date')
                    <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Selector: Kategori (Opsional) -->
            <div>
                <label for="category_id" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Kategori <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                <select id="category_id" 
                        name="category_id" 
                        class="w-full px-4 py-3 bg-white border border-slate-200/90 rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs">
                    <option value="">Tanpa Kategori Khusus</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Input: Catatan Tambahan -->
            <div>
                <label for="notes" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Catatan Tambahan <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                <textarea id="notes" 
                          name="notes" 
                          rows="2" 
                          placeholder="No. Pelanggan / ID Tagihan..." 
                          class="w-full p-3.5 text-sm text-slate-800 bg-white border border-slate-200/90 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs placeholder-slate-300 resize-none">{{ old('notes') }}</textarea>
            </div>

            <!-- Submit Button -->
            <div class="pt-3">
                <button type="submit" 
                        :disabled="submitting" 
                        :class="submitting ? 'opacity-60 cursor-not-allowed' : 'hover:bg-sky-700 active:scale-95 shadow-sky-500/25'" 
                        class="w-full py-4 text-base font-bold bg-sky-600 text-white rounded-2xl shadow-lg transition-all flex items-center justify-center gap-2">
                    <span x-show="!submitting">Simpan Tagihan</span>
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
