@extends('layouts.app')

@section('content')
<style>
    .appels-theme-btn {
        background-color: var(--theme-accent) !important;
        border-color: var(--theme-accent) !important;
        color: var(--text-on-accent, #fff) !important;
    }

    .appels-theme-btn i,
    .appels-theme-btn span {
        color: inherit !important;
    }

    .appels-back-btn {
        color: var(--theme-accent) !important;
        border-color: var(--theme-accent) !important;
        background: transparent !important;
    }

    .appels-back-btn:hover {
        background-color: var(--theme-accent) !important;
        color: var(--text-on-accent, #fff) !important;
    }
</style>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('appels-epreuves.index') }}" class="btn btn-sm appels-back-btn" title="Retour">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="fw-bold mb-0">Nouvel appel d'épreuve</h5>
</div>

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Appels d'épreuves</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('appels-epreuves.index') }}">Historique</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nouvel appel</li>
            </ol>
        </nav>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
@endif

<div class="card theme-card shadow-sm mb-4">
    <div class="card-header theme-header">
        <h5 class="fw-bold mb-0"><i class="bi bi-ui-checks-grid me-2"></i>Préparer l'appel</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('appels-epreuves.create') }}" class="row g-3" data-auto-filter="true">
            <div class="col-md-6">
                <label class="form-label fw-bold">Classe</label>
                <select name="id_classe" class="form-select" required>
                    <option value="">Choisir</option>
                    @foreach($classes as $classe)
                        <option value="{{ $classe->id_classe }}" @selected($selectedClasse == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Année scolaire</label>
                <select name="id_annee_scolaire" class="form-select" required>
                    <option value="">Choisir</option>
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id_anneeScolaire }}" @selected($selectedAnnee == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

