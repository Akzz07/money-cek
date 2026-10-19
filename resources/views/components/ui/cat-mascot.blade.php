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

    if ($amount < 500000) {
        $state = 'sick';
        $label = 'Kucing Sakit';
        $tagline = 'Saldo Kritis (< Rp 500rb)';
        $dialogTitle = 'Aduh... Kucingmu Meriang 🩹';
        $dialogMessage = 'Saldomu saat ini di bawah Rp 500.000. Kucingmu lemas kedinginan. Tahan dulu belanja konsumtif dan amankan kebutuhan pokok ya!';
        $dialogTip = 'Prioritaskan pelunasan tagihan terdekat dan batasi pengeluaran harian.';
        $glowColor = 'from-rose-500/20 to-rose-500/5';
        $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800';
        $animClass = 'animate-cat-shiver';
    } elseif ($amount < 1500000) {
        $state = 'hungry';
        $label = 'Kucing Lapar';
        $tagline = 'Saldo Menipis (< Rp 1.5jt)';
        $dialogTitle = 'Kruyuukk... Kucing Mulai Lapar 🐟';
        $dialogMessage = 'Saldomu di kisaran Rp 500.000 s/d Rp 1.500.000. Kucingmu mulai mengeong minta ikan segar. Yuk tambah asupan saldo dan terus menabung!';
        $dialogTip = 'Sisihkan minimal 10% dari sisa saldo ke Pos Tabungan Impian.';
        $glowColor = 'from-amber-500/20 to-amber-500/5';
        $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800';
        $animClass = 'animate-cat-tail';
    } else {
        $state = 'healthy';
        $label = 'Kucing Sehat & Gemoy';
        $tagline = 'Finansial Makmur (≥ Rp 1.5jt)';
        $dialogTitle = 'Purrr-fect! Kucing Sehat & Makmur ✨';
        $dialogMessage = 'Luar biasa! Saldomu di atas Rp 1.500.000. Kucingmu gemoy, berenergi, dan sangat bahagia. Pertahankan stabilitas arus kas keuanganmu!';
        $dialogTip = 'Pertahankan disiplin anggaran dan optimalkan aset produktifmu.';
        $glowColor = 'from-emerald-500/20 to-emerald-500/5';
        $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800';
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
            class="group relative inline-flex items-center gap-1.5 p-1 -m-1 rounded-2xl hover:bg-slate-200/50 dark:hover:bg-slate-800/60 active:scale-95 transition-all focus:outline-none"
            title="Klik untuk cek kondisi kucing keuanganmu">
        
        <!-- Animated Cat Character SVG Container -->
        <div class="relative w-9 h-9 sm:w-10 sm:h-10 rounded-2xl flex items-center justify-center bg-gradient-to-br {{ $glowColor }} border border-black/5 dark:border-white/10 shadow-xs group-hover:shadow-md transition-all">
            
            @if($state === 'sick')
                <!-- KUCING SAKIT (Shivering, Icepack / Bandage, Sad eyes) -->
                <svg class="w-7 h-7 sm:w-8 sm:h-8 {{ $animClass }}" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Cat Ears with bandages -->
                    <polygon points="12,28 20,8 30,24" fill="#94A3B8" />
                    <polygon points="16,24 21,12 28,22" fill="#FCA5A5" />
                    <polygon points="52,28 44,8 34,24" fill="#94A3B8" />
                    <polygon points="48,24 43,12 36,22" fill="#FCA5A5" />
                    <!-- Cat Head -->
                    <ellipse cx="32" cy="38" rx="22" ry="18" fill="#CBD5E1" stroke="#64748B" stroke-width="2.5" />
                    <!-- Ice pack on top of head -->
                    <ellipse cx="32" cy="21" rx="9" ry="5" fill="#38BDF8" stroke="#0284C7" stroke-width="2" />
                    <circle cx="32" cy="18" r="2.5" fill="#E0F2FE" />
                    <!-- Bandage on right cheek -->
                    <rect x="42" y="36" width="10" height="4" rx="1.5" transform="rotate(25 42 36)" fill="#FDE047" stroke="#CA8A04" stroke-width="1.2" />
                    <!-- Sad Eyes (x x or tear) -->
                    <line x1="22" y1="34" x2="28" y2="40" stroke="#475569" stroke-width="2.5" stroke-linecap="round" />
                    <line x1="28" y1="34" x2="22" y2="40" stroke="#475569" stroke-width="2.5" stroke-linecap="round" />
                    <line x1="36" y1="34" x2="42" y2="40" stroke="#475569" stroke-width="2.5" stroke-linecap="round" />
                    <line x1="42" y1="34" x2="36" y2="40" stroke="#475569" stroke-width="2.5" stroke-linecap="round" />
                    <!-- Tear droplet -->
                    <circle cx="21" cy="43" r="2" fill="#38BDF8" />
                    <!-- Nose & Downward Sad Mouth -->
                    <polygon points="32,43 30,41 34,41" fill="#F43F5E" />
                    <path d="M29 47 Q32 44 35 47" stroke="#475569" stroke-width="2" stroke-linecap="round" fill="none" />
                </svg>
            @elseif($state === 'hungry')
                <!-- KUCING LAPAR (Pleading big eyes, open mouth, wagging tail vibe) -->
                <svg class="w-7 h-7 sm:w-8 sm:h-8 {{ $animClass }}" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Cat Ears -->
                    <polygon points="12,28 20,8 30,24" fill="#F59E0B" />
                    <polygon points="16,24 21,12 28,22" fill="#FDE68A" />
                    <polygon points="52,28 44,8 34,24" fill="#F59E0B" />
                    <polygon points="48,24 43,12 36,22" fill="#FDE68A" />
                    <!-- Cat Head -->
                    <ellipse cx="32" cy="38" rx="22" ry="18" fill="#FCD34D" stroke="#D97706" stroke-width="2.5" />
                    <!-- Tiger stripes on forehead -->
                    <path d="M32 24 L32 29 M28 26 L30 29 M36 26 L34 29" stroke="#B45309" stroke-width="2" stroke-linecap="round" />
                    <!-- Big Pleading Eyes with highlight -->
                    <ellipse cx="23" cy="35" rx="4" ry="5" fill="#1E293B" />
                    <circle cx="22" cy="33" r="1.5" fill="#FFFFFF" />
                    <ellipse cx="41" cy="35" rx="4" ry="5" fill="#1E293B" />
                    <circle cx="40" cy="33" r="1.5" fill="#FFFFFF" />
                    <!-- Rosy cheeks -->
                    <circle cx="18" cy="41" r="3" fill="#F87171" opacity="0.4" />
                    <circle cx="46" cy="41" r="3" fill="#F87171" opacity="0.4" />
                    <!-- Nose & Open Hungry Mouth -->
                    <polygon points="32,40 30,38 34,38" fill="#EC4899" />
                    <path d="M29 42 Q32 46 35 42" stroke="#B45309" stroke-width="2" stroke-linecap="round" fill="#BE123C" />
                    <!-- Fish bone floating nearby -->
                    <g class="animate-cat-tail" transform="translate(46, 12)">
                        <path d="M0 8 L12 8 M4 4 L4 12 M8 5 L8 11 M12 6 L15 3 L15 13 L12 10 Z" stroke="#D97706" stroke-width="1.5" fill="none" />
                    </g>
                </svg>
            @else
                <!-- KUCING SEHAT & GEMOY (Chubby, happy eyes, sparkles, golden coin crown) -->
                <svg class="w-7 h-7 sm:w-8 sm:h-8 {{ $animClass }}" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Cat Ears with bouncy feel -->
                    <polygon points="12,28 19,8 29,24" fill="#3B82F6" />
                    <polygon points="16,24 20,12 27,22" fill="#BAE6FD" />
                    <polygon points="52,28 45,8 35,24" fill="#3B82F6" />
                    <polygon points="48,24 44,12 37,22" fill="#BAE6FD" />
                    <!-- Cat Head -->
                    <ellipse cx="32" cy="38" rx="23" ry="19" fill="#60A5FA" stroke="#1D4ED8" stroke-width="2.5" />
                    <!-- Crown / Coin Tiara -->
                    <polygon points="26,20 32,13 38,20 35,22 29,22" fill="#FBBF24" stroke="#B45309" stroke-width="1.5" />
                    <circle cx="32" cy="17" r="1.5" fill="#EF4444" />
                    <!-- Cheerful Happy Eyes (^ ^) -->
                    <path d="M20 36 Q24 31 28 36" stroke="#1E3A8A" stroke-width="2.8" stroke-linecap="round" fill="none" />
                    <path d="M36 36 Q40 31 44 36" stroke="#1E3A8A" stroke-width="2.8" stroke-linecap="round" fill="none" />
                    <!-- Blushing Rosy Cheeks -->
                    <circle cx="17" cy="41" r="3.5" fill="#F43F5E" opacity="0.5" />
                    <circle cx="47" cy="41" r="3.5" fill="#F43F5E" opacity="0.5" />
                    <!-- Happy Cat Mouth (:3) -->
                    <polygon points="32,39 30.5,37 33.5,37" fill="#F43F5E" />
                    <path d="M28 41 Q30.5 44 32 41 Q33.5 44 36 41" stroke="#1E3A8A" stroke-width="2.2" stroke-linecap="round" fill="none" />
                    <!-- Whiskers -->
                    <line x1="12" y1="39" x2="6" y2="38" stroke="#93C5FD" stroke-width="1.8" stroke-linecap="round" />
                    <line x1="12" y1="42" x2="7" y2="43" stroke="#93C5FD" stroke-width="1.8" stroke-linecap="round" />
                    <line x1="52" y1="39" x2="58" y2="38" stroke="#93C5FD" stroke-width="1.8" stroke-linecap="round" />
                    <line x1="52" y1="42" x2="57" y2="43" stroke="#93C5FD" stroke-width="1.8" stroke-linecap="round" />
                </svg>
            @endif

            <!-- Mini Status Dot / Mood Indicator -->
            <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $state === 'sick' ? 'bg-rose-400' : ($state === 'hungry' ? 'bg-amber-400' : 'bg-emerald-400') }}"></span>
                <span class="relative inline-flex rounded-full h-3.5 w-3.5 border-2 border-white dark:border-slate-900 {{ $state === 'sick' ? 'bg-rose-500' : ($state === 'hungry' ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
            </span>
        </div>

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

            <!-- Mascot Big Avatar in Modal -->
            <div class="mx-auto w-20 h-20 rounded-3xl flex items-center justify-center bg-gradient-to-br {{ $glowColor }} border border-black/5 dark:border-white/10 shadow-inner mb-3">
                @if($state === 'sick')
                    <span class="text-4xl {{ $animClass }}">😿</span>
                @elseif($state === 'hungry')
                    <span class="text-4xl {{ $animClass }}">🐱</span>
                @else
                    <span class="text-4xl {{ $animClass }}">😻</span>
                @endif
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
                <div class="pt-2 border-t border-slate-200/50 dark:border-slate-700/50 flex items-start gap-1.5 text-[11px] text-blue-600 dark:text-blue-400 font-semibold">
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
                   class="py-2.5 px-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 active:scale-95 transition-all flex items-center justify-center">
                    Catat Transaksi
                </a>
            </div>
        </div>
    </div>
</div>
