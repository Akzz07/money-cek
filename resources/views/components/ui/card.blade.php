@props([
    'padding' => 'p-4 sm:p-5',
    'as' => 'div',
])

<{{ $as }} {{ $attributes->merge(['class' => "bg-white dark:bg-slate-800/90 text-slate-900 dark:text-slate-100 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-[0_4px_20px_-4px_rgba(15,23,42,0.05)] dark:shadow-[0_4px_20px_-4px_rgba(0,0,0,0.3)] transition-all {$padding}"]) }}>
    {{ $slot }}
</{{ $as }}>