@if($selectedClasse && $selectedAnnee)
<form method="POST" action="{{ route('appels-epreuves.store') }}">
    @csrf
    <input type="hidden" name="id_classe" value="{{ $selectedClasse }}">
    <input type="hidden" name="id_annee_scolaire" value="{{ $selectedAnnee }}">

    <div class="card theme-card shadow-sm mb-4">
        <div class="card-header theme-header">
            <h5 class="fw-bold mb-0"><i class="bi bi-pencil-square me-2"></i>Informations de l'épreuve</h5>
        </div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">Matière</label>
                <select name="id_matiere" class="form-select" required>
                    <option value="">Choisir</option>
                    @foreach($matieres as $matiere)
                        <option value="{{ $matiere->id_matiere }}" @selected(old('id_matiere') == $matiere->id_matiere)>{{ $matiere->nom_matiere }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Période</label>
                <select name="id_trimestre" class="form-select" required>
                    <option value="">Choisir</option>
                    @foreach($trimestres as $trimestre)
                        <option value="{{ $trimestre->id_trimestre }}" @selected(old('id_trimestre') == $trimestre->id_trimestre)>{{ $trimestre->nom_trimestre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Date</label>
                <input type="date" name="date" class="form-control" value="{{ old('date', now()->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Libellé</label>
                <input name="libelle" class="form-control" value="{{ old('libelle') }}" placeholder="Devoir, composition..." required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Début</label>
                <input type="time" name="heure_debut" class="form-control" value="{{ old('heure_debut', '08:00') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Fin</label>
                <input type="time" name="heure_fin" class="form-control" value="{{ old('heure_fin', '10:00') }}" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="notifier_parent" value="1" id="notifier-parent" checked>
                    <label class="form-check-label fw-bold" for="notifier-parent">Notifier</label>
                </div>
            </div>
        </div>
    </div>

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Appel des élèves</h5>
            <button class="btn btn-primary shadow-sm" type="submit"><i class="bi bi-check2-circle me-1"></i>Enregistrer</button>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th style="width: 280px;">Statut</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($eleves as $eleve)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $eleve->nom_eleve }} {{ $eleve->prenom_eleve }}</div>
                            <small class="text-muted">{{ $eleve->matricule }}</small>
                        </td>
                        <td>
                            <select name="statuts[{{ $eleve->id_eleve }}]" class="form-select" required>
                                @foreach($statuts as $statut)
                                    <option value="{{ $statut->id_controle }}">{{ $statut->type_controle }}{{ abs((float) $statut->penalite_conduite) > 0 ? ' (-'.abs((float) $statut->penalite_conduite).' conduite)' : '' }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center text-muted py-4">Aucun élève actif trouvé pour cette classe et cette année.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end">
            <button class="btn btn-primary shadow-sm px-4" type="submit"><i class="bi bi-check2-circle me-1"></i>Enregistrer</button>
        </div>
    </div>
</form>

{{-- ── Overlay de progression envoi mail (même style que bulletins) ── --}}
<div id="kn-notify-overlay" style="
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(10,25,14,.88); backdrop-filter:blur(4px);
    align-items:center; justify-content:center; flex-direction:column; gap:0;">

    <div style="background:#112a1b; border:1px solid #1e4330; border-radius:18px;
                padding:32px 40px 28px; width:min(92vw,440px); text-align:center;">

        {{-- Spinner SVG tri-couleur identique au bulletin --}}
        <svg style="width:80px;height:80px;display:block;margin:0 auto 20px;" viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
            <style>
                @keyframes kn-cw  { to { transform: rotate( 360deg); } }
                @keyframes kn-ccw { to { transform: rotate(-360deg); } }
                .kn-arc-g { animation: kn-cw  1.6s linear infinite; transform-origin:70px 70px; }
                .kn-arc-a { animation: kn-ccw 1.1s linear infinite; transform-origin:70px 70px; }
                .kn-arc-r { animation: kn-cw  0.7s linear infinite; transform-origin:70px 70px; }
            </style>
            <circle class="kn-arc-g" cx="70" cy="70" r="60"
                fill="none" stroke="#16a34a" stroke-width="12"
                stroke-dasharray="265 112" stroke-linecap="round"/>
            <circle class="kn-arc-a" cx="70" cy="70" r="46"
                fill="none" stroke="#f59e0b" stroke-width="12"
                stroke-dasharray="185 103" stroke-linecap="round"/>
            <circle class="kn-arc-r" cx="70" cy="70" r="32"
                fill="none" stroke="#ef4444" stroke-width="12"
                stroke-dasharray="115 87" stroke-linecap="round"/>
            <circle cx="70" cy="70" r="18" fill="#0a3d20"/>
            <text x="70" y="75" text-anchor="middle" fill="#fff"
                font-size="12" font-weight="900"
                font-family="Arial Black, Arial, sans-serif">KN</text>
        </svg>

        <div id="kn-notify-label" style="
            font-size:14px; font-weight:700; color:#e8f5ee;
            margin-bottom:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
            Enregistrement de l'appel…
        </div>

        {{-- Barre de progression --}}
        <div style="height:13px; background:rgba(255,255,255,.08); border-radius:99px; overflow:hidden; margin-bottom:8px;">
            <div id="kn-notify-fill" style="
                height:100%; width:0%; border-radius:99px;
                background: linear-gradient(90deg,#16a34a 0%,#f59e0b 55%,#ef4444 100%);
                transition: width .45s ease; position:relative; overflow:hidden;">
                <div style="
                    position:absolute; inset:0;
                    background:linear-gradient(90deg,transparent,rgba(255,255,255,.22),transparent);
                    background-size:200% 100%;
                    animation:kn-shimmer 1.6s infinite;">
                </div>
            </div>
        </div>
        <style>
            @keyframes kn-shimmer {
                0%   { background-position:-200% 0; }
                100% { background-position: 200% 0; }
            }
        </style>

        <div style="display:flex; justify-content:space-between; font-size:11px; color:#7eaa8e;">
            <span id="kn-notify-count">Préparation…</span>
            <span id="kn-notify-pct">0%</span>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const form      = document.querySelector('form[action="{{ route('appels-epreuves.store') }}"]');
    const checkbox  = document.getElementById('notifier-parent');
    const overlay   = document.getElementById('kn-notify-overlay');
    const fill      = document.getElementById('kn-notify-fill');
    const label     = document.getElementById('kn-notify-label');
    const count     = document.getElementById('kn-notify-count');
    const pct       = document.getElementById('kn-notify-pct');

    if (!form) return;

    function setProgress(p, msg, detail) {
        p = Math.min(100, Math.max(0, p));
        fill.style.width  = p + '%';
        pct.textContent   = Math.round(p) + '%';
        if (msg)    label.textContent  = msg;
        if (detail) count.textContent  = detail;
    }

    form.addEventListener('submit', function () {
        const notify   = checkbox && checkbox.checked;
        const nbEleves = document.querySelectorAll('tbody tr[data-student], tbody tr:has(select[name^="statuts"])').length
                      || document.querySelectorAll('select[name^="statuts"]').length;

        overlay.style.display = 'flex';
        setProgress(5, 'Enregistrement de l\'appel…', 'Sauvegarde en base de données…');

        if (!notify || nbEleves === 0) {
            // Juste l'enregistrement, pas d'emails
            let p = 5;
            const t = setInterval(function () {
                p = Math.min(90, p + 15);
                setProgress(p, 'Enregistrement en cours…', '');
                if (p >= 90) clearInterval(t);
            }, 200);
            return;
        }

        // Simulation de progression : enregistrement (0→30%) + emails (30→95%)
        // Durée estimée : ~0.4s/email + 1s pour la BDD
        const emailDuration = Math.max(2000, nbEleves * 400);
        const startTime     = Date.now();

        function tick() {
            const elapsed = Date.now() - startTime;
            const ratio   = Math.min(1, elapsed / emailDuration);

            if (ratio < 0.15) {
                // Phase 1 : sauvegarde BDD (0 → 30%)
                const p = ratio / 0.15 * 30;
                setProgress(p, 'Enregistrement de l\'appel…', 'Sauvegarde en base de données…');
            } else {
                // Phase 2 : envoi emails (30 → 95%)
                const emailRatio  = (ratio - 0.15) / 0.85;
                const p           = 30 + emailRatio * 65;
                const nbDone      = Math.round(emailRatio * nbEleves);
                setProgress(p,
                    'Envoi des notifications aux parents…',
                    nbDone + ' / ' + nbEleves + ' e-mail' + (nbEleves > 1 ? 's' : '') + ' envoyé' + (nbDone > 1 ? 's' : ''));
            }

            if (ratio < 1) requestAnimationFrame(tick);
        }

        requestAnimationFrame(tick);
    });
})();
</script>
@endpush
@endif
@endsection
