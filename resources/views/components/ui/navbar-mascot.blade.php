@props([
    'balance' => null,
])

@php
    $user = Auth::user();
    $amount = 0;
    if ($balance instanceof \App\Domain\Money\Money) {
        $amount = (float) $balance->toDecimalString();
    } elseif (is_numeric($balance)) {
        $amount = (float) $balance;
    } elseif ($user) {
        $amount = (float) app(\App\Services\Accounts\AccountBalanceService::class)->getTotalNetWorth($user)->toDecimalString();
    }

    if ($amount < 100000) {
        $prefix = 'mode_survival';
        $modeName = 'Mode Survival';
        $meows = [
            'Lemas kedinginan... hemat ya! 🩹',
            'Tahan jajan dulu meong...',
            'Prioritaskan makan pokok ya!',
            'Semoga rezeki lekas datang! ✨',
        ];
    } elseif ($amount <= 500000) {
        $prefix = 'pejuang';
        $modeName = 'Pejuang';
        $meows = [
            'Semangat berjuang meong! 💪',
            'Ikat kepala, pantang boros!',
            'Sedikit lagi naik level!',
            'Catat terus transaksimu!',
        ];
    } elseif ($amount <= 1000000) {
        $prefix = 'petualang';
        $modeName = 'Petualang';
        $meows = [
            'Petualangan finansial dimulai! 🎒',
            'Meneropong peluang baru! 🔭',
            'Tabungan makin bertambah meong!',
            'Kucing petualang siap temani kamu!',
        ];
    } elseif ($amount <= 10000000) {
        $prefix = 'perintis';
        $modeName = 'Perintis';
        $meows = [
            'Langkah perintis sukses! 💼✨',
            'Portofolio makin rapi meong!',
            'Kerja cerdas, kelola bijak!',
            'Investasi masa depan cerah!',
        ];
    } else {
        $prefix = 'sultan';
        $modeName = 'Sultan';
        $meows = [
            'Ampun Sultan! Makmur sentosa! 👑',
            'Tahta emas, saldo melimpah!',
            'Kucing Sultan sangat bahagia! 😻',
            'Pertahankan aset produktifmu!',
        ];
    }
@endphp

