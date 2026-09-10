@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">Configuration</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Revendeurs</li>
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

    <div class="row g-4">
        <div class="col-12 col-lg-3">
            @include('configuration._menu')
        </div>

        <div class="col-12 col-lg-9">
            <div class="card theme-card shadow-sm mb-4">
                <div class="card-header theme-header">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-briefcase-fill me-2"></i>Nouveau revendeur</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('configuration.revendeurs.store') }}" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nom du revendeur</label>
                            <input name="nom" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nom du contact (compte de connexion)</label>
                            <input name="nomPrenom" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Email</label>
                            <input name="email" type="email" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Téléphone</label>
                            <input name="telephone" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Mot de passe initial</label>
                            <input name="pwd" type="password" class="form-control" minlength="4" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Numéro Orange Money / Wave (optionnel)</label>
                            <input name="numero_orange_wave" class="form-control" placeholder="Ex: 74745669">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Numéro MobiCash (optionnel)</label>
                            <input name="numero_mobicash" class="form-control" placeholder="Ex: 67205736">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn theme-action-btn w-100" type="submit">Créer le revendeur</button>
                        </div>
                    </form>
                </div>
            </div>

            @forelse($revendeurs as $revendeur)
                <div class="card theme-card shadow-sm mb-4">
                    <div class="card-header theme-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">
                            {{ $revendeur->nom }}
                            @if($revendeur->utilisateur)
                                <span class="small fw-normal opacity-75">— {{ $revendeur->utilisateur->email }}</span>
                            @endif
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ $revendeur->actif ? 'bg-success' : 'bg-secondary' }}">{{ $revendeur->actif ? 'Actif' : 'Inactif' }}</span>
                            <span class="badge bg-info">{{ $revendeur->ecoles_count }} école(s)</span>
                            <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#editRevendeurModal{{ $revendeur->id }}">
                                <i class="bi bi-pencil-fill me-1"></i>Modifier
                            </button>
                            <form method="POST" action="{{ route('configuration.revendeurs.toggle', $revendeur) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-light" type="submit">{{ $revendeur->actif ? 'Désactiver' : 'Activer' }}</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">Formules ouvertes à ce revendeur</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Formule</th>
                                        <th class="text-end">Prix de gros</th>
                                        <th class="text-end">Prix de revente actuel</th>
                                        <th>Actif</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($revendeur->offres as $revendeurOffre)
                                        <tr>
                                            <td>{{ $revendeurOffre->offre->nom }}</td>
                                            <td class="text-end">{{ number_format($revendeurOffre->offre->montant, 0, ',', ' ') }} {{ $revendeurOffre->offre->devise }}</td>
                                            <td class="text-end fw-bold">{{ number_format($revendeurOffre->montant_revente, 0, ',', ' ') }} {{ $revendeurOffre->offre->devise }}</td>
                                            <td>{{ $revendeurOffre->actif ? 'Oui' : 'Non' }}</td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('configuration.revendeurs.offres.toggle', $revendeurOffre) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-sm btn-outline-secondary" type="submit">{{ $revendeurOffre->actif ? 'Retirer' : 'Réactiver' }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">Aucune formule ouverte pour ce revendeur.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <form method="POST" action="{{ route('configuration.revendeurs.offres.store', $revendeur) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">Ouvrir une formule</label>
                                <select name="id_offre" class="form-select form-select-sm" required>
                                    <option value="">Choisir une formule</option>
                                    @foreach($offres as $offre)
                                        <option value="{{ $offre->id }}">{{ $offre->nom }} ({{ number_format($offre->montant, 0, ',', ' ') }} {{ $offre->devise }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Prix de revente initial</label>
                                <input name="montant_revente" type="number" min="0" step="1" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-sm btn-primary w-100" type="submit">Ouvrir</button>
                            </div>
                        </form>

                        @php($revendeurReversements = $reversements->get($revendeur->id, collect()))
                        @php($enAttente = $revendeurReversements->where('reverse_statut', 'en_attente'))
                        @if($revendeurReversements->isNotEmpty())
                            <hr class="my-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fw-bold mb-0">Reversement au développeur (prix de gros)</h6>
                                @if($enAttente->isNotEmpty())
                                    <span class="badge bg-danger">{{ number_format($enAttente->sum('montant_du_developpeur'), 0, ',', ' ') }} XOF dû</span>
                                @else
                                    <span class="badge bg-success">À jour</span>
                                @endif
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>École</th>
                                            <th>Formule</th>
                                            <th class="text-end">Montant dû</th>
                                            <th>Statut</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($revendeurReversements as $reversement)
                                            <tr>
                                                <td>{{ $reversement->ecole->nomEcole ?? '—' }}</td>
                                                <td>{{ $reversement->offre->nom ?? '—' }}</td>
                                                <td class="text-end">{{ number_format($reversement->montant_du_developpeur, 0, ',', ' ') }} {{ $reversement->devise }}</td>
                                                <td>
                                                    @if($reversement->reverse_statut === 'recu')
                                                        <span class="badge bg-success">Reçu le {{ $reversement->reverse_at?->format('d/m/Y') }}</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">En attente</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($reversement->reverse_statut === 'en_attente')
                                                        <form method="POST" action="{{ route('configuration.reversements.recu', $reversement) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="btn btn-sm btn-outline-success" type="submit">Marquer reçu</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal fade" id="editRevendeurModal{{ $revendeur->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content border-0 rounded-4 shadow">
                            <form method="POST" action="{{ route('configuration.revendeurs.update', $revendeur) }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-header theme-header">
                                    <h5 class="modal-title fw-bold">Modifier {{ $revendeur->nom }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Nom du revendeur</label>
                                            <input name="nom" class="form-control" value="{{ $revendeur->nom }}" required>
                                        </div>
                                        <div class="col-md-6"></div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Numéro Orange Money / Wave</label>
                                            <input name="numero_orange_wave" class="form-control" value="{{ $revendeur->numero_orange_wave }}" placeholder="Ex: 74745669">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Numéro MobiCash</label>
                                            <input name="numero_mobicash" class="form-control" value="{{ $revendeur->numero_mobicash }}" placeholder="Ex: 67205736">
                                        </div>
                                        <div class="col-12"><hr class="my-1"></div>
                                        <div class="col-12 small text-muted fw-bold text-uppercase">Compte de connexion</div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Nom du contact</label>
                                            <input name="nomPrenom" class="form-control" value="{{ $revendeur->utilisateur->nomPrenom ?? '' }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Email</label>
                                            <input name="email" type="email" class="form-control" value="{{ $revendeur->utilisateur->email ?? '' }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Téléphone</label>
                                            <input name="telephone" class="form-control" value="{{ $revendeur->utilisateur->telephone ?? '' }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Nouveau mot de passe</label>
                                            <input name="pwd" type="password" class="form-control" minlength="4" placeholder="Laisser vide pour ne pas changer">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn theme-action-btn">Enregistrer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card theme-card shadow-sm">
                    <div class="card-body text-center text-muted py-4">Aucun revendeur créé pour le moment.</div>
                </div>
            @endforelse
        </div>
    </div>
@endsection
