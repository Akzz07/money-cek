<x-app-layout :activeNav="'home'">
    <div class="px-5 pt-6 pb-6" x-data="{ tab: 'expense' }">
        <!-- Header -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="w-9 h-9 rounded-full bg-white border border-slate-200/80 shadow-xs flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" aria-label="Kembali">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 class="text-xl font-bold text-slate-800">Kategori</h1>
            </div>
            
            <x-ui.button :href="route('categories.create')" size="sm" class="flex items-center gap-1 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah</span>
            </x-ui.button>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium rounded-xl flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Segmented Tab: Pengeluaran vs Pemasukan -->
        <div class="flex p-1 bg-slate-200/70 rounded-2xl mb-4">
            <button 
                type="button"
                @click="tab = 'expense'"
                :class="tab === 'expense' ? 'bg-white text-slate-800 shadow-xs font-bold' : 'text-slate-500 font-medium hover:text-slate-700'"
                class="flex-1 py-2 text-xs rounded-xl transition-all flex items-center justify-center gap-1.5"
            >
                <span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span>
                <span>Pengeluaran ({{ $expenseCategories->count() }})</span>
            </button>
            <button 
                type="button"
                @click="tab = 'income'"
                :class="tab === 'income' ? 'bg-white text-slate-800 shadow-xs font-bold' : 'text-slate-500 font-medium hover:text-slate-700'"
                class="flex-1 py-2 text-xs rounded-xl transition-all flex items-center justify-center gap-1.5"
            >
                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                <span>Pemasukan ({{ $incomeCategories->count() }})</span>
            </button>
        </div>

        <!-- Expense Tab Content -->
        <div x-show="tab === 'expense'" class="space-y-2.5">
            @forelse($expenseCategories as $category)
                <div class="bg-white rounded-2xl p-3.5 border border-slate-100 shadow-xs flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold bg-slate-100 text-slate-700">
                            {{ mb_substr($category->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-slate-800">{{ $category->name }}</span>
                                @if($category->is_system)
                                    <span class="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-medium">Bawaan</span>
                                @endif
                                @if($category->status->value === 'archived')
                                    <span class="text-[10px] bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded font-medium">Diarsipkan</span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-400 capitalize">{{ $category->type->label() }}</span>
                        </div>
                    </div>

                    @if(!$category->is_system)
                        <div class="flex items-center gap-1">
                            <a href="{{ route('categories.edit', $category) }}" class="p-1.5 text-slate-400 hover:text-slate-600 transition-colors" title="Edit Kategori">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </a>
                            <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengarsipkan kategori ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 transition-colors" title="Arsipkan Kategori">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-8 text-slate-400 text-sm">Tidak ada kategori pengeluaran</div>
            @endforelse
        </div>

        <!-- Income Tab Content -->
        <div x-show="tab === 'income'" class="space-y-2.5" style="display: none;">
            @forelse($incomeCategories as $category)
                <div class="bg-white rounded-2xl p-3.5 border border-slate-100 shadow-xs flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold bg-slate-100 text-slate-700">
                            {{ mb_substr($category->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-slate-800">{{ $category->name }}</span>
                                @if($category->is_system)
                                    <span class="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-medium">Bawaan</span>
                                @endif
                                @if($category->status->value === 'archived')
                                    <span class="text-[10px] bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded font-medium">Diarsipkan</span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-400 capitalize">{{ $category->type->label() }}</span>
                        </div>
                    </div>

                    @if(!$category->is_system)
                        <div class="flex items-center gap-1">
                            <a href="{{ route('categories.edit', $category) }}" class="p-1.5 text-slate-400 hover:text-slate-600 transition-colors" title="Edit Kategori">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </a>
                            <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengarsipkan kategori ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 transition-colors" title="Arsipkan Kategori">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-8 text-slate-400 text-sm">Tidak ada kategori pemasukan</div>
            @endforelse
        </div>
    </div>
</x-app-layout>
