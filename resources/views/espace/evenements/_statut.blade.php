@php
    $couleurs = [
        'brouillon' => 'bg-amber-100 text-amber-800',
        'publie' => 'bg-emerald-100 text-emerald-800',
        'archive' => 'bg-stone-200 text-stone-700',
    ];
@endphp
<span class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $couleurs[$event->status] ?? 'bg-gray-100 text-gray-700' }}">
    {{ \App\Support\EventOptions::STATUS[$event->status] ?? $event->status }}
</span>