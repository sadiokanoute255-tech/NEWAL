@props(['nom', 'etiquette', 'icone', 'type' => 'text', 'placeholder' => '', 'requis' => false])
<div>
    <label for="{{ $nom }}" class="etiquette">{{ $etiquette }}</label>
    <div class="relative mt-1.5">
        <x-icone :nom="$icone" class="pointer-events-none absolute left-3.5 top-1/2 size-5 -translate-y-1/2 text-brand/70" />

        <input id="{{ $nom }}" name="{{ $nom }}" type="{{ $type }}" placeholder="{{ $placeholder }}"
               @if ($requis) required @endif
               @if ($type !== 'password') value="{{ old($nom) }}" @endif
               {{ $attributes->merge(['class' => 'champ pl-11'.($type === 'password' ? ' pr-12' : '')]) }}>

        @if ($type === 'password')
            <button type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full p-1 text-brand/70 hover:text-brand"
                    aria-label="Afficher le mot de passe" aria-pressed="false"
                    onclick="const champ = this.parentElement.querySelector('input'); const montrer = champ.type === 'password'; champ.type = montrer ? 'text' : 'password'; this.setAttribute('aria-pressed', montrer); this.setAttribute('aria-label', montrer ? 'Masquer le mot de passe' : 'Afficher le mot de passe'); this.querySelector('.oeil').classList.toggle('hidden', montrer); this.querySelector('.oeil-barre').classList.toggle('hidden', !montrer);">
                <svg class="oeil size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="oeil-barre hidden size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></svg>
            </button>
        @endif
    </div>
</div>