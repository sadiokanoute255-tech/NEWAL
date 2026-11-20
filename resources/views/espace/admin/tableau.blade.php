@extends('layouts.espace')

@section('titre', 'Administration · Tableau de bord')

@section('page')
@php
    $blocs = [
        ['Utilisateurs', $stats['users'], $stats['new_users'].' nouveaux (30 jours)'],
        ['Comptes suspendus', $stats['suspended'], null],
        ['Événements', $stats['events'], $stats['upcoming'].' à venir'],
        ['Invités', $stats['guests'], null],
        ['Invitations', $stats['invitations'], $stats['sent'].' envoyées'],
        ['Abonnements actifs', $stats['with_plan'], 'événements avec une offre'],
        ['Revenus', '—', 'Disponible avec le paiement en ligne'],
    ];
@endphp

<h1 class="font-display text-3xl">Administration</h1>
@include('espace.admin._onglets')

<div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
    @foreach ($blocs as [$libelle, $valeur, $detail])
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-sand">
            <p class="text-sm text-muted">{{ $libelle }}</p>
            <p class="mt-1 font-display text-3xl">{{ $valeur }}</p>
            @if ($detail)<p class="mt-1 text-xs text-muted">{{ $detail }}</p>@endif
        </div>
    @endforeach
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="carte">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-xl">Nouveaux comptes</h2>
            <a href="{{ route('admin.utilisateurs') }}" class="text-sm font-semibold text-or-fonce hover:underline">Voir tous →</a>
        </div>
        @forelse ($newUsers as $u)
            <div class="mt-4 flex items-center justify-between gap-3 text-sm">
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ trim(($u->first_name ?? '').' '.$u->name) }}</p>
                    <p class="truncate text-muted">{{ $u->email }}</p>
                </div>
                <span class="shrink-0 text-xs text-muted">{{ $u->created_at->locale('fr')->diffForHumans() }}</span>
            </div>
        @empty
            <p class="mt-4 text-sm text-muted">Aucun compte.</p>
        @endforelse
    </section>

    <section class="carte">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-xl">Derniers événements</h2>
            <a href="{{ route('admin.evenements') }}" class="text-sm font-semibold text-or-fonce hover:underline">Voir tous →</a>
        </div>
        @forelse ($lastEvents as $e)
            <div class="mt-4 flex items-center justify-between gap-3 text-sm">
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ $e->name }}</p>
                    <p class="truncate text-muted">{{ $e->organization?->name }} · {{ $e->guests_count }} invités</p>
                </div>
                <span class="shrink-0 text-xs text-muted">{{ $e->created_at->locale('fr')->diffForHumans() }}</span>
            </div>
        @empty
            <p class="mt-4 text-sm text-muted">Aucun événement.</p>
        @endforelse
    </section>
</div>
@endsection