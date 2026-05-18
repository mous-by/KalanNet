<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ $action }}" method="POST" class="ecole-dynamic-form">
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif
                <div class="modal-header theme-header">
                    <h5 class="modal-title fw-bold">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom de l'école</label>
                            <input type="text" name="nomEcole" class="form-control" value="{{ old('nomEcole', $ecole->nomEcole ?? '') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select name="typeEcole" class="form-select js-ecole-type" required>
                                <option value="">Sélectionnez le type d'établissement</option>
                                @foreach(['Complexe Scolaire', 'Fondamentale I', 'Fondamentale II', 'Secondaire Generale', 'Secondaire Technique et Professionnel'] as $type)
                                    <option value="{{ $type }}" @selected(old('typeEcole', $ecole->typeEcole ?? '') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Statut</label>
                            <select name="statut" class="form-select" required>
                                <option value="public" @selected(old('statut', $ecole->statut ?? 'public') === 'public')>Public</option>
                                <option value="prive" @selected(old('statut', $ecole->statut ?? 'public') === 'prive')>Privé</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Académie</label>
                            <select name="id_academie" class="form-select js-academie-select" required>
                                <option value="">Sélectionner</option>
                                @foreach($academies as $academie)
                                    <option value="{{ $academie->id_academie }}" @selected(old('id_academie', $ecole->id_academie ?? null) == $academie->id_academie)>
                                        {{ $academie->nom_academie }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 js-cap-field">
                            <label class="form-label">CAP</label>
                            <select name="id_cap" class="form-select js-cap-select">
                                <option value="">Sélectionner</option>
                                @foreach($caps as $cap)
                                    <option value="{{ $cap->id_cap }}" data-academie="{{ $cap->id_academie }}" @selected(old('id_cap', $ecole->id_cap ?? null) == $cap->id_cap)>
                                        {{ $cap->nom_cap }} - {{ $cap->academie->nom_academie ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="telephone" class="form-control" value="{{ old('telephone', $ecole->telephone ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $ecole->email ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Notification SMS</label>
                            <select name="notification_sms" class="form-select">
                                <option value="0" @selected(old('notification_sms', $ecole->notification_sms ?? 0) == 0)>Non</option>
                                <option value="1" @selected(old('notification_sms', $ecole->notification_sms ?? 0) == 1)>Oui</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Adresse</label>
                            <textarea name="adresse" class="form-control" rows="2">{{ old('adresse', $ecole->adresse ?? '') }}</textarea>
                        </div>
                        <div class="col-md-6 js-type-field js-complexe">
                            <label class="form-label">Nom du Complexe Scolaire</label>
                            <input type="text" name="nomComplexe" class="form-control js-dynamic-input" value="{{ old('nomComplexe', $ecole->nomComplexe ?? '') }}" placeholder="Entrez le nom du complexe">
                        </div>
                        <div class="col-md-6 js-type-field js-fondamentale js-complexe">
                            <label class="form-label">Nom école fondamentale</label>
                            <input type="text" name="nomFondamental" class="form-control js-dynamic-input js-nom-fondamental" value="{{ old('nomFondamental', $ecole->nomFondamental ?? '') }}" placeholder="Entrez le nom de l'école fondamentale">
                        </div>
                        <div class="col-md-6 js-type-field js-secondaire-generale js-complexe">
                            <label class="form-label">Nom Lycée</label>
                            <input type="text" name="nomLycee" class="form-control js-dynamic-input" value="{{ old('nomLycee', $ecole->nomLycee ?? '') }}" placeholder="Entrez le nom du lycée">
                        </div>
                        <div class="col-md-6 js-type-field js-secondaire-technique js-complexe">
                            <label class="form-label">Nom Technique et Professionnelle</label>
                            <input type="text" name="nomProfessionnel" class="form-control js-dynamic-input" value="{{ old('nomProfessionnel', $ecole->nomProfessionnel ?? '') }}" placeholder="Entrez le nom de l'établissement professionnel">
                        </div>
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

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.ecole-dynamic-form').forEach((form) => {
                const typeSelect = form.querySelector('.js-ecole-type');
                const academieSelect = form.querySelector('.js-academie-select');
                const capField = form.querySelector('.js-cap-field');
                const capSelect = form.querySelector('.js-cap-select');
                const nomFondamental = form.querySelector('.js-nom-fondamental');
                const typeFields = Array.from(form.querySelectorAll('.js-type-field'));
                const capOptions = Array.from(capSelect?.querySelectorAll('option[data-academie]') || []);

                function selectedType() {
                    return typeSelect?.value || '';
                }

                function shouldShowCap() {
                    const type = selectedType();
                    if (type === 'Fondamentale I' || type === 'Fondamentale II') return true;
                    if (type === 'Complexe Scolaire') return (nomFondamental?.value || '').trim() !== '';
                    return false;
                }

                function fieldMatches(field, type) {
                    if (type === 'Complexe Scolaire') return field.classList.contains('js-complexe');
                    if (type === 'Fondamentale I' || type === 'Fondamentale II') return field.classList.contains('js-fondamentale');
                    if (type === 'Secondaire Generale') return field.classList.contains('js-secondaire-generale');
                    if (type === 'Secondaire Technique et Professionnel') return field.classList.contains('js-secondaire-technique');
                    return false;
                }

                function filterCaps() {
                    const academieId = academieSelect?.value || '';
                    capOptions.forEach((option) => {
                        option.hidden = academieId !== '' && option.dataset.academie !== academieId;
                    });

                    if (capSelect?.selectedOptions[0]?.hidden) {
                        capSelect.value = '';
                    }
                }

                function updateFields() {
                    const type = selectedType();
                    typeFields.forEach((field) => {
                        const visible = fieldMatches(field, type);
                        field.classList.toggle('d-none', !visible);
                        field.querySelectorAll('.js-dynamic-input').forEach((input) => {
                            input.disabled = !visible;
                        });
                    });

                    const capVisible = shouldShowCap();
                    capField?.classList.toggle('d-none', !capVisible);
                    if (capSelect) {
                        capSelect.required = capVisible;
                        capSelect.disabled = !capVisible;
                        if (!capVisible) capSelect.value = '';
                    }

                    filterCaps();
                }

                typeSelect?.addEventListener('change', updateFields);
                academieSelect?.addEventListener('change', filterCaps);
                nomFondamental?.addEventListener('input', updateFields);
                updateFields();
            });
        });
    </script>
@endonce