<div x-data="{
    prefix: '{{ $prefix }}',
    frameUrls: [
        '{{ asset("images/spriteMascot/{$prefix}_01.png") }}',
        '{{ asset("images/spriteMascot/{$prefix}_02.png") }}',
        '{{ asset("images/spriteMascot/{$prefix}_03.png") }}',
        '{{ asset("images/spriteMascot/{$prefix}_04.png") }}',
        '{{ asset("images/spriteMascot/{$prefix}_05.png") }}',
        '{{ asset("images/spriteMascot/{$prefix}_06.png") }}',
        '{{ asset("images/spriteMascot/{$prefix}_07.png") }}'
    ],
    meowPhrases: {{ Js::from($meows) }},
    currentFrame: 1,
    posX: 10, // percentage 4% to 86%
    posY: 0,
    direction: 1, // 1: right, -1: left
    state: 'walking', // walking, paused, sleeping, tapped
    showBubble: false,
    bubbleText: '',
    walkStep: 0,
    walkFrames: [1, 2, 3, 2],
    idleTimer: 0,
    bubbleTimeout: null,
    motionReduced: false,

    get currentFrameUrl() {
        return this.frameUrls[this.currentFrame - 1] || this.frameUrls[0];
    },

    init() {
        // Preload all 7 frames for instant 60fps switching
        this.frameUrls.forEach(url => {
            const img = new Image();
            img.src = url;
        });

        this.motionReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (this.motionReduced) {
            this.posX = 80;
            this.currentFrame = 6;
            this.state = 'paused';
            return;
        }

        // Start patrol loop
        setInterval(() => this.tick(), 130);

        // Activity listeners for sleep mode
        const resetActivity = () => {
            if (this.state === 'sleeping') {
                this.wakeUp();
            }
            this.idleTimer = 0;
        };

        window.addEventListener('pointerdown', resetActivity, { passive: true });
        window.addEventListener('keydown', resetActivity, { passive: true });
        window.addEventListener('scroll', resetActivity, { passive: true });

        // Sleep timer check every second
        setInterval(() => {
            this.idleTimer++;
            if (this.idleTimer >= 22 && this.state !== 'sleeping') {
                this.goToSleep();
            }
        }, 1000);
    },

    tick() {
        if (this.state !== 'walking') {
            return;
        }

        // 1. Advance walk frame
        this.walkStep = (this.walkStep + 1) % this.walkFrames.length;
        this.currentFrame = this.walkFrames[this.walkStep];

        // 2. Move position smoothly
        this.posX += this.direction * 0.4;

        // 3. Jump over center FAB button (approx 36% to 64% of track width)
        if (this.posX >= 36 && this.posX <= 64) {
            const progress = (this.posX - 36) / 28; // 0 to 1
            this.posY = -Math.sin(progress * Math.PI) * 24; // smooth jump arc
        } else {
            this.posY = 0;
        }

        // 4. Hit boundaries
        if (this.posX >= 88 && this.direction === 1) {
            this.posX = 88;
            this.pauseAtBoundary(-1);
        } else if (this.posX <= 8 && this.direction === -1) {
            this.posX = 8;
            this.pauseAtBoundary(1);
        }
    },

    pauseAtBoundary(nextDirection) {
        this.state = 'paused';
        // Pose: Resting/Eating or curious pose
        this.currentFrame = (Math.random() > 0.5) ? 6 : 4;
        
        // Occasionally show a speech bubble on pause
        if (Math.random() > 0.45) {
            this.triggerBubble(this.meowPhrases[Math.floor(Math.random() * this.meowPhrases.length)], 2600);
        }

        setTimeout(() => {
            if (this.state === 'paused') {
                this.direction = nextDirection;
                this.state = 'walking';
            }
        }, 2800);
    },

    tapMascot() {
        this.idleTimer = 0;
        this.state = 'tapped';
        // Pose: Joyful celebration / curious
        this.currentFrame = (Math.random() > 0.5) ? 4 : 5;
        this.playMeowSound();

        // Haptic feedback
        if (navigator.vibrate) {
            navigator.vibrate([25, 30, 25]);
        }

        // Random encouragement meow phrase
        const phrase = this.meowPhrases[Math.floor(Math.random() * this.meowPhrases.length)];
        this.triggerBubble(phrase, 3000);

        setTimeout(() => {
            if (this.state === 'tapped') {
                this.state = 'walking';
            }
        }, 3000);
    },

    goToSleep() {
        this.state = 'sleeping';
        this.currentFrame = 7; // Sleep frame with Zzz
        this.triggerBubble('Zzz... 💤', 8000);
    },

    wakeUp() {
        this.state = 'paused';
        this.currentFrame = 4;
        this.triggerBubble('Meong! Bangun! ✨', 2000);
        setTimeout(() => {
            this.state = 'walking';
        }, 1200);
    },

    triggerBubble(text, duration = 3000) {
        this.bubbleText = text;
        this.showBubble = true;
        if (this.bubbleTimeout) {
            clearTimeout(this.bubbleTimeout);
        }
        this.bubbleTimeout = setTimeout(() => {
            this.showBubble = false;
        }, duration);
    },

    playMeowSound() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const now = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, now); // D5
            osc.frequency.exponentialRampToValueAtTime(880.00, now + 0.12); // A5
            osc.frequency.exponentialRampToValueAtTime(1174.66, now + 0.22); // D6
            gain.gain.setValueAtTime(0.12, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.26);
            osc.start(now);
            osc.stop(now + 0.26);
        } catch(e) {}
    }
}" 
class="absolute bottom-[calc(100%-6px)] left-0 right-0 pointer-events-none z-30 select-none overflow-visible">

    <!-- Patrol Track Floor Area -->
    <div class="relative w-full h-14 pointer-events-none">

        <!-- Walking Mascot Character Container -->
        <div :style="`left: ${posX}%; transform: translate3d(-50%, ${posY}px, 0) scaleX(${direction});`"
             @click.stop="tapMascot()"
             class="absolute bottom-0 pointer-events-auto cursor-pointer select-none touch-manipulation group"
             style="will-change: transform;"
             title="Kucing Penjaga Keuanganmu (Klik untuk tepuk!)">
            
            <!-- Speech Bubble (Non-flipped) -->
            <div x-show="showBubble"
                 x-cloak
                 :style="`transform: scaleX(${direction});`"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2 scale-90"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-90"
                 class="absolute -top-9 left-1/2 -translate-x-1/2 whitespace-nowrap px-2.5 py-1 rounded-xl bg-slate-900/90 dark:bg-slate-800/95 backdrop-blur-md text-white text-[10px] font-bold shadow-lg pointer-events-none z-50 border border-white/20 dark:border-slate-700/80 flex items-center gap-1">
                <span x-text="bubbleText"></span>
                <!-- Speech Tail -->
                <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-slate-900/90 dark:bg-slate-800/95 rotate-45 border-r border-b border-white/20 dark:border-slate-700/80"></span>
            </div>

            <!-- Preloaded Animated Mascot Image -->
            <img :src="currentFrameUrl" 
                 alt="{{ $modeName }}" 
                 class="w-12 h-12 sm:w-14 sm:h-14 object-contain drop-shadow-md select-none pointer-events-none"
                 draggable="false">
        </div>

    </div>
</div>
