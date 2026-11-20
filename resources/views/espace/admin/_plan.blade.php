@php $p = $plan ?? null; @endphp
<form method="POST" action="{{ $p ? route('admin.offres.update', $p) : route('admin.offres.store') }}" class="carte grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @csrf
    @if ($p) @method('PUT') @endif

    <div>
        <label class="etiquette">Nom</label>
        <input name="name" required maxlength="100" value="{{ $p?->name }}" class="champ mt-1.5">
    </div>
    <div>
        <label class="etiquette">Capacité (invités)</label>
        <input type="number" name="max_guests" required min="1" value="{{ $p?->max_guests ?? 50 }}" class="champ mt-1.5">
    </div>
    <div>
        <label class="etiquette">Tarif</label>
        <input type="number" name="price" required min="0" value="{{ $p?->price ?? 0 }}" class="champ mt-1.5">
    </div>
    <div>
        <label class="etiquette">Devise</label>
        <input name="currency" required maxlength="5" value="{{ $p?->currency ?? 'XOF' }}" class="champ mt-1.5">
    </div>
    <div>
        <label class="etiquette">Période</label>
        <input name="billing_period" required maxlength="20" value="{{ $p?->billing_period ?? 'unique' }}" class="champ mt-1.5" placeholder="unique, mensuel, annuel">
    </div>
    <div>
        <label class="etiquette">Ordre d'affichage</label>
        <input type="number" name="sort_order" required min="0" value="{{ $p?->sort_order ?? 0 }}" class="champ mt-1.5">
    </div>
    <label class="flex items-center gap-2 self-end pb-3 text-sm">
        <input type="checkbox" name="is_active" value="1" class="size-5 rounded border-sand" @checked($p ? $p->is_active : true)>
        Offre active
    </label>
    <div class="self-end">
        <button class="bouton w-full">{{ $p ? 'Enregistrer' : 'Créer l\'offre' }}</button>
    </div>
</form>