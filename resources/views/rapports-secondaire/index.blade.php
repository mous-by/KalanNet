@extends('layouts.app')

@section('content')
    @php $D = \App\Support\RapportSecondaireDefinitions::class; @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ $D::TYPES[$type] }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $annee->annee }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('rapports-succincts._onglets', ['actif' => $type])

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="alert {{ $surKalanNet ? 'alert-info' : 'alert-warning' }} border-0 border-start border-4">
        @if($surKalanNet)
            <i class="bi bi-send me-1"></i>Votre académie utilise KalanNet : vous pouvez lui envoyer ce rapport directement. Vous pouvez aussi le télécharger à tout moment.
        @else
            <i class="bi bi-printer me-1"></i>Votre académie n'utilise pas encore KalanNet : <strong>téléchargez le rapport rempli, imprimez-le et remettez-le à votre académie</strong>, puis marquez-le comme remis.
        @endif
    </div>

    @if($rapport?->remis())
        <div class="alert alert-success border-0 border-start border-success border-4">
            <i class="bi bi-check-circle me-1"></i>{{ $rapport->statut === 'envoye' ? "Envoyé à l'académie" : "Remis à l'académie" }} le {{ $rapport->envoye_at?->format('d/m/Y à H:i') }}.
            @if($rapport->aModificationsNonEnvoyees())
                <strong>Vous avez modifié le rapport depuis : remettez-le de nouveau pour transmettre ces changements.</strong>
            @endif
        </div>
    @endif
    @if($sansDate > 0)
        <div class="alert alert-warning border-0 border-start border-warning border-4"><i class="bi bi-exclamation-triangle me-1"></i>{{ $sansDate }} élève(s) sans date de naissance : absents du tableau des âges (T7). Complétez leur fiche, puis reprenez les chiffres de KalanNet.</div>
    @endif

    <form method="GET" action="{{ route('rapport-secondaire.index', $type) }}" class="row g-2 align-items-end mb-3">
        <div class="col-sm-5 col-md-3">
            <label class="form-label small fw-bold text-uppercase" for="annee">Année scolaire</label>
            <select name="annee" id="annee" class="form-select" onchange="this.form.submit()">
                @foreach($annees as $a)
                    <option value="{{ $a->id_anneeScolaire }}" @selected($a->id_anneeScolaire === $annee->id_anneeScolaire)>{{ $a->annee }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto ms-auto">
            <a href="{{ route('rapport-secondaire.index', [$type, 'annee' => $annee->id_anneeScolaire, 'recalculer' => 1]) }}" class="btn btn-light"><i class="bi bi-arrow-repeat me-1"></i>Reprendre les chiffres de KalanNet</a>
        </div>
    </form>

    <form method="POST" action="{{ route('rapport-secondaire.store', [$type, 'annee' => $annee->id_anneeScolaire]) }}" id="rapport-secondaire-form">
        @csrf
        <input type="hidden" name="tableaux_json" id="tableaux_json">

        <div class="card theme-card shadow-sm mb-3">
            <div class="card-header theme-header"><h6 class="mb-0 fw-bold">T0 : Informations générales</h6></div>
            <div class="card-body">
                <p class="small text-muted">Effectifs, groupes pédagogiques et enseignants sont repris de KalanNet. Ce qui ne change pas d'une année à l'autre (création, arrêtés, superficie…) est repris de votre rapport précédent.</p>
                <div class="row g-3">
                    @foreach($infos as $champ => $libelle)
                        <div class="col-sm-6 col-lg-4">
                            <label class="form-label small fw-bold" for="info-{{ $champ }}">{{ $libelle }}</label>
                            <input type="text" maxlength="255" class="form-control" name="infos[{{ $champ }}]" id="info-{{ $champ }}" value="{{ old("infos.$champ", $donnees['infos'][$champ] ?? null) }}">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @foreach($tableaux as $tableau)
            @include('rapports-secondaire._tableau', ['tableau' => $tableau])
        @endforeach

        <div class="card shadow-sm form-actions-sticky d-flex flex-row flex-wrap justify-content-between align-items-center gap-2">
            <div class="small text-muted">
                @if($manquants !== [])
                    <i class="bi bi-exclamation-circle me-1"></i>À compléter avant la remise : {{ implode(', ', $manquants) }}.
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" name="action" value="enregistrer" class="btn btn-outline-primary">Enregistrer</button>
                <button type="submit" name="action" value="telecharger" class="btn btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Télécharger le PDF</button>
                <button type="submit" name="action" value="excel" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</button>
                <button type="submit" name="action" value="remettre" class="btn btn-success fw-semibold">
                    @if($surKalanNet)
                        <i class="bi bi-send me-1"></i>{{ $rapport?->remis() ? "Renvoyer à l'académie" : "Envoyer à l'académie" }}
                    @else
                        <i class="bi bi-check2-circle me-1"></i>{{ $rapport?->remis() ? 'Marquer de nouveau comme remis' : "Marquer comme remis à l'académie" }}
                    @endif
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('rapport-secondaire-form');
            const nombre = (el) => parseInt(el.value, 10) || 0;
            // Totaux de chaque tableau, recalculés pendant la saisie.
            const totaux = () => form.querySelectorAll('[data-totaux]').forEach((table) => {
                const lignes = [...table.querySelectorAll('[data-ligne]')];
                const parColonne = [];
                lignes.forEach((ligne) => {
                    const cases = [...ligne.querySelectorAll('[data-t]')];
                    cases.forEach((c, i) => { parColonne[i] = (parColonne[i] || 0) + nombre(c); });
                    const total = ligne.querySelector('[data-total-ligne]');
                    if (total) total.textContent = cases.reduce((s, c) => s + nombre(c), 0);
                });
                table.querySelectorAll('[data-total-colonne]').forEach((c) => { c.textContent = parColonne[c.dataset.totalColonne] || 0; });
                const general = table.querySelector('[data-total-general]');
                if (general) general.textContent = parColonne.reduce((s, n) => s + (n || 0), 0);
            });
            form.addEventListener('input', totaux);
            totaux();

            // Tous les tableaux partent dans un seul champ : la limite de champs de PHP ne peut rien tronquer.
            form.addEventListener('submit', () => {
                const t = {};
                form.querySelectorAll('[data-t]').forEach((champ) => {
                    if (champ.value === '') return;
                    const [code, ligne, colonne] = champ.dataset.t.split('|');
                    ((t[code] ??= {})[ligne] ??= {})[colonne] = champ.value;
                });
                document.getElementById('tableaux_json').value = JSON.stringify(t);
            });
        })();
    </script>
@endpush
