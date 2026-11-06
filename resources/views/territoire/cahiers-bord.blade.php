@extends('layouts.app')

@section('content')
    @php
        $C = \App\Support\CahierBordDonnees::class;
        $recus = $lignes->filter(fn ($l) => $l->fiche);
    @endphp
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Espace DCAP</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cahiers de bord</li>
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

    <p class="text-muted">Cahiers reçus : <strong>{{ $recus->count() }} / {{ $lignes->count() }}</strong>. La liste montre les écoles de votre CAP qui utilisent KalanNet, une ligne par cycle : les autres remettent leur cahier sur papier.</p>

    <div class="card theme-card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>École</th><th>Cycle</th><th>État</th><th class="text-end">Élèves</th><th class="text-end">Enseignants</th><th></th></tr></thead>
                    <tbody>
                        @forelse($lignes as $l)
                            @php $d = $l->fiche?->donnees_envoyees; @endphp
                            <tr>
                                <td><div class="fw-semibold">{{ $l->ecole->nomEcole }}</div><div class="small text-muted">{{ $l->ecole->adresse }}</div></td>
                                <td>{{ $C::CYCLES[$l->cycle] }}</td>
                                <td>
                                    @if($l->fiche)
                                        <span class="badge bg-success">Reçu</span>
                                        <div class="small text-muted">{{ $l->fiche->envoye_at?->format('d/m/Y') }}{{ $l->fiche->statut === 'remis' ? ' (sur papier)' : '' }}</div>
                                    @else
                                        <span class="badge bg-warning text-dark">Non reçu</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $d ? collect($d['effectifs'] ?? [])->sum(fn ($e) => (int) $e['g'] + (int) $e['f']) : '—' }}</td>
                                <td class="text-end">{{ $d ? count($d['enseignants'] ?? []) : '—' }}</td>
                                <td class="text-end text-nowrap">
                                    @if($l->fiche)
                                        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ route('territoire.cahiers-bord.pdf', $l->fiche->id) }}"><i class="bi bi-eye"></i> Voir</a>
                                        <a class="btn btn-sm btn-primary" href="{{ route('territoire.cahiers-bord.pdf', [$l->fiche->id, 'telecharger' => 1]) }}" title="Télécharger"><i class="bi bi-download"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center py-4">Aucune école du jardin d'enfants ou du fondamental sur KalanNet dans votre CAP.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
