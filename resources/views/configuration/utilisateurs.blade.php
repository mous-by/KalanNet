@extends('layouts.app')

@php
    $connectedUser = Auth::user();
    $administrateurs = $utilisateurs->filter(fn ($u) => empty($u->id_enseignant) && empty($u->id_parent) && !in_array($u->droit, ['DAE', 'DCAP'], true));
    $enseignants = $utilisateurs->filter(fn ($u) => !empty($u->id_enseignant));
    $parents = $utilisateurs->filter(fn ($u) => !empty($u->id_parent));
    $daeUsers = $utilisateurs->filter(fn ($u) => $u->droit === 'DAE');
    $dcapUsers = $utilisateurs->filter(fn ($u) => $u->droit === 'DCAP');

    $tabs = [
        'administrateurs' => [
            'label' => 'Administrateurs',
            'permission' => 'administrateur_tabsConfig',
            'users' => $administrateurs,
        ],
        'enseignants' => [
            'label' => 'Enseignants',
            'permission' => 'enseignants_tabsConfig',
            'users' => $enseignants,
        ],
        'parents' => [
            'label' => 'Parents',
            'permission' => 'parents_tabsConfig',
            'users' => $parents,
        ],
        'dae' => [
            'label' => 'DAE',
            'permission' => 'dae_apercu',
            'users' => $daeUsers,
        ],
        'dcap' => [
            'label' => 'DCAP',
            'permission' => 'dcap_apercu',
            'users' => $dcapUsers,
        ],
    ];

    $visibleTabs = collect($tabs)->filter(fn ($tab) => $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission($tab['permission']));
    if ($visibleTabs->isEmpty()) {
        $visibleTabs = collect(['administrateurs' => $tabs['administrateurs']]);
    }
    $activeTab = $visibleTabs->keys()->first();

    $canSeeDaeActions = $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('dae_voiraction');
    $canSeeDcapActions = $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('dcap_voiraction');
@endphp

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">Aperçu</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Utilisateurs</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('configuration.partials.flash')

    <div class="row g-4">
        <div class="col-12 col-lg-3">
            @include('configuration._menu')
        </div>
        <div class="col-12 col-lg-9">
            <div class="card theme-card shadow-sm">
                <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold"><i class="bx bx-group me-2"></i>Liste des utilisateurs</h5>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <form action="{{ route('configuration.utilisateurs') }}" method="GET">
                            <input type="text" name="search" class="form-control" placeholder="Nom, email, fonction..." value="{{ request('search') }}">
                        </form>
                        @if(in_array($connectedUser->droit, ['SupAdmin', 'Admin'], true))
                            <a href="{{ route('configuration.utilisateurs.create') }}"
                               class="btn btn-sm d-flex align-items-center gap-1 shadow-sm text-white"
                               style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;">
                                <i class="bi bi-plus-lg"></i>
                                <span>Ajouter</span>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        @foreach($visibleTabs as $key => $tab)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $key === $activeTab ? 'active theme-pill-active' : '' }}" id="{{ $key }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $key }}" type="button" role="tab">
                                    {{ $tab['label'] }}
                                    <span class="badge theme-icon-soft ms-1">{{ $tab['users']->count() }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content">
                        @foreach($visibleTabs as $key => $tab)
                            <div class="tab-pane fade {{ $key === $activeTab ? 'show active' : '' }}" id="{{ $key }}" role="tabpanel">
                                @if($key === 'dae')
                                    @include('configuration.partials.users-table', [
                                        'users' => $tab['users'],
                                        'columns' => ['name', 'email', 'fonction', 'telephone', 'academie'],
                                        'showActions' => $canSeeDaeActions,
                                        'permissionAllowed' => $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('dae_permission'),
                                        'statusAllowed' => $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('dae_activer'),
                                    ])
                                @elseif($key === 'dcap')
                                    @include('configuration.partials.users-table', [
                                        'users' => $tab['users'],
                                        'columns' => ['name', 'email', 'fonction', 'telephone', 'cap'],
                                        'showActions' => $canSeeDcapActions,
                                        'permissionAllowed' => $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('dcap_permission'),
                                        'statusAllowed' => $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('dcap_activer'),
                                    ])
                                @elseif($key === 'enseignants' || $key === 'parents')
                                    @include('configuration.partials.users-table', [
                                        'users' => $tab['users'],
                                        'columns' => ['name', 'email', 'ecole', 'telephone'],
                                        'showActions' => true,
                                        'permissionAllowed' => true,
                                        'statusAllowed' => $connectedUser->droit === 'SupAdmin' || $connectedUser->droit === 'Admin',
                                    ])
                                @else
                                    @include('configuration.partials.users-table', [
                                        'users' => $tab['users'],
                                        'columns' => ['name', 'email', 'ecole', 'fonction', 'genre', 'telephone'],
                                        'showActions' => true,
                                        'permissionAllowed' => true,
                                        'statusAllowed' => $connectedUser->droit === 'SupAdmin' || $connectedUser->droit === 'Admin',
                                    ])
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
