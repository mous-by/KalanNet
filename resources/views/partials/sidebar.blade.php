@php
    $user = Auth::user();
    if (!$user) return;
@endphp

<!--start sidebar -->
<aside class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        {{-- <div>
            <img src="{{ asset('assets/images/logo_gsco1.png') }}" class="logo-icon" alt="logo icon">
        </div> --}}
        <div>
            <h4 class="logo-text fw-bold mb-0" style="color: var(--text-on-accent) !important; font-family: 'Arial Black', Impact, sans-serif; letter-spacing: 2px; text-transform: uppercase; text-shadow: 1px 1px 0 rgba(0,0,0,0.3), 2px 2px 0 rgba(0,0,0,0.2), 3px 3px 0 rgba(0,0,0,0.1), 4px 4px 4px rgba(0,0,0,0.4);">KALANNET</h4>
        </div>
        <div class="toggle-icon ms-auto" style="color: var(--text-on-accent) !important;"><i class="bi bi-list"></i></div>
    </div>
    <!--navigation-->
    <ul class="metismenu" id="menu">
        <li>
            <a href="{{ route('dashboard') }}">
                <div class="parent-icon"><i class="bi bi-house-fill"></i></div>
                <div class="menu-title">Tableau de Bord</div>
            </a>
        </li>
        
        <li class="menu-label">Pédagogie</li>
        
        @if ($user->userHasPermission('eleves_apercu'))
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-people-fill"></i></div>
                <div class="menu-title">Élèves & Parents</div>
            </a>
            <ul>
                <li><a href="{{ route('eleves.index') }}"><i class="bi bi-circle"></i>Liste des Élèves</a></li>
                <li><a href="{{ route('pedagogie.parents') }}"><i class="bi bi-circle"></i>Parents d'élèves</a></li>
                <li><a href="{{ route('inscriptions.create') }}"><i class="bi bi-circle"></i>Inscriptions</a></li>
                <li><a href="{{ route('inscriptions.group.create') }}"><i class="bi bi-circle"></i>Inscription par groupe</a></li>
            </ul>
        </li>
        @endif

        @if ($user->userHasPermission('enseignants_apercu'))
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-person-badge-fill"></i></div>
                <div class="menu-title">Enseignants</div>
            </a>
            <ul>
                <li><a href="{{ route('enseignants.index') }}"><i class="bi bi-circle"></i>Liste des Enseignants</a></li>
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('emargement_faire'))
                    <li><a href="{{ route('enseignants.emargements') }}"><i class="bi bi-circle"></i>Émargements</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('presence_apercu'))
                    <li><a href="{{ route('enseignants.presences') }}"><i class="bi bi-circle"></i>Cahier de présence</a></li>
                @endif
            </ul>
        </li>
        @endif

        @if ($user->userHasPermission('classes_apercu'))
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-building"></i></div>
                <div class="menu-title">Classes & Cours</div>
            </a>
            <ul>
                <li><a href="{{ route('classes.index') }}"><i class="bi bi-circle"></i>Classes</a></li>
                @if ($user->droit === 'SupAdmin')
                <li><a href="{{ route('classes.associations') }}"><i class="bi bi-circle"></i>Associer classes</a></li>
                @endif
                @if ($user->userHasPermission('matieres_apercu'))
                <li><a href="{{ route('pedagogie.matieres') }}"><i class="bi bi-circle"></i>Matières</a></li>
                @endif
                @if ($user->userHasPermission('programmes_apercu') || $user->droit === 'SupAdmin')
                <li><a href="{{ route('programmes.index') }}"><i class="bi bi-circle"></i>Programmes officiels</a></li>
                @endif
                <li><a href="{{ route('pedagogie.timetable') }}"><i class="bi bi-circle"></i>Emploi du temps</a></li>
            </ul>
        </li>
        @endif

        @if ($user->userHasPermission('evaluation_apercu'))
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="menu-title">Évaluations</div>
            </a>
            <ul>
                <li><a href="{{ route('evaluations.index') }}"><i class="bi bi-circle"></i>Notes & Évaluations</a></li>
                <li><a href="{{ route('evaluations.index') }}"><i class="bi bi-circle"></i>Générer Bulletins</a></li>
            </ul>
        </li>
        @endif

        <li class="menu-label">Gestion</li>
        
        @if ($user->userHasPermission('caisses_apercu') || $user->userHasPermission('banques_apercu') || $user->userHasPermission('paiements_apercu') || $user->userHasPermission('Planification de paiements_apercu') || $user->userHasPermission('planifications_apercu'))
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-wallet2"></i></div>
                <div class="menu-title">Finances</div>
            </a>
            <ul>
                @if ($user->userHasPermission('Planification de paiements_apercu') || $user->userHasPermission('planifications_apercu'))
                <li><a href="{{ route('finances.planifications') }}"><i class="bi bi-circle"></i>Planification</a></li>
                @endif
                <li><a href="{{ route('finances.paiements') }}"><i class="bi bi-circle"></i>Paiements Élèves</a></li>
                @if ($user->userHasPermission('historique_paiement_apercu'))
                <li><a href="{{ route('finances.paiements.historique') }}"><i class="bi bi-circle"></i>Historique paiements</a></li>
                @endif
                @if ($user->userHasPermission('caisses_apercu'))
                <li><a href="{{ route('finances.caisse') }}"><i class="bi bi-circle"></i>Journal de Caisse</a></li>
                @endif
                @if ($user->userHasPermission('banques_apercu'))
                <li><a href="{{ route('finances.banques') }}"><i class="bi bi-circle"></i>Banques</a></li>
                @endif
                @if ($user->userHasPermission('versements_apercu'))
                <li><a href="{{ route('finances.versements') }}"><i class="bi bi-circle"></i>Versements</a></li>
                @endif
                @if ($user->userHasPermission('retraits_apercu'))
                <li><a href="{{ route('finances.retraits') }}"><i class="bi bi-circle"></i>Retraits</a></li>
                @endif
            </ul>
        </li>
        @endif

        <li>
            <a href="{{ route('configuration.index') }}">
                <div class="parent-icon"><i class="bi bi-gear-fill"></i></div>
                <div class="menu-title">Configuration</div>
            </a>
        </li>

    </ul>
    <!--end navigation-->
</aside>
<!--end sidebar -->
