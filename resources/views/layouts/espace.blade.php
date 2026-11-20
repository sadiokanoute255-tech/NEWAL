@extends('layouts.app')

@php
    $user = auth()->user();
    $estAdmin = (bool) $user->is_admin;
    $theme = $user->theme === 'nuit' ? 'nuit' : 'clair';
    $initiales = mb_strtoupper(mb_substr($user->first_name ?: $user->name, 0, 1).mb_substr($user->name, 0, 1));

    // Rôle dans l'organisation (propriétaire par défaut pour les anciens comptes)
    $role = $estAdmin ? null : ($user->mainRole() ?? 'proprietaire');
    $libelleRole = $estAdmin
        ? 'Administrateur'
        : ($role === 'proprietaire' ? 'Organisateur' : (\App\Support\Roles::LIBELLES[$role] ?? 'Membre'));

    // Logo de l'organisation (celui de l'organisateur pour toute son équipe, agents d'accueil compris)
    $orgMenu = $estAdmin ? null : $user->organizations()->first();
    $logoUrl = $orgMenu?->logo_path ? asset('storage/'.$orgMenu->logo_path) : null;

    $reclNouvelles = 0;
    $nouvelles = 0;

    if ($estAdmin) {
        // Administration ATAABA (§37.2)
        $reclNouvelles = \App\Models\Complaint::where('status', 'nouveau')->count();
        $menu = [
            ['Tableau de bord', 'admin.tableau', route('admin.tableau'), 'home'],
            ['Utilisateurs', 'admin.utilisateurs*', route('admin.utilisateurs'), 'users'],
            ['Événements', 'admin.evenements*', route('admin.evenements'), 'calendar'],
            ['Offres', 'admin.offres*', route('admin.offres'), 'chart'],
            ['Réclamations', 'admin.reclamations*', route('admin.reclamations'), 'mail'],
            ['Historique', 'admin.historique', route('admin.historique'), 'shield'],
            ['Mon compte', 'profil.*', route('profil.show'), 'user'],
        ];
    } else {
        if ($role !== 'accueil') {
            $nouvelles = \App\Support\Notifs::nouvelles($user);
        }

        $items = [
            'tableau' => ['Tableau de bord', 'tableau-de-bord', route('tableau-de-bord'), 'home'],
            'evenements' => ['Mes événements', ['evenements.index', 'evenements.show', 'evenements.edit', 'offres.*', 'exports.*'], route('evenements.index'), 'calendar'],
            'creer' => ['Créer un événement', 'evenements.create', route('evenements.create'), 'plus'],
            'invites' => ['Mes invités', ['section.invites', 'invites.*', 'cercles.*'], route('section.invites'), 'users'],
            'invitations' => ['Invitations', ['section.invitations', 'invitations.*'], route('section.invitations'), 'mail'],
            'controle' => ['Contrôle d\'accès', ['section.controle', 'controle.*'], route('section.controle'), 'shield'],
            'stats' => ['Statistiques', ['section.statistiques', 'statistiques.*'], route('section.statistiques'), 'chart'],
            'equipe' => ['Équipe', 'equipe.*', route('equipe.index'), 'users'],
            'reclamations' => ['Réclamations', 'reclamations.*', route('reclamations.index'), 'mail'],
            'profil' => ['Mon profil', 'profil.*', route('profil.show'), 'user'],
            'parametres' => ['Paramètres', 'parametres.*', route('parametres.show'), 'settings'],
        ];

        // Ce que chaque rôle voit dans son menu (§40)
        $parRole = [
            'proprietaire' => ['tableau', 'evenements', 'creer', 'invites', 'invitations', 'controle', 'stats', 'equipe', 'reclamations', 'profil', 'parametres'],
            'gestionnaire' => ['tableau', 'evenements', 'creer', 'invites', 'invitations', 'controle', 'stats', 'reclamations', 'profil'],
            'communication' => ['tableau', 'evenements', 'invites', 'invitations', 'profil'],
            'accueil' => ['controle', 'profil'],
            'lecture_seule' => ['tableau', 'evenements', 'invites', 'invitations', 'stats', 'profil'],
        ];

        $menu = collect($parRole[$role] ?? $parRole['proprietaire'])->map(fn ($cle) => $items[$cle])->all();
    }

    $avecRecherche = ! $estAdmin && $role !== 'accueil';
