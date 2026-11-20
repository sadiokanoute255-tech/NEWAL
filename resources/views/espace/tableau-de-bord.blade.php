@extends('layouts.espace')

@section('titre', 'Tableau de bord · Ñewal')

@section('page')
@php
    $prenom = auth()->user()->first_name ?: auth()->user()->name;
    $cartes = [
        ['calendar', $stats['events'], 'Événements créés', $stats['upcoming'].' à venir'],
        ['users', $stats['guests'], 'Invités', null],
        ['check', $stats['confirmed'], 'Confirmations', $stats['confirmed_persons'].' personnes attendues'],
        ['user-check', $stats['present'], 'Présents', 'personnes entrées'],
    ];
@endphp

{{-- Bandeau de bienvenue --}}
<section class="relative overflow-hidden rounded-3xl bg-[#2a170d] bg-cover bg-center px-6 py-9 text-cream sm:px-10 sm:py-11"
         style="background-image: url('{{ asset('images/accueil.jpg') }}');">
    <div class="absolute inset-0 bg-linear-to-r from-[#1d0f08]/95 via-[#1d0f08]/75 to-[#1d0f08]/25" aria-hidden="true"></div>
    <div class="relative">
        <h1 class="font-display text-3xl sm:text-4xl">Bienvenue, {{ $prenom }} !</h1>
        <p class="mt-2 max-w-md text-cream/90">Créez, gérez et partagez vos événements avec Ñewal.</p>
        <a href="{{ route('evenements.create') }}" class="bouton mt-6">
            <x-icone nom="calendar" class="size-5" />
            Créer un événement
            <x-icone nom="arrow" class="size-4" />
        </a>
    </div>
</section>

{{-- Chiffres clés --}}
<section class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
    @foreach ($cartes as [$icone, $valeur, $libelle, $detail])
        <div class="flex items-center gap-4 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-sand sm:p-5">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#6b3a1a] text-or">
                <x-icone :nom="$icone" class="size-6" />
            </span>
            <div class="min-w-0">
                <p class="font-display text-2xl leading-none sm:text-3xl">{{ $valeur }}</p>
                <p class="mt-1 text-sm text-muted">{{ $libelle }}</p>
                @if ($detail)<p class="text-xs text-muted">{{ $detail }}</p>@endif
            </div>
        </div>
    @endforeach
</section>

<div class="mt-6 grid gap-6 lg:grid-cols-3">

    {{-- Mes événements --}}
    <section class="carte lg:col-span-2">
        <div class="flex items-center justify-between">
            <h2 class="flex items-center gap-2 font-display text-xl">
                <x-icone nom="calendar" class="size-5 text-[#6b3a1a]" />
                Mes événements
            </h2>
            <a href="{{ route('evenements.index') }}" class="text-sm font-semibold text-or-fonce hover:underline">Voir tous →</a>
        </div>

        @if ($upcoming->isEmpty())
            <div class="mt-6 rounded-2xl bg-cream p-8 text-center">
                <p class="text-muted">Aucun événement à venir pour le moment.</p>
                <a href="{{ route('evenements.create') }}" class="bouton mt-4">Créer mon premier événement</a>
            </div>
        @else
            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($upcoming as $event)
                    <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-sand">
                        <div class="relative h-32 bg-linear-to-br from-[#5a3b2a] to-[#1d140e] bg-cover bg-center"
                             @if ($event->cover_path) style="background-image: url('{{ asset('storage/'.$event->cover_path) }}');" @endif>
                            <span class="absolute bottom-2 left-2 rounded-full px-3 py-1 text-xs font-bold {{ $event->status === 'publie' ? 'bg-or text-[#2a170d]' : 'bg-stone-200 text-stone-800' }}">
                                {{ $event->status === 'publie' ? 'À venir' : 'Brouillon' }}
                            </span>
                        </div>
                        <div class="p-4">
                            <p class="truncate font-semibold">{{ $event->name }}</p>
                            <p class="mt-1 flex items-center gap-1.5 text-xs text-muted">
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
                                    {{ $event->guests_count }} invités
                                </span>
                                <a href="{{ route('evenements.show', $event) }}"
                                   class="inline-flex items-center gap-1.5 rounded-full bg-[#4a2a14] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#2a170d]">
                                    Gérer <x-icone nom="arrow" class="size-3.5" />
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Activité récente --}}
    <section class="carte">
        <h2 class="flex items-center gap-2 font-display text-xl">
            <x-icone nom="bell" class="size-5 text-[#6b3a1a]" />
            Activité récente
        </h2>

        @forelse ($activity as $a)
            <div class="mt-5 flex gap-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-cream text-[#6b3a1a]">
                    <x-icone :nom="$a['icone']" class="size-5" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold">{{ $a['titre'] }}</p>
                    <p class="truncate text-sm text-muted">{{ $a['detail'] }}</p>
                    <p class="text-xs text-muted">{{ $a['date']->locale('fr')->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <p class="mt-5 text-sm text-muted">
                Les confirmations de vos invités et les entrées scannées apparaîtront ici.
            </p>
        @endforelse
    </section>
</div>
@endsection