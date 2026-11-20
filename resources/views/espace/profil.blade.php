@extends('layouts.espace')

@section('titre', 'Mon profil · Ñewal')

@section('page')
<h1 class="font-display text-3xl">Mon profil</h1>
<p class="mt-1 text-muted">Type de compte : {{ ucfirst($user->account_type) }}</p>

@if ($errors->any())
    <div class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">
        @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('profil.update') }}" class="carte mt-6 space-y-5">
    @csrf
    @method('PUT')
    <h2 class="font-display text-xl">Mes informations</h2>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="etiquette" for="first_name">Prénom</label>
            <input id="first_name" name="first_name" required value="{{ old('first_name', $user->first_name) }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="name">Nom</label>
            <input id="name" name="name" required value="{{ old('name', $user->name) }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="email">Adresse e-mail</label>
            <input id="email" type="email" name="email" required value="{{ old('email', $user->email) }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="phone">Téléphone</label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" class="champ mt-1.5">
        </div>
    </div>

    <button class="bouton">Enregistrer</button>
</form>

<form method="POST" action="{{ route('profil.password') }}" class="carte mt-6 space-y-5">
    @csrf
    @method('PUT')
    <h2 class="font-display text-xl">Changer le mot de passe</h2>

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label class="etiquette" for="current_password">Mot de passe actuel</label>
            <input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="password">Nouveau mot de passe</label>
            <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="password_confirmation">Confirmation</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="champ mt-1.5">
        </div>
    </div>
    <p class="-mt-2 text-xs text-muted">8 caractères minimum, avec une majuscule, une minuscule, un chiffre et un symbole.</p>

    <button class="bouton">Modifier le mot de passe</button>
</form>
@endsection