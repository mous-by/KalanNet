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
                    <div class="bienvenue-motif" aria-hidden="true"></div>
                    <div class="bienvenue-sceau" aria-hidden="true"><i class="bi bi-award-fill"></i></div>
                    <p class="bienvenue-surtitre">{{ __('bienvenue.surtitre') }}</p>
                    <h2 class="bienvenue-titre" id="bienvenueTitre">{{ __('bienvenue.titre', ['ecole' => $ecoleBienvenue?->nomEcole ?? session('nomEcole')]) }}</h2>
                    <p class="bienvenue-devise">{{ __('bienvenue.phrase') }}</p>
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
            position: relative; overflow: hidden; color: #fff; text-align: center; padding: 42px 36px 34px;
            background: var(--theme-accent, #14532d);
        }
        .bienvenue-hero::after {
            content: ""; position: absolute; inset: 0;
            background: linear-gradient(160deg, rgba(255, 255, 255, .16) 0%, rgba(255, 255, 255, 0) 45%, rgba(0, 0, 0, .28) 100%);
        }
        .bienvenue-motif {
            position: absolute; inset: 0; opacity: .14;
            background-image: radial-gradient(circle at 1px 1px, #fff 1.4px, transparent 0);
            background-size: 22px 22px;
        }
        .bienvenue-hero > * { position: relative; z-index: 1; }
        .bienvenue-sceau {
            width: 74px; height: 74px; margin: 0 auto 16px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 34px;
            background: rgba(255, 255, 255, .16); border: 2px solid rgba(255, 255, 255, .55);
            box-shadow: 0 0 0 8px rgba(255, 255, 255, .08);
        }
        .bienvenue-surtitre { margin: 0 0 6px; font-size: .78rem; font-weight: 700; letter-spacing: .22em; text-transform: uppercase; opacity: .9; }
        .bienvenue-titre { margin: 0 auto; max-width: 30ch; font-size: clamp(1.45rem, 3.4vw, 2rem); font-weight: 800; line-height: 1.2; text-wrap: balance; color: #fff; }
        .bienvenue-devise { margin: 14px auto 0; max-width: 46ch; font-size: 1.02rem; line-height: 1.55; opacity: .95; text-wrap: balance; }
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
            #bienvenueModal.show .bienvenue-sceau { animation: none; }
            .bienvenue-bouton { transition: none; }
        }
        @media (max-width: 576px) {
            .bienvenue-hero { padding: 32px 20px 26px; }
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
