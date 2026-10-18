@props([
    'id' => 'confirm-modal',
    'title' => 'Konfirmasi Aksi',
    'message' => 'Apakah Anda yakin ingin melanjutkan aksi ini? Tindakan ini akan dicatat dalam riwayat audit.',
    'confirmText' => 'Ya, Lanjutkan',
    'cancelText' => 'Batal',
    'action' => '#',
    'method' => 'POST',
    'variant' => 'danger',
])

<div x-data="{ open: false }" 
     x-show="open" 
     x-on:open-modal.window="if ($event.detail.id === '{{ $id }}') open = true"
     x-on:keydown.escape.window="open = false"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto" 
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true">
    
    <!-- Backdrop overlay -->
    <div x-show="open" 
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
         @click="open = false"></div>

    <div class="flex min-h-full items-end sm:items-center justify-center p-4 text-center sm:p-0">
        <div x-show="open" 
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative transform overflow-hidden rounded-3xl bg-white px-5 pb-6 pt-5 text-left shadow-2xl transition-all w-full max-w-sm sm:my-8 border border-slate-100">
            
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl {{ $variant === 'danger' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600' }} sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                    <h3 class="text-base font-bold leading-6 text-slate-800" id="modal-title">{{ $title }}</h3>
                    <div class="mt-2">
                        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">{{ $message }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-5 sm:mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                <x-ui.button @click="open = false" variant="secondary" size="md">
                    {{ $cancelText }}
                </x-ui.button>

                <form :action="action" method="POST" class="inline">
                    @csrf
                    @if(strtoupper($method) !== 'POST')
                        @method($method)
                    @endif
                    <x-ui.button type="submit" :variant="$variant" size="md" class="w-full sm:w-auto">
                        {{ $confirmText }}
                    </x-ui.button>
                </form>
            </div>
        </div>
    </div>
</div>
