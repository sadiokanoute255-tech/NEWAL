<section class="mt-8 space-y-4 text-left">
    <div class="rounded-2xl bg-white/5 p-5 ring-1 ring-cream/15">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#E3B04B]">Lieu</p>
        <p class="mt-2 font-semibold">{{ $event->venue_name ?: 'Lieu à préciser' }}</p>
        @if ($event->address)<p class="text-sm text-cream/75">{{ $event->address }}</p>@endif
        @if ($mapsUrl)
            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="mt-3 inline-block font-semibold text-[#F3D28A] underline">Voir l'itinéraire</a>
        @endif
        @if ($event->dress_code)<p class="mt-3 text-sm text-cream/75">Dress code : {{ $event->dress_code }}</p>@endif
        @if ($event->instructions)<p class="mt-2 text-sm text-cream/75">{{ $event->instructions }}</p>@endif
    </div>

    @if ($event->programItems->isNotEmpty())
        <div class="rounded-2xl bg-white/5 p-5 ring-1 ring-cream/15">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#E3B04B]">Programme</p>
            <ul class="mt-3 space-y-2.5">
                @foreach ($event->programItems->sortBy('sort_order') as $item)
                    <li class="flex gap-4 text-sm">
                        <span class="w-12 shrink-0 font-semibold text-[#F3D28A]">{{ substr($item->time, 0, 5) }}</span>
                        <span>{{ $item->title }}
                            @if ($item->description)<span class="block text-cream/65">{{ $item->description }}</span>@endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</section>