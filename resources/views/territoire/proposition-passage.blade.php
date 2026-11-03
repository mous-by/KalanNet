@extends('layouts.app')

@section('content')
    @php $C = \App\Support\PropositionPassageDonnees::class; @endphp
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Espace DCAP</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('territoire.propositions-passage') }}">Propositions de passage</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $ecole }} : {{ $classe }}</li>
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

    <form method="POST" action="{{ route('territoire.propositions-passage.decider', $fiche->id) }}">
        @csrf
        <div class="card theme-card shadow-sm mb-3">
            <div class="card-header theme-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="mb-0 fw-bold">{{ $ecole }} : {{ $classe }} ({{ $annee }})</h6>
                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-light" target="_blank" rel="noopener" href="{{ route('territoire.propositions-passage.pdf', $fiche->id) }}"><i class="bi bi-eye me-1"></i>Voir le PDF</a>
                    <a class="btn btn-sm btn-light" href="{{ route('territoire.propositions-passage.pdf', [$fiche->id, 'telecharger' => 1]) }}"><i class="bi bi-download me-1"></i>Télécharger</a>
                </div>
            </div>
            <div class="card-body">
                <p class="small text-muted">
                    Conseil des maîtres : {{ $recap['passe']['t'] }} passent, {{ $recap['redouble']['t'] }} redoublent, {{ $recap['exclu']['t'] }} exclus, {{ $recap['abandon']['t'] }} abandons.
                    Chaque décision reprend la proposition de l'école : changez seulement ce qui diffère, puis validez.
                    @if($fiche->validee()) <strong>Décision déjà rendue le {{ $fiche->valide_at?->format('d/m/Y') }} : vous pouvez la corriger.</strong> @endif
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead><tr class="text-center"><th>N°</th><th>Élève</th><th>Sexe</th><th>Mle</th><th>Scolarité</th><th>Class. doublées</th><th>Moyenne</th><th>Proposition</th><th>Observation école</th><th>Décision CAP</th><th>Observation CAP</th></tr></thead>
                        <tbody>
                            @foreach($lignes as $id => $l)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="fw-semibold text-nowrap">{{ $l['prenom'] }} {{ $l['nom'] }}</td>
                                    <td class="text-center">{{ $l['sexe'] }}</td>
                                    <td class="text-center">{{ $l['matricule'] }}</td>
                                    <td class="text-center">{{ $fiche->modele === 'detaillee' ? ($l['scolarite_1er'] ?? '') : $l['scolarite'] }}</td>
                                    <td class="text-center">{{ $fiche->modele === 'detaillee' ? ($l['doublees_1er'] ?? '') : $l['doublees'] }}</td>
                                    <td class="text-center fw-bold">{{ isset($l['moyenne']) ? number_format((float) $l['moyenne'], 2, ',', '') : '—' }}</td>
                                    <td class="text-center">{{ $C::DECISIONS[$l['proposition'] ?? ''] ?? '—' }}</td>
                                    <td>{{ $l['observation'] ?? '' }}</td>
                                    <td style="min-width: 120px;">
                                        <select name="decisions[{{ $id }}][decision]" class="form-select form-select-sm" required aria-label="Décision pour {{ $l['prenom'] }}">
                                            @foreach($C::DECISIONS as $v => $libelle)
                                                <option value="{{ $v }}" @selected(old("decisions.$id.decision", $l['decision_cap'] ?? $l['proposition'] ?? null) === $v)>{{ $libelle }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td style="min-width: 150px;"><input type="text" name="decisions[{{ $id }}][observation]" class="form-control form-control-sm" maxlength="255" value="{{ old("decisions.$id.observation", $l['observation_cap'] ?? '') }}" aria-label="Observation pour {{ $l['prenom'] }}"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card shadow-sm form-actions-sticky d-flex flex-row justify-content-end">
            <button type="submit" class="btn btn-success fw-semibold"><i class="bi bi-check2-circle me-1"></i>Valider la décision du CAP</button>
        </div>
    </form>
@endsection
