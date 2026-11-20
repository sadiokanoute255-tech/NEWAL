@extends('layouts.espace')

@section('titre', 'Nouvel invité · Ñewal')

@section('page')
<a href="{{ route('invites.index', $event) }}" class="text-sm font-semibold text-or-fonce hover:underline">← Invités</a>
<h1 class="mt-3 font-display text-3xl">Ajouter un invité</h1>

<form method="POST" action="{{ route('invites.store', $event) }}" class="mt-8 space-y-6">
    @csrf
    @include('espace.invites._form')

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="bouton">Ajouter l'invité</button>
        <a href="{{ route('invites.index', $event) }}" class="bouton-clair">Annuler</a>
    </div>
</form>
@endsection