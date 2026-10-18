@props([
    'padding' => 'p-4 sm:p-5',
    'as' => 'div',
])

<{{ $as }} {{ $attributes->merge(['class' => "bg-white rounded-3xl border border-slate-100 shadow-[0_4px_20px_-4px_rgba(15,23,42,0.05)] transition-all {$padding}"]) }}>
    {{ $slot }}
</{{ $as }}>
