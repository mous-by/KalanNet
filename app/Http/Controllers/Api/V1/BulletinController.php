<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BulletinController as WebBulletinController;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Trimestre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BulletinController extends WebBulletinController
{
    public function classes()
    {
        $this->authorizeBulletinGeneration();

        $user = request()->user();
        $idEcole = session('idEcole') ?: $user->idEcole;

        $classes = Classe::query()
            ->with(['ecole', 'classeOfficielle'])
            ->withCount(['eleves' => fn ($query) => $query->where('etat_dossier', 0)])
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->where('idEcole', $idEcole))
            ->orderBy('ordreEnseignement')
            ->orderBy('nom_classe')
            ->get();

        return response()->json(['data' => $classes]);
    }

    public function index(int $idClasse)
    {
        $this->authorizeBulletinGeneration();

        $classe = Classe::findOrFail($idClasse);
        $this->authorizeClasse($classe);

        return response()->json([
            'classe' => $classe,
            'annees' => AnneeScolaire::orderByDesc('date_debut')->get(),
            'trimestres' => Trimestre::orderBy('id_trimestre')->get(),
            'mois_options' => $this->moisOptions(),
        ]);
    }

    // data() is already JSON in the web controller and reused unmodified via inheritance.
    // downloadBulletin() and downloadClassBulletins() stream PDFs directly and are
    // routed straight to the web controller (see routes/api.php) — no override needed.

    public function studentsForBulletin(int $idClasse, Request $request)
    {
        $this->authorizeBulletinGeneration();
        $classe = Classe::findOrFail($idClasse);
        $this->authorizeClasse($classe);

        $data = $request->validate([
            'id_annee' => 'required|integer',
            'id_trimestre' => 'nullable|integer',
            'mois' => 'nullable|integer|between:1,12',
            'ids' => 'nullable|string',
        ]);

        $selectedIds = collect();
        if (!empty($data['ids'])) {
            $decoded = json_decode($data['ids'], true);
            if (is_array($decoded)) {
                $selectedIds = collect($decoded)->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique()->values();
            }
        }

        $query = Eleve::query()
            ->where('id_classe', $classe->id_classe)
            ->where('id_annee', $data['id_annee'])
            ->where('etat_dossier', 0)
            ->orderBy('prenom_eleve')
            ->orderBy('nom_eleve');

        if ($selectedIds->isNotEmpty()) {
            $query->whereIn('id_eleve', $selectedIds);
        }

        $params = ['id_annee' => $data['id_annee']];
        if (!empty($data['mois'])) {
            $params['mois'] = $data['mois'];
        } elseif (!empty($data['id_trimestre'])) {
            $params['id_trimestre'] = $data['id_trimestre'];
        }

        $students = $query->get()->map(fn ($eleve) => [
            'id' => $eleve->id_eleve,
            'nom' => $eleve->nom_eleve,
            'prenom' => $eleve->prenom_eleve,
            'matricule' => $eleve->matricule,
            // API-relative path (not the web route) so a mobile client can call it
            // directly with its Bearer token.
            'url' => '/api/v1/bulletins/' . $eleve->id_eleve . '/telecharger?' . http_build_query($params),
        ]);

        return response()->json($students);
    }

    public function publishClassBulletins(int $idClasse, Request $request)
    {
        $classe = Classe::findOrFail($idClasse);
        $this->authorizeClasse($classe);
        $this->authorizeBulletinPublication();

        $data = $request->validate([
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'id_trimestre' => 'nullable|integer|exists:trimestre,id_trimestre',
            'mois' => 'nullable|integer|between:1,12',
        ]);

        if (empty($data['id_trimestre']) && empty($data['mois'])) {
            return response()->json(['message' => 'Sélectionnez la période à publier.'], 422);
        }

        if (!Schema::hasTable('bulletin_publications')) {
            return response()->json(['message' => 'Le module de publication des bulletins n’est pas encore migré.'], 422);
        }

        DB::table('bulletin_publications')->updateOrInsert(
            [
                'id_ecole' => $classe->idEcole,
                'id_classe' => $classe->id_classe,
                'id_annee' => $data['id_annee'],
                'id_trimestre' => ($data['mois'] ?? null) ? null : ($data['id_trimestre'] ?? null),
                'mois' => $data['mois'] ?? null,
            ],
            [
                'published_by' => $request->user()->idUtilisateur,
                'published_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }

    public function unpublishClassBulletins(int $idClasse, Request $request)
    {
        $classe = Classe::findOrFail($idClasse);
        $this->authorizeClasse($classe);
        $this->authorizeBulletinPublication();

        $data = $request->validate([
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'id_trimestre' => 'nullable|integer|exists:trimestre,id_trimestre',
            'mois' => 'nullable|integer|between:1,12',
        ]);

        if (Schema::hasTable('bulletin_publications')) {
            DB::table('bulletin_publications')
                ->where('id_ecole', $classe->idEcole)
                ->where('id_classe', $classe->id_classe)
                ->where('id_annee', $data['id_annee'])
                ->where('id_trimestre', ($data['mois'] ?? null) ? null : ($data['id_trimestre'] ?? null))
                ->where('mois', $data['mois'] ?? null)
                ->delete();
        }

        return response()->json(['success' => true]);
    }
}
