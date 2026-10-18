@props([
    'title' => 'Belum Ada Data',
    'description' => 'Mulai catat transaksi pertamamu untuk melihat laporan keuangan yang rapi.',
    'actionText' => null,
    'actionUrl' => null,
])

<div class="flex flex-col items-center justify-center text-center p-8 bg-white rounded-3xl border border-slate-100 shadow-sm my-4">
    <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-4">
        {{ $icon ?? '' }}
        @unless(isset($icon))
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
        @endunless
    </div>
    <h3 class="text-base font-bold text-slate-800 mb-1">{{ $title }}</h3>
    <p class="text-xs sm:text-sm text-slate-500 max-w-xs mb-5">{{ $description }}</p>
    
    @if ($actionText && $actionUrl)
        <x-ui.button :href="$actionUrl" size="sm">
            {{ $actionText }}
        </x-ui.button>
    @endif
</div>
