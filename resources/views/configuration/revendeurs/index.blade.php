@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('configuration.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">{{ __('configuration.title') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('configuration.menu_revendeurs') }}</li>
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
                    <h5 class="mb-0 fw-bold"><i class="bi bi-briefcase-fill me-2"></i>{{ __('configuration.rv_nouveau_title') }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('configuration.revendeurs.store') }}" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ __('configuration.rv_nom_label') }}</label>
                            <input name="nom" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ __('configuration.rv_nom_contact_label') }}</label>
                            <input name="nomPrenom" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ __('configuration.uf_email_label') }}</label>
                            <input name="email" type="email" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ __('configuration.ut_th_telephone') }}</label>
                            <input name="telephone" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ __('configuration.rv_mot_de_passe_initial') }}</label>
                            <input name="pwd" type="password" class="form-control" minlength="4" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ __('configuration.rv_numero_orange_wave') }}</label>
                            <input name="numero_orange_wave" class="form-control" placeholder="Ex: 74745669">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ __('configuration.rv_numero_mobicash') }}</label>
                            <input name="numero_mobicash" class="form-control" placeholder="Ex: 67205736">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn theme-action-btn w-100" type="submit">{{ __('configuration.rv_creer_revendeur') }}</button>
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
                            <span class="badge {{ $revendeur->actif ? 'bg-success' : 'bg-secondary' }}">{{ $revendeur->actif ? __('configuration.rv_actif') : __('configuration.rv_inactif') }}</span>
                            <span class="badge bg-info">{{ __('configuration.rv_ecoles_count', ['count' => $revendeur->ecoles_count]) }}</span>
                            <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#editRevendeurModal{{ $revendeur->id }}">
                                <i class="bi bi-pencil-fill me-1"></i>{{ __('configuration.modifier') }}
                            </button>
                            <form method="POST" action="{{ route('configuration.revendeurs.toggle', $revendeur) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-light" type="submit">{{ $revendeur->actif ? __('configuration.ut_toggle_title_desactiver') : __('configuration.ut_toggle_title_activer') }}</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">{{ __('configuration.rv_formules_ouvertes_title') }}</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('configuration.rv_th_formule') }}</th>
                                        <th class="text-end">{{ __('configuration.rv_th_prix_gros') }}</th>
                                        <th class="text-end">{{ __('configuration.rv_th_prix_revente') }}</th>
                                        <th>{{ __('configuration.rv_th_actif') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($revendeur->offres as $revendeurOffre)
                                        <tr>
                                            <td>{{ $revendeurOffre->offre->nom }}</td>
                                            <td class="text-end">{{ number_format($revendeurOffre->offre->montant, 0, ',', ' ') }} {{ $revendeurOffre->offre->devise }}</td>
                                            <td class="text-end fw-bold">{{ number_format($revendeurOffre->montant_revente, 0, ',', ' ') }} {{ $revendeurOffre->offre->devise }}</td>
                                            <td>{{ $revendeurOffre->actif ? __('configuration.oui') : __('configuration.non') }}</td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('configuration.revendeurs.offres.toggle', $revendeurOffre) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-sm btn-outline-secondary" type="submit">{{ $revendeurOffre->actif ? __('configuration.rv_retirer') : __('configuration.rv_reactiver') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">{{ __('configuration.rv_empty_formules') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <form method="POST" action="{{ route('configuration.revendeurs.offres.store', $revendeur) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">{{ __('configuration.rv_ouvrir_formule_label') }}</label>
                                <select name="id_offre" class="form-select form-select-sm" required>
                                    <option value="">{{ __('configuration.rv_choisir_formule') }}</option>
                                    @foreach($offres as $offre)
                                        <option value="{{ $offre->id }}">{{ $offre->nom }} ({{ number_format($offre->montant, 0, ',', ' ') }} {{ $offre->devise }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">{{ __('configuration.rv_prix_revente_initial') }}</label>
                                <input name="montant_revente" type="number" min="0" step="1" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-sm btn-primary w-100" type="submit">{{ __('configuration.rv_ouvrir') }}</button>
                            </div>
                        </form>

                        @php($revendeurReversements = $reversements->get($revendeur->id, collect()))
                        @php($enAttente = $revendeurReversements->where('reverse_statut', 'en_attente'))
                        @if($revendeurReversements->isNotEmpty())
                            <hr class="my-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fw-bold mb-0">{{ __('configuration.rv_reversement_title') }}</h6>
                                @if($enAttente->isNotEmpty())
                                    <span class="badge bg-danger">{{ __('configuration.rv_montant_du', ['montant' => number_format($enAttente->sum('montant_du_developpeur'), 0, ',', ' ')]) }}</span>
                                @else
                                    <span class="badge bg-success">{{ __('configuration.rv_a_jour') }}</span>
                                @endif
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>{{ __('configuration.th_ecole') }}</th>
                                            <th>{{ __('configuration.rv_th_formule') }}</th>
                                            <th class="text-end">{{ __('configuration.rv_th_montant_du') }}</th>
                                            <th>{{ __('configuration.th_statut') }}</th>
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
                                                        <span class="badge bg-success">{{ __('configuration.rv_recu_le', ['date' => $reversement->reverse_at?->format('d/m/Y')]) }}</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">{{ __('configuration.rv_en_attente') }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($reversement->reverse_statut === 'en_attente')
                                                        <form method="POST" action="{{ route('configuration.reversements.recu', $reversement) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="btn btn-sm btn-outline-success" type="submit">{{ __('configuration.rv_marquer_recu') }}</button>
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
                                    <h5 class="modal-title fw-bold">{{ __('configuration.rv_modifier_title', ['nom' => $revendeur->nom]) }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('configuration.fermer') }}"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">{{ __('configuration.rv_nom_label') }}</label>
                                            <input name="nom" class="form-control" value="{{ $revendeur->nom }}" required>
                                        </div>
                                        <div class="col-md-6"></div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">{{ __('configuration.rv_numero_orange_wave_sans_optionnel') }}</label>
                                            <input name="numero_orange_wave" class="form-control" value="{{ $revendeur->numero_orange_wave }}" placeholder="Ex: 74745669">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">{{ __('configuration.rv_numero_mobicash_sans_optionnel') }}</label>
                                            <input name="numero_mobicash" class="form-control" value="{{ $revendeur->numero_mobicash }}" placeholder="Ex: 67205736">
                                        </div>
                                        <div class="col-12"><hr class="my-1"></div>
                                        <div class="col-12 small text-muted fw-bold text-uppercase">{{ __('configuration.rv_compte_connexion') }}</div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">{{ __('configuration.rv_nom_contact_court') }}</label>
                                            <input name="nomPrenom" class="form-control" value="{{ $revendeur->utilisateur->nomPrenom ?? '' }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">{{ __('configuration.uf_email_label') }}</label>
                                            <input name="email" type="email" class="form-control" value="{{ $revendeur->utilisateur->email ?? '' }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">{{ __('configuration.ut_th_telephone') }}</label>
                                            <input name="telephone" class="form-control" value="{{ $revendeur->utilisateur->telephone ?? '' }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">{{ __('configuration.rv_nouveau_mot_de_passe') }}</label>
                                            <input name="pwd" type="password" class="form-control" minlength="4" placeholder="{{ __('configuration.rv_laisser_vide_ne_pas_changer') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('configuration.annuler') }}</button>
                                    <button type="submit" class="btn theme-action-btn">{{ __('configuration.enregistrer') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card theme-card shadow-sm">
                    <div class="card-body text-center text-muted py-4">{{ __('configuration.rv_empty_revendeurs') }}</div>
                </div>
            @endforelse
        </div>
    </div>
@endsection
