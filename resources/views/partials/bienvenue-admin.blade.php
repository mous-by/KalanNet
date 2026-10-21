{{-- Bienvenue de l'Admin d'une nouvelle école : une seule fois, tarifs de son statut (public ou privé) uniquement. --}}
@php
    $utilisateurBienvenue = auth()->user();
    $afficherBienvenue = $utilisateurBienvenue
        && $utilisateurBienvenue->droit === 'Admin'
        // Colonne absente (migration pas encore faite) : rien ne s'affiche.
        && array_key_exists('bienvenue_vue_at', $utilisateurBienvenue->getAttributes())
        && $utilisateurBienvenue->bienvenue_vue_at === null
        && !$utilisateurBienvenue->doit_changer_mdp
        && !session()->has('assistance');
    if ($afficherBienvenue) {
        $ecoleBienvenue = \App\Models\Ecole::withoutGlobalScopes()->find(session('idEcole') ?: $utilisateurBienvenue->idEcole);
        $tarifBienvenue = \App\Support\TarifsBienvenue::pour($ecoleBienvenue);
        $montant = [\App\Support\TarifsBienvenue::class, 'montant'];
    }
@endphp
@if($afficherBienvenue)
    <div class="modal fade" id="bienvenueModal" tabindex="-1" aria-labelledby="bienvenueTitre" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bienvenue">
                <div class="bienvenue-hero">
                    {{-- Photos de la page de connexion, en fondu, sous un voile qui garde le texte lisible. --}}
                    <div class="bienvenue-diapos" aria-hidden="true">
                        @foreach(range(1, 7) as $n)
                            <div class="bienvenue-diapo {{ $n === 1 ? 'active' : '' }}" @if($n === 1) style="background-image: url('{{ asset('assets/images/bienvenue/fond1.jpg') }}')" @else data-image="{{ asset('assets/images/bienvenue/fond'.$n.'.jpg') }}" @endif></div>
                        @endforeach
                    </div>
                    <div class="bienvenue-voile" aria-hidden="true"></div>

                    {{-- Icône de l'application KalanNet (celle du Play Store). --}}
                    <div class="bienvenue-sceau" aria-hidden="true"><img src="{{ asset('assets/images/bienvenue/kalannet-icone.png') }}" alt=""></div>
                    <p class="bienvenue-surtitre">{{ __('bienvenue.surtitre') }}</p>
                    {{-- Texte complet pour les lecteurs d'écran ; à l'écran, il s'écrit mot par mot. --}}
                    <h2 class="bienvenue-titre" id="bienvenueTitre" aria-label="{{ __('bienvenue.titre', ['ecole' => $ecoleBienvenue?->nomEcole ?? session('nomEcole')]) }}">
                        <span class="bienvenue-ecrit" data-texte="{{ __('bienvenue.titre', ['ecole' => $ecoleBienvenue?->nomEcole ?? session('nomEcole')]) }}" aria-hidden="true"></span>
                    </h2>
                    <p class="bienvenue-devise" aria-label="{{ __('bienvenue.phrase') }}">
                        <span class="bienvenue-ecrit" data-texte="{{ __('bienvenue.phrase') }}" aria-hidden="true"></span>
                    </p>
                </div>

                <div class="bienvenue-corps">
                    <div class="bienvenue-statut">
                        <i class="bi {{ $tarifBienvenue['statut'] === 'public' ? 'bi-bank2' : 'bi-building' }}"></i>
                        {{ __('bienvenue.offre_'.$tarifBienvenue['statut']) }}
                    </div>

                    <div class="bienvenue-calcul" role="group" aria-label="{{ __('bienvenue.premiere_annee') }}">
                        <div class="bienvenue-ligne">
                            <span class="bienvenue-lib">{{ __('bienvenue.adhesion') }}<small>{{ __('bienvenue.une_fois') }}</small></span>
                            <span class="bienvenue-val">{{ $montant($tarifBienvenue['adhesion']) }}</span>
                        </div>
                        <div class="bienvenue-ligne">
                            <span class="bienvenue-lib"><span class="bienvenue-plus" aria-hidden="true">+</span>{{ __('bienvenue.abonnement') }}<small>{{ __('bienvenue.par_an') }}</small></span>
                            <span class="bienvenue-val">{{ $montant($tarifBienvenue['annuel']) }}</span>
                        </div>
                        <div class="bienvenue-total">
                            <span>{{ __('bienvenue.premiere_annee') }}</span>
                            <strong>{{ $montant($tarifBienvenue['premiere_annee']) }}</strong>
                        </div>
                    </div>

                    <div class="bienvenue-suite">
                        <i class="bi bi-arrow-repeat"></i>
                        <span>{!! __('bienvenue.annees_suivantes', ['montant' => '<strong>'.e($montant($tarifBienvenue['annuel'])).'</strong>']) !!}</span>
                    </div>

                    <button type="button" class="btn bienvenue-bouton" id="bienvenueCommencer">
                        {{ __('bienvenue.commencer') }} <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bienvenue { border: 0; border-radius: 22px; overflow: hidden; box-shadow: 0 30px 80px rgba(15, 23, 42, .35); }
        .bienvenue-hero {
            position: relative; overflow: hidden; color: #fff; text-align: center; padding: 34px 36px 32px;
            min-height: 330px; background: #0b1f1a;
        }
        .bienvenue-diapos, .bienvenue-diapo, .bienvenue-voile { position: absolute; inset: 0; }
        .bienvenue-diapo { background-size: cover; background-position: center; opacity: 0; transform: scale(1.04); transition: opacity 1.4s ease; }
        .bienvenue-diapo.active { opacity: 1; animation: bienvenue-zoom 9s ease-out both; }
        @keyframes bienvenue-zoom { from { transform: scale(1.04); } to { transform: scale(1.12); } }
        /* Voile sombre aux couleurs du thème : les photos restent visibles, le texte reste lisible. */
        .bienvenue-voile {
            background:
                linear-gradient(180deg, rgba(0, 0, 0, .55) 0%, rgba(0, 0, 0, .35) 45%, rgba(0, 0, 0, .7) 100%),
                color-mix(in srgb, var(--theme-accent, #14532d) 45%, transparent);
        }
        .bienvenue-hero > :not(.bienvenue-diapos):not(.bienvenue-voile) { position: relative; z-index: 1; }
        .bienvenue-sceau {
            width: 96px; height: 96px; margin: 0 auto 14px; border-radius: 24px; overflow: hidden;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, .85), 0 14px 30px rgba(0, 0, 0, .4);
        }
        .bienvenue-sceau img { width: 100%; height: 100%; display: block; }
        .bienvenue-surtitre { margin: 0 0 8px; font-size: .78rem; font-weight: 700; letter-spacing: .22em; text-transform: uppercase; text-shadow: 0 1px 6px rgba(0, 0, 0, .6); }
        .bienvenue-titre {
            margin: 0 auto; max-width: 30ch; min-height: 2.4em; font-size: clamp(1.45rem, 3.4vw, 2rem); font-weight: 800; line-height: 1.2;
            text-wrap: balance; color: #fff; text-shadow: 0 2px 10px rgba(0, 0, 0, .65);
        }
        .bienvenue-devise {
            margin: 14px auto 0; max-width: 46ch; min-height: 4.6em; font-size: 1.04rem; line-height: 1.55; font-weight: 500;
            text-wrap: balance; text-shadow: 0 1px 8px rgba(0, 0, 0, .7);
        }
        /* Écriture mot par mot, avec le stylo qui avance. */
        .bienvenue-mot { opacity: 0; filter: blur(3px); transition: opacity .35s ease, filter .35s ease; }
        .bienvenue-mot.ecrit { opacity: 1; filter: none; }
        .bienvenue-stylo {
            display: inline-block; width: 1.05em; margin-left: .15em; vertical-align: -0.1em; color: #fcd34d;
            transform-origin: 10% 90%; animation: bienvenue-stylo .35s ease-in-out infinite alternate;
            filter: drop-shadow(0 2px 3px rgba(0, 0, 0, .5)); transition: opacity .4s ease;
        }
        .bienvenue-stylo.fini { opacity: 0; }
        @keyframes bienvenue-stylo { from { transform: rotate(-8deg) translateY(0); } to { transform: rotate(6deg) translateY(-2px); } }
        .bienvenue-corps { padding: 28px 36px 32px; background: var(--bs-body-bg, #fff); }
        .bienvenue-statut {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 999px;
            font-weight: 700; font-size: .9rem; color: var(--theme-accent, #14532d);
            background: color-mix(in srgb, var(--theme-accent, #14532d) 10%, transparent);
        }
        .bienvenue-calcul { margin-top: 18px; border: 1px solid var(--bs-border-color, #e5e7eb); border-radius: 16px; overflow: hidden; }
        .bienvenue-ligne { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 14px 20px; border-bottom: 1px dashed var(--bs-border-color, #e5e7eb); }
        .bienvenue-lib { font-weight: 600; }
        .bienvenue-lib small { display: block; font-weight: 400; color: var(--bs-secondary-color, #6b7280); font-size: .82rem; }
        .bienvenue-plus { display: inline-block; width: 1.1em; color: var(--theme-accent, #14532d); font-weight: 800; }
        .bienvenue-val { font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .bienvenue-total {
            display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 16px 20px;
            background: color-mix(in srgb, var(--theme-accent, #14532d) 9%, transparent);
        }
        .bienvenue-total span { font-weight: 700; text-transform: uppercase; letter-spacing: .06em; font-size: .85rem; }
        .bienvenue-total strong { font-size: 1.55rem; font-weight: 800; color: var(--theme-accent, #14532d); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .bienvenue-suite { display: flex; gap: 10px; align-items: flex-start; margin-top: 16px; color: var(--bs-secondary-color, #4b5563); line-height: 1.5; }
        .bienvenue-suite i { color: var(--theme-accent, #14532d); font-size: 1.15rem; margin-top: 1px; }
        .bienvenue-suite strong { color: var(--bs-body-color, #111827); }
        .bienvenue-bouton {
            display: block; width: 100%; margin-top: 24px; padding: 13px 18px; border: 0; border-radius: 12px;
            font-weight: 700; font-size: 1.02rem; color: #fff; background: var(--theme-accent, #14532d);
            box-shadow: 0 10px 24px color-mix(in srgb, var(--theme-accent, #14532d) 35%, transparent);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .bienvenue-bouton:hover, .bienvenue-bouton:focus-visible { color: #fff; transform: translateY(-1px); box-shadow: 0 14px 28px color-mix(in srgb, var(--theme-accent, #14532d) 45%, transparent); }
        .bienvenue-bouton:focus-visible { outline: 3px solid color-mix(in srgb, var(--theme-accent, #14532d) 45%, #fff); outline-offset: 2px; }
        #bienvenueModal.show .bienvenue-sceau { animation: bienvenue-sceau .7s ease-out both; }
        @keyframes bienvenue-sceau { from { transform: scale(.6); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @media (prefers-reduced-motion: reduce) {
            #bienvenueModal.show .bienvenue-sceau, .bienvenue-diapo.active, .bienvenue-stylo { animation: none; }
            .bienvenue-diapo { transition: none; }
            .bienvenue-mot { opacity: 1; filter: none; transition: none; }
            .bienvenue-bouton { transition: none; }
        }
        @media (max-width: 576px) {
            .bienvenue-hero { padding: 26px 18px 24px; min-height: 300px; }
            .bienvenue-sceau { width: 78px; height: 78px; border-radius: 20px; }
            .bienvenue-corps { padding: 22px 18px 24px; }
            .bienvenue-ligne, .bienvenue-total { padding: 12px 14px; }
            .bienvenue-total strong { font-size: 1.3rem; }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fenetre = document.getElementById('bienvenueModal');
            if (!fenetre || !window.bootstrap) return;
            const modal = bootstrap.Modal.getOrCreateInstance(fenetre);
            const sansAnimation = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Carrousel : chaque photo est chargée juste avant son tour.
            const diapos = Array.from(fenetre.querySelectorAll('.bienvenue-diapo'));
            const charger = (d) => { if (d && d.dataset.image) { d.style.backgroundImage = "url('" + d.dataset.image + "')"; delete d.dataset.image; } };
            let courante = 0;
            charger(diapos[1]);
            if (!sansAnimation) {
                setInterval(function () {
                    diapos[courante].classList.remove('active');
                    courante = (courante + 1) % diapos.length;
                    diapos[courante].classList.add('active');
                    charger(diapos[(courante + 1) % diapos.length]);
                }, 4500);
            }

            // Le stylo écrit le titre puis la phrase, mot par mot.
            const stylo = '<svg class="bienvenue-stylo" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>';
            const zones = Array.from(fenetre.querySelectorAll('.bienvenue-ecrit'));
            zones.forEach(function (zone) {
                zone.innerHTML = zone.dataset.texte.split(/\s+/).map((mot) => '<span class="bienvenue-mot">' + mot.replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</span>').join(' ');
            });
            function ecrire() {
                const mots = zones.flatMap((zone) => Array.from(zone.querySelectorAll('.bienvenue-mot')));
                if (sansAnimation) { mots.forEach((m) => m.classList.add('ecrit')); return; }
                const plume = document.createElement('span');
                plume.innerHTML = stylo;
                const icone = plume.firstChild;
                let i = 0;
                (function suivant() {
                    if (i >= mots.length) { icone.classList.add('fini'); return; }
                    const mot = mots[i++];
                    mot.classList.add('ecrit');
                    mot.after(icone);
                    // Petite pause entre le titre et la phrase.
                    const finTitre = mot.parentElement === zones[0] && !mot.nextElementSibling?.classList.contains('bienvenue-mot') && i < mots.length && mots[i].parentElement !== zones[0];
                    setTimeout(suivant, finTitre ? 650 : 170);
                })();
            }
            fenetre.addEventListener('shown.bs.modal', ecrire, { once: true });
            modal.show();
            document.getElementById('bienvenueCommencer').addEventListener('click', function () {
                fetch(@json(route('bienvenue.vue')), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token()), 'Accept': 'application/json' },
                }).finally(function () { modal.hide(); });
            });
        });
    </script>
@endif
