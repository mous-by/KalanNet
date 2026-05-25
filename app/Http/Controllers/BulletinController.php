<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\AnneeScolaire;
use App\Models\Trimestre;
use App\Services\ConductNoteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulletinController extends Controller
{
    public function classes()
    {
        $user = auth()->user();
        $idEcole = session('idEcole') ?: $user->idEcole;

        $classes = Classe::query()
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
        $classe = Classe::findOrFail($idClasse);
        $this->authorizeClasse($classe);

        return view('bulletins.index', [
            'classe' => $classe,
            'annees' => AnneeScolaire::orderByDesc('date_debut')->get(),
            'trimestres' => Trimestre::orderBy('id_trimestre')->get(),
            'moisOptions' => $this->moisOptions(),
        ]);
    }

    public function data(int $idClasse, Request $request)
    {
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

        $rows = Eleve::query()
            ->where('id_classe', $classe->id_classe)
            ->where('id_annee', $data['id_annee'])
            ->where('etat_dossier', 0)
            ->orderBy('nom_eleve')
            ->orderBy('prenom_eleve')
            ->get()
            ->map(function ($eleve) use ($data, $periode) {
                $bulletin = $this->getBulletinData($eleve->id_eleve, $data['id_annee'], $periode['column'] === 'id_trimestre' ? $periode['value'] : null, $periode['column'] === 'mois' ? $periode['value'] : null, false);

                return [
                    'id_eleve' => $eleve->id_eleve,
                    'matricule' => $eleve->matricule,
                    'nom_eleve' => $eleve->nom_eleve,
                    'prenom_eleve' => $eleve->prenom_eleve,
                    'genre_eleve' => $eleve->genre_eleve,
                    'moyenne' => $bulletin['moyenne_periode'] ?? 0,
                ];
            })
            ->filter(fn ($row) => $row['moyenne'] > 0)
            ->sort(function ($a, $b) {
                if ((float) $a['moyenne'] === (float) $b['moyenne']) {
                    if (($a['genre_eleve'] ?? '') !== ($b['genre_eleve'] ?? '')) {
                        return ($a['genre_eleve'] ?? '') === 'F' ? -1 : 1;
                    }
                    return strcmp($a['nom_eleve'] . $a['prenom_eleve'], $b['nom_eleve'] . $b['prenom_eleve']);
                }

                return $b['moyenne'] <=> $a['moyenne'];
            })
            ->values();

        $lastMoyenne = null;
        $lastRang = 0;
        $ranked = $rows->map(function ($row, $index) use (&$lastMoyenne, &$lastRang) {
            $moyenne = round((float) $row['moyenne'], 2);
            if ($lastMoyenne !== null && $moyenne === $lastMoyenne) {
                $row['rang'] = $lastRang;
                $row['exaequo'] = true;
            } else {
                $row['rang'] = $index + 1;
                $row['exaequo'] = false;
                $lastRang = $index + 1;
            }
            $lastMoyenne = $moyenne;

            return $row;
        });

        return response()->json($ranked);
    }

    public function downloadBulletin($id_eleve, Request $request)
    {
        $id_annee = $request->get('id_annee');
        $id_trimestre = $request->get('id_trimestre');
        $mois = $request->get('mois');

        if (!$id_annee) {
            return back()->with('error', "Année scolaire manquante !");
        }

        $data = $this->getBulletinData($id_eleve, $id_annee, $id_trimestre, $mois);
        
        if (!$data) {
            return back()->with('error', "Données de bulletin indisponibles.");
        }

        $pdf = Pdf::loadView('pdf.bulletin', $data);
        $pdf->setPaper('A5', 'portrait');
        
        return $pdf->stream('Bulletin_' . ($data['apercu']->nom_eleve ?? 'Eleve') . '.pdf');
    }

    public function downloadClassBulletins(int $idClasse, Request $request)
    {
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
            ->orderBy('nom_eleve')
            ->orderBy('prenom_eleve');

        if ($selectedIds->isNotEmpty()) {
            $studentQuery->whereIn('id_eleve', $selectedIds);
        }

        $bulletins = $studentQuery
            ->pluck('id_eleve')
            ->map(fn ($idEleve) => $this->getBulletinData(
                $idEleve,
                (int) $data['id_annee'],
                $periode['column'] === 'id_trimestre' ? $periode['value'] : null,
                $periode['column'] === 'mois' ? $periode['value'] : null
            ))
            ->filter()
            ->values();

        if ($bulletins->isEmpty()) {
            return back()->with('error', "Aucun bulletin disponible pour cette sélection.");
        }

        $annee = AnneeScolaire::find($data['id_annee']);
        $periodeLabel = $periode['column'] === 'mois'
            ? ($this->moisOptions()[(int) $periode['value']] ?? 'Mois sélectionné')
            : (Trimestre::find($periode['value'])->nom_trimestre ?? 'Période sélectionnée');

        $pdf = Pdf::loadView('pdf.bulletins_classe', [
            'bulletins' => $bulletins,
            'classe' => $classe,
            'annee' => $annee,
            'periodeLabel' => $periodeLabel,
        ]);
        $pdf->setPaper('A5', 'portrait');

        $fileName = 'Bulletins_' . str_replace(' ', '_', $classe->nom_classe) . '_' . str_replace(' ', '_', $periodeLabel) . '.pdf';

        return $pdf->stream($fileName);
    }

    private function getBulletinData($id, $id_annee, $id_trimestre = null, $mois = null, bool $withRank = true)
    {
        // 1. Basic Info
        $eleve = Eleve::with('classe')->findOrFail($id);
        $classe = $eleve->classe;
        $this->authorizeClasse($classe);
        $ordre = $classe->ordreEnseignement;
        $ecole = Ecole::findOrFail($classe->idEcole);

        $periode_col = $mois ? "mois" : "id_trimestre";
        $periode_val = $mois ?: $id_trimestre;

        // 2. Conduite
        $note_conduite = null;
        if ($ordre !== "fondamentale1" && !$mois) {
            $conduite = DB::table('conduite')
                ->where('id_eleve', $id)
                ->where('id_classe', $classe->id_classe)
                ->where('id_annee_scolaire', $id_annee)
                ->where('id_trimestre', $id_trimestre)
                ->first();
            $schoolId = (int) $classe->idEcole;
            $hasCalls = DB::table('controle_eleve')
                ->where('id_eleve', $id)
                ->where('id_classe', $classe->id_classe)
                ->where('id_annee_scolaire', $id_annee)
                ->where('id_trimestre', $id_trimestre)
                ->where('id_ecole', $schoolId)
                ->exists();

            if ($hasCalls) {
                $note_conduite = app(ConductNoteService::class)->syncStudent(
                    (int) $id,
                    (int) $classe->id_classe,
                    (int) $id_annee,
                    (int) $id_trimestre,
                    $schoolId
                );
            } elseif ($conduite) {
                $note_conduite = $conduite->note_conduite;
            }
        }

        // 3. Aperçu Info
        $apercu = DB::table('eleve as e')
            ->join('classe as c', 'e.id_classe', '=', 'c.id_classe')
            ->join('ligne_evaluation as le', 'le.id_eleve', '=', 'e.id_eleve')
            ->leftJoin('anneescolaire as ascol', 'le.id_annee_scolaire', '=', 'ascol.id_anneeScolaire')
            ->leftJoin('trimestre as t', 'le.id_trimestre', '=', 't.id_trimestre')
            ->select('e.*', 'c.nom_classe', 'c.ordreEnseignement', 'ascol.annee', 't.nom_trimestre')
            ->where('e.id_eleve', $id)
            ->where('le.id_annee_scolaire', $id_annee)
            ->where('le.' . $periode_col, $periode_val)
            ->first();

        if (!$apercu) return null;

        if ($mois) {
            $moisNoms = $this->moisOptions();
            $apercu->mois_nom = $moisNoms[intval($mois)] ?? 'Inconnu';
        }

        // 4. Matieres & Notes
        $matieres = DB::table('ligneclasse as lc')
            ->join('matiere as m', 'lc.id_matiere', '=', 'm.id_matiere')
            ->where('lc.id_classe', $classe->id_classe)
            ->select('m.nom_matiere', 'lc.coefficient as coef', 'lc.id_matiere')
            ->get();

        foreach ($matieres as $matiere) {
            $matiere->M_Class = DB::table('ligne_evaluation as le')
                ->join('note as n', 'le.id_note', '=', 'n.id_note')
                ->where('le.id_eleve', $id)
                ->where('le.id_matiere', $matiere->id_matiere)
                ->where(function ($query) {
                    $query->where('n.typeNote', 'devoir')
                        ->orWhereIn('n.codeNote', ['dv', 'devoir']);
                })
                ->where('le.' . $periode_col, $periode_val)
                ->where('le.id_annee_scolaire', $id_annee)
                ->avg('le.note') ?? 0;

            $matiere->M_Compo = (DB::table('ligne_evaluation as le')
                ->join('note as n', 'le.id_note', '=', 'n.id_note')
                ->where('le.id_eleve', $id)
                ->where('le.id_matiere', $matiere->id_matiere)
                ->where(function ($query) {
                    $query->where('n.typeNote', 'composition')
                        ->orWhereIn('n.codeNote', ['cp', 'composition']);
                })
                ->where('le.' . $periode_col, $periode_val)
                ->where('le.id_annee_scolaire', $id_annee)
                ->value('le.note') ?? 0) * 2;

            $matiere->NT10 = DB::table('ligne_evaluation as le')
                ->join('note as n', 'le.id_note', '=', 'n.id_note')
                ->where('le.id_eleve', $id)
                ->where('le.id_matiere', $matiere->id_matiere)
                ->where(function ($query) {
                    $query->where('n.typeNote', 'NT10')
                        ->orWhere('n.codeNote', 'NT10');
                })
                ->where('le.' . $periode_col, $periode_val)
                ->where('le.id_annee_scolaire', $id_annee)
                ->avg('le.note') ?? 0;

            // Calculations
            if ($ordre === "fondamentale1") {
                $matiere->M_Gle = floatval($matiere->NT10);
                $matiere->M_Coef = $matiere->M_Gle * 1;
            } else {
                $matiere->M_Gle = ($matiere->M_Class + $matiere->M_Compo) / 3;
                $matiere->M_Coef = $matiere->M_Gle * $matiere->coef;
            }
            $matiere->appreciation = $this->getAppreciation($matiere->M_Gle, $ordre);
        }

        // 5. Global Averages
        $totalCoef = ($ordre === "fondamentale1") ? $matieres->count() : $matieres->sum('coef');
        $totalNotes = ($ordre === "fondamentale1") ? $matieres->sum('M_Gle') : $matieres->sum('M_Coef');

        if ($ordre !== "fondamentale1" && $note_conduite !== null) {
            $totalCoef += 1;
            $totalNotes += floatval($note_conduite);
        }

        $moyenne_periode = $totalCoef ? $totalNotes / $totalCoef : 0;

        $rankData = $withRank ? $this->classAverages($classe, (int) $id_annee, $periode_col, $periode_val) : collect();
        $current_rang = $rankData->firstWhere('id_eleve', $id)['rang'] ?? null;
        $total_eleves = $rankData->count();
        $meilleure_moyenne = $rankData->first()['moyenne'] ?? 0;

        return [
            'apercu' => $apercu,
            'ecole' => $ecole,
            'matieres' => $matieres,
            'moyenne_periode' => $moyenne_periode,
            'moyenne_premier' => $meilleure_moyenne,
            'note_conduite' => $note_conduite,
            'appreciation_conduite' => $note_conduite ? $this->getAppreciation($note_conduite, $ordre) : null,
            'rang' => $current_rang,
            'total_eleves' => $total_eleves,
            'ordre' => $ordre
        ];
    }

    private function getAppreciation($moyenne, $ordre)
    {
        if ($ordre === "fondamentale1") {
            if ($moyenne >= 9) return "Très bien";
            if ($moyenne >= 8) return "Bien";
            if ($moyenne >= 7) return "Assez bien";
            if ($moyenne >= 5) return "Passable";
            return "Insuffisant";
        } else {
            if ($moyenne >= 16) return "Très Bien";
            if ($moyenne >= 14) return "Bien";
            if ($moyenne >= 12) return "Assez Bien";
            if ($moyenne >= 10) return "Passable";
            return "Insuffisant";
        }
    }

    private function classAverages(Classe $classe, int $idAnnee, string $periodeCol, $periodeVal)
    {
        $rows = Eleve::query()
            ->where('id_classe', $classe->id_classe)
            ->where('id_annee', $idAnnee)
            ->where('etat_dossier', 0)
            ->get()
            ->map(function ($eleve) use ($idAnnee, $periodeCol, $periodeVal) {
                $data = $this->getBulletinData($eleve->id_eleve, $idAnnee, $periodeCol === 'id_trimestre' ? $periodeVal : null, $periodeCol === 'mois' ? $periodeVal : null, false);
                return ['id_eleve' => $eleve->id_eleve, 'moyenne' => $data['moyenne_periode'] ?? 0];
            })
            ->filter(fn ($row) => $row['moyenne'] > 0)
            ->sortByDesc('moyenne')
            ->values();

        $lastMoyenne = null;
        $lastRang = 0;

        return $rows->map(function ($row, $index) use (&$lastMoyenne, &$lastRang) {
            $moyenne = round((float) $row['moyenne'], 2);
            if ($lastMoyenne !== null && $moyenne === $lastMoyenne) {
                $row['rang'] = $lastRang;
            } else {
                $row['rang'] = $index + 1;
                $lastRang = $index + 1;
            }
            $lastMoyenne = $moyenne;
            return $row;
        });
    }

    private function authorizeClasse(Classe $classe): void
    {
        $user = auth()->user();
        if ($user->droit !== 'SupAdmin' && (int) $classe->idEcole !== (int) (session('idEcole') ?: $user->idEcole)) {
            abort(403);
        }
    }

    private function moisOptions(): array
    {
        return [1 => "Janvier", 2 => "Février", 3 => "Mars", 4 => "Avril", 5 => "Mai", 6 => "Juin", 7 => "Juillet", 8 => "Août", 9 => "Septembre", 10 => "Octobre", 11 => "Novembre", 12 => "Décembre"];
    }
}
