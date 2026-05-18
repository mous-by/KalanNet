@extends('layouts.app')

@php
    $icons = [
        'enseignants' => 'bx-user',
        'matieres' => 'bx-book',
        'bulletins' => 'bx-file',
        'notes' => 'bx-edit',
        'eleves' => 'bx-group',
        'classes' => 'bx-buildings',
        'planning' => 'bx-calendar',
        'planifications' => 'bx-calendar',
        'inscriptions' => 'bx-user-plus',
        'parents' => 'bx-group',
        'paiements' => 'bx-money',
        'caisses' => 'bx-wallet',
        'encaissement' => 'bx-arrow-to-bottom',
        'decaissements' => 'bx-arrow-to-top',
        'banques' => 'bx-building-house',
        'mouvements' => 'bx-transfer',
        'configurations' => 'bx-cog',
        'evaluation' => 'bx-check-square',
        'controle' => 'bx-list-check',
        'controles' => 'bx-list-check',
        'programmes' => 'bx-layer',
        'profiles' => 'bx-user-circle',
        'assistant_ia' => 'bx-cog',
        'autres' => 'bx-key',
    ];
    $totalPermissions = collect($groupedPermissions)->flatten(1)->count();
@endphp

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.utilisateurs') }}">Utilisateurs</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Permissions</li>
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
                <div class="card theme-card shadow-sm mb-4">
                    <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="mb-0 fw-bold">
                            <i class="bx bx-user-check me-2"></i>Assignation de permissions
                        </h5>
                        <a href="{{ route('configuration.utilisateurs') }}" class="btn btn-light px-4">
                            <i class="bx bx-arrow-back me-2"></i>Retour
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('configuration.utilisateurs.permissions.assigner') }}" id="selectUserPermissionForm">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Utilisateur</label>
                                    <select name="user_id" id="permission_user_id" class="form-select" required>
                                        <option value="">Choisir un utilisateur</option>
                                        @foreach($availableUsers as $userItem)
                                            <option value="{{ $userItem->idUtilisateur }}" @selected(optional($utilisateur)->idUtilisateur === $userItem->idUtilisateur)>
                                                {{ $userItem->nomPrenom }} - {{ $userItem->droit ?? 'N/A' }} - {{ $userItem->ecole->nomEcole ?? $userItem->academie->nom_academie ?? $userItem->cap->nom_cap ?? 'Toutes les écoles' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Filtrer les permissions</label>
                                    <input type="text" id="permissionFilter" class="form-control" placeholder="Ex: création, eleves...">
                                </div>
                            </div>
                        </form>

                        @if($utilisateur)
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4 pt-3 border-top">
                                <div>
                                    <h4 class="fw-bold mb-1">{{ $utilisateur->nomPrenom }}</h4>
                                    <div class="text-muted">
                                        {{ $utilisateur->email ?? 'Email non renseigné' }} - {{ $utilisateur->droit ?? 'Droit non renseigné' }} - {{ $utilisateur->ecole->nomEcole ?? 'Toutes les écoles' }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge theme-icon-soft fs-6 px-3 py-2">{{ count($userPermissionIds) }} / {{ $totalPermissions }} cochées</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if($utilisateur)
            <form method="POST" action="{{ route('configuration.utilisateurs.permissions.update', $utilisateur->idUtilisateur) }}">
                @csrf
                @method('PUT')

                <div class="card theme-card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between">
                            <div>
                                <strong>Organisation:</strong>
                                permissions triées par module puis par action, comme Alliance-Team.
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" id="select_all_permissions">
                                    <label class="form-check-label fw-bold" for="select_all_permissions">Tout cocher/décocher</label>
                                </div>
                                <button type="submit" class="btn btn-primary shadow-sm" style="background-color: var(--theme-accent) !important; border-color: var(--theme-accent) !important;">
                                    <i class="bx bx-save me-2"></i>Enregistrer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach($groupedPermissions as $module => $permissions)
                        <div class="col-12">
                            <div class="card theme-card shadow-sm">
                                <div class="card-header theme-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="fw-bold">
                                        <i class="bx {{ $icons[$module] ?? 'bx-key' }} me-2"></i>
                                        {{ $permissions[0]->module_display ?? ucwords(str_replace('_', ' ', $module)) }}
                                        <span class="badge theme-header-badge ms-2">{{ count($permissions) }}</span>
                                    </div>
                                    <div class="form-check m-0">
                                        <input class="form-check-input module-checkbox" type="checkbox" id="module_{{ $module }}" data-module="{{ $module }}">
                                        <label class="form-check-label" for="module_{{ $module }}">Tout le module</label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        @foreach($permissions as $permission)
                                            @php
                                                $checked = in_array((int) $permission->id, $userPermissionIds, true) || in_array($permission->name, $userPermissionNames, true);
                                                $inputId = 'perm_' . $permission->id;
                                            @endphp
                                            <div class="col-md-4 col-xl-3">
                                                <div class="form-check theme-permission-check">
                                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="{{ $inputId }}" data-module="{{ $module }}" @checked($checked)>
                                                    <label class="form-check-label" for="{{ $inputId }}">
                                                        {{ ucfirst(str_replace('_', ' ', $permission->action)) }}
                                                        <small class="d-block text-muted">{{ $permission->name }}</small>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="text-end mt-4 mb-5 pb-5">
                    <button type="submit" class="btn btn-primary px-5 shadow-sm" style="background-color: var(--theme-accent) !important; border-color: var(--theme-accent) !important;">
                        <i class="bx bx-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
                @else
                    <div class="card theme-card shadow-sm">
                        <div class="card-body text-center py-5 text-muted">
                            <i class="bx bx-user-check d-block mb-3" style="font-size: 3rem;"></i>
                            Sélectionnez un utilisateur pour charger ses permissions cochées.
                        </div>
                    </div>
                @endif
        </div>
    </div>

    <style>
        html[data-theme] .theme-header-badge {
            background-color: var(--accent-light) !important;
            border: 1px solid rgba(255,255,255,0.28);
            color: var(--text-on-accent) !important;
        }
        html[data-theme] .theme-permission-check {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 12px 10px 36px;
            min-height: 64px;
            background: var(--bg-card);
        }
        html[data-theme] .theme-permission-check:has(.form-check-input:checked) {
            background: var(--accent-light);
            border-color: var(--theme-primary);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const permissionBoxes = Array.from(document.querySelectorAll('.permission-checkbox'));
            const selectAll = document.getElementById('select_all_permissions');
            const moduleBoxes = Array.from(document.querySelectorAll('.module-checkbox'));
            const userSelect = document.getElementById('permission_user_id');

            userSelect?.addEventListener('change', function () {
                if (userSelect.value) {
                    document.getElementById('selectUserPermissionForm').submit();
                }
            });

            if (!selectAll) return;

            function syncModule(module) {
                const items = permissionBoxes.filter((box) => box.dataset.module === module);
                const moduleBox = document.querySelector(`.module-checkbox[data-module="${module}"]`);
                if (!moduleBox || items.length === 0) return;
                const checkedCount = items.filter((box) => box.checked).length;
                moduleBox.checked = checkedCount === items.length;
                moduleBox.indeterminate = checkedCount > 0 && checkedCount < items.length;
            }

            function syncAll() {
                const checkedCount = permissionBoxes.filter((box) => box.checked).length;
                selectAll.checked = checkedCount === permissionBoxes.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < permissionBoxes.length;
                moduleBoxes.forEach((box) => syncModule(box.dataset.module));
            }

            selectAll.addEventListener('change', function () {
                permissionBoxes.forEach((box) => box.checked = selectAll.checked);
                syncAll();
            });

            moduleBoxes.forEach((box) => {
                box.addEventListener('change', function () {
                    permissionBoxes
                        .filter((permissionBox) => permissionBox.dataset.module === box.dataset.module)
                        .forEach((permissionBox) => permissionBox.checked = box.checked);
                    syncAll();
                });
            });

            permissionBoxes.forEach((box) => box.addEventListener('change', syncAll));
            syncAll();

            // Filtrage dynamique des permissions
            const filterInput = document.getElementById('permissionFilter');
            if (filterInput) {
                filterInput.addEventListener('input', function() {
                    const term = this.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                    const permissionContainers = document.querySelectorAll('.theme-permission-check');
                    
                    permissionContainers.forEach(container => {
                        const labelText = container.textContent.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                        const colElement = container.closest('.col-md-4');
                        if (labelText.includes(term)) {
                            colElement.style.display = '';
                        } else {
                            colElement.style.display = 'none';
                        }
                    });

                    // Cacher les modules entiers s'ils sont vides
                    document.querySelectorAll('.card.theme-card').forEach(moduleCard => {
                        if(moduleCard.querySelector('.theme-permission-check')) {
                            const visiblePerms = Array.from(moduleCard.querySelectorAll('.col-md-4')).some(col => col.style.display !== 'none');
                            moduleCard.parentElement.style.display = visiblePerms ? '' : 'none';
                        }
                    });
                });
            }
        });
    </script>
@endsection
