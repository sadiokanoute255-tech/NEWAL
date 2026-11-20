@extends('layouts.app')

@php
    $extFond = collect(['jpg', 'jpeg', 'png', 'webp'])->first(fn ($e) => file_exists(public_path("images/accueil.$e")));
@endphp

@section('contenu')
<div class="relative flex min-h-dvh flex-col bg-[#1d140e] bg-cover bg-center px-5 pb-8 pt-6 text-cream sm:px-10 lg:px-16"
     @if ($extFond) style="background-image: url('{{ asset('images/accueil.'.$extFond) }}');" @endif>

    {{-- Voile sombre pour la lisibilité --}}
    <div class="absolute inset-0 bg-[#140c06]/75 lg:bg-[#140c06]/0 lg:bg-linear-to-r lg:from-[#140c06]/90 lg:via-[#140c06]/55 lg:to-[#140c06]/15" aria-hidden="true"></div>

    <div class="relative z-10 flex flex-1 flex-col">
        <header class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4">
            <a href="{{ route('accueil') }}" class="flex items-center gap-2.5">
                <x-logo class="size-17" />
                <span class="font-display text-3xl lg:text-4xl">Ñewal</span>
            </a>
            @yield('bouton-haut')
        </header>

        <div class="mx-auto grid w-full max-w-6xl flex-1 items-center gap-10 py-10 lg:grid-cols-2 lg:gap-16">
            <div class="max-w-xl">
                @yield('texte')
            </div>

            <div class="w-full max-w-md justify-self-center rounded-[1.75rem] bg-white p-7 text-brand shadow-2xl sm:p-8 lg:justify-self-end">
                <div class="flex items-center justify-center gap-3" aria-hidden="true">
                    <span class="h-px w-12 bg-or/60"></span>
                    <x-logo class="size-18" />
                    <span class="h-px w-12 bg-or/60"></span>
                </div>

                @yield('carte')
            </div>
        </div>

        <footer class="mx-auto w-full max-w-6xl text-sm text-cream/70">Ñewal by ATAABA</footer>
    </div>
</div>
@endsection