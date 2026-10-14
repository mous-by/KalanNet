{{-- Administrateur / gestionnaire : l'évaluation est programmée au nom d'un enseignant. --}}
@if($choisirEnseignant ?? false)
    <div class="col-md-3">
        <label class="form-label" for="id_enseignant">Enseignant</label>
        <select name="id_enseignant" id="id_enseignant" class="form-select @error('id_enseignant') is-invalid @enderror" data-selected="{{ $selectionne }}">
            <option value="">Enseignant de la matière</option>
            @foreach($enseignants as $enseignant)
                <option value="{{ $enseignant->id_enseignant }}" @selected((string) $selectionne === (string) $enseignant->id_enseignant)>{{ $enseignant->nom_prenom_enseignant }}</option>
            @endforeach
        </select>
        @error('id_enseignant')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Proposé d'après la matière de la classe.</small>
    </div>
@endif
