<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card theme-card h-100">
            <div class="card-header theme-header d-flex justify-content-between align-items-center">
                <div>
                    <strong>Cotisations autorisées</strong>
                    <div class="small opacity-75">Coopérative, inscription, badge, tenue, activités et autres cotisations.</div>
                </div>
                @if($canPay)
                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#fraisModal">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                @endif
            </div>
            @include('finances.paiements.partials.fees-table', ['showClass' => false])
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card theme-card h-100">
            <div class="card-header theme-header"><strong>Préparer un paiement</strong></div>
            <form method="POST" action="{{ route('finances.paiements.plans.store') }}" class="card-body">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Élève</label>
                    <select name="eleve_id" class="form-select" required>
                        <option value="">Choisir un élève</option>
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
                <input type="hidden" name="mode_paiement" value="personnalise">
                <button class="btn btn-primary w-100" @disabled(!$canPay)>Créer les cotisations à encaisser</button>
            </form>
        </div>
    </div>
</div>

@include('finances.paiements.partials.plans-table', ['compactPublic' => true])
