@extends('layouts.app')

@section('content')
    @php $recus = $lignes->filter(fn ($l) => $l->rapport); @endphp
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Académie</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Rapports du secondaire</li>
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
        <div class="col-sm-4 col-md-3">
            <label class="form-label small fw-bold" for="type">Rapport</label>
            <select name="type" id="type" class="form-select" onchange="this.form.submit()">
                @foreach(\App\Services\Rapports\CanevasEsg::TYPES as $v => $libelle)
                    <option value="{{ $v }}" @selected($type === $v)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <p class="text-muted">Rapports reçus : <strong>{{ $recus->count() }} / {{ $lignes->count() }}</strong>. La liste montre les établissements du secondaire, général et technique, de votre académie qui utilisent KalanNet.</p>

    <div class="card theme-card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Établissement</th><th>État</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        @forelse($lignes as $l)
                            <tr>
                                <td class="fw-semibold">{{ $l->ecole->nomEcole }}<div class="small text-muted fw-normal">{{ $l->ecole->adresse }}</div></td>
                                <td>
                                    @if(!$l->rapport)
                                        <span class="badge bg-warning text-dark">Non reçu</span>
                                    @elseif($l->rapport->statut === 'envoye')
                                        <span class="badge bg-success">Reçu</span>
                                    @else
                                        <span class="badge bg-secondary">Remis hors KalanNet</span>
                                    @endif
                                </td>
                                <td>{{ $l->rapport?->envoye_at?->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    @if($l->rapport?->fichier)
                                        <a class="btn btn-sm btn-primary" href="{{ route('territoire.rapports-academie.fichier', $l->rapport->id) }}"><i class="bi bi-download me-1"></i>Télécharger</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Aucun établissement du secondaire sur KalanNet dans votre académie.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
