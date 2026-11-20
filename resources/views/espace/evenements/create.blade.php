@extends('layouts.espace')

@section('titre', 'Nouvel événement · Ñewal')

@section('page')
<a href="{{ route('evenements.index') }}" class="text-sm font-semibold text-or-fonce hover:underline">← Mes événements</a>
<h1 class="mt-3 font-display text-3xl">Nouvel événement</h1>

<form method="POST" action="{{ route('evenements.store') }}" class="mt-8 space-y-6" enctype="multipart/form-data">
    @csrf
    @include('espace.evenements._form')

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="bouton">Créer l'événement</button>
        <a href="{{ route('evenements.index') }}" class="bouton-clair">Annuler</a>
    </div>
</form>
@endsection