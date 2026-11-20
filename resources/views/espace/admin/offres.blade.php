@extends('layouts.espace')

@section('titre', 'Administration · Offres')

@section('page')
<h1 class="font-display text-3xl">Administration</h1>
@include('espace.admin._onglets')

@if ($errors->any())
    <div class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">
        @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
@endif

<h2 class="mt-8 font-display text-xl">Offres</h2>
<p class="mt-1 text-sm text-muted">Modifiez les capacités et les tarifs sans toucher au code. Un tarif de 0 s'affiche « Tarif à définir ».</p>

<div class="mt-4 space-y-4">
    @foreach ($plans as $plan)
        @include('espace.admin._plan', ['plan' => $plan])
    @endforeach
</div>

<h2 class="mt-10 font-display text-xl">Nouvelle offre</h2>
<div class="mt-4">
    @include('espace.admin._plan', ['plan' => null])
</div>
@endsection