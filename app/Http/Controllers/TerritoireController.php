<?php

namespace App\Http\Controllers;

use App\Support\Territoire\AnnoncesTerritoire;
use App\Support\Territoire\StatistiquesTerritoire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

/**
 * Espaces de supervision : le DAE suit toute son académie (CAP, écoles,
 * effectifs), le DCAP tout son CAP (écoles, effectifs). Chacun publie des
 * annonces à son territoire, ciblées par rôle.
 */
class TerritoireController extends Controller
{
    public function espace()
    {
        $user = Auth::user();

        if ($user->droit === 'DAE') {
            abort_unless($user->id_academie, 403, "Votre compte DAE n'est rattaché à aucune académie.");
            $academie = DB::table('academie')->where('id_academie', $user->id_academie)->first();

            return view('dashboards.dae', [
                'academie' => $academie,
                'stats' => StatistiquesTerritoire::academie((int) $user->id_academie),
                'annonces' => AnnoncesTerritoire::publiees($user),
                'cibles' => AnnoncesTerritoire::CIBLES,
            ]);
        }

        if ($user->droit === 'DCAP') {
            abort_unless($user->id_cap, 403, "Votre compte DCAP n'est rattaché à aucun CAP.");
            $cap = DB::table('cap as c')->leftJoin('academie as a', 'a.id_academie', '=', 'c.id_academie')
                ->where('c.id_cap', $user->id_cap)->first(['c.*', 'a.nom_academie']);

            return view('dashboards.dcap', [
                'cap' => $cap,
                'stats' => StatistiquesTerritoire::cap((int) $user->id_cap),
                'annonces' => AnnoncesTerritoire::publiees($user),
                'annoncesAcademie' => AnnoncesTerritoire::visibles($user, null, 10),
                'cibles' => collect(AnnoncesTerritoire::CIBLES)->except('dcap')->all(),
            ]);
        }

        abort(403);
    }

    /** Annonces du territoire : publication et liste (DAE : académie ; DCAP : CAP + annonces reçues de l'académie). */
    public function annonces()
    {
        $user = Auth::user();
        abort_unless(($user->droit === 'DAE' && $user->id_academie) || ($user->droit === 'DCAP' && $user->id_cap), 403);

        return view('territoire.annonces', [
            'annonces' => AnnoncesTerritoire::publiees($user),
            'annoncesAcademie' => $user->droit === 'DCAP' ? AnnoncesTerritoire::visibles($user, null, 10) : collect(),
            'cibles' => $user->droit === 'DAE' ? AnnoncesTerritoire::CIBLES : collect(AnnoncesTerritoire::CIBLES)->except('dcap')->all(),
        ]);
    }

    /** Effectifs (lecture seule) : élèves par classe et enseignants, école par école. */
    public function effectifs(Request $request)
    {
        $user = Auth::user();
        abort_unless(($user->droit === 'DAE' && $user->id_academie) || ($user->droit === 'DCAP' && $user->id_cap), 403);

        $caps = $user->droit === 'DAE'
            ? DB::table('cap')->where('id_academie', $user->id_academie)->orderBy('nom_cap')->get(['id_cap', 'nom_cap'])
            : collect();
        $capChoisi = $user->droit === 'DAE' ? $request->integer('cap') ?: null : (int) $user->id_cap;
        $recherche = trim((string) $request->query('recherche', ''));

        $ecoles = DB::table('ecole as e')
            ->leftJoin('cap as c', 'c.id_cap', '=', 'e.id_cap')
            ->when($user->droit === 'DAE', fn ($q) => $q->where('e.id_academie', $user->id_academie))
            ->when($capChoisi, fn ($q) => $q->where('e.id_cap', $capChoisi))
            ->when($recherche !== '', fn ($q) => $q->where('e.nomEcole', 'like', '%'.$recherche.'%'))
            ->orderBy('c.nom_cap')->orderBy('e.nomEcole')
            ->get(['e.idEcole', 'e.nomEcole', 'e.statut', 'c.nom_cap']);

        $effectifs = StatistiquesTerritoire::effectifs($ecoles);

        return view('territoire.effectifs', compact('effectifs', 'caps', 'capChoisi', 'recherche'));
    }

    public function storeAnnonce(Request $request)
    {
        $user = Auth::user();
        [$table, $colonne, $id] = AnnoncesTerritoire::tablePour($user);
        abort_unless($table && $id, 403);

        $cibles = array_keys(AnnoncesTerritoire::CIBLES);
        if ($user->droit === 'DCAP') {
            $cibles = array_values(array_diff($cibles, ['dcap']));
        }

        $data = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string|max:5000',
            'public_cible' => ['required', Rule::in($cibles)],
            'type_annonce' => 'nullable|in:information,urgence,pedagogique',
            'fichier' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt',
        ]);

        $fichier = null;
        if ($request->hasFile('fichier')) {
            $dossier = public_path('uploads/annonces_territoire');
            File::ensureDirectoryExists($dossier, 0755);
            $upload = $request->file('fichier');
            $nom = \App\Support\FichierTeleverse::nom($upload, 'annonce_', \App\Support\FichierTeleverse::DOCUMENTS, 'fichier');
            $fichier = [
                'fichier_joint' => 'uploads/annonces_territoire/'.$nom,
                'type_fichier' => $upload->getClientMimeType(),
                'taille_fichier' => $upload->getSize(),
            ];
            $upload->move($dossier, $nom);
        }

        DB::table($table)->insert(array_merge([
            $colonne => $id,
            'titre' => $data['titre'],
            'contenu' => $data['contenu'],
            'public_cible' => $data['public_cible'],
            'id_utilisateur' => $user->idUtilisateur,
            'date_publication' => now(),
            'statut_annonce' => 'active',
            'type_annonce' => $data['type_annonce'] ?? 'information',
        ], $fichier ?? []));

        $destinataires = match (true) {
            $user->droit !== 'DAE' => 'à votre CAP.',
            $data['public_cible'] === 'dcap' => "aux directeurs de CAP de l'académie.",
            default => "aux établissements du secondaire de l'académie.",
        };

        return back()->with('success', 'Annonce publiée '.$destinataires);
    }

    public function destroyAnnonce(int $id)
    {
        $user = Auth::user();
        [$table, $colonne, $territoire] = AnnoncesTerritoire::tablePour($user);
        abort_unless($table && $territoire, 403);

        $annonce = DB::table($table)->where('id_annonce', $id)->where($colonne, $territoire)->first();
        abort_unless($annonce, 404);

        DB::table($table)->where('id_annonce', $id)->delete();
        DB::table('annonces_lues')->where('id_annonce', $id)->where('type_annonce', $user->droit === 'DAE' ? 'ACADEMIE' : 'CAP')->delete();
        if ($annonce->fichier_joint && str_starts_with($annonce->fichier_joint, 'uploads/annonces_territoire/')) {
            File::delete(public_path($annonce->fichier_joint));
        }

        return back()->with('success', 'Annonce supprimée.');
    }
}
