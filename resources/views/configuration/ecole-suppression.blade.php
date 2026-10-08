@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('configuration.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.ecoles') }}">{{ __('configuration.menu_ecoles') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('configuration.eco_suppression_titre') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('configuration.partials.flash')

    <div class="card border-danger shadow-sm" style="max-width: 760px;">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0 fw-bold"><i class="bx bx-error me-2"></i>{{ __('configuration.eco_suppression_titre') }} : {{ $ecole->nomEcole }}</h5>
        </div>
        <div class="card-body">
            <p class="fw-semibold text-danger mb-2">{{ __('configuration.eco_suppression_irreversible') }}</p>
            <p class="mb-3">{{ __('configuration.eco_suppression_intro') }}</p>

            @if($inventaire)
                <table class="table table-sm table-bordered mb-3">
                    <tbody>
                        @foreach($inventaire as $libelle => $nombre)
                            <tr>
                                <td>{{ $libelle }}</td>
                                <td class="text-end fw-bold" style="font-variant-numeric: tabular-nums;">{{ number_format($nombre, 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted">{{ __('configuration.eco_suppression_vide') }}</p>
            @endif

            <p class="small text-muted mb-3">{{ __('configuration.eco_suppression_conserve') }}</p>

            <form action="{{ route('configuration.ecoles.destroy', $ecole->idEcole) }}" method="POST">
                @csrf
                @method('DELETE')
                <label for="confirmation" class="form-label">{!! __('configuration.eco_suppression_retaper', ['nom' => '<strong>' . e($ecole->nomEcole) . '</strong>']) !!}</label>
                <input type="text" id="confirmation" name="confirmation" class="form-control mb-3" autocomplete="off" required>
                <div class="d-flex gap-2">
                    <a href="{{ route('configuration.ecoles') }}" class="btn btn-light">{{ __('configuration.annuler') }}</a>
                    <button type="submit" class="btn btn-danger"><i class="bx bx-trash me-1"></i>{{ __('configuration.eco_suppression_bouton') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
