@php
    $tz = old('timezone', $event?->timezone ?: 'Africa/Dakar');
    $debut = old('starts_at', $event?->starts_at?->copy()->timezone($tz)->format('Y-m-d\TH:i'));
    $fin = old('ends_at', $event?->ends_at?->copy()->timezone($tz)->format('Y-m-d\TH:i'));
    $rsvpActuel = $event?->rsvp_settings ?? ['ask_party_size' => true, 'ask_comment' => true];
    $programme = old('program', $event
        ? $event->programItems->sortBy('sort_order')->map(fn ($i) => [
            'time' => substr($i->time, 0, 5), 'title' => $i->title, 'description' => $i->description,
        ])->values()->all()
        : []);
@endphp

@if ($errors->any())
    <div class="rounded-xl bg-red-50 p-4 text-sm text-red-700">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

@isset($orgs)
    @if ($orgs->count() > 1)
        <div class="carte">
            <label class="etiquette" for="organization_id">Organisation</label>
            <select id="organization_id" name="organization_id" class="champ mt-1.5">
                @foreach ($orgs as $o)
                    <option value="{{ $o->id }}" @selected(old('organization_id') == $o->id)>{{ $o->name }}</option>
                @endforeach
            </select>
        </div>
    @else
        <input type="hidden" name="organization_id" value="{{ $orgs->first()->id }}">
    @endif
@endisset

<section class="carte space-y-4">
    <h2 class="font-display text-xl">Visuel principal</h2>
    <p class="text-sm text-muted">Une belle photo de votre événement (JPG, PNG ou WebP, 4 Mo maximum). Elle illustre vos cartes, la fiche de l'événement et l'invitation.</p>

    @if ($event?->cover_path)
        <img src="{{ asset('storage/'.$event->cover_path) }}" alt="Visuel actuel" class="h-40 w-full rounded-2xl object-cover sm:w-72">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remove_cover" value="1" class="size-5 rounded border-sand">
            Supprimer le visuel actuel
        </label>
    @endif

    <input type="file" name="cover" accept="image/png,image/jpeg,image/webp"
           class="champ file:mr-4 file:rounded-full file:border-0 file:bg-sand file:px-4 file:py-2 file:font-semibold file:text-brand">
</section>

<section class="carte space-y-5">
    <h2 class="font-display text-xl">Informations générales</h2>

    <div>
        <label class="etiquette" for="name">Nom de l'événement</label>
        <input id="name" name="name" required value="{{ old('name', $event?->name) }}" class="champ mt-1.5">
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="etiquette" for="type">Type</label>
            <select id="type" name="type" class="champ mt-1.5">
                @foreach (\App\Support\EventOptions::TYPES as $v => $l)
                    <option value="{{ $v }}" @selected(old('type', $event?->type ?? 'autre') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="etiquette" for="visibility">Visibilité</label>
            <select id="visibility" name="visibility" class="champ mt-1.5">
                @foreach (\App\Support\EventOptions::VISIBILITY as $v => $l)
                    <option value="{{ $v }}" @selected(old('visibility', $event?->visibility ?? 'sur_invitation') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="etiquette" for="description">Description</label>
        <textarea id="description" name="description" rows="3" class="champ mt-1.5">{{ old('description', $event?->description) }}</textarea>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="etiquette" for="starts_at">Début</label>
            <input id="starts_at" type="datetime-local" name="starts_at" required value="{{ $debut }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="ends_at">Fin <span class="font-normal text-muted">(facultatif)</span></label>
            <input id="ends_at" type="datetime-local" name="ends_at" value="{{ $fin }}" class="champ mt-1.5">
        </div>
    </div>

    <div>
        <label class="etiquette" for="timezone">Fuseau horaire</label>
        <select id="timezone" name="timezone" class="champ mt-1.5">
            @foreach (\App\Support\EventOptions::TIMEZONES as $v => $l)
                <option value="{{ $v }}" @selected($tz === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
</section>

<section class="carte space-y-5">
    <h2 class="font-display text-xl">Lieu</h2>

    <div>
        <label class="etiquette" for="venue_name">Nom du lieu</label>
        <input id="venue_name" name="venue_name" value="{{ old('venue_name', $event?->venue_name) }}" class="champ mt-1.5">
    </div>
    <div>
        <label class="etiquette" for="address">Adresse</label>
        <input id="address" name="address" value="{{ old('address', $event?->address) }}" class="champ mt-1.5">
    </div>
    <div>
        <label class="etiquette" for="map_url">Lien de la carte <span class="font-normal text-muted">(Google Maps, etc.)</span></label>
        <input id="map_url" type="url" name="map_url" value="{{ old('map_url', $event?->map_url) }}" class="champ mt-1.5" placeholder="https://">
    </div>
</section>

<section class="carte space-y-5">
    <h2 class="font-display text-xl">Informations complémentaires</h2>

    <div>
        <label class="etiquette" for="dress_code">Dress code</label>
        <input id="dress_code" name="dress_code" value="{{ old('dress_code', $event?->dress_code) }}" class="champ mt-1.5">
    </div>
    <div>
        <label class="etiquette" for="instructions">Instructions particulières</label>
        <textarea id="instructions" name="instructions" rows="3" class="champ mt-1.5">{{ old('instructions', $event?->instructions) }}</textarea>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="etiquette" for="contact_name">Contact : nom</label>
            <input id="contact_name" name="contact_name" value="{{ old('contact_name', $event?->contact_name) }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="contact_phone">Contact : téléphone</label>
            <input id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $event?->contact_phone) }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="contact_email">Contact : e-mail</label>
            <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email', $event?->contact_email) }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="website">Site web</label>
            <input id="website" type="url" name="website" value="{{ old('website', $event?->website) }}" class="champ mt-1.5" placeholder="https://">
        </div>
    </div>
</section>

<section class="carte space-y-4">
    <h2 class="font-display text-xl">Programme</h2>
    <p class="text-sm text-muted">Heure, titre et description. Les lignes vides sont ignorées.</p>

    @for ($i = 0; $i < 8; $i++)
        <div class="grid gap-3 sm:grid-cols-[8rem_1fr_1fr]">
            <input type="time" name="program[{{ $i }}][time]" value="{{ $programme[$i]['time'] ?? '' }}" class="champ" aria-label="Heure">
            <input name="program[{{ $i }}][title]" value="{{ $programme[$i]['title'] ?? '' }}" placeholder="Ex. Cérémonie" class="champ" aria-label="Titre">
            <input name="program[{{ $i }}][description]" value="{{ $programme[$i]['description'] ?? '' }}" placeholder="Description (facultatif)" class="champ" aria-label="Description">
        </div>
    @endfor
</section>

<section class="carte space-y-3">
    <h2 class="font-display text-xl">Réponse de l'invité (RSVP)</h2>
    <p class="text-sm text-muted">Informations demandées à l'invité quand il confirme sa présence.</p>

    <input type="hidden" name="rsvp[_]" value="1">
    @foreach (\App\Support\EventOptions::RSVP as $cle => $libelle)
        <label class="flex items-center gap-3 text-sm">
            <input type="checkbox" name="rsvp[{{ $cle }}]" value="1" class="size-5 rounded border-sand"
                   @checked(old('rsvp') !== null ? ! empty(old("rsvp.$cle")) : ! empty($rsvpActuel[$cle] ?? false))>
            {{ $libelle }}
        </label>
    @endforeach
</section>