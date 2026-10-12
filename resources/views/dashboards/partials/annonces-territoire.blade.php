{{-- Annonces de l'académie (DAE) ou du CAP (DCAP) : publication + liste. --}}
<div class="card theme-card shadow-sm">
    <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-megaphone me-2"></i>{{ $titreAnnonces }}</h5>
        <button type="button" class="btn btn-sm text-white" style="background-color: var(--theme-accent);" data-bs-toggle="modal" data-bs-target="#annonceTerritoireModal">
            <i class="bi bi-plus-lg me-1"></i>Publier une annonce
        </button>
    </div>
    <div class="card-body">
        @forelse($annonces as $annonce)
            <div class="border-bottom py-3">
                <div class="d-flex justify-content-between gap-2 flex-wrap">
                    <div>
                        <h6 class="fw-bold mb-1">
                            @if(($annonce->type_annonce ?? null) === 'urgence')<span class="badge bg-danger me-1">Urgent</span>@endif
                            {{ $annonce->titre }}
                        </h6>
                        <div class="small text-muted">
                            {{ $annonce->date_publication ? \Carbon\Carbon::parse($annonce->date_publication)->format('d/m/Y H:i') : '' }}
                            @if($annonce->auteur) · {{ $annonce->auteur }} @endif
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <span class="badge bg-light text-dark border">{{ $cibles[$annonce->public_cible] ?? $annonce->public_cible }}</span>
                        <form action="{{ route('territoire.annonces.destroy', $annonce->id_annonce) }}" method="POST" class="js-supprimer-annonce" data-titre="{{ $annonce->titre }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light" title="Supprimer"><i class="bi bi-trash text-danger"></i></button>
                        </form>
                    </div>
                </div>
                <p class="mb-1 mt-2" style="white-space: pre-line;">{{ $annonce->contenu }}</p>
                @if($annonce->fichier_joint)
                    <a href="{{ asset($annonce->fichier_joint) }}" target="_blank" rel="noopener" class="small"><i class="bi bi-paperclip"></i> Pièce jointe</a>
                @endif
            </div>
        @empty
            <p class="text-muted mb-0">Aucune annonce publiée pour l'instant.</p>
        @endforelse
    </div>
</div>

<div class="modal fade" id="annonceTerritoireModal" tabindex="-1" aria-labelledby="annonceTerritoireTitre" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('territoire.annonces.store') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header theme-header">
                <h5 class="modal-title fw-bold" id="annonceTerritoireTitre"><i class="bi bi-megaphone me-2"></i>Nouvelle annonce</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold" for="annonce_titre">Titre <span class="text-danger">*</span></label>
                    <input type="text" id="annonce_titre" name="titre" class="form-control" maxlength="255" required value="{{ old('titre') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="annonce_public">Destinataires <span class="text-danger">*</span></label>
                    <select id="annonce_public" name="public_cible" class="form-select" required>
                        @foreach($cibles as $valeur => $libelle)
                            <option value="{{ $valeur }}" @selected(old('public_cible', 'tous') === $valeur)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="annonce_type">Type</label>
                    <select id="annonce_type" name="type_annonce" class="form-select">
                        <option value="information">Information</option>
                        <option value="pedagogique">Pédagogique</option>
                        <option value="urgence">Urgent</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="annonce_contenu">Message <span class="text-danger">*</span></label>
                    <textarea id="annonce_contenu" name="contenu" rows="5" class="form-control" maxlength="5000" required>{{ old('contenu') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="annonce_fichier">Pièce jointe (facultative)</label>
                    <input type="file" id="annonce_fichier" name="fichier" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt">
                    <div class="form-text">PDF, Word, Excel, image ou texte, 10 Mo au maximum.</div>
                </div>
                <div class="col-12">
                    <div class="alert alert-info small mb-0">{{ $aideDiffusion }}</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn text-white" style="background-color: var(--theme-accent);"><i class="bi bi-send me-1"></i>Publier</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        document.querySelectorAll('.js-supprimer-annonce').forEach(function (formulaire) {
            formulaire.addEventListener('submit', function (event) {
                event.preventDefault();
                const titre = 'Supprimer l’annonce « ' + (formulaire.dataset.titre || '') + ' » ?';
                if (!window.Swal) { if (confirm(titre)) formulaire.submit(); return; }
                Swal.fire({ title: titre, text: 'Elle ne sera plus visible par les écoles.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Supprimer', cancelButtonText: 'Annuler', confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d' })
                    .then(function (r) { if (r.isConfirmed) formulaire.submit(); });
            });
        });
        @if($errors->any())
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('annonceTerritoireModal');
                if (modal && window.bootstrap) new bootstrap.Modal(modal).show();
            });
        @endif
    </script>
@endpush
