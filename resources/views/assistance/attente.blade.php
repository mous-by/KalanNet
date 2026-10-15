@extends('layouts.app')

@section('title', __('assistance.attente_titre'))

@section('content')
    @php
        $textesAttente = [
            'accepte' => __('assistance.attente_accepte'),
            'refuse' => __('assistance.attente_refuse'),
            'expire' => __('assistance.attente_expire'),
            'autre_compte' => __('assistance.autre_compte'),
        ];
    @endphp
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card theme-card shadow-sm text-center">
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success border-0 text-start"><i class="bx bx-check-circle me-1"></i>{{ session('success') }}</div>
                    @endif
                    <i class="bx bx-support text-danger" style="font-size: 3rem;"></i>
                    <h4 class="fw-bold mt-2">{{ __('assistance.attente_titre') }}</h4>
                    <p class="text-muted">{{ __('assistance.attente_texte', ['nom' => $compte]) }}</p>
                    <p class="mb-1 small text-muted">{{ __('assistance.attente_restant') }}</p>
                    <div id="assistance-compteur" class="display-6 fw-bold">{{ sprintf('%d:%02d', intdiv($restant, 60), $restant % 60) }}</div>
                    <div class="spinner-border text-danger mt-3" id="assistance-attente" role="status"></div>
                    <div class="alert mt-3 d-none" id="assistance-resultat"></div>
                    <div class="d-flex justify-content-center gap-2 mt-3 d-none" id="assistance-actions">
                        <a href="{{ route('configuration.utilisateurs') }}" class="btn btn-danger">{{ __('assistance.relancer') }}</a>
                    </div>
                    <form method="POST" action="{{ route('assistance.ouvrir', $acces->id) }}" id="assistance-ouvrir">@csrf</form>
                    <a href="{{ route('configuration.utilisateurs') }}" class="d-block small mt-3">{{ __('assistance.retour_liste') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const textes = @json($textesAttente);
            const url = @json(route('assistance.statut', $acces->id));
            const compteur = document.getElementById('assistance-compteur');
            let restant = {{ (int) $restant }};
            let fini = false;

            function afficher(seconds) {
                compteur.textContent = Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0');
            }
            function terminer(statut) {
                fini = true;
                document.getElementById('assistance-attente').classList.add('d-none');
                const resultat = document.getElementById('assistance-resultat');
                resultat.classList.remove('d-none');
                resultat.classList.add(statut === 'accepte' ? 'alert-success' : 'alert-warning');
                resultat.textContent = textes[statut] || textes.expire;
                if (statut === 'accepte') {
                    document.getElementById('assistance-ouvrir').submit();
                } else {
                    document.getElementById('assistance-actions').classList.remove('d-none');
                }
            }
            async function verifier() {
                if (fini) return;
                try {
                    const reponse = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (reponse.status === 401 || reponse.redirected) { terminer('autre_compte'); return; }
                    const donnees = await reponse.json();
                    restant = donnees.restant;
                    afficher(restant);
                    if (donnees.statut !== 'en_attente') terminer(donnees.statut);
                } catch (e) { /* réseau : on réessaie */ }
            }
            setInterval(() => { if (!fini && restant > 0) { restant--; afficher(restant); } }, 1000);
            setInterval(verifier, 3000);
            verifier();
        })();
    </script>
@endpush
