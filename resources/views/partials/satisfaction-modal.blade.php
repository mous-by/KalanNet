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
            <button type="button" class="btn-close satisfaction-fermer" data-satisfaction-plus-tard aria-label="Fermer"></button>

            {{-- Étape 1 : la note --}}
            <div class="satisfaction-etape" data-etape="1">
                <div class="satisfaction-icone"><i class="bi bi-emoji-smile"></i></div>
                <h5 class="fw-bold mb-1" id="satisfactionTitre">C'est fait ! Un petit avis ?</h5>
                <p class="text-muted small mb-3">Comment trouvez-vous KalanNet en ce moment ? Cela nous aide à l'améliorer pour votre école.</p>
                <div class="satisfaction-notes" role="radiogroup" aria-label="Votre note">
                    @foreach(Satisfaction::LIBELLES_NOTE as $note => $libelle)
                        <button type="button" class="satisfaction-note" data-note="{{ $note }}" role="radio" aria-checked="false" title="{{ $libelle }}">
                            <span class="satisfaction-visage">{{ ['😞', '🙁', '😐', '🙂', '😍'][$note - 1] }}</span>
                            <span class="satisfaction-libelle">{{ $libelle }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="d-flex justify-content-between mt-3 small">
                    <button type="button" class="btn btn-link btn-sm text-muted p-0" data-satisfaction-jamais>Ne plus me demander</button>
                    <button type="button" class="btn btn-link btn-sm text-muted p-0" data-satisfaction-plus-tard>Plus tard</button>
                </div>
            </div>

            {{-- Étape 2 : détails selon la note --}}
            <div class="satisfaction-etape d-none" data-etape="2">
                <div class="satisfaction-choisie mb-2"></div>
                <h6 class="fw-bold mb-2" data-question-positive>Qu'appréciez-vous le plus ?</h6>
                <h6 class="fw-bold mb-2 d-none" data-question-negative>Qu'est-ce qui vous gêne ?</h6>
                <div class="satisfaction-points mb-3" data-points-positifs>
                    @foreach(Satisfaction::POINTS_FORTS as $cle => $libelle)
                        <button type="button" class="satisfaction-point" data-point="{{ $cle }}">{{ $libelle }}</button>
                    @endforeach
                </div>
                <div class="satisfaction-points mb-3 d-none" data-points-negatifs>
                    @foreach(Satisfaction::POINTS_FAIBLES as $cle => $libelle)
                        <button type="button" class="satisfaction-point" data-point="{{ $cle }}">{{ $libelle }}</button>
                    @endforeach
                </div>
                <textarea class="form-control mb-2" rows="3" maxlength="1000" data-commentaire placeholder="Un mot de plus ? (facultatif)"></textarea>
                <div class="form-check small mb-3 d-none" data-accord-bloc>
                    <input class="form-check-input" type="checkbox" id="satisfactionAccord" data-accord>
                    <label class="form-check-label" for="satisfactionAccord">
                        J'accepte que cet avis soit publié sur le site de Computer Service Barry, avec mon nom, ma fonction et mon école.
                    </label>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-link btn-sm text-muted p-0" data-retour>Changer ma note</button>
                    <button type="button" class="btn satisfaction-envoyer" data-envoyer>Envoyer mon avis</button>
                </div>
            </div>

            {{-- Étape 3 : merci --}}
            <div class="satisfaction-etape d-none text-center" data-etape="3">
                <div class="satisfaction-icone"><i class="bi bi-heart-fill"></i></div>
                <h5 class="fw-bold mb-1">Merci beaucoup !</h5>
                <p class="text-muted small mb-0" data-merci>Votre avis a bien été transmis à l'équipe de KalanNet.</p>
            </div>
        </div>
    </div>
</div>

<style>
    .satisfaction-carte { border-radius: 18px; padding: 26px 24px 20px; box-shadow: 0 24px 60px rgba(15, 23, 42, .25); border-top: 4px solid var(--theme-accent, #831843) !important; }
    .satisfaction-fermer { position: absolute; top: 14px; right: 14px; }
    .satisfaction-etape { text-align: center; }
    .satisfaction-etape[data-etape="2"] { text-align: left; }
    .satisfaction-icone { width: 52px; height: 52px; margin: 0 auto 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--theme-accent, #831843); background: var(--accent-light, #fce7f3); }
    .satisfaction-notes { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; }
    .satisfaction-note { border: 1px solid var(--border-color, #e5e7eb); background: var(--bg-card, #fff); border-radius: 12px; padding: 10px 2px 8px; display: flex; flex-direction: column; align-items: center; gap: 4px; transition: transform .12s, border-color .12s, background .12s; min-height: 76px; }
    .satisfaction-note:hover, .satisfaction-note:focus-visible { transform: translateY(-2px); border-color: var(--theme-accent, #831843); background: var(--accent-light, #fdf2f8); outline: none; }
    .satisfaction-visage { font-size: 1.7rem; line-height: 1; }
    .satisfaction-libelle { font-size: .68rem; font-weight: 600; color: var(--text-muted, #6b7280); line-height: 1.1; }
    .satisfaction-choisie { font-weight: 700; color: var(--theme-accent, #831843); }
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
        const libelles = @json(Satisfaction::LIBELLES_NOTE);

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
            el.querySelector('[data-commentaire]').placeholder = positif ? 'Un mot sur votre expérience ? (facultatif)' : 'Dites-nous ce qu\'il faudrait améliorer (facultatif)';
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
            el.querySelector('[data-merci]').textContent = etat.note >= 4
                ? 'Votre avis nous fait très plaisir. Il a bien été transmis à l\'équipe de KalanNet.'
                : 'Merci de votre franchise : l\'équipe de KalanNet va étudier vos remarques.';
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
