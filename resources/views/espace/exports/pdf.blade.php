<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $titre }} · {{ $event->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #2B2118; }
        h1 { font-size: 18px; margin: 0; color: #2B2118; }
        .sous { margin: 4px 0 14px; color: #5c5247; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #4a2a14; color: #fff; text-align: left; padding: 6px; font-size: 9px; }
        td { padding: 5px 6px; border-bottom: 1px solid #EADFC8; }
        tr:nth-child(even) td { background: #FBF5EA; }
        .pied { margin-top: 14px; color: #5c5247; font-size: 9px; }
    </style>
</head>
<body>
    <h1>{{ $titre }}</h1>
    <p class="sous">{{ $event->name }} · {{ $date }}</p>

    <table>
        <thead>
            <tr>@foreach ($entetes as $e)<th>{{ $e }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse ($lignes as $ligne)
                <tr>@foreach ($ligne as $cellule)<td>{{ $cellule }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($entetes) }}">Aucune donnée.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="pied">Généré le {{ $genere }} · Ñewal by ATAABA</p>
</body>
</html>