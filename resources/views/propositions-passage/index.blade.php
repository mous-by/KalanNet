@extends('layouts.app')

@section('content')
    @php
        $C = \App\Support\PropositionPassageDonnees::class;
        $statuts = ['envoye' => ['En attente du CAP', 'bg-info text-dark'], 'remis' => ['Remise sur papier', 'bg-secondary'], 'validee' => ['Décision du CAP reçue', 'bg-success'], 'brouillon' => ['Brouillon', 'bg-light text-dark border']];
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Proposition de passage</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $annee->annee }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="alert {{ $capSurKalanNet ? 'alert-info' : 'alert-warning' }} border-0 border-start border-4">
        @if($capSurKalanNet)
            <i class="bi bi-send me-1"></i>Votre CAP utilise KalanNet : envoyez-lui chaque classe, il rendra sa décision ici. Vous pouvez aussi télécharger la fiche à tout moment.
        @else
            <i class="bi bi-printer me-1"></i>Votre CAP n'utilise pas encore KalanNet : <strong>téléchargez la fiche de chaque classe, imprimez-la et remettez-la à votre CAP</strong>, puis marquez-la comme remise.
        @endif
    </div>

    <form method="GET" action="{{ route('proposition-passage.index') }}" class="row g-2 align-items-end mb-3">
        <div class="col-sm-4 col-md-3">
            <label class="form-label small fw-bold text-uppercase" for="annee">Année scolaire</label>
            <select name="annee" id="annee" class="form-select" onchange="this.form.submit()">
                @foreach($annees as $a)
                    <option value="{{ $a->id_anneeScolaire }}" @selected($a->id_anneeScolaire === $annee->id_anneeScolaire)>{{ $a->annee }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-5 col-md-4">
            <label class="form-label small fw-bold text-uppercase" for="classe-choix">Classe</label>
            <select name="classe" id="classe-choix" class="form-select" onchange="this.form.submit()">
                <option value="">— Choisir une classe —</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id_classe }}" @selected($classe?->id_classe === $c->id_classe)>{{ $c->nom_classe }}{{ isset($fiches[$c->id_classe]) ? ' — '.$statuts[$fiches[$c->id_classe]->statut][0] : '' }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @if(!$classe)
        <div class="card theme-card shadow-sm"><div class="card-body text-muted">Choisissez une classe : la liste de ses élèves se prépare automatiquement avec leur moyenne annuelle et une proposition.</div></div>
    @else
        @if($fiche?->remise())
            <div class="alert alert-success border-0 border-start border-success border-4">
                <span class="badge {{ $statuts[$fiche->statut][1] }}">{{ $statuts[$fiche->statut][0] }}</span>
                {{ $fiche->validee() ? 'Le '.$fiche->valide_at?->format('d/m/Y').' : la réinscription reprend les décisions du CAP pour cette classe.' : 'Remise le '.$fiche->envoye_at?->format('d/m/Y').'.' }}
                @if($fiche->aModificationsNonEnvoyees())
                    <strong>Vous avez modifié la fiche depuis : remettez-la de nouveau pour transmettre ces changements.</strong>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('proposition-passage.store', ['classe' => $classe->id_classe, 'annee' => $annee->id_anneeScolaire]) }}" id="proposition-form">
            @csrf
            <input type="hidden" name="lignes_json" id="lignes_json">

            <div class="card theme-card shadow-sm mb-3">
                <div class="card-header theme-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="mb-0 fw-bold">{{ $classe->nom_classe }} : {{ count($lignes) }} élève(s)</h6>
                    <a href="{{ route('proposition-passage.index', ['classe' => $classe->id_classe, 'annee' => $annee->id_anneeScolaire, 'recalculer' => 1]) }}" class="btn btn-sm btn-light"><i class="bi bi-arrow-repeat me-1"></i>Reprendre les chiffres de KalanNet</a>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold" for="directeur">Le Directeur d'École</label>
                            <input type="text" name="directeur" id="directeur" class="form-control" maxlength="150" value="{{ $directeur }}">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-bold" for="modele">Format de la fiche demandé par votre CAP</label>
                            <select name="modele" id="modele" class="form-select" @disabled(!$detailleeDisponible)>
                                @foreach($C::MODELES as $v => $libelle)
                                    <option value="{{ $v }}" @selected($modele === $v)>{{ $libelle }}</option>
                                @endforeach
                            </select>
                            @if(!$detailleeDisponible)
                                <div class="form-text">La fiche détaillée concerne le second cycle (7e à 9e).</div>
                            @endif
                        </div>
                    </div>
                    <div class="row g-3 mb-3 {{ $modele === 'detaillee' ? '' : 'd-none' }}" data-seulement-detaillee>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label small fw-bold" for="commune">Commune</label>
                            <input type="text" name="renseignements[commune]" id="commune" class="form-control" maxlength="100" value="{{ $renseignements['commune'] ?? '' }}">
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label small fw-bold" for="telephone">N° de téléphone</label>
                            <input type="text" name="renseignements[telephone]" id="telephone" class="form-control" maxlength="40" value="{{ $renseignements['telephone'] ?? '' }}">
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label small fw-bold" for="statut-ecole">Statut de l'école</label>
                            <select name="renseignements[statut]" id="statut-ecole" class="form-select">
                                <option value="">—</option>
                                @foreach($C::STATUTS_ECOLE as $v => $libelle)
                                    <option value="{{ $v }}" @selected(($renseignements['statut'] ?? null) === $v)>{{ $libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label small fw-bold" for="recrutement">Mode de recrutement</label>
                            <select name="renseignements[recrutement]" id="recrutement" class="form-select">
                                <option value="">—</option>
                                @foreach($C::RECRUTEMENTS as $v => $libelle)
                                    <option value="{{ $v }}" @selected(($renseignements['recrutement'] ?? null) === $v)>{{ $libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 small text-muted">Années et scolarité déduites des redoublements enregistrés dans KalanNet : vérifiez-les avec le dossier scolaire. Écrivez le père et la mère sous la forme « Prénom NOM » : la fiche les sépare en deux cases.</div>
                    </div>
                    <p class="small text-muted mb-2">Moyennes et résultats d'examen repris de KalanNet. Moyenne manquante : la proposition reste à choisir. @if(\App\Support\ExamenNational::estMali((int) $ecole->idEcole))En cas d'échec, KalanNet propose « Exclu(e) » si l'élève a déjà doublé cette classe ou deux fois dans le cycle (la 9e peut être doublée deux fois sans redoublement antérieur), ou si sa moyenne est de 0 ; il ne connaît que les redoublements enregistrés ici : vérifiez et corrigez si besoin.@endif Complétez le père, la mère et les classes doublées quand ils manquent.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size: .85rem;">
                            <thead>
                                <tr class="text-center">
                                    <th>N°</th><th>Élève</th><th>Sexe</th><th>Mle</th><th>Père</th><th>Mère</th>
                                    <th data-m="standard" class="{{ $modele === 'standard' ? '' : 'd-none' }}">Scolarité</th><th data-m="standard" class="{{ $modele === 'standard' ? '' : 'd-none' }}">Class. doublées</th>
                                    @foreach(['Recrutement', 'Doublées 1er cycle', 'Scol. 1er cycle', 'Entrée 7e', 'Ans 7e', 'Ans 8e', 'Ans 9e'] as $titre)
                                        <th data-m="detaillee" class="{{ $modele === 'detaillee' ? '' : 'd-none' }}">{{ $titre }}</th>
                                    @endforeach
                                    <th>Moyenne</th><th>Proposition</th><th>Observation</th>
                                    @if($fiche?->validee())<th>Décision CAP</th>@endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lignes as $id => $l)
                                    <tr data-ligne="{{ $id }}" class="{{ empty($l['proposition']) ? 'table-warning' : '' }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-nowrap fw-semibold">{{ $l['prenom'] }} {{ $l['nom'] }}<div class="small text-muted fw-normal">{{ $l['date_naissance'] }}{{ $l['lieu_naissance'] ? ' à '.$l['lieu_naissance'] : '' }}</div></td>
                                        <td class="text-center">{{ $l['sexe'] }}</td>
                                        <td class="text-center">{{ $l['matricule'] }}</td>
                                        <td style="min-width: 130px;"><input type="text" class="form-control form-control-sm" data-c="pere" maxlength="150" value="{{ $l['pere'] }}" aria-label="Père de {{ $l['prenom'] }}"></td>
                                        <td style="min-width: 150px;"><input type="text" class="form-control form-control-sm" data-c="mere" maxlength="150" value="{{ $l['mere'] }}" aria-label="Mère de {{ $l['prenom'] }}"></td>
                                        <td data-m="standard" class="{{ $modele === 'standard' ? '' : 'd-none' }}" style="width: 74px;"><input type="number" min="0" max="30" class="form-control form-control-sm text-center" data-c="scolarite" value="{{ $l['scolarite'] }}" aria-label="Scolarité de {{ $l['prenom'] }}"></td>
                                        <td data-m="standard" class="{{ $modele === 'standard' ? '' : 'd-none' }}" style="min-width: 90px;"><input type="text" class="form-control form-control-sm" data-c="doublees" maxlength="60" value="{{ $l['doublees'] }}" aria-label="Classes doublées de {{ $l['prenom'] }}"></td>
                                        @foreach(['annee_recrutement' => 'Année de recrutement', 'doublees_1er' => 'Classes doublées au 1er cycle', 'scolarite_1er' => 'Scolarité 1er cycle', 'annee_7e' => 'Année d\'entrée en 7e', 'annees_7' => 'Années en 7e', 'annees_8' => 'Années en 8e', 'annees_9' => 'Années en 9e'] as $champ => $titre)
                                            <td data-m="detaillee" class="{{ $modele === 'detaillee' ? '' : 'd-none' }}" style="min-width: {{ in_array($champ, ['annee_recrutement', 'annee_7e', 'doublees_1er'], true) ? 84 : 62 }}px;">
                                                <input type="{{ $champ === 'doublees_1er' ? 'text' : 'number' }}" @if($champ !== 'doublees_1er') min="0" @endif class="form-control form-control-sm text-center" data-c="{{ $champ }}" value="{{ $l[$champ] ?? '' }}" aria-label="{{ $titre }} de {{ $l['prenom'] }}">
                                            </td>
                                        @endforeach
                                        <td class="text-center fw-bold text-nowrap">{{ isset($l['moyenne']) ? number_format((float) $l['moyenne'], 2, ',', '') : '—' }}@if(!empty($l['resultat_examen']))<div class="small fw-normal text-muted">{{ $l['resultat_examen'] }}</div>@endif</td>
                                        <td style="min-width: 118px;">
                                            <select class="form-select form-select-sm" data-c="proposition" aria-label="Proposition pour {{ $l['prenom'] }}">
                                                <option value="">— À choisir —</option>
                                                @foreach($C::DECISIONS as $v => $libelle)
                                                    <option value="{{ $v }}" @selected(($l['proposition'] ?? null) === $v)>{{ $libelle }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td style="min-width: 130px;"><input type="text" class="form-control form-control-sm" data-c="observation" maxlength="255" value="{{ $l['observation'] ?? '' }}" aria-label="Observation pour {{ $l['prenom'] }}"></td>
                                        @if($fiche?->validee())
                                            <td class="text-center fw-bold">{{ $C::DECISIONS[$fiche->lignes_envoyees[$id]['decision_cap'] ?? ''] ?? '—' }}</td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr><td colspan="11" class="text-center text-muted py-4">Aucun élève dans cette classe pour {{ $annee->annee }}.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm form-actions-sticky d-flex flex-row flex-wrap justify-content-between align-items-center gap-2">
                <div class="small text-muted">
                    Passent : <strong>{{ $recap['passe']['t'] }}</strong> · Redoublent : <strong>{{ $recap['redouble']['t'] }}</strong> · Exclus : <strong>{{ $recap['exclu']['t'] }}</strong> · Abandons : <strong>{{ $recap['abandon']['t'] }}</strong>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" name="action" value="enregistrer" class="btn btn-outline-primary">Enregistrer</button>
                    <button type="submit" name="action" value="telecharger" class="btn btn-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Télécharger le PDF</button>
                    <button type="submit" name="action" value="remettre" class="btn btn-success fw-semibold">
                        @if($capSurKalanNet)
                            <i class="bi bi-send me-1"></i>{{ $fiche?->remise() ? 'Renvoyer au CAP' : 'Envoyer au CAP' }}
                        @else
                            <i class="bi bi-check2-circle me-1"></i>{{ $fiche?->remise() ? 'Marquer de nouveau comme remise' : 'Marquer comme remise au CAP' }}
                        @endif
                    </button>
                </div>
            </div>
        </form>
    @endif
@endsection

@push('scripts')
    <script>
        // Changer de format ne recharge pas la page : ce qui est déjà saisi reste.
        document.getElementById('modele')?.addEventListener('change', function () {
            document.querySelectorAll('[data-m]').forEach((c) => c.classList.toggle('d-none', c.dataset.m !== this.value));
            document.querySelector('[data-seulement-detaillee]')?.classList.toggle('d-none', this.value !== 'detaillee');
        });

        // Toute la liste part dans un seul champ : une grande classe ne dépasse pas la limite de champs de PHP.
        document.getElementById('proposition-form')?.addEventListener('submit', function () {
            const lignes = {};
            this.querySelectorAll('[data-ligne]').forEach((ligne) => {
                const valeurs = {};
                ligne.querySelectorAll('[data-c]').forEach((champ) => { valeurs[champ.dataset.c] = champ.value === '' ? null : champ.value; });
                lignes[ligne.dataset.ligne] = valeurs;
            });
            document.getElementById('lignes_json').value = JSON.stringify(lignes);
        });
    </script>
@endpush
