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
                    </div>
                    <p class="small text-muted mb-2">Moyennes et résultats d'examen repris de KalanNet. Moyenne manquante : la proposition reste à choisir. Complétez le père, la mère et les classes doublées quand ils manquent.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size: .85rem;">
                            <thead>
                                <tr class="text-center">
                                    <th>N°</th><th>Élève</th><th>Sexe</th><th>Mle</th><th>Père</th><th>Mère</th><th>Scolarité</th><th>Class. doublées</th><th>Moyenne</th><th>Proposition</th><th>Observation</th>
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
                                        <td style="width: 74px;"><input type="number" min="0" max="30" class="form-control form-control-sm text-center" data-c="scolarite" value="{{ $l['scolarite'] }}" aria-label="Scolarité de {{ $l['prenom'] }}"></td>
                                        <td style="min-width: 90px;"><input type="text" class="form-control form-control-sm" data-c="doublees" maxlength="60" value="{{ $l['doublees'] }}" aria-label="Classes doublées de {{ $l['prenom'] }}"></td>
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
