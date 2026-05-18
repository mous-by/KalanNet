@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center bg-card p-4 rounded-4 shadow-sm mb-4">
    <div><h2 class="mb-1 fw-bold">Versements</h2><div class="text-muted">Transfert de la caisse active vers une banque.</div></div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#versementModal">Nouveau versement</button>
</div>

@include('finances.paiements.partials.alerts')

<div class="card theme-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="px-4">Date</th><th>Banque</th><th>Motif</th><th class="text-end">Montant</th></tr></thead>
            <tbody>
            @forelse($versements as $versement)
                <tr>
                    <td class="px-4">{{ $versement->date_versement?->format('d/m/Y') }}</td>
                    <td>{{ $versement->banque?->nom_banque }}</td>
                    <td>{{ $versement->motif_versement }}</td>
                    <td class="text-end fw-bold">{{ number_format($versement->montant_versement, 0, ',', ' ') }} FCFA</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-5">Aucun versement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="versementModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('finances.versements.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Ajouter un versement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @if($caisse)
                    <div class="alert alert-light">Caisse active : {{ $caisse->reference }} - {{ number_format($caisse->montant_net, 0, ',', ' ') }} FCFA</div>
                @endif
                <label class="form-label">Banque</label>
                <select name="id_banque" class="form-select mb-3" required>
                    @foreach($banques as $banque)<option value="{{ $banque->id_banques }}">{{ $banque->nom_banque }} - {{ $banque->numero_compte }}</option>@endforeach
                </select>
                <label class="form-label">Montant</label><input type="number" name="montant_versement" class="form-control mb-3" min="1" step="1" required>
                <label class="form-label">Motif</label><input name="motif_versement" class="form-control" required>
            </div>
            <div class="modal-footer"><button class="btn btn-primary" @disabled(!$caisse)>Valider</button></div>
        </form>
    </div>
</div>
@endsection
