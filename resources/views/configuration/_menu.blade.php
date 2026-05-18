@php($connectedUser = Auth::user())

<div class="card theme-card shadow-sm h-100">
    <div class="card-header theme-header d-flex align-items-center">
        <i class="bi bi-gear-fill me-2"></i>
        <span class="fw-bold">Menu Configuration</span>
    </div>
    <div class="card-body p-2">
        <ul class="nav flex-column gap-2">
            @if($connectedUser->droit === 'SupAdmin')
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 {{ request()->routeIs('configuration.index') ? 'theme-pill-active fw-bold' : '' }}" href="{{ route('configuration.index') }}">
                        <span class="me-2"><i class="bi bi-grid"></i></span>
                        <span>Aperçu</span>
                    </a>
                </li>
            @endif
            @if(in_array($connectedUser->droit, ['SupAdmin', 'Admin'], true) || $connectedUser->userHasPermission('administrateur_tabsConfig'))
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 {{ request()->routeIs('configuration.utilisateurs') ? 'theme-pill-active fw-bold' : '' }}" href="{{ route('configuration.utilisateurs') }}">
                        <span class="me-2"><i class="bi bi-people"></i></span>
                        <span>Utilisateurs</span>
                    </a>
                </li>
            @endif
            @if($connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('ecoles_apercu'))
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 {{ request()->routeIs('configuration.ecoles') ? 'theme-pill-active fw-bold' : '' }}" href="{{ route('configuration.ecoles') }}">
                        <span class="me-2"><i class="bi bi-building"></i></span>
                        <span>Écoles</span>
                    </a>
                </li>
            @endif
            @if($connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('academies_apercu'))
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 {{ request()->routeIs('configuration.academies') ? 'theme-pill-active fw-bold' : '' }}" href="{{ route('configuration.academies') }}">
                        <span class="me-2"><i class="bi bi-bank"></i></span>
                        <span>Académies</span>
                    </a>
                </li>
            @endif
            @if($connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('dcap_apercu'))
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 {{ request()->routeIs('configuration.caps') ? 'theme-pill-active fw-bold' : '' }}" href="{{ route('configuration.caps') }}">
                        <span class="me-2"><i class="bi bi-diagram-3"></i></span>
                        <span>CAP</span>
                    </a>
                </li>
            @endif
            @if($connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('annees_scolaires_apercu'))
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 {{ request()->routeIs('configuration.annees') ? 'theme-pill-active fw-bold' : '' }}" href="{{ route('configuration.annees') }}">
                        <span class="me-2"><i class="bi bi-calendar3"></i></span>
                        <span>Années scolaires</span>
                    </a>
                </li>
            @endif
            @if($connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('types_notes_apercu'))
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 {{ request()->routeIs('configuration.types-notes') ? 'theme-pill-active fw-bold' : '' }}" href="{{ route('configuration.types-notes') }}">
                        <span class="me-2"><i class="bi bi-clipboard"></i></span>
                        <span>Type de notes</span>
                    </a>
                </li>
            @endif
            @if($connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('classes_officielles_apercu'))
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 {{ request()->routeIs('configuration.classes-officielles') ? 'theme-pill-active fw-bold' : '' }}" href="{{ route('configuration.classes-officielles') }}">
                        <span class="me-2"><i class="bi bi-building-check"></i></span>
                        <span>Classes officielles</span>
                    </a>
                </li>
            @endif
            @if($connectedUser->droit === 'SupAdmin' || $connectedUser->userHasPermission('status_controles_apercu'))
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 {{ request()->routeIs('configuration.status-controles') ? 'theme-pill-active fw-bold' : '' }}" href="{{ route('configuration.status-controles') }}">
                        <span class="me-2"><i class="bi bi-check"></i></span>
                        <span>Status Controle</span>
                    </a>
                </li>
            @endif
            @if($connectedUser->droit === 'SupAdmin')
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 {{ request()->routeIs('configuration.permissions') ? 'theme-pill-active fw-bold' : '' }}" href="{{ route('configuration.permissions') }}">
                        <span class="me-2"><i class="bi bi-shield-lock"></i></span>
                        <span>Permissions</span>
                    </a>
                </li>
            @endif
            @if(in_array($connectedUser->droit, ['SupAdmin', 'Admin'], true) || $connectedUser->userHasPermission('dae_permission') || $connectedUser->userHasPermission('dcap_permission'))
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center py-2 {{ request()->routeIs('configuration.utilisateurs.permissions.assigner') ? 'theme-pill-active fw-bold' : '' }}" href="{{ route('configuration.utilisateurs.permissions.assigner') }}">
                        <span class="me-2"><i class="bx bx-user-check"></i></span>
                        <span>Assigner permission</span>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</div>
