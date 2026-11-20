@extends('layouts.espace')

@section('titre', $event->name.' · Ñewal')

@section('page')
<a href="{{ route('evenements.index') }}" class="text-sm font-semibold text-or-fonce hover:underline">← Mes événements</a>

<section class="relative mt-3 overflow-hidden rounded-3xl bg-linear-to-br from-[#5a3b2a] to-[#1d140e] bg-cover bg-center px-6 py-10 text-cream sm:px-10 sm:py-14"
         @if ($event->cover_path) style="background-image: url('{{ asset('storage/'.$event->cover_path) }}');" @endif>
    <div class="absolute inset-0 bg-linear-to-r from-[#1d0f08]/90 via-[#1d0f08]/60 to-[#1d0f08]/20" aria-hidden="true"></div>
    <div class="relative flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#F3C9A6]">{{ \App\Support\EventOptions::typeLabel($event->type) }}</p>
            <h1 class="mt-2 font-display text-3xl sm:text-4xl">{{ $event->name }}</h1>
            <p class="mt-2 text-cream/90">
                {{ \App\Support\EventOptions::date($event) }}@if ($event->venue_name) · {{ $event->venue_name }}@endif
            </p>
        </div>
        @include('espace.evenements._statut')
    </div>
</section>

@if ($canWrite && $event->status !== 'archive')
    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route('evenements.edit', $event) }}" class="bouton-clair">Modifier</a>

        @if ($event->status === 'brouillon')
            <form method="POST" action="{{ route('evenements.publier', $event) }}">
                @csrf
                <button class="bouton">Publier</button>
            </form>
        @endif

        <form method="POST" action="{{ route('evenements.dupliquer', $event) }}">
            @csrf
            <button class="bouton-clair">Dupliquer</button>
        </form>

        <form method="POST" action="{{ route('evenements.destroy', $event) }}"
              onsubmit="return confirm('Archiver cet événement ?')">
            @csrf
            @method('DELETE')
            <button class="bouton-clair text-red-700">Archiver</button>
        </form>
    </div>
@endif

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="carte">
            <h2 class="font-display text-xl">Détails</h2>
            <dl class="mt-4 space-y-3 text-sm">
                @foreach ([
                    'Lieu' => $event->venue_name,
                    'Adresse' => $event->address,
                    'Dress code' => $event->dress_code,
                    'Visibilité' => \App\Support\EventOptions::VISIBILITY[$event->visibility] ?? $event->visibility,
                    'Contact' => collect([$event->contact_name, $event->contact_phone, $event->contact_email])->filter()->implode(' · '),
                ] as $label => $valeur)
                    @if ($valeur)
                        <div class="flex gap-4"><dt class="w-28 shrink-0 text-muted">{{ $label }}</dt><dd>{{ $valeur }}</dd></div>
                    @endif
                @endforeach
                @if ($event->map_url)
                    <div class="flex gap-4"><dt class="w-28 shrink-0 text-muted">Carte</dt>
                        <dd><a href="{{ $event->map_url }}" target="_blank" rel="noopener" class="font-semibold text-or-fonce underline">Ouvrir la carte</a></dd></div>
                @endif
            </dl>
            @if ($event->description)<p class="mt-5 whitespace-pre-line text-sm">{{ $event->description }}</p>@endif
            @if ($event->instructions)
                <p class="mt-5 text-sm"><span class="font-semibold">Instructions :</span> {{ $event->instructions }}</p>
            @endif
        </section>

        @if ($event->programItems->isNotEmpty())
            <section class="carte">
                <h2 class="font-display text-xl">Programme</h2>
                <ul class="mt-4 space-y-3">
                    @foreach ($event->programItems->sortBy('sort_order') as $item)
                        <li class="flex gap-4 text-sm">
                            <span class="w-14 shrink-0 font-semibold text-or-fonce">{{ substr($item->time, 0, 5) }}</span>
                            <span><span class="font-semibold">{{ $item->title }}</span>
                                @if ($item->description)<br><span class="text-muted">{{ $item->description }}</span>@endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    <aside class="space-y-6">
        <section class="carte">
            <h2 class="font-display text-xl">Invités</h2>
            <p class="mt-3 font-display text-3xl">{{ $guestsCount }} <span class="text-lg text-muted">/ {{ $guestLimit }}</span></p>
            <p class="mt-1 text-sm text-muted">
                Offre : {{ $event->plan?->name ?? 'par défaut' }} ·
                <a href="{{ route('offres.show', $event) }}" class="font-semibold text-or-fonce hover:underline">Changer</a>
            </p>

            <div class="mt-4 flex flex-col gap-2">
                <a href="{{ route('invites.index', $event) }}" class="bouton">Gérer les invités</a>
                @if ($canWrite)
                    <a href="{{ route('invites.import', $event) }}" class="bouton-clair">Importer (Excel ou CSV)</a>
                @endif
                <a href="{{ route('cercles.index', $event) }}" class="bouton-clair">Cercles d'invités</a>
            </div>
        </section>

        @if ($event->status !== 'archive')
            <section class="carte">
                <h2 class="font-display text-xl">Le jour J</h2>
                <div class="mt-4 flex flex-col gap-2">
                    <a href="{{ route('invitations.index', $event) }}" class="bouton-clair">Invitations</a>
                    <a href="{{ route('controle.index', $event) }}" class="bouton-clair">Contrôle d'accès</a>
                    <a href="{{ route('statistiques.index', $event) }}" class="bouton-clair">Statistiques</a>
                    @if ($canWrite)
                        <a href="{{ route('exports.index', $event) }}" class="bouton-clair">Rapports et exports</a>
                    @endif
                </div>
            </section>
        @endif
    </aside>
</div>
@endsection