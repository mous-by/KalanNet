@extends('layouts.app')

@section('content')
    @php
        $isEdit = $mode === 'edit';
        $action = $isEdit ? route('pedagogie.parents.update', $parent->id_parent) : route('pedagogie.parents.store');
        $liens = ['Père', 'Mère', 'Frère', 'Sœur', 'Tuteur', 'Tutrice', 'Autre'];
        $elevesForPicker = $eleves->map(function ($eleve) {
            return [
                'id' => $eleve->id_eleve,
                'matricule' => $eleve->matricule,
                'nom' => trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve),
                'classe' => $eleve->classe->nom_classe ?? 'Classe non définie',
            ];
        })->values();
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Élèves & Parents</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pedagogie.parents') }}">Parents d'élèves</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'Modifier' : 'Ajouter' }}</li>
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
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="card theme-card shadow-sm mb-3">
            <div class="card-header">
                <h5 class="mb-0 fw-bold">{{ $isEdit ? 'Modifier le parent' : 'Ajouter un parent' }}</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Nom complet <span class="text-danger">*</span></label>
                        <input type="text" name="nom_prenom_parent" class="form-control rounded-3" value="{{ old('nom_prenom_parent', $parent->nom_prenom_parent) }}" placeholder="Prénom et nom du parent" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Téléphone <span class="text-danger">*</span></label>
                        <input type="text" name="telephone_parent" class="form-control rounded-3" value="{{ old('telephone_parent', $parent->telephone_parent) }}" placeholder="Numéro principal" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Email</label>
                        <input type="email" name="email_parent" class="form-control rounded-3" value="{{ old('email_parent', $parent->email_parent) }}" placeholder="Adresse email si disponible">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Genre</label>
                        <select name="genre" class="form-select rounded-3">
                            <option value="">Non renseigné</option>
                            <option value="Féminin" @selected(old('genre', $parent->genre) === 'Féminin' || old('genre', $parent->genre) === 'Feminin')>Féminin</option>
                            <option value="Masculin" @selected(old('genre', $parent->genre) === 'Masculin')>Masculin</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card theme-card shadow-sm">
            <div class="card-header d-flex align-items-center flex-wrap gap-2">
                <h5 class="mb-0 fw-bold">Élèves concernés</h5>
                <span class="badge bg-light text-primary ms-auto" id="selected-count">{{ $selectedRows->count() }} élève(s)</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-md-5">
                        <label class="form-label" for="eleve_filter">Chercher dans la liste</label>
                        <input type="text" id="eleve_filter" class="form-control" placeholder="Tapez nom, matricule ou classe...">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label" for="eleve_picker">Choisir l'élève à rattacher</label>
                        <select id="eleve_picker" class="form-select">
                            <option value="">Sélectionner un élève</option>
                            @foreach($eleves as $eleve)
                                <option value="{{ $eleve->id_eleve }}" data-search="{{ strtolower($eleve->matricule . ' ' . $eleve->prenom_eleve . ' ' . $eleve->nom_eleve . ' ' . ($eleve->classe->nom_classe ?? '')) }}">
                                    {{ $eleve->matricule }} - {{ $eleve->prenom_eleve }} {{ $eleve->nom_eleve }} ({{ $eleve->classe->nom_classe ?? 'Classe non définie' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="alert alert-info border-0 border-start border-info border-4">
                    Un même élève peut avoir plusieurs contacts enregistrés. Cochez “Informer” seulement pour les personnes qui doivent recevoir les informations de l'école.
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Élève</th>
                                <th>Classe</th>
                                <th style="min-width: 160px;">Lien avec l'élève</th>
                                <th style="min-width: 140px;">Informer</th>
                                <th class="text-center" style="width: 80px;">Action</th>
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
                                            @foreach($liens as $lien)
                                                <option value="{{ $lien }}" @selected($row['lien_parent'] === $lien)>{{ $lien }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="informer[]" class="form-select" required>
                                            <option value="Oui" @selected($row['informer'] === 'Oui')>Oui</option>
                                            <option value="Non" @selected($row['informer'] === 'Non')>Non</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-light btn-sm p-2 remove-row" title="Retirer">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tbody id="empty-selected" @if($selectedRows->isNotEmpty()) style="display: none;" @endif>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Aucun élève rattaché pour le moment. Choisissez un élève dans la liste ci-dessus.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between flex-wrap gap-2 mt-4">
                    <a href="{{ route('pedagogie.parents') }}" class="btn btn-light px-4">
                        <i class="bi bi-arrow-left me-2"></i>Retour
                    </a>
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="bi bi-check2-circle me-2"></i>{{ $isEdit ? 'Enregistrer' : 'Valider' }}
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const eleves = @json($elevesForPicker);
            const liens = @json($liens);
            const picker = document.getElementById('eleve_picker');
            const filter = document.getElementById('eleve_filter');
            const tbody = document.getElementById('selected-eleves');
            const emptyRow = document.getElementById('empty-selected');
            const selectedCount = document.getElementById('selected-count');

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, function (char) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
                });
            }

            function refreshState() {
                const count = tbody.querySelectorAll('tr[data-eleve-id]').length;
                selectedCount.textContent = count + ' élève(s)';
                emptyRow.style.display = count > 0 ? 'none' : '';
            }

            function lienOptions() {
                return liens.map(function (item) {
                    return '<option value="' + escapeHtml(item) + '">' + escapeHtml(item) + '</option>';
                }).join('');
            }

            filter.addEventListener('input', function () {
                const value = this.value.trim().toLowerCase();
                Array.from(picker.options).forEach(function (option) {
                    if (!option.value) return;
                    option.hidden = value && !option.dataset.search.includes(value);
                });
            });

            picker.addEventListener('change', function () {
                const id = Number(this.value);
                if (!id || tbody.querySelector('[data-eleve-id="' + id + '"]')) {
                    this.value = '';
                    return;
                }

                const eleve = eleves.find(item => Number(item.id) === id);
                if (!eleve) return;

                tbody.insertAdjacentHTML('beforeend',
                    '<tr data-eleve-id="' + eleve.id + '">' +
                    '<td><span class="badge bg-light text-dark font-monospace">' + escapeHtml(eleve.matricule) + '</span></td>' +
                    '<td><span class="fw-bold">' + escapeHtml(eleve.nom) + '</span><input type="hidden" name="id_eleve[]" value="' + eleve.id + '"></td>' +
                    '<td>' + escapeHtml(eleve.classe) + '</td>' +
                    '<td><select name="lien_parent[]" class="form-select" required>' + lienOptions() + '</select></td>' +
                    '<td><select name="informer[]" class="form-select" required><option value="Oui">Oui</option><option value="Non">Non</option></select></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-light btn-sm p-2 remove-row" title="Retirer"><i class="bi bi-trash text-danger"></i></button></td>' +
                    '</tr>'
                );

                this.value = '';
                refreshState();
            });

            tbody.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-row');
                if (button) {
                    button.closest('tr').remove();
                    refreshState();
                }
            });

            document.getElementById('parent-form').addEventListener('submit', function (event) {
                if (!tbody.querySelector('tr[data-eleve-id]')) {
                    event.preventDefault();
                    alert('Veuillez rattacher au moins un élève à ce parent.');
                }
            });
        });
    </script>
@endpush
