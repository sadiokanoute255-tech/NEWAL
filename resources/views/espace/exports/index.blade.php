@extends('layouts.espace')

@section('titre', 'Rapports et exports · '.$event->name)

@section('page')
<a href="{{ route('evenements.show', $event) }}" class="text-sm font-semibold text-or-fonce hover:underline">← {{ $event->name }}</a>

<h1 class="mt-3 font-display text-3xl">Rapports et exports</h1>
<p class="mt-1 text-muted">Téléchargez vos listes en Excel, CSV ou PDF.</p>

<div class="mt-6 space-y-3">
    @foreach ($rapports as $cle => [$titre, $aide])
        <div class="carte flex flex-wrap items-center justify-between gap-4 p-4 sm:p-5">
            <div class="min-w-0">
                <p class="font-semibold">{{ $titre }}</p>
                <p class="mt-0.5 text-sm text-muted">{{ $aide }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('exports.download', [$event, $cle, 'xlsx']) }}" class="bouton min-h-10 px-4 text-sm">Excel</a>
                <a href="{{ route('exports.download', [$event, $cle, 'csv']) }}" class="bouton-clair min-h-10 px-4 text-sm">CSV</a>
                <a href="{{ route('exports.download', [$event, $cle, 'pdf']) }}" class="bouton-clair min-h-10 px-4 text-sm">PDF</a>
            </div>
        </div>
    @endforeach
</div>

<p class="mt-6 text-xs text-muted">Ces fichiers contiennent des données personnelles : conservez-les en sécurité et ne les partagez qu'avec les personnes concernées.</p>
@endsection