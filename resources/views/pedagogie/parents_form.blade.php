@extends('layouts.app')

@section('content')
    @php
        $isEdit = $mode === 'edit';
        $action = $isEdit ? route('pedagogie.parents.update', $parent->id_parent) : route('pedagogie.parents.store');
        $liensMap = [
            'Père' => __('parents.lien_pere'),
            'Mère' => __('parents.lien_mere'),
            'Frère' => __('parents.lien_frere'),
            'Sœur' => __('parents.lien_soeur'),
            'Tuteur' => __('parents.lien_tuteur'),
            'Tutrice' => __('parents.lien_tutrice'),
            'Autre' => __('parents.lien_autre'),
        ];
        $elevesForPicker = $eleves->map(fn ($e) => [
            'id'       => $e->id_eleve,
            'matricule'=> $e->matricule,
            'nom'      => trim($e->prenom_eleve . ' ' . $e->nom_eleve),
            'classe'   => $e->classe->nom_classe ?? __('parents.th_classe'),
            'id_classe'=> $e->id_classe,
        ])->values();
        $classesForPicker = $classes->map(fn ($c) => ['id' => $c->id_classe, 'nom' => $c->nom_classe])->values();
        $parentsFormI18n = [
            'countStudents' => __('parents.count_students'),
            'lienOui' => __('parents.informer_oui'),
            'lienNon' => __('parents.informer_non'),
            'remove' => __('parents.remove'),
            'phoneErrorDigits' => __('parents.phone_error_digits'),
            'phoneErrorPrefix' => __('parents.phone_error_prefix'),
            'alertNoStudent' => __('parents.alert_no_student'),
        ];
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('eleves.title_parents') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pedagogie.parents') }}">{{ __('parents.parents_link') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? __('parents.breadcrumb_edit') : __('parents.breadcrumb_add') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ $action }}" id="parent-form">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- ── Informations du parent ─────────────────────────────────────── --}}
        <div class="card theme-card shadow-sm mb-3">
            <div class="card-header">
                <h5 class="mb-0 fw-bold">{{ $isEdit ? __('parents.edit_title') : __('parents.add_title') }}</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('parents.full_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="nom_prenom_parent" class="form-control rounded-3"
                               value="{{ old('nom_prenom_parent', $parent->nom_prenom_parent) }}"
                               placeholder="{{ __('parents.full_name_placeholder') }}" required>
                    </div>

                    {{-- Téléphone Mali ──────────────────────────────────────── --}}
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">
                            {{ __('parents.phone') }} <span class="text-danger">*</span>
                            <span class="text-muted fw-normal ms-1" style="font-size:.75rem;">{{ __('parents.phone_format_mali') }}</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold" style="background:#f8f9fa;font-size:.9rem;">
                                <img src="https://flagcdn.com/w20/ml.png" alt="ML" style="height:14px;margin-right:5px;">+223
                            </span>
                            <input type="tel" name="telephone_parent" id="telephone_parent"
                                   class="form-control rounded-end"
                                   value="{{ old('telephone_parent', $parent->telephone_parent) }}"
                                   placeholder="{{ __('parents.phone_placeholder') }}"
                                   maxlength="20"
                                   autocomplete="tel"
                                   required>
                        </div>
                        <div id="tel-feedback" class="invalid-feedback" style="display:none;"></div>
                        <small id="tel-hint" class="text-muted" style="font-size:.75rem;">
                            {{ __('parents.phone_hint') }}
                        </small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('parents.email') }}</label>
                        <input type="email" name="email_parent" class="form-control rounded-3"
                               value="{{ old('email_parent', $parent->email_parent) }}"
                               placeholder="{{ __('parents.email_placeholder') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('parents.genre') }}</label>
                        <select name="genre" class="form-select rounded-3">
                            <option value="">{{ __('parents.genre_non_renseigne') }}</option>
                            <option value="Féminin"  @selected(in_array(old('genre', $parent->genre), ['Féminin','Feminin']))>{{ __('eleves.genre_feminin') }}</option>
                            <option value="Masculin" @selected(old('genre', $parent->genre) === 'Masculin')>{{ __('eleves.genre_masculin') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Élèves concernés ───────────────────────────────────────────── --}}
        <div class="card theme-card shadow-sm">
            <div class="card-header d-flex align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold">{{ __('parents.concerned_students') }}</h5>
                <span class="badge bg-light text-primary ms-auto" id="selected-count">{{ __('parents.count_students', ['count' => $selectedRows->count()]) }}</span>
            </div>
            <div class="card-body p-4">

                {{-- Filtres + picker ────────────────────────────────────────── --}}
                <div class="row g-3 align-items-end mb-3">
                    {{-- Filtre par classe --}}
                    <div class="col-md-3">
                        <label class="form-label" for="classe_filter">
                            <i class="bi bi-filter me-1"></i>{{ __('parents.filter_by_classe') }}
                        </label>
                        <select id="classe_filter" class="form-select">
                            <option value="">{{ __('parents.all_classes') }}</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id_classe }}">{{ $classe->nom_classe }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Recherche texte --}}
                    <div class="col-md-3">
                        <label class="form-label" for="eleve_filter">
                            <i class="bi bi-search me-1"></i>{{ __('parents.search_student') }}
                        </label>
                        <input type="text" id="eleve_filter" class="form-control" placeholder="{{ __('parents.search_student_placeholder') }}">
                    </div>

                    {{-- Picker --}}
                    <div class="col-md-6">
                        <label class="form-label" for="eleve_picker">
                            {{ __('parents.choose_student_label') }}
                            <span class="text-muted fw-normal ms-1" style="font-size:.75rem;">{{ __('parents.choose_student_hint') }}</span>
                        </label>
                        <select id="eleve_picker" class="form-select">
                            <option value="">{{ __('parents.choose_student_default') }}</option>
                            @foreach($eleves as $eleve)
                                <option value="{{ $eleve->id_eleve }}"
                                    data-classe="{{ $eleve->id_classe }}"
                                    data-search="{{ strtolower($eleve->matricule . ' ' . $eleve->prenom_eleve . ' ' . $eleve->nom_eleve . ' ' . ($eleve->classe->nom_classe ?? '')) }}">
                                    {{ $eleve->matricule }} — {{ $eleve->nom_eleve }} {{ $eleve->prenom_eleve }}
                                    ({{ $eleve->classe->nom_classe ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="alert alert-info border-0 border-start border-info border-4 py-2" style="font-size:.85rem;">
                    <i class="bi bi-info-circle me-1"></i>
                    {!! __('parents.info_one_parent') !!}
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('parents.th_matricule') }}</th>
                                <th>{{ __('parents.th_eleve') }}</th>
                                <th>{{ __('parents.th_classe') }}</th>
                                <th style="min-width:160px;">{{ __('parents.th_lien') }}</th>
                                <th style="min-width:140px;">{{ __('parents.th_informer') }}</th>
                                <th class="text-center" style="width:80px;">{{ __('parents.th_action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="selected-eleves">
                            @foreach($selectedRows as $row)
                                <tr data-eleve-id="{{ $row['id_eleve'] }}">
                                    <td><span class="badge bg-light text-dark font-monospace">{{ $row['matricule'] }}</span></td>
                                    <td>
                                        <span class="fw-bold">{{ $row['nom'] }}</span>
                                        <input type="hidden" name="id_eleve[]" value="{{ $row['id_eleve'] }}">
                                    </td>
                                    <td>{{ $row['classe'] }}</td>
                                    <td>
                                        <select name="lien_parent[]" class="form-select" required>
                                            @foreach($liensMap as $value => $label)
                                                <option value="{{ $value }}" @selected($row['lien_parent'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="informer[]" class="form-select" required>
                                            <option value="Oui" @selected($row['informer'] === 'Oui')>{{ __('parents.informer_oui') }}</option>
                                            <option value="Non" @selected($row['informer'] === 'Non')>{{ __('parents.informer_non') }}</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-light btn-sm p-2 remove-row" title="{{ __('parents.remove') }}">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tbody id="empty-selected" @if($selectedRows->isNotEmpty()) style="display:none;" @endif>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    {{ __('parents.no_student_attached') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between flex-wrap gap-2 mt-4">
                    <a href="{{ route('pedagogie.parents') }}" class="btn btn-light px-4">
                        <i class="bi bi-arrow-left me-2"></i>{{ __('parents.back') }}
                    </a>
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="bi bi-check2-circle me-2"></i>{{ $isEdit ? __('parents.save') : __('parents.validate') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const eleves       = @json($elevesForPicker);
    const liensMap      = @json($liensMap);
    const i18n          = @json($parentsFormI18n);
    const picker       = document.getElementById('eleve_picker');
    const classeFilter = document.getElementById('classe_filter');
    const textFilter   = document.getElementById('eleve_filter');
    const tbody        = document.getElementById('selected-eleves');
    const emptyRow     = document.getElementById('empty-selected');
    const countBadge   = document.getElementById('selected-count');
    const telInput     = document.getElementById('telephone_parent');
    const telFeedback  = document.getElementById('tel-feedback');

    // ── Téléphone Mali ──────────────────────────────────────────────────────
    function validateMaliPhone(raw) {
        let v = raw.replace(/[\s\-\.]/g, '');
        if (v.startsWith('+223'))  v = v.slice(4);
        if (v.startsWith('00223')) v = v.slice(5);
        if (!/^[0-9]{8}$/.test(v)) return i18n.phoneErrorDigits;
        if (parseInt(v[0]) < 2)    return i18n.phoneErrorPrefix;
        return null;
    }

    function formatMaliDisplay(raw) {
        let v = raw.replace(/[\s\-\.]/g, '');
        if (v.startsWith('+223'))  v = v.slice(4);
        if (v.startsWith('00223')) v = v.slice(5);
        if (v.length === 8) return v.replace(/(\d{2})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4');
        return raw;
    }

    if (telInput) {
        telInput.addEventListener('input', function () {
            const err = validateMaliPhone(this.value);
            if (err) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                telFeedback.textContent = err;
                telFeedback.style.display = 'block';
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                telFeedback.style.display = 'none';
            }
        });

        telInput.addEventListener('blur', function () {
            if (!validateMaliPhone(this.value)) {
                this.value = formatMaliDisplay(this.value);
            }
        });
    }

    // ── Picker helpers ───────────────────────────────────────────────────────
    function escapeHtml(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }

    function refreshState() {
        const count = tbody.querySelectorAll('tr[data-eleve-id]').length;
        countBadge.textContent = i18n.countStudents.replace(':count', count);
        emptyRow.style.display = count > 0 ? 'none' : '';
    }

    function lienOptions() {
        return Object.entries(liensMap).map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`).join('');
    }

    function applyPickerFilters() {
        const classeId  = classeFilter.value;
        const textVal   = textFilter.value.trim().toLowerCase();
        Array.from(picker.options).forEach(function (opt) {
            if (!opt.value) return;
            const matchClasse = !classeId  || opt.dataset.classe === classeId;
            const matchText   = !textVal   || opt.dataset.search.includes(textVal);
            opt.hidden = !(matchClasse && matchText);
        });
    }

    classeFilter.addEventListener('change', applyPickerFilters);
    textFilter.addEventListener('input',   applyPickerFilters);

    picker.addEventListener('change', function () {
        const id = Number(this.value);
        if (!id || tbody.querySelector(`[data-eleve-id="${id}"]`)) {
            this.value = '';
            return;
        }
        const eleve = eleves.find(e => Number(e.id) === id);
        if (!eleve) return;

        tbody.insertAdjacentHTML('beforeend',
            `<tr data-eleve-id="${eleve.id}">
                <td><span class="badge bg-light text-dark font-monospace">${escapeHtml(eleve.matricule)}</span></td>
                <td><span class="fw-bold">${escapeHtml(eleve.nom)}</span><input type="hidden" name="id_eleve[]" value="${eleve.id}"></td>
                <td>${escapeHtml(eleve.classe)}</td>
                <td><select name="lien_parent[]" class="form-select" required>${lienOptions()}</select></td>
                <td><select name="informer[]" class="form-select" required>
                    <option value="Oui">${escapeHtml(i18n.lienOui)}</option><option value="Non">${escapeHtml(i18n.lienNon)}</option>
                </select></td>
                <td class="text-center">
                    <button type="button" class="btn btn-light btn-sm p-2 remove-row" title="${escapeHtml(i18n.remove)}">
                        <i class="bi bi-trash text-danger"></i>
                    </button>
                </td>
            </tr>`
        );
        this.value = '';
        refreshState();
    });

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-row');
        if (btn) { btn.closest('tr').remove(); refreshState(); }
    });

    document.getElementById('parent-form').addEventListener('submit', function (e) {
        // Block if phone invalid
        if (telInput && validateMaliPhone(telInput.value)) {
            e.preventDefault();
            telInput.focus();
            telInput.classList.add('is-invalid');
            return;
        }
        // Block if no student
        if (!tbody.querySelector('tr[data-eleve-id]')) {
            e.preventDefault();
            alert(i18n.alertNoStudent);
        }
    });
});
</script>
@endpush
