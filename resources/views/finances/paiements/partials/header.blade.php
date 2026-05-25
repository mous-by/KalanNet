<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Finances</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Paiements scolaires</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('finances.paiements.historique') }}" class="btn theme-outline-btn">
            <i class="bi bi-clock-history me-1"></i> Historique
        </a>
        @if($caisse && $canPay)
            <button class="btn theme-action-btn" data-bs-toggle="modal" data-bs-target="#encaissementModal">
                <i class="bi bi-cash-stack me-1"></i> Encaisser
            </button>
        @elseif(!$caisse)
            <a href="{{ route('finances.caisse') }}" class="btn btn-warning text-white">Créer une caisse active</a>
        @endif
    </div>
</div>
