@extends('layouts.app')

@section('titre', $event->name.' · Invitation')

@section('contenu')
@php
    $confirme = $rsvp?->response === 'accepte';
    $decline = $rsvp?->response === 'refuse';
    $modifier = request()->boolean('modifier');
    $dejaRepondu = $rsvp && ! $modifier;
@endphp

<div class="min-h-dvh bg-[#1d140e] px-4 py-6 text-cream sm:py-10">
    <div class="mx-auto max-w-md rounded-[1.75rem] border border-[#C9A24A] p-2">
        <div class="overflow-hidden rounded-[1.4rem] border border-[#C9A24A]/45">

            @if ($event->cover_path)
                <img src="{{ asset('storage/'.$event->cover_path) }}" alt="" class="h-48 w-full object-cover">
            @endif

            <div class="px-6 py-9 text-center sm:px-8">

                @if (session('merci'))
                    <div class="mb-6 rounded-xl bg-[#E3B04B]/15 p-3 text-sm font-semibold text-[#F3D28A] ring-1 ring-[#E3B04B]/40">
                        {{ session('merci') }}
                    </div>
                @endif

                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#E3B04B]">
                    {{ $confirme ? 'Invitation confirmée' : ($decline ? 'Réponse enregistrée' : 'Vous êtes invités') }}
                </p>

                <h1 class="mt-4 font-display text-4xl leading-tight">{{ $event->name }}</h1>
                <p class="mt-2 text-sm text-cream/70">{{ $typeLabel }}</p>

                <p class="mt-5 text-cream/90">{{ ucfirst($jour) }} · {{ $heure }}</p>
                @if ($event->venue_name)<p class="text-cream/90">{{ $event->venue_name }}</p>@endif

                <div class="mx-auto my-7 h-px w-16 bg-[#C9A24A]"></div>

                <p class="font-display text-2xl">{{ $guest->display_name }}</p>
                @if ($confirme)
                    <p class="mt-3 inline-block rounded-full border border-[#E3B04B]/50 bg-[#E3B04B]/15 px-4 py-1.5 text-sm font-semibold text-[#F3D28A]">
                        Présence confirmée · {{ $rsvp->party_size }} {{ $rsvp->party_size > 1 ? 'personnes' : 'personne' }}
                    </p>
                @else
                    <p class="mt-1 text-sm font-semibold text-[#E3B04B]">
                        {{ $guest->allowed_seats }} {{ $guest->allowed_seats > 1 ? 'places' : 'place' }} maximum
                    </p>
                @endif

                @unless ($dejaRepondu)
                    <p class="mx-auto mt-5 max-w-xs leading-relaxed text-cream/80">
                        {{ $event->description ?: 'Nous serions heureux de vous compter parmi nous.' }}
                    </p>
                @endunless

                @if ($dejaRepondu)
                    @include('invitation._reponse')
                    @include('invitation._infos')
                @else
                    @include('invitation._infos')
                    @include('invitation._reponse')
                @endif

                @if ($event->contact_name || $event->contact_phone || $event->contact_email)
                    <p class="mt-8 text-sm text-cream/60">
                        Une question ?
                        {{ collect([$event->contact_name, $event->contact_phone, $event->contact_email])->filter()->implode(' · ') }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    <p class="mt-6 text-center text-xs text-cream/40">Ñewal by ATAABA</p>
</div>
@endsection