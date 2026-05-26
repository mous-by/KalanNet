<?php

namespace App\Http\Controllers;

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
        $schoolId = session('idEcole') ?: Auth::user()->idEcole;

        $annonces = collect();
        if ($schoolId && Schema::hasTable('annonces_admin_gestionnaire')) {
            $annonces = DB::table('annonces_admin_gestionnaire as annonces')
                ->leftJoin('utilisateurs as users', 'users.idUtilisateur', '=', 'annonces.id_utilisateur')
                ->where('annonces.id_ecole', $schoolId)
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
        $schoolId = session('idEcole') ?: Auth::user()->idEcole;

        if (!$schoolId || !Schema::hasTable('annonces_admin_gestionnaire')) {
            return back()->with('error', 'Le module des annonces n’est pas encore disponible.');
        }

        $data = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'public_cible' => 'required|string|in:tous,parents,enseignants,gestionnaires',
            'statut_annonce' => 'required|string|in:publie,brouillon,archive',
            'fichiers' => 'nullable|array',
            'fichiers.*' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            'titres_fichiers' => 'nullable|array',
            'titres_fichiers.*' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $data, $schoolId) {
            $storedFiles = $this->storeAttachments($request);
            $firstFile = $storedFiles[0] ?? [];

            $announcementId = DB::table('annonces_admin_gestionnaire')->insertGetId(array_merge([
                'id_ecole' => $schoolId,
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
        });

        return back()->with('success', 'Annonce enregistrée avec succès.');
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

    private function authorizeAnnouncementAccess(string $permission): void
    {
        $user = Auth::user();
        if ($user->droit === 'SupAdmin' || $user->userHasPermission($permission)) {
            return;
        }

        abort(403);
    }

    private function ownedAnnouncementQuery(int $id)
    {
        $schoolId = session('idEcole') ?: Auth::user()->idEcole;

        return DB::table('annonces_admin_gestionnaire')
            ->where('id_annonce', $id)
            ->where('id_ecole', $schoolId);
    }

    private function storeAttachments(Request $request): array
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

    private function insertAttachmentRows(int $announcementId, array $files): void
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

    private function filesByAnnouncement(array $ids)
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

    private function optionalAnnouncementColumns(array $values): array
    {
        return collect($values)
            ->filter(fn ($value, $column) => Schema::hasColumn('annonces_admin_gestionnaire', $column))
            ->all();
    }

    private function visibleUnreadAnnouncementIds()
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
            ->where('annonces.id_ecole', $schoolId)
            ->whereIn('annonces.public_cible', $targets)
            ->when(Schema::hasColumn('annonces_admin_gestionnaire', 'statut_annonce'), fn ($query) => $query->where('annonces.statut_annonce', 'publie'))
            ->orderByDesc('annonces.date_publication')
            ->orderByDesc('annonces.id_annonce');
    }
}
