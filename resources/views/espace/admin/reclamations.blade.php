@extends('layouts.espace')

@section('titre', 'Administration · Réclamations')

@section('page')
@php
    $badges = [
        'nouveau' => 'bg-amber-100 text-amber-900',
        'en_cours' => 'bg-sky-100 text-sky-900',
        'resolu' => 'bg-emerald-100 text-emerald-900',
    ];
    $filtres = ['' => 'Toutes'] + \App\Models\Complaint::STATUS;
@endphp

<h1 class="font-display text-3xl">Réclamations</h1>
<p class="mt-1 text-muted">Les messages envoyés par les organisateurs.</p>

<nav class="mt-5 flex flex-wrap gap-3" aria-label="Filtrer par statut">
    @foreach ($filtres as $cle => $libelle)
        <a href="{{ route('admin.reclamations', $cle ? ['status' => $cle] : []) }}"
           class="{{ ($statut ?? '') === (string) $cle ? 'bouton' : 'bouton-clair' }} min-h-10 px-5 text-sm">
            {{ $libelle }}
            @if ($cle) <span class="ml-1 opacity-70">({{ $comptes[$cle] ?? 0 }})</span> @endif
        </a>
    @endforeach
</nav>

<div class="mt-6 space-y-4">
    @forelse ($complaints as $c)
        <div class="carte p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $c->subject }}</p>
                    <p class="mt-0.5 text-xs text-muted">
                        {{ trim(($c->user?->first_name ?? '').' '.($c->user?->name ?? 'Compte supprimé')) }}
                        @if ($c->user) · {{ $c->user->email }} @endif
                        · {{ $c->created_at->format('d/m/Y H:i') }}
                        @if ($c->event) · Événement : {{ $c->event->name }} @endif
                    </p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badges[$c->status] ?? 'bg-stone-200 text-stone-800' }}">
                    {{ \App\Models\Complaint::STATUS[$c->status] ?? $c->status }}
                </span>
            </div>

            <p class="mt-3 whitespace-pre-line text-sm">{{ $c->message }}</p>

            <form method="POST" action="{{ route('admin.reclamations.update', $c) }}" class="mt-4 grid gap-3 border-t border-sand pt-4 sm:grid-cols-[12rem_1fr_auto] sm:items-end">
                @csrf
                @method('PUT')
                <div>
                    <label class="etiquette" for="statut-{{ $c->id }}">Statut</label>
                    <select id="statut-{{ $c->id }}" name="status" class="champ mt-1.5">
                        @foreach (\App\Models\Complaint::STATUS as $cle => $libelle)
                            <option value="{{ $cle }}" @selected($c->status === $cle)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="etiquette" for="reponse-{{ $c->id }}">Réponse à l'organisateur</label>
                    <textarea id="reponse-{{ $c->id }}" name="response" rows="2" maxlength="3000" class="champ mt-1.5">{{ $c->response }}</textarea>
                </div>
                <button class="bouton">Enregistrer</button>
            </form>
        </div>
    @empty
        <div class="carte text-center text-muted">Aucune réclamation.</div>
    @endforelse
</div>

@if ($complaints->hasPages())
    <div class="mt-6 flex items-center justify-between text-sm">
        @if ($complaints->previousPageUrl())
            <a href="{{ $complaints->previousPageUrl() }}" class="bouton-clair">← Précédent</a>
        @else <span></span> @endif
        <span class="text-muted">Page {{ $complaints->currentPage() }} / {{ $complaints->lastPage() }}</span>
        @if ($complaints->nextPageUrl())
            <a href="{{ $complaints->nextPageUrl() }}" class="bouton-clair">Suivant →</a>
        @else <span></span> @endif
    </div>
@endif
@endsection