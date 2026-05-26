@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Support</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Documentation Utilisateur</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto">
            <div class="btn-group">
                <a href="{{ route('documentation.download', 'pdf') }}" class="btn btn-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Télécharger en PDF
                </a>
                <a href="{{ route('documentation.download', 'word') }}" class="btn btn-primary">
                    <i class="bi bi-file-earmark-word me-1"></i> Télécharger en Word
                </a>
            </div>
        </div>
    </div>

    <div class="card theme-card shadow-sm mb-4">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold" style="color: var(--theme-accent);">DOCUMENTATION UTILISATEUR KALANNET</h2>
                <p class="lead text-muted">Le guide complet pour maîtriser KalanNet de A à Z</p>
            </div>

            @include('documentation.content')
            
        </div>
    </div>
@endsection
