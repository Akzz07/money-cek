@props([
    'variant' => 'neutral', // success, danger, warning, info, neutral
    'size' => 'md',        // sm, md
])

@php
$baseClasses = 'inline-flex items-center font-semibold rounded-full select-none';

$sizeClasses = match($size) {
    'sm' => 'px-2 py-0.5 text-[10px]',
    default => 'px-2.5 py-1 text-xs',
};

$variantClasses = match($variant) {
    'success' => 'bg-emerald-50 text-emerald-700 border border-emerald-200/60',
    'danger' => 'bg-rose-50 text-rose-700 border border-rose-200/60',
    'warning' => 'bg-amber-50 text-amber-700 border border-amber-200/60',
    'info' => 'bg-sky-50 text-sky-700 border border-sky-200/60 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800',
    default => 'bg-slate-100 text-slate-700 border border-slate-200',
};

$classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
