@props([
    'balance' => 0,
])

@php
    $amount = 0;
    if ($balance instanceof \App\Domain\Money\Money) {
        $amount = (float) $balance->toDecimalString();
    } elseif (is_numeric($balance)) {
        $amount = (float) $balance;
    }

    if ($amount < 100000) {
        $state = 'sick';
        $image = 'images/01_mode_survival.png';
        $label = 'Mode Survival';
        $tagline = 'Status: Mode Survival (< Rp 100rb)';
        $dialogTitle = 'Aduh... Mode Survival Aktif! 🩹';
        $dialogMessage = 'Saldomu di bawah Rp 100.000! Kucingmu lemas kedinginan. Prioritaskan kebutuhan pokok paling mendesak dan tahan semua jajan!';
        $dialogTip = 'Gunakan sisa saldo hanya untuk makan & kebutuhan darurat primer.';
        $glowColor = 'from-rose-500/20 to-rose-500/5';
        $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800';
        $dotClass = 'bg-rose-500';
        $dotPingClass = 'bg-rose-400';
        $animClass = 'animate-cat-shiver';
    } elseif ($amount <= 500000) {
        $state = 'sick';
        $image = 'images/02_pejuang.png';
        $label = 'Pejuang';
        $tagline = 'Status: Pejuang (Rp 100rb - 500rb)';
        $dialogTitle = 'Tetap Semangat, Sang Pejuang! 💪';
        $dialogMessage = 'Saldomu di kisaran Rp 100.000 s/d Rp 500.000. Kucing pejuangmu sedang gigih bertahan. Rem dulu pengeluaran tidak penting ya!';
        $dialogTip = 'Prioritaskan pelunasan tagihan terdekat dan batasi pengeluaran harian.';
        $glowColor = 'from-orange-500/20 to-orange-500/5';
        $badgeClass = 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/60 dark:text-orange-300 dark:border-orange-800';
        $dotClass = 'bg-orange-500';
        $dotPingClass = 'bg-orange-400';
        $animClass = 'animate-cat-shiver';
    } elseif ($amount <= 1000000) {
        $state = 'hungry';
        $image = 'images/03_petualang.png';
        $label = 'Petualang';
        $tagline = 'Status: Petualang (Rp 500rb - 1jt)';
        $dialogTitle = 'Petualangan Baru Menanti! 🎒';
        $dialogMessage = 'Saldomu di kisaran Rp 500.000 s/d Rp 1.000.000. Kucing petualangmu siap melangkah lebih jauh. Terus berjuang dan tambah tabunganmu!';
        $dialogTip = 'Sisihkan minimal 10% dari sisa saldo ke Pos Tabungan Impian.';
        $glowColor = 'from-amber-500/20 to-amber-500/5';
        $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800';
        $dotClass = 'bg-amber-500';
        $dotPingClass = 'bg-amber-400';
        $animClass = 'animate-cat-tail';
    } elseif ($amount <= 10000000) {
        $state = 'hungry';
        $image = 'images/04_perintis.png';
        $label = 'Perintis';
        $tagline = 'Status: Perintis (Rp 1jt - 10jt)';
        $dialogTitle = 'Ngeong! Langkah Perintis Finansial! 🚀';
        $dialogMessage = 'Saldomu mencapai jutaan rupiah (Rp 1jt - 10jt)! Kucing perintismu berenergi dan siap menemani langkah perintis investasimu!';
        $dialogTip = 'Disiplin anggaran dan alokasikan ke tujuan finansial jangka panjang.';
        $glowColor = 'from-sky-500/20 to-sky-500/5';
        $badgeClass = 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800';
        $dotClass = 'bg-sky-500';
        $dotPingClass = 'bg-sky-400';
        $animClass = 'animate-cat-bounce';
    } else {
        $state = 'healthy';
        $image = 'images/05_sultan.png';
        $label = 'Sultan';
        $tagline = 'Status: Sultan (> Rp 10jt)';
        $dialogTitle = 'Purrr-fect! Meong Sultan Makmur 👑✨';
        $dialogMessage = 'Ampun Sultan! Saldomu melimpah di atas Rp 10.000.000! Kucingmu gemoy, royal, dan sangat bahagia. Pertahankan aset dan investasimu!';
        $dialogTip = 'Optimalkan aset produktif dan diversifikasi instrumen investasi.';
        $glowColor = 'from-emerald-500/20 to-emerald-500/5';
        $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800';
        $dotClass = 'bg-emerald-500';
        $dotPingClass = 'bg-emerald-400';
        $animClass = 'animate-cat-bounce';
    }
@endphp