@endphp

{{-- Apparence du compte connecté : la classe « dark » est posée par le serveur --}}
@section('html-class', $theme === 'nuit' ? 'dark' : '')

@section('contenu')
<div class="min-h-dvh lg:flex">

    <div id="voile" class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden"></div>

    {{-- Menu latéral --}}
    <aside id="menu" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-linear-to-b from-[#2a170d] to-[#1a0e07] text-white transition-transform lg:sticky lg:top-0 lg:h-dvh lg:translate-x-0">
        <div class="flex items-center justify-between px-6 pt-7">
            <a href="{{ route('accueil') }}" class="flex items-center gap-2">
                                <x-logo class="size-8" />
                <span class="font-display text-3xl">Ñewal</span>
            </a>
            <button id="fermer" type="button" class="lg:hidden" aria-label="Fermer le menu">
                <x-icone nom="x" class="size-6" />
            </button>
        </div>

        <div class="mx-4 mt-6 flex items-center gap-3 border-b border-white/10 pb-5">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo de {{ $orgMenu->name }}" width="48" height="48"
                     style="width:48px;height:48px"
                     class="size-12 shrink-0 rounded-full border-2 border-or bg-[#4a2a14] object-cover">
            @else
                <span class="flex size-12 shrink-0 items-center justify-center rounded-full border-2 border-or bg-[#4a2a14] font-semibold text-or">{{ $initiales }}</span>
            @endif
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold">{{ $user->first_name }} {{ $user->name }}</p>
                <span class="mt-1 inline-block rounded-full border border-or/60 bg-or/15 px-2.5 py-0.5 text-[11px] font-semibold text-or">{{ $libelleRole }}</span>
            </div>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            @foreach ($menu as [$libelle, $routes, $url, $icone])
                <a href="{{ $url }}"
                   class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition {{ request()->routeIs($routes) ? 'bg-or/20 text-or ring-1 ring-or/40' : 'text-white/85 hover:bg-white/10' }}">
                    <x-icone :nom="$icone" class="size-5" />
                    {{ $libelle }}
                    @if ($estAdmin && $libelle === 'Réclamations' && $reclNouvelles > 0)
                        <span class="ml-auto rounded-full bg-red-600 px-2 py-0.5 text-[11px] font-bold text-white">{{ $reclNouvelles }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('deconnexion') }}" class="border-t border-white/10 p-3">
            @csrf
            <button class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-white/85 transition hover:bg-white/10">
                <x-icone nom="logout" class="size-5" />
                Déconnexion
            </button>
        </form>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">

        {{-- Barre du haut --}}
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-sand bg-white px-4 py-3 sm:px-8">
            <button id="ouvrir" type="button" class="lg:hidden" aria-label="Ouvrir le menu">
                <x-icone nom="menu" class="size-6" />
            </button>

            @if ($estAdmin)
                <form method="GET" action="{{ route('admin.utilisateurs') }}" class="relative max-w-xl flex-1">
                    <x-icone nom="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-brand/70" />
                    <input name="search" type="search" placeholder="Rechercher un utilisateur..."
                           class="min-h-11 w-full rounded-full bg-sand/60 pl-11 pr-4 outline-none focus:ring-2 focus:ring-or/40">
                </form>
            @elseif ($avecRecherche)
                <form method="GET" action="{{ route('evenements.index') }}" class="relative max-w-xl flex-1">
                    <x-icone nom="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-brand/70" />
                    <input name="search" type="search" placeholder="Rechercher un événement..."
                           class="min-h-11 w-full rounded-full bg-sand/60 pl-11 pr-4 outline-none focus:ring-2 focus:ring-or/40">
                </form>
            @endif

            <div class="ml-auto flex items-center gap-3">
                {{-- Apparence : clair ou nuit, propre à ce compte --}}
                <button id="theme-btn" type="button" aria-label="Changer l'apparence (clair ou nuit)" title="Clair / Nuit"
                        style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border:0;background:transparent;cursor:pointer"
                        class="text-brand/70">
                    <svg id="ic-lune" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                    <svg id="ic-soleil" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                </button>

                @if ($avecRecherche)
                    <a href="{{ route('notifications.index') }}" class="relative block" aria-label="Notifications" title="Notifications">
                        <x-icone nom="bell" class="size-6 {{ request()->routeIs('notifications.*') ? 'text-or-fonce' : 'text-brand/70' }}" />
                        <span id="notif-badge"
                              style="position:absolute;top:-6px;right:-8px;min-width:18px;height:18px;padding:0 5px;border-radius:9999px;background:#DC2626;color:#fff;font-size:11px;font-weight:700;line-height:1;align-items:center;justify-content:center;display:{{ $nouvelles > 0 ? 'flex' : 'none' }}">{{ $nouvelles > 9 ? '9+' : $nouvelles }}</span>
                    </a>
                @endif
                <a href="{{ route('profil.show') }}" aria-label="Mon profil">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="" width="36" height="36"
                             style="width:36px;height:36px"
                             class="size-9 rounded-full border border-or object-cover">
                    @else
                        <span class="flex size-9 items-center justify-center rounded-full bg-[#4a2a14] text-sm font-semibold text-or">{{ $initiales }}</span>
                    @endif
                </a>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-8 sm:py-8">
            @if (session('succes'))
                <div class="mb-6 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('succes') }}</div>
            @endif

            @yield('page')
        </main>

        {{-- Pied de page --}}
        <footer class="bg-[#1a0e07] px-4 py-5 text-cream sm:px-8">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3">
                <span class="flex items-center gap-2">
                                        <x-logo class="size-6" />
                    <span class="font-display text-xl">Ñewal</span>
                </span>
                <span class="font-display text-sm italic text-cream/80">L'élégance au cœur de vos événements</span>
                <span class="text-xs text-cream/60">Ñewal by ATAABA</span>
            </div>
        </footer>
    </div>
