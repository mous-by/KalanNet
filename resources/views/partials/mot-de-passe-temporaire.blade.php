{{-- Mot de passe temporaire (création ou réinitialisation d'un compte) : affiché une seule fois. --}}
@if(session('mot_de_passe_temporaire'))
    <div class="modal fade" id="motDePasseTemporaireModal" tabindex="-1" aria-labelledby="motDePasseTemporaireTitre" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="motDePasseTemporaireTitre"><i class="bi bi-key me-2"></i>{{ __('securite.modal_titre') }}</h5>
                </div>
                <div class="modal-body p-4">
                    <p>{!! __('securite.modal_texte', ['nom' => '<strong>' . e(session('mot_de_passe_temporaire_pour')) . '</strong>']) !!}</p>
                    <div class="input-group">
                        <input type="text" class="form-control fw-bold fs-5 text-center" id="mot-de-passe-temporaire-valeur" value="{{ session('mot_de_passe_temporaire') }}" readonly style="letter-spacing: .08em; font-family: ui-monospace, monospace;">
                        <button type="button" class="btn btn-primary" id="copier-mot-de-passe-temporaire" title="{{ __('securite.copier') }}" aria-label="{{ __('securite.copier') }}"><i class="bi bi-clipboard"></i></button>
                    </div>
                    <div class="small text-success mt-2 d-none" id="mot-de-passe-temporaire-copie">{{ __('securite.copie') }}</div>
                    <p class="small fw-semibold mt-3 mb-1"><i class="bi bi-hourglass-split me-1"></i>{{ __('securite.modal_duree', ['heures' => \App\Support\MotDePasse::DUREE_TEMPORAIRE_HEURES, 'date' => \App\Support\MotDePasse::finValidite()]) }}</p>
                    <p class="small text-muted mb-0">{{ __('securite.modal_aide') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ __('securite.note') }}</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('motDePasseTemporaireModal');
            if (window.bootstrap) new bootstrap.Modal(modal).show();
            document.getElementById('copier-mot-de-passe-temporaire').addEventListener('click', function () {
                const champ = document.getElementById('mot-de-passe-temporaire-valeur');
                const confirmer = () => document.getElementById('mot-de-passe-temporaire-copie').classList.remove('d-none');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(champ.value).then(confirmer);
                } else {
                    champ.select();
                    document.execCommand('copy');
                    confirmer();
                }
            });
        });
    </script>
@endif
