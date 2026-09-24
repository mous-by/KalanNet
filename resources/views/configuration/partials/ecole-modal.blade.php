<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ $action }}" method="POST" class="ecole-dynamic-form" enctype="multipart/form-data">
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
                                <option value="Complexe Scolaire" @selected(old('typeEcole', $ecole->typeEcole ?? '') === 'Complexe Scolaire')>Complexe Scolaire</option>
                                {{-- Mali : cycles decoupes finement (voir ExamenNational/ClasseController::ordresDisponibles) --}}
                                @foreach(['Fondamentale I', 'Fondamentale II', 'Collège'] as $type)
                                    <option value="{{ $type }}" data-mali-only="1" @selected(old('typeEcole', $ecole->typeEcole ?? '') === $type)>{{ $type }}</option>
                                @endforeach
                                {{-- Hors Mali : un seul cycle primaire (1 a 6), le secondaire ne se decoupe pas en etablissements distincts --}}
                                <option value="Primaire" data-non-mali-only="1" @selected(old('typeEcole', $ecole->typeEcole ?? '') === 'Primaire')>Primaire</option>
                                <option value="Secondaire Generale" @selected(old('typeEcole', $ecole->typeEcole ?? '') === 'Secondaire Generale')>Secondaire Generale</option>
                                <option value="Secondaire Technique et Professionnel" @selected(old('typeEcole', $ecole->typeEcole ?? '') === 'Secondaire Technique et Professionnel')>Secondaire Technique et Professionnel</option>
                                {{-- École de Santé : independant du pays, pas de decoupage cycle/academie --}}
                                <option value="École de Santé" @selected(old('typeEcole', $ecole->typeEcole ?? '') === 'École de Santé')>École de Santé</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Statut</label>
                            <select name="statut" class="form-select js-statut-select" required>
                                <option value="public" @selected(old('statut', $ecole->statut ?? 'public') === 'public')>Public</option>
                                <option value="prive" @selected(old('statut', $ecole->statut ?? 'public') === 'prive')>Privé</option>
                            </select>
                        </div>
                        <div class="col-md-6 js-academie-field">
                            <div class="js-academie-input-group">
                                <label class="form-label">Académie</label>
                                <select name="id_academie" class="form-select js-academie-select" required>
                                    <option value="">Sélectionner</option>
                                    @foreach($academies as $academie)
                                        <option value="{{ $academie->id_academie }}" data-pays="{{ $academie->id_pays }}" @selected(old('id_academie', $ecole->id_academie ?? null) == $academie->id_academie)>
                                            {{ $academie->nom_academie }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-link btn-sm px-0 mt-1 js-academie-new-toggle">+ Créer une nouvelle académie</button>
                                <input type="text" name="nouvelle_academie_nom" class="form-control mt-1 d-none js-academie-new-input" placeholder="Nom de la nouvelle académie" value="{{ old('nouvelle_academie_nom') }}">
                            </div>
                            <div class="form-text js-academie-pays-help d-none">Optionnel hors Mali — sélectionnez une académie déjà créée par une autre école de ce pays, ou créez la vôtre.</div>
                        </div>
                        <div class="col-md-6 js-cap-field">
                            <label class="form-label">CAP</label>
                            <select name="id_cap" class="form-select js-cap-select">
                                <option value="">Sélectionner</option>
                                @foreach($caps as $cap)
                                    <option value="{{ $cap->id_cap }}" data-academie="{{ $cap->id_academie }}" data-pays="{{ $cap->id_pays }}" @selected(old('id_cap', $ecole->id_cap ?? null) == $cap->id_cap)>
                                        {{ $cap->nom_cap }} - {{ $cap->academie->nom_academie ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-link btn-sm px-0 mt-1 js-cap-new-toggle">+ Créer un nouveau CAP</button>
                            <input type="text" name="nouveau_cap_nom" class="form-control mt-1 d-none js-cap-new-input" placeholder="Nom du nouveau CAP" value="{{ old('nouveau_cap_nom') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Pays</label>
                            <select name="id_pays" class="form-select js-pays-select">
                                @foreach($pays as $unPays)
                                    <option value="{{ $unPays->id }}" data-code-iso="{{ $unPays->code_iso }}" @selected(old('id_pays', $ecole->id_pays ?? null) == $unPays->id)>
                                        {{ $unPays->nom }} ({{ $unPays->devise_symbole }})
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
                        <div class="col-md-3">
                            <label class="form-label">Notification SMS</label>
                            <select name="notification_sms" class="form-select">
                                <option value="0" @selected(old('notification_sms', $ecole->notification_sms ?? 0) == 0)>Non</option>
                                <option value="1" @selected(old('notification_sms', $ecole->notification_sms ?? 0) == 1)>Oui</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Notification email parents</label>
                            <select name="notification_email" class="form-select">
                                <option value="1" @selected(old('notification_email', $ecole->notification_email ?? 1) == 1)>Oui</option>
                                <option value="0" @selected(old('notification_email', $ecole->notification_email ?? 1) == 0)>Non</option>
                            </select>
                        </div>
                        @if(Auth::user()->droit === 'SupAdmin')
                            <div class="col-md-4">
                                <label class="form-label">{{ $ecole ? "Changer/activer l'abonnement" : "Plan d'abonnement initial" }}</label>
                                <select name="abonnement_offre_id" class="form-select js-offre-select">
                                    <option value="{{ $ecole ? '__KEEP__' : '' }}" selected>{{ $ecole ? "Ne pas modifier l'abonnement" : 'Aucun plan au démarrage' }}</option>
                                    @foreach($abonnementOffres ?? [] as $offre)
                                        <option value="{{ $offre->id }}" data-type-ecole="{{ $offre->type_ecole_cible }}" @selected(old('abonnement_offre_id') == $offre->id)>
                                            {{ $offre->nom }} - {{ number_format($offre->montant, 0, ',', ' ') }} {{ $offre->devise }} / {{ $offre->duree_jours }} jours
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">{{ $ecole ? 'Choisir un plan ajoute une nouvelle période.' : 'Optionnel.' }} Seules les formules compatibles avec le statut choisi (public/privé) sont proposées.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Apportée par un revendeur</label>
                                <select name="id_revendeur" class="form-select">
                                    <option value="">Aucun</option>
                                    @foreach($revendeurs ?? [] as $revendeur)
                                        <option value="{{ $revendeur->id }}" @selected(old('id_revendeur', $ecole->id_revendeur ?? null) == $revendeur->id)>
                                            {{ $revendeur->nom }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">Optionnel — l'école paiera alors le tarif fixé par ce revendeur.</small>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <label class="form-label">Logo de l'école</label>
                            <input type="file" name="logoEcole" class="form-control js-logo-input" accept="image/png,image/jpeg,image/webp">
                            <small class="text-muted d-block mt-1">Formats acceptés : JPG, PNG, WebP. Taille max : 2 Mo.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Aperçu du logo</label>
                            @php($logoPreview = !empty($ecole?->logoEcole) ? asset($ecole->logoEcole) : '')
                            <div class="ecole-logo-preview d-flex align-items-center justify-content-center">
                                <img src="{{ $logoPreview }}" alt="Aperçu du logo" class="js-logo-preview {{ $logoPreview ? '' : 'd-none' }}">
                                <span class="js-logo-placeholder text-muted small {{ $logoPreview ? 'd-none' : '' }}">Aucun logo sélectionné</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Adresse</label>
                            <textarea name="adresse" class="form-control" rows="2">{{ old('adresse', $ecole->adresse ?? '') }}</textarea>
                        </div>
                        <div class="col-md-6 js-type-field js-complexe">
                            <label class="form-label">Nom du Complexe Scolaire</label>
                            <input type="text" name="nomComplexe" class="form-control js-dynamic-input" value="{{ old('nomComplexe', $ecole->nomComplexe ?? '') }}" placeholder="Entrez le nom du complexe">
                        </div>
                        <div class="col-md-6 js-type-field js-complexe">
                            <label class="form-label">Nom école fondamentale du complexe</label>
                            <input type="text" name="nomFondamental" class="form-control js-dynamic-input js-nom-fondamental" value="{{ old('nomFondamental', $ecole->nomFondamental ?? '') }}" placeholder="Renseigner seulement si le complexe contient une fondamentale">
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
    <style>
        .ecole-logo-preview {
            min-height: 92px;
            border: 1px dashed var(--bs-border-color);
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }
        .ecole-logo-preview img {
            max-width: 100%;
            max-height: 84px;
            object-fit: contain;
            padding: 8px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.ecole-dynamic-form').forEach((form) => {
                const typeSelect = form.querySelector('.js-ecole-type');
                const statutSelect = form.querySelector('.js-statut-select');
                const offreSelect = form.querySelector('.js-offre-select');
                const paysSelect = form.querySelector('.js-pays-select');
                const academieInputGroup = form.querySelector('.js-academie-input-group');
                const academieSelect = form.querySelector('.js-academie-select');
                const academieField = form.querySelector('.js-academie-field');
                const academiePaysHelp = form.querySelector('.js-academie-pays-help');
                const academieNewToggle = form.querySelector('.js-academie-new-toggle');
                const academieNewInput = form.querySelector('.js-academie-new-input');
                const capField = form.querySelector('.js-cap-field');
                const capSelect = form.querySelector('.js-cap-select');
                const capNewToggle = form.querySelector('.js-cap-new-toggle');
                const capNewInput = form.querySelector('.js-cap-new-input');
                const nomFondamental = form.querySelector('.js-nom-fondamental');
                const typeFields = Array.from(form.querySelectorAll('.js-type-field'));
                const logoInput = form.querySelector('.js-logo-input');
                const logoPreview = form.querySelector('.js-logo-preview');
                const logoPlaceholder = form.querySelector('.js-logo-placeholder');
                const modalEl = form.closest('.modal');
                const $modal = modalEl ? jQuery(modalEl) : undefined;

                // Snapshot of every Academie/CAP option as originally rendered by the
                // server, used to rebuild each select whenever Pays (or Academie, for
                // CAP) changes, rather than just toggling `hidden`, which Select2 does
                // not react to.
                const allAcademieOptions = academieSelect
                    ? Array.from(academieSelect.querySelectorAll('option[data-pays]')).map((opt) => ({
                        value: opt.value,
                        text: opt.textContent,
                        paysId: opt.dataset.pays,
                    }))
                    : [];
                const allCapOptions = capSelect
                    ? Array.from(capSelect.querySelectorAll('option[data-academie]')).map((opt) => ({
                        value: opt.value,
                        text: opt.textContent,
                        academieId: opt.dataset.academie,
                        paysId: opt.dataset.pays,
                    }))
                    : [];

                if (academieSelect) {
                    jQuery(academieSelect).select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: $modal,
                        placeholder: 'Sélectionner une académie',
                        allowClear: true,
                    });
                }
                if (capSelect) {
                    jQuery(capSelect).select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: $modal,
                        placeholder: 'Sélectionner un CAP',
                        allowClear: true,
                    });
                }

                function selectedType() {
                    return typeSelect?.value || '';
                }

                // Le referentiel academie/CAP est 100% malien -- pas de pays
                // selectionne = Mali par defaut (meme regle que cote serveur).
                function isMali() {
                    if (!paysSelect) return true;
                    const option = paysSelect.options[paysSelect.selectedIndex];
                    return !option || option.dataset.codeIso === 'ML';
                }

                function selectedPaysId() {
                    if (!paysSelect) return '';
                    const option = paysSelect.options[paysSelect.selectedIndex];
                    return option ? option.value : '';
                }

                // Hors Mali, academie/CAP restent optionnels quel que soit le
                // type d'ecole (rien a imposer sur un referentiel que l'ecole
                // elle-meme est en train de construire).
                function shouldShowCap() {
                    if (!isMali()) return true;
                    const type = selectedType();
                    return type === 'Fondamentale I'
                        || type === 'Fondamentale II'
                        || type === 'Collège'
                        || type === 'Complexe Scolaire';
                }

                // Les Académies/CAP sont la subdivision administrative du fondamental/
                // secondaire classique : elles ne s'appliquent pas à une École de Santé.
                function shouldShowAcademie() {
                    return selectedType() !== 'École de Santé';
                }

                function fieldMatches(field, type) {
                    if (type === 'Complexe Scolaire') return field.classList.contains('js-complexe');
                    return false;
                }

                // Reconstruit le select Academie a partir des seules options du
                // pays choisi (propose d'abord l'existant). Si ce pays n'a
                // encore aucune academie enregistree, bascule directement sur
                // la saisie libre plutot que de laisser un select vide.
                function filterAcademies() {
                    if (!academieSelect) return;

                    const paysId = selectedPaysId();
                    const currentValue = academieSelect.value;

                    academieSelect.innerHTML = '';
                    academieSelect.appendChild(new Option('Sélectionner', '', false, false));

                    allAcademieOptions
                        .filter((opt) => paysId === '' || opt.paysId === paysId)
                        .forEach((opt) => {
                            const isSelected = opt.value === currentValue;
                            academieSelect.appendChild(new Option(opt.text, opt.value, isSelected, isSelected));
                        });

                    if (!Array.from(academieSelect.options).some((o) => o.value === currentValue)) {
                        academieSelect.value = '';
                    }

                    jQuery(academieSelect).trigger('change.select2');

                    if (!isMali() && academieSelect.options.length <= 1) {
                        toggleAcademieNew(true);
                    }
                }

                // Filtre par academie quand elle est choisie ; sinon (academie
                // pas encore selectionnee, notamment hors Mali) filtre au
                // minimum par pays pour ne jamais melanger les CAP d'un autre pays.
                function filterCaps() {
                    if (!capSelect) return;

                    const academieId = academieSelect?.value || '';
                    const paysId = selectedPaysId();
                    const currentValue = capSelect.value;

                    capSelect.innerHTML = '';
                    capSelect.appendChild(new Option('Sélectionner', '', false, false));

                    allCapOptions
                        .filter((opt) => academieId ? opt.academieId === academieId : (paysId === '' || opt.paysId === paysId))
                        .forEach((opt) => {
                            const isSelected = opt.value === currentValue;
                            capSelect.appendChild(new Option(opt.text, opt.value, isSelected, isSelected));
                        });

                    if (!Array.from(capSelect.options).some((o) => o.value === currentValue)) {
                        capSelect.value = '';
                    }

                    jQuery(capSelect).trigger('change.select2');
                }

                function toggleAcademieNew(show) {
                    academieNewInput?.classList.toggle('d-none', !show);
                    if (academieSelect) {
                        jQuery(academieSelect).prop('disabled', show);
                        if (show) {
                            academieSelect.value = '';
                            jQuery(academieSelect).trigger('change.select2');
                        }
                    }
                    if (!show && academieNewInput) academieNewInput.value = '';
                    filterCaps();
                }

                function toggleCapNew(show) {
                    capNewInput?.classList.toggle('d-none', !show);
                    if (capSelect) {
                        jQuery(capSelect).prop('disabled', show);
                        if (show) {
                            capSelect.value = '';
                            jQuery(capSelect).trigger('change.select2');
                        }
                    }
                    if (!show && capNewInput) capNewInput.value = '';
                }

                academieNewToggle?.addEventListener('click', () => toggleAcademieNew(academieNewInput?.classList.contains('d-none')));
                capNewToggle?.addEventListener('click', () => toggleCapNew(capNewInput?.classList.contains('d-none')));
                jQuery(academieSelect).on('change', function () {
                    if (this.value) toggleAcademieNew(false);
                });
                jQuery(capSelect).on('change', function () {
                    if (this.value) toggleCapNew(false);
                });

                // Une formule réservée au public ou au privé (data-type-ecole) ne
                // doit être proposable que pour une école du même statut — sinon le
                // serveur l'ignore silencieusement à l'enregistrement.
                function filterOffres() {
                    if (!offreSelect || !statutSelect) return;

                    const statut = statutSelect.value;
                    let currentOptionStillValid = true;

                    Array.from(offreSelect.options).forEach((option) => {
                        if (!option.hasAttribute('data-type-ecole')) return; // option "aucun/ne pas modifier"
                        const cible = option.dataset.typeEcole;
                        const matches = cible === '' || cible === statut;
                        option.hidden = !matches;
                        option.disabled = !matches;
                        if (option.selected && !matches) currentOptionStillValid = false;
                    });

                    if (!currentOptionStillValid) {
                        offreSelect.value = offreSelect.options[0]?.value ?? '';
                    }
                }

                function filterTypeEcoleByPays() {
                    if (!typeSelect) return;

                    const mali = isMali();
                    let currentOptionStillValid = true;

                    Array.from(typeSelect.options).forEach((option) => {
                        if (option.dataset.maliOnly) {
                            option.hidden = !mali;
                            option.disabled = !mali;
                            if (option.selected && !mali) currentOptionStillValid = false;
                        }
                        if (option.dataset.nonMaliOnly) {
                            option.hidden = mali;
                            option.disabled = mali;
                            if (option.selected && mali) currentOptionStillValid = false;
                        }
                    });

                    if (!currentOptionStillValid) {
                        typeSelect.value = '';
                    }
                }

                function updateFields() {
                    filterTypeEcoleByPays();
                    const type = selectedType();
                    typeFields.forEach((field) => {
                        const visible = fieldMatches(field, type);
                        field.classList.toggle('d-none', !visible);
                        field.querySelectorAll('.js-dynamic-input').forEach((input) => {
                            input.disabled = !visible;
                        });
                    });

                    // Academie/CAP restent visibles pour tout pays (sauf Ecole de
                    // Sante, qui n'a pas de subdivision academie/CAP) : seul change
                    // le caractere obligatoire (Mali) vs libre-service optionnel
                    // (les autres pays creent/choisissent le leur, voir plus haut).
                    const mali = isMali();
                    const academieVisible = shouldShowAcademie();
                    academieField?.classList.toggle('d-none', !academieVisible);
                    academiePaysHelp?.classList.toggle('d-none', !academieVisible || mali);
                    academieNewToggle?.classList.toggle('d-none', !academieVisible || mali);
                    if (!academieVisible || mali) toggleAcademieNew(false);
                    if (academieSelect) {
                        academieSelect.required = academieVisible && mali;
                        jQuery(academieSelect).prop('disabled', !academieVisible);
                        if (!academieVisible) academieSelect.value = '';
                    }
                    filterAcademies();

                    const capVisible = academieVisible && shouldShowCap();
                    capField?.classList.toggle('d-none', !capVisible);
                    capNewToggle?.classList.toggle('d-none', mali);
                    if (mali) toggleCapNew(false);
                    if (capSelect) {
                        capSelect.required = mali && capVisible;
                        jQuery(capSelect).prop('disabled', !capVisible);
                        if (!capVisible) capSelect.value = '';
                    }

                    filterCaps();
                    filterOffres();
                }

                typeSelect?.addEventListener('change', updateFields);
                statutSelect?.addEventListener('change', filterOffres);
                paysSelect?.addEventListener('change', updateFields);
                jQuery(academieSelect).on('change', filterCaps);
                logoInput?.addEventListener('change', () => {
                    const file = logoInput.files?.[0];
                    if (!file || !logoPreview) return;

                    logoPreview.src = URL.createObjectURL(file);
                    logoPreview.classList.remove('d-none');
                    logoPlaceholder?.classList.add('d-none');
                });
                updateFields();
            });
        });
    </script>
@endonce
