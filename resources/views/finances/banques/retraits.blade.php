@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">{{ __('finances.title') }}</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ __('finances.breadcrumb_retraits') }}</li>
            </ol>
        </nav>
    </div>
    @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('retraits_creation'))
        <div class="ms-auto">
            <button class="btn theme-action-btn" data-bs-toggle="modal" data-bs-target="#retraitModal">
                <i class="bi bi-plus-lg me-1"></i>{{ __('finances.new_retrait') }}
            </button>
        </div>
    @endif
</div>

@include('finances.paiements.partials.alerts')

<div class="card theme-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="px-4">{{ __('finances.th_date') }}</th><th>{{ __('finances.th_banque') }}</th><th>{{ __('finances.th_motif') }}</th><th class="text-end">{{ __('finances.th_montant') }}</th><th>{{ __('finances.th_statut') }}</th><th class="text-end px-4">{{ __('classes.th_action') }}</th></tr></thead>
            <tbody>
            @forelse($retraits as $retrait)
                <tr>
                    <td class="px-4">{{ $retrait->date_retrait?->format('d/m/Y') }}</td>
                    <td>{{ $retrait->banque?->nom_banque }}</td>
                    <td>{{ $retrait->motif_retrait }}</td>
                    <td class="text-end fw-bold">@devise($retrait->montant_retrait)</td>
                    <td><span class="badge bg-{{ $retrait->valide ? 'success' : 'warning' }}">{{ $retrait->valide ? __('finances.statut_retrait_valide') : __('finances.statut_en_attente') }}</span></td>
                    <td class="text-end px-4">
                        @if(!$retrait->valide && (auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('retraits_modification')))
                            <form method="POST" action="{{ route('finances.retraits.validate', $retrait->id_retrait) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-success">{{ __('finances.validate_button') }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">{{ __('finances.empty_retraits') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="retraitModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('finances.retraits.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">{{ __('finances.add_retrait_title') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">{{ __('finances.th_banque') }}</label>
                <select name="id_banque" class="form-select mb-3" required>
                    @foreach($banques as $banque)
                        @php($soldeFormatted = number_format($banque->solde, 0, ',', ' '))
                        <option value="{{ $banque->id_banques }}">{{ $banque->nom_banque }} - {{ __('finances.solde_suffix', ['montant' => $soldeFormatted]) }}</option>
                    @endforeach
                </select>
                <label class="form-label">{{ __('finances.label_date') }}</label><input type="date" name="date_retrait" value="{{ now()->toDateString() }}" class="form-control mb-3" required>
                <label class="form-label">{{ __('finances.label_montant') }}</label><input type="number" name="montant_retrait" class="form-control mb-3" min="1" step="1" required>
                <label class="form-label">{{ __('finances.label_motif') }}</label><input name="motif_retrait" class="form-control" required>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">{{ __('finances.validate_button') }}</button></div>
        </form>
    </div>
</div>
@endsection
