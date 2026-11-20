@extends('layouts.espace')

@section('titre', 'Offre · '.$event->name)

@section('page')
@php
    $prix = fn ($p) => $p->price > 0
        ? number_format($p->price, 0, ',', ' ').' '.($p->currency === 'XOF' ? 'FCFA' : $p->currency)
        : 'Tarif à définir';
    $pct = $limit > 0 ? min(100, (int) round($guests / $limit * 100)) : 0;
@endphp

<a href="{{ route('evenements.show', $event) }}" class="text-sm font-semibold text-or-fonce hover:underline">← {{ $event->name }}</a>
<h1 class="mt-3 font-display text-3xl">Offre de l'événement</h1>

@if ($errors->any())
    <div class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">
        @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
@endif

<section class="carte mt-6">
    <p class="text-sm text-muted">Offre actuelle</p>
    <p class="mt-1 font-display text-2xl">{{ $event->plan?->name ?? 'Offre par défaut' }}</p>
    <div class="mt-4 h-4 overflow-hidden rounded-full bg-sand/60" role="img" aria-label="{{ $guests }} invités sur {{ $limit }}">
        <div class="h-full rounded-full bg-linear-to-r from-[#F2B01E] to-[#E8A317]" style="width: {{ $pct }}%"></div>
    </div>
    <p class="mt-2 text-sm text-muted">{{ $guests }} invités sur {{ $limit }} autorisés.</p>
</section>

<h2 class="mt-8 font-display text-xl">Offres disponibles</h2>
<div class="mt-3 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach ($plans as $plan)
        @php
            $actuelle = $event->plan_id === $plan->id;
            $trop = $guests > $plan->max_guests;
            $libre = $plan->price == 0 && $plan->max_guests <= \App\Models\Event::DEFAULT_GUEST_LIMIT;
        @endphp
        <div class="flex flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 {{ $actuelle ? 'ring-2 ring-or' : 'ring-sand' }}">
            <p class="font-display text-xl">{{ $plan->name }}</p>
            <p class="mt-1 text-sm text-muted">Jusqu'à {{ number_format($plan->max_guests, 0, ',', ' ') }} invités</p>
            <p class="mt-4 font-display text-2xl">{{ $prix($plan) }}</p>

            <div class="mt-auto pt-5">
                @if ($actuelle)
                    <span class="flex min-h-12 items-center justify-center rounded-full bg-or/20 font-semibold text-or-fonce">Offre actuelle</span>
                @elseif ($trop)
                    <span class="flex min-h-12 items-center justify-center rounded-full bg-stone-100 px-3 text-center text-sm text-muted">Trop petite pour vos {{ $guests }} invités</span>
                @elseif (! $canWrite)
                    <span class="flex min-h-12 items-center justify-center text-sm text-muted">Réservé aux gestionnaires</span>
                @elseif ($libre)
                    <form method="POST" action="{{ route('offres.choose', [$event, $plan]) }}">
                        @csrf
                        <button class="bouton w-full">Choisir cette offre</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('offres.choose', [$event, $plan]) }}">
                        @csrf
                        <button class="bouton-clair w-full">Activer après paiement</button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
</div>

<p class="mt-6 text-sm text-muted">Le paiement en ligne (Wave, Orange Money, carte bancaire) arrive bientôt. En attendant, l'équipe ATAABA peut activer votre offre.</p>
@endsection