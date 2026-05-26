<div class="table-responsive">
    <table class="table table-striped table-bordered align-middle mb-0">
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>
                        @switch($column)
                            @case('name') Nom & Prénom @break
                            @case('email') Email @break
                            @case('ecole') École @break
                            @case('fonction') Fonction @break
                            @case('genre') Genre @break
                            @case('telephone') Téléphone @break
                            @case('academie') Académie @break
                            @case('cap') CAP @break
                        @endswitch
                    </th>
                @endforeach
                @if($showActions)
                    <th class="text-center">Action</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($users as $utilisateur)
                <tr>
                    @foreach($columns as $column)
                        <td>
                            @switch($column)
                                @case('name')
                                    <span class="fw-bold">{{ $utilisateur->nomPrenom ?? 'N/A' }}</span>
                                    @break
                                @case('email')
                                    {{ $utilisateur->email ?? 'N/A' }}
                                    @break
                                @case('ecole')
                                    {{ $utilisateur->ecole->nomEcole ?? $utilisateur->enseignant->ecole->nomEcole ?? $utilisateur->parent->ecole->nomEcole ?? 'Non assigné' }}
                                    @break
                                @case('fonction')
                                    {{ $utilisateur->fonction ?? $utilisateur->droit ?? 'N/A' }}
                                    @break
                                @case('genre')
                                    {{ $utilisateur->genre ?? 'N/A' }}
                                    @break
                                @case('telephone')
                                    {{ $utilisateur->telephone ?? $utilisateur->enseignant->telephone_enseignant ?? $utilisateur->parent->telephone_parent ?? 'N/A' }}
                                    @break
                                @case('academie')
                                    {{ $utilisateur->academie->nom_academie ?? 'Non assigné' }}
                                    @break
                                @case('cap')
                                    {{ $utilisateur->cap->nom_cap ?? 'Non assigné' }}
                                    @break
                            @endswitch
                        </td>
                    @endforeach
                    @if($showActions)
                        <td class="text-center">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                @php($deleteAllowed = $deleteAllowed ?? false)
                                @if($statusAllowed)
                                    <button class="btn btn-light btn-sm p-2" data-bs-toggle="modal" data-bs-target="#statusUserModal{{ $utilisateur->idUtilisateur }}" title="{{ (int) $utilisateur->statut === 1 ? 'Désactiver' : 'Activer' }}">
                                        @if((int) $utilisateur->statut === 1)
                                            <i class="bx bx-user-check text-success fs-5"></i>
                                        @else
                                            <i class="bx bx-lock-alt text-danger fs-5"></i>
                                        @endif
                                    </button>
                                @endif
                                @if($deleteAllowed && Auth::id() !== $utilisateur->idUtilisateur)
                                    <form action="{{ route('configuration.utilisateurs.destroy', $utilisateur->idUtilisateur) }}" method="POST" data-confirm-delete data-confirm-title="Supprimer ce compte ?" data-confirm-text="Le compte d'accès sera supprimé. Les fiches enseignant, parent et les historiques métier restent conservés.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light btn-sm p-2" title="Supprimer le compte">
                                            <i class="bx bx-trash text-danger fs-5"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>

                            @if($statusAllowed)
                                <div class="modal fade" id="statusUserModal{{ $utilisateur->idUtilisateur }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 rounded-4 shadow">
                                            <form action="{{ route('configuration.utilisateurs.status', $utilisateur->idUtilisateur) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <div class="modal-header theme-header">
                                                    <h5 class="modal-title fw-bold">Confirmation</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                                </div>
                                                <div class="modal-body text-center">
                                                    <i class="bx bx-error text-danger" style="font-size: 4rem;"></i>
                                                    <p class="mt-3 mb-0">
                                                        Voulez-vous vraiment {{ (int) $utilisateur->statut === 1 ? 'désactiver' : 'activer' }}
                                                        <strong>{{ $utilisateur->nomPrenom }}</strong> ?
                                                    </p>
                                                    <input type="hidden" name="statut" value="{{ (int) $utilisateur->statut === 1 ? 0 : 1 }}">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-primary px-4">
                                                        {{ (int) $utilisateur->statut === 1 ? 'Oui, désactiver' : 'Oui, activer' }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + ($showActions ? 1 : 0) }}" class="text-center py-4 text-muted">Aucun utilisateur trouvé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
