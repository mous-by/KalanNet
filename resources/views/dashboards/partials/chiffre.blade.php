{{-- Tuile de chiffre clé d'un espace de supervision. --}}
<div class="col">
    <div class="card theme-card shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="widget-icon rounded-3 theme-icon-box"><i class="bi {{ $icone }} fs-4"></i></div>
            <div>
                <div class="text-muted small">{{ $libelle }}</div>
                <div class="fs-4 fw-bold" style="font-variant-numeric: tabular-nums;">{{ number_format($valeur, 0, ',', ' ') }}</div>
                @isset($detail)<div class="small text-muted">{{ $detail }}</div>@endisset
            </div>
        </div>
    </div>
</div>
