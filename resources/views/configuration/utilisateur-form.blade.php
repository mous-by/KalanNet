@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.utilisateurs') }}">Utilisateurs</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Enregistrement</li>
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
                <div class="card-header theme-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold"><i class="bx bx-user-plus me-2"></i>Enregistrement de l'utilisateur</h5>
                    <a href="{{ route('configuration.utilisateurs') }}" class="btn btn-light px-4">
                        <i class="bx bx-arrow-back me-2"></i>Retour
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('configuration.utilisateurs.store') }}">
                        @csrf

                        <div class="mb-4">
                            <h6 class="text-center fw-bold">Type de Utilisateur</h6>
                            <div class="border rounded-3 d-flex justify-content-center p-3 flex-wrap gap-3">
                                @foreach([
                                    1 => 'Administrateurs',
                                    0 => 'Enseignants',
                                    2 => 'Parents',
                                    3 => 'DAE',
                                    4 => 'DCAP',
                                ] as $value => $label)
                                    <div class="form-check">
                                        <input type="radio" name="type_utilisateur" id="type_{{ $value }}" class="form-check-input user-type-radio" value="{{ $value }}" @checked((int) old('type_utilisateur', 1) === $value)>
                                        <label class="form-check-label" for="type_{{ $value }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4 js-field js-manual">
                                <label class="form-label">Nom & Prénom <span class="text-danger">*</span></label>
                                <input type="text" name="nomPrenom" class="form-control" value="{{ old('nomPrenom') }}" placeholder="Nom & Prénom">
                            </div>

                            <div class="col-md-4 js-field js-enseignant d-none">
                                <label class="form-label">Enseignant <span class="text-danger">*</span></label>
                                <select name="id_enseignant" id="id_enseignant" class="form-select">
                                    <option value="">Choisissez un enseignant</option>
                                    @foreach($enseignants as $enseignant)
                                        <option value="{{ $enseignant->id_enseignant }}" data-email="{{ $enseignant->email_enseignant }}" data-contact="{{ $enseignant->telephone_enseignant }}" @selected(old('id_enseignant') == $enseignant->id_enseignant)>
                                            {{ strtoupper($enseignant->nom_prenom_enseignant) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 js-field js-parent d-none">
                                <label class="form-label">Parent <span class="text-danger">*</span></label>
                                <select name="id_parent" id="id_parent" class="form-select">
                                    <option value="">Choisissez un parent</option>
                                    @foreach($parents as $parent)
                                        <option value="{{ $parent->id_parent }}" data-email="{{ $parent->email_parent }}" data-contact="{{ $parent->telephone_parent }}" @selected(old('id_parent') == $parent->id_parent)>
                                            {{ strtoupper($parent->nom_prenom_parent) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 js-field js-manual">
                                <label class="form-label">E-mail <span class="text-danger">*</span></label>
                                <input type="email" id="email_utilisateurs" name="email" class="form-control" value="{{ old('email') }}" placeholder="E-mail">
                            </div>
                            <div class="col-md-4 js-field js-manual">
                                <label class="form-label">Contact <span class="text-danger">*</span></label>
                                <input type="text" id="contact_utilisateur" name="telephone" class="form-control" value="{{ old('telephone') }}" placeholder="Contact">
                            </div>
                            <div class="col-md-4 js-field js-manual">
                                <label class="form-label">Genre <span class="text-danger">*</span></label>
                                <select name="genre" class="form-select">
                                    <option value="feminin" @selected(old('genre') === 'feminin')>F</option>
                                    <option value="masculin" @selected(old('genre') === 'masculin')>M</option>
                                </select>
                            </div>
                            <div class="col-md-4 js-field js-manual">
                                <label class="form-label">Fonction</label>
                                <input type="text" name="fonction" id="fonction" class="form-control" value="{{ old('fonction') }}" placeholder="Fonction">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mot de passe</label>
                                <input type="password" name="pwd" class="form-control" minlength="4" placeholder="Laisser vide pour générer">
                                <small class="text-muted">4 caractères minimum.</small>
                            </div>

                            <div class="col-md-6 js-field js-dae d-none">
                                <label class="form-label">Académie <span class="text-danger">*</span></label>
                                <select name="id_academie" class="form-select">
                                    <option value="">Choisir une académie</option>
                                    @foreach($academies as $academie)
                                        <option value="{{ $academie->id_academie }}" @selected(old('id_academie') == $academie->id_academie)>{{ $academie->nom_academie }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 js-field js-dcap d-none">
                                <label class="form-label">CAP <span class="text-danger">*</span></label>
                                <select name="id_cap" class="form-select">
                                    <option value="">Choisir un CAP</option>
                                    @foreach($caps as $cap)
                                        <option value="{{ $cap->id_cap }}" @selected(old('id_cap') == $cap->id_cap)>{{ $cap->nom_cap }} - {{ $cap->academie->nom_academie ?? 'N/A' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 js-field js-admin">
                                <label class="form-label">École</label>
                                <select name="idEcole" class="form-select">
                                    <option value="">Choisir une école</option>
                                    @foreach($ecoles as $ecole)
                                        <option value="{{ $ecole->idEcole }}" @selected(old('idEcole') == $ecole->idEcole)>{{ $ecole->nomEcole }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 js-field js-admin">
                                <label class="form-label">Droit <span class="text-danger">*</span></label>
                                <select name="droit" class="form-select">
                                    <option value="">Choisir un droit</option>
                                    @if(Auth::user()->droit === 'SupAdmin')
                                        <option value="SupAdmin" @selected(old('droit') === 'SupAdmin')>SupAdmin</option>
                                        <option value="Admin" @selected(old('droit') === 'Admin')>Admin</option>
                                    @endif
                                    <option value="Gestionnaire" @selected(old('droit') === 'Gestionnaire')>Gestionnaire</option>
                                </select>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary px-5">Envoyer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const radios = document.querySelectorAll('.user-type-radio');
            const fields = document.querySelectorAll('.js-field');

            function setVisibility() {
                const type = Number(document.querySelector('.user-type-radio:checked')?.value || 1);
                fields.forEach((field) => field.classList.add('d-none'));

                if (type === 0) {
                    document.querySelectorAll('.js-enseignant').forEach((el) => el.classList.remove('d-none'));
                } else if (type === 2) {
                    document.querySelectorAll('.js-parent').forEach((el) => el.classList.remove('d-none'));
                } else {
                    document.querySelectorAll('.js-manual').forEach((el) => el.classList.remove('d-none'));
                    if (type === 3) {
                        document.querySelectorAll('.js-dae').forEach((el) => el.classList.remove('d-none'));
                        document.getElementById('fonction').value ||= 'DAE';
                    } else if (type === 4) {
                        document.querySelectorAll('.js-dcap').forEach((el) => el.classList.remove('d-none'));
                        document.getElementById('fonction').value ||= 'DCAP';
                    } else {
                        document.querySelectorAll('.js-admin').forEach((el) => el.classList.remove('d-none'));
                    }
                }
            }

            function fillFromSelect(selectId) {
                const select = document.getElementById(selectId);
                if (!select) return;
                select.addEventListener('change', function () {
                    const option = select.selectedOptions[0];
                    document.getElementById('email_utilisateurs').value = option?.dataset.email || '';
                    document.getElementById('contact_utilisateur').value = option?.dataset.contact || '';
                });
            }

            radios.forEach((radio) => radio.addEventListener('change', setVisibility));
            fillFromSelect('id_enseignant');
            fillFromSelect('id_parent');
            setVisibility();
        });
    </script>
@endsection
