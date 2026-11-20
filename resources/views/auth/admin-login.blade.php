@extends('layouts.vitrine')

@section('titre', 'Administration · Ñewal')

@section('bouton-haut')
    <a href="{{ route('login') }}"
       class="inline-flex min-h-11 items-center gap-2 rounded-full border border-cream/80 px-5 font-semibold text-cream transition hover:bg-white/10">
        Espace organisateur
    </a>
@endsection

@section('texte')
    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-or">Administration ATAABA</p>
    <div class="mt-3 h-px w-40 bg-or/70"></div>

    <h1 class="mt-6 font-display text-5xl leading-[1.05] sm:text-6xl lg:text-7xl">
        Espace <span class="text-or">réservé.</span>
    </h1>

    <p class="mt-6 max-w-md text-lg leading-relaxed text-cream/90">
        Gérez les utilisateurs, les événements et les offres de la plateforme Ñewal.
    </p>
@endsection

@section('carte')
    <h2 class="mt-3 text-center font-display text-3xl">Connexion administrateur</h2>
    <p class="mt-1.5 text-center text-sm text-muted">Accès réservé à l'équipe ATAABA.</p>

    <form method="POST" action="{{ url('/admin/connexion') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-champ nom="email" etiquette="Adresse e-mail" icone="mail" type="email" placeholder="admin@ataaba.com" :requis="true" autofocus autocomplete="email" />
            @error('email')
                <p class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <x-champ nom="password" etiquette="Mot de passe" icone="lock" type="password" placeholder="Votre mot de passe" :requis="true" autocomplete="current-password" />

        <label class="flex items-center gap-2 text-sm text-muted">
            <input type="checkbox" name="remember" class="size-4 rounded border-sand">
            Rester connecté
        </label>

        <button type="submit" class="bouton-or w-full">
            <x-icone nom="log-in" class="size-5" />
            Se connecter
        </button>
    </form>
@endsection