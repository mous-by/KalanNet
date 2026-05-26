@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Communications</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active">Annonces</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('annonces_creation'))
            <div class="col-lg-4">
                <div class="card theme-card shadow-sm">
                    <div class="card-header theme-header">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-megaphone me-2"></i>Nouvelle publication</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('annonces.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Titre</label>
                                <input type="text" name="titre" class="form-control" value="{{ old('titre') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Public</label>
                                <select name="public_cible" class="form-select" required>
                                    <option value="tous" @selected(old('public_cible') === 'tous')>Tous</option>
                                    <option value="parents" @selected(old('public_cible') === 'parents')>Parents</option>
                                    <option value="enseignants" @selected(old('public_cible') === 'enseignants')>Enseignants</option>
                                    <option value="gestionnaires" @selected(old('public_cible') === 'gestionnaires')>Administration</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Statut</label>
                                <select name="statut_annonce" class="form-select" required>
                                    <option value="publie" @selected(old('statut_annonce') === 'publie')>Publier maintenant</option>
                                    <option value="brouillon" @selected(old('statut_annonce') === 'brouillon')>Brouillon</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Contenu</label>
                                <textarea name="contenu" class="form-control" rows="6" required>{{ old('contenu') }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label mb-0">Pièces jointes</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-file-row">
                                        <i class="bi bi-plus-lg me-1"></i>Ajouter
                                    </button>
                                </div>
                                <div id="announcement-files">
                                    <div class="file-row border rounded p-2 mb-2">
                                        <input type="text" name="titres_fichiers[]" class="form-control form-control-sm mb-2" placeholder="Titre du fichier">
                                        <input type="file" name="fichiers[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-send me-1"></i>Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="{{ auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('annonces_creation') ? 'col-lg-8' : 'col-12' }}">
            <div class="card theme-card shadow-sm">
                <div class="card-header theme-header">
                    <h5 class="mb-0 fw-bold">Publications</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Public</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($annonces as $annonce)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $annonce->titre }}</div>
                                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($annonce->contenu, 100) }}</div>
                                            @php($files = $filesByAnnouncement[$annonce->id_annonce] ?? collect())
                                            @if($files->isNotEmpty())
                                                <div class="small mt-1">
                                                    @foreach($files as $file)
                                                        <a href="{{ asset($file->nom_fichier) }}" target="_blank" class="d-inline-block me-2">
                                                            <i class="bi bi-paperclip me-1"></i>{{ $file->titre ?: ($file->nom_original ?: 'Fichier') }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @elseif($annonce->fichier_joint)
                                                <a href="{{ asset($annonce->fichier_joint) }}" target="_blank" class="small"><i class="bi bi-paperclip me-1"></i>Pièce jointe</a>
                                            @endif
                                        </td>
                                        <td>{{ ucfirst($annonce->public_cible) }}</td>
                                        <td>
                                            <span class="badge {{ ($annonce->statut_annonce ?? 'publie') === 'publie' ? 'bg-success' : (($annonce->statut_annonce ?? '') === 'archive' ? 'bg-secondary' : 'bg-warning text-dark') }}">
                                                {{ ucfirst($annonce->statut_annonce ?? 'publie') }}
                                            </span>
                                        </td>
                                        <td>{{ $annonce->date_publication ? \Carbon\Carbon::parse($annonce->date_publication)->format('d/m/Y H:i') : 'Non publiée' }}</td>
                                        <td class="text-end">
                                            @if(($annonce->statut_annonce ?? 'publie') !== 'publie' && (auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('annonces_creation')))
                                                <form action="{{ route('annonces.publish', $annonce->id_annonce) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-success">Publier</button>
                                                </form>
                                            @endif
                                            @if(($annonce->statut_annonce ?? 'publie') !== 'archive' && (auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('annonces_creation')))
                                                <form action="{{ route('annonces.archive', $annonce->id_annonce) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Archiver</button>
                                                </form>
                                            @endif
                                            @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('annonces_supprimer'))
                                                <form action="{{ route('annonces.destroy', $annonce->id_annonce) }}" method="POST" class="d-inline" data-confirm-delete data-confirm-title="Supprimer cette annonce ?" data-confirm-text="Cette publication ne sera plus visible.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Aucune annonce enregistrée.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($annonces, 'hasPages') && $annonces->hasPages())
                        {{ $annonces->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addFileRow = document.getElementById('add-file-row');
            const filesContainer = document.getElementById('announcement-files');
            if (addFileRow && filesContainer) {
                addFileRow.addEventListener('click', function () {
                    const row = document.createElement('div');
                    row.className = 'file-row border rounded p-2 mb-2 position-relative';
                    row.innerHTML = `
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-2" aria-label="Retirer"></button>
                        <input type="text" name="titres_fichiers[]" class="form-control form-control-sm mb-2 pe-4" placeholder="Titre du fichier">
                        <input type="file" name="fichiers[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                    `;
                    row.querySelector('.btn-close').addEventListener('click', () => row.remove());
                    filesContainer.appendChild(row);
                });
            }

            document.querySelectorAll('[data-confirm-delete]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    const title = form.dataset.confirmTitle || 'Confirmer ?';
                    const text = form.dataset.confirmText || '';
                    if (!window.Swal) {
                        if (confirm(title)) form.submit();
                        return;
                    }
                    Swal.fire({
                        title,
                        text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Oui, supprimer',
                        cancelButtonText: 'Annuler',
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                    }).then(result => {
                        if (result.isConfirmed) form.submit();
                    });
                });
            });
        });
    </script>
@endpush
