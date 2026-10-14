@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('domaines.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pedagogie.matieres') }}">{{ __('matieres.title') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('domaines.title') }}</li>
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

    @if($peutModifier)
        <div class="mb-3 d-flex justify-content-end">
            <button type="button" class="btn px-4 theme-pill-active" data-bs-toggle="modal" data-bs-target="#domaineModal" data-action="{{ route('pedagogie.domaines.store') }}">
                <i class="bi bi-plus-lg me-2"></i>{{ __('domaines.add') }}
            </button>
        </div>
    @endif

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header">
            <p class="mb-0 small" style="max-width: 80ch">{{ __('domaines.intro') }}</p>
        </div>
        <div class="card-body">
            @php $groupes = $domaines->groupBy(fn ($d) => $d->partie ?? ''); @endphp
            @forelse(array_merge(array_keys($parties), ['']) as $partie)
                @continue(!$groupes->has($partie))
                <h6 class="fw-bold mt-3 mb-2 text-uppercase small text-muted">{{ $parties[$partie] ?? __('domaines.autres') }}</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('domaines.nom') }}</th>
                                <th class="text-end">{{ __('domaines.nom_ar') }}</th>
                                <th class="text-center" style="width: 150px">{{ __('domaines.matieres_classes') }}</th>
                                @if($peutModifier)<th class="text-center" style="width: 90px">{{ __('domaines.actions') }}</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groupes[$partie] as $domaine)
                                <tr>
                                    <td class="fw-semibold">{{ $domaine->nom }}</td>
                                    <td class="text-end" dir="rtl">{{ $domaine->nom_ar }}</td>
                                    <td class="text-center">{{ $utilisations[$domaine->id_domaine] ?? 0 }}</td>
                                    @if($peutModifier)
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a class="text-muted fs-5" href="#" data-bs-toggle="dropdown" aria-label="Actions">
                                                    <i class="bi bi-three-dots"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                                    @foreach(['haut' => ['bi-arrow-up', 'monter', $loop->first], 'bas' => ['bi-arrow-down', 'descendre', $loop->last]] as $sens => [$icone, $libelle, $desactive])
                                                        @unless($desactive)
                                                            <li>
                                                                <form method="POST" action="{{ route('pedagogie.domaines.deplacer', $domaine->id_domaine) }}">
                                                                    @csrf
                                                                    <input type="hidden" name="sens" value="{{ $sens }}">
                                                                    <button type="submit" class="dropdown-item py-2"><i class="bi {{ $icone }} text-secondary me-2"></i>{{ __('domaines.'.$libelle) }}</button>
                                                                </form>
                                                            </li>
                                                        @endunless
                                                    @endforeach
                                                    <li>
                                                        <a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#domaineModal"
                                                            data-action="{{ route('pedagogie.domaines.update', $domaine->id_domaine) }}"
                                                            data-nom="{{ $domaine->nom }}" data-nom-ar="{{ $domaine->nom_ar }}" data-partie="{{ $domaine->partie }}">
                                                            <i class="bi bi-pencil text-warning me-2"></i>{{ __('domaines.edit') }}
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('pedagogie.domaines.destroy', $domaine->id_domaine) }}" class="form-suppression-domaine">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item py-2 text-danger"><i class="bi bi-trash me-2"></i>{{ __('domaines.delete') }}</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
            @endforelse
            @if($domaines->isEmpty())
                <p class="text-muted mb-0">{{ __('domaines.aucun') }}</p>
            @endif
        </div>
    </div>

    @if($peutModifier)
        <div class="modal fade" id="domaineModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 shadow-lg" id="domaineForm">
                    @csrf
                    <input type="hidden" name="_method" value="POST" id="domaineMethod">
                    <div class="modal-header text-white" style="background-color: var(--theme-accent) !important;">
                        <h5 class="modal-title fw-bold" id="domaineModalTitre">{{ __('domaines.add') }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label" for="domaine_nom">{{ __('domaines.nom') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="domaine_nom" name="nom" maxlength="120" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="domaine_nom_ar">{{ __('domaines.nom_ar') }}</label>
                            <input type="text" class="form-control" id="domaine_nom_ar" name="nom_ar" maxlength="120" dir="rtl">
                        </div>
                        <div>
                            <label class="form-label" for="domaine_partie">{{ __('domaines.partie') }}</label>
                            <select class="form-select" id="domaine_partie" name="partie">
                                <option value="">{{ __('domaines.sans_partie') }}</option>
                                @foreach($parties as $code => $libelle)
                                    <option value="{{ $code }}">{{ $libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">{{ __('domaines.cancel') }}</button>
                        <button type="submit" class="btn text-white px-4 fw-semibold" style="background-color: var(--theme-accent) !important;">{{ __('domaines.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('domaineModal');
            if (modal) {
                modal.addEventListener('show.bs.modal', function (event) {
                    const bouton = event.relatedTarget;
                    const edition = bouton.dataset.nom !== undefined;
                    document.getElementById('domaineForm').action = bouton.dataset.action;
                    document.getElementById('domaineMethod').value = edition ? 'PUT' : 'POST';
                    document.getElementById('domaineModalTitre').textContent = edition ? @json(__('domaines.edit')) : @json(__('domaines.add'));
                    document.getElementById('domaine_nom').value = bouton.dataset.nom || '';
                    document.getElementById('domaine_nom_ar').value = bouton.dataset.nomAr || '';
                    document.getElementById('domaine_partie').value = bouton.dataset.partie || '';
                });
            }

            document.querySelectorAll('.form-suppression-domaine').forEach(function (formulaire) {
                formulaire.addEventListener('submit', function (event) {
                    event.preventDefault();
                    Swal.fire({
                        title: @json(__('domaines.confirm_delete')),
                        text: @json(__('domaines.confirm_delete_text')),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: @json(__('domaines.delete')),
                        cancelButtonText: @json(__('domaines.cancel')),
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        reverseButtons: true,
                        focusCancel: true,
                    }).then(function (resultat) {
                        if (resultat.isConfirmed) formulaire.submit();
                    });
                });
            });
        });
    </script>
@endpush
