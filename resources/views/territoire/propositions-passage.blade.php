@extends('layouts.app')

@section('content')
    @php $recues = $lignes->filter(fn ($l) => $l->fiche); @endphp
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Espace DCAP</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Propositions de passage</li>
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

    <p class="text-muted">Classes reçues : <strong>{{ $recues->count() }} / {{ $lignes->count() }}</strong>. Décidées : <strong>{{ $recues->filter(fn ($l) => $l->fiche->validee())->count() }}</strong>. La liste montre les écoles de votre CAP qui utilisent KalanNet : les autres remettent leur fiche sur papier.</p>

    <div class="card theme-card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>École</th><th>Classe</th><th>État</th><th class="text-end">Passent</th><th class="text-end">Redoublent</th><th class="text-end">Exclus</th><th class="text-end">Abandons</th><th></th></tr></thead>
                    <tbody>
                        @forelse($lignes as $l)
                            <tr>
                                <td class="fw-semibold">{{ $l->classe->nomEcole }}</td>
                                <td>{{ $l->classe->nom_classe }}</td>
                                <td>
                                    @if(!$l->fiche)
                                        <span class="badge bg-warning text-dark">Non reçue</span>
                                    @elseif($l->fiche->validee())
                                        <span class="badge bg-success">Décidée</span>
                                    @elseif($l->fiche->statut === 'remis')
                                        <span class="badge bg-secondary">Remise sur papier</span>
                                    @else
                                        <span class="badge bg-info text-dark">À décider</span>
                                    @endif
                                </td>
                                @foreach(['passe', 'redouble', 'exclu', 'abandon'] as $d)
                                    <td class="text-end">{{ $l->recap[$d]['t'] ?? '—' }}</td>
                                @endforeach
                                <td class="text-end">
                                    @if($l->fiche)
                                        <a class="btn btn-sm btn-primary" href="{{ route('territoire.propositions-passage.show', $l->fiche->id) }}">Ouvrir</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Aucune classe du fondamental sur KalanNet dans votre CAP.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
