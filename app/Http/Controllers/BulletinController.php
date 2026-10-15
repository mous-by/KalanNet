<?php

namespace App\Http\Controllers;

use App\Support\SchemaExiste;
use App\Models\Eleve;
use App\Models\Classe;
use App\Models\AnneeScolaire;
use App\Models\Trimestre;
use App\Services\Bulletins\CalculBulletin;
use App\Services\Bulletins\MoyennesAnnuelles;
use App\Support\ArabePdf;
use App\Support\ModelesBulletin;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BulletinController extends Controller
{
    public function classes()
    {
        $this->authorizeBulletinGeneration();

        $user = auth()->user();
        $idEcole = session('idEcole') ?: $user->idEcole;

        $classes = Classe::query()
            ->avecNotes()
            ->with(['ecole', 'classeOfficielle'])
            ->withCount(['eleves' => fn ($query) => $query->where('etat_dossier', 0)])
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->where('idEcole', $idEcole))
            ->orderBy('ordreEnseignement')
            ->orderBy('nom_classe')
            ->get();

        return view('bulletins.classes', compact('classes'));
    }

    public function index(int $idClasse)
    {
        abort_if(\App\Support\Ordres::sansNotes(Classe::find($idClasse)?->ordreEnseignement), 404);
        $this->authorizeBulletinGeneration();

        $classe = Classe::findOrFail($idClasse);
        $this->authorizeClasse($classe);

        return view('bulletins.index', [
            'classe' => $classe,
            'modeles' => ModelesBulletin::disponibles((int) $classe->idEcole),
            'modeleActuel' => ModelesBulletin::pourClasse($classe),
            'nbMatieres' => $classe->ligneClasses()->count(),
            'annees' => AnneeScolaire::orderByDesc('date_debut')->get(),
            'trimestres' => Trimestre::orderBy('id_trimestre')->get(),
            'moisOptions' => $this->moisOptions(),
        ]);
    }

    public function data(int $idClasse, Request $request)
    {
        $this->authorizeBulletinGeneration();

        $classe = Classe::findOrFail($idClasse);
        $this->authorizeClasse($classe);

        $data = $request->validate([
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'id_trimestre' => 'nullable|integer|exists:trimestre,id_trimestre',
            'mois' => 'nullable|integer|between:1,12',
        ]);

        $periode = !empty($data['mois'])
            ? ['column' => 'mois', 'value' => $data['mois']]
            : ['column' => 'id_trimestre', 'value' => $data['id_trimestre'] ?? null];

        if (!$periode['value']) {
            return response()->json([]);
        }

        $modele = ModelesBulletin::pourClasse($classe);
        $calcul = CalculBulletin::pour(
            $classe,
            (int) $data['id_annee'],
            $periode['column'] === 'id_trimestre' ? (int) $periode['value'] : null,
            $periode['column'] === 'mois' ? (int) $periode['value'] : null,
            ModelesBulletin::calcul($modele)
        );

        $ranked = $calcul->resultats()
            ->filter(fn ($r) => $r['moyenne_periode'] > 0)
            ->map(fn ($r) => [
                'id_eleve' => $r['apercu']->id_eleve,
                'matricule' => $r['apercu']->matricule,
                'nom_eleve' => $r['apercu']->nom_eleve,
                'prenom_eleve' => $r['apercu']->prenom_eleve,
                'genre_eleve' => $r['apercu']->genre_eleve,
                'moyenne' => $r['moyenne_periode'],
                'rang' => $r['rang'],
            ])
            ->sort(function ($a, $b) {
                if ($a['rang'] === $b['rang']) {
                    if (($a['genre_eleve'] ?? '') !== ($b['genre_eleve'] ?? '')) {
                        return ($a['genre_eleve'] ?? '') === 'F' ? -1 : 1;
                    }

                    return strcmp($a['nom_eleve'].$a['prenom_eleve'], $b['nom_eleve'].$b['prenom_eleve']);
                }

                return $a['rang'] <=> $b['rang'];
            })
            ->values();
        $compte = $ranked->countBy('rang');
        $ranked = $ranked->map(fn ($row) => $row + ['exaequo' => $compte[$row['rang']] > 1]);

        return response()->json($ranked);
    }

    public function generatorPage(int $idClasse, Request $request)
    {
        $this->authorizeBulletinGeneration();
        $classe = Classe::findOrFail($idClasse);
        $this->authorizeClasse($classe);

        return view('bulletins.generator', [
            'classe'       => $classe,
            'id_annee'     => $request->get('id_annee', ''),
            'id_trimestre' => $request->get('id_trimestre', ''),
            'mois'         => $request->get('mois', ''),
            'ids'          => $request->get('ids', '[]'),
            'fin_annee'    => $request->boolean('fin_annee'),
            'modele'       => ModelesBulletin::pourClasse($classe),
        ]);
    }

    public function studentsForBulletin(int $idClasse, Request $request)
    {
        $this->authorizeBulletinGeneration();
        $classe = Classe::findOrFail($idClasse);
        $this->authorizeClasse($classe);

        $data = $request->validate([
            'id_annee'     => 'required|integer',
            'id_trimestre' => 'nullable|integer',
            'mois'         => 'nullable|integer|between:1,12',
            'ids'          => 'nullable|string',
        ]);

        $selectedIds = collect();
        if (!empty($data['ids'])) {
            $decoded = json_decode($data['ids'], true);
            if (is_array($decoded)) {
                $selectedIds = collect($decoded)
                    ->filter(fn($id) => is_numeric($id))
                    ->map(fn($id) => (int) $id)
                    ->unique()
                    ->values();
            }
        }

        $query = Eleve::query()
            ->where('id_classe', $classe->id_classe)
            ->where('id_annee', $data['id_annee'])
            ->where('etat_dossier', 0)
            ->orderBy('nom_eleve')
            ->orderBy('prenom_eleve');

        if ($selectedIds->isNotEmpty()) {
            $query->whereIn('id_eleve', $selectedIds);
        }

        $params = ['id_annee' => $data['id_annee']];
        if (!empty($data['mois'])) {
            $params['mois'] = $data['mois'];
            if ($request->boolean('fin_annee')) {
                $params['fin_annee'] = 1;
            }
        } elseif (!empty($data['id_trimestre'])) {
            $params['id_trimestre'] = $data['id_trimestre'];
        }

        $students = $query->get()->map(fn($eleve) => [
            'id'        => $eleve->id_eleve,
            'nom'       => $eleve->nom_eleve,
            'prenom'    => $eleve->prenom_eleve,
            'matricule' => $eleve->matricule,
            'url'       => route('pedagogie.bulletins.download', $eleve->id_eleve) . '?' . http_build_query($params),
        ]);

        return response()->json($students);
    }

    public function downloadBulletin($id_eleve, Request $request)
    {
        set_time_limit(180);
        $id_annee = $request->get('id_annee');
        $id_trimestre = $request->get('id_trimestre');
        $mois = $request->get('mois');

        if (!$id_annee) {
            return back()->with('error', "Année scolaire manquante !");
        }

        $this->authorizeParentBulletinAccess((int) $id_eleve, (int) $id_annee, $id_trimestre ? (int) $id_trimestre : null, $mois ? (int) $mois : null);

        // Aperçu d'un autre modèle avant de le choisir (réservé aux gestionnaires).
        $modele = $request->get('modele');
        if (!ModelesBulletin::existe($modele) || Auth::user()?->droit === 'parent') {
            $modele = null;
        }

        $data = $this->getBulletinData($id_eleve, $id_annee, $id_trimestre, $mois, true, $modele);

        if (!$data) {
            return back()->with('error', "Données de bulletin indisponibles.");
        }

        $pdf = Pdf::loadView('pdf.bulletin', ['bulletins' => collect([$data]), 'modele' => $data['modele']]);
        $pdf->setPaper(ModelesBulletin::format($data['modele']), ModelesBulletin::orientation($data['modele']));

        return $pdf->stream('Bulletin_' . ($data['apercu']->nom_eleve ?? 'Eleve') . '.pdf');
    }

    public function downloadClassBulletins(int $idClasse, Request $request)
    {
        set_time_limit(300);
        $this->authorizeBulletinGeneration();

        $classe = Classe::findOrFail($idClasse);
        $this->authorizeClasse($classe);

        $data = $request->validate([
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'id_trimestre' => 'nullable|integer|exists:trimestre,id_trimestre',
            'mois' => 'nullable|integer|between:1,12',
            'ids' => 'nullable|string',
            'eleves' => 'nullable|array',
            'eleves.*' => 'integer|exists:eleve,id_eleve',
        ]);

        $periode = !empty($data['mois'])
            ? ['column' => 'mois', 'value' => $data['mois']]
            : ['column' => 'id_trimestre', 'value' => $data['id_trimestre'] ?? null];

        if (!$periode['value']) {
            return back()->with('error', "Sélectionnez l'année scolaire et la période avant d'imprimer.");
        }

        $selectedIds = collect($data['eleves'] ?? []);
        if (!empty($data['ids'])) {
            $decodedIds = json_decode($data['ids'], true);
            if (is_array($decodedIds)) {
                $selectedIds = collect($decodedIds);
            }
        }

        $selectedIds = $selectedIds
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $studentQuery = Eleve::query()
            ->where('id_classe', $classe->id_classe)
            ->where('id_annee', $data['id_annee'])
            ->where('etat_dossier', 0)
            ->orderBy('nom_eleve')->orderBy('prenom_eleve');

        if ($selectedIds->isNotEmpty()) {
            $studentQuery->whereIn('id_eleve', $selectedIds);
        }

        $modele = ModelesBulletin::pourClasse($classe);
        // Élèves et classe chargés une seule fois pour toute la classe.
        $bulletins = $studentQuery
            ->with('classe')
            ->get()
            ->map(fn (Eleve $eleve) => $this->getBulletinData(
                $eleve,
                (int) $data['id_annee'],
                $periode['column'] === 'id_trimestre' ? $periode['value'] : null,
                $periode['column'] === 'mois' ? $periode['value'] : null
            ))
            ->filter()
            ->values();

        if ($bulletins->isEmpty()) {
            return back()->with('error', "Aucun bulletin disponible pour cette sélection.");
        }

        $periodeLabel = $periode['column'] === 'mois'
            ? ($this->moisOptions()[(int) $periode['value']] ?? 'Mois sélectionné')
            : (Trimestre::find($periode['value'])->nom_trimestre ?? 'Période sélectionnée');

        $pdf = Pdf::loadView('pdf.bulletin', [
            'bulletins' => $bulletins,
            'modele' => $modele,
        ]);
        $pdf->setPaper(ModelesBulletin::format($modele), ModelesBulletin::orientation($modele));

        $fileName = 'Bulletins_' . str_replace(' ', '_', $classe->nom_classe) . '_' . str_replace(' ', '_', $periodeLabel) . '.pdf';

        return $pdf->stream($fileName);
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
            return back()->with('error', 'Sélectionnez la période à publier.');
        }

        if (!SchemaExiste::table('bulletin_publications')) {
            return back()->with('error', 'Le module de publication des bulletins n’est pas encore migré.');
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
                'published_by' => Auth::id(),
                'published_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('success', 'Bulletins publiés pour les parents.');
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

        if (SchemaExiste::table('bulletin_publications')) {
            DB::table('bulletin_publications')
                ->where('id_ecole', $classe->idEcole)
                ->where('id_classe', $classe->id_classe)
                ->where('id_annee', $data['id_annee'])
                ->where('id_trimestre', ($data['mois'] ?? null) ? null : ($data['id_trimestre'] ?? null))
                ->where('mois', $data['mois'] ?? null)
                ->delete();
        }

        return back()->with('success', 'Publication des bulletins retirée pour cette période.');
    }

    /**
     * Données du bulletin d'un élève, calculées avec toute la classe (rang,
     * moyenne du premier). $modele : modèle demandé, sinon celui de la classe.
     */
    protected function getBulletinData($id, $id_annee, $id_trimestre = null, $mois = null, bool $withRank = true, ?string $modele = null)
    {
        // $id : identifiant de l'élève, ou l'élève déjà chargé avec sa classe (bulletins de toute une classe).
        $eleve = $id instanceof Eleve ? $id : Eleve::with('classe')->findOrFail($id);
        $id = $eleve->id_eleve;
        $classe = $eleve->classe;
        $this->authorizeClasse($classe);

        $modele = ModelesBulletin::disponible($modele, (int) $classe->idEcole) ? $modele : ModelesBulletin::pourClasse($classe);
        $calcul = CalculBulletin::pour($classe, (int) $id_annee, $mois ? null : ($id_trimestre ? (int) $id_trimestre : null), $mois ? (int) $mois : null, ModelesBulletin::calcul($modele));

        $data = $calcul->pourEleve((int) $id);
        if (!$data) {
            return null;
        }

        $data['modele'] = $modele;
        $data['statistiques'] = $calcul->statistiques();

        // Bulletin de fin d'année : moyenne et rang annuels (moyenne des périodes composées).
        $annuel = MoyennesAnnuelles::pour($classe, (int) $id_annee, ModelesBulletin::calcul($modele));
        $data['annuel'] = null;
        // Compositions mensuelles : seulement si l'école imprime les bulletins de fin d'année.
        if ((!$mois || request()->boolean('fin_annee'))
            && $annuel->estDernierePeriode($mois ? null : ($id_trimestre ? (int) $id_trimestre : null), $mois ? (int) $mois : null)
            && ($resultatAnnuel = $annuel->pourEleve((int) $id))) {
            $data['annuel'] = $resultatAnnuel + [
                'libelle' => $resultatAnnuel['nb_periodes'].' '.($annuel->mensuel() ? 'composition(s) mensuelle(s)' : 'trimestre(s)'),
            ];
        }
        // Secondaire général (10ème, 11ème) : tendance d'orientation vers une série.
        $data['tendance'] = $data['annuel'] ? \App\Support\Series::tendance($classe, $data['annuel']['matieres'] ?? []) : null;
        $data['maitres'] = $this->maitres($classe);
        $data['annee_hegirienne'] = $this->anneeHegirienne($data['apercu']);
        $data['photo'] = $this->imageBase64($eleve->image);
        if ($modele === 'principales') {
            $data['lv2'] = $eleve->id_matiere_lv2
                ? trim(preg_replace('/\s*LV2$/i', '', (string) DB::table('matiere')->where('id_matiere', $eleve->id_matiere_lv2)->value('nom_matiere')))
                : null;
        }

        return $data;
    }

    /** Maîtres d'une classe à deux maîtres (partie arabe / partie française). */
    private function maitres(Classe $classe): array
    {
        if (!$classe->deux_maitres) {
            return [];
        }
        $noms = DB::table('enseignants')->whereIn('id_enseignant', array_filter([$classe->id_maitre_arabe, $classe->id_maitre_francais]))
            ->pluck('nom_prenom_enseignant', 'id_enseignant');

        return [
            'arabe' => $noms[$classe->id_maitre_arabe] ?? null,
            'francais' => $noms[$classe->id_maitre_francais] ?? null,
        ];
    }

    /** « 1447 - 1448 » : années de l'hégire couvertes par l'année scolaire. */
    private function anneeHegirienne(object $apercu): ?string
    {
        if (empty($apercu->annee_debut) || empty($apercu->annee_fin)) {
            return null;
        }
        $debut = ArabePdf::anneeHegirienne(Carbon::parse($apercu->annee_debut));
        $fin = ArabePdf::anneeHegirienne(Carbon::parse($apercu->annee_fin));

        return $debut === $fin ? $debut : $debut.' - '.$fin;
    }

    private function imageBase64(?string $chemin): ?string
    {
        if (!$chemin) {
            return null;
        }
        $fichier = public_path($chemin);
        if (!is_file($fichier) || filesize($fichier) > 3 * 1024 * 1024) {
            return null;
        }
        $extension = strtolower(pathinfo($fichier, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($fichier));
    }

    /** Enregistre le modèle de bulletin de la classe. */
    public function reglages(int $idClasse, Request $request)
    {
        $this->authorizeBulletinGeneration();
        $classe = Classe::findOrFail($idClasse);
        $this->authorizeClasse($classe);

        $data = $request->validate([
            'modele_bulletin' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(ModelesBulletin::disponibles((int) $classe->idEcole)))],
        ]);

        $classe->update($data);

        return response()->json([
            'modele_bulletin' => $classe->modele_bulletin,
        ]);
    }

    protected function authorizeClasse(?Classe $classe): void
    {
        // Classe invisible pour l'école active (autre école ou supprimée) : 404.
        abort_if($classe === null, 404);

        $user = auth()->user();
        if ($user->droit !== 'SupAdmin' && (int) $classe->idEcole !== (int) (session('idEcole') ?: $user->idEcole)) {
            abort(403);
        }
    }

    protected function authorizeBulletinGeneration(): void
    {
        $user = Auth::user();

        if (!$user || ($user->droit !== 'SupAdmin' && !$user->userHasAnyPermission([
            'bulletins_apercu',
            'bulletins_generation',
            'bulletins_génération',
            'bulletins_pdf',
            'bulletins_impression',
            'bulletins_publication',
            'generer_bulletins',
            'générer_bulletins',
        ]))) {
            abort(403);
        }
    }

    protected function moisOptions(): array
    {
        return [1 => "Janvier", 2 => "Février", 3 => "Mars", 4 => "Avril", 5 => "Mai", 6 => "Juin", 7 => "Juillet", 8 => "Août", 9 => "Septembre", 10 => "Octobre", 11 => "Novembre", 12 => "Décembre"];
    }

    protected function authorizeBulletinPublication(): void
    {
        $user = Auth::user();

        if ($user->droit !== 'SupAdmin' && !$user->userHasPermission('bulletins_publication')) {
            abort(403);
        }
    }

    protected function authorizeParentBulletinAccess(int $idEleve, int $idAnnee, ?int $idTrimestre, ?int $mois): void
    {
        $user = Auth::user();
        if ($user?->droit !== 'parent') {
            $this->authorizeBulletinGeneration();
            return;
        }

        $eleve = Eleve::with('parents')->findOrFail($idEleve);
        $parentId = $user->id_parent ?: $user->parent?->id_parent;
        $isOwnChild = $parentId && $eleve->parents->contains('id_parent', $parentId);

        if (!$isOwnChild) {
            abort(403);
        }

        if (!SchemaExiste::table('bulletin_publications')) {
            abort(403, 'Bulletin non publié.');
        }

        $published = DB::table('bulletin_publications')
            ->where('id_ecole', $eleve->id_ecole)
            ->where('id_classe', $eleve->id_classe)
            ->where('id_annee', $idAnnee)
            ->where('id_trimestre', $mois ? null : $idTrimestre)
            ->where('mois', $mois)
            ->whereNotNull('published_at')
            ->exists();

        if (!$published) {
            abort(403, 'Bulletin non publié.');
        }
    }
}
