<div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead>
            <tr>
                <th>Type</th>
                @if($showClass)<th>Classe</th>@endif
                <th>Année</th>
                <th class="text-end">Montant</th>
            </tr>
        </thead>
        <tbody>
        @forelse($frais as $item)
            <tr>
                <td>{{ $item->type_frais }}</td>
                @if($showClass)<td>{{ $item->classe?->nom_classe }}</td>@endif
                <td>{{ $item->anneeScolaire?->annee }}</td>
                <td class="text-end fw-bold">{{ number_format($item->montant, 0, ',', ' ') }} FCFA</td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $showClass ? 4 : 3 }}" class="text-center text-muted py-4">
                    Aucun frais configuré pour ce type d’école.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
