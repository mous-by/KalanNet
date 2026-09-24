@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="btn btn-primary rounded-circle p-2 me-3" title="{{ __('bulletins.back_tooltip') }}">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="breadcrumb-title pe-3">{{ __('bulletins.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('bulletins.generate_breadcrumb') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-0 fw-bold">{{ __('bulletins.generate_by_classe_title') }}</h5>
                <small class="opacity-75">{{ __('bulletins.generate_by_classe_desc') }}</small>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('bulletins.th_classe') }}</th>
                            <th>{{ __('classes.th_classe_officielle') }}</th>
                            <th>{{ __('bulletins.th_ordre') }}</th>
                            <th>{{ __('classes.th_effectif') }}</th>
                            <th class="text-center">{{ __('classes.th_action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $classe)
                            <tr>
                                <td class="fw-bold">{{ $classe->nom_classe }}</td>
                                <td>{{ $classe->classeOfficielle->nom_classe_officielle ?? __('classes.non_associee') }}</td>
                                <td>{{ $classe->ordreEnseignement }}</td>
                                <td>
                                    <span class="badge bg-light text-primary border border-primary-subtle rounded-pill">
                                        {{ $classe->eleves_count }} {{ __('classes.students_suffix') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('pedagogie.bulletins.index', $classe->id_classe) }}" class="btn btn-primary px-4">
                                        <i class="bi bi-file-earmark-pdf me-2"></i>{{ __('bulletins.generate_button') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">{{ __('bulletins.empty_classes') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
