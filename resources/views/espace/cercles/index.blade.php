@extends('layouts.espace')

@section('titre', 'Cercles · '.$event->name)

@section('page')
@php
    $couleurs = [
        '#B8432C' => 'Terracotta', '#E8A317' => 'Or', '#2F7D5B' => 'Vert',
        '#2B5BA8' => 'Bleu', '#7A3E8E' => 'Violet', '#5c5247' => 'Brun',
    ];
@endphp

<a href="{{ route('invites.index', $event) }}" class="text-sm font-semibold text-or-fonce hover:underline">← Invités</a>
<h1 class="mt-3 font-display text-3xl">Cercles d'invités</h1>
<p class="mt-1 text-muted">Regroupez vos invités : famille, amis, collègues, VIP, presse…</p>

@if ($errors->any())
    <div class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

@if ($canWrite)
    <form method="POST" action="{{ route('cercles.store', $event) }}" class="carte mt-6 flex flex-wrap items-end gap-4">
        @csrf
        <div class="min-w-0 flex-1">
            <label class="etiquette" for="nouveau-nom">Nouveau cercle</label>
            <input id="nouveau-nom" name="name" required maxlength="100" placeholder="Ex. Famille proche" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="nouveau-couleur">Couleur</label>
            <select id="nouveau-couleur" name="color" class="champ mt-1.5">
                @foreach ($couleurs as $hex => $nom)
                    <option value="{{ $hex }}">{{ $nom }}</option>
                @endforeach
            </select>
        </div>
        <button class="bouton">Créer</button>
    </form>
@endif

<div class="mt-6 space-y-3">
    @forelse ($groups as $g)
        <div class="carte flex flex-wrap items-center gap-4 p-4 sm:p-5">
            <span class="size-4 shrink-0 rounded-full" style="background: {{ $g->color ?: '#B8432C' }}"></span>

            @if ($canWrite)
                <form method="POST" action="{{ route('cercles.update', $g) }}" class="flex min-w-0 flex-1 flex-wrap items-center gap-3">
                    @csrf
                    @method('PUT')
                    <input name="name" value="{{ $g->name }}" required maxlength="100" class="champ min-w-0 flex-1" aria-label="Nom du cercle">
                    <select name="color" class="champ w-auto" aria-label="Couleur">
                        @foreach ($couleurs as $hex => $nom)
                            <option value="{{ $hex }}" @selected(strtolower($g->color ?? '') === strtolower($hex))>{{ $nom }}</option>
                        @endforeach
                    </select>
                    <button class="bouton-clair min-h-10 px-4 text-sm">Enregistrer</button>
                </form>
                <span class="text-sm text-muted">{{ $g->guests_count }} invités</span>
                <form method="POST" action="{{ route('cercles.destroy', $g) }}"
                      onsubmit="return confirm('Supprimer ce cercle ? Les invités ne sont pas supprimés.')">
                    @csrf
                    @method('DELETE')
                    <button class="bouton-clair min-h-10 px-4 text-sm text-red-700">Supprimer</button>
                </form>
            @else
                <p class="flex-1 font-semibold">{{ $g->name }}</p>
                <span class="text-sm text-muted">{{ $g->guests_count }} invités</span>
            @endif
        </div>
    @empty
        <div class="carte text-center text-muted">Aucun cercle pour le moment.</div>
    @endforelse
</div>
@endsection