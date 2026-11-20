@extends('layouts.espace')

@section('titre', 'Administration · Événements')

@section('page')
<h1 class="font-display text-3xl">Administration</h1>
@include('espace.admin._onglets')

@if ($errors->any())
    <div class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">
        @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
@endif

<form method="GET" class="mt-6 flex flex-wrap gap-3">
    <input name="search" value="{{ request('search') }}" placeholder="Rechercher un événement" class="champ min-w-0 flex-1">
    <button class="bouton-clair">Rechercher</button>
</form>

<div class="mt-6 space-y-3">
    @forelse ($events as $event)
        <div class="carte flex flex-wrap items-center justify-between gap-4 p-4 sm:p-5">
            <div class="min-w-0">
                <p class="truncate font-semibold">{{ $event->name }}</p>
                <p class="mt-0.5 text-sm text-muted">
                    {{ $event->organization?->name }} · {{ $event->guests_count }} invités · offre : {{ $event->plan?->name ?? 'par défaut' }}
                </p>
            </div>
            <form method="POST" action="{{ route('admin.evenements.offre', $event) }}" class="flex items-center gap-2">
                @csrf
                @method('PUT')
                <select name="plan_id" class="champ min-h-10 w-auto py-2" aria-label="Offre">
                    <option value="">Par défaut ({{ \App\Models\Event::DEFAULT_GUEST_LIMIT }})</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}" @selected($event->plan_id === $plan->id)>{{ $plan->name }} ({{ $plan->max_guests }})</option>
                    @endforeach
                </select>
                <button class="bouton min-h-10 px-4 text-sm">Appliquer</button>
            </form>
        </div>
    @empty
        <div class="carte text-center text-muted">Aucun événement.</div>
    @endforelse
</div>

@if ($events->hasPages())
    <div class="mt-6 flex items-center justify-between text-sm">
        @if ($events->previousPageUrl())
            <a href="{{ $events->previousPageUrl() }}" class="bouton-clair">← Précédent</a>
        @else <span></span> @endif
        <span class="text-muted">Page {{ $events->currentPage() }} / {{ $events->lastPage() }}</span>
        @if ($events->nextPageUrl())
            <a href="{{ $events->nextPageUrl() }}" class="bouton-clair">Suivant →</a>
        @else <span></span> @endif
    </div>
@endif
@endsection