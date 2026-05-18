<div class="modal fade" id="encaissementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('finances.paiements.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Encaisser {{ $isPublicSchool ? 'une cotisation' : 'une échéance' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ $isPublicSchool ? 'Cotisation' : 'Échéance' }}</label>
                    <select name="echeance_id" class="form-select" required>
                        <option value="">Choisir</option>
                        @foreach($plans as $plan)
                            @foreach($plan->echeances as $echeance)
                                @if($echeance->statut !== 'paye')
                                    <option value="{{ $echeance->id }}">
                                        {{ $plan->eleve?->nom_eleve }} {{ $plan->eleve?->prenom_eleve }} - {{ $echeance->libelle }} ({{ number_format($echeance->montant_prevu, 0, ',', ' ') }})
                                    </option>
                                @endif
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Date</label><input type="date" name="date_paiement" value="{{ now()->toDateString() }}" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Montant reçu</label><input type="number" name="montant_paye" class="form-control" min="1" step="1" required></div>
                <div class="col-md-6">
                    <label class="form-label">Mode de règlement</label>
                    <select name="mode_reglement" class="form-select" required>
                        <option value="especes">Espèces</option>
                        <option value="cheque">Chèque</option>
                        <option value="virement">Virement</option>
                        <option value="mobile_money">Mobile money manuel</option>
                    </select>
                </div>
                <div class="col-12"><label class="form-label">Motif</label><input name="motif" class="form-control" placeholder="{{ $isPublicSchool ? 'Paiement cotisation' : 'Paiement échéance' }}"></div>
                <div class="col-md-4">
                    <label class="form-label">Parent payeur</label>
                    <select name="parent_id" class="form-select">
                        <option value="">Autre personne</option>
                        @foreach($eleves as $eleve)
                            @foreach($eleve->parents as $parent)
                                <option value="{{ $parent->id_parent }}">{{ $parent->nom_prenom_parent }} - {{ $eleve->nom_eleve }} {{ $eleve->prenom_eleve }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Nom payeur</label><input name="nom_payeur" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Téléphone</label><input name="telephone" class="form-control"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Encaisser</button></div>
        </form>
    </div>
</div>
