@php
    $extension = collect(['png', 'svg', 'webp', 'jpeg'])->first(fn ($e) => file_exists(public_path("images/logo.$e")));
@endphp
@if ($extension)
    <img src="{{ asset('images/logo.'.$extension) }}" alt="Ñewal" {{ $attributes->class(['object-contain']) }}>
@else
    <x-icone nom="logo." {{ $attributes->class(['text-or']) }} />
@endif