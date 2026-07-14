@extends('layouts.app')

@section('content')
    <style>
        .doc-intro { font-size: 1.05rem; color: var(--bs-secondary-color, #6c757d); margin-bottom: 1.5rem; }
        .doc-toc { background: color-mix(in srgb, var(--theme-accent, #2563eb) 6%, transparent); border: 1px solid color-mix(in srgb, var(--theme-accent, #2563eb) 25%, transparent); border-radius: 10px; padding: 1.25rem 1.5rem; margin-bottom: 2rem; }
        .doc-toc-title { font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.04em; margin-bottom: 0.5rem; color: var(--theme-accent, #2563eb); }
        .doc-toc ol { margin-bottom: 0; columns: 2; column-gap: 2rem; }
        .doc-toc a { text-decoration: none; }
        .doc-toc a:hover { text-decoration: underline; }
        .doc-tip, .doc-note { border-radius: 8px; padding: 1rem 1.25rem; margin: 1rem 0; border-left: 4px solid; }
        .doc-tip { background: rgba(34, 197, 94, 0.08); border-left-color: #22c55e; }
        .doc-note { background: rgba(234, 179, 8, 0.10); border-left-color: #eab308; }
        table.doc-catalogue { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        table.doc-catalogue th, table.doc-catalogue td { border: 1px solid var(--border-color, rgba(148, 163, 184, 0.4)); padding: 0.6rem 0.8rem; text-align: left; }
        table.doc-catalogue th { background: color-mix(in srgb, var(--theme-accent, #2563eb) 10%, transparent); font-weight: 700; }
        @media (max-width: 575.98px) { .doc-toc ol { columns: 1; } }
        .doc-shot { margin: 1.5rem 0; text-align: center; }
        .doc-shot img { max-width: 100%; height: auto; border-radius: 10px; border: 1px solid var(--border-color, rgba(148, 163, 184, 0.4)); box-shadow: 0 4px 16px rgba(0,0,0,0.12); }
        .doc-shot figcaption { margin-top: 0.6rem; font-size: 0.9rem; color: var(--bs-secondary-color, #6c757d); }
        .doc-shot-row { display: flex; flex-wrap: wrap; gap: 1.5rem; justify-content: center; }
        .doc-shot-row .doc-shot { flex: 1 1 320px; max-width: 420px; margin: 1.5rem 0; }
    </style>
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
                <h2 class="fw-bold" style="color: var(--theme-accent);">DOCUMENTATION UTILISATEUR <span style="color:#16a34a;">KAL</span><span style="color:#eab308;">AN</span><span style="color:#dc2626;">NET</span></h2>
                <p class="lead text-muted">Le guide complet pour maîtriser <span style="color:#16a34a;font-weight:700;">Kal</span><span style="color:#eab308;font-weight:700;">an</span><span style="color:#dc2626;font-weight:700;">Net</span> de A à Z</p>
            </div>

            @include('documentation.content')
            
        </div>
    </div>
@endsection
