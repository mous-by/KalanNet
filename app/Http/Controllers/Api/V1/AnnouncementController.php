<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\AnnouncementController as WebAnnouncementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class AnnouncementController extends WebAnnouncementController
{
    public function index()
    {
        $this->authorizeAnnouncementAccess('annonces_apercu');
        $user = request()->user();
        $schoolId = session('idEcole') ?: $user->idEcole;
        $isSupAdmin = $user->droit === 'SupAdmin';

        $annonces = collect();
        if (($schoolId || $isSupAdmin) && Schema::hasTable('annonces_admin_gestionnaire')) {
            $annonces = DB::table('annonces_admin_gestionnaire as annonces')
                ->leftJoin('utilisateurs as users', 'users.idUtilisateur', '=', 'annonces.id_utilisateur')
                ->where(function ($query) use ($schoolId, $isSupAdmin) {
                    $query->where('annonces.id_ecole', $schoolId);
                    if ($isSupAdmin) {
                        $query->orWhereNull('annonces.id_ecole');
                    }
                })
                ->select('annonces.*', 'users.nomPrenom as auteur')
                ->orderByDesc('annonces.date_publication')
                ->orderByDesc('annonces.id_annonce')
                ->paginate(15);
        }

        $items = method_exists($annonces, 'items') ? collect($annonces->items()) : collect($annonces);

        return response()->json([
            'annonces' => $annonces,
            'fichiers' => $this->filesByAnnouncement($items->pluck('id_annonce')->all()),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAnnouncementAccess('annonces_creation');
        $user = $request->user();
        $schoolId = session('idEcole') ?: $user->idEcole;
        $isGlobal = $user->droit === 'SupAdmin' && $request->boolean('global');

        if (!Schema::hasTable('annonces_admin_gestionnaire') || (!$isGlobal && !$schoolId)) {
            return response()->json(['message' => 'Le module des annonces n’est pas encore disponible.'], 422);
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
            $data['public_cible'] = 'admins';
        } elseif ($data['public_cible'] === 'admins') {
            abort(422, 'Cible invalide pour une annonce d’école.');
        }

        $announcementId = DB::transaction(function () use ($request, $data, $schoolId, $isGlobal) {
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

            return $announcementId;
        });

        return response()->json(['id_annonce' => $announcementId], 201);
    }

    public function markVisibleAsRead()
    {
        if (!Schema::hasTable('annonces_lues')) {
            return response()->json(['success' => true]);
        }

        foreach ($this->visibleUnreadAnnouncementIds() as $id) {
            $readPayload = ['date_lecture' => now()];
            if (Schema::hasColumn('annonces_lues', 'created_at')) {
                $readPayload['created_at'] = now();
            }
            if (Schema::hasColumn('annonces_lues', 'updated_at')) {
                $readPayload['updated_at'] = now();
            }

            DB::table('annonces_lues')->updateOrInsert([
                'id_utilisateur' => Auth::id(),
                'id_annonce' => $id,
                'type_annonce' => 'admin_gestionnaire',
            ], $readPayload);
        }

        return response()->json(['success' => true]);
    }

    public function publish(int $id)
    {
        $this->authorizeAnnouncementAccess('annonces_creation');
        $updates = $this->optionalAnnouncementColumns(['statut_annonce' => 'publie', 'date_publication' => now()]);
        if (!empty($updates)) {
            $this->ownedAnnouncementQuery($id)->update($updates);
        }

        return response()->json(['success' => true]);
    }

    public function archive(int $id)
    {
        $this->authorizeAnnouncementAccess('annonces_creation');
        $updates = $this->optionalAnnouncementColumns(['statut_annonce' => 'archive']);
        if (!empty($updates)) {
            $this->ownedAnnouncementQuery($id)->update($updates);
        }

        return response()->json(['success' => true]);
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
            DB::table('annonces_fichiers')->where('id_annonce', $id)->where('type_annonce', 'admin_gestionnaire')->delete();
        }

        $this->ownedAnnouncementQuery($id)->delete();

        return response()->json(['success' => true]);
    }
}
