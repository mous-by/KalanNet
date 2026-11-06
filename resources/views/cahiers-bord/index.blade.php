@extends('layouts.app')

@section('content')
    @php
        $C = \App\Support\CahierBordDonnees::class;
        $val = fn ($champ) => old($champ, $donnees[$champ] ?? null);
        $jardin = $cycle === 'jardin';
        $nouveaux = $jardin ? 'Nouveaux' : 'Pass.';
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Cahier de bord</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $annee->annee }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(count($cycles) > 1)
        <ul class="nav nav-tabs mb-3">
            @foreach($cycles as $c)
                <li class="nav-item">
                    <a class="nav-link fw-semibold {{ $cycle === $c ? 'active' : '' }}" @if($cycle === $c) aria-current="page" @endif href="{{ route('cahier-bord.index', ['annee' => $annee->id_anneeScolaire, 'cycle' => $c]) }}">{{ $C::CYCLES[$c] }}</a>
                </li>
            @endforeach
        </ul>
    @endif

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="alert {{ $capSurKalanNet ? 'alert-info' : 'alert-warning' }} border-0 border-start border-4">
        @if($capSurKalanNet)
            <i class="bi bi-send me-1"></i>Votre CAP utilise KalanNet : vous pouvez lui envoyer ce cahier de bord directement. Vous pouvez aussi le télécharger à tout moment.
        @else
            <i class="bi bi-printer me-1"></i>Votre CAP n'utilise pas encore KalanNet : <strong>téléchargez le cahier de bord rempli, imprimez-le et remettez-le à votre CAP</strong>, puis marquez-le comme remis.
        @endif
    </div>

    @if($fiche?->remis())
        <div class="alert alert-success border-0 border-start border-success border-4">
            <i class="bi bi-check-circle me-1"></i>{{ $fiche->statut === 'envoye' ? 'Envoyé au CAP' : 'Remis au CAP' }} le {{ $fiche->envoye_at?->format('d/m/Y à H:i') }}.
            @if($fiche->aModificationsNonEnvoyees())
                <strong>Vous avez modifié la fiche depuis : remettez-la de nouveau pour transmettre ces changements.</strong>
            @endif
        </div>
    @endif

    @if($nonReconnues !== [])
        <div class="alert alert-warning border-0 border-start border-warning border-4"><i class="bi bi-exclamation-triangle me-1"></i>Classes non comptées dans les effectifs, leur année ou section n'étant pas reconnue : {{ implode(', ', $nonReconnues) }}. Corrigez leur nom ou ajoutez leurs élèves à la main.</div>
    @endif

    <form method="GET" action="{{ route('cahier-bord.index') }}" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="cycle" value="{{ $cycle }}">
        <div class="col-sm-5 col-md-3">
            <label class="form-label small fw-bold text-uppercase" for="annee">Année scolaire</label>
            <select name="annee" id="annee" class="form-select" onchange="this.form.submit()">
                @foreach($annees as $a)
                    <option value="{{ $a->id_anneeScolaire }}" @selected($a->id_anneeScolaire === $annee->id_anneeScolaire)>{{ $a->annee }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <form method="POST" action="{{ route('cahier-bord.store', ['annee' => $annee->id_anneeScolaire, 'cycle' => $cycle]) }}" id="cahier-form">
        @csrf
        <input type="hidden" name="enseignants_json" id="enseignants_json">

        <div class="card theme-card shadow-sm mb-3">
            <div class="card-header theme-header"><h6 class="mb-0 fw-bold">{{ $jardin ? "Jardin d'enfants" : 'École fondamentale' }} : {{ $ecole->nomEcole }} ({{ $C::CYCLES[$cycle] }})</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach(['directeur' => $jardin ? 'Dirigé par' : 'Dirigée par', 'depuis' => 'Depuis', 'contact' => 'Contact', 'commune' => 'Commune de', 'cap' => 'CAP de', 'academie' => "Académie d'enseignement de"] as $champ => $libelle)
                        <div class="col-md-4">
                            <label class="form-label small fw-bold" for="{{ $champ }}">{{ $libelle }}</label>
                            <input type="text" name="{{ $champ }}" id="{{ $champ }}" class="form-control" maxlength="{{ in_array($champ, ['contact']) ? 60 : ($champ === 'depuis' ? 30 : 150) }}" value="{{ $val($champ) }}">
                        </div>
                    @endforeach
                    <div class="col-md-4">
                        <label class="form-label small fw-bold" for="regime">Régime de recrutement</label>
                        <select name="regime" id="regime" class="form-select">
                            <option value="">— À choisir —</option>
                            @foreach(\App\Support\RapportSuccinctDonnees::RECRUTEMENTS as $n => $libelle)
                                <option value="{{ $n }}" @selected((int) $val('regime') === $n)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card theme-card shadow-sm mb-3">
            <div class="card-header theme-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="mb-0 fw-bold">I. Situation des effectifs</h6>
                <a href="{{ route('cahier-bord.index', ['annee' => $annee->id_anneeScolaire, 'cycle' => $cycle, 'recalculer' => 1]) }}" class="btn btn-sm btn-light"><i class="bi bi-arrow-repeat me-1"></i>Reprendre les chiffres de KalanNet</a>
            </div>
            <div class="card-body">
                <p class="small text-muted">Élèves actifs de {{ $annee->annee }}. {{ $jardin ? 'Anciens' : 'Redoublants' }} : ceux enregistrés comme redoublants à la réinscription, vérifiez-les. {{ $jardin ? 'Nouveaux' : 'Passants (recrutés en 1ère année)' }} : les autres.</p>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center mb-0" data-effectifs>
                        <thead>
                            <tr><th rowspan="2">{{ $jardin ? 'Section' : 'Année' }}</th><th rowspan="2">Nbre de cours</th><th colspan="2">Élèves</th><th colspan="2">{{ $jardin ? 'Anciens' : 'Redoublants' }}</th><th colspan="3">{{ $jardin ? 'Nouveaux' : 'Recrutés / passants' }}</th><th rowspan="2">Total</th></tr>
                            <tr><th>G</th><th>F</th><th>G</th><th>F</th><th>G</th><th>F</th><th>T</th></tr>
                        </thead>
                        <tbody>
                            @foreach($donnees['effectifs'] as $l)
                                <tr data-ligne>
                                    <td class="fw-semibold text-nowrap">{{ $l['libelle'] }}</td>
                                    @foreach(['groupes', 'g', 'f', 'rg', 'rf'] as $c)
                                        <td style="min-width: 72px;"><input type="number" min="0" max="99999" required class="form-control form-control-sm text-center" name="effectifs[{{ $l['cle'] }}][{{ $c }}]" data-c="{{ $c }}" value="{{ old("effectifs.{$l['cle']}.{$c}", $l[$c]) }}" aria-label="{{ $l['libelle'] }} {{ $c }}"></td>
                                    @endforeach
                                    <td data-pg></td><td data-pf></td><td class="fw-bold" data-pt></td><td class="fw-bold" data-t></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card theme-card shadow-sm mb-3">
            <div class="card-header theme-header"><h6 class="mb-0 fw-bold">II. Personnel administratif (directeur, chargé de dossiers, gardien)</h6></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size: .85rem;">
                        <thead class="text-center">
                            <tr><th>N°</th><th>Prénom &amp; nom</th><th>Date nais.</th><th>N° Mle</th><th>Classe / échelon</th><th>S.F</th><th>Fonction</th><th>Diplôme</th>
                                @foreach($C::DATES as $libelle)<th>{{ $libelle }}</th>@endforeach<th>Contact</th></tr>
                        </thead>
                        <tbody>
                            @foreach(range(0, 3) as $i)
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    @foreach($C::CHAMPS_ADMINISTRATIF as $champ)
                                        <td style="min-width: {{ $champ === 'nom' ? 170 : 105 }}px;"><input type="text" maxlength="150" class="form-control form-control-sm" name="administratifs[{{ $i }}][{{ $champ }}]" value="{{ old("administratifs.$i.$champ", $donnees['administratifs'][$i][$champ] ?? null) }}" aria-label="Personnel {{ $i + 1 }} {{ $champ }}"></td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card theme-card shadow-sm mb-3">
            <div class="card-header theme-header"><h6 class="mb-0 fw-bold">III. Salles de classe et équipements</h6></div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    @foreach($C::SALLES as $cle => $libelle)
                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-bold" for="salle-{{ $cle }}">Salles en {{ Str::lower($libelle) }}</label>
                            <input type="number" min="0" max="9999" class="form-control" name="salles[{{ $cle }}]" id="salle-{{ $cle }}" value="{{ old("salles.$cle", $donnees['salles'][$cle] ?? null) }}">
                        </div>
                    @endforeach
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-bold" for="date_creation">Date de création</label>
                        <input type="text" maxlength="30" class="form-control" name="date_creation" id="date_creation" value="{{ $val('date_creation') }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small fw-bold" for="date_ouverture">Date d'ouverture</label>
                        <input type="text" maxlength="30" class="form-control" name="date_ouverture" id="date_ouverture" value="{{ $val('date_ouverture') }}">
                    </div>
                </div>
                <p class="fw-bold small mb-1">IV. Tables-bancs</p>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered align-middle text-center mb-0">
                        <thead><tr><th></th>@foreach($C::niveaux($cycle) as $libelle)<th>{{ $libelle }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach($C::TABLES as $type => $libelle)
                                <tr>
                                    <td class="text-start text-nowrap">{{ $libelle }}</td>
                                    @foreach($C::niveaux($cycle) as $cle => $niveau)
                                        <td style="min-width: 72px;"><input type="number" min="0" max="9999" class="form-control form-control-sm text-center" name="tables[{{ $cle }}][{{ $type }}]" value="{{ old("tables.$cle.$type", $donnees['tables'][$cle][$type] ?? null) }}" aria-label="{{ $libelle }} {{ $niveau }}"></td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="row g-3">
                    @foreach($C::EQUIPEMENTS as $cle => $libelle)
                        <div class="col-6 col-md-3">
                            <label class="form-label small fw-bold" for="eq-{{ $cle }}">{{ $libelle }}</label>
                            <input type="number" min="0" max="9999" class="form-control" name="equipements[{{ $cle }}]" id="eq-{{ $cle }}" value="{{ old("equipements.$cle", $donnees['equipements'][$cle] ?? null) }}">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card theme-card shadow-sm mb-3">
            <div class="card-header theme-header"><h6 class="mb-0 fw-bold">V. Personnel enseignant : {{ count($donnees['enseignants']) }}</h6></div>
            <div class="card-body">
                <p class="small text-muted">Repris de KalanNet : nom, date de naissance, matricule, classes tenues. Les dates de carrière et l'échelon saisis cette année seront repris l'an prochain. Le PDF écrit en rouge le nom des femmes, comme le demande le modèle.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size: .85rem;">
                        <thead class="text-center">
                            <tr><th>N°</th><th>Prénom &amp; nom</th><th>Date nais.</th><th>N° Mle</th><th>Classe / échelon</th><th>S.F</th><th>Fonction</th><th>Spécialité</th><th>{{ $jardin ? 'Sections tenues' : 'Classes tenues' }}</th><th>Diplôme</th>
                                @foreach($C::DATES as $libelle)<th>{{ $libelle }}</th>@endforeach<th>Contact</th></tr>
                        </thead>
                        <tbody>
                            @forelse($donnees['enseignants'] as $e)
                                <tr data-enseignant="{{ $e['id'] }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="text-nowrap fw-semibold {{ $e['femme'] ? 'text-danger' : '' }}">{{ $e['nom'] }}</td>
                                    <td class="text-nowrap">{{ $e['naissance'] }}</td>
                                    <td class="text-nowrap">{{ $e['matricule'] }}</td>
                                    @foreach(['echelon', 'sf', 'fonction', 'specialite', 'classes', 'diplome', 'recrutement', 'integration', 'titularisation', 'arrivee_cap', 'dernier_poste', 'arrivee_ecole', 'contact'] as $champ)
                                        <td style="min-width: {{ $champ === 'classes' ? 150 : 105 }}px;"><input type="text" maxlength="150" class="form-control form-control-sm" data-c="{{ $champ }}" value="{{ $e[$champ] ?? '' }}" aria-label="{{ $e['nom'] }} {{ $champ }}"></td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="20" class="text-center text-muted py-3">Aucun enseignant pour ce cycle : attribuez les classes à vos enseignants dans KalanNet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm form-actions-sticky d-flex flex-row flex-wrap justify-content-between align-items-center gap-2">
            <div class="small text-muted">
                @if($manquants !== [])
                    <i class="bi bi-exclamation-circle me-1"></i>À compléter avant la remise : {{ implode(', ', $manquants) }}.
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" name="action" value="enregistrer" class="btn btn-outline-primary">Enregistrer</button>
                <button type="submit" name="action" value="telecharger" class="btn btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Télécharger le PDF</button>
                <button type="submit" name="action" value="remettre" class="btn btn-success fw-semibold">
                    @if($capSurKalanNet)
                        <i class="bi bi-send me-1"></i>{{ $fiche?->remis() ? 'Renvoyer au CAP' : 'Envoyer au CAP' }}
                    @else
                        <i class="bi bi-check2-circle me-1"></i>{{ $fiche?->remis() ? 'Marquer de nouveau comme remis' : 'Marquer comme remis au CAP' }}
                    @endif
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('cahier-form');
            const nombre = (el) => parseInt(el?.value, 10) || 0;
            // Totaux recalculés pendant la saisie : nouveaux ou passants = élèves - redoublants.
            const calculer = () => form.querySelectorAll('[data-effectifs] [data-ligne]').forEach((ligne) => {
                const v = (c) => nombre(ligne.querySelector(`[data-c="${c}"]`));
                const pg = Math.max(v('g') - v('rg'), 0);
                const pf = Math.max(v('f') - v('rf'), 0);
                ligne.querySelector('[data-pg]').textContent = pg;
                ligne.querySelector('[data-pf]').textContent = pf;
                ligne.querySelector('[data-pt]').textContent = pg + pf;
                ligne.querySelector('[data-t]').textContent = v('g') + v('f');
            });
            form.addEventListener('input', calculer);
            calculer();

            // Les enseignants partent dans un seul champ : une grande école ne dépasse pas la limite de champs de PHP.
            form.addEventListener('submit', () => {
                const enseignants = {};
                form.querySelectorAll('[data-enseignant]').forEach((ligne) => {
                    const valeurs = {};
                    ligne.querySelectorAll('[data-c]').forEach((champ) => { valeurs[champ.dataset.c] = champ.value === '' ? null : champ.value; });
                    enseignants[ligne.dataset.enseignant] = valeurs;
                });
                document.getElementById('enseignants_json').value = JSON.stringify(enseignants);
            });
        })();
    </script>
@endpush
