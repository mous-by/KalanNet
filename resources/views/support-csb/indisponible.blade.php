@extends('layouts.app')

@section('content')
    @include('support-csb._header')

    <div class="card theme-card shadow-sm">
        <div class="card-body p-5 text-center">
            <i class="bi bi-cloud-slash fs-1 text-secondary"></i>
            <h5 class="mt-3">{{ __('support_csb.indisponible_titre') }}</h5>
            <p class="text-secondary mb-0">{{ $message }}</p>
        </div>
    </div>
@endsection
