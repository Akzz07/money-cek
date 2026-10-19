@props([
    'variant' => 'primary', // primary, secondary, outline, danger, ghost
    'size' => 'md',        // sm, md, lg
    'type' => 'button',
    'as' => 'button',
    'href' => null,
])

@php
$baseClasses = 'inline-flex items-center justify-center font-semibold rounded-2xl transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 active:scale-[0.98] disabled:opacity-60 disabled:pointer-events-none select-none cursor-pointer';

$sizeClasses = match($size) {
    'sm' => 'min-h-[38px] px-3.5 py-1.5 text-xs',
    'lg' => 'min-h-[52px] px-6 py-3.5 text-base',
    default => 'min-h-[46px] px-4 py-2.5 text-sm', // Meets touch target ≥ 44px
};

$variantClasses = match($variant) {
    'secondary' => 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-700 focus:ring-slate-400',
    'outline' => 'bg-white dark:bg-slate-800 border-2 border-sky-600 dark:border-sky-500 text-sky-600 dark:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-950/40 focus:ring-sky-500',
    'danger' => 'bg-rose-600 text-white hover:bg-rose-700 shadow-sm focus:ring-rose-500',
    'ghost' => 'bg-transparent text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100 focus:ring-slate-300',
    default => 'bg-sky-600 hover:bg-sky-700 text-white shadow-md shadow-sky-500/20 focus:ring-sky-500',
};

$classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
