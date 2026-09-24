@extends('layouts.app')

@section('content')
    @php
        $statusLabel = match((int) $eleve->etat_dossier) {
            1 => __('eleves.status_transfere'),
            2 => __('eleves.status_retire'),
            3 => __('eleves.status_diplome'),
            default => __('eleves.status_actif'),
        };
        $user = Auth::user();
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('eleves.dossiers_title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('eleves.dossiers') }}">{{ __('eleves.dossiers_title') }}</a></li>
                    <li class="breadcrumb-item active">{{ $eleve->prenom_eleve }} {{ $eleve->nom_eleve }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="student-file-shell">
        <section class="student-file-hero">
            <div class="student-photo">
                @if($eleve->image)
                    <img src="{{ asset($eleve->image) }}" alt="">
                @else
                    <i class="bi bi-person"></i>
                @endif
            </div>
            <div class="student-title">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h4 class="mb-0 fw-bold">{{ $eleve->prenom_eleve }} {{ $eleve->nom_eleve }}</h4>
                    <span class="badge {{ (int) $eleve->etat_dossier === 3 ? 'bg-success' : 'theme-icon-soft' }}">{{ $statusLabel }}</span>
                </div>
                <div class="student-subtitle">
                    {{ __('eleves.label_matricule') }} <span class="font-monospace">{{ $eleve->matricule ?: __('eleves.not_specified_m') }}</span>
                    <span class="mx-2">•</span>
                    {{ $eleve->classe?->nom_classe ?? __('eleves.classe_non_renseignee') }}
                    <span class="mx-2">•</span>
                    {{ $annee?->annee ?? __('eleves.annee_non_renseignee') }}
                </div>
            </div>
            <div class="student-actions">
                <a href="{{ route('eleves.dossiers') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>{{ __('eleves.back') }}</a>
            </div>
        </section>

        @if(!empty($dossierAlerts))
            <div class="row g-2 mb-3">
                @foreach($dossierAlerts as $alert)
                    <div class="col-md-6">
                        <div class="alert alert-{{ $alert['type'] }} mb-0 py-2">{{ $alert['text'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <section class="quick-actions mb-3">
            <a href="#identite-scolarite" class="quick-action-btn"><i class="bi bi-person-lines-fill"></i><span>{{ __('eleves.quick_identite') }}</span></a>
            <a href="#parents-responsables" class="quick-action-btn"><i class="bi bi-telephone"></i><span>{{ __('eleves.quick_parents') }}</span></a>
            <a href="#situation-financiere" class="quick-action-btn"><i class="bi bi-cash-coin"></i><span>{{ __('eleves.quick_finances') }}</span></a>
            <a href="#resultats-scolaires" class="quick-action-btn"><i class="bi bi-file-earmark-text"></i><span>{{ __('eleves.quick_bulletins') }}</span></a>
            <a href="#historique-dossier" class="quick-action-btn"><i class="bi bi-clock-history"></i><span>{{ __('eleves.quick_historique') }}</span></a>
        </section>

        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card theme-card shadow-sm h-100">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted text-uppercase small fw-bold mb-1">{{ __('eleves.stat_responsables') }}</p>
                            <h3 class="fw-bold mb-0">{{ $eleve->parents->count() }}</h3>
                            <small class="text-muted d-block mt-2">{{ __('eleves.stat_responsables_desc') }}</small>
                        </div>
                        <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-people fs-4"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card theme-card shadow-sm h-100">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted text-uppercase small fw-bold mb-1">{{ __('eleves.stat_statut_paiement') }}</p>
                            <h3 class="fw-bold mb-0">{{ $paymentSummary['statut'] }}</h3>
                            <small class="text-muted d-block mt-2">{{ $paymentSummary['progress'] }}{{ __('eleves.stat_progress_suffix') }}</small>
                        </div>
                        <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-check-circle fs-4"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card theme-card shadow-sm h-100">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted text-uppercase small fw-bold mb-1">{{ __('eleves.stat_paye') }}</p>
                            <h3 class="fw-bold mb-0">{{ number_format($paymentSummary['montant_paye'], \App\Support\Devise::decimales($eleve->ecole ?? null), ',', ' ') }}</h3>
                            <small class="text-muted d-block mt-2">{{ \App\Support\Devise::symbole($eleve->ecole ?? null) }} {{ __('eleves.stat_paye_desc_suffix') }}</small>
                        </div>
                        <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-cash-stack fs-4"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card theme-card shadow-sm h-100">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted text-uppercase small fw-bold mb-1">{{ __('eleves.stat_reste') }}</p>
                            <h3 class="fw-bold mb-0">{{ number_format($paymentSummary['reste'], \App\Support\Devise::decimales($eleve->ecole ?? null), ',', ' ') }}</h3>
                            <small class="text-muted d-block mt-2">{{ \App\Support\Devise::symbole($eleve->ecole ?? null) }} {{ __('eleves.stat_reste_desc_suffix') }}</small>
                        </div>
                        <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-wallet2 fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-4" id="identite-scolarite">
                <div class="card theme-card shadow-sm h-100">
                    <div class="card-header theme-header"><h6 class="mb-0 fw-bold">{{ __('eleves.section_identite') }}</h6></div>
                    <div class="card-body">
                        <div class="info-row"><span>{{ __('eleves.row_prenom_nom') }}</span><strong>{{ $eleve->prenom_eleve }} {{ $eleve->nom_eleve }}</strong></div>
                        <div class="info-row"><span>{{ __('eleves.row_genre') }}</span><strong>{{ $eleve->genre_eleve ?: __('eleves.not_specified_m') }}</strong></div>
                        <div class="info-row"><span>{{ __('eleves.row_naissance') }}</span><strong>{{ __('eleves.row_naissance_value', ['date' => $eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : __('eleves.not_specified'), 'lieu' => $eleve->lieu_naiss ?: __('eleves.not_specified_m')]) }}</strong></div>
                        <div class="info-row"><span>{{ __('eleves.label_adresse') }}</span><strong>{{ $eleve->adresse_eleve ?: __('eleves.not_specified') }}</strong></div>
                        <div class="info-row"><span>{{ __('eleves.label_cas_social') }}</span><strong>{{ $eleve->cas_social ?: __('eleves.social_normal') }}</strong></div>
                        <div class="info-row"><span>{{ __('eleves.row_ecole') }}</span><strong>{{ $eleve->ecole?->nomEcole ?? session('nomEcole') ?? __('eleves.not_specified') }}</strong></div>
                        <div class="info-row"><span>{{ __('eleves.th_date_inscription') }}</span><strong>{{ $eleve->date_inscription ? \Carbon\Carbon::parse($eleve->date_inscription)->format('d/m/Y') : __('eleves.not_specified') }}</strong></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4" id="parents-responsables">
                <div class="card theme-card shadow-sm h-100">
                    <div class="card-header theme-header"><h6 class="mb-0 fw-bold">{{ __('eleves.section_parents') }}</h6></div>
                    <div class="card-body">
                        @forelse($eleve->parents as $parent)
                            <div class="contact-block">
                                <div>
                                    <strong>{{ $parent->nom_prenom_parent }}</strong>
                                    <div class="text-muted small">{{ $parent->pivot?->lien_parent ?: __('eleves.parent_default_role') }}</div>
                                </div>
                                <div class="contact-actions">
                                    @if($parent->telephone_parent)
                                        <a href="tel:{{ $parent->telephone_parent }}" class="btn btn-sm btn-light"><i class="bi bi-telephone"></i></a>
                                    @endif
                                    @if($parent->email_parent)
                                        <a href="mailto:{{ $parent->email_parent }}" class="btn btn-sm btn-light"><i class="bi bi-envelope"></i></a>
                                    @endif
                                </div>
                                <div class="small text-muted mt-2">
                                    {{ $parent->telephone_parent ?: __('eleves.no_phone') }}
                                    @if($parent->email_parent) • {{ $parent->email_parent }} @endif
                                </div>
                            </div>
                        @empty
                            <div class="empty-note">{{ __('eleves.no_parent') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-xl-4" id="situation-financiere">
                <div class="card theme-card shadow-sm h-100">
                    <div class="card-header theme-header"><h6 class="mb-0 fw-bold">{{ __('eleves.section_finances') }}</h6></div>
                    <div class="card-body">
                        <div class="payment-ring">
                            <div class="payment-percent">{{ $paymentSummary['progress'] }}%</div>
                            <div class="progress flex-grow-1" style="height: 10px;">
                                <div class="progress-bar" style="width: {{ $paymentSummary['progress'] }}%;"></div>
                            </div>
                        </div>
                        <div class="info-row"><span>{{ __('eleves.row_total_prevu') }}</span><strong>{{ \App\Support\Devise::format($paymentSummary['montant_final'], $eleve->ecole ?? null) }}</strong></div>
                        <div class="info-row"><span>{{ __('eleves.row_reduction') }}</span><strong>{{ \App\Support\Devise::format($paymentSummary['reduction'], $eleve->ecole ?? null) }}</strong></div>
                        <div class="info-row"><span>{{ __('eleves.row_mode') }}</span><strong>{{ $paymentSummary['plan']?->mode_paiement ?: $eleve->mode_paiement ?: __('eleves.not_specified_m') }}</strong></div>
                        <div class="info-row"><span>{{ __('eleves.row_payeur') }}</span><strong>{{ $paymentSummary['plan']?->payeur_libelle ?: __('eleves.not_specified_m') }}</strong></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7" id="echeancier-paiements">
                <div class="card theme-card shadow-sm h-100">
                    <div class="card-header theme-header"><h6 class="mb-0 fw-bold">{{ __('eleves.section_echeancier') }}</h6></div>
                    <div class="card-body">
                        @forelse($echeancesResume as $echeance)
                            <div class="timeline-row">
                                <div class="timeline-dot {{ $echeance['statut'] }}"></div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between gap-2">
                                        <strong>{{ $echeance['libelle'] }}</strong>
                                        <span>{{ \App\Support\Devise::format($echeance['reste'], $eleve->ecole ?? null) }} {{ __('eleves.reste_suffix') }}</span>
                                    </div>
                                    <div class="small text-muted">
                                        {{ __('eleves.prevu_prefix') }} {{ \App\Support\Devise::format($echeance['montant_prevu'], $eleve->ecole ?? null) }}
                                        @if($echeance['date_limite']) • {{ __('eleves.limite_prefix') }} {{ $echeance['date_limite']->format('d/m/Y') }} @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="empty-note mb-3">{{ __('eleves.no_echeancier') }}</div>
                        @endforelse

                        <div class="table-responsive mt-3">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>{{ __('eleves.th_date') }}</th><th>{{ __('eleves.th_reference') }}</th><th>{{ __('eleves.th_motif') }}</th><th class="text-end">{{ __('eleves.th_montant') }}</th><th></th></tr></thead>
                                <tbody>
                                    @forelse($paiementsRecents as $paiement)
                                        <tr>
                                            <td>{{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : 'N/A' }}</td>
                                            <td>{{ $paiement->reference ?: $paiement->numero_recu ?: 'N/A' }}</td>
                                            <td>{{ $paiement->echeance?->libelle ?? $paiement->motif ?? __('eleves.motif_default') }}</td>
                                            <td class="text-end">{{ number_format((float) ($paiement->montant_paye ?? $paiement->montant), 0, ',', ' ') }}</td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1">
                                                    <a href="{{ route('finances.paiements.download', $paiement->id_paiement) }}" class="btn btn-light btn-sm" target="_blank" title="{{ __('eleves.receipt_full') }}">
                                                        <i class="bi bi-printer"></i>
                                                    </a>
                                                    <a href="{{ route('finances.paiements.thermique', $paiement->id_paiement) }}" class="btn btn-light btn-sm" target="_blank" title="{{ __('eleves.receipt_thermal') }}">
                                                        <i class="bi bi-receipt-cutoff"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('eleves.no_paiement') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5" id="resultats-scolaires">
                <div class="card theme-card shadow-sm h-100">
                    <div class="card-header theme-header"><h6 class="mb-0 fw-bold">{{ __('eleves.section_resultats') }}</h6></div>
                    <div class="card-body">
                        <div class="mini-section-title">{{ __('eleves.moyennes_title') }}</div>
                        @forelse($moyennes as $moyenne)
                            <div class="result-row">
                                <div>
                                    <strong>{{ $moyenne->nom_trimestre ?: ($moyenne->mois ? __('eleves.mois_prefix').' '.$moyenne->mois : __('eleves.periode_default')) }}</strong>
                                    <div class="small text-muted">{{ $moyenne->valide ? __('eleves.valide') : __('eleves.en_attente_validation') }}</div>
                                </div>
                                <div class="text-end">
                                    <strong>{{ number_format((float) $moyenne->moyenne, 2) }}/20</strong>
                                    <div class="small text-muted">{{ __('eleves.rang_prefix') }} {{ $moyenne->rang ?: 'N/A' }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="empty-note">{{ __('eleves.no_moyenne') }}</div>
                        @endforelse

                        <div class="mini-section-title mt-3">{{ __('eleves.notes_title') }}</div>
                        @forelse($evaluationsRecentes as $evaluation)
                            <div class="result-row">
                                <div>
                                    <strong>{{ $evaluation->nom_matiere ?: __('eleves.matiere_default') }}</strong>
                                    <div class="small text-muted">{{ $evaluation->typeNote ?: __('eleves.evaluation_default') }} {{ $evaluation->nom_trimestre ? '• '.$evaluation->nom_trimestre : '' }}</div>
                                </div>
                                <strong>{{ $evaluation->note !== null ? number_format((float) $evaluation->note, 2) : 'N/A' }}</strong>
                            </div>
                        @empty
                            <div class="empty-note">{{ __('eleves.no_note') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-12" id="historique-dossier">
                <div class="card theme-card shadow-sm">
                    <div class="card-header theme-header"><h6 class="mb-0 fw-bold">{{ __('eleves.section_historique') }}</h6></div>
                    <div class="card-body">
                        <div class="history-grid">
                            <div class="history-item">
                                <span>{{ __('eleves.hist_creation') }}</span>
                                <strong>{{ $eleve->date_inscription ? \Carbon\Carbon::parse($eleve->date_inscription)->format('d/m/Y') : __('eleves.hist_date_non_renseignee') }}</strong>
                            </div>
                            <div class="history-item">
                                <span>{{ __('eleves.hist_dernier_paiement') }}</span>
                                <strong>{{ $paiementsRecents->first()?->date_paiement ? \Carbon\Carbon::parse($paiementsRecents->first()->date_paiement)->format('d/m/Y') : __('eleves.hist_aucun') }}</strong>
                            </div>
                            <div class="history-item">
                                <span>{{ __('eleves.hist_derniere_moyenne') }}</span>
                                <strong>{{ $moyennes->first()?->moyenne ? number_format((float) $moyennes->first()->moyenne, 2).'/20' : __('eleves.hist_aucun') }}</strong>
                            </div>
                        </div>
                        @forelse($transferts as $transfert)
                            <div class="transfer-box mt-3">
                                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                                    <div>
                                        <strong>{{ __('eleves.transfert_vers', ['dest' => $transfert->destination]) }}</strong>
                                        <div class="text-muted small">{{ __('eleves.transfert_motif_label') }} : {{ $transfert->motif }} • {{ __('eleves.transfert_travail_label') }} : {{ $transfert->travail ?: __('eleves.not_specified_m') }} • {{ __('eleves.transfert_conduite_label') }} : {{ $transfert->conduite }}</div>
                                    </div>
                                    <a href="{{ route('eleves.transfer.fiche', $transfert->id_transfert) }}" target="_blank" class="btn btn-sm theme-outline-btn">
                                        <i class="bi bi-file-earmark-pdf me-1"></i>{{ __('eleves.fiche_pdf') }}
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="empty-note mt-3">{{ __('eleves.no_transfert') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .student-file-shell { color: #111827; }
        #identite-scolarite,
        #parents-responsables,
        #situation-financiere,
        #echeancier-paiements,
        #resultats-scolaires,
        #historique-dossier {
            scroll-margin-top: 92px;
        }
        .student-file-hero {
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
            background: #fff;
            padding: 16px;
            margin-bottom: 12px;
        }
        .student-photo {
            width: 86px;
            height: 86px;
            border-radius: 50%;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: #6b7280;
            font-size: 2rem;
            flex: 0 0 auto;
        }
        .student-photo img { width: 100%; height: 100%; object-fit: cover; }
        .student-title { flex: 1 1 auto; min-width: 220px; }
        .student-subtitle { color: #6b7280; margin-top: 5px; }
        .student-actions, .quick-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .quick-actions {
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
            background: #fff;
            padding: 10px;
        }
        .quick-action-btn {
            min-height: 42px;
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
            background: #fff;
            color: #111827;
            padding: 8px 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .quick-action-btn:hover { border-color: var(--theme-accent); color: var(--theme-accent); }
        .widget-icon { width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; }
        .info-row span { color: #6b7280; font-size: .82rem; }
        .info-row, .result-row, .timeline-row {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 10px 0;
            border-bottom: 1px solid var(--bs-border-color);
        }
        .info-row:last-child, .result-row:last-child, .timeline-row:last-child { border-bottom: 0; }
        .info-row strong { text-align: right; }
        .contact-block {
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
        }
        .contact-actions { float: right; display: flex; gap: 6px; }
        .payment-ring { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .payment-percent { font-weight: 800; font-size: 1.2rem; color: var(--theme-accent); width: 54px; }
        .timeline-row { align-items: flex-start; }
        .timeline-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #6b7280;
            margin-top: 5px;
            flex: 0 0 auto;
        }
        .timeline-dot.paye { background: #16a34a; }
        .timeline-dot.retard { background: #dc2626; }
        .timeline-dot.attente { background: #f59e0b; }
        .mini-section-title {
            color: #6b7280;
            font-size: .78rem;
            text-transform: uppercase;
            font-weight: 700;
        }
        .empty-note {
            border: 1px dashed var(--bs-border-color);
            border-radius: 8px;
            color: #6b7280;
            padding: 14px;
            background: #fff;
        }
        .history-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }
        .history-item, .transfer-box {
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
            padding: 12px;
            background: #fff;
        }
        .history-item span { display: block; color: #6b7280; font-size: .82rem; }
        .history-item strong { display: block; margin-top: 3px; }
        @media (max-width: 767.98px) {
            .student-file-hero { align-items: flex-start; }
            .student-actions { width: 100%; }
            .history-grid { grid-template-columns: 1fr; }
            .info-row { display: block; }
            .info-row strong { display: block; text-align: left; margin-top: 3px; }
        }
    </style>
@endsection
