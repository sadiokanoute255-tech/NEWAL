@extends('layouts.vitrine')

@section('titre', 'Ñewal · Invitez. Confirmez. Accueillez.')

@section('bouton-haut')
    <a href="{{ route('login') }}"
       class="inline-flex min-h-11 items-center gap-2 rounded-full border border-cream/80 px-5 font-semibold text-cream transition hover:bg-white/10">
        <x-icone nom="log-in" class="size-5" />
        Connexion
    </a>
@endsection

@section('texte')
    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-or">Invitez. Confirmez. Accueillez.</p>
    <div class="mt-3 h-px w-40 bg-or/70"></div>

    <h1 class="mt-6 font-display text-5xl leading-[1.05] sm:text-6xl lg:text-7xl">
        Recevez vos invités avec <span class="text-or">élégance.</span>
    </h1>

    <p class="mt-6 max-w-md text-lg leading-relaxed text-cream/90">
        Invitations personnelles, confirmations en direct et accueil par QR Code,
        pour un mariage comme pour une cérémonie officielle.
    </p>

    <div class="mt-10 hidden flex-wrap items-center gap-6 sm:flex">
        @foreach ([['calendar', 'Événements<br>inoubliables'], ['users', 'Une communauté<br>engagée'], ['shield', '100 % sécurisé']] as $i => [$icone, $libelle])
            @if ($i > 0)<span class="h-10 w-px bg-cream/25"></span>@endif
            <div class="flex items-center gap-3">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-full border border-or text-or">
                    <x-icone :nom="$icone" class="size-6" />
                </span>
                <span class="text-sm font-semibold leading-tight">{!! $libelle !!}</span>
            </div>
        @endforeach
    </div>
@endsection

@section('carte')
@php
    $types = [
        'particulier' => ['Particulier', 'Mariage, baptême, anniversaire'],
        'entreprise' => ['Entreprise', 'Séminaire, lancement, gala'],
        'organisation' => ['Organisation', 'Institution, ONG, association'],
        'agence' => ['Agence', 'Événementielle, plusieurs clients'],
    ];
    $typeActuel = old('account_type', 'particulier');
    $affiche = fn (string $pour) => in_array($typeActuel, explode(' ', $pour), true) ? '' : 'hidden';
    $champBase = 'champ mt-1.5';
@endphp

<h2 class="mt-3 text-center font-display text-3xl">Créer un compte</h2>
<p class="mt-1.5 text-center text-sm text-muted">Votre premier événement en quelques minutes.</p>

