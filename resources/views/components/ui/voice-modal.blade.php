<div x-data="{
    isOpen: false,
    step: 'prompt', // prompt, listening, processing, review, saved, error
    supported: true,
    transcript: '',
    interimText: '',
    errorMessage: '',
    submitting: false,
    manualQuery: '',
    parsed: {
        type: 'expense',
        amount: '',
        amount_formatted: '',
        source_account_id: '',
        category_id: '',
        description: '',
        transaction_date: '',
        client_request_id: ''
    },
    metaAccounts: [],
    metaCategories: [],
    recognition: null,

    init() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            this.supported = false;
        }
    },

    openModal() {
        this.isOpen = true;
        this.step = 'prompt';
        this.transcript = '';
        this.interimText = '';
        this.errorMessage = '';
        this.manualQuery = '';
        this.submitting = false;

        // Auto start listening after small modal entrance delay if supported
        if (this.supported) {
            setTimeout(() => {
                this.startListening();
            }, 300);
        }
    },

    closeModal() {
        this.stopListening();
        this.isOpen = false;
    },

    startListening() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            this.supported = false;
            return;
        }

        try {
            if (this.recognition) {
                this.recognition.abort();
            }

            this.recognition = new SpeechRecognition();
            this.recognition.lang = 'id-ID';
            this.recognition.interimResults = true;
            this.recognition.continuous = false;
            this.recognition.maxAlternatives = 1;

            this.step = 'listening';
            this.transcript = '';
            this.interimText = '';
            this.errorMessage = '';

            this.recognition.onstart = () => {
                this.step = 'listening';
            };

            this.recognition.onresult = (event) => {
                let interim = '';
                let final = '';

                for (let i = event.resultIndex; i < event.results.length; ++i) {
                    if (event.results[i].isFinal) {
                        final += event.results[i][0].transcript;
                    } else {
                        interim += event.results[i][0].transcript;
                    }
                }

                if (final) {
                    this.transcript = final;
                    this.interimText = '';
                } else {
                    this.interimText = interim;
                }
            };

            this.recognition.onerror = (event) => {
                if (event.error === 'no-speech') {
                    this.errorMessage = 'Suara tidak terdeteksi. Silakan coba lagi dan bicara lebih jelas.';
                } else if (event.error === 'not-allowed') {
                    this.errorMessage = 'Izin mikrofon ditolak oleh browser. Harap izinkan akses mikrofon di pengaturan browser Anda.';
                } else {
                    this.errorMessage = 'Terjadi kesalahan mikrofon (' + event.error + ').';
                }
                this.step = 'error';
            };

            this.recognition.onend = () => {
                const queryText = this.transcript || this.interimText;
                if (queryText && queryText.trim().length > 0) {
                    this.transcript = queryText.trim();
                    this.sendForParsing(this.transcript);
                } else if (this.step === 'listening') {
                    this.step = 'prompt';
                }
            };

            this.recognition.start();
        } catch (e) {
            this.errorMessage = 'Gagal menginisialisasi mikrofon: ' + e.message;
            this.step = 'error';
        }
    },

    stopListening() {
        if (this.recognition) {
            try {
                this.recognition.abort();
            } catch (e) {}
            this.recognition = null;
        }
    },

    sendForParsing(text) {
        this.step = 'processing';
        this.errorMessage = '';

        fetch('{{ route('voice.parse') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
            },
            body: JSON.stringify({ text: text })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Gagal memproses kalimat suara (HTTP ' + response.status + ')');
            }
            return response.json();
        })
        .then(result => {
            if (result.status === 'success' && result.data) {
                const d = result.data;
                this.parsed = {
                    type: d.type || 'expense',
                    amount: d.amount ? parseFloat(d.amount) : '',
                    amount_formatted: d.amount_formatted || '',
                    source_account_id: d.source_account_id || '',
                    category_id: d.category_id || '',
                    description: d.description || '',
                    transaction_date: d.transaction_date || new Date().toISOString().split('T')[0],
                    client_request_id: d.client_request_id || ''
                };

                if (result.meta) {
                    this.metaAccounts = result.meta.accounts || [];
                    this.metaCategories = result.meta.categories || [];
                }

                // If no account was matched, pick first
                if (!this.parsed.source_account_id && this.metaAccounts.length > 0) {
                    this.parsed.source_account_id = this.metaAccounts[0].id;
                }

                // If no category was matched, pick first of that type
                if (!this.parsed.category_id) {
                    const firstCat = this.metaCategories.find(c => c.type === this.parsed.type);
                    if (firstCat) {
                        this.parsed.category_id = firstCat.id;
                    }
                }

                this.step = 'review';
            } else {
                throw new Error('Hasil pemrosesan suara tidak valid.');
            }
        })
        .catch(err => {
            this.errorMessage = err.message || 'Gagal memproses perintah suara.';
            this.step = 'error';
        });
    },

    submitDirectly() {
        if (!this.parsed.amount || this.parsed.amount <= 0) {
            alert('Nominal transaksi wajib diisi!');
            return;
        }

        if (!this.parsed.source_account_id) {
            alert('Akun sumber wajib dipilih!');
            return;
        }

        if (!this.parsed.category_id) {
            alert('Kategori wajib dipilih!');
            return;
        }

        this.submitting = true;

        fetch('{{ route('transactions.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
            },
            body: JSON.stringify({
                type: this.parsed.type,
                source_account_id: this.parsed.source_account_id,
                category_id: this.parsed.category_id,
                amount: this.parsed.amount,
                transaction_date: this.parsed.transaction_date,
                description: this.parsed.description,
                client_request_id: this.parsed.client_request_id
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || 'Gagal menyimpan transaksi');
                });
            }
            return response.json();
        })
        .then(data => {
            this.step = 'saved';
            setTimeout(() => {
                window.location.href = data.redirect_url || '{{ route('transactions.index') }}';
            }, 900);
        })
        .catch(err => {
            this.submitting = false;
            alert(err.message || 'Terjadi kesalahan saat menyimpan transaksi.');
        });
    },

    editInFullForm() {
        const params = new URLSearchParams({
            type: this.parsed.type || 'expense',
            amount: this.parsed.amount || '',
            category_id: this.parsed.category_id || '',
            source_account_id: this.parsed.source_account_id || '',
            description: this.parsed.description || '',
            transaction_date: this.parsed.transaction_date || ''
        });

        window.location.href = '{{ route('transactions.create') }}?' + params.toString();
    },

    useExample(text) {
        this.transcript = text;
        this.sendForParsing(text);
    }
}"
@open-voice-modal.window="openModal()"
x-show="isOpen"
x-cloak
class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200"
@click.self="closeModal()"
@keydown.escape.window="closeModal()">

    <!-- Voice Sheet Container -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-12 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-12 sm:scale-95"
         class="w-full max-w-md bg-white dark:bg-slate-900 border-t sm:border border-slate-200/80 dark:border-slate-800 rounded-t-3xl sm:rounded-3xl p-5 shadow-2xl relative overflow-hidden max-h-[90vh] flex flex-col">
        
        <!-- Top Drag Bar for Mobile -->
        <div class="w-12 h-1.5 bg-slate-300 dark:bg-slate-700 rounded-full mx-auto mb-3 sm:hidden"></div>

        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-sky-500 to-indigo-600 text-white flex items-center justify-center shadow-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-black text-slate-800 dark:text-slate-100">
                    Catat dengan Suara (AI)
                </h3>
            </div>
            <button type="button" 
                    @click="closeModal()" 
                    class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center justify-center transition-colors"
                    aria-label="Tutup">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Body / Content Area -->
        <div class="overflow-y-auto flex-1 pr-0.5 space-y-4">

            <!-- STATE: PROMPT / WAITING TO LISTEN -->
            <div x-show="step === 'prompt'" class="text-center py-4 space-y-4">
                <div class="relative mx-auto w-20 h-20 rounded-full bg-sky-50 dark:bg-sky-950/60 border border-sky-200 dark:border-sky-800 flex items-center justify-center">
                    <button type="button" 
                            @click="startListening()"
                            class="w-14 h-14 rounded-full bg-gradient-to-tr from-[#0284C7] via-[#0EA5E9] to-[#38BDF8] text-white flex items-center justify-center shadow-lg shadow-sky-500/35 hover:scale-105 active:scale-95 transition-all">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                    </button>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200">Tekan mikrofon & mulailah bicara</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Sebutkan jenis transaksi, nominal, dan akun/kategori</p>
                </div>
            </div>

            <!-- STATE: LISTENING (Active Waveform & Live Transcript) -->
            <div x-show="step === 'listening'" class="text-center py-5 space-y-4">
                <!-- Glowing Pulsing Mic Orb with Waveform Bars -->
                <div class="relative mx-auto w-24 h-24 flex items-center justify-center">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 opacity-25"></span>
                    <span class="animate-pulse absolute inline-flex h-20 w-20 rounded-full bg-sky-300 dark:bg-sky-800 opacity-40"></span>
                    <div class="relative w-16 h-16 rounded-full bg-gradient-to-tr from-sky-500 to-indigo-600 text-white flex items-center justify-center shadow-xl shadow-sky-500/40">
                        <!-- Waveform Animated Bars -->
                        <div class="flex items-center gap-1">
                            <span class="w-1 bg-white rounded-full animate-bounce h-5" style="animation-delay: 0.1s"></span>
                            <span class="w-1 bg-white rounded-full animate-bounce h-8" style="animation-delay: 0.25s"></span>
                            <span class="w-1 bg-white rounded-full animate-bounce h-4" style="animation-delay: 0.15s"></span>
                            <span class="w-1 bg-white rounded-full animate-bounce h-7" style="animation-delay: 0.3s"></span>
                            <span class="w-1 bg-white rounded-full animate-bounce h-5" style="animation-delay: 0.2s"></span>
                        </div>
                    </div>
                </div>

                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-sky-100 dark:bg-sky-950/70 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 animate-pulse">
                        <span class="w-2 h-2 rounded-full bg-sky-500 animate-ping"></span>
                        <span>Mendengarkan ucapanmu...</span>
                    </span>

                    <!-- Live Realtime Transcript -->
                    <div class="min-h-[50px] p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700 text-left mt-3">
                        <p class="text-sm font-bold text-slate-800 dark:text-slate-100" x-text="transcript || interimText || 'Katakan sesuatu...'"></p>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 italic mt-1" x-show="!transcript && !interimText">
                            Contoh: "Beli kopi susu 25 ribu pakai BCA"
                        </p>
                    </div>
                </div>

                <button type="button" 
                        @click="stopListening()"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 active:scale-95 transition-all">
                    Selesai Bicara
                </button>
            </div>

            <!-- STATE: PROCESSING (NLP Analysis) -->
            <div x-show="step === 'processing'" class="text-center py-8 space-y-3">
                <div class="inline-block animate-spin rounded-full h-10 w-10 border-3 border-sky-600 border-t-transparent"></div>
                <p class="text-sm font-bold text-slate-800 dark:text-slate-200">Menganalisis Perintah Keuangan...</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 italic" x-text="'\"' + transcript + '\"'"></p>
            </div>

            <!-- STATE: REVIEW (Slot Verification & 1-Tap Save) -->
            <div x-show="step === 'review'" class="space-y-3.5">
                <!-- Spoken Transcript Banner -->
                <div class="p-2.5 rounded-2xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200/60 dark:border-slate-700 flex items-start gap-2">
                    <span class="text-xs">💬</span>
                    <p class="text-xs text-slate-600 dark:text-slate-300 italic font-medium leading-relaxed" x-text="'\"' + transcript + '\"'"></p>
                </div>

                <!-- Parsed Slots Review Form -->
                <div class="bg-white dark:bg-slate-800/90 rounded-2xl border border-slate-200/90 dark:border-slate-700/80 p-3.5 space-y-3 shadow-2xs">
                    
                    <!-- Type Selector (Expense vs Income) -->
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-700/60">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Jenis</span>
                        <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-900 p-0.5 text-xs font-bold">
                            <button type="button" 
                                    @click="parsed.type = 'expense'"
                                    :class="parsed.type === 'expense' ? 'bg-rose-500 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400'"
                                    class="px-2.5 py-1 rounded-lg transition-all">
                                Pengeluaran
                            </button>
                            <button type="button" 
                                    @click="parsed.type = 'income'"
                                    :class="parsed.type === 'income' ? 'bg-emerald-500 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400'"
                                    class="px-2.5 py-1 rounded-lg transition-all">
                                Pemasukan
                            </button>
                        </div>
                    </div>

                    <!-- Nominal Amount Input -->
                    <div class="flex items-center justify-between gap-2">
                        <label class="text-xs font-semibold text-slate-500 dark:text-slate-400 shrink-0">Nominal (Rp)</label>
                        <div class="relative w-44">
                            <input type="number" 
                                   x-model="parsed.amount" 
                                   placeholder="0"
                                   class="w-full text-right text-base font-extrabold text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        </div>
                    </div>

                    <!-- Account Selector -->
                    <div class="flex items-center justify-between gap-2">
                        <label class="text-xs font-semibold text-slate-500 dark:text-slate-400 shrink-0">Akun Sumber</label>
                        <select x-model="parsed.source_account_id" 
                                class="text-xs font-bold text-slate-800 dark:text-slate-200 bg-slate-50 dark:bg-slate-900 px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500 max-w-[190px]">
                            <template x-for="acc in metaAccounts" :key="acc.id">
                                <option :value="acc.id" x-text="acc.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Category Selector -->
                    <div class="flex items-center justify-between gap-2">
                        <label class="text-xs font-semibold text-slate-500 dark:text-slate-400 shrink-0">Kategori</label>
                        <select x-model="parsed.category_id" 
                                class="text-xs font-bold text-slate-800 dark:text-slate-200 bg-slate-50 dark:bg-slate-900 px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500 max-w-[190px]">
                            <template x-for="cat in metaCategories.filter(c => c.type === parsed.type)" :key="cat.id">
                                <option :value="cat.id" x-text="cat.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Description Input -->
                    <div class="flex items-center justify-between gap-2">
                        <label class="text-xs font-semibold text-slate-500 dark:text-slate-400 shrink-0">Deskripsi</label>
                        <input type="text" 
                               x-model="parsed.description" 
                               placeholder="Contoh: Kopi susu"
                               class="text-xs font-medium text-slate-800 dark:text-slate-200 bg-slate-50 dark:bg-slate-900 px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500 w-44 text-right">
                    </div>

                    <!-- Transaction Date Input -->
                    <div class="flex items-center justify-between gap-2">
                        <label class="text-xs font-semibold text-slate-500 dark:text-slate-400 shrink-0">Tanggal</label>
                        <input type="date" 
                               x-model="parsed.transaction_date" 
                               class="text-xs font-medium text-slate-800 dark:text-slate-200 bg-slate-50 dark:bg-slate-900 px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    </div>

                </div>

                <!-- Action Buttons -->
                <div class="space-y-2 pt-1">
                    <button type="button" 
                            @click="submitDirectly()"
                            :disabled="submitting"
                            class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-700 hover:to-indigo-700 text-white text-xs font-bold shadow-md shadow-sky-500/25 active:scale-98 transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                        <span x-show="!submitting">⚡ Simpan Sekarang (1-Tap)</span>
                        <span x-show="submitting">Menyimpan Transaksi...</span>
                    </button>

                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" 
                                @click="startListening()"
                                class="py-2.5 px-3 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold active:scale-95 transition-all text-center">
                            🔄 Bicara Ulang
                        </button>
                        <button type="button" 
                                @click="editInFullForm()"
                                class="py-2.5 px-3 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-sky-600 dark:text-sky-400 text-xs font-bold active:scale-95 transition-all text-center">
                            ✍️ Edit di Form
                        </button>
                    </div>
                </div>
            </div>

            <!-- STATE: SAVED SUCCESS -->
            <div x-show="step === 'saved'" class="text-center py-8 space-y-3">
                <div class="w-14 h-14 rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 mx-auto flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h4 class="text-base font-black text-slate-800 dark:text-slate-100">Transaksi Berhasil Dicatat!</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">Mengalihkan ke halaman aktivitas...</p>
            </div>

            <!-- STATE: ERROR & FALLBACK -->
            <div x-show="step === 'error'" class="space-y-4 py-2">
                <div class="p-3 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 rounded-2xl text-rose-700 dark:text-rose-300 text-xs font-medium">
                    <p x-text="errorMessage"></p>
                </div>

                <!-- Fallback manual text input (Allows testing parser even if mic is blocked) -->
                <div class="space-y-2">
                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Atau ketik perintah secara manual:</p>
                    <div class="flex gap-1.5">
                        <input type="text" 
                               x-model="manualQuery" 
                               @keydown.enter="if(manualQuery.trim()) sendForParsing(manualQuery.trim())"
                               placeholder="Contoh: Beli kopi 25rb pakai BCA"
                               class="flex-1 text-xs px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <button type="button" 
                                @click="if(manualQuery.trim()) sendForParsing(manualQuery.trim())"
                                class="px-3 py-2 rounded-xl bg-sky-600 text-white text-xs font-bold shadow-xs active:scale-95">
                            Proses
                        </button>
                    </div>
                </div>

                <button type="button" 
                        @click="startListening()"
                        class="w-full py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 active:scale-95">
                    Coba Mikrofon Lagi
                </button>
            </div>

            <!-- Example Tips Chips (Shown in prompt or error steps) -->
            <div x-show="step === 'prompt' || step === 'error'" class="pt-2 border-t border-slate-100 dark:border-slate-800">
                <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Contoh Ucapan yang Dipahami:</p>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" 
                            @click="useExample('Beli kopi susu 25rb pakai BCA')"
                            class="text-[11px] px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-sky-950 hover:text-sky-600 active:scale-95 transition-colors">
                        ☕ "Beli kopi susu 25rb pakai BCA"
                    </button>
                    <button type="button" 
                            @click="useExample('Makan siang 35.000 tunai')"
                            class="text-[11px] px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-sky-950 hover:text-sky-600 active:scale-95 transition-colors">
                        🍛 "Makan siang 35.000 tunai"
                    </button>
                    <button type="button" 
                            @click="useExample('Bensin motor 50rb kemarin')"
                            class="text-[11px] px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-sky-950 hover:text-sky-600 active:scale-95 transition-colors">
                        ⛽ "Bensin motor 50rb kemarin"
                    </button>
                    <button type="button" 
                            @click="useExample('Gaji bulanan 8 juta masuk ke Mandiri')"
                            class="text-[11px] px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-sky-50 dark:hover:bg-sky-950 hover:text-sky-600 active:scale-95 transition-colors">
                        💼 "Gaji bulanan 8 juta masuk ke Mandiri"
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
