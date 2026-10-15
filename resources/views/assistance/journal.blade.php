@extends('layouts.app')

@section('title', __('assistance.journal'))

@section('content')
    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header">
            <h5 class="mb-0 fw-bold"><i class="bx bx-history me-2"></i>{{ __('assistance.journal') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('assistance.col_debut') }}</th>
                            <th>{{ __('assistance.col_fin') }}</th>
                            <th>{{ __('assistance.col_support') }}</th>
                            <th>{{ __('assistance.col_compte') }}</th>
                            <th>{{ __('assistance.col_ecole') }}</th>
                            <th>{{ __('assistance.col_motif') }}</th>
                            <th>{{ __('assistance.col_ip') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($acces as $ligne)
                            <tr>
                                <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($ligne->debut_at)->format('d/m/Y H:i') }}</td>
                                <td class="text-nowrap">
                                    @if($ligne->fin_at)
                                        {{ \Illuminate\Support\Carbon::parse($ligne->fin_at)->format('d/m/Y H:i') }}
                                        <span class="badge bg-light text-dark">{{ __('assistance.raison_'.$ligne->fin_raison) }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('assistance.en_cours') }}</span>
                                    @endif
                                </td>
                                <td>{{ $ligne->superviseur }}</td>
                                <td>{{ $ligne->compte }} <span class="text-muted small">({{ $ligne->role }})</span></td>
                                <td>{{ $ligne->ecole ?? '—' }}</td>
                                <td>{{ $ligne->motif }}</td>
                                <td class="text-muted small">{{ $ligne->ip }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">{{ __('assistance.journal_vide') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $acces->links() }}</div>
        </div>
    </div>
@endsection
