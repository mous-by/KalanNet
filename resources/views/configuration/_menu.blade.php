@php
    $connectedUser = Auth::user();
    $showUsers  = in_array($connectedUser->droit, ['SupAdmin', 'Admin'], true)
                  || $connectedUser->userHasAnyPermission(['utilisateurs_apercu', 'administrateur_tabsConfig', 'enseignants_tabsConfig', 'parents_tabsConfig', 'dae_apercu', 'dcap_apercu']);
    $showAssign = in_array($connectedUser->droit, ['SupAdmin', 'Admin'], true)
                  || $connectedUser->userHasAnyPermission(['permissions_assigner', 'permission_assigner', 'dae_permission', 'dcap_permission']);
    $showEcoles    = $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('ecoles_apercu');
    // Reserve a l'Admin : c'est lui qui connait le systeme scolaire de son
    // propre pays, pas le SupAdmin (base au Mali) pour chaque pays ou
    // KalanNet s'etend -- meme principe que le libre-service academie/CAP.
    // Sans objet pour une Ecole de Sante (filiere/annee, pas d'examen
    // national de type DEF/BAC).
    $adminEcoleType = $connectedUser->droit === 'Admin' ? ($connectedUser->ecole->typeEcole ?? null) : null;
    $showPays      = $connectedUser->droit === 'Admin' && $adminEcoleType !== 'École de Santé';
    // Symétrique de Pays : les filières (Infirmier, Sage-femme...) ne
    // concernent QUE les Écoles de Santé.
    $showFilieres  = $connectedUser->droit === 'Admin' && $adminEcoleType === 'École de Santé';
    $showAcademies = $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('academies_apercu');
    $showCaps      = $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('dcap_apercu');
    $showAnnees    = $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('annees_scolaires_apercu');
    $showNotes     = $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('types_notes_apercu');
    $showClasses   = $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('classes_officielles_apercu');
    $showStatus    = $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('status_controles_apercu');
    $showPerms     = $connectedUser->droit === 'SupAdmin' || $connectedUser->userHasAnyPermission(['permissions_apercu', 'permission_voir']);
@endphp

<div class="card theme-card shadow-sm h-100">
    <div class="card-header theme-header d-flex align-items-center gap-2 py-3">
        <i class="bi bi-gear-fill fs-5"></i>
        <span class="fw-semibold">Configuration</span>
    </div>
    <div class="card-body p-2">

        {{-- Aperçu général (SupAdmin) --}}
        @if($connectedUser->droit === 'SupAdmin')
            <ul class="nav flex-column mb-1">
                <li class="nav-item">
                    @include('configuration._menu_link', ['route' => 'configuration.index', 'icon' => 'bi-grid-fill', 'label' => 'Aperçu'])
                </li>
            </ul>
            <hr class="my-2 opacity-25">
        @endif

        {{-- Utilisateurs --}}
        @if($showUsers || $showAssign)
            <p class="text-uppercase fw-bold px-2 mb-1" class="config-menu-section-label">Utilisateurs</p>
            <ul class="nav flex-column mb-1">
                @if($showUsers)
                    <li class="nav-item">
                        @include('configuration._menu_link', ['route' => 'configuration.utilisateurs', 'icon' => 'bi-people-fill', 'label' => 'Utilisateurs'])
                    </li>
                @endif
                @if($showAssign)
                    <li class="nav-item">
                        @include('configuration._menu_link', ['route' => 'configuration.utilisateurs.permissions.assigner', 'icon' => 'bi-person-check-fill', 'label' => 'Assigner permissions'])
                    </li>
                @endif
            </ul>
            <hr class="my-2 opacity-25">
        @endif

        {{-- Structure scolaire --}}
        @if($showEcoles || $showAcademies || $showCaps || $showPays || $showFilieres)
            <p class="text-uppercase fw-bold px-2 mb-1" class="config-menu-section-label">Structure</p>
            <ul class="nav flex-column mb-1">
                @if($showEcoles)
                    <li class="nav-item">
                        @include('configuration._menu_link', ['route' => 'configuration.ecoles', 'icon' => 'bi-building-fill', 'label' => 'Écoles'])
                    </li>
                @endif
                @if($showAcademies)
                    <li class="nav-item">
                        @include('configuration._menu_link', ['route' => 'configuration.academies', 'icon' => 'bi-bank2', 'label' => 'Académies'])
                    </li>
                @endif
                @if($showCaps)
                    <li class="nav-item">
                        @include('configuration._menu_link', ['route' => 'configuration.caps', 'icon' => 'bi-diagram-3-fill', 'label' => 'CAP'])
                    </li>
                @endif
                @if($showPays)
                    <li class="nav-item">
                        @include('configuration._menu_link', ['route' => 'configuration.pays', 'icon' => 'bi-globe-americas', 'label' => 'Pays'])
                    </li>
                @endif
                @if($showFilieres)
                    <li class="nav-item">
                        @include('configuration._menu_link', ['route' => 'configuration.filieres', 'icon' => 'bi-diagram-3-fill', 'label' => 'Filières'])
                    </li>
                @endif
            </ul>
            <hr class="my-2 opacity-25">
        @endif

        {{-- Revendeurs --}}
        @if($connectedUser->droit === 'SupAdmin')
            <p class="text-uppercase fw-bold px-2 mb-1" class="config-menu-section-label">Partenaires</p>
            <ul class="nav flex-column mb-1">
                <li class="nav-item">
                    @include('configuration._menu_link', ['route' => 'configuration.revendeurs', 'icon' => 'bi-briefcase-fill', 'label' => 'Revendeurs'])
                </li>
            </ul>
            <hr class="my-2 opacity-25">
        @endif

        {{-- Paramètres pédagogiques --}}
        @if($showAnnees || $showNotes || $showClasses || $showStatus)
            <p class="text-uppercase fw-bold px-2 mb-1" class="config-menu-section-label">Paramètres</p>
            <ul class="nav flex-column mb-1">
                @if($showAnnees)
                    <li class="nav-item">
                        @include('configuration._menu_link', ['route' => 'configuration.annees', 'icon' => 'bi-calendar3', 'label' => 'Années scolaires'])
                    </li>
                @endif
                @if($showNotes)
                    <li class="nav-item">
                        @include('configuration._menu_link', ['route' => 'configuration.types-notes', 'icon' => 'bi-clipboard2-check-fill', 'label' => 'Types de notes'])
                    </li>
                @endif
                @if($showClasses)
                    <li class="nav-item">
                        @include('configuration._menu_link', ['route' => 'configuration.classes-officielles', 'icon' => 'bi-building-check', 'label' => 'Classes officielles'])
                    </li>
                @endif
                @if($showStatus)
                    <li class="nav-item">
                        @include('configuration._menu_link', ['route' => 'configuration.status-controles', 'icon' => 'bi-check2-circle', 'label' => 'Statuts de contrôle'])
                    </li>
                @endif
            </ul>
            <hr class="my-2 opacity-25">
        @endif

        {{-- Sécurité --}}
        @if($showPerms)
            <p class="text-uppercase fw-bold px-2 mb-1" class="config-menu-section-label">Sécurité</p>
            <ul class="nav flex-column mb-1">
                <li class="nav-item">
                    @include('configuration._menu_link', ['route' => 'configuration.permissions', 'icon' => 'bi-shield-lock-fill', 'label' => 'Permissions'])
                </li>
            </ul>
        @endif

    </div>
</div>