<div x-data="{
    openDialog: false,
    playMeow() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const now = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            
            if ('{{ $state }}' === 'healthy') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(523.25, now);
                osc.frequency.exponentialRampToValueAtTime(783.99, now + 0.12);
                osc.frequency.exponentialRampToValueAtTime(1046.50, now + 0.25);
                gain.gain.setValueAtTime(0.12, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.28);
                osc.start(now);
                osc.stop(now + 0.28);
            } else if ('{{ $state }}' === 'hungry') {
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(659.25, now);
                osc.frequency.linearRampToValueAtTime(587.33, now + 0.14);
                osc.frequency.linearRampToValueAtTime(783.99, now + 0.28);
                gain.gain.setValueAtTime(0.12, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.32);
                osc.start(now);
                osc.stop(now + 0.32);
            } else {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(440, now);
                osc.frequency.linearRampToValueAtTime(349.23, now + 0.22);
                gain.gain.setValueAtTime(0.1, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
                osc.start(now);
                osc.stop(now + 0.3);
            }
        } catch(e) {}
    }
}" class="relative inline-flex items-center">

    <!-- Interactive Mascot Button -->
    <button type="button"
            @click="openDialog = true; playMeow();"
            class="group relative inline-flex items-center justify-center rounded-full hover:scale-105 active:scale-95 transition-transform focus:outline-none"
            title="Klik untuk cek kondisi kucing keuanganmu">
        
        <!-- Animated Cat Character Container (Circular & Full) -->
        <div class="relative w-10 h-10 sm:w-11 sm:h-11 rounded-full flex items-center justify-center bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border border-white/70 dark:border-white/20 shadow-xs group-hover:shadow-md transition-all overflow-hidden">
            <img src="{{ asset($image) }}" alt="{{ $label }}" class="w-full h-full object-cover {{ $animClass }} select-none pointer-events-none">
        </div>

        <!-- Mini Status Dot / Mood Indicator -->
        <span class="absolute -top-0.5 -right-0.5 flex h-3.5 w-3.5 z-10 pointer-events-none">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $dotPingClass }}"></span>
            <span class="relative inline-flex rounded-full h-3.5 w-3.5 border-2 border-white dark:border-slate-900 {{ $dotClass }}"></span>
        </span>

    </button>

    <!-- Interactive Dialogue Modal / Cat Speech Bubble -->
    <div x-show="openDialog"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @click.self="openDialog = false">
        
        <div class="w-full max-w-sm bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl p-5 shadow-2xl relative overflow-hidden text-center">
            <!-- Background Glow Decor -->
            <div class="absolute -top-12 -left-12 w-32 h-32 rounded-full bg-gradient-to-br {{ $glowColor }} blur-2xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -right-12 w-32 h-32 rounded-full bg-gradient-to-br {{ $glowColor }} blur-2xl pointer-events-none"></div>

            <!-- Close Button -->
            <button @click="openDialog = false" 
                    type="button" 
                    class="absolute top-4 right-4 w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center justify-center transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <!-- Mascot Big Avatar in Modal (Circular & Full) -->
            <div class="mx-auto w-24 h-24 rounded-full flex items-center justify-center bg-white/80 dark:bg-slate-800/80 border-2 border-white/80 dark:border-white/20 shadow-md mb-3 overflow-hidden">
                <img src="{{ asset($image) }}" alt="{{ $label }}" class="w-full h-full object-cover {{ $animClass }} select-none">
            </div>

            <!-- Title & Status Tag -->
            <h3 class="text-base font-black text-slate-800 dark:text-slate-100 mb-1">
                {{ $dialogTitle }}
            </h3>
            <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $badgeClass }} mb-3">
                <span>{{ $tagline }}</span>
            </div>

            <!-- Speech Message -->
            <div class="bg-slate-50 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700/60 rounded-2xl p-3.5 text-xs text-slate-600 dark:text-slate-300 mb-4 leading-relaxed text-left">
                <p class="font-medium mb-2">{{ $dialogMessage }}</p>
                <div class="pt-2 border-t border-slate-200/50 dark:border-slate-700/50 flex items-start gap-1.5 text-[11px] text-sky-600 dark:text-sky-400 font-semibold">
                    <span>{{ $dialogTip }}</span>
                </div>
            </div>

            <!-- Action Buttons inside Modal -->
            <div class="grid grid-cols-2 gap-2">
                <button type="button"
                        @click="playMeow()"
                        class="py-2.5 px-3 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold active:scale-95 transition-all flex items-center justify-center gap-1">
                    <span>Meong Lagi!</span> 🐾
                </button>
                <a href="{{ route('transactions.create') }}"
                   class="py-2.5 px-3 rounded-2xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-md shadow-sky-500/20 active:scale-95 transition-all flex items-center justify-center">
                    Catat Transaksi
                </a>
            </div>
        </div>
    </div>
</div>
