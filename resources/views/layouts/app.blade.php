@php
    $logoOnglet = collect(['png', 'svg', 'webp', 'jpg'])->first(fn ($e) => file_exists(public_path("images/logo.$e")));
@endphp
<!doctype html>
<html lang="fr" class="@yield('html-class')">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FBF5EA">
    <title>@yield('titre', 'Ñewal')</title>
    @stack('meta')
    @stack('head')
    <link rel="icon" href="{{ $logoOnglet ? asset('images/logo.'.$logoOnglet) : '/favicon.svg' }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Fraunces:wght@600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-cream font-sans text-brand antialiased">
    @yield('contenu')
</body>
</html>