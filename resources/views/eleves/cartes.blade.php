@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Élèves & Parents</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active">Cartes scolaires</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="card theme-card shadow-sm mb-3">
        <div class="card-header theme-header">
            <h5 class="mb-0 fw-bold">Cartes d'identité scolaires</h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('eleves.cartes') }}" method="POST" class="row g-3 align-items-end" data-auto-filter="true" data-auto-filter-fields="id_classe,id_annee,search">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Classe</label>
                    <select name="id_classe" class="form-select" required>
                        <option value="">Choisir une classe...</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id_classe }}" @selected(request('id_classe') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Année scolaire</label>
                    <select name="id_annee" class="form-select" required>
                        <option value="">Choisir une année...</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id_anneeScolaire }}" @selected(request('id_annee') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Recherche</label>
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nom, prénom ou matricule" data-auto-filter-search="true">
                </div>
            </form>
            <div class="row g-3 align-items-end">
                <div class="col-12">
                    <hr class="my-2">
                    <h6 class="fw-bold mb-3">Atelier de configuration avant impression</h6>
                    <label class="form-label fw-bold">Modèle de carte</label>
                    <div class="row g-3">
                        @foreach([
                            'institutionnel' => ['Institutionnel', 'Sobre, administratif, très lisible'],
                            'moderne' => ['Moderne', 'Couleur forte, présentation plus actuelle'],
                            'vertical' => ['Badge vertical', 'Format carte professionnelle avec photo mise en avant'],
                            'alliance_pro' => ['Alliance amélioré', 'Fond blanc, rendu plus propre'],
                            'horizon' => ['Horizon', 'Carte large avec bande officielle'],
                            'compact' => ['Compact', 'Plus de cartes par page, pratique pour les grands effectifs'],
                        ] as $value => [$label, $description])
                            <div class="col-md-4">
                                <label class="card-template-option d-block">
                                    <input type="radio" name="template" value="{{ $value }}" class="form-check-input me-2 card-config" @checked(request('template', 'alliance_pro') === $value)>
                                    <span class="fw-bold">{{ $label }}</span>
                                    <small class="d-block text-muted mt-1">{{ $description }}</small>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Couleur principale</label>
                    <input type="color" name="primary_color" class="form-control form-control-color card-config" value="{{ request('primary_color', '#0f766e') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Couleur secondaire</label>
                    <input type="color" name="secondary_color" class="form-control form-control-color card-config" value="{{ request('secondary_color', '#f59e0b') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Titre sur la carte</label>
                    <input type="text" name="card_title" class="form-control card-config" value="{{ request('card_title', 'CARTE D’IDENTITÉ SCOLAIRE') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Signature</label>
                    <input type="text" name="signature_label" class="form-control card-config" value="{{ request('signature_label', 'Directeur') }}">
                    <div class="mt-3">
                        <label class="form-label">Signature électronique</label>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#signature-modal">
                                <i class="bi bi-pencil-square me-2"></i>Signer
                            </button>
                            <span class="small text-muted" id="signature-status">Aucune signature</span>
                        </div>
                        <input type="hidden" name="signature_image" id="signature-image-input" class="card-config" value="{{ request('signature_image') }}">
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-3">
                        <label class="form-check">
                            <input type="hidden" name="show_mali_flag" value="0">
                            <input type="checkbox" name="show_mali_flag" value="1" class="form-check-input card-config" @checked(request('show_mali_flag', '1') === '1')>
                            <span class="form-check-label">Afficher le drapeau du Mali</span>
                        </label>
                        <label class="form-check">
                            <input type="hidden" name="show_school_logo" value="0">
                            <input type="checkbox" name="show_school_logo" value="1" class="form-check-input card-config" @checked(request('show_school_logo', '1') === '1')>
                            <span class="form-check-label">Afficher le logo de l’école</span>
                        </label>
                        <label class="form-check">
                            <input type="hidden" name="show_qr" value="0">
                            <input type="checkbox" name="show_qr" value="1" class="form-check-input card-config" @checked(request('show_qr', '1') === '1')>
                            <span class="form-check-label">Afficher un QR code par élève</span>
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Présentation du drapeau</label>
                    <select name="mali_flag_style" class="form-select card-config">
                        <option value="corner" @selected(request('mali_flag_style', 'corner') === 'corner')>En haut à gauche</option>
                        <option value="watermark" @selected(request('mali_flag_style') === 'watermark')>Filigrane diagonal</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if(!$showList)
        <div class="empty-eleves-state text-center py-5">
            <div class="empty-eleves-icon mx-auto mb-3 d-flex align-items-center justify-content-center">
                <i class="bi bi-person-vcard fs-1"></i>
            </div>
            <h5 class="fw-bold mb-2">Préparer les cartes scolaires</h5>
            <p class="text-muted mb-0">Choisissez une classe et une année scolaire pour afficher les élèves concernés.</p>
        </div>
    @else
        <div class="card theme-card shadow-sm cards-students-card">
            <div class="card-header theme-header cards-students-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">Élèves disponibles</h5>
                    <div class="small fw-semibold cards-students-count">{{ number_format($eleves->count(), 0, ',', ' ') }} élève(s)</div>
                </div>
                @if($eleves->isNotEmpty())
                    <form action="{{ route('eleves.cartes.pdf') }}" method="POST" target="_blank" id="cards-form">
                        @csrf
                        <input type="hidden" name="id_classe" value="{{ request('id_classe') }}">
                        <input type="hidden" name="id_annee" value="{{ request('id_annee') }}">
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <input type="hidden" name="template" value="{{ request('template', 'alliance_pro') }}" data-card-config-target="template">
                        <input type="hidden" name="primary_color" value="{{ request('primary_color', '#0f766e') }}" data-card-config-target="primary_color">
                        <input type="hidden" name="secondary_color" value="{{ request('secondary_color', '#f59e0b') }}" data-card-config-target="secondary_color">
                        <input type="hidden" name="card_title" value="{{ request('card_title', 'CARTE D’IDENTITÉ SCOLAIRE') }}" data-card-config-target="card_title">
                        <input type="hidden" name="signature_label" value="{{ request('signature_label', 'Directeur') }}" data-card-config-target="signature_label">
                        <input type="hidden" name="signature_image" value="{{ request('signature_image') }}" data-card-config-target="signature_image">
                        <input type="hidden" name="show_mali_flag" value="{{ request('show_mali_flag', '1') }}" data-card-config-target="show_mali_flag">
                        <input type="hidden" name="mali_flag_style" value="{{ request('mali_flag_style', 'corner') }}" data-card-config-target="mali_flag_style">
                        <input type="hidden" name="show_school_logo" value="{{ request('show_school_logo', '1') }}" data-card-config-target="show_school_logo">
                        <input type="hidden" name="show_qr" value="{{ request('show_qr', '1') }}" data-card-config-target="show_qr">
                        <input type="hidden" name="selected_eleves" id="cards-selected-input">
                        <button type="submit" class="btn btn-primary" id="cards-print-button">
                            <i class="bi bi-printer me-2"></i>Imprimer toutes les cartes
                        </button>
                    </form>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0 cards-students-table">
                    <thead>
                    <tr>
                        <th class="text-center" style="width: 48px;"><input type="checkbox" id="cards-check-all"></th>
                        <th>Élève</th>
                        <th>Matricule</th>
                        <th>Classe</th>
                        <th>Genre</th>
                        <th>Contact parent</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($eleves as $eleve)
                        <tr>
                            <td class="text-center"><input type="checkbox" class="cards-check" value="{{ $eleve->id_eleve }}"></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center overflow-hidden" style="width: 38px; height: 38px;">
                                        @if($eleve->image)
                                            <img src="{{ asset($eleve->image) }}" alt="" class="w-100 h-100 object-fit-cover">
                                        @else
                                            <i class="bi bi-person text-muted"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $eleve->prenom_eleve }} {{ $eleve->nom_eleve }}</div>
                                        <div class="small text-muted">{{ $eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : 'Naissance non renseignée' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark font-monospace">{{ $eleve->matricule }}</span></td>
                            <td>{{ $eleve->classe?->nom_classe }}</td>
                            <td>{{ $eleve->genre_eleve }}</td>
                            <td>{{ $eleve->parents->first()?->telephone_parent ?? 'Non renseigné' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Aucun élève trouvé pour ces critères.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="modal fade" id="signature-modal" tabindex="-1" aria-labelledby="signature-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="signature-modal-title">Signature électronique</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="signature-pad-wrap">
                        <canvas id="signature-pad" class="signature-pad"></canvas>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="signature-clear">Effacer</button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Valider</button>
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
            color: var(--theme-primary);
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
            border-color: var(--theme-primary);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, .12);
        }
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

    <script>
        const cardsCheckAll = document.getElementById('cards-check-all');
        const cardsSelectedInput = document.getElementById('cards-selected-input');
        const cardsPrintButton = document.getElementById('cards-print-button');
        const configInputs = Array.from(document.querySelectorAll('.card-config'));
        const signaturePad = document.getElementById('signature-pad');
        const signatureInput = document.getElementById('signature-image-input');
        const signatureClear = document.getElementById('signature-clear');
        const signatureModal = document.getElementById('signature-modal');
        const signatureStatus = document.getElementById('signature-status');
        const signatureStorageKey = 'kalannet_card_signature_{{ session('idEcole') }}';

        function updateCardsSelection() {
            const checked = Array.from(document.querySelectorAll('.cards-check:checked'));
            const ids = checked.map((box) => box.value);
            if (cardsSelectedInput) cardsSelectedInput.value = ids.join(',');
            if (cardsPrintButton) {
                cardsPrintButton.innerHTML = ids.length > 0
                    ? `<i class="bi bi-printer me-2"></i>Imprimer ${ids.length} carte(s)`
                    : `<i class="bi bi-printer me-2"></i>Imprimer toutes les cartes`;
            }
            const boxes = document.querySelectorAll('.cards-check');
            if (cardsCheckAll) {
                cardsCheckAll.checked = boxes.length > 0 && ids.length === boxes.length;
                cardsCheckAll.indeterminate = ids.length > 0 && ids.length < boxes.length;
            }
        }

        cardsCheckAll?.addEventListener('change', () => {
            document.querySelectorAll('.cards-check').forEach((box) => box.checked = cardsCheckAll.checked);
            updateCardsSelection();
        });
        document.querySelectorAll('.cards-check').forEach((box) => box.addEventListener('change', updateCardsSelection));
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
                signatureStatus.textContent = signatureInput.value ? 'Signature enregistrée' : 'Aucune signature';
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

        configInputs.forEach((input) => input.addEventListener('change', syncCardConfig));
        setupSignaturePad();
        syncCardConfig();
        updateCardsSelection();
    </script>
@endsection
