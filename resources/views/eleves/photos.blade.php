@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('eleves.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('eleves.index') }}">{{ __('eleves.list_link') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('eleves.photos_classe') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header">
            <h5 class="mb-0 fw-bold"><i class="bi bi-camera me-2"></i>{{ __('eleves.photos_classe') }}</h5>
        </div>
        <div class="card-body">
            <p class="text-muted small">{{ __('eleves.photos_classe_intro') }}</p>

            <form method="GET" action="{{ route('eleves.photos') }}" class="row g-2 align-items-end mb-3">
                <div class="col-sm-8 col-md-5">
                    <label class="form-label small fw-bold text-uppercase" for="classe">{{ __('eleves.photos_choisir_classe') }}</label>
                    <select name="classe" id="classe" class="form-select" onchange="this.form.submit()">
                        <option value="">—</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id_classe }}" @selected($classe?->id_classe === $c->id_classe)>{{ $c->nom_classe }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if($classe)
                @php $sansPhoto = $eleves->where('sans_photo', true)->count(); @endphp
                <div class="alert {{ $sansPhoto ? 'alert-warning' : 'alert-success' }} py-2" data-photos-compteur
                     data-texte="{{ __('eleves.photos_sans_photo', ['count' => '__N__', 'total' => $eleves->count()]) }}" data-toutes="{{ __('eleves.photos_toutes') }}">
                    {{ $sansPhoto ? __('eleves.photos_sans_photo', ['count' => $sansPhoto, 'total' => $eleves->count()]) : __('eleves.photos_toutes') }}
                </div>

                @forelse($eleves as $eleve)
                    <div class="d-flex align-items-center gap-3 border-bottom py-2" data-photo-eleve data-sans-photo="{{ $eleve->sans_photo ? '1' : '0' }}"
                         data-url="{{ route('eleves.photo', $eleve->id_eleve) }}">
                        <div class="photo-eleve flex-shrink-0">
                            @if($eleve->image_url)
                                <img src="{{ $eleve->image_url }}" alt="">
                            @else
                                <i class="bi bi-person"></i>
                            @endif
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-truncate">{{ $eleve->prenom_eleve }} {{ $eleve->nom_eleve }}</div>
                            <div class="small text-muted">
                                {{ $eleve->matricule }}
                                <span class="badge bg-warning text-dark ms-1 {{ $eleve->sans_photo ? '' : 'd-none' }}" data-badge>{{ __('eleves.photo_manquante') }}</span>
                            </div>
                            <div class="small text-danger d-none" data-erreur>{{ __('eleves.photos_erreur') }}</div>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <label class="btn btn-sm theme-pill-active mb-0" title="{{ __('eleves.photos_prendre') }}">
                                <i class="bi bi-camera"></i><span class="d-none d-md-inline ms-1">{{ __('eleves.photos_prendre') }}</span>
                                <input type="file" accept="image/*" capture="environment" class="d-none" data-photo-input>
                            </label>
                            <label class="btn btn-sm btn-outline-secondary mb-0" title="{{ __('eleves.photo_ajouter') }}">
                                <i class="bi bi-image"></i>
                                <input type="file" accept="image/jpeg,image/png,image/webp" class="d-none" data-photo-input>
                            </label>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">{{ __('eleves.photos_aucun_eleve') }}</p>
                @endforelse
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .photo-eleve { width: 56px; height: 56px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background: var(--bg-main, #f3f4f6); border: 1px solid var(--border-color, #e5e7eb); color: #6b7280; font-size: 1.5rem; position: relative; }
        .photo-eleve img { width: 100%; height: 100%; object-fit: cover; }
        .photo-eleve.is-loading::after { content: ''; position: absolute; inset: 0; background: rgba(255, 255, 255, .6) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Ccircle cx='12' cy='12' r='9' fill='none' stroke='%23999' stroke-width='3' stroke-dasharray='40 20'%3E%3CanimateTransform attributeName='transform' type='rotate' from='0 12 12' to='360 12 12' dur='0.8s' repeatCount='indefinite'/%3E%3C/circle%3E%3C/svg%3E") center / 24px no-repeat; }
    </style>
@endpush

@push('scripts')
    <script>
        // Photo prise ou choisie : envoyée aussitôt, vignette et compteur mis à jour sans recharger la page.
        (function () {
            const token = @json(csrf_token());
            const compteur = document.querySelector('[data-photos-compteur]');
            const majCompteur = () => {
                if (!compteur) return;
                const n = document.querySelectorAll('[data-photo-eleve][data-sans-photo="1"]').length;
                compteur.textContent = n ? compteur.dataset.texte.replace('__N__', n) : compteur.dataset.toutes;
                compteur.className = 'alert py-2 ' + (n ? 'alert-warning' : 'alert-success');
            };

            document.querySelectorAll('[data-photo-input]').forEach((input) => input.addEventListener('change', async () => {
                const ligne = input.closest('[data-photo-eleve]');
                const vignette = ligne.querySelector('.photo-eleve');
                const erreur = ligne.querySelector('[data-erreur]');
                if (!input.files.length) return;

                const donnees = new FormData();
                donnees.append('photo', input.files[0]);
                vignette.classList.add('is-loading');
                erreur.classList.add('d-none');
                ligne.querySelectorAll('[data-photo-input]').forEach((i) => i.disabled = true);
                try {
                    const reponse = await fetch(ligne.dataset.url, { method: 'POST', body: donnees, headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } });
                    const json = await reponse.json().catch(() => ({}));
                    if (!reponse.ok || !json.image_url) throw new Error(json.message || '');
                    vignette.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = json.image_url;
                    img.alt = '';
                    vignette.appendChild(img);
                    ligne.dataset.sansPhoto = '0';
                    ligne.querySelector('[data-badge]').classList.add('d-none');
                    majCompteur();
                } catch (e) {
                    erreur.textContent = e.message || erreur.textContent;
                    erreur.classList.remove('d-none');
                } finally {
                    vignette.classList.remove('is-loading');
                    ligne.querySelectorAll('[data-photo-input]').forEach((i) => { i.disabled = false; i.value = ''; });
                }
            }));
        })();
    </script>
@endpush
