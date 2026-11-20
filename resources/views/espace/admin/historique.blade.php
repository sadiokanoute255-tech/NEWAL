@extends('layouts.espace')

@section('titre', 'Administration · Historique')

@section('page')
@php
    $couleurs = [
        'creation' => 'bg-emerald-100 text-emerald-900',
        'modification' => 'bg-sky-100 text-sky-900',
        'suppression' => 'bg-red-100 text-red-900',
        'envoi' => 'bg-amber-100 text-amber-900',
        'scan' => 'bg-stone-200 text-stone-800',
        'statut' => 'bg-violet-100 text-violet-900',
        'paiement' => 'bg-emerald-200 text-emerald-950',
    ];
@endphp

<h1 class="font-display text-3xl">Historique des actions</h1>
<p class="mt-1 text-muted">Qui a fait quoi sur la plateforme, de la plus récente à la plus ancienne.</p>

<form method="GET" class="mt-6 flex flex-wrap gap-3">
    <input name="search" value="{{ request('search') }}" placeholder="Rechercher dans l'historique" class="champ min-w-0 flex-1">
    <select name="action" class="champ w-auto">
        <option value="">Toutes les actions</option>
        @foreach (\App\Models\ActivityLog::ACTIONS as $cle => $libelle)
            <option value="{{ $cle }}" @selected($action === $cle)>{{ $libelle }}</option>
        @endforeach
    </select>
    <button class="bouton-clair">Filtrer</button>
</form>

<div class="mt-6 space-y-3">
    @forelse ($logs as $l)
        <div class="carte flex flex-wrap items-center justify-between gap-3 p-4">
            <div class="min-w-0">
                <p class="text-sm font-semibold">{{ $l->description }}</p>
                <p class="mt-0.5 text-xs text-muted">
                    {{ $l->user ? trim(($l->user->first_name ?? '').' '.$l->user->name) : 'Compte supprimé' }}
                    @if ($l->event) · {{ $l->event->name }} @endif
                    · {{ $l->created_at->format('d/m/Y H:i') }}
                </p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $couleurs[$l->action] ?? 'bg-stone-200 text-stone-800' }}">
                {{ \App\Models\ActivityLog::ACTIONS[$l->action] ?? $l->action }}
            </span>
        </div>
    @empty
        <div class="carte text-center text-muted">Aucune action enregistrée pour le moment.</div>
    @endforelse
</div>

@if ($logs->hasPages())
    <div class="mt-6 flex items-center justify-between text-sm">
        @if ($logs->previousPageUrl())
            <a href="{{ $logs->previousPageUrl() }}" class="bouton-clair">← Précédent</a>
        @else <span></span> @endif
        <span class="text-muted">Page {{ $logs->currentPage() }} / {{ $logs->lastPage() }}</span>
        @if ($logs->nextPageUrl())
            <a href="{{ $logs->nextPageUrl() }}" class="bouton-clair">Suivant →</a>
        @else <span></span> @endif
    </div>
@endif
@endsection