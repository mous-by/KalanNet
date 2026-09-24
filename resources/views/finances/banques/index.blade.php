@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">{{ __('finances.title') }}</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ __('finances.breadcrumb_banques') }}</li>
            </ol>
        </nav>
    </div>
    @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('banques_creation'))
        <div class="ms-auto">
            <button class="btn theme-action-btn" data-bs-toggle="modal" data-bs-target="#banqueModal">
                <i class="bi bi-plus-lg me-1"></i>{{ __('finances.add_button') }}
            </button>
        </div>
    @endif
</div>

@include('finances.paiements.partials.alerts')

<div class="card theme-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="px-4">{{ __('finances.th_compte') }}</th><th>{{ __('finances.th_banque') }}</th><th class="text-end">{{ __('finances.th_solde') }}</th><th class="text-end px-4">{{ __('finances.th_actions') }}</th></tr></thead>
            <tbody>
            @forelse($banques as $banque)
                <tr>
                    <td class="px-4 fw-bold">{{ $banque->numero_compte }}</td>
                    <td>{{ $banque->nom_banque }}</td>
                    <td class="text-end text-success fw-bold">@devise($banque->solde)</td>
                    <td class="text-end px-4">
                        @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('banques_modification'))
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editBanque{{ $banque->id_banques }}">{{ __('finances.edit_button') }}</button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-5">{{ __('finances.empty_banques') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="banqueModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('finances.banques.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">{{ __('finances.add_banque_title') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">{{ __('finances.label_numero_compte') }}</label><input name="numero_compte" class="form-control mb-3" required>
                <label class="form-label">{{ __('finances.label_nom_banque') }}</label><input name="nom_banque" class="form-control mb-3" required>
                <label class="form-label">{{ __('finances.label_montant_initial') }}</label><input type="number" name="montant_initial" class="form-control" min="0" step="1" required>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">{{ __('finances.save_button') }}</button></div>
        </form>
    </div>
</div>

@foreach($banques as $banque)
<div class="modal fade" id="editBanque{{ $banque->id_banques }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('finances.banques.update', $banque->id_banques) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">{{ __('finances.edit_banque_title') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">{{ __('finances.label_numero_compte') }}</label><input name="numero_compte" value="{{ $banque->numero_compte }}" class="form-control mb-3" required>
                <label class="form-label">{{ __('finances.label_nom_banque') }}</label><input name="nom_banque" value="{{ $banque->nom_banque }}" class="form-control mb-3" required>
                <label class="form-label">{{ __('finances.label_solde') }}</label><input type="number" name="solde" value="{{ $banque->solde }}" class="form-control" min="0" step="1" required>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">{{ __('finances.save_button') }}</button></div>
        </form>
    </div>
</div>
@endforeach
@endsection
