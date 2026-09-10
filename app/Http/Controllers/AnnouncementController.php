<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class AnnouncementController extends Controller
{
    public function index()
    {
        $this->authorizeAnnouncementAccess('annonces_apercu');
        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;
        $isSupAdmin = $user->droit === 'SupAdmin';

        $annonces = collect();
        if (($schoolId || $isSupAdmin) && Schema::hasTable('annonces_admin_gestionnaire')) {
            $annonces = DB::table('annonces_admin_gestionnaire as annonces')
                ->leftJoin('utilisateurs as users', 'users.idUtilisateur', '=', 'annonces.id_utilisateur')
                ->where(function ($query) use ($schoolId, $isSupAdmin) {
                    $query->where('annonces.id_ecole', $schoolId);
                    // Le SupAdmin voit aussi ses propres annonces globales (id_ecole = null),
                    // sinon il ne pourrait jamais les republier/archiver/supprimer.
                    if ($isSupAdmin) {
                        $query->orWhereNull('annonces.id_ecole');
                    }
                })
                ->select('annonces.*', 'users.nomPrenom as auteur')
                ->orderByDesc('annonces.date_publication')
                ->orderByDesc('annonces.id_annonce')
                ->paginate(15);
        }

        $announcementItems = method_exists($annonces, 'items') ? collect($annonces->items()) : collect($annonces);
        $filesByAnnouncement = $this->filesByAnnouncement($announcementItems->pluck('id_annonce')->all());

        return view('annonces.index', compact('annonces', 'filesByAnnouncement'));
    }

    public function store(Request $request)
    {
        $this->authorizeAnnouncementAccess('annonces_creation');
        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;
        // Seul le SupAdmin peut diffuser une annonce à toutes les écoles à la fois.
        $isGlobal = $user->droit === 'SupAdmin' && $request->boolean('global');

        if (!Schema::hasTable('annonces_admin_gestionnaire') || (!$isGlobal && !$schoolId)) {
            return back()->with('error', 'Le module des annonces n’est pas encore disponible.');
        }

        $data = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'public_cible' => 'required|string|in:tous,parents,enseignants,gestionnaires,admins',
            'statut_annonce' => 'required|string|in:publie,brouillon,archive',
            'fichiers' => 'nullable|array',
            'fichiers.*' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            'titres_fichiers' => 'nullable|array',
            'titres_fichiers.*' => 'nullable|string|max:255',
        ]);

        if ($isGlobal) {
            // Une diffusion globale cible toujours et uniquement les Admin — peu importe
            // ce qui a été soumis.
            $data['public_cible'] = 'admins';
        } elseif ($data['public_cible'] === 'admins') {
            // 'admins' n'a de sens que pour une diffusion globale.
            abort(422, 'Cible invalide pour une annonce d’école.');
        }

        DB::transaction(function () use ($request, $data, $schoolId, $isGlobal) {
            $storedFiles = $this->storeAttachments($request);
            $firstFile = $storedFiles[0] ?? [];

            $announcementId = DB::table('annonces_admin_gestionnaire')->insertGetId(array_merge([
                'id_ecole' => $isGlobal ? null : $schoolId,
                'titre' => $data['titre'],
                'contenu' => $data['contenu'],
                'public_cible' => $data['public_cible'],
                'id_utilisateur' => Auth::id(),
                'date_publication' => $data['statut_annonce'] === 'publie' ? now() : null,
            ], $this->optionalAnnouncementColumns([
                'statut_annonce' => $data['statut_annonce'],
                'fichier_joint' => $firstFile['path'] ?? null,
                'type_fichier' => $firstFile['mime'] ?? null,
                'taille_fichier' => $firstFile['size'] ?? null,
            ])));

            $this->insertAttachmentRows($announcementId, $storedFiles);

            if ($isGlobal && $data['statut_annonce'] === 'publie') {
                $this->notifyAllAdmins($announcementId, $data['titre']);
            }
        });

        return back()->with('success', $isGlobal ? 'Annonce diffusée à tous les Admin.' : 'Annonce enregistrée avec succès.');
    }

    /**
     * Announcements targeted at the current user's role (tous / their group)
     * that they have not marked read yet — the same feed the web layout pops
     * up automatically on login (annonces/_unread-modal.blade.php), available
     * to every authenticated user regardless of the annonces_apercu
     * management permission (that one only gates the admin listing/CRUD).
     */
    public function visibleUnread()
    {
        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;

        if (!$schoolId || !Schema::hasTable('annonces_admin_gestionnaire')) {
            return response()->json(['annonces' => [], 'fichiers' => []]);
        }

        $query = static::visibleAnnouncementQuery($user, (int) $schoolId)
            ->leftJoin('utilisateurs as users', 'users.idUtilisateur', '=', 'annonces.id_utilisateur')
            ->select('annonces.*', 'users.nomPrenom as auteur');

        if (Schema::hasTable('annonces_lues')) {
            $query->whereNotExists(function ($inner) use ($user) {
                $inner->select(DB::raw(1))
                    ->from('annonces_lues as lues')
                    ->whereColumn('lues.id_annonce', 'annonces.id_annonce')
                    ->where('lues.id_utilisateur', $user->idUtilisateur)
                    ->where('lues.type_annonce', 'admin_gestionnaire');
            });
        }

        $annonces = $query->limit(5)->get();

        return response()->json([
            'annonces' => $annonces,
            'fichiers' => $this->filesByAnnouncement($annonces->pluck('id_annonce')->all()),
        ]);
    }

    public function markVisibleAsRead()
    {
        if (!Schema::hasTable('annonces_lues')) {
            return back();
        }

        $ids = $this->visibleUnreadAnnouncementIds();
        foreach ($ids as $id) {
            $readPayload = [
                'date_lecture' => now(),
            ];
            if (Schema::hasColumn('annonces_lues', 'created_at')) {
                $readPayload['created_at'] = now();
            }
            if (Schema::hasColumn('annonces_lues', 'updated_at')) {
                $readPayload['updated_at'] = now();
            }

            DB::table('annonces_lues')->updateOrInsert(
                [
                    'id_utilisateur' => Auth::id(),
                    'id_annonce' => $id,
                    'type_annonce' => 'admin_gestionnaire',
                ],
                $readPayload
            );
        }

        return back();
    }

    public function publish(int $id)
    {
        $this->authorizeAnnouncementAccess('annonces_creation');
        $updates = $this->optionalAnnouncementColumns([
            'statut_annonce' => 'publie',
            'date_publication' => now(),
        ]);
        if (!empty($updates)) {
            $this->ownedAnnouncementQuery($id)->update($updates);
        }

        return back()->with('success', 'Annonce publiée.');
    }

    public function archive(int $id)
    {
        $this->authorizeAnnouncementAccess('annonces_creation');
        $updates = $this->optionalAnnouncementColumns([
            'statut_annonce' => 'archive',
        ]);
        if (!empty($updates)) {
            $this->ownedAnnouncementQuery($id)->update($updates);
        }

        return back()->with('success', 'Annonce archivée.');
    }

    public function destroy(int $id)
    {
        $this->authorizeAnnouncementAccess('annonces_supprimer');
        $annonce = $this->ownedAnnouncementQuery($id)->first();

        if (!$annonce) {
            abort(404);
        }

        if (!empty($annonce->fichier_joint)) {
            File::delete(public_path($annonce->fichier_joint));
        }

        foreach ($this->filesByAnnouncement([$id])->flatten(1) as $file) {
            File::delete(public_path($file->nom_fichier));
        }

        if (Schema::hasTable('annonces_fichiers')) {
            DB::table('annonces_fichiers')
                ->where('id_annonce', $id)
                ->where('type_annonce', 'admin_gestionnaire')
                ->delete();
        }

        $this->ownedAnnouncementQuery($id)->delete();

        return back()->with('success', 'Annonce supprimée.');
    }

    protected function authorizeAnnouncementAccess(string $permission): void
    {
        $user = Auth::user();
        if ($user->droit === 'SupAdmin' || $user->userHasPermission($permission)) {
            return;
        }

        abort(403);
    }

    protected function ownedAnnouncementQuery(int $id)
    {
        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;
        $isSupAdmin = $user->droit === 'SupAdmin';

        return DB::table('annonces_admin_gestionnaire')
            ->where('id_annonce', $id)
            ->where(function ($query) use ($schoolId, $isSupAdmin) {
                $query->where('id_ecole', $schoolId);
                // Sinon le SupAdmin ne pourrait jamais republier/archiver/supprimer
                // une annonce globale (id_ecole = null) qu'il a lui-même créée.
                if ($isSupAdmin) {
                    $query->orWhereNull('id_ecole');
                }
            });
    }

    /**
     * Envoie une notification (cloche) à tous les comptes Admin de la plateforme
     * quand une annonce globale est publiée — le système d'annonces n'a sinon
     * aucune notification active, seulement une pop-up passive à la connexion.
     */
    protected function notifyAllAdmins(int $announcementId, string $titre): void
    {
        if (!Schema::hasTable('app_notifications')) {
            return;
        }

        User::where('droit', 'Admin')->pluck('idUtilisateur')->each(function ($id) use ($announcementId, $titre) {
            AppNotification::create([
                'user_id' => $id,
                'type' => 'annonce_globale',
                'title' => 'Nouvelle annonce de la plateforme',
                'message' => $titre,
                'link' => route('annonces.index', [], false),
                'data' => ['id_annonce' => $announcementId],
            ]);
        });
    }

    protected function storeAttachments(Request $request): array
    {
        if (!$request->hasFile('fichiers')) {
            return [];
        }

        $directory = public_path('annonces');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $titles = $request->input('titres_fichiers', []);
        $stored = [];
        foreach ($request->file('fichiers', []) as $index => $file) {
            if (!$file) {
                continue;
            }

            $fileName = uniqid('annonce_', true) . '.' . $file->getClientOriginalExtension();
            $file->move($directory, $fileName);
            $stored[] = [
                'title' => $titles[$index] ?? null,
                'path' => 'annonces/' . $fileName,
                'original' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        }

        return $stored;
    }

    protected function insertAttachmentRows(int $announcementId, array $files): void
    {
        if (empty($files) || !Schema::hasTable('annonces_fichiers')) {
            return;
        }

        DB::table('annonces_fichiers')->insert(array_map(fn ($file) => [
            'id_annonce' => $announcementId,
            'type_annonce' => 'admin_gestionnaire',
            'titre' => $file['title'],
            'nom_fichier' => $file['path'],
            'nom_original' => $file['original'],
            'type_mime' => $file['mime'],
            'taille' => $file['size'],
            'date_ajout' => now(),
        ], $files));
    }

    protected function filesByAnnouncement(array $ids)
    {
        if (empty($ids) || !Schema::hasTable('annonces_fichiers')) {
            return collect();
        }

        return DB::table('annonces_fichiers')
            ->whereIn('id_annonce', $ids)
            ->where('type_annonce', 'admin_gestionnaire')
            ->orderBy('id_fichier')
            ->get()
            ->groupBy('id_annonce');
    }

    protected function optionalAnnouncementColumns(array $values): array
    {
        return collect($values)
            ->filter(fn ($value, $column) => Schema::hasColumn('annonces_admin_gestionnaire', $column))
            ->all();
    }

    protected function visibleUnreadAnnouncementIds()
    {
        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;
        if (!$schoolId || !Schema::hasTable('annonces_admin_gestionnaire')) {
            return collect();
        }

        return $this->visibleAnnouncementQuery($user, $schoolId)
            ->when(Schema::hasTable('annonces_lues'), function ($query) use ($user) {
                $query->whereNotExists(function ($inner) use ($user) {
                    $inner->select(DB::raw(1))
                        ->from('annonces_lues as lues')
                        ->whereColumn('lues.id_annonce', 'annonces.id_annonce')
                        ->where('lues.id_utilisateur', $user->idUtilisateur)
                        ->where('lues.type_annonce', 'admin_gestionnaire');
                });
            })
            ->pluck('annonces.id_annonce');
    }

    public static function visibleAnnouncementQuery($user, int $schoolId)
    {
        $targets = ['tous'];
        if ($user->droit === 'parent') {
            $targets[] = 'parents';
            $targets[] = 'parent';
        } elseif ($user->droit === 'enseignant') {
            $targets[] = 'enseignants';
            $targets[] = 'enseignant';
        } else {
            $targets[] = 'gestionnaires';
            $targets[] = 'administration';
        }

        return DB::table('annonces_admin_gestionnaire as annonces')
            ->where(function ($query) use ($schoolId, $targets, $user) {
                $query->where(function ($local) use ($schoolId, $targets) {
                    $local->where('annonces.id_ecole', $schoolId)
                        ->whereIn('annonces.public_cible', $targets);
                });
                // Diffusion globale du SupAdmin (id_ecole = null) : réservée aux Admin.
                if ($user->droit === 'Admin') {
                    $query->orWhere(function ($global) {
                        $global->whereNull('annonces.id_ecole')
                            ->where('annonces.public_cible', 'admins');
                    });
                }
            })
            ->when(Schema::hasColumn('annonces_admin_gestionnaire', 'statut_annonce'), fn ($query) => $query->where('annonces.statut_annonce', 'publie'))
            ->orderByDesc('annonces.date_publication')
            ->orderByDesc('annonces.id_annonce');
    }
}
