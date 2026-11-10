<div x-data="{
    deferredPrompt: null,
    showPrompt: false,
    isIOS: false,
    isStandalone: false,
    init() {
        this.isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        this.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

        if (this.isStandalone) {
            return;
        }

        // Listen for Chrome / Android beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            // Delay slightly so user isn't immediately overwhelmed
            setTimeout(() => {
                const dismissed = localStorage.getItem('monetra_pwa_dismissed');
                if (!dismissed) {
                    this.showPrompt = true;
                }
            }, 1500);
        });

        // Show iOS hint if not dismissed and on iOS
        if (this.isIOS && !localStorage.getItem('monetra_pwa_dismissed')) {
            setTimeout(() => {
                this.showPrompt = true;
            }, 2500);
        }
    },
    async install() {
        if (this.deferredPrompt) {
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                this.showPrompt = false;
            }
            this.deferredPrompt = null;
        }
    },
    dismiss() {
        this.showPrompt = false;
        // Don't show again for 3 days
        localStorage.setItem('monetra_pwa_dismissed', 'true');
    }
}" 
x-show="showPrompt" 
x-transition:enter="transition ease-out duration-300"
x-transition:enter-start="opacity-0 translate-y-8"
x-transition:enter-end="opacity-100 translate-y-0"
x-transition:leave="transition ease-in duration-200"
x-transition:leave-start="opacity-100 translate-y-0"
x-transition:leave-end="opacity-0 translate-y-8"
class="fixed bottom-20 left-4 right-4 max-w-md mx-auto z-40"
style="display: none;">
    <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border border-sky-200 dark:border-sky-900/60 shadow-xl rounded-2xl p-4 flex flex-col gap-3">
        <div class="flex items-start gap-3">
            <img src="{{ asset('icons/icon-192x192.png') }}" alt="Monetra App" class="w-12 h-12 rounded-xl shadow-md flex-shrink-0">
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Pasang Aplikasi Monetra</h4>
                    <button @click="dismiss()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-1 -mr-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5" x-show="!isIOS">
                    Pasang ke layar HP kamu untuk akses super cepat tanpa bar browser!
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5" x-show="isIOS">
                    Buka menu <span class="font-semibold text-sky-600 dark:text-sky-400">Share (Bagikan) 📤</span> di Safari, lalu pilih <span class="font-semibold text-sky-600 dark:text-sky-400">'Tambah ke Layar Utama' ➕</span>.
                </p>
            </div>
        </div>

        <template x-if="!isIOS && deferredPrompt">
            <div class="flex items-center gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                <button @click="dismiss()" class="flex-1 py-2 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition">
                    Nanti Saja
                </button>
                <button @click="install()" class="flex-1 py-2 text-xs font-semibold text-white bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 rounded-xl shadow-sm transition active:scale-95 flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Pasang Sekarang
                </button>
            </div>
        </template>
    </div>
</div>
