<div class="card border-0 rounded-4 shadow-sm overflow-hidden mb-4">
    <div class="card-header bg-white border-0 p-4">
        <strong>{{ $compactPublic ? 'Cotisations à encaisser' : 'Plans et échéances' }}</strong>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="px-4">Élève</th>
                    @unless($compactPublic)<th>Statut</th><th>Mode</th>@endunless
                    <th>Total</th><th>Final</th><th>Échéances</th>
                </tr>
            </thead>
            <tbody>
            @forelse($plans as $plan)
                <tr>
                    <td class="px-4 fw-bold">{{ $plan->eleve?->nom_eleve }} {{ $plan->eleve?->prenom_eleve }}</td>
                    @unless($compactPublic)
                        <td><span class="badge bg-light text-dark">{{ $statuts[$plan->statut_paiement] ?? $plan->statut_paiement }}</span></td>
                        <td>{{ $modes[$plan->mode_paiement] ?? $plan->mode_paiement }}</td>
                    @endunless
                    <td>{{ number_format($plan->montant_total, 0, ',', ' ') }}</td>
                    <td class="fw-bold text-success">{{ number_format($plan->montant_final, 0, ',', ' ') }}</td>
                    <td>
                        @foreach($plan->echeances as $echeance)
                            <span class="badge bg-{{ $echeance->statut === 'paye' ? 'success' : ($echeance->statut === 'retard' ? 'danger' : 'secondary') }} mb-1">
                                {{ $echeance->libelle }}: {{ number_format($echeance->montant_prevu, 0, ',', ' ') }}
                            </span>
                        @endforeach
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $compactPublic ? 4 : 6 }}" class="text-center py-5 text-muted">Aucune échéance générée.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
