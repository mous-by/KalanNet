@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Compte</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mon profil</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
    @endif

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header">
            <h5 class="mb-0 fw-bold"><i class="bi bi-person-circle me-2"></i>{{ $user->nomPrenom }}</h5>
        </div>
        <div class="card-body p-4">
            <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-pane" type="button" role="tab" aria-controls="info-pane" aria-selected="true">
                        <i class="bi bi-person me-1"></i> Informations
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-pane" type="button" role="tab" aria-controls="password-pane" aria-selected="false">
                        <i class="bi bi-lock me-1"></i> Mot de passe
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-4" id="profileTabsContent">
                <div class="tab-pane fade show active" id="info-pane" role="tabpanel" aria-labelledby="info-tab">
                    <form action="{{ route('profile.update') }}" method="POST" class="col-12 col-lg-6">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="nomPrenom" class="form-label fw-semibold">Nom et prénom</label>
                            <input type="text" class="form-control" id="nomPrenom" name="nomPrenom" value="{{ old('nomPrenom', $user->nomPrenom) }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="telephone" class="form-label fw-semibold">Téléphone</label>
                            <input type="text" class="form-control" id="telephone" name="telephone" value="{{ old('telephone', $user->telephone) }}">
                        </div>
                        <button type="submit" class="btn text-white fw-semibold px-4" style="background-color: var(--theme-accent) !important;">
                            Enregistrer
                        </button>
                    </form>
                </div>

                <div class="tab-pane fade" id="password-pane" role="tabpanel" aria-labelledby="password-tab">
                    <form action="{{ route('profile.password.update') }}" method="POST" class="col-12 col-lg-6">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold">Mot de passe actuel</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="4">
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="4">
                        </div>
                        <button type="submit" class="btn text-white fw-semibold px-4" style="background-color: var(--theme-accent) !important;">
                            Mettre à jour le mot de passe
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
