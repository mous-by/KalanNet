@php
    $user = Auth::user();
    if (!$user) return;
    $canOpenConfiguration = $user->droit === 'SupAdmin' || $user->userHasAnyPermission([
        'ecoles_apercu',
        'academies_apercu',
        'dcap_apercu',
        'annees_scolaires_apercu',
        'types_notes_apercu',
        'classes_officielles_apercu',
        'status_controles_apercu',
        'administrateur_tabsConfig',
        'enseignants_tabsConfig',
        'parents_tabsConfig',
        'utilisateurs_supprimer',
        'dae_permission',
        'dcap_permission',
        'permissions_apercu',
    ]);
    $financeMenuPermissions = [
        'finances_planifications_apercu',
        'paiements_apercu',
        'paiements_faire',
        'subventions_etat_apercu',
        'historique_paiement_apercu',
        'caisses_apercu',
        'decaissements_apercu',
        'banques_apercu',
        'versements_apercu',
        'retraits_apercu',
    ];
    $canOpenFinance = $user->droit === 'SupAdmin' || $user->userHasAnyPermission($financeMenuPermissions);
    $canOpenSubscriptions = $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['abonnements_apercu', 'abonnements_paiement', 'abonnements_configuration', 'abonnements_validation']);
    $studentsParentsMenuPermissions = [
        'eleves_apercu',
        'eleves_dossier',
        'dossiers_eleves_apercu',
        'parents_apercu',
        'inscriptions_apercu',
        'inscriptions_inscrire',
        'reinscriptions_apercu',
        'inscriptions_reinscrire',
    ];
    $canOpenStudentsParents = $user->droit !== 'parent' && ($user->droit === 'SupAdmin' || $user->userHasAnyPermission($studentsParentsMenuPermissions));
    $teacherMenuPermissions = [
        'enseignants_apercu',
        'enseignants_creation',
        'enseignants_création',
        'emargement_faire',
        'presence_apercu',
        'emargement_paiement enseignant',
        'presence_paiement enseignant',
        'emargement_etat de payement',
        'presence_etat de payement',
        'paiements_faire',
    ];
    $canOpenTeachers = $user->droit === 'SupAdmin' || $user->userHasAnyPermission($teacherMenuPermissions);
    $canOpenAnnouncements = $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['annonces_apercu', 'annonces_creation', 'annonces_supprimer']);
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
        
        @if ($canOpenStudentsParents)
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-people-fill"></i></div>
                <div class="menu-title">Élèves & Parents</div>
            </a>
            <ul>
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('eleves_apercu'))
                <li><a href="{{ route('eleves.index') }}"><i class="bi bi-circle"></i>Liste des Élèves</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['eleves_dossier', 'dossiers_eleves_apercu']))
                <li><a href="{{ route('eleves.dossiers') }}"><i class="bi bi-circle"></i>Dossiers élèves</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['eleves_dossier', 'dossiers_eleves_apercu']))
                <li><a href="{{ route('eleves.cartes') }}"><i class="bi bi-circle"></i>Cartes scolaires</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('parents_apercu'))
                <li><a href="{{ route('pedagogie.parents') }}"><i class="bi bi-circle"></i>Parents d'élèves</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['inscriptions_apercu', 'inscriptions_inscrire']))
                <li><a href="{{ route('inscriptions.create') }}"><i class="bi bi-circle"></i>Inscriptions</a></li>
                <li><a href="{{ route('inscriptions.group.create') }}"><i class="bi bi-circle"></i>Inscription par groupe</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['reinscriptions_apercu', 'inscriptions_reinscrire']))
                <li><a href="{{ route('inscriptions.reinscription') }}"><i class="bi bi-circle"></i>Réinscription</a></li>
                @endif
            </ul>
        </li>
        @endif

        @if ($canOpenTeachers)
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-person-badge-fill"></i></div>
                <div class="menu-title">Enseignants</div>
            </a>
            <ul>
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('enseignants_apercu'))
                    <li><a href="{{ route('enseignants.index') }}"><i class="bi bi-circle"></i>Liste des Enseignants</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['enseignants_creation', 'enseignants_création']))
                    <li><a href="{{ route('enseignants.create') }}"><i class="bi bi-circle"></i>Ajouter Enseignant</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('emargement_faire'))
                    <li><a href="{{ route('enseignants.emargements') }}"><i class="bi bi-circle"></i>Émargements</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('presence_apercu'))
                    <li><a href="{{ route('enseignants.presences') }}"><i class="bi bi-circle"></i>Cahier de présence</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['emargement_paiement enseignant', 'presence_paiement enseignant', 'paiements_faire']))
                    <li><a href="{{ route('enseignants.salaires') }}"><i class="bi bi-circle"></i>Salaires</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['emargement_etat de payement', 'presence_etat de payement', 'emargement_etat_de_payement', 'presence_etat_de_payement', 'paiements_faire']))
                    <li><a href="{{ route('enseignants.salaires.etat') }}"><i class="bi bi-circle"></i>État de paiement</a></li>
                @endif
            </ul>
        </li>
        @endif

        @if ($user->droit === 'enseignant' || $user->userHasPermission('classes_apercu') || $user->userHasPermission('enseignants_emploi') || $user->userHasPermission('planning_apercu') || $user->userHasAnyPermission(['programmes_apercu', 'programme_apercu', 'appercu_programm', 'programmes_pdf', 'voir_pdf_programme', 'programmes_creation', 'programme_création', 'programmes_modification', 'programme_modification', 'programmes_supprimer', 'programme_supprimer']))
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-building"></i></div>
                <div class="menu-title">Classes & Cours</div>
            </a>
            <ul>
                @if ($user->userHasPermission('classes_apercu'))
                <li><a href="{{ route('classes.index') }}"><i class="bi bi-circle"></i>Classes</a></li>
                @if ($user->droit === 'SupAdmin')
                <li><a href="{{ route('classes.associations') }}"><i class="bi bi-circle"></i>Associer classes</a></li>
                @endif
                @endif
                @if ($user->userHasPermission('matieres_apercu'))
                <li><a href="{{ route('pedagogie.matieres') }}"><i class="bi bi-circle"></i>Matières</a></li>
                @endif
                @if ($user->userHasAnyPermission(['programmes_apercu', 'programme_apercu', 'appercu_programm', 'programmes_pdf', 'voir_pdf_programme', 'programmes_creation', 'programme_création', 'programmes_modification', 'programme_modification', 'programmes_supprimer', 'programme_supprimer']) || $user->droit === 'SupAdmin')
                <li><a href="{{ route('programmes.index') }}"><i class="bi bi-circle"></i>Programmes officiels</a></li>
                @endif
                @if ($user->droit === 'enseignant' || $user->userHasPermission('classes_apercu') || $user->userHasPermission('enseignants_emploi') || $user->userHasPermission('planning_apercu'))
                <li><a href="{{ route('pedagogie.timetable') }}"><i class="bi bi-circle"></i>Emploi du temps</a></li>
                @endif
            </ul>
        </li>
        @endif

        @if ($user->droit === 'SupAdmin' || $user->userHasPermission('evaluation_apercu') || $user->userHasAnyPermission(['controle_apercu', 'controle_creation', 'controle_création']))
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="menu-title">Contrôles & Évaluations</div>
            </a>
            <ul>
                @if($user->userHasAnyPermission(['controle_apercu', 'controle_creation', 'controle_création']))
                <li><a href="{{ route('appels-epreuves.index') }}"><i class="bi bi-circle"></i>Appel de Présence</a></li>
                @endif
                @if($user->droit === 'SupAdmin' || $user->userHasPermission('evaluation_apercu'))
                <li><a href="{{ route('evaluations.index') }}"><i class="bi bi-circle"></i>Notes & Évaluations</a></li>
                <li><a href="{{ route('pedagogie.resultats-nationaux.index') }}"><i class="bi bi-circle"></i>Résultats nationaux</a></li>
                <li><a href="{{ route('pedagogie.bulletins.classes') }}"><i class="bi bi-circle"></i>Générer Bulletins</a></li>
                @endif
            </ul>
        </li>
        @endif

        <li class="menu-label">Gestion</li>

        @if($canOpenAnnouncements)
            <li>
                <a href="{{ route('annonces.index') }}">
                    <div class="parent-icon"><i class="bi bi-megaphone-fill"></i></div>
                    <div class="menu-title">Annonces</div>
                </a>
            </li>
        @endif
        
        @if ($canOpenFinance)
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-wallet2"></i></div>
                <div class="menu-title">Finances</div>
            </a>
            <ul>
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('finances_planifications_apercu'))
                <li><a href="{{ route('finances.planifications') }}"><i class="bi bi-circle"></i>Planification</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['paiements_apercu', 'paiements_faire']))
                <li><a href="{{ route('finances.paiements') }}"><i class="bi bi-circle"></i>Paiements Élèves</a></li>
                @endif
                @php
                    $isFondamentale = $user->ecole && in_array($user->ecole->typeEcole, ['Fondamentale I', 'Fondamentale II', 'Fondamental I', 'Fondamental II']);
                @endphp
                @if (!$isFondamentale && ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['subventions_etat_apercu', 'paiements_apercu'])))
                <li><a href="{{ route('finances.subventions-etat') }}"><i class="bi bi-circle"></i>Subventions État</a></li>
                @endif
                @if ($user->userHasPermission('historique_paiement_apercu'))
                <li><a href="{{ route('finances.paiements.historique') }}"><i class="bi bi-circle"></i>Historique paiements</a></li>
                @endif
                @if ($user->userHasPermission('caisses_apercu'))
                <li><a href="{{ route('finances.caisse') }}"><i class="bi bi-circle"></i>Journal de Caisse</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('decaissements_apercu'))
                <li><a href="{{ route('finances.depenses') }}"><i class="bi bi-circle"></i>Dépenses</a></li>
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

        @if($canOpenSubscriptions)
            <li>
                <a href="{{ route('abonnements.index') }}">
                    <div class="parent-icon"><i class="bi bi-credit-card-2-front-fill"></i></div>
                    <div class="menu-title">Abonnements</div>
                </a>
            </li>
        @endif

        @if($canOpenConfiguration)
            <li>
                <a href="{{ route('configuration.index') }}">
                    <div class="parent-icon"><i class="bi bi-gear-fill"></i></div>
                    <div class="menu-title">Configuration</div>
                </a>
            </li>
        @endif

        <li>
            <a href="{{ route('documentation.index') }}">
                <div class="parent-icon"><i class="bi bi-book-fill"></i></div>
                <div class="menu-title">Documentation</div>
            </a>
        </li>

    </ul>
    <!--end navigation-->
</aside>
<!--end sidebar -->
