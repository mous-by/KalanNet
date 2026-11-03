@extends('layouts.app')

@section('content')
    @php
        $recus = $lignes->filter(fn ($l) => $l->rapport);
        $somme = fn ($cle) => $recus->sum(fn ($l) => $l->totaux[$cle] ?? 0);
    @endphp
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ $portee === 'secondaire' ? 'Académie' : 'Espace DCAP' }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Rapports succincts</li>
                </ol>
            </nav>
        </div>
    </div>

    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-sm-4 col-md-3">
            <label class="form-label small fw-bold" for="annee">Année scolaire</label>
            <select name="annee" id="annee" class="form-select" onchange="this.form.submit()">
                @foreach($annees as $a)
                    <option value="{{ $a->id_anneeScolaire }}" @selected($annee?->id_anneeScolaire === $a->id_anneeScolaire)>{{ $a->annee }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="row g-3 mb-3">
        @foreach([['Rapports reçus', $recus->count().' / '.$lignes->count()], ['Élèves', number_format($somme('eleves'), 0, ',', ' ')], ['Redoublants', number_format($somme('redoublants'), 0, ',', ' ')], [$portee === 'secondaire' ? 'Enseignants' : 'Maîtres', number_format($somme('maitres'), 0, ',', ' ')]] as [$libelle, $valeur])
            <div class="col-6 col-lg-3">
                <div class="card theme-card shadow-sm h-100"><div class="card-body py-3">
                    <div class="small text-muted text-uppercase fw-bold">{{ $libelle }}</div>
                    <div class="fs-4 fw-bold">{{ $valeur }}</div>
                </div></div>
            </div>
        @endforeach
    </div>
    <p class="small text-muted">Les chiffres ne comptent que les rapports reçus. La liste montre les {{ $portee === 'secondaire' ? 'établissements du secondaire de votre académie' : 'écoles de votre CAP' }} qui utilisent KalanNet : les autres remettent leur rapport sur papier.</p>

    <div class="card theme-card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ $portee === 'secondaire' ? 'Établissement' : 'École' }}</th><th>Rapport</th>
                            <th class="text-end">Groupes</th><th class="text-end">Élèves</th><th class="text-end">Redoublants</th><th class="text-end">{{ $portee === 'secondaire' ? 'Enseignants' : 'Maîtres' }}</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lignes as $l)
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
                                @foreach(['groupes', 'eleves', 'redoublants', 'maitres'] as $c)
                                    <td class="text-end">{{ $l->totaux[$c] ?? '—' }}</td>
                                @endforeach
                                <td class="text-end text-nowrap">
                                    @if($l->rapport)
                                        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ route('territoire.rapports-succincts.pdf', $l->rapport->id) }}"><i class="bi bi-eye"></i> Voir</a>
                                        <a class="btn btn-sm btn-primary" href="{{ route('territoire.rapports-succincts.pdf', [$l->rapport->id, 'telecharger' => 1]) }}" title="Télécharger"><i class="bi bi-download"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted text-center py-4">{{ $portee === 'secondaire' ? 'Aucun établissement du secondaire sur KalanNet dans votre académie.' : 'Aucune école du préscolaire ou du fondamental sur KalanNet dans votre CAP.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
