<x-app-layout :activeNav="'asset'">
    <div class="px-5 pt-6 pb-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <a href="{{ route('assets.index') }}" class="w-9 h-9 rounded-full bg-white border border-slate-200/80 shadow-xs flex items-center justify-center text-slate-600 hover:bg-slate-50 transition-colors" aria-label="Kembali">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 class="text-xl font-bold text-slate-800">Akun Keuangan</h1>
            </div>
            
            <x-ui.button :href="route('accounts.create')" size="sm" class="flex items-center gap-1 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah</span>
            </x-ui.button>
        </div>

        @if($accounts->isEmpty())
            <x-ui.empty-state 
                title="Belum Ada Akun" 
                description="Tambahkan akun bank, dompet digital, atau uang tunai pertamamu untuk mencatat keuangan."
                actionText="Tambah Akun Sekarang"
                :actionUrl="route('accounts.create')"
            />
        @else
            <div class="space-y-3">
                @foreach($accounts as $account)
                    <x-ui.card padding="p-4" class="{{ $account->status->value === 'archived' ? 'opacity-60 bg-slate-50' : '' }}">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <!-- Type Icon Badge -->
                                <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 {{ match($account->type->value) {
                                    'bank' => 'bg-blue-50 text-blue-600',
                                    'e_wallet' => 'bg-emerald-50 text-emerald-600',
                                    'cash' => 'bg-amber-50 text-amber-600',
                                    'investment' => 'bg-purple-50 text-purple-600',
                                    default => 'bg-slate-100 text-slate-600',
                                } }}">
                                    @if($account->type->value === 'bank')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    @elseif($account->type->value === 'e_wallet')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    @elseif($account->type->value === 'cash')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                    @endif
                                </div>

                                <div>
                                    <h2 class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                        {{ $account->name }}
                                        @if($account->status->value === 'archived')
                                            <x-ui.badge variant="neutral" size="sm">Diarsipkan</x-ui.badge>
                                        @endif
                                    </h2>
                                    <p class="text-xs text-slate-400 font-medium">
                                        {{ $account->type->label() }} 
                                        @if($account->institution_name) • {{ $account->institution_name }} @endif
                                        @if($account->account_number) • {{ $account->account_number }} @endif
                                    </p>
                                </div>
                            </div>

                            <!-- Actions Menu -->
                            <div class="flex items-center gap-1">
                                <a href="{{ route('accounts.edit', $account) }}" class="p-1.5 text-slate-400 hover:text-blue-600 transition-colors" aria-label="Edit Akun">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                            </div>
                        </div>

                        <!-- Balances & Flags Info -->
                        <div class="mt-3.5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-slate-400 font-medium">Saldo Terkini:</span>
                                <span class="font-extrabold text-slate-800 ml-1 text-sm">@money($balances[$account->id] ?? $account->opening_balance)</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                @if($account->include_in_safe_to_spend)
                                    <x-ui.badge variant="info" size="sm">Safe-to-Spend</x-ui.badge>
                                @endif
                                @if($account->include_in_total_assets)
                                    <x-ui.badge variant="success" size="sm">Aset</x-ui.badge>
                                @endif
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
