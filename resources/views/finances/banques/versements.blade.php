@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">{{ __('finances.title') }}</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ __('finances.breadcrumb_versements') }}</li>
            </ol>
        </nav>
    </div>
    @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('versements_creation'))
        <div class="ms-auto">
            <button class="btn theme-action-btn" data-bs-toggle="modal" data-bs-target="#versementModal">
                <i class="bi bi-plus-lg me-1"></i>{{ __('finances.new_versement') }}
            </button>
        </div>
    @endif
</div>

@include('finances.paiements.partials.alerts')

<div class="card theme-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="px-4">{{ __('finances.th_date') }}</th><th>{{ __('finances.th_banque') }}</th><th>{{ __('finances.th_motif') }}</th><th class="text-end">{{ __('finances.th_montant') }}</th></tr></thead>
            <tbody>
            @forelse($versements as $versement)
                <tr>
                    <td class="px-4">{{ $versement->date_versement?->format('d/m/Y') }}</td>
                    <td>{{ $versement->banque?->nom_banque }}</td>
                    <td>{{ $versement->motif_versement }}</td>
                    <td class="text-end fw-bold">@devise($versement->montant_versement)</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-5">{{ __('finances.empty_versements') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="versementModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('finances.versements.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">{{ __('finances.add_versement_title') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @if($caisse)
                    <div class="alert alert-light">{{ __('finances.active_caisse_prefix') }} {{ $caisse->reference }} - @devise($caisse->montant_net)</div>
                @endif
                <label class="form-label">{{ __('finances.th_banque') }}</label>
                <select name="id_banque" class="form-select mb-3" required>
                    @foreach($banques as $banque)<option value="{{ $banque->id_banques }}">{{ $banque->nom_banque }} - {{ $banque->numero_compte }}</option>@endforeach
                </select>
                <label class="form-label">{{ __('finances.label_montant') }}</label><input type="number" name="montant_versement" class="form-control mb-3" min="1" step="1" required>
                <label class="form-label">{{ __('finances.label_motif') }}</label><input name="motif_versement" class="form-control" required>
            </div>
            <div class="modal-footer"><button class="btn btn-primary" @disabled(!$caisse)>{{ __('finances.validate_generic') }}</button></div>
        </form>
    </div>
</div>
@endsection
