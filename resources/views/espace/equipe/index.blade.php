@extends('layouts.espace')

@section('titre', 'Équipe · Ñewal')

@section('page')
@php
    $couleurs = [
        'proprietaire' => 'bg-or/20 text-or-fonce',
        'gestionnaire' => 'bg-sky-100 text-sky-900',
        'communication' => 'bg-violet-100 text-violet-900',
        'accueil' => 'bg-emerald-100 text-emerald-900',
        'lecture_seule' => 'bg-stone-200 text-stone-800',
    ];
@endphp

<h1 class="font-display text-3xl">Équipe</h1>
<p class="mt-1 text-muted">Donnez un accès à vos collaborateurs et à vos agents d'accueil, avec les droits qui correspondent à leur rôle et les événements qui les concernent.</p>

@if ($errors->any())
    <div class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">
        @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('equipe.store') }}" class="carte mt-6 space-y-5">
    @csrf
    <h2 class="font-display text-xl">Ajouter un accès</h2>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="etiquette" for="first_name">Prénom</label>
            <input id="first_name" name="first_name" required value="{{ old('first_name') }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="name">Nom</label>
            <input id="name" name="name" required value="{{ old('name') }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="email">Adresse e-mail <span class="font-normal text-muted">(sert à se connecter)</span></label>
            <input id="email" type="email" name="email" required value="{{ old('email') }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="phone">Téléphone <span class="font-normal text-muted">(facultatif)</span></label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="title">Fonction <span class="font-normal text-muted">(facultatif)</span></label>
            <input id="title" name="title" maxlength="60" value="{{ old('title') }}" class="champ mt-1.5" placeholder="Assistant, protocole, prestataire…">
        </div>
        <div>
            <label class="etiquette" for="role">Rôle</label>
            <select id="role" name="role" class="champ mt-1.5">
                @foreach (\App\Support\Roles::ATTRIBUABLES as $r)
                    <option value="{{ $r }}" @selected(old('role', 'accueil') === $r)>{{ \App\Support\Roles::LIBELLES[$r] }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="etiquette" for="password">Mot de passe provisoire <span class="font-normal text-muted">(8 caractères minimum)</span></label>
            <input id="password" type="text" name="password" required minlength="8" autocomplete="off" class="champ mt-1.5">
        </div>
    </div>

    <fieldset class="rounded-xl bg-cream p-4">
        <legend class="etiquette px-1">Événements concernés</legend>
        <label class="mt-2 flex items-center gap-3 text-sm">
            <input type="radio" name="scope" value="tous" class="size-5" @checked(old('scope', 'tous') === 'tous')>
            Tous les événements, y compris les futurs
        </label>
        <label class="mt-2 flex items-center gap-3 text-sm">
            <input type="radio" name="scope" value="certains" class="size-5" @checked(old('scope') === 'certains')>
            Seulement les événements cochés ci-dessous
        </label>

        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            @forelse ($events as $e)
                <label class="flex items-center gap-3 rounded-lg bg-white px-3 py-2 text-sm ring-1 ring-sand">
                    <input type="checkbox" name="events[]" value="{{ $e->id }}" class="size-5 rounded border-sand" @checked(in_array($e->id, (array) old('events', [])))>
                    <span class="truncate">{{ $e->name }}</span>
                </label>
            @empty
                <p class="text-sm text-muted">Aucun événement pour le moment.</p>
            @endforelse
        </div>
    </fieldset>

    <ul class="space-y-1 rounded-xl bg-cream p-4 text-xs text-muted">
        @foreach (\App\Support\Roles::DESCRIPTIONS as $r => $texte)
            <li><strong class="text-brand">{{ \App\Support\Roles::LIBELLES[$r] }}</strong> : {{ $texte }}</li>
        @endforeach
    </ul>

    <button class="bouton">Créer l'accès</button>
</form>

<h2 class="mt-8 font-display text-xl">Membres de l'organisation</h2>
<div class="mt-3 space-y-3">
    @foreach ($membres as $m)
        @php
            $role = $m->pivot->role;
            $mesEvents = $acces[$m->id] ?? [];
            $tous = (bool) $m->pivot->all_events;
        @endphp
        <div class="carte p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-semibold">
                        {{ trim(($m->first_name ?? '').' '.$m->name) }}
                        @if ($m->id === auth()->id()) <span class="font-normal text-muted">(vous)</span> @endif
                    </p>
                    <p class="mt-0.5 truncate text-sm text-muted">
                        {{ $m->email }}@if ($m->pivot->title) · {{ $m->pivot->title }}@endif
                    </p>
                    @if ($role !== 'proprietaire')
                        <p class="mt-0.5 text-xs text-muted">
                            {{ $tous ? 'Tous les événements' : count($mesEvents).' événement(s) affecté(s)' }}
                        </p>
                    @endif
                </div>
                <span class="flex items-center gap-2">
                    @if ($m->is_suspended)
                        <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-900">Suspendu</span>
                    @endif
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $couleurs[$role] ?? 'bg-stone-200 text-stone-800' }}">
                        {{ \App\Support\Roles::LIBELLES[$role] ?? $role }}
                    </span>
                </span>
            </div>

            @if ($role !== 'proprietaire')
                <form method="POST" action="{{ route('equipe.update', $m) }}" class="mt-4 space-y-4 border-t border-sand pt-4">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="etiquette" for="role-{{ $m->id }}">Rôle</label>
                            <select id="role-{{ $m->id }}" name="role" class="champ mt-1.5">
                                @foreach (\App\Support\Roles::ATTRIBUABLES as $r)
                                    <option value="{{ $r }}" @selected($role === $r)>{{ \App\Support\Roles::LIBELLES[$r] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="etiquette" for="fonction-{{ $m->id }}">Fonction</label>
                            <input id="fonction-{{ $m->id }}" name="title" maxlength="60" value="{{ $m->pivot->title }}" class="champ mt-1.5">
                        </div>
                        <div>
                            <label class="etiquette" for="mdp-{{ $m->id }}">Nouveau mot de passe</label>
                            <input id="mdp-{{ $m->id }}" type="text" name="password" minlength="8" autocomplete="off" placeholder="Laisser vide pour ne pas changer" class="champ mt-1.5">
                        </div>
                    </div>

                    <fieldset class="rounded-xl bg-cream p-4">
                        <legend class="etiquette px-1">Événements concernés</legend>
                        <label class="mt-2 flex items-center gap-3 text-sm">
                            <input type="radio" name="scope" value="tous" class="size-5" @checked($tous)>
                            Tous les événements
                        </label>
                        <label class="mt-2 flex items-center gap-3 text-sm">
                            <input type="radio" name="scope" value="certains" class="size-5" @checked(! $tous)>
                            Seulement les événements cochés
                        </label>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($events as $e)
                                <label class="flex items-center gap-3 rounded-lg bg-white px-3 py-2 text-sm ring-1 ring-sand">
                                    <input type="checkbox" name="events[]" value="{{ $e->id }}" class="size-5 rounded border-sand" @checked(in_array($e->id, $mesEvents))>
                                    <span class="truncate">{{ $e->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <button class="bouton">Enregistrer</button>
                </form>

                <form method="POST" action="{{ route('equipe.destroy', $m) }}" class="mt-3"
                      onsubmit="return confirm('Retirer l’accès de cette personne ? Elle ne pourra plus se connecter.')">
                    @csrf
                    @method('DELETE')
                    <button class="text-sm font-semibold text-red-700 hover:underline">Retirer l'accès</button>
                </form>
            @endif
        </div>
    @endforeach
</div>

<section class="carte mt-8">
    <h2 class="font-display text-xl">Activité de l'équipe</h2>
    @forelse ($activite as $a)
        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm">
            <div class="min-w-0">
                <p class="font-semibold">{{ $a->description }}</p>
                <p class="text-xs text-muted">{{ $a->user ? trim(($a->user->first_name ?? '').' '.$a->user->name) : 'Compte supprimé' }}</p>
            </div>
            <span class="text-xs text-muted">{{ $a->created_at->format('d/m/Y H:i') }}</span>
        </div>
    @empty
        <p class="mt-3 text-sm text-muted">Aucune action pour le moment.</p>
    @endforelse
</section>
@endsection