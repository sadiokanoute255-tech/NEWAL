@extends('layouts.espace')

@section('titre', 'Administration · Utilisateurs')

@section('page')
<h1 class="font-display text-3xl">Administration</h1>
@include('espace.admin._onglets')

<form method="GET" class="mt-6 flex flex-wrap gap-3">
    <input name="search" value="{{ request('search') }}" placeholder="Nom, e-mail ou téléphone" class="champ min-w-0 flex-1">
    <button class="bouton-clair">Rechercher</button>
</form>

@if ($errors->any())
    <div class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">
        @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
@endif

<div class="mt-6 space-y-3">
    @forelse ($users as $u)
        <div class="carte flex flex-wrap items-center justify-between gap-4 p-4 sm:p-5">
            <div class="min-w-0">
                <p class="flex flex-wrap items-center gap-2 font-semibold">
                    {{ trim(($u->first_name ?? '').' '.$u->name) }}
                    @if ($u->is_admin)
                        <span class="rounded-full bg-or/20 px-2.5 py-0.5 text-[11px] font-bold text-or-fonce">Admin</span>
                    @endif
                    @if ($u->is_suspended)
                        <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-[11px] font-bold text-red-900">Suspendu</span>
                    @endif
                </p>
                <p class="mt-0.5 truncate text-sm text-muted">
                    {{ collect([$u->email, $u->phone])->filter()->implode(' · ') }}
                </p>
                <p class="mt-0.5 text-xs text-muted">
                    {{ ucfirst($u->account_type) }}
                    @if ($u->organizations->isNotEmpty()) · {{ $u->organizations->pluck('name')->implode(', ') }} @endif
                    · inscrit le {{ $u->created_at->format('d/m/Y') }}
                </p>
            </div>

            @if ($u->id !== auth()->id())
                <form method="POST" action="{{ route('admin.utilisateurs.suspension', $u) }}"
                      onsubmit="return confirm('{{ $u->is_suspended ? 'Réactiver ce compte ?' : 'Suspendre ce compte ? La personne sera déconnectée.' }}')">
                    @csrf
                    @method('PUT')
                    <button class="bouton-clair min-h-10 px-4 text-sm {{ $u->is_suspended ? '' : 'text-red-700' }}">
                        {{ $u->is_suspended ? 'Réactiver' : 'Suspendre' }}
                    </button>
                </form>
            @else
                <span class="text-xs text-muted">Votre compte</span>
            @endif
        </div>
    @empty
        <div class="carte text-center text-muted">Aucun utilisateur trouvé.</div>
    @endforelse
</div>

@if ($users->hasPages())
    <div class="mt-6 flex items-center justify-between text-sm">
        @if ($users->previousPageUrl())
            <a href="{{ $users->previousPageUrl() }}" class="bouton-clair">← Précédent</a>
        @else <span></span> @endif
        <span class="text-muted">Page {{ $users->currentPage() }} / {{ $users->lastPage() }}</span>
        @if ($users->nextPageUrl())
            <a href="{{ $users->nextPageUrl() }}" class="bouton-clair">Suivant →</a>
        @else <span></span> @endif
    </div>
@endif
@endsection