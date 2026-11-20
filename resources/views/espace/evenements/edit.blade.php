@extends('layouts.espace')

@section('titre', 'Modifier · Ñewal')

@section('page')
<a href="{{ route('evenements.show', $event) }}" class="text-sm font-semibold text-or-fonce hover:underline">← {{ $event->name }}</a>
<h1 class="mt-3 font-display text-3xl">Modifier l'événement</h1>

<form method="POST" action="{{ route('evenements.update', $event) }}" class="mt-8 space-y-6" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('espace.evenements._form')

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="bouton">Enregistrer</button>
        <a href="{{ route('evenements.show', $event) }}" class="bouton-clair">Annuler</a>
    </div>
</form>
@endsection