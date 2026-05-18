<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ $action }}" method="POST">
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif
                <div class="modal-header theme-header">
                    <h5 class="modal-title fw-bold">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Académie</label>
                        <select name="id_academie" class="form-select" required>
                            <option value="">Sélectionner</option>
                            @foreach($academies as $academie)
                                <option value="{{ $academie->id_academie }}" @selected(old('id_academie', $cap->id_academie ?? null) == $academie->id_academie)>
                                    {{ $academie->nom_academie }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nom du CAP</label>
                        <input type="text" name="nom_cap" class="form-control" value="{{ old('nom_cap', $cap->nom_cap ?? '') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code_cap" class="form-control" value="{{ old('code_cap', $cap->code_cap ?? '') }}" required>
                    </div>
                    <div>
                        <label class="form-label">Localité</label>
                        <input type="text" name="localite_cap" class="form-control" value="{{ old('localite_cap', $cap->localite_cap ?? '') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
