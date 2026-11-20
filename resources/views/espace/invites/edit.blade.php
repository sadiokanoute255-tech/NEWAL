@extends('layouts.espace')

@section('titre', 'Modifier un invité · Ñewal')

@section('page')
<a href="{{ route('invites.index', $event) }}" class="text-sm font-semibold text-or-fonce hover:underline">← Invités</a>
<h1 class="mt-3 font-display text-3xl">Modifier {{ $guest->display_name }}</h1>

<form method="POST" action="{{ route('invites.update', $guest) }}" class="mt-8 space-y-6">
    @csrf
    @method('PUT')
    @include('espace.invites._form')

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="bouton">Enregistrer</button>
        <a href="{{ route('invites.index', $event) }}" class="bouton-clair">Annuler</a>
    </div>
</form>
@endsection