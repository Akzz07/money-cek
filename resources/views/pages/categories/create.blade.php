<x-app-layout :activeNav="'home'">
    <div class="px-5 pt-6 pb-6">
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('categories.index') }}" class="w-9 h-9 rounded-full bg-white border border-slate-200/80 shadow-xs flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" aria-label="Kembali">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-xl font-bold text-slate-800">Tambah Kategori Baru</h1>
        </div>

        <form action="{{ route('categories.store') }}" method="POST" class="space-y-5">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Nama Kategori</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name') }}" 
                    required 
                    placeholder="misal: Kopi, Langganan Streaming, Gym" 
                    class="w-full px-4 py-3 bg-white border @error('name') border-rose-500 @else border-slate-200 @enderror rounded-2xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all shadow-xs"
                >
                @error('name')
                    <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Type -->
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Jenis Kategori</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="expense" class="peer sr-only" {{ old('type', 'expense') === 'expense' ? 'checked' : '' }}>
                        <div class="p-3 bg-white border border-slate-200 rounded-2xl text-center peer-checked:border-rose-500 peer-checked:bg-rose-50/50 peer-checked:text-rose-700 transition-all">
                            <span class="block text-sm font-bold">Pengeluaran</span>
                            <span class="block text-xs text-slate-400 mt-0.5">Biaya belanja / rutin</span>
                        </div>
                    </label>

                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="income" class="peer sr-only" {{ old('type') === 'income' ? 'checked' : '' }}>
                        <div class="p-3 bg-white border border-slate-200 rounded-2xl text-center peer-checked:border-emerald-500 peer-checked:bg-emerald-50/50 peer-checked:text-emerald-700 transition-all">
                            <span class="block text-sm font-bold">Pemasukan</span>
                            <span class="block text-xs text-slate-400 mt-0.5">Penghasilan / komisi</span>
                        </div>
                    </label>
                </div>
                @error('type')
                    <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Color Palette Selection -->
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Pilihan Warna</label>
                <div class="grid grid-cols-5 gap-3" x-data="{ selectedColor: '{{ old('color', 'blue') }}' }">
                    @foreach(['blue', 'emerald', 'rose', 'amber', 'purple', 'indigo', 'cyan', 'orange', 'slate', 'zinc'] as $color)
                        <label class="cursor-pointer flex flex-col items-center">
                            <input type="radio" name="color" value="{{ $color }}" x-model="selectedColor" class="sr-only">
                            <div 
                                class="w-10 h-10 rounded-2xl flex items-center justify-center transition-all border-2"
                                :class="selectedColor === '{{ $color }}' ? 'border-slate-800 scale-110 shadow-xs' : 'border-transparent opacity-80'"
                                style="background-color: {{ match($color) {
                                    'blue' => '#3b82f6',
                                    'emerald' => '#10b981',
                                    'rose' => '#f43f5e',
                                    'amber' => '#f59e0b',
                                    'purple' => '#a855f7',
                                    'indigo' => '#6366f1',
                                    'cyan' => '#06b6d4',
                                    'orange' => '#f97316',
                                    'slate' => '#64748b',
                                    'zinc' => '#71717a',
                                    default => '#3b82f6'
                                } }};"
                            >
                                <svg x-show="selectedColor === '{{ $color }}'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-[10px] text-slate-500 capitalize mt-1">{{ $color }}</span>
                        </label>
                    @endforeach
                </div>
                @error('color')
                    <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Icon Selection -->
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Pilihan Ikon</label>
                <div class="grid grid-cols-4 gap-2.5" x-data="{ selectedIcon: '{{ old('icon', 'tag') }}' }">
                    @foreach(['tag', 'shopping-bag', 'cup-soda', 'heart', 'truck', 'home', 'ticket', 'academic-cap', 'briefcase', 'gift', 'building-office', 'sparkles'] as $icon)
                        <label class="cursor-pointer">
                            <input type="radio" name="icon" value="{{ $icon }}" x-model="selectedIcon" class="sr-only">
                            <div 
                                class="py-2.5 px-2 bg-white border rounded-2xl text-center transition-all flex flex-col items-center gap-1"
                                :class="selectedIcon === '{{ $icon }}' ? 'border-emerald-500 bg-emerald-50/40 text-emerald-700 font-bold' : 'border-slate-200 text-slate-600'"
                            >
                                <span class="text-xs capitalize">{{ str_replace('-', ' ', $icon) }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('icon')
                    <p class="mt-1.5 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <x-ui.button type="submit" class="w-full py-3.5 shadow-md">
                    Simpan Kategori
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
