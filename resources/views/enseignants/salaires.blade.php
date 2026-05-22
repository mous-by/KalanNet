@extends('layouts.app')

@section('content')
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
                    <h4 class="fw-bold mb-0">{{ number_format($summary['teachers'], 0, ',', ' ') }}</h4>
                    <span class="small text-muted">Période filtrée</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">À payer</small>
                    <h4 class="fw-bold mb-0">{{ number_format($summary['due'], 0, ',', ' ') }}</h4>
                    <span class="small text-muted">FCFA</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">Déjà versé</small>
                    <h4 class="fw-bold mb-0 text-success">{{ number_format($summary['paid'], 0, ',', ' ') }}</h4>
                    <span class="small text-muted">FCFA</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">Reste</small>
                    <h4 class="fw-bold mb-0 {{ $summary['remaining'] > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($summary['remaining'], 0, ',', ' ') }}</h4>
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

    <div class="card theme-card shadow-sm overflow-hidden">
        <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold">
                    <i class="bx {{ $mode === 'state' ? 'bx-list-check' : 'bx-money-withdraw' }} me-2"></i>{{ $mode === 'state' ? 'État de paiement des enseignants' : 'Paiement des enseignants' }}
                </h5>
                <a href="{{ route('enseignants.salaires', $filters) }}" class="btn btn-sm {{ $mode === 'pay' ? 'btn-light' : 'btn-outline-light' }}">Paiement</a>
                <a href="{{ route('enseignants.salaires.etat', $filters) }}" class="btn btn-sm {{ $mode === 'state' ? 'btn-light' : 'btn-outline-light' }}">État</a>
            </div>
            <span class="badge theme-header-badge">{{ $sources[$filters['source']] ?? $filters['source'] }} - {{ $months[$filters['mois']] ?? $filters['mois'] }} {{ $filters['annee'] }}</span>
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
                        <th class="py-3 small fw-bold text-uppercase bg-white text-end" style="color: #212529 !important;">À payer</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white text-end" style="color: #212529 !important;">Versé</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white text-end" style="color: #212529 !important;">Reste</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Statut</th>
                        <th class="px-4 py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">{{ $mode === 'state' ? 'Bulletin' : 'Versement' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salaryRows as $row)
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
                            <td class="px-4" style="min-width: 260px;">
                                @if($mode === 'pay')
                                    <form action="{{ route('enseignants.salaires.pay') }}" method="POST" class="row g-2">
                                        @csrf
                                        <input type="hidden" name="id_enseignant" value="{{ $row['enseignant']->id_enseignant }}">
                                        <input type="hidden" name="mois" value="{{ $filters['mois'] }}">
                                        <input type="hidden" name="annee" value="{{ $filters['annee'] }}">
                                        <input type="hidden" name="source" value="{{ $filters['source'] }}">
                                        <div class="col-6">
                                            <input type="number" name="montant_verse" class="form-control form-control-sm" min="1" max="{{ max(1, (int) $row['remaining']) }}" placeholder="Montant" @disabled($row['remaining'] <= 0 || $row['amount_due'] <= 0)>
                                        </div>
                                        <div class="col-4">
                                            <input type="date" name="date_paiement" class="form-control form-control-sm" value="{{ now()->toDateString() }}" @disabled($row['remaining'] <= 0 || $row['amount_due'] <= 0)>
                                        </div>
                                        <div class="col-2 d-grid">
                                            <button class="btn btn-sm theme-pill-active" title="Enregistrer le versement" @disabled($row['remaining'] <= 0 || $row['amount_due'] <= 0)>
                                                <i class="bx bx-check"></i>
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <a class="btn btn-sm theme-pill-active w-100" href="{{ route('enseignants.salaires.bulletin', ['id_enseignant' => $row['enseignant']->id_enseignant, 'mois' => $filters['mois'], 'annee' => $filters['annee'], 'source' => $filters['source']]) }}">
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
                            <td colspan="9" class="text-center py-5 text-muted">Aucun enseignant payable par l’école trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
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
    </style>
@endsection
