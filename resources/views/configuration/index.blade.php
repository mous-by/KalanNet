@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Aperçu</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-3">
            @include('configuration._menu')
        </div>
        <div class="col-12 col-lg-9">
            <div class="row g-3 mb-4">
                @foreach([
                    ['label' => 'Écoles', 'value' => $stats['ecoles'], 'icon' => 'bi-building', 'route' => route('configuration.ecoles')],
                    ['label' => 'Académies', 'value' => $stats['academies'], 'icon' => 'bi-bank', 'route' => route('configuration.academies')],
                    ['label' => 'CAP', 'value' => $stats['caps'], 'icon' => 'bi-diagram-3', 'route' => route('configuration.caps')],
                    ['label' => 'Années scolaires', 'value' => $stats['annees'], 'icon' => 'bi-calendar3', 'route' => route('configuration.annees')],
                    ['label' => 'Utilisateurs', 'value' => $stats['utilisateurs'], 'icon' => 'bi-people', 'route' => route('configuration.utilisateurs')],
                    ['label' => 'Permissions', 'value' => $stats['permissions'], 'icon' => 'bi-shield-lock', 'route' => route('configuration.permissions')],
                ] as $item)
                    <div class="col-md-6 col-xl-3">
                        <a href="{{ $item['route'] }}" class="text-decoration-none">
                            <div class="card theme-card shadow-sm h-100">
                                <div class="card-body d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-muted text-uppercase small fw-bold mb-1">{{ $item['label'] }}</p>
                                        <h3 class="fw-bold mb-0">{{ number_format($item['value']) }}</h3>
                                    </div>
                                    <div class="widget-icon theme-icon-box rounded-3">
                                        <i class="bi {{ $item['icon'] }} fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card theme-card shadow-sm">
                        <div class="card-header theme-header">
                            <h5 class="mb-0 fw-bold">Utilisateurs récents</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Fonction</th>
                                            <th>Droit</th>
                                            <th>École</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentUsers as $utilisateur)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">{{ $utilisateur->nomPrenom }}</div>
                                                    <small class="text-muted">{{ $utilisateur->email }}</small>
                                                </td>
                                                <td>{{ $utilisateur->fonction ?? 'N/A' }}</td>
                                                <td><span class="badge bg-light text-primary">{{ $utilisateur->droit ?? 'N/A' }}</span></td>
                                                <td>{{ $utilisateur->ecole->nomEcole ?? 'Toutes' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center py-4 text-muted">Aucun utilisateur trouvé.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card theme-card shadow-sm">
                        <div class="card-header theme-header">
                            <h5 class="mb-0 fw-bold">Années scolaires</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Année</th>
                                            <th>Début</th>
                                            <th>Fin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($annees as $annee)
                                            <tr>
                                                <td class="fw-bold">{{ $annee->annee }}</td>
                                                <td>{{ $annee->date_debut ?? 'N/A' }}</td>
                                                <td>{{ $annee->date_fin ?? 'N/A' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center py-4 text-muted">Aucune année trouvée.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .widget-icon { width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; }
    </style>
@endsection
