@extends('layouts.espace')

@section('titre', 'Importer des invités · '.$event->name)

@section('page')
@php $rapport = session('rapport'); @endphp

<a href="{{ route('invites.index', $event) }}" class="text-sm font-semibold text-or-fonce hover:underline">← Invités</a>
<h1 class="mt-3 font-display text-3xl">Importer des invités</h1>
<p class="mt-1 text-muted">{{ $total }} / {{ $limit }} invités actuellement.</p>

@if ($rapport)
    <section class="carte mt-6">
        <h2 class="font-display text-xl">Résultat de l'import</h2>

        <p class="mt-3 rounded-xl bg-emerald-50 p-4 font-semibold text-emerald-900">
            {{ $rapport['importes'] }} invité{{ $rapport['importes'] > 1 ? 's' : '' }} importé{{ $rapport['importes'] > 1 ? 's' : '' }}.
        </p>

        @if ($rapport['quota'])
            <p class="mt-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-900">
                Le quota de votre offre ({{ $rapport['quota'] }} invités) est atteint : les lignes suivantes n'ont pas été importées.
            </p>
        @endif

        @if ($rapport['nb_ignores'] > 0)
            <p class="mt-4 font-semibold">{{ $rapport['nb_ignores'] }} ligne{{ $rapport['nb_ignores'] > 1 ? 's' : '' }} ignorée{{ $rapport['nb_ignores'] > 1 ? 's' : '' }} :</p>
            <ul class="mt-2 divide-y divide-sand rounded-xl ring-1 ring-sand">
                @foreach ($rapport['ignores'] as [$ligne, $nom, $raison])
                    <li class="flex flex-wrap gap-x-3 px-4 py-3 text-sm">
                        <span class="font-semibold">Ligne {{ $ligne }}</span>
                        <span>{{ $nom }}</span>
                        <span class="text-red-700">{{ $raison }}</span>
                    </li>
                @endforeach
            </ul>
            @if ($rapport['nb_ignores'] > count($rapport['ignores']))
                <p class="mt-2 text-xs text-muted">Seules les 100 premières erreurs sont affichées.</p>
            @endif
            <p class="mt-3 text-sm text-muted">Corrigez ces lignes dans votre fichier, puis réimportez-les : les invités déjà importés seront reconnus par leur téléphone.</p>
        @endif

        <a href="{{ route('invites.index', $event) }}" class="bouton mt-5">Voir les invités</a>
    </section>
@endif

<section class="carte mt-6">
    <h2 class="font-display text-xl">1. Télécharger le modèle</h2>
    <p class="mt-2 text-sm text-muted">Un fichier Excel avec les bonnes colonnes et une ligne d'exemple à supprimer.</p>
    <a href="{{ route('invites.modele', $event) }}" class="bouton-clair mt-4">Télécharger le modèle Excel</a>
</section>

<section class="carte mt-6">
    <h2 class="font-display text-xl">2. Remplir le fichier</h2>
    <ul class="mt-3 space-y-1.5 text-sm text-muted">
        <li><strong class="text-brand">Nom</strong> est obligatoire (le nom de famille, ou le nom de la famille invitée).</li>
        <li><strong class="text-brand">Places</strong> : de 1 à 100 (1 si vide).</li>
        <li><strong class="text-brand">Cercle</strong> : Famille, Amis, VIP… Un cercle qui n'existe pas encore est créé automatiquement.</li>
        <li><strong class="text-brand">Téléphone</strong> : un même numéro ne peut apparaître qu'une fois dans l'événement.</li>
        <li>Gardez la première ligne (les en-têtes). Formats acceptés : Excel (.xlsx, .xls) et CSV, 2 000 lignes maximum.</li>
    </ul>
</section>

<section class="carte mt-6">
    <h2 class="font-display text-xl">3. Envoyer le fichier</h2>

    @if ($errors->any())
        <div class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-800">
            @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('invites.import.store', $event) }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-4">
        @csrf
        <div class="min-w-0 flex-1">
            <label for="fichier" class="etiquette">Fichier Excel ou CSV</label>
            <input id="fichier" type="file" name="fichier" required accept=".xlsx,.xls,.csv,.txt"
                   class="champ mt-1.5 file:mr-4 file:rounded-full file:border-0 file:bg-sand file:px-4 file:py-2 file:font-semibold file:text-brand">
        </div>
        <button class="bouton" onclick="this.form.requestSubmit(); this.disabled = true; this.textContent = 'Import en cours…';">Importer</button>
    </form>
</section>
@endsection