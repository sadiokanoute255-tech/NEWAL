@extends('layouts.espace')

@section('titre', 'Notifications · Ñewal')

@section('page')
<h1 class="font-display text-3xl">Notifications</h1>
<p class="mt-1 text-muted">Les alertes de vos événements et les dernières réponses de vos invités.</p>

@if ($alertes->isNotEmpty())
    <h2 class="mt-8 font-display text-xl">À surveiller</h2>
    <div class="mt-3 space-y-3">
        @foreach ($alertes as $a)
            <a href="{{ $a['lien'] }}" class="flex items-center gap-4 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200 transition hover:ring-amber-400">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-900">
                    <x-icone :nom="$a['icone']" class="size-5" />
                </span>
                <div class="min-w-0">
                    <p class="font-semibold text-amber-950">{{ $a['titre'] }}</p>
                    <p class="text-sm text-amber-900">{{ $a['detail'] }}</p>
                </div>
            </a>
        @endforeach
    </div>
@endif

<h2 class="mt-8 font-display text-xl">Activité récente</h2>
<div class="mt-3 space-y-3">
    @forelse ($activite as $a)
        <a href="{{ $a['lien'] }}" class="carte flex items-center gap-4 p-4 transition hover:ring-or/60">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-cream text-[#6b3a1a]">
                <x-icone :nom="$a['icone']" class="size-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-semibold">{{ $a['titre'] }}</p>
                <p class="truncate text-sm text-muted">{{ $a['detail'] }}</p>
            </div>
            <span class="shrink-0 text-xs text-muted">{{ $a['date']->locale('fr')->diffForHumans() }}</span>
        </a>
    @empty
        <div class="carte text-center text-muted">
            Aucune réponse pour le moment. Les confirmations et refus de vos invités apparaîtront ici.
        </div>
    @endforelse
</div>
@endsection