@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('eleves.title_parents') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active">{{ __('eleves.dossiers_title') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card theme-card shadow-sm mb-3">
        <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0 fw-bold"><i class="bi bi-folder2-open me-2"></i>{{ __('eleves.dossiers_title') }}</h5>
            <a href="{{ route('inscriptions.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>{{ __('eleves.new_inscription') }}
            </a>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('eleves.dossiers') }}" method="GET" class="row g-3 align-items-end" id="dossiers-filter-form">
                <div class="col-md-3">
                    <label class="form-label">{{ __('eleves.quick_search') }}</label>
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('eleves.search_quick_placeholder') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('eleves.th_classe') }}</label>
                    <select name="id_classe" class="form-select" id="dossier-classe">
                        <option value="">{{ __('eleves.choose_classe') }}</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id_classe }}" @selected(request('id_classe') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('eleves.label_annee') }}</label>
                    <select name="id_annee" class="form-select" id="dossier-annee">
                        <option value="">{{ __('eleves.choose_annee') }}</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id_anneeScolaire }}" @selected(request('id_annee') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('eleves.status_label') }}</label>
                    <select name="status" class="form-select">
                        <option value="actifs" @selected($status === 'actifs')>{{ __('eleves.status_actifs') }}</option>
                        <option value="transferes" @selected($status === 'transferes')>{{ __('eleves.status_transferes') }}</option>
                        <option value="diplomes" @selected($status === 'diplomes')>{{ __('eleves.status_diplomes') }}</option>
                        <option value="retires" @selected($status === 'retires')>{{ __('eleves.status_retires') }}</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100" title="{{ __('eleves.search_button') }}">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="dossiers-loading" class="dossiers-loading d-none">
        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
        <strong>{{ __('eleves.loading_dossiers') }}</strong>
    </div>

    @if(!$showList)
        <div class="empty-dossiers-state text-center py-5">
            <div class="empty-dossiers-icon mx-auto mb-3 d-flex align-items-center justify-content-center">
                <i class="bi bi-folder2-open fs-1"></i>
            </div>
            <h5 class="fw-bold mb-2">{{ __('eleves.empty_dossiers_title') }}</h5>
            <p class="text-muted mb-0">{{ __('eleves.empty_dossiers_text') }}</p>
        </div>
    @else
        <div id="dossiers-result">
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card theme-card shadow-sm h-100">
                        <div class="card-body p-4 d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted text-uppercase small fw-bold mb-1">{{ __('eleves.stat_dossiers_affiches') }}</p>
                                <h3 class="fw-bold mb-0">{{ number_format($eleves->total(), 0, ',', ' ') }}</h3>
                                <small class="text-muted d-block mt-2">{{ __('eleves.stat_resultat_filtre') }}</small>
                            </div>
                            <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-folder2-open fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card theme-card shadow-sm h-100">
                        <div class="card-body p-4 d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted text-uppercase small fw-bold mb-1">{{ __('eleves.stat_filtre_statut') }}</p>
                                <h3 class="fw-bold mb-0">{{ ['actifs' => __('eleves.status_actifs'), 'transferes' => __('eleves.status_transferes'), 'diplomes' => __('eleves.status_diplomes'), 'retires' => __('eleves.status_retires')][$status] ?? __('eleves.status_actifs') }}</h3>
                                <small class="text-muted d-block mt-2">{{ __('eleves.stat_dossiers_concernes') }}</small>
                            </div>
                            <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-funnel fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card theme-card shadow-sm h-100">
                        <div class="card-body p-4 d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted text-uppercase small fw-bold mb-1">{{ __('eleves.stat_acces') }}</p>
                                <h3 class="fw-bold mb-0">{{ __('eleves.stat_ouvrir') }}</h3>
                                <small class="text-muted d-block mt-2">{{ __('eleves.stat_bouton_dossier') }}</small>
                            </div>
                            <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-box-arrow-in-right fs-4"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card theme-card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('eleves.th_eleve') }}</th>
                                <th>{{ __('eleves.th_matricule') }}</th>
                                <th>{{ __('eleves.th_classe') }}</th>
                                <th>{{ __('eleves.th_parent_contact') }}</th>
                                <th>{{ __('eleves.status_label') }}</th>
                                <th class="text-end">{{ __('eleves.th_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($eleves as $eleve)
                                @php
                                    $statusLabel = match((int) $eleve->etat_dossier) {
                                        1 => __('eleves.status_transfere'),
                                        2 => __('eleves.status_retire'),
                                        3 => __('eleves.status_diplome'),
                                        default => __('eleves.status_actif'),
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center overflow-hidden" style="width: 42px; height: 42px;">
                                                @if($eleve->image)
                                                    <img src="{{ asset($eleve->image) }}" alt="" class="w-100 h-100 object-fit-cover">
                                                @else
                                                    <i class="bi bi-person text-muted"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ $eleve->prenom_eleve }} {{ $eleve->nom_eleve }}</div>
                                                <small class="text-muted">{{ $eleve->genre_eleve ?: __('eleves.genre_non_renseigne') }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark font-monospace">{{ $eleve->matricule ?: 'N/A' }}</span></td>
                                    <td>{{ $eleve->classe?->nom_classe ?? __('eleves.not_specified') }}</td>
                                    <td>
                                        @php($parent = $eleve->parents->first())
                                        <div>{{ $parent?->nom_prenom_parent ?? __('eleves.not_specified_m') }}</div>
                                        <small class="text-muted">{{ $parent?->telephone_parent ?? '' }}</small>
                                    </td>
                                    <td><span class="badge {{ (int) $eleve->etat_dossier === 3 ? 'bg-success' : 'theme-icon-soft' }}">{{ $statusLabel }}</span></td>
                                    <td class="text-end">
                                        @if((int) $eleve->etat_dossier === 1 && (auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('eleves_modification')))
                                            <button type="button" class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal" data-bs-target="#reintegrateModal{{ $eleve->id_eleve }}">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>{{ __('eleves.reintegrer') }}
                                            </button>
                                        @endif
                                        <a href="{{ route('eleves.show', $eleve->id_eleve) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-folder2-open me-1"></i>{{ __('eleves.stat_ouvrir') }}
                                        </a>
                                        @if((int) $eleve->etat_dossier === 1 && (auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('eleves_modification')))
                                            <div class="modal fade text-start" id="reintegrateModal{{ $eleve->id_eleve }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content border-0 rounded-4 shadow">
                                                        <form action="{{ route('eleves.reintegrate', $eleve->id_eleve) }}" method="POST">
                                                            @csrf
                                                            <div class="modal-header theme-header">
                                                                <h5 class="modal-title fw-bold">{{ __('eleves.reintegrer_title', ['nom' => $eleve->prenom_eleve . ' ' . $eleve->nom_eleve]) }}</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('eleves.close') }}"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">{{ __('eleves.classe_retour') }}</label>
                                                                    <select name="id_classe" class="form-select" required>
                                                                        @foreach($classes as $classe)
                                                                            <option value="{{ $classe->id_classe }}" @selected($classe->id_classe == $eleve->id_classe)>{{ $classe->nom_classe }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">{{ __('eleves.label_annee') }}</label>
                                                                    <select name="id_annee" class="form-select" required>
                                                                        @foreach($annees as $annee)
                                                                            <option value="{{ $annee->id_anneeScolaire }}" @selected($annee->id_anneeScolaire == $eleve->id_annee)>{{ $annee->annee }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div>
                                                                    <label class="form-label">{{ __('eleves.motif_retour') }}</label>
                                                                    <input type="text" name="motif_retour" class="form-control" placeholder="{{ __('eleves.motif_retour_placeholder') }}">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('eleves.cancel') }}</button>
                                                                <button type="submit" class="btn btn-success">{{ __('eleves.reintegrer') }}</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">{{ __('eleves.no_dossier') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($eleves->hasPages())
                    <div class="card-body">{{ $eleves->links() }}</div>
                @endif
            </div>
        </div>
    @endif

    <style>
        .widget-icon { width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; }
        .empty-dossiers-state,
        .dossiers-loading {
            border: 1px dashed var(--bs-border-color);
            border-radius: 8px;
            background: #fff;
        }
        .empty-dossiers-icon {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            color: var(--theme-accent);
            background: var(--accent-light);
        }
        .dossiers-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            min-height: 140px;
            margin-bottom: 16px;
        }
    </style>

    <script>
        const dossiersForm = document.getElementById('dossiers-filter-form');
        const dossierClasse = document.getElementById('dossier-classe');
        const dossierAnnee = document.getElementById('dossier-annee');
        const dossiersLoading = document.getElementById('dossiers-loading');
        const dossiersResult = document.getElementById('dossiers-result');
        const dossierStatus = document.querySelector('select[name="status"]');

        function submitDossiersWhenReady() {
            if (!dossiersForm || !dossierClasse || !dossierAnnee) return;
            if (!dossierClasse.value || !dossierAnnee.value) return;
            dossiersResult?.classList.add('d-none');
            dossiersLoading?.classList.remove('d-none');
            dossiersForm.submit();
        }

        dossierClasse?.addEventListener('change', submitDossiersWhenReady);
        dossierAnnee?.addEventListener('change', submitDossiersWhenReady);
        dossierStatus?.addEventListener('change', submitDossiersWhenReady);
        dossiersForm?.addEventListener('submit', () => {
            dossiersResult?.classList.add('d-none');
            dossiersLoading?.classList.remove('d-none');
        });
    </script>
@endsection
