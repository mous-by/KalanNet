<div class="mb-3">
    <label for="{{ $prefix }}nom_classe_officielle" class="form-label fw-bold">Nom de la classe officielle <span class="text-danger">*</span></label>
    <input type="text" id="{{ $prefix }}nom_classe_officielle" name="nom_classe_officielle" class="form-control" placeholder="Ex: 7eme année" required>
</div>
<div class="mb-3">
    <label for="{{ $prefix }}ordre_enseignement" class="form-label fw-bold">Ordre d'enseignement <span class="text-danger">*</span></label>
    <select id="{{ $prefix }}ordre_enseignement" name="ordre_enseignement" class="form-select" required>
        <option value="">Choisir...</option>
        @foreach($ordres as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
</div>
