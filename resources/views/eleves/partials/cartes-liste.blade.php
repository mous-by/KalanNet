{{-- Liste des élèves de la page Cartes scolaires (rechargée sans recharger la page). --}}
@if(!$showList)
    <div class="empty-eleves-state text-center py-5">
        <div class="empty-eleves-icon mx-auto mb-3 d-flex align-items-center justify-content-center">
            <i class="bi bi-person-vcard fs-1"></i>
        </div>
        <h5 class="fw-bold mb-2">{{ __('cartes.empty_title') }}</h5>
        <p class="text-muted mb-0">{{ __('cartes.empty_desc') }}</p>
    </div>
@else
    <div class="card theme-card shadow-sm cards-students-card">
        <div class="card-header theme-header cards-students-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0 fw-bold">{{ __('cartes.available_title') }}</h5>
                <div class="small fw-semibold cards-students-count">{{ __('cartes.count_eleves', ['count' => number_format($eleves->count(), 0, ',', ' ')]) }}</div>
            </div>
            @if($eleves->isNotEmpty())
                <form action="{{ route('eleves.cartes.pdf') }}" method="POST" target="impression-cartes-cadre" id="cards-form" class="d-flex flex-wrap gap-2">
                    @csrf
                    <input type="hidden" name="id_classe" value="{{ request('id_classe') }}">
                    <input type="hidden" name="id_annee" value="{{ request('id_annee') }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="template" value="{{ $modeleActuel }}" data-card-config-target="template">
                    <input type="hidden" name="primary_color" value="{{ $couleur1 }}" data-card-config-target="primary_color">
                    <input type="hidden" name="secondary_color" value="{{ $couleur2 }}" data-card-config-target="secondary_color">
                    <input type="hidden" name="card_title" value="{{ request('card_title', __('cartes.card_title_default')) }}" data-card-config-target="card_title">
                    <input type="hidden" name="signature_label" value="{{ request('signature_label', __('cartes.signature_label_default')) }}" data-card-config-target="signature_label">
                    <input type="hidden" name="signature_image" value="{{ request('signature_image') }}" data-card-config-target="signature_image">
                    <input type="hidden" name="show_mali_flag" value="{{ request('show_mali_flag', '1') }}" data-card-config-target="show_mali_flag">
                    <input type="hidden" name="mali_flag_style" value="{{ request('mali_flag_style', 'corner') }}" data-card-config-target="mali_flag_style">
                    <input type="hidden" name="show_school_logo" value="{{ request('show_school_logo', '1') }}" data-card-config-target="show_school_logo">
                    <input type="hidden" name="show_qr" value="{{ request('show_qr', '1') }}" data-card-config-target="show_qr">
                    <input type="hidden" name="selected_eleves" id="cards-selected-input">
                    <input type="hidden" name="imprimer" value="1" id="cards-imprimer-input">
                    <button type="button" class="btn btn-outline-secondary" id="cards-download-button">
                        <i class="bi bi-download me-2"></i>{{ __('cartes.telecharger_pdf') }}
                    </button>
                    <button type="submit" class="btn btn-primary" id="cards-print-button">
                        <i class="bi bi-printer me-2"></i>{{ __('cartes.print_all_button') }}
                    </button>
                </form>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0 cards-students-table">
                <thead>
                <tr>
                    <th class="text-center" style="width: 48px;"><input type="checkbox" id="cards-check-all"></th>
                    <th>{{ __('parents.th_eleve') }}</th>
                    <th>{{ __('eleves.label_matricule') }}</th>
                    <th>{{ __('eleves.label_classe') }}</th>
                    <th>{{ __('eleves.label_genre') }}</th>
                    <th>{{ __('cartes.th_contact_parent') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($eleves as $eleve)
                    <tr>
                        <td class="text-center"><input type="checkbox" class="cards-check" value="{{ $eleve->id_eleve }}"></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center overflow-hidden" style="width: 38px; height: 38px;">
                                    @if($eleve->image)
                                        <img src="{{ asset($eleve->image) }}" alt="" class="w-100 h-100 object-fit-cover">
                                    @else
                                        <i class="bi bi-person text-muted"></i>
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-bold">{{ $eleve->prenom_eleve }} {{ $eleve->nom_eleve }}</div>
                                    <div class="small text-muted">{{ $eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : __('cartes.birth_not_specified') }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark font-monospace">{{ $eleve->matricule }}</span></td>
                        <td>{{ $eleve->classe?->nom_classe }}</td>
                        <td>{{ $eleve->genre_eleve }}</td>
                        <td>{{ $eleve->parents->first()?->telephone_parent ?? __('eleves.not_specified_m') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">{{ __('cartes.empty_table') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
