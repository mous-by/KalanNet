@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Élèves & Parents</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active">Dossiers élèves</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card theme-card shadow-sm mb-3">
        <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0 fw-bold"><i class="bi bi-folder2-open me-2"></i>Dossiers élèves</h5>
            <a href="{{ route('inscriptions.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nouvelle inscription
            </a>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('eleves.dossiers') }}" method="GET" class="row g-3 align-items-end" id="dossiers-filter-form">
                <div class="col-md-3">
                    <label class="form-label">Recherche rapide</label>
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nom, prénom ou matricule">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Classe</label>
                    <select name="id_classe" class="form-select" id="dossier-classe">
                        <option value="">Choisir une classe...</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id_classe }}" @selected(request('id_classe') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Année scolaire</label>
                    <select name="id_annee" class="form-select" id="dossier-annee">
                        <option value="">Choisir une année...</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id_anneeScolaire }}" @selected(request('id_annee') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="actifs" @selected($status === 'actifs')>Actifs</option>
                        <option value="transferes" @selected($status === 'transferes')>Transférés</option>
                        <option value="retires" @selected($status === 'retires')>Retirés</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100" title="Rechercher">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="dossiers-loading" class="dossiers-loading d-none">
        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
        <strong>Chargement des dossiers...</strong>
    </div>

    @if(!$showList)
        <div class="empty-dossiers-state text-center py-5">
            <div class="empty-dossiers-icon mx-auto mb-3 d-flex align-items-center justify-content-center">
                <i class="bi bi-folder2-open fs-1"></i>
            </div>
            <h5 class="fw-bold mb-2">Aucun dossier affiché pour le moment</h5>
            <p class="text-muted mb-0">Sélectionnez une classe et une année scolaire pour afficher la liste des dossiers élèves.</p>
        </div>
    @else
        <div id="dossiers-result">
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card theme-card shadow-sm h-100">
                        <div class="card-body p-4 d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted text-uppercase small fw-bold mb-1">Dossiers affichés</p>
                                <h3 class="fw-bold mb-0">{{ number_format($eleves->total(), 0, ',', ' ') }}</h3>
                                <small class="text-muted d-block mt-2">Résultat du filtre</small>
                            </div>
                            <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-folder2-open fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card theme-card shadow-sm h-100">
                        <div class="card-body p-4 d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted text-uppercase small fw-bold mb-1">Filtre statut</p>
                                <h3 class="fw-bold mb-0">{{ ['actifs' => 'Actifs', 'transferes' => 'Transférés', 'retires' => 'Retirés'][$status] ?? 'Actifs' }}</h3>
                                <small class="text-muted d-block mt-2">Dossiers concernés</small>
                            </div>
                            <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-funnel fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card theme-card shadow-sm h-100">
                        <div class="card-body p-4 d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted text-uppercase small fw-bold mb-1">Accès</p>
                                <h3 class="fw-bold mb-0">Ouvrir</h3>
                                <small class="text-muted d-block mt-2">Un bouton, un dossier</small>
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
                                <th>Élève</th>
                                <th>Matricule</th>
                                <th>Classe</th>
                                <th>Parent à contacter</th>
                                <th>Statut</th>
                                <th class="text-end">Dossier</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($eleves as $eleve)
                                @php
                                    $statusLabel = match((int) $eleve->etat_dossier) {
                                        1 => 'Transféré',
                                        2 => 'Retiré',
                                        default => 'Actif',
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
                                                <small class="text-muted">{{ $eleve->genre_eleve ?: 'Genre non renseigné' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark font-monospace">{{ $eleve->matricule ?: 'N/A' }}</span></td>
                                    <td>{{ $eleve->classe?->nom_classe ?? 'Non renseignée' }}</td>
                                    <td>
                                        @php($parent = $eleve->parents->first())
                                        <div>{{ $parent?->nom_prenom_parent ?? 'Non renseigné' }}</div>
                                        <small class="text-muted">{{ $parent?->telephone_parent ?? '' }}</small>
                                    </td>
                                    <td><span class="badge theme-icon-soft">{{ $statusLabel }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('eleves.show', $eleve->id_eleve) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-folder2-open me-1"></i>Ouvrir
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">Aucun dossier ne correspond à ces critères.</td>
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
            color: var(--theme-primary);
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
