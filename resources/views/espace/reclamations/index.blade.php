@extends('layouts.espace')

@section('titre', 'Réclamations · Ñewal')

@section('page')
@php
    $badges = [
        'nouveau' => 'bg-amber-100 text-amber-900',
        'en_cours' => 'bg-sky-100 text-sky-900',
        'resolu' => 'bg-emerald-100 text-emerald-900',
    ];
@endphp

<h1 class="font-display text-3xl">Réclamations</h1>
<p class="mt-1 text-muted">Un problème, une question ? Écrivez à l'équipe ATAABA : sa réponse apparaîtra ici.</p>

@if ($errors->any())
    <div class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">
        @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('reclamations.store') }}" class="carte mt-6 space-y-5">
    @csrf
    <h2 class="font-display text-xl">Nouvelle réclamation</h2>

    <div>
        <label class="etiquette" for="subject">Sujet</label>
        <input id="subject" name="subject" required maxlength="150" value="{{ old('subject') }}" class="champ mt-1.5">
    </div>

    <div>
        <label class="etiquette" for="event_id">Événement concerné <span class="font-normal text-muted">(facultatif)</span></label>
        <select id="event_id" name="event_id" class="champ mt-1.5">
            <option value="">Aucun en particulier</option>
            @foreach ($events as $e)
                <option value="{{ $e->id }}" @selected(old('event_id') == $e->id)>{{ $e->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="etiquette" for="message">Message</label>
        <textarea id="message" name="message" rows="5" required maxlength="3000" class="champ mt-1.5">{{ old('message') }}</textarea>
    </div>

    <button class="bouton">Envoyer</button>
</form>

<h2 class="mt-8 font-display text-xl">Mes réclamations</h2>
<div class="mt-3 space-y-3">
    @forelse ($complaints as $c)
        <div class="carte p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $c->subject }}</p>
                    <p class="mt-0.5 text-xs text-muted">
                        {{ $c->created_at->format('d/m/Y H:i') }}@if ($c->event) · {{ $c->event->name }}@endif
                    </p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badges[$c->status] ?? 'bg-stone-200 text-stone-800' }}">
                    {{ \App\Models\Complaint::STATUS[$c->status] ?? $c->status }}
                </span>
            </div>
            <p class="mt-3 whitespace-pre-line text-sm">{{ $c->message }}</p>

            @if ($c->response)
                <div class="mt-4 rounded-xl bg-cream p-4 text-sm">
                    <p class="font-semibold text-or-fonce">Réponse de l'équipe ATAABA</p>
                    <p class="mt-1 whitespace-pre-line">{{ $c->response }}</p>
                </div>
            @endif
        </div>
    @empty
        <div class="carte text-center text-muted">Vous n'avez envoyé aucune réclamation.</div>
    @endforelse
</div>
@endsection