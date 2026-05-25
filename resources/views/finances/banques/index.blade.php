@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Finances</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Banques</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <button class="btn theme-action-btn" data-bs-toggle="modal" data-bs-target="#banqueModal">
            <i class="bi bi-plus-lg me-1"></i>Ajouter
        </button>
    </div>
</div>

@include('finances.paiements.partials.alerts')

<div class="card theme-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="px-4">Compte</th><th>Banque</th><th class="text-end">Solde</th><th class="text-end px-4">Actions</th></tr></thead>
            <tbody>
            @forelse($banques as $banque)
                <tr>
                    <td class="px-4 fw-bold">{{ $banque->numero_compte }}</td>
                    <td>{{ $banque->nom_banque }}</td>
                    <td class="text-end text-success fw-bold">{{ number_format($banque->solde, 0, ',', ' ') }} FCFA</td>
                    <td class="text-end px-4">
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editBanque{{ $banque->id_banques }}">Modifier</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-5">Aucune banque enregistrée.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="banqueModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('finances.banques.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Ajouter une banque</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Numéro compte</label><input name="numero_compte" class="form-control mb-3" required>
                <label class="form-label">Nom banque</label><input name="nom_banque" class="form-control mb-3" required>
                <label class="form-label">Montant initial</label><input type="number" name="montant_initial" class="form-control" min="0" step="1" required>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
        </form>
    </div>
</div>

@foreach($banques as $banque)
<div class="modal fade" id="editBanque{{ $banque->id_banques }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('finances.banques.update', $banque->id_banques) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Modifier banque</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Numéro compte</label><input name="numero_compte" value="{{ $banque->numero_compte }}" class="form-control mb-3" required>
                <label class="form-label">Nom banque</label><input name="nom_banque" value="{{ $banque->nom_banque }}" class="form-control mb-3" required>
                <label class="form-label">Solde</label><input type="number" name="solde" value="{{ $banque->solde }}" class="form-control" min="0" step="1" required>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
        </form>
    </div>
</div>
@endforeach
@endsection
