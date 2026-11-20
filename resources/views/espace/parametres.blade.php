@extends('layouts.espace')

@section('titre', 'Paramètres · Ñewal')

@section('page')
@php
    $pro = in_array($org->type, ['entreprise', 'organisation', 'agence'], true);
    $nomLibelle = match ($org->type) {
        'entreprise' => "Nom de l'entreprise",
        'organisation' => "Nom de l'organisation",
        'agence' => "Nom de l'agence",
        default => 'Nom affiché',
    };
    $legalLibelle = $org->type === 'organisation' ? 'Numéro de récépissé ou NINEA' : 'NINEA ou RCCM';
@endphp

<h1 class="font-display text-3xl">Paramètres</h1>
<p class="mt-1 text-muted">L'identité de votre organisation. Pour passer en mode nuit, utilisez le bouton en forme de lune en haut de la page : ce réglage est propre à chaque compte.</p>

@if ($errors->any())
    <div class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">
        @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('parametres.update') }}" enctype="multipart/form-data" class="carte mt-6 space-y-6">
    @csrf
    @method('PUT')

    <div>
        <label class="etiquette" for="name">{{ $nomLibelle }}</label>
        <input id="name" name="name" required maxlength="255" value="{{ old('name', $org->name) }}" class="champ mt-1.5">
        <p class="mt-1.5 text-xs text-muted">Type de compte : {{ ucfirst($org->type) }}</p>
    </div>

    @if ($org->type === 'organisation')
        <div>
            <label class="etiquette" for="subtype">Type d'organisation</label>
            <select id="subtype" name="subtype" required class="champ mt-1.5">
                <option value="">Choisir…</option>
                @foreach (\App\Http\Controllers\Web\AuthController::SOUS_TYPES as $cle => $libelle)
                    <option value="{{ $cle }}" @selected(old('subtype', $org->subtype) === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($org->type === 'entreprise')
        <div>
            <label class="etiquette" for="sector">Secteur d'activité</label>
            <input id="sector" name="sector" maxlength="100" value="{{ old('sector', $org->sector) }}" class="champ mt-1.5">
        </div>
    @endif

    @if ($pro)
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="etiquette" for="legal_id">{{ $legalLibelle }}</label>
                <input id="legal_id" name="legal_id" maxlength="60" value="{{ old('legal_id', $org->legal_id) }}" class="champ mt-1.5">
            </div>
            <div>
                <label class="etiquette" for="website">Site web</label>
                <input id="website" name="website" type="url" value="{{ old('website', $org->website) }}" class="champ mt-1.5" placeholder="https://">
            </div>
        </div>
    @endif

    <div>
        <p class="etiquette">Logo</p>

        @if ($org->logo_path)
            <div class="mt-3 flex items-center gap-4">
                <img src="{{ asset('storage/'.$org->logo_path) }}" alt="Logo.jpeg" width="80" height="80"
                     style="width:80px;height:80px"
                     class="size-20 shrink-0 rounded-full border-2 border-or bg-cream object-cover">
                <p class="text-sm text-muted">Voici comment votre logo apparaît dans le menu, à la place de vos initiales. Votre équipe (collaborateurs et agents d'accueil) le voit aussi.</p>
            </div>
            <label class="mt-3 flex items-center gap-2 text-sm">
                <input type="checkbox" name="remove_logo" value="1" class="size-5 rounded border-sand">
                Supprimer le logo actuel (les initiales reviennent)
            </label>
        @endif

        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp"
               class="champ mt-3 file:mr-4 file:rounded-full file:border-0 file:bg-sand file:px-4 file:py-2 file:font-semibold file:text-brand">
        <p class="mt-1.5 text-xs text-muted">
            JPG, PNG ou WebP, 2 Mo maximum. Une image carrée donne le meilleur résultat : elle est affichée en rond.
        </p>
    </div>

    <button class="bouton">Enregistrer</button>
</form>
@endsection