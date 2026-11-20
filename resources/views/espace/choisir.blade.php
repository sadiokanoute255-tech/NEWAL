@extends('layouts.espace')

@section('titre', $titre.' · Ñewal')

@section('page')
<h1 class="font-display text-3xl">{{ $titre }}</h1>
<p class="mt-1 text-muted">{{ $aide }}</p>

<div class="mt-6 space-y-3">
    @forelse ($events as $event)
        @php $s = $stats[$event->id] ?? null; @endphp
        <a href="{{ route($section.'.index', $event) }}"
           class="carte flex items-center justify-between gap-4 p-4 transition hover:ring-or/60 sm:p-5">
            <div class="min-w-0">
                <p class="truncate font-semibold">{{ $event->name }}</p>
                <p class="mt-0.5 text-sm text-muted">{{ \App\Support\EventOptions::date($event, 'D j M Y, H:i') }}</p>
            </div>
            <div class="shrink-0 text-right text-sm text-muted">
                                @if ($section === 'invites')
                    {{ $event->guests_count }} {{ $event->guests_count > 1 ? 'invités' : 'invité' }}
                @elseif ($section === 'invitations')
                    {{ $s->total ?? 0 }} générée(s) · {{ $s->sent ?? 0 }} envoyée(s)
                @else
                    {{ $event->guests_count }} {{ $event->guests_count > 1 ? 'invités' : 'invité' }}
                @endif
                    {{ $s->total ?? 0 }} générée(s) · {{ $s->sent ?? 0 }} envoyée(s)
                
            </div>
        </a>
    @empty
        <div class="carte text-center">
            <p class="text-muted">Vous n'avez pas encore d'événement.</p>
            <a href="{{ route('evenements.create') }}" class="bouton mt-4">Créer un événement</a>
        </div>
    @endforelse
</div>
@endsection