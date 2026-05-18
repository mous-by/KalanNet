@extends('layouts.app')

@section('content')
    @php
        $isEdit = $mode === 'edit';
        $isPublic = old('type_contrat', $enseignant->type_contrat_enseignant) === 'FONCTIONNAIRE';
        $action = $isEdit ? route('enseignants.update', $enseignant->id_enseignant) : route('enseignants.store');
        $contratsAutorises = $contratsAutorises ?? ['CDI' => 'CDI', 'CDD' => 'CDD', 'VCT' => 'VCT', 'FONCTIONNAIRE' => 'FONCTIONNAIRE'];
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Enseignants</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('enseignants.index') }}">Liste des enseignants</a></li>
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

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" id="enseignant-form">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="card theme-card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0 fw-bold">{{ $isEdit ? 'Modifier l’enseignant' : 'Nouvel enseignant' }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-lg-10">
                        <h6 class="fw-bold mb-3">Informations personnelles</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nom & Prénom <span class="text-danger">*</span></label>
                                <input name="nom_prenom" type="text" class="form-control" value="{{ old('nom_prenom', $enseignant->nom_prenom_enseignant) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Genre <span class="text-danger">*</span></label>
                                <select name="genre" class="form-select" required>
                                    <option value="">Choisir...</option>
                                    <option value="Feminin" @selected(old('genre', $enseignant->genre_enseignant) === 'Feminin')>Feminin</option>
                                    <option value="Masculin" @selected(old('genre', $enseignant->genre_enseignant) === 'Masculin')>Masculin</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input name="email" type="email" class="form-control" value="{{ old('email', $enseignant->email_enseignant) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                                <input name="telephone" type="text" class="form-control" maxlength="8" value="{{ old('telephone', $enseignant->telephone_enseignant) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date de naissance <span class="text-danger">*</span></label>
                                <input name="date_naissance" type="date" class="form-control" value="{{ old('date_naissance', $enseignant->date_naissance_enseignant) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Lieu de naissance <span class="text-danger">*</span></label>
                                <input name="lieu_naissance" type="text" class="form-control" value="{{ old('lieu_naissance', $enseignant->lieu_naissance_enseignant) }}" required>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3 mt-4">Informations professionnelles</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Diplôme <span class="text-danger">*</span></label>
                                <input name="diplome" type="text" class="form-control" value="{{ old('diplome', $enseignant->diplome_enseignant) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Type de contrat <span class="text-danger">*</span></label>
                                <select name="type_contrat" id="type_contrat" class="form-select" required>
                                    @foreach($contratsAutorises as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type_contrat', $enseignant->type_contrat_enseignant ?: array_key_first($contratsAutorises)) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 private-field">
                                <label class="form-label">Salaire</label>
                                <input name="salaire" type="number" class="form-control" min="0" step="0.01" value="{{ old('salaire', $enseignant->salaire_enseignant) }}">
                            </div>
                            <div class="col-md-4 cdd-field">
                                <label class="form-label">Durée du contrat</label>
                                <input name="duree_contrat" type="text" class="form-control" value="{{ old('duree_contrat', $enseignant->duree_contrat) }}">
                            </div>
                            <div class="col-md-4 vct-field">
                                <label class="form-label">Nombre d'heures</label>
                                <input name="nombre_heure" type="number" class="form-control" min="0" value="{{ old('nombre_heure', $enseignant->nombre_heure) }}">
                            </div>
                            <div class="col-md-4 vct-field">
                                <label class="form-label">Prix par heure</label>
                                <input name="prix_heure" type="number" class="form-control" min="0" step="0.01" value="{{ old('prix_heure', $enseignant->prix_heure) }}">
                            </div>
                        </div>

                        <div id="public-fields" class="mt-4">
                            <h6 class="fw-bold mb-3">Informations fonctionnaire</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Statut matrimonial</label>
                                    <select name="statut_matrimonial" class="form-select">
                                        <option value="">Choisir...</option>
                                        @foreach(['Célibataire', 'Marié(e)', 'Divorcé(e)', 'Veuf(ve)'] as $statut)
                                            <option value="{{ $statut }}" @selected(old('statut_matrimonial', $enseignant->statut_matrimonial) === $statut)>{{ $statut }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nombre d'enfants</label>
                                    <input name="nombre_enfants" type="number" class="form-control" min="0" value="{{ old('nombre_enfants', $enseignant->nombre_enfants ?? 0) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Spécialité</label>
                                    <input name="specialite" type="text" class="form-control" value="{{ old('specialite', $enseignant->specialite) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Service employeur</label>
                                    <input name="service_employeur" type="text" class="form-control" value="{{ old('service_employeur', $enseignant->service_employeur) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ancienneté</label>
                                    <input name="anciennete_annees" type="number" class="form-control" min="0" value="{{ old('anciennete_annees', $enseignant->anciennete_annees ?? 0) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nom & Prénom du père</label>
                                    <input name="pere_nom_prenom" type="text" class="form-control" value="{{ old('pere_nom_prenom', $enseignant->pere_nom_prenom) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nom & Prénom de la mère</label>
                                    <input name="mere_nom_prenom" type="text" class="form-control" value="{{ old('mere_nom_prenom', $enseignant->mere_nom_prenom) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-2">
                        <div class="card theme-card">
                            <div class="card-body">
                                <label class="form-label">Matricule</label>
                                <input type="text" name="matricule" id="matricule" class="form-control mb-3" value="{{ old('matricule', $enseignant->matricule) }}" placeholder="Auto si vide">
                                <input type="file" name="avatar" class="form-control d-none" id="avatarInput" accept="image/*">
                                <img id="avatarPreview" src="{{ asset($enseignant->avatar_enseignant ?: 'assets/images/avatars/avatar-1.png') }}" alt="Avatar" class="w-100 rounded" style="cursor:pointer; object-fit: cover; aspect-ratio: 1 / 1;" onclick="document.getElementById('avatarInput').click();">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('enseignants.index') }}" class="btn btn-light px-4">Retour</a>
                    <button type="submit" class="btn btn-primary px-4">{{ $isEdit ? 'Modifier' : 'Envoyer' }}</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typeContrat = document.getElementById('type_contrat');
            const avatarInput = document.getElementById('avatarInput');
            const avatarPreview = document.getElementById('avatarPreview');

            function toggleContractFields() {
                const type = typeContrat.value;
                document.querySelectorAll('.private-field').forEach(el => el.style.display = ['CDI', 'CDD'].includes(type) ? '' : 'none');
                document.querySelectorAll('.cdd-field').forEach(el => el.style.display = type === 'CDD' ? '' : 'none');
                document.querySelectorAll('.vct-field').forEach(el => el.style.display = type === 'VCT' ? '' : 'none');
                document.getElementById('public-fields').style.display = type === 'FONCTIONNAIRE' ? '' : 'none';
            }

            typeContrat.addEventListener('change', toggleContractFields);
            toggleContractFields();

            avatarInput.addEventListener('change', function (event) {
                const file = event.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = e => avatarPreview.src = e.target.result;
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
@endpush
