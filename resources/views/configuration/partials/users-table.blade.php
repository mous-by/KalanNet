<div class="table-responsive">
    <table class="table table-striped table-bordered align-middle mb-0">
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>
                        @switch($column)
                            @case('name') {{ __('configuration.ut_th_nom_prenom') }} @break
                            @case('email') {{ __('configuration.ut_th_email') }} @break
                            @case('ecole') {{ __('configuration.th_ecole') }} @break
                            @case('fonction') {{ __('configuration.th_fonction') }} @break
                            @case('genre') {{ __('configuration.ut_th_genre') }} @break
                            @case('telephone') {{ __('configuration.ut_th_telephone') }} @break
                            @case('academie') {{ __('configuration.ut_th_academie') }} @break
                            @case('cap') {{ __('configuration.menu_caps') }} @break
                        @endswitch
                    </th>
                @endforeach
                @if($showActions)
                    <th class="text-center">{{ __('configuration.ut_th_action') }}</th>
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
                                    {{ $utilisateur->ecole->nomEcole ?? $utilisateur->enseignant->ecole->nomEcole ?? $utilisateur->parent->ecole->nomEcole ?? __('configuration.ut_non_assigne') }}
                                    @if($utilisateur->droit === 'Gestionnaire' && $utilisateur->ecole?->typeEcole === 'Complexe Scolaire')
                                        <small class="d-block text-muted">
                                            {{ __('configuration.ut_ordres_prefix') }} {{ implode(', ', $utilisateur->managedOrderLabels()) ?: __('configuration.ut_aucun_ordre_assigne') }}
                                        </small>
                                    @endif
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
                                    {{ $utilisateur->academie->nom_academie ?? __('configuration.ut_non_assigne') }}
                                    @break
                                @case('cap')
                                    {{ $utilisateur->cap->nom_cap ?? __('configuration.ut_non_assigne') }}
                                    @break
                            @endswitch
                        </td>
                    @endforeach
                    @if($showActions)
                        <td class="text-center">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                @php($deleteAllowed = $deleteAllowed ?? false)
                                @php($isCurrentUser = Auth::id() === $utilisateur->idUtilisateur)
                                @php($isSuperAdminTarget = $utilisateur->droit === 'SupAdmin')
                                @php($isAdminTarget = $utilisateur->droit === 'Admin')
                                @php($connectedUser = Auth::user())
                                @if(($editAllowed ?? false) && !$isCurrentUser && !$isSuperAdminTarget && ($connectedUser->droit === 'SupAdmin' || !$isAdminTarget))
                                    <a href="{{ route('configuration.utilisateurs.edit', $utilisateur->idUtilisateur) }}" class="btn btn-light btn-sm p-2" title="{{ __('configuration.ut_modifier_utilisateur') }}">
                                        <i class="bx bx-edit text-warning fs-5"></i>
                                    </a>
                                @endif
                                @if(($permissionAllowed ?? false) && !$isCurrentUser && !$isSuperAdminTarget && ($connectedUser->droit === 'SupAdmin' || !$isAdminTarget))
                                    <a href="{{ route('configuration.utilisateurs.permissions.assigner', ['user_id' => $utilisateur->idUtilisateur]) }}" class="btn btn-light btn-sm p-2" title="{{ __('configuration.ut_assigner_permissions_title') }}">
                                        <i class="bx bx-user-check text-primary fs-5"></i>
                                    </a>
                                @endif
                                @if($statusAllowed)
                                    <button class="btn btn-light btn-sm p-2" data-bs-toggle="modal" data-bs-target="#statusUserModal{{ $utilisateur->idUtilisateur }}" title="{{ (int) $utilisateur->statut === 1 ? __('configuration.ut_toggle_title_desactiver') : __('configuration.ut_toggle_title_activer') }}">
                                        @if((int) $utilisateur->statut === 1)
                                            <i class="bx bx-user-check text-success fs-5"></i>
                                        @else
                                            <i class="bx bx-lock-alt text-danger fs-5"></i>
                                        @endif
                                    </button>
                                @endif
                                @if($deleteAllowed && Auth::id() !== $utilisateur->idUtilisateur)
                                    <form action="{{ route('configuration.utilisateurs.destroy', $utilisateur->idUtilisateur) }}" method="POST" data-confirm-delete data-confirm-title="{{ __('configuration.ut_confirm_delete_title') }}" data-confirm-text="{{ __('configuration.ut_confirm_delete_text') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light btn-sm p-2" title="{{ __('configuration.ut_supprimer_compte') }}">
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
                                                    <h5 class="modal-title fw-bold">{{ __('configuration.ut_confirmation') }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('configuration.fermer') }}"></button>
                                                </div>
                                                <div class="modal-body text-center">
                                                    <i class="bx bx-error text-danger" style="font-size: 4rem;"></i>
                                                    <p class="mt-3 mb-0">
                                                        {{ (int) $utilisateur->statut === 1 ? __('configuration.ut_confirm_desactiver_text') : __('configuration.ut_confirm_activer_text') }}
                                                        <strong>{{ $utilisateur->nomPrenom }}</strong> ?
                                                    </p>
                                                    <input type="hidden" name="statut" value="{{ (int) $utilisateur->statut === 1 ? 0 : 1 }}">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">{{ __('configuration.annuler') }}</button>
                                                    <button type="submit" class="btn btn-primary px-4">
                                                        {{ (int) $utilisateur->statut === 1 ? __('configuration.ut_oui_desactiver') : __('configuration.ut_oui_activer') }}
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
                    <td colspan="{{ count($columns) + ($showActions ? 1 : 0) }}" class="text-center py-4 text-muted">{{ __('configuration.empty_utilisateurs') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
