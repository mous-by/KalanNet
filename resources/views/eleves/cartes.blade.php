@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('eleves.title_parents') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active">{{ __('cartes.breadcrumb_title') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="card theme-card shadow-sm mb-3">
        <div class="card-header theme-header">
            <h5 class="mb-0 fw-bold">{{ __('cartes.page_title') }}</h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('eleves.cartes') }}" method="GET" class="row g-3 align-items-end" id="cartes-filtres">
                <div class="col-md-4">
                    <label class="form-label">{{ __('eleves.label_classe') }}</label>
                    <select name="id_classe" class="form-select" required>
                        <option value="">{{ __('eleves.choose_classe') }}</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id_classe }}" @selected(request('id_classe') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('eleves.label_annee') }}</label>
                    <select name="id_annee" class="form-select" required>
                        <option value="">{{ __('eleves.choose_annee') }}</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id_anneeScolaire }}" @selected(request('id_annee') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('cartes.label_recherche') }}</label>
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('eleves.search_quick_placeholder') }}" data-auto-filter-search="true">
                </div>
            </form>
            <div class="row g-3 align-items-end">
                <div class="col-12">
                    <hr class="my-2">
                    <h6 class="fw-bold mb-3">{{ __('cartes.workshop_title') }}</h6>
                    <label class="form-label fw-bold">{{ __('cartes.model_label') }}</label>
                    <div class="row g-3">
                        @foreach(\App\Support\ModelesCarte::MODELES as $value => $infos)
                            <div class="col-sm-6 col-lg-4">
                                <label class="card-template-option d-flex flex-column">
                                    <img src="{{ asset('assets/images/cartes/'.$value.'.png') }}" alt="" class="carte-vignette mb-2" loading="lazy">
                                    <span>
                                        <input type="radio" name="template" value="{{ $value }}" class="form-check-input me-2 card-config" data-couleurs="{{ implode(',', $infos['couleurs']) }}" @checked($modeleActuel === $value)>
                                        <span class="fw-bold">{{ __('cartes.modele_'.$value) }}</span>
                                    </span>
                                    <small class="d-block text-muted mt-1 mb-2">{{ __('cartes.modele_'.$value.'_desc') }}</small>
                                    <span class="d-flex align-items-center mt-auto">
                                        <span class="small fw-semibold text-success modele-coche"><i class="bi bi-check-circle-fill me-1"></i>{{ __('cartes.modele_choisi') }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto carte-apercu" data-template="{{ $value }}" data-nom="{{ __('cartes.modele_'.$value) }}">
                                            <i class="bi bi-eye me-1"></i>{{ __('cartes.apercu') }}
                                        </button>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('cartes.primary_color_label') }}</label>
                    <input type="color" name="primary_color" class="form-control form-control-color card-config" value="{{ $couleur1 }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('cartes.secondary_color_label') }}</label>
                    <input type="color" name="secondary_color" class="form-control form-control-color card-config" value="{{ $couleur2 }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('cartes.card_title_label') }}</label>
                    <input type="text" name="card_title" class="form-control card-config" value="{{ request('card_title', __('cartes.card_title_default')) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('cartes.entete_gauche_label') }}</label>
                    <textarea name="entete_gauche" rows="2" class="form-control card-config" placeholder="{{ __('cartes.entete_gauche_placeholder') }}">{{ $enteteGauche }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('cartes.entete_droite_label') }}</label>
                    <textarea name="entete_droite" rows="2" class="form-control card-config" placeholder="{{ __('cartes.entete_droite_placeholder') }}">{{ $enteteDroite }}</textarea>
                </div>
                <div class="col-12 small text-muted mt-1">{{ __('cartes.entete_aide') }}</div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('cartes.signature_label_label') }}</label>
                    <input type="text" name="signature_label" class="form-control card-config" value="{{ request('signature_label', __('cartes.signature_label_default')) }}">
                    <div class="mt-3">
                        <label class="form-label">{{ __('cartes.signature_electronic_label') }}</label>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#signature-modal">
                                <i class="bi bi-pencil-square me-2"></i>{{ __('cartes.sign_button') }}
                            </button>
                            <span class="small text-muted" id="signature-status">{{ __('cartes.no_signature') }}</span>
                        </div>
                        <input type="hidden" name="signature_image" id="signature-image-input" class="card-config" value="{{ request('signature_image') }}">
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-3">
                        <label class="form-check">
                            <input type="hidden" name="show_mali_flag" value="0">
                            <input type="checkbox" name="show_mali_flag" value="1" class="form-check-input card-config" @checked(request('show_mali_flag', '1') === '1')>
                            <span class="form-check-label">{{ __('cartes.show_flag_label') }}</span>
                        </label>
                        <label class="form-check">
                            <input type="hidden" name="show_school_logo" value="0">
                            <input type="checkbox" name="show_school_logo" value="1" class="form-check-input card-config" @checked(request('show_school_logo', '1') === '1')>
                            <span class="form-check-label">{{ __('cartes.show_logo_label') }}</span>
                        </label>
                        <label class="form-check">
                            <input type="hidden" name="show_qr" value="0">
                            <input type="checkbox" name="show_qr" value="1" class="form-check-input card-config" @checked(request('show_qr', '1') === '1')>
                            <span class="form-check-label">{{ __('cartes.show_qr_label') }}</span>
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('cartes.flag_style_label') }}</label>
                    <select name="mali_flag_style" class="form-select card-config">
                        <option value="corner" @selected(request('mali_flag_style', 'corner') === 'corner')>{{ __('cartes.flag_style_corner') }}</option>
                        <option value="watermark" @selected(request('mali_flag_style') === 'watermark')>{{ __('cartes.flag_style_watermark') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div id="cartes-liste">
        @include('eleves.partials.cartes-liste')
    </div>
    {{-- Le PDF des cartes est chargé ici puis imprimé directement (pas de téléchargement). --}}
    <iframe name="impression-cartes-cadre" id="impression-cartes-cadre" title="{{ __('cartes.print_all_button') }}" style="position:fixed; right:0; bottom:0; width:0; height:0; border:0;"></iframe>

    <div class="modal fade" id="apercu-carte-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">{{ __('cartes.apercu_titre') }} · <span id="apercu-carte-nom"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('eleves.close') }}"></button>
                </div>
                <div class="px-3 pt-2 small text-muted" id="apercu-carte-aide"></div>
                <div class="modal-body p-0" style="height: 76vh;">
                    <iframe name="apercu-carte-cadre" id="apercu-carte-cadre" title="{{ __('cartes.apercu_titre') }}" style="width:100%; height:100%; border:0;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('eleves.close') }}</button>
                    <button type="button" class="btn btn-primary" id="apercu-carte-choisir"><i class="bi bi-check2 me-1"></i>{{ __('cartes.choisir_modele') }}</button>
                </div>
            </div>
        </div>
    </div>
    <form action="{{ route('eleves.cartes.apercu') }}" method="POST" target="apercu-carte-cadre" id="apercu-carte-form" class="d-none">
        @csrf
        <input type="hidden" name="id_classe" value="{{ request('id_classe') }}">
        <input type="hidden" name="id_annee" value="{{ request('id_annee') }}">
        <input type="hidden" name="search" value="{{ request('search') }}">
    </form>

    <div class="modal fade" id="signature-modal" tabindex="-1" aria-labelledby="signature-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="signature-modal-title">{{ __('cartes.signature_modal_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('eleves.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="signature-pad-wrap">
                        <canvas id="signature-pad" class="signature-pad"></canvas>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="signature-clear">{{ __('cartes.clear_button') }}</button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ __('parents.validate') }}</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .empty-eleves-state {
            border: 1px dashed var(--bs-border-color);
            border-radius: 12px;
            background: var(--bs-light);
        }
        .empty-eleves-icon {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            color: var(--theme-accent);
            background: var(--accent-light);
        }
        .card-template-option {
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
            padding: 12px 14px;
            cursor: pointer;
            height: 100%;
            background: #fff;
        }
        .card-template-option:has(input:checked) {
            border-color: var(--theme-accent);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, .12);
        }
        .card-template-option .modele-coche { visibility: hidden; }
        .carte-vignette { width: 100%; height: auto; border-radius: 6px; box-shadow: 0 1px 4px rgba(15, 23, 42, .18); }
        #cartes-liste.chargement { opacity: .55; pointer-events: none; transition: opacity .15s; }
        .card-template-option:has(input:checked) .modele-coche { visibility: visible; }
        .cards-students-table thead th {
            background: #fff !important;
            color: inherit !important;
        }
        html[data-theme] .cards-students-card {
            border-top: 0 !important;
        }
        .cards-students-header .cards-students-count {
            color: inherit;
            opacity: .92;
        }
        html[data-theme] .cards-students-table thead,
        html[data-theme] .cards-students-table thead tr,
        html[data-theme] .cards-students-table thead th,
        html[data-theme] .cards-students-table thead th * {
            background: #fff !important;
            background-color: #fff !important;
            color: #111827 !important;
        }
        .signature-pad-wrap {
            position: relative;
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
            background: #fff;
            height: 210px;
            overflow: hidden;
        }
        .signature-pad {
            width: 100%;
            height: 100%;
            display: block;
            touch-action: none;
            cursor: crosshair;
        }
    </style>

    @php
        $cartesI18n = [
            'printAll' => __('cartes.print_all_button'),
            'printN' => __('cartes.print_n_button'),
            'signatureSaved' => __('cartes.signature_saved'),
            'noSignature' => __('cartes.no_signature'),
            'apercuExemple' => __('cartes.apercu_exemple'),
            'apercuClasse' => __('cartes.apercu_classe'),
            'preparation' => __('cartes.preparation_impression'),
        ];
    @endphp
    <script>
        const cartesI18n = @json($cartesI18n);
        // La liste des élèves est remplacée sans recharger la page : ses éléments sont relus à chaque usage.
        const el = (id) => document.getElementById(id);
        const configInputs = Array.from(document.querySelectorAll('.card-config'));
        const signaturePad = document.getElementById('signature-pad');
        const signatureInput = document.getElementById('signature-image-input');
        const signatureClear = document.getElementById('signature-clear');
        const signatureModal = document.getElementById('signature-modal');
        const signatureStatus = document.getElementById('signature-status');
        const signatureStorageKey = 'kalannet_card_signature_{{ session('idEcole') }}';
        let impressionEnCours = false;

        function updateCardsSelection() {
            const ids = Array.from(document.querySelectorAll('.cards-check:checked')).map((box) => box.value);
            if (el('cards-selected-input')) el('cards-selected-input').value = ids.join(',');
            const bouton = el('cards-print-button');
            if (bouton && !impressionEnCours) {
                bouton.innerHTML = ids.length > 0
                    ? `<i class="bi bi-printer me-2"></i>${cartesI18n.printN.replace(':count', ids.length)}`
                    : `<i class="bi bi-printer me-2"></i>${cartesI18n.printAll}`;
            }
            const boxes = document.querySelectorAll('.cards-check');
            const tout = el('cards-check-all');
            if (tout) {
                tout.checked = boxes.length > 0 && ids.length === boxes.length;
                tout.indeterminate = ids.length > 0 && ids.length < boxes.length;
            }
        }

        document.addEventListener('change', (event) => {
            if (event.target.id === 'cards-check-all') {
                document.querySelectorAll('.cards-check').forEach((box) => box.checked = event.target.checked);
                updateCardsSelection();
            } else if (event.target.classList.contains('cards-check')) {
                updateCardsSelection();
            }
        });

        function syncCardConfig() {
            configInputs.forEach((input) => {
                const target = document.querySelector(`[data-card-config-target="${input.name}"]`);
                if (!target) return;
                if (input.type === 'checkbox') {
                    target.value = input.checked ? '1' : '0';
                } else if (input.type !== 'radio' || input.checked) {
                    target.value = input.value;
                }
            });
        }

        function setupSignaturePad() {
            if (!signaturePad || !signatureInput) return;

            if (!signatureInput.value) {
                signatureInput.value = localStorage.getItem(signatureStorageKey) || '';
            }

            const context = signaturePad.getContext('2d');
            let drawing = false;
            let hasInk = Boolean(signatureInput.value);

            function updateSignatureStatus() {
                if (!signatureStatus) return;
                signatureStatus.textContent = signatureInput.value ? cartesI18n.signatureSaved : cartesI18n.noSignature;
            }

            function resizePad() {
                const rect = signaturePad.getBoundingClientRect();
                if (!rect.width || !rect.height) return;
                const ratio = window.devicePixelRatio || 1;
                signaturePad.width = Math.max(1, Math.round(rect.width * ratio));
                signaturePad.height = Math.max(1, Math.round(rect.height * ratio));
                context.setTransform(ratio, 0, 0, ratio, 0, 0);
                context.lineWidth = 2;
                context.lineCap = 'round';
                context.lineJoin = 'round';
                context.strokeStyle = '#111827';
                drawStoredSignature();
            }

            function drawStoredSignature() {
                if (!signatureInput.value) return;
                const image = new Image();
                image.onload = () => {
                    context.drawImage(image, 0, 0, signaturePad.clientWidth, signaturePad.clientHeight);
                };
                image.src = signatureInput.value;
            }

            function pointFromEvent(event) {
                const rect = signaturePad.getBoundingClientRect();
                return {
                    x: event.clientX - rect.left,
                    y: event.clientY - rect.top,
                };
            }

            function saveSignature() {
                signatureInput.value = hasInk ? signaturePad.toDataURL('image/png') : '';
                if (signatureInput.value) {
                    localStorage.setItem(signatureStorageKey, signatureInput.value);
                } else {
                    localStorage.removeItem(signatureStorageKey);
                }
                syncCardConfig();
                updateSignatureStatus();
            }

            updateSignatureStatus();
            window.addEventListener('resize', resizePad);
            signatureModal?.addEventListener('shown.bs.modal', resizePad);

            signaturePad.addEventListener('pointerdown', (event) => {
                event.preventDefault();
                signaturePad.setPointerCapture(event.pointerId);
                drawing = true;
                hasInk = true;
                const point = pointFromEvent(event);
                context.beginPath();
                context.moveTo(point.x, point.y);
            });

            signaturePad.addEventListener('pointermove', (event) => {
                if (!drawing) return;
                event.preventDefault();
                const point = pointFromEvent(event);
                context.lineTo(point.x, point.y);
                context.stroke();
                saveSignature();
            });

            ['pointerup', 'pointercancel', 'pointerleave'].forEach((eventName) => {
                signaturePad.addEventListener(eventName, () => {
                    if (!drawing) return;
                    drawing = false;
                    saveSignature();
                });
            });

            signatureClear?.addEventListener('click', () => {
                context.clearRect(0, 0, signaturePad.width, signaturePad.height);
                hasInk = false;
                saveSignature();
            });
        }

        // Impression directe : le PDF arrive dans le cadre caché, puis la fenêtre d'impression s'ouvre.
        const cadreImpression = el('impression-cartes-cadre');
        function finImpression() {
            impressionEnCours = false;
            if (el('cards-print-button')) el('cards-print-button').disabled = false;
            updateCardsSelection();
        }
        document.addEventListener('submit', (event) => {
            if (event.target.id !== 'cards-form') return;
            syncCardConfig();
            if (el('cards-imprimer-input').value !== '1') return;
            impressionEnCours = true;
            const bouton = el('cards-print-button');
            bouton.disabled = true;
            bouton.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${cartesI18n.preparation}`;
            // Garde-fou : le bouton redevient utilisable même si le navigateur ne signale pas la fin.
            setTimeout(() => { if (impressionEnCours) finImpression(); }, 120000);
        });
        cadreImpression?.addEventListener('load', () => {
            if (!impressionEnCours) return;
            finImpression();
            setTimeout(() => {
                try {
                    cadreImpression.contentWindow.focus();
                    cadreImpression.contentWindow.print();
                } catch (e) {
                    // Navigateur qui refuse d'imprimer depuis le cadre : le PDF s'ouvre dans un onglet.
                    const form = el('cards-form');
                    form.target = '_blank';
                    form.submit();
                    form.target = 'impression-cartes-cadre';
                }
            }, 300);
        });
        document.addEventListener('click', (event) => {
            if (!event.target.closest('#cards-download-button')) return;
            const form = el('cards-form');
            syncCardConfig();
            el('cards-imprimer-input').value = '0';
            form.target = '_self';
            form.submit();
            el('cards-imprimer-input').value = '1';
            form.target = 'impression-cartes-cadre';
        });

        // Classe, année ou recherche : la liste est rechargée seule, sans recharger la page.
        const filtres = el('cartes-filtres');
        let filtreDemande = null;
        let filtreMinuterie = null;
        function chargerListe() {
            const params = new URLSearchParams(new FormData(filtres));
            const zone = el('cartes-liste');
            const demande = (filtreDemande = Symbol());
            zone.classList.add('chargement');
            fetch(filtres.action + '?' + params.toString(), { headers: { 'X-Cartes-Liste': '1', 'X-Requested-With': 'XMLHttpRequest' } })
                .then((reponse) => { if (!reponse.ok) throw new Error(); return reponse.text(); })
                .then((html) => {
                    if (demande !== filtreDemande) return;
                    zone.innerHTML = html;
                    // Aperçu : mêmes élèves que la liste ; adresse de la page à jour (rechargement, favori).
                    ['id_classe', 'id_annee', 'search'].forEach((nom) => {
                        const champ = el('apercu-carte-form').querySelector(`[name="${nom}"]`);
                        if (champ) champ.value = params.get(nom) || '';
                    });
                    history.replaceState(null, '', filtres.action + '?' + params.toString());
                    syncCardConfig();
                    updateCardsSelection();
                })
                .catch(() => filtres.submit())
                .finally(() => { if (demande === filtreDemande) zone.classList.remove('chargement'); });
        }
        filtres?.addEventListener('submit', (event) => { event.preventDefault(); chargerListe(); });
        filtres?.querySelectorAll('select').forEach((liste) => liste.addEventListener('change', chargerListe));
        filtres?.querySelector('[name="search"]')?.addEventListener('input', () => {
            clearTimeout(filtreMinuterie);
            filtreMinuterie = setTimeout(chargerListe, 350);
        });

        // Choisir un modèle applique ses couleurs (modifiables ensuite).
        document.querySelectorAll('input[name="template"]').forEach((radio) => {
            radio.addEventListener('change', () => {
                const [principale, secondaire] = (radio.dataset.couleurs || '').split(',');
                if (principale) document.querySelector('input[name="primary_color"]').value = principale;
                if (secondaire) document.querySelector('input[name="secondary_color"]').value = secondaire;
                syncCardConfig();
            });
        });

        // Aperçu d'un modèle avec les réglages en cours (couleurs, titre, signature…).
        const apercuForm = document.getElementById('apercu-carte-form');
        const apercuModal = document.getElementById('apercu-carte-modal');
        let apercuTemplate = null;
        document.querySelectorAll('.carte-apercu').forEach((bouton) => {
            bouton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                apercuTemplate = bouton.dataset.template;
                apercuForm.querySelectorAll('.apercu-config').forEach((champ) => champ.remove());
                const valeurs = { template: apercuTemplate };
                configInputs.forEach((input) => {
                    if (input.name === 'template') return;
                    if (input.type === 'checkbox') valeurs[input.name] = input.checked ? '1' : '0';
                    else if (input.type !== 'radio' || input.checked) valeurs[input.name] = input.value;
                });
                // Modèle pas encore choisi : affiché dans ses propres couleurs.
                const radioModele = document.querySelector(`input[name="template"][value="${apercuTemplate}"]`);
                if (radioModele && !radioModele.checked) {
                    [valeurs.primary_color, valeurs.secondary_color] = radioModele.dataset.couleurs.split(',');
                }
                const coches = Array.from(document.querySelectorAll('.cards-check:checked')).map((box) => box.value);
                if (coches.length) valeurs.selected_eleves = coches.join(',');
                Object.entries(valeurs).forEach(([nom, valeur]) => {
                    const champ = document.createElement('input');
                    champ.type = 'hidden'; champ.name = nom; champ.value = valeur; champ.className = 'apercu-config';
                    apercuForm.appendChild(champ);
                });
                document.getElementById('apercu-carte-nom').textContent = bouton.dataset.nom;
                document.getElementById('apercu-carte-aide').textContent = apercuForm.querySelector('[name=id_classe]').value ? cartesI18n.apercuClasse : cartesI18n.apercuExemple;
                bootstrap.Modal.getOrCreateInstance(apercuModal).show();
                apercuForm.submit();
            });
        });
        document.getElementById('apercu-carte-choisir')?.addEventListener('click', () => {
            const radio = document.querySelector(`input[name="template"][value="${apercuTemplate}"]`);
            if (radio && !radio.checked) { radio.checked = true; radio.dispatchEvent(new Event('change', { bubbles: true })); }
            bootstrap.Modal.getOrCreateInstance(apercuModal).hide();
        });
        apercuModal?.addEventListener('hidden.bs.modal', () => {
            document.getElementById('apercu-carte-cadre').src = 'about:blank';
        });

        configInputs.forEach((input) => input.addEventListener('change', syncCardConfig));
        setupSignaturePad();
        syncCardConfig();
        updateCardsSelection();
    </script>
@endsection
