@props([
    'name' => 'amount',
    'id' => null,
    'value' => null,
    'label' => 'Nominal Transaksi',
    'placeholder' => '0',
    'required' => true,
    'showPresets' => true,
    'min' => '0.01',
])

@php
    $inputId = $id ?? $name;
    $initialValue = old($name, $value ?? '');
    // Ensure clean digits for initial numeric value if passed as formatted
    $cleanInitial = is_numeric($initialValue) ? (string) (float) $initialValue : preg_replace('/[^0-9]/', '', (string) $initialValue);
@endphp

<div x-data="{
    name: '{{ $name }}',
    rawValue: '{{ $cleanInitial }}',
    displayValue: '',
    scale: '',
    words: '',

    init() {
        if (this.rawValue) {
            this.updateFromRaw(this.rawValue);
        }
    },

    updateFromRaw(val) {
        let cleanStr = (val || '').toString().trim();
        // Strip sen decimals (,00 or .00) if user pastes formatted Indonesian IDR
        cleanStr = cleanStr.replace(/[,.]00$/, '');
        let clean = cleanStr.replace(/[^0-9]/g, '');
        // Remove leading zeros unless it's just '0'
        clean = clean.replace(/^0+(?=\d)/, '');
        this.rawValue = clean;
        this.displayValue = clean ? clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        this.computeScaleAndWords(clean);
    },

    handleInput(event) {
        let inputVal = event.target.value;
        this.updateFromRaw(inputVal);
        // keep input in sync
        event.target.value = this.displayValue;
    },

    addAmount(delta) {
        let current = parseInt(this.rawValue || '0', 10);
        let next = current + delta;
        if (next < 0) next = 0;
        this.updateFromRaw(next.toString());
    },

    appendZeros(zeros = '000') {
        if (!this.rawValue || this.rawValue === '0') {
            this.updateFromRaw('1' + zeros);
            return;
        }
        this.updateFromRaw(this.rawValue + zeros);
    },

    clearAmount() {
        this.updateFromRaw('');
    },

    computeScaleAndWords(numStr) {
        if (!numStr || numStr === '0') {
            this.scale = '';
            this.words = '';
            return;
        }

        let num = parseInt(numStr, 10);
        if (isNaN(num)) {
            this.scale = '';
            this.words = '';
            return;
        }

        // 1. Scale Label (Satuan, Puluhan, Ratusan, Ribuan, Puluhan Ribu, Ratusan Ribu, Jutaan, dst)
        if (num < 10) {
            this.scale = 'Satuan';
        } else if (num < 100) {
            this.scale = 'Puluhan';
        } else if (num < 1000) {
            this.scale = 'Ratusan';
        } else if (num < 10000) {
            this.scale = 'Ribuan';
        } else if (num < 100000) {
            this.scale = 'Puluhan Ribu';
        } else if (num < 1000000) {
            this.scale = 'Ratusan Ribu';
        } else if (num < 10000000) {
            this.scale = 'Jutaan';
        } else if (num < 100000000) {
            this.scale = 'Puluhan Juta';
        } else if (num < 1000000000) {
            this.scale = 'Ratusan Juta';
        } else if (num < 1000000000000) {
            this.scale = 'Miliaran';
        } else {
            this.scale = 'Triliunan';
        }

        // 2. Terbilang Verbal Reading in Indonesian
        this.words = this.terbilangIndo(num) + ' rupiah';
    },

    terbilangIndo(n) {
        n = Math.floor(Math.abs(Number(n)));
        if (n === 0) return 'Nol';

        const bil = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        if (n < 12) {
            return bil[n];
        } else if (n < 20) {
            return this.terbilangIndo(n - 10) + ' Belas';
        } else if (n < 100) {
            let sisa = n % 10;
            return this.terbilangIndo(Math.floor(n / 10)) + ' Puluh' + (sisa ? ' ' + this.terbilangIndo(sisa) : '');
        } else if (n < 200) {
            return 'Seratus' + (n - 100 ? ' ' + this.terbilangIndo(n - 100) : '');
        } else if (n < 1000) {
            let sisa = n % 100;
            return this.terbilangIndo(Math.floor(n / 100)) + ' Ratus' + (sisa ? ' ' + this.terbilangIndo(sisa) : '');
        } else if (n < 2000) {
            return 'Seribu' + (n - 1000 ? ' ' + this.terbilangIndo(n - 1000) : '');
        } else if (n < 1000000) {
            let sisa = n % 1000;
            return this.terbilangIndo(Math.floor(n / 1000)) + ' Ribu' + (sisa ? ' ' + this.terbilangIndo(sisa) : '');
        } else if (n < 1000000000) {
            let sisa = n % 1000000;
            return this.terbilangIndo(Math.floor(n / 1000000)) + ' Juta' + (sisa ? ' ' + this.terbilangIndo(sisa) : '');
        } else if (n < 1000000000000) {
            let sisa = n % 1000000000;
            return this.terbilangIndo(Math.floor(n / 1000000000)) + ' Miliar' + (sisa ? ' ' + this.terbilangIndo(sisa) : '');
        } else {
            let sisa = n % 1000000000000;
            return this.terbilangIndo(Math.floor(n / 1000000000000)) + ' Triliun' + (sisa ? ' ' + this.terbilangIndo(sisa) : '');
        }
    }
}" class="space-y-2">

    @if($label)
        <div class="flex items-center justify-between">
            <label for="{{ $inputId }}" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">
                {{ $label }}
            </label>
            <!-- Dynamic Scale Badge (Satuan, Puluhan, Ribuan, Jutaan, etc) -->
            <template x-if="scale">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide transition-all"
                      :class="{
                          'bg-slate-100 text-slate-600 border border-slate-200': scale === 'Satuan' || scale === 'Puluhan' || scale === 'Ratusan',
                          'bg-sky-50 text-sky-700 border border-sky-200': scale === 'Ribuan' || scale === 'Puluhan Ribu',
                          'bg-indigo-50 text-indigo-700 border border-indigo-200': scale === 'Ratusan Ribu',
                          'bg-emerald-50 text-emerald-700 border border-emerald-200': scale === 'Jutaan' || scale === 'Puluhan Juta' || scale === 'Ratusan Juta',
                          'bg-amber-50 text-amber-700 border border-amber-200': scale === 'Miliaran' || scale === 'Triliunan'
                      }"
                      x-text="scale">
                </span>
            </template>
        </div>
    @endif

    <!-- Main Input Box with Formatting -->
    <div class="relative rounded-2xl border @error($name) border-rose-500 @else border-slate-200/90 @enderror bg-white focus-within:ring-2 focus-within:ring-sky-500/20 focus-within:border-sky-500 transition-all shadow-xs">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400 select-none">Rp</span>
        
        <!-- Display Input with Thousand Separators -->
        <input type="text"
               inputmode="numeric"
               id="{{ $inputId }}"
               :value="displayValue"
               @input="handleInput($event)"
               placeholder="{{ $placeholder }}"
               {{ $required ? 'required' : '' }}
               autocomplete="off"
               class="w-full pl-12 pr-10 py-3.5 text-2xl font-black text-slate-800 bg-transparent focus:outline-none placeholder-slate-300 tracking-tight">

        <!-- Quick Clear Button (when value exists) -->
        <button type="button"
                x-show="rawValue.length > 0"
                x-cloak
                @click="clearAmount()"
                class="absolute right-3 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors"
                title="Hapus nominal">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <!-- Hidden Input for Form Submission with clean unformatted integer/decimal -->
        <input type="hidden"
               name="{{ $name }}"
               :value="rawValue">
    </div>

    <!-- Live Terbilang Reading (Indonesian Verbal Text) -->
    <div x-show="words" x-cloak class="min-h-[1.25rem] px-1 transition-all">
        <p class="text-xs font-medium text-slate-500 italic flex items-center gap-1.5">
            <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            <span x-text="words" class="capitalize"></span>
        </p>
    </div>

    <!-- Quick Increment Presets & Zero Shortcuts (+0, +00, +000, +Jt, +10rb, etc) -->
    @if($showPresets)
        <div class="pt-1 flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5 select-none">
            <!-- Satuan & Nol Appenders (Puluhan, Ratusan, Ribuan, Jutaan) -->
            <button type="button"
                    @click="appendZeros('0')"
                    class="px-2 py-1 text-xs font-bold rounded-lg bg-indigo-50/80 text-indigo-700 hover:bg-indigo-100 border border-indigo-200/70 active:scale-95 transition-all whitespace-nowrap"
                    title="Tambah satu nol (Puluhan)">
                +0
            </button>
            <button type="button"
                    @click="appendZeros('00')"
                    class="px-2 py-1 text-xs font-bold rounded-lg bg-indigo-50/80 text-indigo-700 hover:bg-indigo-100 border border-indigo-200/70 active:scale-95 transition-all whitespace-nowrap"
                    title="Tambah dua nol (Ratusan)">
                +00
            </button>
            <button type="button"
                    @click="appendZeros('000')"
                    class="px-2.5 py-1 text-xs font-extrabold rounded-lg bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 active:scale-95 transition-all whitespace-nowrap"
                    title="Tambah tiga nol (.000 - Ribuan)">
                +000 <span class="text-[10px] opacity-75 font-semibold">Ribu</span>
            </button>
            <button type="button"
                    @click="appendZeros('000000')"
                    class="px-2.5 py-1 text-xs font-extrabold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 active:scale-95 transition-all whitespace-nowrap"
                    title="Tambah enam nol (.000.000 - Jutaan)">
                +Jt <span class="text-[10px] opacity-75 font-semibold">(.000.000)</span>
            </button>

            <span class="inline-block w-px h-4 bg-slate-200 mx-0.5"></span>

            <!-- Direct Value Increments -->
            <button type="button"
                    @click="addAmount(10000)"
                    class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200/60 active:scale-95 transition-all whitespace-nowrap">
                +10rb
            </button>
            <button type="button"
                    @click="addAmount(50000)"
                    class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200/60 active:scale-95 transition-all whitespace-nowrap">
                +50rb
            </button>
            <button type="button"
                    @click="addAmount(100000)"
                    class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200/60 active:scale-95 transition-all whitespace-nowrap">
                +100rb
            </button>
            <button type="button"
                    @click="addAmount(500000)"
                    class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200/60 active:scale-95 transition-all whitespace-nowrap">
                +500rb
            </button>
            <button type="button"
                    @click="addAmount(1000000)"
                    class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200/60 active:scale-95 transition-all whitespace-nowrap">
                +1jt
            </button>
        </div>
    @endif

    @error($name)
        <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
    @enderror
</div>
