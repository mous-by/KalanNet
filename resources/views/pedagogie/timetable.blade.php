@extends('layouts.app')

@section('content')
    @php
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $heures = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Emploi du Temps</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Emploi du Temps</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="col-12">
        <div class="card theme-card shadow-sm">
            <div class="card-body p-4">
                <p class="mb-2 fw-bold text-muted">Filtrer par</p>
                <form action="{{ route('pedagogie.timetable.filter') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label" for="id_classe">Classe</label>
                        <select name="id_classe" id="id_classe" class="form-select" onchange="this.form.submit()">
                            <option value="">Sélectionner une classe</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id_classe }}" @selected(session('timetable_id_classe') == $classe->id_classe)>
                                    {{ $classe->nom_classe }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="id_annee">Année scolaire</label>
                        <select name="id_annee" id="id_annee" class="form-select" onchange="this.form.submit()">
                            <option value="">Sélectionner une année</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id_anneeScolaire }}" @selected(session('timetable_id_annee') == $annee->id_anneeScolaire)>
                                    {{ $annee->annee }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="{{ route('pedagogie.timetable', ['reset' => 1]) }}" class="btn btn-light w-100">Réinitialiser</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row align-items-center mb-3">
        <div class="col-md-12 pt-2">
            @if($selectedClasse && $selectedAnnee)
                <a href="{{ route('pedagogie.timetable.download_pdf') }}" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm" style="font-size: 14px; border-radius: 8px;">
                    <i class="bi bi-file-earmark-pdf-fill me-2 fs-5"></i>Télécharger / Imprimer l'Emploi du Temps en PDF (Paysage, Une Page)
                </a>
            @else
                <button type="button" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm" disabled style="font-size: 14px; border-radius: 8px;">
                    <i class="bi bi-file-earmark-pdf-fill me-2 fs-5"></i>Imprimer l'Emploi du Temps (Sélectionnez une classe)
                </button>
            @endif
        </div>
    </div>

    <div class="card theme-card shadow-sm mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 gap-3 no-print">
                <ul class="nav nav-pills" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active px-4 py-2 theme-pill-active">
                            <i class="bi bi-calendar-week me-2"></i>Planning hebdomadaire
                        </button>
                    </li>
                </ul>
                
                @if($selectedClasse && $selectedAnnee)
                    <div class="d-flex align-items-center gap-3">
                        {{-- Mode Toggle --}}
                        <div class="btn-group shadow-sm" role="group" aria-label="Mode affichage">
                            <input type="radio" class="btn-check" name="grid_mode" id="mode_lecture" autocomplete="off" checked onclick="setGridMode('lecture')">
                            <label class="btn btn-outline-primary px-3 py-2 fw-semibold" for="mode_lecture" style="font-size: 13px;">
                                <i class="bi bi-eye me-1"></i> Mode Lecture / Impression
                            </label>
                            
                            <input type="radio" class="btn-check" name="grid_mode" id="mode_edition" autocomplete="off" onclick="setGridMode('edition')">
                            <label class="btn btn-outline-primary px-3 py-2 fw-semibold" for="mode_edition" style="font-size: 13px;">
                                <i class="bi bi-pencil-square me-1"></i> Mode Édition
                            </label>
                        </div>
                        
                        <button type="submit" form="timetableGridForm" class="btn btn-success px-4 py-2 fw-bold shadow-sm d-none btn-enregistre-top">
                            <i class="bi bi-check2-circle me-2"></i>Enregistrer
                        </button>
                    </div>
                @endif
            </div>

            @if($selectedClasse && $selectedAnnee)
                <form action="{{ route('pedagogie.timetable.save_grid') }}" method="POST" id="timetableGridForm">
                    @csrf
                    @foreach($heures as $hr)
                        <input type="hidden" name="recesses[{{ $hr }}]" id="recess_after_{{ str_replace(':', '_', $hr) }}" value="{{ isset($recesses[$hr]) && $recesses[$hr] == '1' ? '1' : '0' }}">
                    @endforeach

                    <div id="emploi" class="p-3 bg-white">
                        {{-- Alliance (GESCO) School Header Layout --}}
                        <div id="header_ecole" class="mb-4">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    @if($ecole && $ecole->logoEcole)
                                        <td style="width: 75px; vertical-align: middle; text-align: left; padding-right: 15px;">
                                            <img src="{{ asset('images_ecoles/' . basename($ecole->logoEcole)) }}" width="65" height="65" style="border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0;">
                                        </td>
                                    @endif
                                    <td style="vertical-align: middle; text-align: center;">
                                        <h4 style="font-weight: bold; text-decoration: underline; text-transform: uppercase; margin: 0; color: #1e293b; font-size: 19px;">
                                            {{ $ecole->nomEcole ?? config('app.name', 'KalanNet') }}
                                        </h4>
                                        <div style="font-weight: bold; text-transform: uppercase; font-size: 13px; margin-top: 5px; color: #475569; letter-spacing: 0.5px;">
                                            EMPLOI DU TEMPS DE CLASSE
                                        </div>
                                        <div class="small fw-semibold text-muted mt-1">Classe : {{ $selectedClasse->nom_classe }} - Année scolaire : {{ $selectedAnnee->annee }}</div>
                                    </td>
                                </tr>
                            </table>
                            <hr style="border-top: 2px double #cbd5e1; margin-top: 15px; margin-bottom: 20px;">
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle text-center" style="width:100%;">
                                <thead id="emploi_du_temps_thead" class="table-light fw-bold">
                                    <tr>
                                        <th class="text-center bg-light" style="width: 12%; font-size: 13px;">Heure / Jour</th>
                                        @foreach($jours as $jour)
                                            <th class="text-center bg-light" style="font-size: 13px;">{{ $jour }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $loopIndex = 0;
                                    @endphp
                                    @foreach($heures as $heure)
                                        @php
                                            $loopIndex++;
                                            $recessDuration = isset($recesses[$heure]) && (int)$recesses[$heure] > 0 ? (int)$recesses[$heure] : 0;
                                            $hasRecessAfter = $recessDuration > 0;
                                            
                                            $heureParts = explode(':', $heure);
                                            $startHour = (int) $heureParts[0];
                                            $endHour = ($startHour + 1) % 24;
                                            $endHourFormatted = str_pad($endHour, 2, '0', STR_PAD_LEFT) . ':00';
                                        @endphp
                                        <tr data-hour="{{ $heure }}">
                                            <td class="text-center fw-bold bg-white align-middle text-primary" style="white-space: nowrap; font-size: 13px; padding: 12px 6px;">
                                                {{ $heure }} - {{ $endHourFormatted }}
                                            </td>
                                            @foreach($jours as $jour)
                                                <td class="p-2 align-middle text-center position-relative" style="min-width: 165px; height: 110px;">
                                                    @php
                                                        $courses = $timetable[$jour] ?? collect();
                                                        // Find the course at this hour slot
                                                        $course = $courses->first(fn ($c) => substr($c->heure_debut, 0, 5) <= $heure && substr($c->heure_fin, 0, 5) > $heure);
                                                        $courseId = $course ? $course->id : '';
                                                        $matiereId = $course ? $course->id_matiere : '';
                                                        $enseignantId = $course ? $course->id_enseignant : '';
                                                        $heureDebutVal = $course ? substr($course->heure_debut, 0, 5) : $heure;
                                                        $heureFinVal = $course ? substr($course->heure_fin, 0, 5) : $endHourFormatted;
                                                    @endphp
                                                    
                                                    {{-- Clean View Mode (GESCO Alliance Style with dynamic croix-case for empty cells) --}}
                                                    <div class="timetable-view-block py-2">
                                                        @if($course)
                                                            <div class="fw-bold text-primary mb-1" style="font-size: 13px;">{{ $course->matiere->nom_matiere ?? 'Matière' }}</div>
                                                            <div class="text-muted small mb-1" style="font-size: 11px;">{{ $course->enseignant->nom_prenom_enseignant ?? 'Pas d\'enseignant' }}</div>
                                                        @else
                                                            <div class="d-flex align-items-center justify-content-center" style="height: 30px;">
                                                                <span class="croix-case"></span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    
                                                    {{-- Edit Mode Container (Hidden by default, not printable) --}}
                                                    <div class="timetable-edit-block d-none no-print quick-add-cell-container p-2 border rounded shadow-xs bg-white text-start" style="border: 1px solid #cbd5e1; transition: all 0.2s;">
                                                        <input type="hidden" name="slots[{{ $jour }}][{{ $heure }}][id]" value="{{ $courseId }}">
                                                        
                                                        {{-- Clear Cross Button --}}
                                                        <button type="button" class="btn btn-link text-danger position-absolute p-0 btn-clear-slot {{ $courseId ? '' : 'd-none' }}" style="top: 2px; right: 2px; z-index: 10; line-height: 1; background: white; border-radius: 50%;" title="Supprimer ce cours" onclick="clearSlot(this)">
                                                            <i class="bi bi-x-circle-fill fs-5"></i>
                                                        </button>
                                                        
                                                        {{-- Matière Select (Large and Prominent) --}}
                                                        <div class="mb-2">
                                                            <select name="slots[{{ $jour }}][{{ $heure }}][id_matiere]" class="form-select quick-select-matiere border-primary-subtle fw-semibold" style="font-size: 12px; font-weight: 600; padding: 6px 10px; border-radius: 6px; border: 2px solid var(--theme-primary) !important;" onchange="onMatiereChange(this)">
                                                                <option value="" class="fw-bold">+ Cours (Libre)</option>
                                                                @foreach($matieres as $matiere)
                                                                    <option value="{{ $matiere->id_matiere }}" @selected($matiereId == $matiere->id_matiere)>
                                                                        {{ $matiere->nom_matiere }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        
                                                        {{-- Enseignant Select --}}
                                                        <div class="mb-2 quick-enseignant-wrapper {{ $matiereId ? '' : 'd-none' }}">
                                                            <select name="slots[{{ $jour }}][{{ $heure }}][id_enseignant]" class="form-select form-select-sm quick-select-enseignant border-light-subtle" style="font-size: 11px; padding: 4px 8px; border-radius: 5px;">
                                                                <option value="">Pas d'enseignant</option>
                                                                @foreach($enseignants as $enseignant)
                                                                    <option value="{{ $enseignant->id_enseignant }}" @selected($enseignantId == $enseignant->id_enseignant)>
                                                                        {{ $enseignant->nom_prenom_enseignant }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        
                                                        {{-- Time Picker (For defining recess / custom durations) --}}
                                                        <div class="row g-1 mt-1 quick-time-wrapper {{ $matiereId ? '' : 'd-none' }}">
                                                            <div class="col-6">
                                                                <label style="font-size: 9px;" class="text-muted mb-0 d-block">Début</label>
                                                                <input type="time" name="slots[{{ $jour }}][{{ $heure }}][heure_debut]" class="form-control form-control-sm quick-input-debut" style="font-size: 10px; padding: 2px 4px; height: 23px;" value="{{ $heureDebutVal }}" onchange="recalculateAllTimes()">
                                                            </div>
                                                            <div class="col-6">
                                                                <label style="font-size: 9px;" class="text-muted mb-0 d-block">Fin</label>
                                                                <input type="time" name="slots[{{ $jour }}][{{ $heure }}][heure_fin]" class="form-control form-control-sm quick-input-fin" style="font-size: 10px; padding: 2px 4px; height: 23px;" value="{{ $heureFinVal }}" onchange="recalculateAllTimes()">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>

                                        {{-- Recess Row --}}
                                        <tr class="recess-row {{ $hasRecessAfter ? '' : 'd-none' }}" id="recess_row_{{ str_replace(':', '_', $heure) }}" data-after-hour="{{ $heure }}" style="background: linear-gradient(90deg, #fef3c7 0%, #fffbeb 50%, #fef3c7 100%);">
                                            <td class="text-center fw-bold text-warning align-middle" style="white-space: nowrap; font-size: 12px; padding: 15px 6px; border: 1px solid #fde68a;">
                                                <span class="d-block text-warning-emphasis" style="font-size: 12px; font-weight: 700;"><i class="bi bi-clock-fill"></i> Récréation</span>
                                                <span class="text-muted small recess-time-display fw-bold" id="recess_time_display_{{ str_replace(':', '_', $heure) }}">--:--</span>
                                            </td>
                                            <td colspan="6" class="align-middle text-center" style="border: 1px solid #fde68a; padding: 15px 0;">
                                                <div class="d-flex justify-content-between align-items-center px-4">
                                                    {{-- Duration Dropdown inside Recess Row (Only in Edit Mode) --}}
                                                    <div class="d-flex align-items-center gap-2 timetable-edit-block d-none" id="recess_duration_container_{{ str_replace(':', '_', $heure) }}">
                                                        <span class="fw-semibold text-dark small" style="font-size: 11px;">Durée :</span>
                                                        <select class="form-select form-select-sm border-warning-subtle fw-bold text-warning-emphasis" style="width: 105px; font-size: 12px; height: 30px; padding: 2px 8px;" onchange="updateRecessDuration('{{ $heure }}', this.value)">
                                                            <option value="10" @selected($recessDuration == 10)>10 Min</option>
                                                            <option value="15" @selected($recessDuration == 15 || $recessDuration == 0)>15 Min</option>
                                                            <option value="20" @selected($recessDuration == 20)>20 Min</option>
                                                            <option value="30" @selected($recessDuration == 30)>30 Min</option>
                                                        </select>
                                                    </div>

                                                    <div class="flex-grow-1 text-center">
                                                        <h5 class="m-0 fw-extrabold text-warning-emphasis text-uppercase tracking-wider" style="font-size: 14px; font-weight: 800; letter-spacing: 3px;">
                                                            ☕ &nbsp; RÉCRÉATION &nbsp; ☕
                                                        </h5>
                                                    </div>
                                                    
                                                    <div class="no-print timetable-edit-block d-none" id="recess_delete_btn_{{ str_replace(':', '_', $heure) }}">
                                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill fw-bold" onclick="removeMagicRecess('{{ $heure }}')" style="padding: 2px 10px; font-size: 11px;">
                                                            <i class="bi bi-trash3 me-1"></i> Supprimer
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4 no-print d-none btn-enregistre-bottom">
                        <button type="submit" class="btn btn-success px-5 py-2.5 fw-bold shadow-sm">
                            <i class="bi bi-check2-circle me-2"></i>Enregistrer l'Emploi du Temps
                        </button>
                    </div>
                </form>

                {{-- Magic Recess Proposer Card (Intelligent slide-in from random directions) --}}
                <div id="magic_recess_proposer_card" class="d-none no-print">
                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-warning-subtle text-warning p-2.5 rounded-circle d-flex align-items-center justify-content-center animate-pulse" style="width: 42px; height: 42px; background-color: #fffbeb; border: 2px solid #fbbf24;">
                            <i class="bi bi-clock-fill fs-4 text-warning-emphasis"></i>
                        </div>
                        <div class="flex-grow-1 text-start">
                            <h6 class="m-0 fw-bold text-dark" style="font-size: 13px; font-weight: 700;">☕ Proposition de Récréation</h6>
                            <p class="text-muted my-1" style="font-size: 11px; line-height: 1.3;">
                                Voulez-vous planifier une récréation après le cours de <strong class="text-primary" id="proposal_hour_display">--:--</strong> ?
                            </p>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <span class="small text-muted" style="font-size: 10px;">Durée :</span>
                                <select id="proposal_recess_duration" class="form-select form-select-sm border-warning-subtle py-0 px-1" style="width: 80px; font-size: 11px; height: 24px;">
                                    <option value="10">10 Min</option>
                                    <option value="15" selected>15 Min</option>
                                    <option value="20">20 Min</option>
                                    <option value="30">30 Min</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <button type="button" class="btn btn-light btn-sm fw-bold py-1 px-2.5" style="font-size: 11px; border-radius: 6px;" onclick="closeRecessProposal()">Non</button>
                                <button type="button" class="btn btn-warning btn-sm text-warning-emphasis fw-bold py-1 px-3" style="font-size: 11px; border-radius: 6px;" onclick="acceptRecessProposal()">Insérer ☕</button>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    window.selectedClasseId = {{ $selectedClasse->id_classe ?? 'null' }};
                    window.selectedAnneeId = {{ $selectedAnnee->id_anneeScolaire ?? 'null' }};
                </script>
            @else
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-calendar-week fs-1 d-block mb-3"></i>
                    Sélectionnez une classe et une année scolaire pour afficher l'emploi du temps.
                </div>
            @endif
        </div>
    </div>

    <style>
        .print-visible { display: block !important; }
        @media print { .no-print, .sidebar-wrapper, .top-header { display: none !important; } }

        /* Alliance Parity Cross Case */
        .croix-case {
            display: inline-block;
            width: 20px;
            height: 20px;
            position: relative;
            vertical-align: middle;
            opacity: 0.35;
        }
        .croix-case::before, .croix-case::after {
            content: '';
            position: absolute;
            background: #475569;
            width: 100%;
            height: 2px;
            top: 50%;
            left: 0;
            margin-top: -1px;
            border-radius: 1px;
        }
        .croix-case::before { transform: rotate(45deg); }
        .croix-case::after { transform: rotate(-45deg); }

        /* Premium Magic Proposer Card Styling */
        #magic_recess_proposer_card {
            position: fixed;
            z-index: 2000;
            width: 330px;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border: 2px solid #fbbf24;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
            padding: 16px;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .recess-slide-left {
            left: 20px;
            bottom: 20px;
            animation: recessSlideLeft 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        .recess-slide-right {
            right: 20px;
            bottom: 20px;
            animation: recessSlideRight 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        .recess-slide-top {
            left: 50%;
            margin-left: -165px;
            top: 20px;
            animation: recessSlideTop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        .recess-slide-bottom {
            left: 50%;
            margin-left: -165px;
            bottom: 20px;
            animation: recessSlideBottom 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes recessSlideLeft {
            from { transform: translateX(-150%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes recessSlideRight {
            from { transform: translateX(150%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes recessSlideTop {
            from { transform: translateY(-150%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes recessSlideBottom {
            from { transform: translateY(150%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Premium Magic Button Styling */
        .btn-magic-recess {
            background: linear-gradient(135deg, var(--theme-primary) 0%, #4f46e5 100%) !important;
            color: white !important;
            border: 2px solid white !important;
            font-size: 11px !important;
            padding: 5px 15px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
        }
        .btn-magic-recess:hover {
            transform: scale(1.1) translateY(-1px) !important;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3) !important;
            filter: brightness(1.1);
        }
        .recess-row {
            transition: all 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%) scale(0.9);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
    </style>
@endsection

@push('scripts')
    <script src="{{ asset('assets/mon_js/html2pdf.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/mon_js/Emploi_du_temps.js') }}"></script>
    <script>
    function setGridMode(mode) {
        const viewBlocks = document.querySelectorAll('.timetable-view-block');
        const editBlocks = document.querySelectorAll('.timetable-edit-block');
        const saveTopBtn = document.querySelector('.btn-enregistre-top');
        const saveBottomBtn = document.querySelector('.btn-enregistre-bottom');

        if (mode === 'edition') {
            viewBlocks.forEach(b => b.classList.add('d-none'));
            editBlocks.forEach(b => b.classList.remove('d-none'));
            if (saveTopBtn) saveTopBtn.classList.remove('d-none');
            if (saveBottomBtn) saveBottomBtn.classList.remove('d-none');
            
            // Re-evaluate recess visibility in edit mode
            const hours = @json($heures);
            hours.forEach(hr => {
                const key = hr.replace(':', '_');
                const recessInput = document.getElementById('recess_after_' + key);
                const recessVal = recessInput ? parseInt(recessInput.value, 10) : 0;
                const hasRecess = recessVal > 0;
                
                const deleteBtn = document.getElementById('recess_delete_btn_' + key);
                const durationContainer = document.getElementById('recess_duration_container_' + key);
                
                if (hasRecess) {
                    if (deleteBtn) deleteBtn.classList.remove('d-none');
                    if (durationContainer) durationContainer.classList.remove('d-none');
                }
            });
        } else {
            viewBlocks.forEach(b => b.classList.remove('d-none'));
            editBlocks.forEach(b => b.classList.add('d-none'));
            if (saveTopBtn) saveTopBtn.classList.add('d-none');
            if (saveBottomBtn) saveBottomBtn.classList.add('d-none');
            closeRecessProposal();
        }
    }

    function customImprimer(nomFichier) {
        // Remember active mode
        const modeEditionRadio = document.getElementById('mode_edition');
        const isEditMode = modeEditionRadio ? modeEditionRadio.checked : false;
        
        // Force lecture mode for perfect printing representation
        if (isEditMode) {
            setGridMode('lecture');
        }
        
        // Call the original imprimer function
        imprimer(nomFichier);
        
        // Restore edit mode if it was active
        if (isEditMode) {
            setTimeout(() => {
                setGridMode('edition');
            }, 1200); // Small delay to allow html2canvas to capture the DOM state
        }
    }

    function onMatiereChange(select) {
        const container = select.closest('.quick-add-cell-container');
        const enseignantWrapper = container.querySelector('.quick-enseignant-wrapper');
        const timeWrapper = container.querySelector('.quick-time-wrapper');
        const btnClear = container.querySelector('.btn-clear-slot');
        const selectEnseignant = container.querySelector('.quick-select-enseignant');

        if (select.value) {
            enseignantWrapper.classList.remove('d-none');
            timeWrapper.classList.remove('d-none');
            btnClear.classList.remove('d-none');
            
            // Intelligent recess proposer triggers after every two hours (indices 1, 3, 5, 7 in heures)
            const tr = select.closest('tr');
            const hr = tr ? tr.getAttribute('data-hour') : null;
            if (hr) {
                const hours = @json($heures);
                const idx = hours.indexOf(hr);
                if (idx !== -1 && (idx + 1) % 2 === 0) {
                    setTimeout(() => {
                        triggerRecessProposal(hr);
                    }, 500);
                }
            }
        } else {
            enseignantWrapper.classList.add('d-none');
            timeWrapper.classList.add('d-none');
            btnClear.classList.add('d-none');
            selectEnseignant.value = "";
        }
    }

    function clearSlot(btn) {
        const container = btn.closest('.quick-add-cell-container');
        const selectMatiere = container.querySelector('.quick-select-matiere');
        const selectEnseignant = container.querySelector('.quick-select-enseignant');
        const enseignantWrapper = container.querySelector('.quick-enseignant-wrapper');
        const timeWrapper = container.querySelector('.quick-time-wrapper');

        selectMatiere.value = "";
        selectEnseignant.value = "";
        enseignantWrapper.classList.add('d-none');
        timeWrapper.classList.add('d-none');
        btn.classList.add('d-none');
        
        // Add a nice visual subtle indication that it was deleted
        container.style.backgroundColor = '#fff5f5';
        container.style.borderColor = '#feb2b2';
        setTimeout(() => {
            container.style.backgroundColor = '';
            container.style.borderColor = '';
        }, 1000);
    }

    /* Intelligent sliding Recess proposal */
    let currentProposalHour = '';
    
    function triggerRecessProposal(heure) {
        const key = heure.replace(':', '_');
        
        // If recess is already scheduled after this hour, don't propose it!
        const recessInput = document.getElementById('recess_after_' + key);
        if (recessInput && parseInt(recessInput.value, 10) > 0) {
            return;
        }
        
        currentProposalHour = heure;
        
        const card = document.getElementById('magic_recess_proposer_card');
        const hourDisplay = document.getElementById('proposal_hour_display');
        if (!card || !hourDisplay) return;
        
        hourDisplay.textContent = heure;
        
        // Reset classes
        card.className = 'no-print';
        card.classList.remove('d-none');
        
        // Randomly select a slide direction animation!
        const animations = ['recess-slide-left', 'recess-slide-right', 'recess-slide-top', 'recess-slide-bottom'];
        const randomAnimation = animations[Math.floor(Math.random() * animations.length)];
        
        card.classList.add(randomAnimation);
    }
    
    function closeRecessProposal() {
        const card = document.getElementById('magic_recess_proposer_card');
        if (card) {
            card.classList.add('d-none');
            card.className = 'd-none no-print';
        }
    }
    
    function acceptRecessProposal() {
        if (!currentProposalHour) return;
        
        const durationSelect = document.getElementById('proposal_recess_duration');
        const duration = durationSelect ? durationSelect.value : '15';
        
        const key = currentProposalHour.replace(':', '_');
        
        // Set the hidden input to selected duration
        const input = document.getElementById('recess_after_' + key);
        if (input) {
            input.value = duration;
        }
        
        // Show recess row
        const recessRow = document.getElementById('recess_row_' + key);
        if (recessRow) {
            recessRow.classList.remove('d-none');
        }
        
        // Show delete button
        const deleteBtn = document.getElementById('recess_delete_btn_' + key);
        if (deleteBtn) {
            deleteBtn.classList.remove('d-none');
        }
        
        // Show duration container and select value
        const durationContainer = document.getElementById('recess_duration_container_' + key);
        if (durationContainer) {
            durationContainer.classList.remove('d-none');
            const sel = durationContainer.querySelector('select');
            if (sel) sel.value = duration;
        }
        
        // Recalculate
        recalculateAllTimes();
        
        showToast(`Récréation de ${duration} minutes insérée avec succès !`, "success");
        
        closeRecessProposal();
    }

    function removeMagicRecess(heure) {
        const key = heure.replace(':', '_');
        
        // Set the hidden input to 0 (inactive)
        const input = document.getElementById('recess_after_' + key);
        if (input) {
            input.value = "0";
        }
        
        // Hide the recess row
        const recessRow = document.getElementById('recess_row_' + key);
        if (recessRow) {
            recessRow.classList.add('d-none');
        }
        
        // Recalculate and shift all times back!
        recalculateAllTimes();
        
        showToast("Récréation supprimée. Les horaires ont été réalignés.", "info");
    }

    function updateRecessDuration(heure, duration) {
        const key = heure.replace(':', '_');
        
        // Update hidden input
        const input = document.getElementById('recess_after_' + key);
        if (input) {
            input.value = duration;
        }
        
        // Recalculate times
        recalculateAllTimes();
        
        showToast(`Durée de la récréation mise à jour à ${duration} minutes. Les horaires suivants ont été recalculés.`, "success");
    }

    function recalculateAllTimes() {
        const hours = @json($heures);
        const jours = @json($jours);
        if (!hours || hours.length === 0) return;
        
        // Find the start time of the first row
        const firstHour = hours[0];
        let firstStartVal = "08:00";
        
        // Check if user edited the start time in the first row
        for (let j of jours) {
            const input = document.querySelector(`input[name="slots[${j}][${firstHour}][heure_debut]"]`);
            if (input && input.value && input.closest('.quick-add-cell-container').querySelector('.quick-select-matiere').value) {
                firstStartVal = input.value;
                break;
            }
        }
        
        let currentEndTime = firstStartVal;
        
        for (let i = 0; i < hours.length; i++) {
            const hr = hours[i];
            
            // The start time of this row is currentEndTime
            const rowStartTime = currentEndTime;
            
            // Calculate row end time by adding 1 hour (60 minutes)
            const rowEndTime = addMinutesToTime(rowStartTime, 60);
            
            // Update all cells in this row with the new start/end times
            for (let j of jours) {
                const startInput = document.querySelector(`input[name="slots[${j}][${hr}][heure_debut]"]`);
                const endInput = document.querySelector(`input[name="slots[${j}][${hr}][heure_fin]"]`);
                if (startInput) startInput.value = rowStartTime;
                if (endInput) endInput.value = rowEndTime;
            }
            
            // Update the hour column (first column) display in this row!
            const hourTd = document.querySelector(`tr[data-hour="${hr}"] td:first-child`);
            if (hourTd) {
                hourTd.innerHTML = `
                    <span class="text-primary">${rowStartTime} - ${rowEndTime}</span>
                `;
            }
            
            // Update currentEndTime to rowEndTime
            currentEndTime = rowEndTime;
            
            // Now check if a recess is active after this row
            const recessInput = document.getElementById('recess_after_' + hr.replace(':', '_'));
            const recessDuration = recessInput ? parseInt(recessInput.value, 10) : 0;
            
            if (recessDuration > 0) {
                const recessStartTime = currentEndTime;
                const recessEndTime = addMinutesToTime(recessStartTime, recessDuration);
                
                // Update the display of the recess row
                const recessDisplay = document.getElementById('recess_time_display_' + hr.replace(':', '_'));
                if (recessDisplay) {
                    recessDisplay.textContent = `${recessStartTime} - ${recessEndTime}`;
                }
                
                // The next row starts after the recess!
                currentEndTime = recessEndTime;
            }
        }
    }

    // Helper to add minutes to time string (HH:MM)
    function addMinutesToTime(timeStr, minsToAdd) {
        const parts = timeStr.split(':');
        let hrs = parseInt(parts[0], 10);
        let mins = parseInt(parts[1], 10);
        
        mins += minsToAdd;
        hrs += Math.floor(mins / 60);
        mins = mins % 60;
        hrs = hrs % 24;
        
        return strPadZero(hrs) + ':' + strPadZero(mins);
    }

    function strPadZero(num) {
        return num.toString().padStart(2, '0');
    }

    function showToast(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} position-fixed shadow-lg border-2 rounded-3 text-start px-4 py-3`;
        alertDiv.style.cssText = 'bottom: 20px; right: 20px; z-index: 1060; min-width: 300px; animation: slideUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);';
        
        let icon = 'bi-check-circle-fill text-success';
        if (type === 'info') icon = 'bi-info-circle-fill text-info';
        if (type === 'danger' || type === 'error') icon = 'bi-exclamation-triangle-fill text-danger';
        
        alertDiv.innerHTML = `
            <div class="d-flex align-items-center gap-3">
                <i class="bi ${icon} fs-4"></i>
                <div class="fw-semibold text-dark" style="font-size: 13px;">${message}</div>
            </div>
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.style.animation = 'fadeOut 0.5s ease forwards';
            setTimeout(() => alertDiv.remove(), 500);
        }, 3500);
    }

    // Run dynamic calculations on load to align everything perfectly
    document.addEventListener('DOMContentLoaded', function() {
        recalculateAllTimes();
    });
    </script>
@endpush
