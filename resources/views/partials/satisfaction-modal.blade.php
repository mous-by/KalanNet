{{--
    Avis de satisfaction : proposé juste après une action réussie (voir App\Support\Satisfaction::aProposer),
    jamais par-dessus une autre fenêtre, jamais pendant une saisie.
--}}
@php
    use App\Support\Satisfaction;
@endphp
<div class="modal fade" id="satisfactionModal" tabindex="-1" aria-labelledby="satisfactionTitre" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content satisfaction-carte border-0">
            <button type="button" class="btn-close satisfaction-fermer" data-satisfaction-plus-tard aria-label="{{ __('satisfaction.fermer') }}"></button>

            {{-- Étape 1 : la note --}}
            <div class="satisfaction-etape" data-etape="1">
                <div class="satisfaction-icone"><i class="bi bi-emoji-smile"></i></div>
                <h5 class="fw-bold mb-1" id="satisfactionTitre" dir="auto">{{ __('satisfaction.titre') }}</h5>
                <p class="text-muted small mb-3" dir="auto">{{ __('satisfaction.intro') }}</p>
                <div class="satisfaction-notes" role="radiogroup" aria-label="{{ __('satisfaction.votre_note') }}">
                    @foreach(__('satisfaction.notes') as $note => $libelle)
                        <button type="button" class="satisfaction-note" data-note="{{ $note }}" role="radio" aria-checked="false" title="{{ $libelle }}">
                            <span class="satisfaction-visage">{{ ['😞', '🙁', '😐', '🙂', '😍'][$note - 1] }}</span>
                            <span class="satisfaction-libelle" dir="auto">{{ $libelle }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="d-flex justify-content-between mt-3 small">
                    <button type="button" class="btn btn-link btn-sm text-muted p-0" data-satisfaction-jamais>{{ __('satisfaction.ne_plus_demander') }}</button>
                    <button type="button" class="btn btn-link btn-sm text-muted p-0" data-satisfaction-plus-tard>{{ __('satisfaction.plus_tard') }}</button>
                </div>
            </div>

            {{-- Étape 2 : détails selon la note --}}
            <div class="satisfaction-etape d-none" data-etape="2">
                <div class="satisfaction-choisie mb-2" dir="auto"></div>
                <h6 class="fw-bold mb-2" dir="auto" data-question-positive>{{ __('satisfaction.question_positive') }}</h6>
                <h6 class="fw-bold mb-2 d-none" dir="auto" data-question-negative>{{ __('satisfaction.question_negative') }}</h6>
                <div class="satisfaction-points mb-3" data-points-positifs>
                    @foreach(array_keys(Satisfaction::POINTS_FORTS) as $cle)
                        <button type="button" dir="auto" class="satisfaction-point" data-point="{{ $cle }}">{{ __('satisfaction.points.'.$cle) }}</button>
                    @endforeach
                </div>
                <div class="satisfaction-points mb-3 d-none" data-points-negatifs>
                    @foreach(array_keys(Satisfaction::POINTS_FAIBLES) as $cle)
                        <button type="button" dir="auto" class="satisfaction-point" data-point="{{ $cle }}">{{ __('satisfaction.points.'.$cle) }}</button>
                    @endforeach
                </div>
                <textarea dir="auto" class="form-control mb-2" rows="3" maxlength="1000" data-commentaire placeholder="{{ __('satisfaction.commentaire') }}"></textarea>
                <div class="form-check small mb-3 d-none" data-accord-bloc>
                    <input class="form-check-input" type="checkbox" id="satisfactionAccord" data-accord>
                    <label class="form-check-label" for="satisfactionAccord" dir="auto">
                        {{ __('satisfaction.accord') }}
                    </label>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-link btn-sm text-muted p-0" data-retour>{{ __('satisfaction.changer_note') }}</button>
                    <button type="button" class="btn satisfaction-envoyer" data-envoyer>{{ __('satisfaction.envoyer') }}</button>
                </div>
            </div>

            {{-- Étape 3 : merci --}}
            <div class="satisfaction-etape d-none text-center" data-etape="3">
                <div class="satisfaction-icone"><i class="bi bi-heart-fill"></i></div>
                <h5 class="fw-bold mb-1" dir="auto">{{ __('satisfaction.merci_titre') }}</h5>
                <p class="text-muted small mb-0" dir="auto" data-merci>{{ __('satisfaction.merci') }}</p>
            </div>
        </div>
    </div>
</div>

<style>
    .satisfaction-carte { border-radius: 18px; padding: 26px 24px 20px; box-shadow: 0 24px 60px rgba(15, 23, 42, .25); border-top: 4px solid var(--theme-accent, #831843) !important; }
    .satisfaction-fermer { position: absolute; top: 14px; inset-inline-end: 14px; }
    .satisfaction-etape { text-align: center; }
    .satisfaction-etape[data-etape="2"] { text-align: start; }
    .satisfaction-icone { width: 52px; height: 52px; margin: 0 auto 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--theme-accent, #831843); background: var(--accent-light, #fce7f3); }
    .satisfaction-notes { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; }
    .satisfaction-note { border: 1px solid var(--border-color, #e5e7eb); background: var(--bg-card, #fff); border-radius: 12px; padding: 10px 2px 8px; display: flex; flex-direction: column; align-items: center; gap: 4px; transition: transform .12s, border-color .12s, background .12s; min-height: 76px; }
    .satisfaction-note:hover, .satisfaction-note:focus-visible { transform: translateY(-2px); border-color: var(--theme-accent, #831843); background: var(--accent-light, #fdf2f8); outline: none; }
    .satisfaction-visage { font-size: 1.7rem; line-height: 1; }
    .satisfaction-libelle { font-size: .68rem; font-weight: 600; color: var(--text-muted, #6b7280); line-height: 1.1; }
    .satisfaction-choisie { font-weight: 700; color: var(--theme-accent, #831843); padding-right: 30px; }
    .satisfaction-points { display: flex; flex-wrap: wrap; gap: 6px; }
    .satisfaction-point { border: 1px solid var(--border-color, #d1d5db); background: var(--bg-card, #fff); border-radius: 999px; padding: 6px 12px; font-size: .82rem; min-height: 36px; }
    .satisfaction-point.actif { background: var(--theme-accent, #831843); color: var(--text-on-accent, #fff); border-color: var(--theme-accent, #831843); }
    .satisfaction-envoyer { background: var(--theme-accent, #831843); color: var(--text-on-accent, #fff) !important; font-weight: 700; border-radius: 10px; padding: 8px 18px; }
    @media (max-width: 420px) { .satisfaction-libelle { display: none; } .satisfaction-note { min-height: 56px; } }
</style>

<script>
    (function () {
        const el = document.getElementById('satisfactionModal');
        if (!el || typeof bootstrap === 'undefined') return;
        const modal = new bootstrap.Modal(el);
        const etat = { note: null, points: new Set(), envoye: false };
        const contexte = @json(Satisfaction::contexte());
        const libelles = @json(__('satisfaction.notes'));
        const textes = @json(__('satisfaction'));

        const etape = (n) => el.querySelectorAll('.satisfaction-etape').forEach((e) => e.classList.toggle('d-none', e.dataset.etape !== String(n)));

        function envoyer(statut) {
            if (etat.envoye) return Promise.resolve();
            etat.envoye = true;
            const corps = { statut, contexte };
            if (statut === 'repondu') {
                corps.note = etat.note;
                corps.points = [...etat.points];
                corps.commentaire = el.querySelector('[data-commentaire]').value;
                corps.accord_publication = el.querySelector('[data-accord]').checked ? 1 : 0;
            }
            return fetch(@json(route('satisfaction.store')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                body: JSON.stringify(corps),
            }).catch(() => {});
        }

        el.querySelectorAll('.satisfaction-note').forEach((b) => b.addEventListener('click', () => {
            etat.note = Number(b.dataset.note);
            el.querySelectorAll('.satisfaction-note').forEach((x) => x.setAttribute('aria-checked', x === b ? 'true' : 'false'));
            const positif = etat.note >= 4;
            el.querySelector('.satisfaction-choisie').textContent = b.querySelector('.satisfaction-visage').textContent + ' ' + libelles[etat.note];
            el.querySelector('[data-question-positive]').classList.toggle('d-none', !positif);
            el.querySelector('[data-question-negative]').classList.toggle('d-none', positif);
            el.querySelector('[data-points-positifs]').classList.toggle('d-none', !positif);
            el.querySelector('[data-points-negatifs]').classList.toggle('d-none', positif);
            el.querySelector('[data-accord-bloc]').classList.toggle('d-none', !positif);
            el.querySelector('[data-commentaire]').placeholder = positif ? textes.commentaire_positif : textes.commentaire_negatif;
            etat.points.clear();
            el.querySelectorAll('.satisfaction-point').forEach((p) => p.classList.remove('actif'));
            etape(2);
        }));
        el.querySelectorAll('.satisfaction-point').forEach((p) => p.addEventListener('click', () => {
            p.classList.toggle('actif');
            p.classList.contains('actif') ? etat.points.add(p.dataset.point) : etat.points.delete(p.dataset.point);
        }));
        el.querySelector('[data-retour]').addEventListener('click', () => etape(1));
        el.querySelector('[data-envoyer]').addEventListener('click', () => {
            envoyer('repondu');
            el.querySelector('[data-merci]').textContent = etat.note >= 4 ? textes.merci_positif : textes.merci_negatif;
            etape(3);
            setTimeout(() => modal.hide(), 2600);
        });
        el.querySelectorAll('[data-satisfaction-plus-tard]').forEach((b) => b.addEventListener('click', () => { envoyer('plus_tard'); modal.hide(); }));
        el.querySelector('[data-satisfaction-jamais]').addEventListener('click', () => { envoyer('jamais'); modal.hide(); });

        // Laisser lire le message de réussite, puis proposer — sauf si une autre fenêtre est ouverte ou qu'une saisie est en cours.
        let essais = 0;
        function proposer() {
            const occupe = document.querySelector('.modal.show') || document.querySelector('.swal2-container')
                || (document.activeElement && document.activeElement.matches('input, textarea, select, [contenteditable]'));
            if (occupe) {
                if (++essais < 10) setTimeout(proposer, 3000);
                return;
            }
            modal.show();
        }
        window.addEventListener('load', () => setTimeout(proposer, 2000));
    })();
</script>
