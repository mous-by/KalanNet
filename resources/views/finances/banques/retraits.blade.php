@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Finances</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Retraits</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <button class="btn theme-action-btn" data-bs-toggle="modal" data-bs-target="#retraitModal">
            <i class="bi bi-plus-lg me-1"></i>Nouveau retrait
        </button>
    </div>
</div>

@include('finances.paiements.partials.alerts')

<div class="card theme-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="px-4">Date</th><th>Banque</th><th>Motif</th><th class="text-end">Montant</th><th>Statut</th><th class="text-end px-4">Action</th></tr></thead>
            <tbody>
            @forelse($retraits as $retrait)
                <tr>
                    <td class="px-4">{{ $retrait->date_retrait?->format('d/m/Y') }}</td>
                    <td>{{ $retrait->banque?->nom_banque }}</td>
                    <td>{{ $retrait->motif_retrait }}</td>
                    <td class="text-end fw-bold">{{ number_format($retrait->montant_retrait, 0, ',', ' ') }} FCFA</td>
                    <td><span class="badge bg-{{ $retrait->valide ? 'success' : 'warning' }}">{{ $retrait->valide ? 'Validé' : 'En attente' }}</span></td>
                    <td class="text-end px-4">
                        @if(!$retrait->valide && auth()->user()->droit === 'Admin')
                            <form method="POST" action="{{ route('finances.retraits.validate', $retrait->id_retrait) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-success">Valider</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">Aucun retrait.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="retraitModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('finances.retraits.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Ajouter un retrait</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Banque</label>
                <select name="id_banque" class="form-select mb-3" required>
                    @foreach($banques as $banque)<option value="{{ $banque->id_banques }}">{{ $banque->nom_banque }} - solde {{ number_format($banque->solde, 0, ',', ' ') }}</option>@endforeach
                </select>
                <label class="form-label">Date</label><input type="date" name="date_retrait" value="{{ now()->toDateString() }}" class="form-control mb-3" required>
                <label class="form-label">Montant</label><input type="number" name="montant_retrait" class="form-control mb-3" min="1" step="1" required>
                <label class="form-label">Motif</label><input name="motif_retrait" class="form-control" required>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Valider</button></div>
        </form>
    </div>
</div>
@endsection
