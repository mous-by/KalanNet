<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card theme-card h-100">
            <div class="card-header theme-header d-flex justify-content-between align-items-center">
                <strong>Frais scolaires par classe</strong>
                @if($canPay)
                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#fraisModal">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                @endif
            </div>
            @include('finances.paiements.partials.fees-table', ['showClass' => true])
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card theme-card h-100">
            <div class="card-header theme-header d-flex justify-content-between align-items-center">
                <strong>Statuts et réductions</strong>
                @if($canPay)
                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#reductionModal">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                @endif
            </div>
            <div class="card-body">
                @forelse($reductions as $reduction)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>{{ $statuts[$reduction->statut_paiement] ?? $reduction->statut_paiement }}</span>
                        <strong>{{ $reduction->type_reduction }} {{ number_format($reduction->valeur, 0, ',', ' ') }}</strong>
                    </div>
                @empty
                    <div class="text-muted">Aucune règle configurée.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card theme-card h-100">
            <div class="card-header theme-header"><strong>Générer les échéances</strong></div>
            <form method="POST" action="{{ route('finances.paiements.plans.store') }}" class="card-body">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Élève</label>
                    <select name="eleve_id" class="form-select" required>
                        <option value="">Choisir</option>
                        @foreach($eleves as $eleve)
                            <option value="{{ $eleve->id_eleve }}">{{ $eleve->nom_eleve }} {{ $eleve->prenom_eleve }} - {{ $eleve->classe?->nom_classe }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Année scolaire</label>
                    <select name="annee_scolaire_id" class="form-select" required>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id_anneeScolaire }}">{{ $annee->annee }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mode</label>
                    <select name="mode_paiement" class="form-select" required>
                        @foreach($modes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary w-100" @disabled(!$canPay)>Générer / actualiser</button>
            </form>
        </div>
    </div>
</div>

@include('finances.paiements.partials.plans-table', ['compactPublic' => false])
