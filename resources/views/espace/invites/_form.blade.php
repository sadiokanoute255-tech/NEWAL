@if ($errors->any())
    <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<section class="carte space-y-5">
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="etiquette" for="first_name">Prénom</label>
            <input id="first_name" name="first_name" value="{{ old('first_name', $guest?->first_name) }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="last_name">Nom <span class="font-normal text-muted">(ou nom de la famille)</span></label>
            <input id="last_name" name="last_name" required value="{{ old('last_name', $guest?->last_name) }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="phone">Téléphone</label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone', $guest?->phone) }}" class="champ mt-1.5" placeholder="+221 77 123 45 67">
        </div>
        <div>
            <label class="etiquette" for="whatsapp">WhatsApp <span class="font-normal text-muted">(si différent)</span></label>
            <input id="whatsapp" type="tel" name="whatsapp" value="{{ old('whatsapp', $guest?->whatsapp) }}" class="champ mt-1.5">
        </div>
        <div class="sm:col-span-2">
            <label class="etiquette" for="email">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', $guest?->email) }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="organization">Organisation</label>
            <input id="organization" name="organization" value="{{ old('organization', $guest?->organization) }}" class="champ mt-1.5">
        </div>
        <div>
            <label class="etiquette" for="job_title">Fonction</label>
            <input id="job_title" name="job_title" value="{{ old('job_title', $guest?->job_title) }}" class="champ mt-1.5">
        </div>
    </div>
</section>

<section class="carte space-y-5">
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="etiquette" for="guest_group_id">Cercle</label>
            <select id="guest_group_id" name="guest_group_id" class="champ mt-1.5">
                <option value="">Sans cercle</option>
                @foreach ($groups as $g)
                    <option value="{{ $g->id }}" @selected(old('guest_group_id', $guest?->guest_group_id) == $g->id)>{{ $g->name }}</option>
                @endforeach
            </select>
            <p class="mt-1.5 text-xs text-muted">
                <a href="{{ route('cercles.index', $event) }}" class="font-semibold text-or-fonce hover:underline">Gérer les cercles</a>
            </p>
        </div>
        <div>
            <label class="etiquette" for="allowed_seats">Nombre de places autorisées</label>
            <input id="allowed_seats" type="number" min="1" max="100" name="allowed_seats"
                   value="{{ old('allowed_seats', $guest?->allowed_seats ?? 1) }}" class="champ mt-1.5">
        </div>
    </div>
    <div>
        <label class="etiquette" for="comment">Commentaire interne</label>
        <textarea id="comment" name="comment" rows="3" class="champ mt-1.5">{{ old('comment', $guest?->comment) }}</textarea>
    </div>
</section>