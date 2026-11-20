@extends('layouts.espace')

@section('titre', 'Statistiques · '.$event->name)

@section('page')
@php
    $blocs = [
        'Invitations' => [
            ['Créées', $total, $guests.' invités'],
            ['Envoyées', $sent, null],
            ['Ouvertes', $opened, 'Taux d\'ouverture : '.$openRate.' %'],
        ],
        'Réponses' => [
            ['Confirmations', $confirmed, null],
            ['Refus', $declined, null],
            ['Sans réponse', $awaiting, 'Taux de réponse : '.$responseRate.' %'],
        ],
        'Participation' => [
            ['Participants attendus', $expected, 'personnes confirmées'],
            ['Participants présents', $present, 'personnes entrées'],
            ['Taux de participation', $attendanceRate.' %', null],
        ],
    ];
    $maxHeure = max(1, $byHour->max() ?? 1);
@endphp

<a href="{{ route('evenements.show', $event) }}" class="text-sm font-semibold text-or-fonce hover:underline">← {{ $event->name }}</a>

<div class="mt-3 flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="font-display text-3xl">Statistiques</h1>
        <p class="mt-1 text-muted">{{ $event->name }} · {{ \App\Support\EventOptions::date($event) }}</p>
    </div>
    <a href="{{ route('controle.index', $event) }}" class="bouton-clair">Contrôle d'accès</a>
</div>

@foreach ($blocs as $titre => $cartes)
    <h2 class="mt-8 font-display text-xl">{{ $titre }}</h2>
    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ($cartes as [$libelle, $valeur, $detail])
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-sand">
                <p class="text-sm text-muted">{{ $libelle }}</p>
                <p class="mt-1 font-display text-4xl">{{ $valeur }}</p>
                @if ($detail)<p class="mt-1 text-xs text-muted">{{ $detail }}</p>@endif
            </div>
        @endforeach
    </div>
@endforeach

{{-- Répartition des réponses --}}
<section class="carte mt-8">
    <h2 class="font-display text-xl">Répartition des réponses</h2>

    @if ($total === 0)
        <p class="mt-3 text-sm text-muted">Aucune invitation pour le moment. Générez les invitations pour suivre les réponses.</p>
    @else
        <div class="mt-4 flex h-5 overflow-hidden rounded-full bg-sand/60" role="img"
             aria-label="{{ $confirmed }} confirmations, {{ $declined }} refus, {{ $awaiting }} sans réponse">
            <div class="bg-emerald-600" style="width: {{ $pConfirmed }}%"></div>
            <div class="bg-red-500" style="width: {{ $pDeclined }}%"></div>
            <div class="bg-stone-400" style="width: {{ $pAwaiting }}%"></div>
        </div>
        <div class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm">
            <span class="flex items-center gap-2"><span class="size-3 rounded-full bg-emerald-600"></span>Confirmées : {{ $confirmed }} ({{ $pConfirmed }} %)</span>
            <span class="flex items-center gap-2"><span class="size-3 rounded-full bg-red-500"></span>Refus : {{ $declined }} ({{ $pDeclined }} %)</span>
            <span class="flex items-center gap-2"><span class="size-3 rounded-full bg-stone-400"></span>Sans réponse : {{ $awaiting }} ({{ $pAwaiting }} %)</span>
        </div>
    @endif
</section>

{{-- Présence --}}
<section class="carte mt-6">
    <h2 class="font-display text-xl">Présence le jour J</h2>

    <div class="mt-4 h-5 overflow-hidden rounded-full bg-sand/60" role="img" aria-label="{{ $present }} présents sur {{ $expected }} attendus">
        <div class="h-full rounded-full bg-linear-to-r from-[#F2B01E] to-[#E8A317]" style="width: {{ min(100, $attendanceRate) }}%"></div>
    </div>
    <p class="mt-2 text-sm text-muted">{{ $present }} personnes entrées sur {{ $expected }} attendues.</p>

    <h3 class="mt-6 text-sm font-semibold">Arrivées par heure</h3>
    @forelse ($byHour as $heure => $nb)
        <div class="mt-2 flex items-center gap-3 text-sm">
            <span class="w-14 shrink-0 text-muted">{{ str_replace(':', ' h ', $heure) }}</span>
            <div class="h-4 flex-1 overflow-hidden rounded-full bg-sand/60">
                <div class="h-full rounded-full bg-[#6b3a1a]" style="width: {{ max(3, (int) round($nb / $maxHeure * 100)) }}%"></div>
            </div>
            <span class="w-8 shrink-0 text-right font-semibold">{{ $nb }}</span>
        </div>
    @empty
        <p class="mt-2 text-sm text-muted">Aucune entrée enregistrée pour le moment.</p>
    @endforelse
</section>

{{-- Par cercle --}}
<section class="carte mt-6">
    <h2 class="font-display text-xl">Par cercle</h2>

    @if (count($groups) === 0)
        <p class="mt-3 text-sm text-muted">Aucun cercle ni invité pour le moment.</p>
    @else
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[30rem] text-left text-sm">
                <thead class="text-muted">
                    <tr class="border-b border-sand">
                        <th class="py-2 font-semibold">Cercle</th>
                        <th class="py-2 text-right font-semibold">Invités</th>
                        <th class="py-2 text-right font-semibold">Confirmés</th>
                        <th class="py-2 text-right font-semibold">Refus</th>
                        <th class="py-2 text-right font-semibold">Personnes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groups as $g)
                        <tr class="border-b border-sand/60 last:border-0">
                            <td class="py-3">
                                <span class="inline-flex items-center gap-2 font-semibold">
                                    <span class="size-3 rounded-full" style="background: {{ $g['color'] }}"></span>{{ $g['name'] }}
                                </span>
                            </td>
                            <td class="py-3 text-right">{{ $g['guests'] }}</td>
                            <td class="py-3 text-right">{{ $g['confirmed'] }}</td>
                            <td class="py-3 text-right">{{ $g['declined'] }}</td>
                            <td class="py-3 text-right">{{ $g['persons'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

{{-- Dernières entrées --}}
<section class="carte mt-6">
    <h2 class="font-display text-xl">Dernières entrées</h2>
    @forelse ($recent as $c)
        <div class="mt-3 flex items-center justify-between gap-3 text-sm">
            <span class="truncate font-semibold">{{ $c->invitation?->guest?->display_name ?? 'Invité supprimé' }}</span>
            <span class="shrink-0 text-muted">
                {{ $c->persons_entered }} {{ $c->persons_entered > 1 ? 'pers.' : 'pers.' }}
                · {{ $c->checked_in_at->copy()->timezone($tz)->format('H \h i') }}
            </span>
        </div>
    @empty
        <p class="mt-3 text-sm text-muted">Aucune entrée enregistrée pour le moment.</p>
    @endforelse
</section>
@endsection