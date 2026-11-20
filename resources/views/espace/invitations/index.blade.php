@extends('layouts.espace')

@section('titre', 'Invitations · '.$event->name)

@section('page')
@php
    $statuts = [
        'creee' => ['Créée', 'bg-stone-200 text-stone-800'],
        'envoyee' => ['Envoyée', 'bg-sky-100 text-sky-900'],
        'distribuee' => ['Distribuée', 'bg-sky-100 text-sky-900'],
        'ouverte' => ['Ouverte', 'bg-amber-100 text-amber-900'],
        'confirmee' => ['Confirmée', 'bg-emerald-100 text-emerald-900'],
        'refusee' => ['Déclinée', 'bg-red-100 text-red-900'],
        'sans_reponse' => ['Sans réponse', 'bg-stone-200 text-stone-800'],
        'arrivee' => ['Arrivé', 'bg-emerald-200 text-emerald-950'],
    ];
@endphp

<a href="{{ route('evenements.show', $event) }}" class="text-sm font-semibold text-or-fonce hover:underline">← {{ $event->name }}</a>

<div class="mt-3 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="font-display text-3xl">Invitations</h1>
        <p class="mt-1 text-muted">{{ $total }} générée(s) · {{ $sent }} envoyée(s)</p>
    </div>
    @if ($canWrite && $missing > 0)
        <form method="POST" action="{{ route('invitations.generate', $event) }}">
            @csrf
            <button class="bouton">Générer {{ $missing }} invitation{{ $missing > 1 ? 's' : '' }}</button>
        </form>
    @endif
</div>

@if ($event->status === 'brouillon')
    <div class="mt-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-900">
        Cet événement est encore en brouillon. Pensez à le publier avant d'envoyer les invitations.
    </div>
@endif

<form method="GET" class="mt-6 flex flex-wrap gap-3">
    <input name="search" value="{{ request('search') }}" placeholder="Rechercher un invité" class="champ min-w-0 flex-1">
    <button class="bouton-clair">Rechercher</button>
</form>

<div class="mt-6 space-y-3">
    @forelse ($guests as $guest)
        @php
            $inv = $guest->invitation;
            $statut = $inv ? ($statuts[$inv->status] ?? [$inv->status, 'bg-stone-200 text-stone-800']) : null;
        @endphp
        <div class="carte flex flex-wrap items-center justify-between gap-4 p-4 sm:p-5">
            <div class="min-w-0">
                <p class="truncate font-semibold">{{ $guest->display_name }}</p>
                <p class="mt-0.5 text-sm text-muted">
                    {{ $guest->allowed_seats }} {{ $guest->allowed_seats > 1 ? 'places' : 'place' }}
                    @if ($guest->group) · {{ $guest->group->name }} @endif
                    @if ($guest->whatsapp || $guest->phone) · {{ $guest->whatsapp ?: $guest->phone }} @endif
                </p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    @if ($inv)
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statut[1] }}">{{ $statut[0] }}</span>
                        <span class="text-xs text-muted">Code {{ $inv->code }}</span>
                        @if ($inv->open_count > 0)<span class="text-xs text-muted">ouverte {{ $inv->open_count }} fois</span>@endif
                    @else
                        <span class="text-xs text-muted">Invitation pas encore générée</span>
                    @endif
                </div>
            </div>

            @if ($inv && $canWrite)
                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('invitations.whatsapp', $inv) }}" target="_blank">
                        @csrf
                        <button class="bouton min-h-10 px-4 text-sm">WhatsApp</button>
                    </form>
                    <button type="button" data-copier="{{ $inv->public_url }}" class="bouton-clair min-h-10 px-4 text-sm">Copier le lien</button>
                </div>
            @endif
        </div>
    @empty
        <div class="carte text-center">
            <p class="text-muted">Aucun invité pour cet événement.</p>
            <a href="{{ route('invites.create', $event) }}" class="bouton mt-4">Ajouter un invité</a>
        </div>
    @endforelse
</div>

@if ($guests->hasPages())
    <div class="mt-6 flex items-center justify-between text-sm">
        @if ($guests->previousPageUrl())
            <a href="{{ $guests->previousPageUrl() }}" class="bouton-clair">← Précédent</a>
        @else <span></span> @endif
        <span class="text-muted">Page {{ $guests->currentPage() }} / {{ $guests->lastPage() }}</span>
        @if ($guests->nextPageUrl())
            <a href="{{ $guests->nextPageUrl() }}" class="bouton-clair">Suivant →</a>
        @else <span></span> @endif
    </div>
@endif

<script>
    document.querySelectorAll('[data-copier]').forEach((b) => {
        b.addEventListener('click', async () => {
            const texte = b.textContent;
            try {
                await navigator.clipboard.writeText(b.dataset.copier);
                b.textContent = 'Lien copié';
                setTimeout(() => (b.textContent = texte), 1500);
            } catch (e) {
                window.prompt('Copiez ce lien :', b.dataset.copier);
            }
        });
    });
</script>
@endsection