@extends('layouts.app')

@section('content')
    @php
        $isEdit = $mode === 'edit';
        $action = $isEdit ? route('pedagogie.parents.update', $parent->id_parent) : route('pedagogie.parents.store');
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Parents</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pedagogie.parents') }}">Parents d'élèves</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'Modification' : 'Enregistrement' }}</li>
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

        <div class="card theme-card shadow-sm mb-4">
            <div class="card-header theme-header">
                <h5 class="mb-0 fw-bold">{{ $isEdit ? 'Modifier le parent' : 'Nouveau parent' }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nom & Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="nom_prenom_parent" class="form-control" value="{{ old('nom_prenom_parent', $parent->nom_prenom_parent) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                        <input type="text" name="telephone_parent" class="form-control" value="{{ old('telephone_parent', $parent->telephone_parent) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email_parent" class="form-control" value="{{ old('email_parent', $parent->email_parent) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Genre</label>
                        <select name="genre" class="form-select">
                            <option value="">Choisir...</option>
                            <option value="Feminin" @selected(old('genre', $parent->genre) === 'Feminin')>Feminin</option>
                            <option value="Masculin" @selected(old('genre', $parent->genre) === 'Masculin')>Masculin</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card theme-card shadow-sm">
            <div class="card-header theme-header">
                <h5 class="mb-0 fw-bold">Élèves rattachés</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label" for="eleve_picker">Ajouter un élève</label>
                        <select id="eleve_picker" class="form-select">
                            <option value="">Sélectionner un élève</option>
                            @foreach($eleves as $eleve)
                                <option value="{{ $eleve->id_eleve }}">
                                    {{ $eleve->matricule }} - {{ $eleve->prenom_eleve }} {{ $eleve->nom_eleve }} ({{ $eleve->classe->nom_classe ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Élève</th>
                                <th>Classe</th>
                                <th>Lien</th>
                                <th>Informer</th>
                                <th class="text-center" style="width: 90px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="selected-eleves">
                            @foreach($selectedRows as $row)
                                <tr data-eleve-id="{{ $row['id_eleve'] }}">
                                    <td>{{ $row['matricule'] }}</td>
                                    <td>
                                        <span class="fw-bold">{{ $row['nom'] }}</span>
                                        <input type="hidden" name="id_eleve[]" value="{{ $row['id_eleve'] }}">
                                    </td>
                                    <td>{{ $row['classe'] }}</td>
                                    <td>
                                        <select name="lien_parent[]" class="form-select" required>
                                            @foreach(['Père', 'Mère', 'Frère', 'Sœur', 'Tuteur', 'Tutrice', 'Autre'] as $lien)
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
                                        <button type="button" class="btn btn-light btn-sm remove-row"><i class="bi bi-trash text-danger"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('pedagogie.parents') }}" class="btn btn-light px-4">Retour</a>
                    <button type="submit" class="btn btn-primary px-4">{{ $isEdit ? 'Modifier' : 'Envoyer' }}</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const eleves = @json($eleves->map(fn ($eleve) => [
                'id' => $eleve->id_eleve,
                'matricule' => $eleve->matricule,
                'nom' => trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve),
                'classe' => $eleve->classe->nom_classe ?? 'N/A',
            ])->values());
            const picker = document.getElementById('eleve_picker');
            const tbody = document.getElementById('selected-eleves');

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, function (char) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
                });
            }

            function lienOptions() {
                return ['Père', 'Mère', 'Frère', 'Sœur', 'Tuteur', 'Tutrice', 'Autre'].map(function (item) {
                    return '<option value="' + escapeHtml(item) + '">' + escapeHtml(item) + '</option>';
                }).join('');
            }

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
                    '<td>' + escapeHtml(eleve.matricule) + '</td>' +
                    '<td><span class="fw-bold">' + escapeHtml(eleve.nom) + '</span><input type="hidden" name="id_eleve[]" value="' + eleve.id + '"></td>' +
                    '<td>' + escapeHtml(eleve.classe) + '</td>' +
                    '<td><select name="lien_parent[]" class="form-select" required>' + lienOptions() + '</select></td>' +
                    '<td><select name="informer[]" class="form-select" required><option value="Oui">Oui</option><option value="Non">Non</option></select></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-light btn-sm remove-row"><i class="bi bi-trash text-danger"></i></button></td>' +
                    '</tr>'
                );

                this.value = '';
            });

            tbody.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-row');
                if (button) button.closest('tr').remove();
            });
        });
    </script>
@endpush
