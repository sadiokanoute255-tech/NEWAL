@php
    $settings = $event->rsvp_settings ?? [];
    $champ = 'mt-1.5 block min-h-12 w-full rounded-xl border border-cream/30 bg-white/10 px-4 py-3 text-cream outline-none placeholder:text-cream/45 focus:border-[#E3B04B]';
    $places = max(1, (int) $guest->allowed_seats);
@endphp

@if ($confirme && ! $modifier)

    {{-- §48.9 : QR Code après confirmation --}}
    <div class="mx-auto mt-8 max-w-[15rem] rounded-3xl bg-white p-5">
        <div class="mx-auto size-48 [&>svg]:size-full" role="img" aria-label="QR Code de votre invitation">{!! $qr !!}</div>
        <p class="mt-3 text-sm font-semibold tracking-[0.08em] text-brand">{{ $invitation->code }}</p>
    </div>
    <p class="mx-auto mt-5 max-w-xs leading-relaxed text-cream/80">
        Présentez ce QR Code à l'entrée. Il ne peut être utilisé qu'une seule fois.
    </p>

    <a href="{{ route('invitation.calendar', $invitation->token) }}" class="bouton mt-6 w-full">Ajouter au calendrier</a>
    <a href="{{ route('invitation.show', $invitation->token) }}?modifier=1"
       class="mt-3 flex min-h-12 w-full items-center justify-center rounded-full border border-cream font-semibold text-cream transition hover:bg-white/10">
        Modifier ma réponse
    </a>

@elseif ($decline && ! $modifier)

    <p class="mx-auto mt-6 max-w-xs leading-relaxed text-cream/80">
        Vous avez indiqué ne pas pouvoir participer. Merci d'avoir répondu.
    </p>
    <a href="{{ route('invitation.show', $invitation->token) }}?modifier=1"
       class="mt-5 flex min-h-12 w-full items-center justify-center rounded-full border border-cream font-semibold text-cream transition hover:bg-white/10">
        Modifier ma réponse
    </a>

@else

    {{-- §17 : formulaire de réponse --}}
    <form method="POST" action="{{ route('invitation.rsvp', $invitation->token) }}" class="group mt-8 text-left">
        @csrf
        <p class="text-center font-display text-xl">Votre réponse</p>

        @if ($errors->any())
            <div class="mt-4 rounded-xl bg-red-950/60 p-3 text-sm text-red-200">
                @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
            </div>
        @endif

        <div class="mt-4 grid gap-3">
            <label class="cursor-pointer">
                <input id="reponse-oui" type="radio" name="response" value="accepte" required class="peer sr-only"
                       @checked(old('response', $rsvp?->response) === 'accepte')>
                <span class="flex min-h-13 items-center justify-center rounded-full border border-cream/40 px-5 font-semibold transition peer-checked:border-transparent peer-checked:bg-linear-to-r peer-checked:from-[#F2B01E] peer-checked:to-[#E8A317] peer-checked:text-[#2a170d] peer-focus-visible:ring-2 peer-focus-visible:ring-[#E3B04B]">
                    Je participerai
                </span>
            </label>
            <label class="cursor-pointer">
                <input id="reponse-non" type="radio" name="response" value="refuse" required class="peer sr-only"
                       @checked(old('response', $rsvp?->response) === 'refuse')>
                <span class="flex min-h-13 items-center justify-center rounded-full border border-cream/40 px-5 font-semibold transition peer-checked:border-transparent peer-checked:bg-cream peer-checked:text-[#2a170d] peer-focus-visible:ring-2 peer-focus-visible:ring-[#E3B04B]">
                    Je ne pourrai pas participer
                </span>
            </label>
        </div>

        {{-- Champs demandés seulement si l'invité participe --}}
        <div class="mt-5 hidden space-y-4 group-has-[#reponse-oui:checked]:block">
            @if (! empty($settings['ask_party_size']) && $places > 1)
                <div>
                    <label for="party_size" class="text-sm font-semibold">Nombre de participants</label>
                    <select id="party_size" name="party_size" class="{{ $champ }}">
                        @for ($i = 1; $i <= $places; $i++)
                            <option value="{{ $i }}" class="text-brand" @selected((int) old('party_size', $rsvp?->party_size ?: $places) === $i)>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
            @endif
            @if (! empty($settings['ask_companion']))
                <div>
                    <label for="companion_name" class="text-sm font-semibold">Nom de l'accompagnant</label>
                    <input id="companion_name" name="companion_name" value="{{ old('companion_name', $rsvp?->companion_name) }}" class="{{ $champ }}">
                </div>
            @endif
            @if (! empty($settings['ask_diet']))
                <div>
                    <label for="dietary_preferences" class="text-sm font-semibold">Préférences alimentaires</label>
                    <input id="dietary_preferences" name="dietary_preferences" value="{{ old('dietary_preferences', $rsvp?->dietary_preferences) }}" class="{{ $champ }}">
                </div>
            @endif
            @if (! empty($settings['ask_special_needs']))
                <div>
                    <label for="special_needs" class="text-sm font-semibold">Besoins particuliers</label>
                    <input id="special_needs" name="special_needs" value="{{ old('special_needs', $rsvp?->special_needs) }}" class="{{ $champ }}">
                </div>
            @endif
        </div>

        @if (! empty($settings['ask_comment']))
            <div class="mt-4">
                <label for="comment" class="text-sm font-semibold">Un mot pour les organisateurs <span class="font-normal text-cream/60">(facultatif)</span></label>
                <textarea id="comment" name="comment" rows="3" class="{{ $champ }}">{{ old('comment', $rsvp?->comment) }}</textarea>
            </div>
        @endif

        <button type="submit" class="bouton mt-6 w-full">Envoyer ma réponse</button>
    </form>

@endif