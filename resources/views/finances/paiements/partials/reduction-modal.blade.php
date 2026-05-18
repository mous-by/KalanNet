<div class="modal fade" id="reductionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('finances.paiements.reductions.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Configurer une règle de statut</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label">Statut financier</label>
                    <select name="statut_paiement" class="form-select" required>
                        @foreach($statuts as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Type de réduction</label>
                    <select name="type_reduction" class="form-select" required>
                        <option value="aucune">Aucune</option>
                        <option value="pourcentage">Pourcentage</option>
                        <option value="fixe">Fixe</option>
                        <option value="gratuite_partielle">Gratuité partielle</option>
                        <option value="gratuite_totale">Gratuité totale</option>
                    </select>
                </div>
                <div class="col-12"><label class="form-label">Valeur</label><input type="number" name="valeur" class="form-control" min="0" step="1" value="0" required></div>
                <div class="col-12"><label class="form-label">Payeur / organisme</label><input name="payeur_libelle" class="form-control" placeholder="État, ONG, fondation..."></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
        </form>
    </div>
</div>
