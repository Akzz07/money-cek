<x-app-layout :activeNav="'budget'">
    <div class="px-5 pt-6 pb-8" x-data="{ submitting: false, selectedColor: '{{ $savingsGoal->color ?? '#2563EB' }}' }">
        <!-- Top Navigation Bar -->
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('savings-goals.show', $savingsGoal) }}" class="w-9 h-9 rounded-full bg-white border border-slate-200/80 shadow-xs flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" aria-label="Kembali">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-base font-bold text-slate-800">Ubah Target Tabungan</h1>
            <div class="w-9"></div>
        </div>

        @if(session('error'))
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium rounded-2xl">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('savings-goals.update', $savingsGoal) }}" method="POST" @submit="submitting = true" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- Input: Nama Target -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Nama Target Tabungan</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="{{ old('name', $savingsGoal->name) }}" 
                       required 
                       class="w-full px-4 py-3 bg-white border @error('name') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs">
                @error('name')
                    <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Input: Nominal Target -->
            <div>
                <label for="target_amount" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Nominal Target Akhir</label>
                <div class="relative rounded-2xl border @error('target_amount') border-rose-500 @else border-slate-200/90 @enderror bg-white focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all shadow-xs">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">Rp</span>
                    <input type="number" 
                           step="0.01"
                           min="0.01"
                           id="target_amount"
                           name="target_amount" 
                           value="{{ old('target_amount', $savingsGoal->target_amount->toDecimalString()) }}"
                           required
                           class="w-full pl-12 pr-4 py-3.5 text-xl font-black text-slate-800 bg-transparent focus:outline-none">
                </div>
                @error('target_amount')
                    <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Input: Status Target -->
            <div>
                <label for="status" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Status Target</label>
                <select id="status" 
                        name="status" 
                        class="w-full px-4 py-3 bg-white border @error('status') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs">
                    @foreach(\App\Enums\SavingsGoalStatus::cases() as $statusCase)
                        <option value="{{ $statusCase->value }}" {{ old('status', $savingsGoal->status->value) === $statusCase->value ? 'selected' : '' }}>
                            {{ $statusCase->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Input: Tanggal Target -->
            <div>
                <label for="target_date" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Tenggat Waktu / Target Selesai (opsional)</label>
                <input type="date" 
                       id="target_date" 
                       name="target_date" 
                       value="{{ old('target_date', $savingsGoal->target_date?->toDateString()) }}"
                       class="w-full px-4 py-3 bg-white border @error('target_date') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs">
                @error('target_date')
                    <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Input: Rekening Tertaut -->
            <div>
                <label for="target_account_id" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Rekening Simpanan Tertaut (opsional)</label>
                <select id="target_account_id" 
                        name="target_account_id" 
                        class="w-full px-4 py-3 bg-white border @error('target_account_id') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs">
                    <option value="">-- Virtual / Dompet Mandiri --</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ old('target_account_id', $savingsGoal->target_account_id) == $account->id ? 'selected' : '' }}>
                            {{ $account->name }} ({{ $account->type->label() }})
                        </option>
                    @endforeach
                </select>
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
                            <input type="radio" name="color" value="{{ $color }}" class="sr-only" x-model="selectedColor">
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
                <label for="notes" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Catatan (opsional)</label>
                <textarea id="notes" 
                          name="notes" 
                          rows="2" 
                          class="w-full px-4 py-3 bg-white border @error('notes') border-rose-500 @else border-slate-200/90 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-xs">{{ old('notes', $savingsGoal->notes) }}</textarea>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" 
                        :disabled="submitting"
                        class="w-full py-4 px-4 bg-gradient-to-r from-blue-700 to-blue-600 text-white font-bold text-sm rounded-2xl shadow-lg shadow-blue-500/30 hover:shadow-xl hover:from-blue-800 hover:to-blue-700 active:scale-[0.99] transition-all flex items-center justify-center gap-2 disabled:opacity-70">
                    <span x-show="!submitting">Perbarui Target Tabungan</span>
                    <span x-show="submitting" x-cloak>Memperbarui Target...</span>
                </button>
            </div>
        </form>

        <!-- Delete Form -->
        <div class="pt-4 border-t border-slate-200">
            <form action="{{ route('savings-goals.destroy', $savingsGoal) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus target tabungan ini beserta seluruh riwayat mutasinya?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full py-3 px-4 bg-rose-50 text-rose-600 font-bold text-xs rounded-2xl border border-rose-200 hover:bg-rose-100 transition-colors">
                    Hapus Target Tabungan
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
