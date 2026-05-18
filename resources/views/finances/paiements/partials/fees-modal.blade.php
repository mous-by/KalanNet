<div class="modal fade" id="fraisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('finances.paiements.frais.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ $isPublicSchool ? 'Configurer une cotisation' : 'Configurer un frais scolaire' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                @if($isPrivateSchool)
                    <div class="col-12">
                        <label class="form-label">Classe</label>
                        <select name="classe_id" class="form-select" required>
                            <option value="">Choisir une classe</option>
                            @foreach($classes as $classe)<option value="{{ $classe->id_classe }}">{{ $classe->nom_classe }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Type de frais</label>
                        <input name="type_frais" class="form-control" required placeholder="Scolarité, inscription privée...">
                    </div>
                @else
                    <input type="hidden" name="classe_id" value="">
                    <div class="col-12">
                        <label class="form-label">Cotisation</label>
                        <select name="type_frais" class="form-select" required>
                            @foreach($publicFeeTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-12">
                    <label class="form-label">Année</label>
                    <select name="annee_scolaire_id" class="form-select" required>
                        @foreach($annees as $annee)<option value="{{ $annee->id_anneeScolaire }}">{{ $annee->annee }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12"><label class="form-label">Montant</label><input type="number" name="montant" class="form-control" min="0" step="1" required></div>
                <div class="col-12 form-check ms-2"><input type="checkbox" name="obligatoire" value="1" class="form-check-input" checked><label class="form-check-label">Obligatoire</label></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
        </form>
    </div>
</div>
