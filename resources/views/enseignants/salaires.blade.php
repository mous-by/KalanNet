@extends('layouts.app')

@section('content')
    @php
        $pageSummary = $mode === 'state' ? $stateSummary : $summary;
        $visibleRows = $mode === 'state' ? $stateRows : $salaryRows;
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Enseignants</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('enseignants.index') }}">Enseignants</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $mode === 'state' ? 'État de paiement' : 'Salaires' }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">Enseignants</small>
                    <h4 class="fw-bold mb-0">{{ number_format($pageSummary['teachers'], 0, ',', ' ') }}</h4>
                    <span class="small text-muted">Période filtrée</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">À payer</small>
                    <h4 class="fw-bold mb-0">{{ number_format($pageSummary['due'], 0, ',', ' ') }}</h4>
                    <span class="small text-muted">FCFA</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">Déjà versé</small>
                    <h4 class="fw-bold mb-0 text-success">{{ number_format($pageSummary['paid'], 0, ',', ' ') }}</h4>
                    <span class="small text-muted">FCFA</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">Reste</small>
                    <h4 class="fw-bold mb-0 {{ $pageSummary['remaining'] > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($pageSummary['remaining'], 0, ',', ' ') }}</h4>
                    <span class="small text-muted">FCFA</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card theme-card shadow-sm mb-4">
        <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0 fw-bold"><i class="bx bx-filter-alt me-2"></i>Filtrer les salaires</h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ $mode === 'state' ? route('enseignants.salaires.etat') : route('enseignants.salaires') }}" method="GET" class="row g-3" data-auto-filter="true" data-auto-filter-fields="mois,annee,source,id_enseignant">
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted text-uppercase">Mois</label>
                    <select name="mois" class="form-select">
                        @foreach($months as $value => $label)
                            <option value="{{ $value }}" @selected($filters['mois'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted text-uppercase">Année</label>
                    <input type="number" name="annee" class="form-control" value="{{ $filters['annee'] }}" min="2000" max="2100">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Base</label>
                    <select name="source" class="form-select">
                        @foreach($sources as $value => $label)
                            <option value="{{ $value }}" @selected($filters['source'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Enseignant</label>
                    <select name="id_enseignant" class="form-select">
                        <option value="">Tous</option>
                        @foreach($enseignants as $enseignant)
                            <option value="{{ $enseignant->id_enseignant }}" @selected((string) $filters['id_enseignant'] === (string) $enseignant->id_enseignant)>
                                {{ $enseignant->nom_prenom_enseignant }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($mode === 'pay')
        <div class="card theme-card shadow-sm mb-4">
            <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="bx bx-list-check me-2"></i>Paiement groupé des arriérés</h5>
                <span class="badge theme-header-badge">{{ $sources[$filters['source']] ?? $filters['source'] }}</span>
            </div>
            <form action="{{ route('enseignants.salaires.pay') }}" method="POST">
                <div class="card-body p-4">
                    @csrf
                    <input type="hidden" name="redirect_mois" value="{{ $filters['mois'] }}">
                    <input type="hidden" name="redirect_annee" value="{{ $filters['annee'] }}">
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-md-4 col-lg-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Date du paiement</label>
                            <input type="date" name="date_paiement" class="form-control" value="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-8 col-lg-9">
                            <div class="theme-icon-soft rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <div class="fw-bold">Montant à valider</div>
                                    <small class="text-muted">Saisir seulement les montants à payer maintenant. Les lignes à 0 seront ignorées.</small>
                                </div>
                                <div class="h5 fw-bold mb-0" id="bulkPaymentTotal">0 F</div>
                            </div>
                        </div>
                    </div>

                    @php
                        $bulkRowsByTeacher = $bulkRows->groupBy(fn ($row) => $row['enseignant']->id_enseignant);
                        $paymentRowIndex = 0;
                    @endphp

                    @forelse($bulkRowsByTeacher as $teacherRows)
                        @php
                            $teacher = $teacherRows->first()['enseignant'];
                            $contract = $teacherRows->first()['contract'];
                            $teacherRemaining = $teacherRows->sum('remaining');
                        @endphp
                        <div class="teacher-payment-group mb-3">
                            <div class="teacher-payment-head">
                                <div>
                                    <div class="fw-bold">{{ $teacher->nom_prenom_enseignant }}</div>
                                    <small>{{ $contract }} - {{ $teacherRows->count() }} mois à régulariser</small>
                                </div>
                                <div class="text-end">
                                    <small class="d-block">Reste total</small>
                                    <strong>{{ number_format($teacherRemaining, 0, ',', ' ') }} F</strong>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0 bulk-payment-table">
                                    <thead>
                                        <tr>
                                            <th>Mois concerné</th>
                                            <th class="text-end">Salaire du mois</th>
                                            <th class="text-end">Déjà payé</th>
                                            <th class="text-end">Reste</th>
                                            <th class="text-end">À payer maintenant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($teacherRows as $row)
                                            @php $rowIndex = $paymentRowIndex++; @endphp
                                            <tr>
                                                <td class="fw-bold">
                                                    {{ $row['period_label'] }}
                                                    <input type="hidden" name="rows[{{ $rowIndex }}][id_enseignant]" value="{{ $row['enseignant']->id_enseignant }}">
                                                    <input type="hidden" name="rows[{{ $rowIndex }}][mois]" value="{{ $row['period_value'] ? substr($row['period_value'], 5, 2) : $filters['mois'] }}">
                                                    <input type="hidden" name="rows[{{ $rowIndex }}][annee]" value="{{ $row['period_value'] ? substr($row['period_value'], 0, 4) : $filters['annee'] }}">
                                                    <input type="hidden" name="rows[{{ $rowIndex }}][source]" value="{{ $row['source'] }}">
                                                </td>
                                                <td class="text-end">{{ number_format($row['amount_due'], 0, ',', ' ') }} F</td>
                                                <td class="text-end text-success fw-bold">{{ number_format($row['paid'], 0, ',', ' ') }} F</td>
                                                <td class="text-end fw-bold">{{ number_format($row['remaining'], 0, ',', ' ') }} F</td>
                                                <td style="min-width: 170px;">
                                                    <input type="number"
                                                           name="rows[{{ $rowIndex }}][montant_verse]"
                                                           class="form-control form-control-sm text-end bulk-payment-amount"
                                                           min="0"
                                                           max="{{ (int) $row['remaining'] }}"
                                                           step="1"
                                                           placeholder="0"
                                                           data-remaining="{{ (int) $row['remaining'] }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            Aucun reste à payer trouvé sur l'année scolaire sélectionnée pour ce filtre.
                        </div>
                    @endforelse
                </div>
                @if($bulkRows->isNotEmpty())
                    <div class="card-footer bg-white d-flex justify-content-end">
                        <button type="submit" class="btn theme-action-btn px-4">
                            <i class="bx bx-check-circle me-1"></i>Valider le paiement
                        </button>
                    </div>
                @endif
            </form>
        </div>
    @endif

    <div class="card theme-card shadow-sm overflow-hidden">
        <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold">
                    <i class="bx {{ $mode === 'state' ? 'bx-list-check' : 'bx-money-withdraw' }} me-2"></i>{{ $mode === 'state' ? 'Mandat de paiement des enseignants' : 'Paiement des enseignants' }}
                </h5>
                <a href="{{ route('enseignants.salaires', $filters) }}" class="btn btn-sm {{ $mode === 'pay' ? 'theme-tab-btn active' : 'theme-tab-btn' }}">Paiement</a>
                <a href="{{ route('enseignants.salaires.etat', $filters) }}" class="btn btn-sm {{ $mode === 'state' ? 'theme-tab-btn active' : 'theme-tab-btn' }}">État</a>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="badge theme-header-badge">{{ $sources[$filters['source']] ?? $filters['source'] }} - {{ $months[$filters['mois']] ?? $filters['mois'] }} {{ $filters['annee'] }}</span>
                @if($mode === 'state')
                    <a href="{{ route('enseignants.salaires.etat.pdf', $filters) }}" target="_blank" class="btn btn-sm theme-tab-btn active">
                        <i class="bx bx-file me-1"></i>PDF
                    </a>
                @endif
            </div>
        </div>
        <div class="px-4 pt-3">
            <div class="alert alert-info border-0 shadow-sm mb-3">
                Seuls les contrats CDI, CDD et VCT sont pris en compte. Les enseignants fonctionnaires des écoles publiques sont rémunérés par l’État.
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead class="salary-table-head" style="--bs-table-bg: #fff; --bs-table-color: #212529; background-color: #fff !important;">
                    <tr>
                        <th class="px-4 py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Enseignant</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Contrat</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Base</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white text-end" style="color: #212529 !important;">Heures</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white text-end" style="color: #212529 !important;">Salaire du mois</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white text-end" style="color: #212529 !important;">Versé</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white text-end" style="color: #212529 !important;">Reste</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Statut</th>
                        <th class="px-4 py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">{{ $mode === 'state' ? 'Bulletin' : 'Versement' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visibleRows as $row)
                        <tr>
                            <td class="px-4">
                                <div class="fw-bold">{{ $row['enseignant']->nom_prenom_enseignant }}</div>
                                <small class="text-muted">{{ $row['enseignant']->telephone_enseignant ?: 'Téléphone non renseigné' }}</small>
                                <small class="d-block text-muted">{{ $row['reference'] }}</small>
                            </td>
                            <td>{{ $row['contract'] }}</td>
                            <td>
                                @if($row['contract'] === 'VCT')
                                    {{ number_format($row['hourly_rate'], 0, ',', ' ') }} F / h
                                @else
                                    Salaire mensuel
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($row['hours'], 2, ',', ' ') }}</td>
                            <td class="text-end fw-bold">{{ number_format($row['amount_due'], 0, ',', ' ') }} F</td>
                            <td class="text-end text-success fw-bold">{{ number_format($row['paid'], 0, ',', ' ') }} F</td>
                            <td class="text-end fw-bold {{ $row['remaining'] > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($row['remaining'], 0, ',', ' ') }} F</td>
                            <td>
                                @if($row['status'] === 'Payé')
                                    <span class="badge bg-success">Payé</span>
                                @elseif($row['status'] === 'Partiel')
                                    <span class="badge bg-warning text-dark">Partiel</span>
                                @else
                                    <span class="badge bg-secondary">À payer</span>
                                @endif
                            </td>
                            <td class="px-4 salary-payment-cell">
                                @if($mode === 'pay')
                                    <form action="{{ route('enseignants.salaires.pay') }}" method="POST" class="salary-inline-payment">
                                        @csrf
                                        <input type="hidden" name="id_enseignant" value="{{ $row['enseignant']->id_enseignant }}">
                                        <input type="hidden" name="mois" value="{{ $filters['mois'] }}">
                                        <input type="hidden" name="annee" value="{{ $filters['annee'] }}">
                                        <input type="hidden" name="source" value="{{ $filters['source'] }}">
                                        <div class="salary-inline-field">
                                            <label class="form-label">Montant</label>
                                            <input type="number" name="montant_verse" class="form-control form-control-sm" min="1" max="{{ max(1, (int) $row['remaining']) }}" placeholder="0" @disabled($row['remaining'] <= 0 || $row['amount_due'] <= 0)>
                                        </div>
                                        <div class="salary-inline-field">
                                            <label class="form-label">Date</label>
                                            <input type="date" name="date_paiement" class="form-control form-control-sm" value="{{ now()->toDateString() }}" @disabled($row['remaining'] <= 0 || $row['amount_due'] <= 0)>
                                        </div>
                                        <div class="salary-inline-submit">
                                            <button type="submit" class="btn btn-sm theme-action-btn w-100" title="Enregistrer le versement" @disabled($row['remaining'] <= 0 || $row['amount_due'] <= 0)>
                                                <i class="bx bx-check me-1"></i>Enregistrer
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <a class="btn btn-sm theme-action-btn w-100" href="{{ route('enseignants.salaires.bulletin', ['id_enseignant' => $row['enseignant']->id_enseignant, 'mois' => $filters['mois'], 'annee' => $filters['annee'], 'source' => $filters['source']]) }}">
                                        <i class="bx bx-file me-1"></i>Bulletin PDF
                                    </a>
                                @endif
                                @if($row['salary'] && $row['salary']->lignes->isNotEmpty())
                                    <div class="small text-muted mt-2">
                                        Dernier versement : {{ optional($row['salary']->lignes->sortByDesc('date_paiement')->first()->date_paiement)->format('d/m/Y') }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                {{ $mode === 'state' ? 'Aucun salaire à afficher pour cette période.' : 'Aucun enseignant payable par l’école trouvé.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($mode === 'state' && $visibleRows->isNotEmpty())
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end px-4">Montant total</th>
                            <th class="text-end">{{ number_format($stateSummary['due'], 0, ',', ' ') }} F</th>
                            <th class="text-end">{{ number_format($stateSummary['paid'], 0, ',', ' ') }} F</th>
                            <th class="text-end">{{ number_format($stateSummary['remaining'], 0, ',', ' ') }} F</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <style>
        html[data-theme] .theme-header-badge {
            background-color: var(--accent-light) !important;
            border: 1px solid rgba(255,255,255,0.28);
            color: var(--text-on-accent) !important;
        }
        .salary-table-head th {
            background: #fff !important;
            background-color: #fff !important;
            --bs-table-bg: #fff !important;
            box-shadow: none !important;
            color: #212529 !important;
        }
        html[data-theme] .theme-action-btn,
        html[data-theme] .theme-tab-btn.active {
            background-color: var(--theme-accent) !important;
            border-color: var(--theme-accent) !important;
            color: #fff !important;
        }
        html[data-theme] .theme-tab-btn {
            background: rgba(255,255,255,.12) !important;
            border-color: rgba(255,255,255,.28) !important;
            color: var(--text-on-accent) !important;
        }
        .bulk-payment-table td,
        .bulk-payment-table th {
            white-space: nowrap;
        }
        .teacher-payment-group {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            background: var(--bg-card);
        }
        .teacher-payment-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .85rem 1rem;
            background: var(--accent-light);
            color: var(--theme-accent);
        }
        .teacher-payment-head small {
            color: var(--text-muted);
        }
        .bulk-payment-amount.is-invalid {
            border-color: #dc3545 !important;
            background-color: #fff5f5 !important;
        }
        .salary-payment-cell {
            min-width: 430px;
        }
        .salary-inline-payment {
            display: grid;
            grid-template-columns: minmax(110px, 1fr) minmax(145px, 1fr) minmax(130px, auto);
            gap: .5rem;
            align-items: end;
        }
        .salary-inline-payment .form-label {
            display: block;
            margin-bottom: .2rem;
            font-size: .68rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        .salary-inline-submit {
            min-width: 130px;
        }
        @media (max-width: 768px) {
            .salary-payment-cell {
                min-width: 260px;
            }
            .salary-inline-payment {
                grid-template-columns: 1fr;
            }
            .salary-inline-submit {
                min-width: 0;
            }
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const formatter = new Intl.NumberFormat('fr-FR');
                const totalEl = document.getElementById('bulkPaymentTotal');
                const inputs = document.querySelectorAll('.bulk-payment-amount');

                const updateTotal = () => {
                    let total = 0;

                    inputs.forEach((input) => {
                        const max = Number(input.dataset.remaining || 0);
                        const value = Number(input.value || 0);
                        const invalid = value < 0 || value > max;

                        input.classList.toggle('is-invalid', invalid);
                        if (!invalid) {
                            total += value;
                        }
                    });

                    if (totalEl) {
                        totalEl.textContent = formatter.format(total) + ' F';
                    }
                };

                inputs.forEach((input) => input.addEventListener('input', updateTotal));
                updateTotal();
            });
        </script>
    @endpush
@endsection
