<div class="card border-0 rounded-4 shadow-sm overflow-hidden">
    <div class="card-header bg-white border-0 p-4"><strong>Derniers paiements</strong></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="px-4">Réf / Reçu</th><th>Élève</th><th>Objet</th><th>Montant</th><th>Date</th><th>Statut</th><th class="text-end px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($paiements as $p)
                <tr>
                    <td class="px-4"><span class="badge bg-light text-dark">{{ $p->reference }}</span><div class="small">#{{ $p->numero_recu }}</div></td>
                    <td>{{ $p->eleve?->nom_eleve }} {{ $p->eleve?->prenom_eleve }}</td>
                    <td>{{ $p->echeance?->libelle ?? $p->motif }}</td>
                    <td class="fw-bold">{{ number_format($p->montant_paye ?? $p->montant, 0, ',', ' ') }} FCFA</td>
                    <td>{{ optional($p->date_paiement)->format('d/m/Y') ?? date('d/m/Y', strtotime($p->date_paiement)) }}</td>
                    <td><span class="badge bg-{{ $p->statut === 'annule' ? 'danger' : 'success' }}">{{ $p->statut ?? 'valide' }}</span></td>
                    <td class="text-end px-4">
                        <a href="{{ route('finances.paiements.download', $p->id_paiement) }}" class="btn btn-light btn-sm"><i class="bi bi-printer"></i></a>
                        @if($canPay && ($p->statut ?? 'valide') !== 'annule')
                            <form method="POST" action="{{ route('finances.paiements.cancel', $p->id_paiement) }}" class="d-inline">
                                @csrf @method('DELETE')
                                <input type="hidden" name="motif_annulation" value="Annulation demandée depuis le module paiement">
                                <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Annuler comptablement ce paiement ?')">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-5 text-muted">Aucun paiement enregistré.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($paiements->hasPages())
        <div class="card-footer bg-white">{{ $paiements->links() }}</div>
    @endif
</div>