@if ($errors->any())
    <div class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ url('/inscription') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
    @csrf

    {{-- Type de compte : les champs de la suite s'adaptent --}}
    <fieldset>
        <legend class="etiquette">Vous êtes</legend>
        <div class="mt-1.5 grid grid-cols-2 gap-2">
            @foreach ($types as $cle => [$libelle, $aide])
                <label class="cursor-pointer">
                    <input type="radio" name="account_type" value="{{ $cle }}" class="peer sr-only" @checked($typeActuel === $cle)>
                    <span class="block rounded-xl border border-sand bg-white px-3 py-2.5 transition peer-checked:border-or peer-checked:bg-or/10 peer-checked:ring-2 peer-checked:ring-or/40 peer-focus-visible:ring-2 peer-focus-visible:ring-or">
                        <span class="block text-sm font-semibold">{{ $libelle }}</span>
                        <span class="block text-[11px] leading-tight text-muted">{{ $aide }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </fieldset>

    {{-- Entreprise, organisation, agence : nom --}}
    <div data-pour="entreprise organisation agence" class="space-y-4 {{ $affiche('entreprise organisation agence') }}">
        <div>
            <label for="organization_name" class="etiquette"><span id="lib-nom">Nom de l'entreprise</span></label>
            <input id="organization_name" name="organization_name" maxlength="255" data-requis
                   value="{{ old('organization_name') }}" class="{{ $champBase }}">
        </div>
    </div>

    {{-- Organisation : type --}}
    <div data-pour="organisation" class="{{ $affiche('organisation') }}">
        <label for="subtype" class="etiquette">Type d'organisation</label>
        <select id="subtype" name="subtype" data-requis class="{{ $champBase }}">
            <option value="">Choisir…</option>
            @foreach (\App\Http\Controllers\Web\AuthController::SOUS_TYPES as $cle => $libelle)
                <option value="{{ $cle }}" @selected(old('subtype') === $cle)>{{ $libelle }}</option>
            @endforeach
        </select>
    </div>

    {{-- Entreprise : secteur --}}
    <div data-pour="entreprise" class="{{ $affiche('entreprise') }}">
        <label for="sector" class="etiquette">Secteur d'activité <span class="font-normal text-muted">(facultatif)</span></label>
        <input id="sector" name="sector" maxlength="100" value="{{ old('sector') }}" class="{{ $champBase }}" placeholder="Banque, télécoms, commerce…">
    </div>

    {{-- Prénom et nom --}}
    <div class="grid grid-cols-2 gap-3">
        <x-champ nom="first_name" etiquette="Prénom" icone="user" placeholder="Votre prénom" :requis="true" autocomplete="given-name" />
        <x-champ nom="name" etiquette="Nom" icone="user" placeholder="Votre nom" :requis="true" autocomplete="family-name" />
    </div>

    {{-- Entreprise, organisation, agence : fonction --}}
    <div data-pour="entreprise organisation agence" class="{{ $affiche('entreprise organisation agence') }}">
        <label for="job_title" class="etiquette">Votre fonction <span class="font-normal text-muted">(facultatif)</span></label>
        <input id="job_title" name="job_title" maxlength="100" value="{{ old('job_title') }}" class="{{ $champBase }}" placeholder="Directeur, responsable communication…">
    </div>

    <x-champ nom="email" etiquette="Adresse e-mail" icone="mail" type="email" placeholder="exemple@domaine.com" :requis="true" autocomplete="email" />

    <div>
        <label for="phone" class="etiquette">Téléphone <span id="tel-fac" class="font-normal text-muted {{ $affiche('particulier') }}">(facultatif)</span></label>
        <div class="relative mt-1.5">
            <x-icone nom="phone" class="pointer-events-none absolute left-3.5 top-1/2 size-5 -translate-y-1/2 text-brand/70" />
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="+221 77 123 45 67" autocomplete="tel" class="champ pl-11">
        </div>
    </div>

    {{-- Entreprise, organisation, agence : identification, site web, logo --}}
    <div data-pour="entreprise organisation agence" class="space-y-4 {{ $affiche('entreprise organisation agence') }}">
        <div>
            <label for="legal_id" class="etiquette"><span id="lib-legal">NINEA ou RCCM</span> <span class="font-normal text-muted">(facultatif)</span></label>
            <input id="legal_id" name="legal_id" maxlength="60" value="{{ old('legal_id') }}" class="{{ $champBase }}">
        </div>
        <div>
            <label for="website" class="etiquette">Site web <span class="font-normal text-muted">(facultatif)</span></label>
            <input id="website" name="website" type="url" value="{{ old('website') }}" class="{{ $champBase }}" placeholder="https://">
        </div>
        <div>
            <label for="logo" class="etiquette">Logo <span class="font-normal text-muted">(facultatif, 2 Mo maximum)</span></label>
            <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp"
                   class="champ mt-1.5 file:mr-4 file:rounded-full file:border-0 file:bg-sand file:px-4 file:py-2 file:font-semibold file:text-brand">
        </div>
    </div>

    {{-- Mot de passe sécurisé, avec l'œil --}}
    <div class="space-y-3">
        <x-champ nom="password" etiquette="Mot de passe" icone="lock" type="password" placeholder="Mot de passe" :requis="true" autocomplete="new-password" />

        <ul id="regles-mdp" class="grid grid-cols-2 gap-x-3 gap-y-1 text-[11px] text-muted">
            <li data-regle="longueur"><span class="coche">○</span> 8 caractères minimum</li>
            <li data-regle="casse"><span class="coche">○</span> Majuscule et minuscule</li>
            <li data-regle="chiffre"><span class="coche">○</span> Un chiffre</li>
            <li data-regle="symbole"><span class="coche">○</span> Un symbole (! ? # @)</li>
        </ul>

        <x-champ nom="password_confirmation" etiquette="Confirmation" icone="lock" type="password" placeholder="Confirmer le mot de passe" :requis="true" autocomplete="new-password" />
        <p id="mdp-accord" class="hidden text-[11px] font-semibold"></p>
    </div>

    <button type="submit" class="bouton-or w-full">
        <x-icone nom="user-plus" class="size-5" />
        Créer mon compte
    </button>
</form>

<p class="mt-4 border-t border-sand pt-4 text-center text-sm text-muted">
    Déjà inscrit ?
    <a href="{{ route('login') }}" class="font-semibold text-or-fonce hover:underline">Se connecter</a>
</p>

<script>
    (function () {
        const blocs = document.querySelectorAll('[data-pour]');
        const libelles = {
            entreprise: { nom: "Nom de l'entreprise", legal: 'NINEA ou RCCM' },
            organisation: { nom: "Nom de l'organisation", legal: 'Numéro de récépissé ou NINEA' },
            agence: { nom: "Nom de l'agence", legal: 'NINEA ou RCCM' },
        };

        // Les champs qui ne concernent pas le type choisi sont masqués et désactivés
        function appliquer() {
            const choisi = document.querySelector('input[name="account_type"]:checked');
            const type = choisi ? choisi.value : 'particulier';

            blocs.forEach((bloc) => {
                const actif = bloc.dataset.pour.split(' ').includes(type);
                bloc.classList.toggle('hidden', !actif);
                bloc.querySelectorAll('input, select, textarea').forEach((el) => {
                    el.disabled = !actif;
                    if (el.hasAttribute('data-requis')) el.required = actif;
                });
            });

            const l = libelles[type];
            if (l) {
                document.getElementById('lib-nom').textContent = l.nom;
                document.getElementById('lib-legal').textContent = l.legal;
            }
            document.getElementById('tel-fac').classList.toggle('hidden', type !== 'particulier');
            document.getElementById('phone').required = type !== 'particulier';
        }

        document.querySelectorAll('input[name="account_type"]').forEach((r) => r.addEventListener('change', appliquer));
        appliquer();

        // Règles du mot de passe, vérifiées en direct
        const mdp = document.getElementById('password');
        const conf = document.getElementById('password_confirmation');
        const accord = document.getElementById('mdp-accord');
        const regles = {
            longueur: (v) => v.length >= 8,
            casse: (v) => /[a-zà-ÿ]/.test(v) && /[A-ZÀ-Ý]/.test(v),
            chiffre: (v) => /\d/.test(v),
            symbole: (v) => /[^A-Za-z0-9À-ÿ\s]/.test(v),
        };

        function verifier() {
            document.querySelectorAll('[data-regle]').forEach((li) => {
                const ok = regles[li.dataset.regle](mdp.value);
                li.classList.toggle('text-emerald-700', ok);
                li.classList.toggle('text-muted', !ok);
                li.querySelector('.coche').textContent = ok ? '✓' : '○';
            });

            if (conf.value === '') {
                accord.classList.add('hidden');
                return;
            }
            const memes = conf.value === mdp.value;
            accord.classList.remove('hidden');
            accord.textContent = memes ? 'Les mots de passe correspondent.' : 'Les mots de passe ne correspondent pas.';
            accord.classList.toggle('text-emerald-700', memes);
            accord.classList.toggle('text-red-700', !memes);
        }

        mdp.addEventListener('input', verifier);
        conf.addEventListener('input', verifier);
        verifier();
    })();
</script>
@endsection