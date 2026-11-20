import { Html5Qrcode } from 'html5-qrcode';

const cfg = window.CONTROLE;
const resultat = document.getElementById('resultat');
const lecteur = document.getElementById('lecteur');
const btnCamera = document.getElementById('btn-camera');
const formulaire = document.getElementById('recherche');
const champ = document.getElementById('q');

let camera = null;
let occupe = false;

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

// Classes complètes écrites en toutes lettres pour que Tailwind les détecte.
const couleurs = {
    vert: 'bg-emerald-50 text-emerald-900 ring-emerald-200',
    orange: 'bg-amber-50 text-amber-900 ring-amber-200',
    rouge: 'bg-red-50 text-red-900 ring-red-200',
    gris: 'bg-stone-100 text-stone-800 ring-stone-200',
};

const bloc = (message, couleur) =>
    `<div class="rounded-2xl p-5 text-sm font-semibold ring-1 ${couleurs[couleur]}">${message}</div>`;

async function post(url, corps) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': cfg.csrf,
        },
        body: JSON.stringify(corps),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(
            data.message ||
                (res.status === 419 ? 'Session expirée, rechargez la page.' :
                 res.status === 429 ? 'Trop de requêtes, patientez un instant.' : 'Erreur, réessayez.')
        );
    }
    return data;
}

async function chercher(q) {
    if (occupe || !q) return;
    occupe = true;
    resultat.innerHTML = bloc('Recherche…', 'gris');
    try {
        const data = await post(cfg.recherche, { q });
        afficher(data.matches);
    } catch (e) {
        resultat.innerHTML = bloc(esc(e.message), 'rouge');
    } finally {
        occupe = false;
    }
}

function afficher(matches) {
    if (matches.length === 0) {
        resultat.innerHTML = bloc('Invitation introuvable pour cet événement.', 'rouge');
        return;
    }
    if (matches.length > 1) {
        resultat.innerHTML =
            `<div class="carte space-y-3"><p class="font-semibold">Plusieurs invités correspondent :</p>` +
            matches
                .map((m, i) => `<button type="button" data-i="${i}" class="bouton-clair w-full justify-between"><span>${esc(m.nom)}</span><span class="text-sm font-normal text-muted">${esc(m.code)}</span></button>`)
                .join('') +
            `</div>`;
        resultat.querySelectorAll('[data-i]').forEach((b) =>
            b.addEventListener('click', () => afficher([matches[Number(b.dataset.i)]]))
        );
        return;
    }
    fiche(matches[0]);
}

function fiche(m) {
    const pluriel = (n, mot) => `${n} ${mot}${n > 1 ? 's' : ''}`;

    const etats = {
        ok: ['Invitation valide', 'vert', 'Première entrée de cette invitation.'],
        partiel: ['Déjà utilisée', 'orange', `Premier passage à ${esc(m.premier)}. ${pluriel(m.entres, 'personne')} déjà enregistrée(s), ${pluriel(m.reste, 'place')} restante(s).`],
        complet: ['Déjà utilisée', 'rouge', `Premier passage à ${esc(m.premier)}. ${pluriel(m.entres, 'personne')} déjà enregistrée(s). Toutes les places sont utilisées.`],
        decline: ['Invitation déclinée', 'orange', 'Cet invité avait indiqué ne pas venir. À vous de décider.'],
    };
    const [titre, couleur, detail] = etats[m.etat];

    const reponses = { accepte: 'Présence confirmée', refuse: 'A décliné', aucune: 'Sans réponse' };

    resultat.innerHTML = `
        <div class="carte">
            <div class="rounded-2xl p-4 ring-1 ${couleurs[couleur]}">
                <p class="font-display text-xl">${titre}</p>
                <p class="mt-1 text-sm font-normal">${detail}</p>
            </div>

            <p class="mt-5 font-display text-2xl">${esc(m.nom)}</p>
            <p class="mt-1 text-sm text-muted">
                ${pluriel(m.places, 'place')} autorisée(s) · ${reponses[m.reponse]}${m.cercle ? ' · ' + esc(m.cercle) : ''} · ${esc(m.code)}
            </p>

            ${m.reste > 0 ? `
            <div class="mt-5 flex flex-wrap items-end gap-4">
                <div>
                    <label for="nb" class="etiquette">Personnes qui entrent</label>
                    <input id="nb" type="number" min="1" max="${m.reste}" value="${m.defaut}" class="champ mt-1.5 w-32">
                </div>
                <button id="valider" type="button" class="bouton">Valider l'entrée</button>
            </div>` : ''}
        </div>`;

    const valider = document.getElementById('valider');
    if (!valider) return;

    valider.addEventListener('click', async () => {
        const nb = Number(document.getElementById('nb').value);
        if (!Number.isInteger(nb) || nb < 1 || nb > m.reste) {
            resultat.insertAdjacentHTML('beforeend', bloc(`Indiquez un nombre entre 1 et ${m.reste}.`, 'orange'));
            return;
        }
        valider.disabled = true;
        try {
            const data = await post(cfg.entree, { invitation_id: m.id, persons: nb });
            resultat.innerHTML = `
                <div class="rounded-2xl p-6 text-center ring-1 ${couleurs.vert}">
                    <p class="font-display text-2xl">Entrée enregistrée</p>
                    <p class="mt-2">${esc(data.nom)} · ${pluriel(data.persons, 'personne')}</p>
                    <button type="button" id="suivant" class="bouton mt-5">Invitation suivante</button>
                </div>`;
            document.getElementById('suivant').addEventListener('click', () => window.location.reload());
        } catch (e) {
            valider.disabled = false;
            resultat.insertAdjacentHTML('beforeend', bloc(esc(e.message), 'rouge'));
        }
    });
}

async function arreter() {
    if (!camera) return;
    try { await camera.stop(); camera.clear(); } catch { /* déjà arrêtée */ }
    camera = null;
    lecteur.classList.add('hidden');
    btnCamera.textContent = 'Scanner un QR Code';
}

btnCamera.addEventListener('click', async () => {
    if (camera) { await arreter(); return; }
    try {
        camera = new Html5Qrcode('lecteur');
        lecteur.classList.remove('hidden');
        await camera.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 240, height: 240 } },
            async (texte) => { await arreter(); chercher(texte.trim()); },
            () => {}
        );
        btnCamera.textContent = 'Arrêter la caméra';
    } catch {
        camera = null;
        lecteur.classList.add('hidden');
        resultat.innerHTML = bloc('Caméra indisponible. Autorisez-la dans le navigateur (une connexion sécurisée est requise) ou utilisez la recherche manuelle.', 'orange');
    }
});

formulaire.addEventListener('submit', (e) => {
    e.preventDefault();
    chercher(champ.value.trim());
});