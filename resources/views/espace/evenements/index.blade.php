@extends('layouts.espace')

@section('titre', 'Mes événements · Ñewal')

@section('page')
@php
    $badges = [
        'brouillon' => ['Brouillon', 'bg-stone-200 text-stone-800'],
        'publie' => ['Publié', 'bg-or text-[#2a170d]'],
        'archive' => ['Archivé', 'bg-stone-300 text-stone-800'],
    ];
@endphp

<div class="flex flex-wrap items-center justify-between gap-4">
    <h1 class="font-display text-3xl">Mes événements</h1>
    <a href="{{ route('evenements.create') }}" class="bouton">
        <x-icone nom="plus" class="size-5" /> Nouvel événement
    </a>
</div>

<form method="GET" class="mt-6 flex flex-wrap gap-3">
    <input name="search" value="{{ request('search') }}" placeholder="Rechercher un événement" class="champ min-w-0 flex-1">
    <select name="status" class="champ w-auto">
        <option value="">Actifs</option>
        @foreach (\App\Support\EventOptions::STATUS as $v => $l)
            <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
        @endforeach
    </select>
    <button class="bouton-clair">Filtrer</button>
</form>

<div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
    @forelse ($events as $event)
        @php $badge = $badges[$event->status] ?? [$event->status, 'bg-stone-200 text-stone-800']; @endphp
        <article class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-sand">
            <a href="{{ route('evenements.show', $event) }}"
               class="relative block h-32 bg-linear-to-br from-[#5a3b2a] to-[#1d140e] bg-cover bg-center"
               @if ($event->cover_path) style="background-image: url('{{ asset('storage/'.$event->cover_path) }}');" @endif
               aria-label="Ouvrir {{ $event->name }}">
                <span class="absolute bottom-2 left-2 rounded-full px-3 py-1 text-xs font-bold {{ $badge[1] }}">{{ $badge[0] }}</span>
            </a>

            <div class="flex flex-1 flex-col p-4">
                <a href="{{ route('evenements.show', $event) }}" class="truncate font-semibold hover:underline">{{ $event->name }}</a>
                <p class="mt-1 text-xs text-muted">{{ \App\Support\EventOptions::typeLabel($event->type) }}</p>
                <p class="mt-2 flex items-center gap-1.5 text-xs text-muted">
                    <x-icone nom="calendar" class="size-3.5 shrink-0" />
                    {{ \App\Support\EventOptions::date($event, 'D j M Y, H:i') }}
                </p>
                @if ($event->venue_name)
                    <p class="mt-1 flex items-center gap-1.5 text-xs text-muted">
                        <x-icone nom="pin" class="size-3.5 shrink-0" />
                        <span class="truncate">{{ $event->venue_name }}</span>
                    </p>
                @endif

                <div class="mt-4 flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-xs text-muted">
                        <x-icone nom="users" class="size-4" />
                        {{ $event->guests_count }} {{ $event->guests_count > 1 ? 'invités' : 'invité' }}
                    </span>
                    <a href="{{ route('evenements.show', $event) }}"
                       class="inline-flex items-center gap-1.5 rounded-full bg-[#4a2a14] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#2a170d]">
                        Gérer <x-icone nom="arrow" class="size-3.5" />
                    </a>
                </div>

                @if ($event->status !== 'archive')
                    <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 border-t border-sand pt-3 text-xs font-semibold text-or-fonce">
                        <a href="{{ route('invites.index', $event) }}" class="hover:underline">Invités</a>
                        <a href="{{ route('invitations.index', $event) }}" class="hover:underline">Invitations</a>
                        <a href="{{ route('controle.index', $event) }}" class="hover:underline">Contrôle d'accès</a>
                    </div>
                @endif
            </div>
        </article>
    @empty
        <div class="carte text-center sm:col-span-2 xl:col-span-3">
            <p class="text-muted">Aucun événement pour le moment.</p>
            <a href="{{ route('evenements.create') }}" class="bouton mt-4">Créer un événement</a>
        </div>
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