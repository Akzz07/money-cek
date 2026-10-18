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
    'secondary' => 'bg-slate-100 text-slate-800 hover:bg-slate-200 focus:ring-slate-400',
    'outline' => 'bg-white border-2 border-blue-600 text-blue-600 hover:bg-blue-50 focus:ring-blue-500',
    'danger' => 'bg-rose-600 text-white hover:bg-rose-700 shadow-sm focus:ring-rose-500',
    'ghost' => 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:ring-slate-300',
    default => 'bg-blue-600 text-white hover:bg-blue-700 shadow-md shadow-blue-500/20 focus:ring-blue-500',
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
