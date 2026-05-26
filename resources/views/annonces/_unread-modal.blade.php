@php
    $user = Auth::user();
    $schoolId = session('idEcole') ?: ($user->idEcole ?? null);
    $unreadAnnouncements = collect();
    $unreadAnnouncementFiles = collect();

    if ($user && $schoolId && \Illuminate\Support\Facades\Schema::hasTable('annonces_admin_gestionnaire') && \Illuminate\Support\Facades\Schema::hasTable('annonces_lues')) {
        $unreadAnnouncements = \App\Http\Controllers\AnnouncementController::visibleAnnouncementQuery($user, (int) $schoolId)
            ->leftJoin('utilisateurs as users', 'users.idUtilisateur', '=', 'annonces.id_utilisateur')
            ->whereNotExists(function ($query) use ($user) {
                $query->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('annonces_lues as lues')
                    ->whereColumn('lues.id_annonce', 'annonces.id_annonce')
                    ->where('lues.id_utilisateur', $user->idUtilisateur)
                    ->where('lues.type_annonce', 'admin_gestionnaire');
            })
            ->select('annonces.*', 'users.nomPrenom as auteur')
            ->limit(5)
            ->get();

        if ($unreadAnnouncements->isNotEmpty() && \Illuminate\Support\Facades\Schema::hasTable('annonces_fichiers')) {
            $unreadAnnouncementFiles = \Illuminate\Support\Facades\DB::table('annonces_fichiers')
                ->whereIn('id_annonce', $unreadAnnouncements->pluck('id_annonce'))
                ->where('type_annonce', 'admin_gestionnaire')
                ->orderBy('id_fichier')
                ->get()
                ->groupBy('id_annonce');
        }
    }
@endphp

@if($unreadAnnouncements->isNotEmpty())
    <div class="modal fade" id="unreadAnnouncementsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header theme-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-megaphone me-2"></i>Nouvelles annonces</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    @foreach($unreadAnnouncements as $annonce)
                        <div class="border-bottom py-3">
                            <div class="d-flex justify-content-between gap-3 flex-wrap">
                                <div>
                                    <h6 class="fw-bold mb-1">{{ $annonce->titre }}</h6>
                                    <div class="small text-muted mb-2">
                                        {{ $annonce->date_publication ? \Carbon\Carbon::parse($annonce->date_publication)->format('d/m/Y H:i') : 'Publication' }}
                                        @if($annonce->auteur)
                                            - {{ $annonce->auteur }}
                                        @endif
                                    </div>
                                </div>
                                <span class="badge bg-primary align-self-start">{{ ucfirst($annonce->public_cible) }}</span>
                            </div>
                            <div>{{ $annonce->contenu }}</div>
                            @php($files = $unreadAnnouncementFiles[$annonce->id_annonce] ?? collect())
                            @if($files->isNotEmpty())
                                <div class="mt-2">
                                    @foreach($files as $file)
                                        <a href="{{ asset($file->nom_fichier) }}" target="_blank" class="btn btn-sm btn-light border me-1 mb-1">
                                            <i class="bi bi-paperclip me-1"></i>{{ $file->titre ?: ($file->nom_original ?: 'Fichier') }}
                                        </a>
                                    @endforeach
                                </div>
                            @elseif($annonce->fichier_joint)
                                <a href="{{ asset($annonce->fichier_joint) }}" target="_blank" class="btn btn-sm btn-light border mt-2">
                                    <i class="bi bi-paperclip me-1"></i>Pièce jointe
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <form action="{{ route('annonces.read-visible') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary">J'ai lu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalEl = document.getElementById('unreadAnnouncementsModal');
                if (modalEl && window.bootstrap) {
                    new bootstrap.Modal(modalEl).show();
                }
            });
        </script>
    @endpush
@endif
