@php
    $files = ($files ?? collect())->values();
    $carouselId = $carouselId ?? 'annonceFichiers' . uniqid();

    $iconFor = function (?string $mime) {
        $mime = strtolower((string) $mime);
        if (str_contains($mime, 'pdf')) return 'bi-file-earmark-pdf text-danger';
        if (str_contains($mime, 'word') || str_contains($mime, 'msword')) return 'bi-file-earmark-word text-primary';
        if (str_contains($mime, 'sheet') || str_contains($mime, 'excel')) return 'bi-file-earmark-excel text-success';
        return 'bi-file-earmark text-muted';
    };
@endphp

@if($files->isNotEmpty())
    <div id="{{ $carouselId }}" class="carousel slide annonce-attachments-carousel" data-bs-ride="false">
        <div class="carousel-inner rounded-3 border">
            @foreach($files as $index => $file)
                @php($isImage = str_starts_with((string) $file->type_mime, 'image/'))
                <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                    <a href="{{ asset($file->nom_fichier) }}" target="_blank" class="d-block text-decoration-none">
                        @if($isImage)
                            <img src="{{ asset($file->nom_fichier) }}" class="d-block mx-auto annonce-attachment-image" alt="{{ $file->titre ?: $file->nom_original }}">
                        @else
                            <div class="annonce-attachment-card text-center py-5">
                                <i class="bi {{ $iconFor($file->type_mime) }} display-3"></i>
                                <div class="fw-bold text-body mt-2">{{ $file->titre ?: ($file->nom_original ?: 'Fichier') }}</div>
                                <span class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-box-arrow-up-right me-1"></i>Ouvrir</span>
                            </div>
                        @endif
                    </a>
                    <div class="text-center small text-muted py-1 bg-light border-top">
                        {{ $file->titre ?: ($file->nom_original ?: 'Fichier') }}
                    </div>
                </div>
            @endforeach
        </div>

        @if($files->count() > 1)
            <button class="carousel-control-prev" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Précédent</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Suivant</span>
            </button>
            <div class="carousel-indicators position-relative mt-2">
                @foreach($files as $index => $file)
                    <button type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide-to="{{ $index }}" class="{{ $index === 0 ? 'active' : '' }} bg-dark" aria-current="{{ $index === 0 ? 'true' : 'false' }}"></button>
                @endforeach
            </div>
        @endif
    </div>

    @once
        {{-- @push('styles') ne fonctionne pas ici : ce partiel est aussi inclus
             depuis layouts.app APRES @yield('content') (pour le popup "Nouvelles
             annonces"), donc apres que @stack('styles') du <head> ait deja ete
             rendu — le style pousse serait silencieusement perdu. D'ou un <style>
             en ligne, imprime une seule fois grace a @once. --}}
        <style>
            .annonce-attachments-carousel { max-width: 480px; }
            .annonce-attachment-image { max-height: 420px; width: 100%; object-fit: contain; background: #f8f9fa; }
            .annonce-attachment-card { background: #f8f9fa; }
            .annonce-attachments-carousel .carousel-control-prev,
            .annonce-attachments-carousel .carousel-control-next { width: 10%; }
        </style>
    @endonce
@endif
