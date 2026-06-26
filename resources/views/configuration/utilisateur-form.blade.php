@extends('layouts.app')

@php
    $isEdit = isset($utilisateur);
    $selectedType = old('type_utilisateur', $selectedType ?? 1);
    $formAction = $isEdit
        ? route('configuration.utilisateurs.update', $utilisateur->idUtilisateur)
        : route('configuration.utilisateurs.store');
@endphp

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.utilisateurs') }}">Utilisateurs</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'Modification' : 'Enregistrement' }}</li>
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
                    <h5 class="mb-0 fw-bold">
                        <i class="bx {{ $isEdit ? 'bx-edit' : 'bx-user-plus' }} me-2"></i>{{ $isEdit ? "Modification de l'utilisateur" : "Enregistrement de l'utilisateur" }}
                    </h5>
                    <a href="{{ route('configuration.utilisateurs') }}" class="btn btn-light px-4">
                        <i class="bx bx-arrow-back me-2"></i>Retour
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ $formAction }}">
                        @csrf
                        @if($isEdit)
                            @method('PUT')
                            <input type="hidden" name="type_utilisateur" value="{{ $selectedType }}">
                            @if($selectedType == 0)
                                <input type="hidden" name="id_enseignant" value="{{ old('id_enseignant', $utilisateur->id_enseignant) }}">
                            @elseif($selectedType == 2)
                                <input type="hidden" name="id_parent" value="{{ old('id_parent', $utilisateur->id_parent) }}">
                            @endif
                        @endif

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
                                        <input type="radio" name="type_utilisateur" id="type_{{ $value }}" class="form-check-input user-type-radio" value="{{ $value }}" @checked((int) $selectedType === $value) @disabled($isEdit)>
                                        <label class="form-check-label" for="type_{{ $value }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4 js-field js-manual">
                                <label class="form-label">Nom & Prénom <span class="text-danger">*</span></label>
                                <input type="text" name="nomPrenom" class="form-control" value="{{ old('nomPrenom', $utilisateur->nomPrenom ?? '') }}" placeholder="Nom & Prénom">
                            </div>

                            <div class="col-md-4 js-field js-enseignant d-none">
                                <label class="form-label">Enseignant <span class="text-danger">*</span></label>
                                <select name="id_enseignant" id="id_enseignant" class="form-select" @disabled($isEdit)>
                                    <option value="">Choisissez un enseignant</option>
                                    @foreach($enseignants as $enseignant)
                                        <option value="{{ $enseignant->id_enseignant }}" data-email="{{ $enseignant->email_enseignant }}" data-contact="{{ $enseignant->telephone_enseignant }}" @selected(old('id_enseignant', $utilisateur->id_enseignant ?? null) == $enseignant->id_enseignant)>
                                            {{ strtoupper($enseignant->nom_prenom_enseignant) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 js-field js-parent d-none">
                                <label class="form-label">Parent <span class="text-danger">*</span></label>
                                <select name="id_parent" id="id_parent" class="form-select" @disabled($isEdit)>
                                    <option value="">Choisissez un parent</option>
                                    @foreach($parents as $parent)
                                        <option value="{{ $parent->id_parent }}" data-email="{{ $parent->email_parent }}" data-contact="{{ $parent->telephone_parent }}" @selected(old('id_parent', $utilisateur->id_parent ?? null) == $parent->id_parent)>
                                            {{ strtoupper($parent->nom_prenom_parent) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 js-field js-manual">
                                <label class="form-label">E-mail <span class="text-danger">*</span></label>
                                <input type="email" id="email_utilisateurs" name="email" class="form-control" value="{{ old('email', $utilisateur->email ?? '') }}" placeholder="E-mail">
                            </div>
                            <div class="col-md-4 js-field js-manual">
                                <label class="form-label">Contact <span class="text-danger">*</span></label>
                                <input type="text" id="contact_utilisateur" name="telephone" class="form-control" value="{{ old('telephone', $utilisateur->telephone ?? '') }}" placeholder="Contact">
                            </div>
                            <div class="col-md-4 js-field js-manual">
                                <label class="form-label">Genre <span class="text-danger">*</span></label>
                                <select name="genre" class="form-select">
                                    <option value="feminin" @selected(old('genre', $utilisateur->genre ?? '') === 'feminin')>F</option>
                                    <option value="masculin" @selected(old('genre', $utilisateur->genre ?? '') === 'masculin')>M</option>
                                </select>
                            </div>
                            <div class="col-md-4 js-field js-manual">
                                <label class="form-label">Fonction</label>
                                <input type="text" name="fonction" id="fonction" class="form-control" value="{{ old('fonction', $utilisateur->fonction ?? '') }}" placeholder="Fonction">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mot de passe</label>
                                <input type="password" name="pwd" class="form-control" minlength="4" placeholder="Laisser vide pour générer">
                                <small class="text-muted">{{ $isEdit ? 'Laisser vide pour conserver le mot de passe actuel.' : '4 caractères minimum.' }}</small>
                            </div>

                            <div class="col-md-6 js-field js-dae d-none">
                                <label class="form-label">Académie <span class="text-danger">*</span></label>
                                <select name="id_academie" class="form-select">
                                    <option value="">Choisir une académie</option>
                                    @foreach($academies as $academie)
                                        <option value="{{ $academie->id_academie }}" @selected(old('id_academie', $utilisateur->id_academie ?? null) == $academie->id_academie)>{{ $academie->nom_academie }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 js-field js-dcap d-none">
                                <label class="form-label">CAP <span class="text-danger">*</span></label>
                                <select name="id_cap" class="form-select">
                                    <option value="">Choisir un CAP</option>
                                    @foreach($caps as $cap)
                                        <option value="{{ $cap->id_cap }}" @selected(old('id_cap', $utilisateur->id_cap ?? null) == $cap->id_cap)>{{ $cap->nom_cap }} - {{ $cap->academie->nom_academie ?? 'N/A' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 js-field js-admin">
                                <label class="form-label">École</label>
                                <select name="idEcole" class="form-select">
                                    <option value="">Choisir une école</option>
                                    @foreach($ecoles as $ecole)
                                        <option value="{{ $ecole->idEcole }}" data-type="{{ $ecole->typeEcole }}" @selected(old('idEcole', $utilisateur->idEcole ?? session('idEcole')) == $ecole->idEcole)>{{ $ecole->nomEcole }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 js-field js-admin">
                                <label class="form-label">Droit <span class="text-danger">*</span></label>
                                <select name="droit" class="form-select">
                                    <option value="">Choisir un droit</option>
                                    @if(Auth::user()->droit === 'SupAdmin')
                                        <option value="SupAdmin" @selected(old('droit', $utilisateur->droit ?? '') === 'SupAdmin')>SupAdmin</option>
                                        <option value="Admin" @selected(old('droit', $utilisateur->droit ?? '') === 'Admin')>Admin</option>
                                    @endif
                                    <option value="Gestionnaire" @selected(old('droit', $utilisateur->droit ?? '') === 'Gestionnaire')>Gestionnaire</option>
                                </select>
                            </div>
                            <div class="col-12 js-field js-admin js-managed-orders d-none">
                                <label class="form-label fw-bold">Ordres d'enseignement à gérer</label>
                                <div class="border rounded-3 p-3">
                                    <div class="row g-2">
                                        @foreach($complexeOrders as $orderKey => $orderLabel)
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="managed_orders[]" value="{{ $orderKey }}" id="managed_order_{{ $orderKey }}" @checked(in_array($orderKey, old('managed_orders', $utilisateur->managed_orders ?? []), true))>
                                                    <label class="form-check-label" for="managed_order_{{ $orderKey }}">{{ $orderLabel }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <small class="text-muted d-block mt-2">Disponible seulement pour un gestionnaire d'une école de type Complexe Scolaire.</small>
                                    @error('managed_orders')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary px-5">{{ $isEdit ? 'Enregistrer' : 'Envoyer' }}</button>
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
            const schoolSelect = document.querySelector('select[name="idEcole"]');
            const droitSelect = document.querySelector('select[name="droit"]');
            const managedOrders = document.querySelector('.js-managed-orders');

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

                syncManagedOrders();
            }

            function syncManagedOrders() {
                if (!managedOrders || !schoolSelect || !droitSelect) return;
                const type = Number(document.querySelector('.user-type-radio:checked')?.value || 1);
                const selectedSchoolType = schoolSelect.selectedOptions[0]?.dataset.type || '';
                const visible = type === 1 && droitSelect.value === 'Gestionnaire' && selectedSchoolType === 'Complexe Scolaire';
                managedOrders.classList.toggle('d-none', !visible);
                if (!visible) {
                    managedOrders.querySelectorAll('input[type="checkbox"]').forEach((box) => {
                        box.checked = false;
                    });
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
            schoolSelect?.addEventListener('change', syncManagedOrders);
            droitSelect?.addEventListener('change', syncManagedOrders);
            fillFromSelect('id_enseignant');
            fillFromSelect('id_parent');
            setVisibility();
        });
    </script>
@endsection
