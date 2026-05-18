<div class="d-flex flex-wrap align-items-center justify-content-between bg-card p-4 rounded-4 shadow-sm mb-4 gap-3">
    <div>
        <h2 class="mb-1 fw-bold">Paiements scolaires</h2>
        <div class="text-muted">
            {{ $ecole?->nomEcole }} -
            @if($isPublicSchool)
                école publique : cotisations uniquement.
            @else
                école privée : frais scolaires par classe et échéances.
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('finances.paiements.historique') }}" class="btn btn-light">
            <i class="bi bi-clock-history me-1"></i> Historique
        </a>
        @if($caisse && $canPay)
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#encaissementModal">
                <i class="bi bi-cash-stack me-1"></i> Encaisser
            </button>
        @elseif(!$caisse)
            <a href="{{ route('finances.caisse') }}" class="btn btn-warning text-white">Créer une caisse active</a>
        @endif
    </div>
</div>
