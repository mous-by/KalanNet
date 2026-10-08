@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('configuration.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">{{ __('configuration.menu_apercu') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('configuration.menu_ecoles') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('configuration.partials.flash')

    <div class="row g-4">
        <div class="col-12 col-lg-3">
            @include('configuration._menu')
        </div>
        <div class="col-12 col-lg-9">
            <div class="card theme-card shadow-sm">
                <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold"><i class="bx bx-building me-2"></i>{{ __('configuration.eco_gestion') }}</h5>
                    @if(Auth::user()->droit === 'SupAdmin')
                        <button type="button" class="btn btn-sm d-flex align-items-center gap-1 shadow-sm text-white"
                                style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;"
                                data-bs-toggle="modal" data-bs-target="#ecoleCreateModal">
                            <i class="bi bi-plus-lg"></i>
                            <span>{{ __('configuration.ajouter') }}</span>
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-end align-items-center flex-wrap mb-3 gap-3">
                        <form action="{{ route('configuration.ecoles') }}" method="GET" class="col-md-5" data-auto-filter="true">
                            <input type="text" name="search" class="form-control" placeholder="{{ __('configuration.eco_search_placeholder') }}" value="{{ request('search') }}">
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('configuration.menu_ecoles') }}</th>
                                    <th>{{ __('configuration.menu_pays') }}</th>
                                    <th>{{ __('configuration.eco_th_type') }}</th>
                                    <th>{{ __('configuration.menu_academies') }}</th>
                                    <th>{{ __('configuration.menu_caps') }}</th>
                                    <th>{{ __('configuration.eco_th_contact') }}</th>
                                    <th>{{ __('configuration.th_statut') }}</th>
                                    <th class="text-end">{{ __('configuration.th_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ecoles as $ecole)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded border d-flex align-items-center justify-content-center overflow-hidden" style="width: 42px; height: 42px; background: #fff !important;">
                                                    @if($ecole->logoEcole)
                                                        <img src="{{ asset($ecole->logoEcole) }}" alt="" class="w-100 h-100 object-fit-contain p-1">
                                                    @else
                                                        <i class="bx bx-building text-muted"></i>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $ecole->nomEcole }}</div>
                                                    <small class="text-muted">{{ $ecole->adresse ?? __('configuration.eco_adresse_non_renseignee') }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $ecole->pays->nom ?? 'N/A' }}</td>
                                        <td>{{ $ecole->typeEcole }}</td>
                                        <td>{{ $ecole->academieRef->nom_academie ?? $ecole->academie ?? 'N/A' }}</td>
                                        <td>{{ $ecole->capRef->nom_cap ?? $ecole->cap ?? 'N/A' }}</td>
                                        <td>
                                            <div>{{ $ecole->telephone ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ $ecole->email ?: __('configuration.eco_email_non_renseigne') }}</small>
                                        </td>
                                        <td><span class="badge theme-icon-soft">{{ ucfirst($ecole->statut ?? 'public') }}</span></td>
                                        <td class="text-end">
                                            @if(Auth::user()->droit === 'SupAdmin')
                                                <button class="btn btn-light btn-sm p-2" data-bs-toggle="modal" data-bs-target="#ecoleEditModal{{ $ecole->idEcole }}" title="{{ __('configuration.modifier') }}">
                                                    <i class="bx bx-edit text-warning fs-5"></i>
                                                </button>
                                                {{-- Suppression définitive : fenêtre de confirmation (inventaire + nom à retaper). --}}
                                                <form action="{{ route('configuration.ecoles.destroy', $ecole->idEcole) }}" method="POST" class="d-inline js-supprimer-ecole" data-inventaire="{{ route('configuration.ecoles.suppression', $ecole->idEcole) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="confirmation" value="">
                                                    <button type="submit" class="btn btn-light btn-sm p-2" title="{{ __('configuration.supprimer') }}">
                                                        <i class="bx bx-trash text-danger fs-5"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted small">{{ __('configuration.eco_lecture_seule') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center py-4 text-muted">{{ __('configuration.eco_empty') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($ecoles->hasPages())
                        <div class="mt-4">{{ $ecoles->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(Auth::user()->droit === 'SupAdmin')
        @include('configuration.partials.ecole-modal', [
            'modalId' => 'ecoleCreateModal',
            'title' => __('configuration.eco_modal_create_title'),
            'action' => route('configuration.ecoles.store'),
            'method' => 'POST',
            'ecole' => null,
        ])

        @foreach($ecoles as $ecole)
            @include('configuration.partials.ecole-modal', [
                'modalId' => 'ecoleEditModal'.$ecole->idEcole,
                'title' => __('configuration.eco_modal_edit_title'),
                'action' => route('configuration.ecoles.update', $ecole->idEcole),
                'method' => 'PUT',
                'ecole' => $ecole,
            ])
        @endforeach
    @endif
@endsection

@push('scripts')
    @php
        $i18nSuppressionEcole = [
                    'titre' => __('configuration.eco_suppression_titre'),
                    'irreversible' => __('configuration.eco_suppression_irreversible'),
                    'intro' => __('configuration.eco_suppression_intro'),
                    'vide' => __('configuration.eco_suppression_vide'),
                    'conserve' => __('configuration.eco_suppression_conserve'),
                    'retaper' => __('configuration.eco_suppression_retaper', ['nom' => '__NOM__']),
                    'bouton' => __('configuration.eco_suppression_bouton'),
                    'annuler' => __('configuration.annuler'),
                    'incorrect' => __('configuration.eco_suppression_nom_incorrect'),
                    'chargement' => __('configuration.eco_suppression_chargement'),
        ];
    @endphp
    <script>
        // Suppression d'une école : fenêtre SweetAlert avec l'inventaire de ce qui
        // disparaît et le nom de l'école à retaper (vérifié aussi par le serveur).
        document.querySelectorAll('.js-supprimer-ecole').forEach(function (formulaire) {
            formulaire.addEventListener('submit', function (event) {
                if (formulaire.dataset.confirme === '1') return;
                event.preventDefault();
                const i18n = @json($i18nSuppressionEcole);
                const echapper = (texte) => String(texte).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

                fetch(formulaire.dataset.inventaire, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then((reponse) => { if (!reponse.ok) throw new Error(reponse.status); return reponse.json(); })
                    .then(function (donnees) {
                        const lignes = Object.entries(donnees.inventaire || {})
                            .map(([libelle, nombre]) => '<tr><td class="text-start">' + echapper(libelle) + '</td><td class="text-end fw-bold">' + Number(nombre).toLocaleString('fr-FR') + '</td></tr>')
                            .join('');
                        const html =
                            '<p class="text-danger fw-semibold mb-2">' + echapper(i18n.irreversible) + '</p>' +
                            (lignes
                                ? '<p class="text-start mb-1">' + echapper(i18n.intro) + '</p><table class="table table-sm table-bordered mb-2">' + lignes + '</table>'
                                : '<p class="text-muted">' + echapper(i18n.vide) + '</p>') +
                            '<p class="small text-muted text-start mb-2">' + echapper(i18n.conserve) + '</p>' +
                            '<p class="text-start mb-0">' + echapper(i18n.retaper).replace('__NOM__', '<strong>' + echapper(donnees.nom) + '</strong>') + '</p>';

                        if (!window.Swal) {
                            const saisi = prompt(i18n.retaper.replace('__NOM__', donnees.nom));
                            if (saisi === null) return;
                            formulaire.querySelector('[name="confirmation"]').value = saisi;
                            formulaire.dataset.confirme = '1';
                            formulaire.submit();
                            return;
                        }

                        Swal.fire({
                            title: i18n.titre + ' : ' + donnees.nom,
                            icon: 'warning',
                            html: html,
                            input: 'text',
                            inputAttributes: { autocomplete: 'off', autocapitalize: 'off' },
                            showCancelButton: true,
                            confirmButtonText: i18n.bouton,
                            cancelButtonText: i18n.annuler,
                            confirmButtonColor: '#dc3545',
                            cancelButtonColor: '#6c757d',
                            reverseButtons: true,
                            focusCancel: true,
                            preConfirm: function (saisi) {
                                if ((saisi || '').trim().toLowerCase() !== String(donnees.nom).trim().toLowerCase()) {
                                    Swal.showValidationMessage(i18n.incorrect);
                                    return false;
                                }
                                return saisi;
                            },
                        }).then(function (resultat) {
                            if (!resultat.isConfirmed) return;
                            formulaire.querySelector('[name="confirmation"]').value = resultat.value;
                            formulaire.dataset.confirme = '1';
                            formulaire.submit();
                        });
                    })
                    .catch(function () {
                        if (window.Swal) Swal.fire({ icon: 'error', title: i18n.titre, text: i18n.chargement });
                        else alert(i18n.chargement);
                    });
            });
        });
    </script>
@endpush
