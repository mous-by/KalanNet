@extends('layouts.app')

@section('content')
    @php
        $D = \App\Support\RapportSecondaireDefinitions::class;
        $recus = $lignes->filter(fn ($l) => $l->rapport);
    @endphp
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Académie</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Rapports de rentrée et de fin d'année</li>
                </ol>
            </nav>
        </div>
    </div>

    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-sm-4 col-md-3">
            <label class="form-label small fw-bold" for="type">Rapport</label>
            <select name="type" id="type" class="form-select" onchange="this.form.submit()">
                @foreach($D::TYPES as $v => $libelle)
                    <option value="{{ $v }}" @selected($type === $v)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-4 col-md-3">
            <label class="form-label small fw-bold" for="annee">Année scolaire</label>
            <select name="annee" id="annee" class="form-select" onchange="this.form.submit()">
                @foreach($annees as $a)
                    <option value="{{ $a->id_anneeScolaire }}" @selected($annee?->id_anneeScolaire === $a->id_anneeScolaire)>{{ $a->annee }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <p class="text-muted">Rapports reçus : <strong>{{ $recus->count() }} / {{ $lignes->count() }}</strong>. La liste montre les établissements du secondaire de votre académie qui utilisent KalanNet : les autres remettent leur rapport sur papier.</p>

    <div class="card theme-card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Établissement</th><th>État</th><th class="text-end">Élèves</th><th></th></tr></thead>
                    <tbody>
                        @forelse($lignes as $l)
                            @php $i = $l->rapport?->donnees_envoyees['infos'] ?? null; @endphp
                            <tr>
                                <td><div class="fw-semibold">{{ $l->ecole->nomEcole }}</div><div class="small text-muted">{{ $l->ecole->adresse }}</div></td>
                                <td>
                                    @if($l->rapport)
                                        <span class="badge bg-success">Reçu</span>
                                        <div class="small text-muted">{{ $l->rapport->envoye_at?->format('d/m/Y') }}{{ $l->rapport->statut === 'remis' ? ' (sur papier)' : '' }}</div>
                                    @else
                                        <span class="badge bg-warning text-dark">Non reçu</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $i ? (int) ($i['effectif_f'] ?? 0) + (int) ($i['effectif_g'] ?? 0) : '—' }}</td>
                                <td class="text-end text-nowrap">
                                    @if($l->rapport)
                                        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ route('territoire.rapports-secondaire.pdf', $l->rapport->id) }}"><i class="bi bi-eye"></i> Voir</a>
                                        <a class="btn btn-sm btn-primary" href="{{ route('territoire.rapports-secondaire.pdf', [$l->rapport->id, 'telecharger' => 1]) }}" title="Télécharger"><i class="bi bi-download"></i></a>
                                        <a class="btn btn-sm btn-outline-success" href="{{ route('territoire.rapports-secondaire.pdf', [$l->rapport->id, 'format' => 'excel']) }}" title="Excel"><i class="bi bi-file-earmark-excel"></i></a>
                                        <a class="btn btn-sm btn-success" href="{{ route('territoire.rapports-secondaire.canevas', [$l->rapport->id, 'modele' => 'esg']) }}" title="Canevas officiel du général">Canevas</a>
                                        @if(isset(\App\Services\Rapports\CanevasOfficiel::MAQUETTES_ETP[$l->ecole->maquette_etp]))
                                            <a class="btn btn-sm btn-success" href="{{ route('territoire.rapports-secondaire.canevas', [$l->rapport->id, 'modele' => 'etp']) }}" title="{{ \App\Services\Rapports\CanevasOfficiel::MAQUETTES_ETP[$l->ecole->maquette_etp] }}">Technique</a>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted text-center py-4">Aucun établissement du secondaire sur KalanNet dans votre académie.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
