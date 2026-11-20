@extends('layouts.espace')

@section('titre', 'Contrôle d\'accès · '.$event->name)

@section('page')
@if (auth()->user()->canReadOrg($event->organization_id))
    <a href="{{ route('evenements.show', $event) }}" class="text-sm font-semibold text-or-fonce hover:underline">← {{ $event->name }}</a>
@else
    <p class="text-sm font-semibold text-or-fonce">{{ $event->name }}</p>
@endif
<h1 class="mt-3 font-display text-3xl">Contrôle d'accès</h1>
<p class="mt-1 text-muted">{{ \App\Support\EventOptions::date($event) }}</p>

<div class="mt-6 grid grid-cols-3 gap-3 sm:gap-4">
    @foreach ([[$present, 'Personnes entrées'], [$expected, 'Personnes attendues'], [$arrived, 'Invitations arrivées']] as [$valeur, $libelle])
        <div class="rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-sand">
            <p class="font-display text-3xl">{{ $valeur }}</p>
            <p class="mt-1 text-xs text-muted sm:text-sm">{{ $libelle }}</p>
        </div>
    @endforeach
</div>

<section class="carte mt-6">
    <h2 class="font-display text-xl">Scanner une invitation</h2>

    <button id="btn-camera" type="button" class="bouton mt-4 w-full sm:w-auto">Scanner un QR Code</button>
    <div id="lecteur" class="mx-auto mt-4 hidden w-full max-w-sm overflow-hidden rounded-2xl"></div>

    <form id="recherche" class="mt-5 flex flex-wrap gap-3">
        <input id="q" type="text" autocomplete="off" placeholder="Nom, téléphone ou code (NWL-…)" class="champ min-w-0 flex-1" aria-label="Recherche manuelle">
        <button class="bouton-clair">Rechercher</button>
    </form>
</section>

<div id="resultat" class="mt-6" aria-live="polite"></div>

<section class="carte mt-6">
    <h2 class="font-display text-xl">Dernières entrées</h2>

    @forelse ($recent as $c)
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-sand pt-4 first:mt-3 first:border-0 first:pt-0">
            <div class="min-w-0 {{ $c->cancelled_at ? 'opacity-50' : '' }}">
                <p class="truncate font-semibold {{ $c->cancelled_at ? 'line-through' : '' }}">{{ $c->invitation?->guest?->display_name ?? 'Invité supprimé' }}</p>
                <p class="text-sm text-muted">
                    {{ $c->persons_entered }} {{ $c->persons_entered > 1 ? 'personnes' : 'personne' }}
                    · {{ $c->checked_in_at->copy()->timezone($tz)->format('H \h i') }}
                    @if ($c->cancelled_at) · annulée @if ($c->cancel_reason) ({{ $c->cancel_reason }}) @endif @endif
                </p>
            </div>

            @if ($canCancel && ! $c->cancelled_at)
                <form method="POST" action="{{ route('controle.annuler', $c) }}"
                      onsubmit="const r = prompt('Motif de l’annulation (facultatif)'); if (r === null) return false; this.cancel_reason.value = r;">
                    @csrf
                    <input type="hidden" name="cancel_reason" value="">
                    <button class="bouton-clair min-h-10 px-4 text-sm text-red-700">Annuler</button>
                </form>
            @endif
        </div>
    @empty
        <p class="mt-3 text-sm text-muted">Aucune entrée enregistrée pour le moment.</p>
    @endforelse
</section>

@php
    $config = [
        'recherche' => route('controle.recherche', $event),
        'entree' => route('controle.entree', $event),
        'csrf' => csrf_token(),
    ];
@endphp
<script>
    window.CONTROLE = @json($config);
</script>
@vite('resources/js/scanner.js')
@endsection