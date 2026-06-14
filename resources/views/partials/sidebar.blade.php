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
    $canViewEvaluations = $user->droit === 'SupAdmin' || $user->userHasPermission('evaluation_apercu');
    $canViewNationalResults = $user->droit === 'SupAdmin' || $user->userHasAnyPermission([
        'resultats_nationaux_apercu',
        'resultats nationaux_apercu',
        'resultats_def_terminal_apercu',
        'reinscriptions_apercu',
        'inscriptions_reinscrire',
    ]);
    $canGenerateBulletins = $user->droit === 'SupAdmin' || $user->userHasAnyPermission([
        'bulletins_apercu',
        'bulletins_generation',
        'bulletins_génération',
        'bulletins_pdf',
        'bulletins_impression',
        'bulletins_publication',
        'generer_bulletins',
        'générer_bulletins',
    ]);
    $canOpenEvaluationsMenu = $canViewEvaluations
        || $canViewNationalResults
        || $canGenerateBulletins
        || $user->userHasAnyPermission(['controle_apercu', 'controle_creation', 'controle_création']);
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
                <div class="menu-title">{{ __('messages.menu.dashboard') }}</div>
            </a>
        </li>
        
        <li class="menu-label">{{ __('messages.menu.pedagogy') }}</li>
        
        @if ($canOpenStudentsParents)
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-people-fill"></i></div>
                <div class="menu-title">{{ __('messages.menu.students_parents') }}</div>
            </a>
            <ul>
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('eleves_apercu'))
                <li><a href="{{ route('eleves.index') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.students_list') }}</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['eleves_dossier', 'dossiers_eleves_apercu']))
                <li><a href="{{ route('eleves.dossiers') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.student_files') }}</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['eleves_dossier', 'dossiers_eleves_apercu']))
                <li><a href="{{ route('eleves.cartes') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.student_cards') }}</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('parents_apercu'))
                <li><a href="{{ route('pedagogie.parents') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.student_parents') }}</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['inscriptions_apercu', 'inscriptions_inscrire']))
                <li><a href="{{ route('inscriptions.create') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.registrations') }}</a></li>
                <li><a href="{{ route('inscriptions.group.create') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.group_registration') }}</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['reinscriptions_apercu', 'inscriptions_reinscrire']))
                <li><a href="{{ route('inscriptions.reinscription') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.re_registration') }}</a></li>
                @endif
            </ul>
        </li>
        @endif

        @if ($canOpenTeachers)
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-person-badge-fill"></i></div>
                <div class="menu-title">{{ __('messages.menu.teachers') }}</div>
            </a>
            <ul>
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('enseignants_apercu'))
                    <li><a href="{{ route('enseignants.index') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.teachers_list') }}</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['enseignants_creation', 'enseignants_création']))
                    <li><a href="{{ route('enseignants.create') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.add_teacher') }}</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('emargement_faire'))
                    <li><a href="{{ route('enseignants.emargements') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.emargements') }}</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('presence_apercu'))
                    <li><a href="{{ route('enseignants.presences') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.attendance_book') }}</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['emargement_paiement enseignant', 'presence_paiement enseignant', 'paiements_faire']))
                    <li><a href="{{ route('enseignants.salaires') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.salaries') }}</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['emargement_etat de payement', 'presence_etat de payement', 'emargement_etat_de_payement', 'presence_etat_de_payement', 'paiements_faire']))
                    <li><a href="{{ route('enseignants.salaires.etat') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.payment_status') }}</a></li>
                @endif
            </ul>
        </li>
        @endif

        @if ($user->droit === 'enseignant' || $user->userHasPermission('classes_apercu') || $user->userHasPermission('enseignants_emploi') || $user->userHasPermission('planning_apercu') || $user->userHasAnyPermission(['programmes_apercu', 'programme_apercu', 'appercu_programm', 'programmes_pdf', 'voir_pdf_programme', 'programmes_creation', 'programme_création', 'programmes_modification', 'programme_modification', 'programmes_supprimer', 'programme_supprimer']))
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-building"></i></div>
                <div class="menu-title">{{ __('messages.menu.classes_courses') }}</div>
            </a>
            <ul>
                @if ($user->userHasPermission('classes_apercu'))
                <li><a href="{{ route('classes.index') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.classes') }}</a></li>
                @if ($user->droit === 'SupAdmin')
                <li><a href="{{ route('classes.associations') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.associate_classes') }}</a></li>
                @endif
                @endif
                @if ($user->userHasPermission('matieres_apercu'))
                <li><a href="{{ route('pedagogie.matieres') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.subjects') }}</a></li>
                @endif
                @if ($user->userHasAnyPermission(['programmes_apercu', 'programme_apercu', 'appercu_programm', 'programmes_pdf', 'voir_pdf_programme', 'programmes_creation', 'programme_création', 'programmes_modification', 'programme_modification', 'programmes_supprimer', 'programme_supprimer']) || $user->droit === 'SupAdmin')
                <li><a href="{{ route('programmes.index') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.official_programs') }}</a></li>
                @endif
                @if ($user->droit === 'enseignant' || $user->userHasPermission('classes_apercu') || $user->userHasPermission('enseignants_emploi') || $user->userHasPermission('planning_apercu'))
                <li><a href="{{ route('pedagogie.timetable') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.timetable') }}</a></li>
                @endif
            </ul>
        </li>
        @endif

        @if ($canOpenEvaluationsMenu)
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="menu-title">{{ __('messages.menu.tests_evaluations') }}</div>
            </a>
            <ul>
                @if($user->userHasAnyPermission(['controle_apercu', 'controle_creation', 'controle_création']))
                <li><a href="{{ route('appels-epreuves.index') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.presence_call') }}</a></li>
                @endif
                @if($canViewEvaluations)
                <li><a href="{{ route('evaluations.index') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.grades_evaluations') }}</a></li>
                @endif
                @if($canViewNationalResults)
                <li><a href="{{ route('pedagogie.resultats-nationaux.index') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.national_results') }}</a></li>
                @endif
                @if($canGenerateBulletins)
                <li><a href="{{ route('pedagogie.bulletins.classes') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.generate_bulletins') }}</a></li>
                @endif
            </ul>
        </li>
        @endif

        <li class="menu-label">{{ __('messages.menu.management') }}</li>

        @if($canOpenAnnouncements)
            <li>
                <a href="{{ route('annonces.index') }}">
                    <div class="parent-icon"><i class="bi bi-megaphone-fill"></i></div>
                    <div class="menu-title">{{ __('messages.menu.announcements') }}</div>
                </a>
            </li>
        @endif
        
        @if ($canOpenFinance)
        <li>
            <a href="javascript:;" class="has-arrow">
                <div class="parent-icon"><i class="bi bi-wallet2"></i></div>
                <div class="menu-title">{{ __('messages.menu.finances') }}</div>
            </a>
            <ul>
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('finances_planifications_apercu'))
                <li><a href="{{ route('finances.planifications') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.planning') }}</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['paiements_apercu', 'paiements_faire']))
                <li><a href="{{ route('finances.paiements') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.student_payments') }}</a></li>
                @endif
                @php
                    $isFondamentale = $user->ecole && in_array($user->ecole->typeEcole, ['Fondamentale I', 'Fondamentale II', 'Fondamental I', 'Fondamental II']);
                @endphp
                @if (!$isFondamentale && ($user->droit === 'SupAdmin' || $user->userHasAnyPermission(['subventions_etat_apercu', 'paiements_apercu'])))
                <li><a href="{{ route('finances.subventions-etat') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.state_subsidies') }}</a></li>
                @endif
                @if ($user->userHasPermission('historique_paiement_apercu'))
                <li><a href="{{ route('finances.paiements.historique') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.payment_history') }}</a></li>
                @endif
                @if ($user->userHasPermission('caisses_apercu'))
                <li><a href="{{ route('finances.caisse') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.cash_journal') }}</a></li>
                @endif
                @if ($user->droit === 'SupAdmin' || $user->userHasPermission('decaissements_apercu'))
                <li><a href="{{ route('finances.depenses') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.expenses') }}</a></li>
                @endif
                @if ($user->userHasPermission('banques_apercu'))
                <li><a href="{{ route('finances.banques') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.banks') }}</a></li>
                @endif
                @if ($user->userHasPermission('versements_apercu'))
                <li><a href="{{ route('finances.versements') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.deposits') }}</a></li>
                @endif
                @if ($user->userHasPermission('retraits_apercu'))
                <li><a href="{{ route('finances.retraits') }}"><i class="bi bi-circle"></i>{{ __('messages.menu.withdrawals') }}</a></li>
                @endif
            </ul>
        </li>
        @endif

        @if($canOpenSubscriptions)
            <li>
                <a href="{{ route('abonnements.index') }}">
                    <div class="parent-icon"><i class="bi bi-credit-card-2-front-fill"></i></div>
                    <div class="menu-title">{{ __('messages.menu.subscriptions') }}</div>
                </a>
            </li>
        @endif

        @if($canOpenConfiguration)
            <li>
                <a href="{{ route('configuration.index') }}">
                    <div class="parent-icon"><i class="bi bi-gear-fill"></i></div>
                    <div class="menu-title">{{ __('messages.menu.configuration') }}</div>
                </a>
            </li>
        @endif

        <li>
            <a href="{{ route('documentation.index') }}">
                <div class="parent-icon"><i class="bi bi-book-fill"></i></div>
                <div class="menu-title">{{ __('messages.menu.documentation') }}</div>
            </a>
        </li>

    </ul>
    <!--end navigation-->
</aside>
<!--end sidebar -->