</div>

<script>
    const menu = document.getElementById('menu');
    const voile = document.getElementById('voile');
    function basculer(ouvert) {
        menu.classList.toggle('-translate-x-full', !ouvert);
        voile.classList.toggle('hidden', !ouvert);
    }
    document.getElementById('ouvrir').addEventListener('click', () => basculer(true));
    document.getElementById('fermer').addEventListener('click', () => basculer(false));
    voile.addEventListener('click', () => basculer(false));

    // Apparence : enregistrée pour ce compte, pas pour le navigateur
    (function () {
        const racine = document.documentElement;
        const bouton = document.getElementById('theme-btn');
        const lune = document.getElementById('ic-lune');
        const soleil = document.getElementById('ic-soleil');

        function icones() {
            const nuit = racine.classList.contains('dark');
            lune.style.display = nuit ? 'none' : 'block';
            soleil.style.display = nuit ? 'block' : 'none';
        }
        icones();

        bouton.addEventListener('click', async () => {
            const nuit = !racine.classList.contains('dark');
            racine.classList.toggle('dark', nuit);
            icones();
            try {
                await fetch(@json(route('theme.update')), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: JSON.stringify({ theme: nuit ? 'nuit' : 'clair' }),
                });
            } catch (e) { /* hors ligne : le choix reste affiché jusqu'au rechargement */ }
        });
    })();

    @if ($avecRecherche)
    // Pastille de la cloche : mise à jour toutes les 30 secondes
    (function () {
        const badge = document.getElementById('notif-badge');
        function majBadge(n) {
            if (n > 0) {
                badge.textContent = n > 9 ? '9+' : n;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
        async function rafraichir() {
            try {
                const res = await fetch(@json(route('notifications.compteur')), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (res.ok) majBadge((await res.json()).nouvelles);
            } catch (e) { /* hors ligne : on réessaiera */ }
        }
        setInterval(() => { if (!document.hidden) rafraichir(); }, 30000);
    })();
    @endif
</script>
@endsection